<?php
/*
* 
* File Browser Class
*
* Lists files in script directory, creating
* a browsable file tree with options.
*
* This class' proper use is to be included as
* an external file to the page that requires
* the directory list.
*
* Upon dropping this script into a folder,
* it will allow you to browse the contents.
* be sure to experiment with the the SETTINGS, 
* which start on line 41
*
* copyright @ 2005 Gabriel Dunne
* gdunne@quilime.com
*
* @param string $root : Directory to list
*                       Examples:
*                       '/'  : drive root
*                       './' : script root
*                       './Users/Gabe/Pictures/gallery1/' : some images
*    
* List files in a folder recursively with the option to browse.
*
* LICENSE: Redistribution and use in source and binary forms, with or
* without modification, are permitted provided that the following
* conditions are met: Redistributions of source code must retain the
* above copyright notice, this list of conditions and the following
* disclaimer. Redistributions in binary form must reproduce the above
* copyright notice, this list of conditions and the following disclaimer
* in the documentation and/or other materials provided with the
* distribution.
*
* THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED
* WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
* MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN
* NO EVENT SHALL CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
* INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
* BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS
* OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
* ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR
* TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE
* USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH
* DAMAGE.
*
*/
class DirectoryLister
{

    //  settings
    var    $SETTING = array();
    
    function DirectoryLister($root = './') 
    {        
    
        /* 
         * SETTINGS 
         */
    
        // html DOM id
        $this->SETTING['id'] = 'DirectoryLister';

        // allow the users to browse folders
        $this->SETTING['browse'] = true;
        
        // show footer 
        $this->SETTING['footer'] = true;
        
        // show header
        $this->SETTING['header'] = true;
        
        // show sorting header
        $this->SETTING['sort'] = true;
        
        // show/hide columns
        $this->SETTING['lineNumbers'] = true;
        $this->SETTING['showFileSize'] = true;
        $this->SETTING['showFileModDate'] = true;
        $this->SETTING['showFileType'] = true;
        
        // calculate folder sizes (increases processing time)
        $this->SETTING['calcFolderSizes'] = false;
        
        // display MIME type, or "simple" file type 
        // (MIME type increases processing time)
        $this->SETTING['simpleType'] = true;
        
        // open files in new windows    
        $this->SETTING['linkNewWin'] = true;
        
        // sort folders on top of files
        $this->SETTING['separateFolders'] = true;
        
        // natural sort files, as opposed to regular sort (files with capital
        // letters get sorted first)
        $this->SETTING['naturalSort'] = true;
        
        // show hidden files (files with a dot as the first char)
        $this->SETTING['showHiddenFiles'] = true;
        
        // date format. see the url 
        // http://us3.php.net/manual/en/function.date.php
        // for more information
        $this->SETTING['dateFormat'] = 'F d, Y g:i A';

        /*
         * END SETTINGS
         */

        // get path if browsing a tree
        $path = ($this->SETTING['browse']&&isset($_GET['p']))?$_GET['p']:FALSE;

        // get sorting vars from URL, if nothing is set, sort by N [file Name]
        $this->SETTING['sortMode'] = (isset($_GET['N']) ? 'N' : 
                                     (isset($_GET['S']) ? 'S' : 
                                      (isset($_GET['T']) ? 'T' : 
                                      (isset($_GET['M']) ? 'M' : 'N' ))));
        
        // get sort ascending or descending            
        $this->SETTING['sortOrder'] = 
            isset($_GET[$this->SETTING['sortMode']]) ? 
            $_GET[$this->SETTING['sortMode']] : 'A'; 

        // create array of files in tree
        $files = $this->makeFileArray($root, $path);
        
        // get size of arrays before sort
        $totalFolders = sizeof($files['folders']);
        $totalFiles = sizeof($files['files']);
        
        // sort files
        $files = $this->sortFiles($files);

        // display list
        
        // container div
        echo '<div id="'.$this->SETTING['id'].'">';
        
        // header
        echo $this->SETTING['header'] ? 
            $this->headerInfo($root, $path, $totalFolders, $totalFiles) : '';        
        
        // file list
        $this->fileList($root, $path, $files); 
        
        // end of container div
        echo '</div>';    
    }
    
   /* Create array out of files
    *
    * @param   string $root  : path root 
    * @param   string $path  : working dir
    * @param   mixed array $options : user options
    * @return     string array  : Array of files and folders inside the current 
    */
   function makeFileArray($root, $path) 
    {
        if (!function_exists('mime_content_type')) 
        {
           /* MIME Content Type
            *
            * @param   string $file : the extension
             * @return     string      : the files mime type
              * @note                 : alternate function written for UNIX 
              *                         environments when PHP function may 
              *                         not be available.
            */
           function mime_content_type($file) 
           {
               
               $file = escapeshellarg($file);
               $type = `file -bi $file`;
                  $expl = explode(";", $type);    
                  return $expl[0];
           }
        }
    
       if ($this->verifyPath($path)) 
        {
           // init
           $path             = $root.$path;
            $dirArray        = array();
            $folderArray     = array();
            $folderInfo        = array();
            $fileArray         = array();
            $fileInfo        = array();
        
            if ($handle = opendir($path)) 
            {
                while (false !== ($file = readdir($handle))) 
                {
                    if ($file != '.' && $file != '..') 
                    {
                        // show/hide hidden files
                        if (!$this->SETTING['showHiddenFiles'] && substr($file,0,1) == '.') 
                        {
                            continue; 
                        }
                        
                        // is a folder
                        if(is_dir($path.$file)) 
                        { 
                            // store elements of folder in sub array
                            $folderInfo['name']     = $file;
                            $folderInfo['mtime'] = filemtime($path.$file);
                            $folderInfo['type']  = 'Folder';
                            // calc folder size ?
                            $folderInfo['size']  = 
                                $this->SETTING['calcFolderSizes'] ? 
                                $this->folderSize($path.$file) : 
                                '-'; 
                            $folderInfo['rowType'] = 'fr';
                            $folderArray[]          = $folderInfo;
                        } 
                        // is a file
                        else 
                        { 
                            // store elements of file in sub array
                            $fileInfo['name']  = $file;
                            $fileInfo['mtime'] = filemtime($path.$file);
                            $fileInfo['type']  = $this->SETTING['simpleType'] ? 
                                $this->getExtension($path.$file) : 
                                mime_content_type($path.$file);
                            $fileInfo['size']  = filesize($path.$file);
                            $fileInfo['rowType'] = 'fl';
                            $fileArray[] = $fileInfo;
                        }
                    }
                }
                closedir($handle);    
            }    
            $dirArray['folders'] = $folderArray;
            $dirArray['files'] = $fileArray;
            return $dirArray;
        } 
        else 
        {
            echo 'Not a valid directory!';
            exit;        
        }
   }    

   /* Error Check Path for [../]'s
    *
    * @param   string $path : The path to parse
    * @return     bool        
    */
   function verifyPath($path) 
    {
       if (preg_match("/\.\.\//", $path)) // check for '../'s
        { 
           return false;
       } 
        else 
        {
           return true;
       }
   }

   /* Get the file extension from a filename
    *
    * @param     string $filename : filename from which to get extension
    * @return     string : the extension
    */
   function getExtension($filename) 
    {
        $justfile = explode("/", $filename);
        $justfile = $justfile[(sizeof($justfile)-1)];
       $expl = explode(".", $justfile);
        if(sizeof($expl)>1 && $expl[sizeof($expl)-1])
        {
           return $expl[sizeof($expl)-1];   
       }
        else
        {
            return '?';
        }
    }

   /* Get the parent directory of a path
    *
    * @param   string $path : the working dir
    * @return     string : the parent directory of the path
    */
   function parentDir($path) 
    {
       $expl = explode("/", substr($path, 0, -1));
       return  substr($path, 0, -strlen($expl[(sizeof($expl)-1)].'/'));   
   }

   /* Format Byte to Human-Readable Format
    *
    * @param   int $bytes : the byte size
    * @return     string : the ledgable result
    */
    function formatSize($bytes) 
    {
        if(is_integer($bytes) && $bytes > 0) 
        {
            $formats = array("%d bytes","%.1f kb","%.1f mb","%.1f gb","%.1f tb");
            $logsize = min((int)(log($bytes)/log(1024)), count($formats)-1);
            return sprintf($formats[$logsize], $bytes/pow(1024, $logsize));
        }
        // is a folder without calculated size
        else if(!is_integer($bytes) && $bytes == '-')
        {
            return '-';
        }
        else
        {
            return '0 bytes';
        }
    }
    
   /* Calculate the size of a folder recursivly
    *
    * @param   string $bytes : the byte size
    * @return     string : the ledgable result
    */    
    function folderSize($path) 
    {
        $size = 0;
        if ($handle = opendir($path)) 
        {
            while (false !== ($file = readdir($handle))) 
            {
                if ($file != '.' && $file != '..') 
                {
                    if(is_dir($path.'/'.$file)) 
                    {
                        $size += $this->folderSize($path.'/'.$file);
                    } 
                    else 
                    {
                        $size += filesize($path.'/'.$file);
                    }
                }
            }
        }
        return $size;
    }
    
   /* Header Info for current path
    *
    * @param    string $root : root directory
    * @param    string $path : working directory
    * @param    int $totalFolders : total folders in working dir
    * @param   int $totalFiles : total files in working dir
    * @return     string : HTML header <div>     
    */    
    function headerInfo($root, $path, $totalFolders, $totalFiles)
    {
        $slash = '&nbsp;/&nbsp;';
        $header  = '<div class="header">';
        $header .= '<div class="breadcrumbs">';
        $header .= '<a href="'.$_SERVER['PHP_SELF'].'">home</a>';
        
        // explode path into links
        $pathParts = explode("/", $path); 
        
        // path counter
        $pathCT = 0; 
        
        // path parts for breadcrumbs
        foreach($pathParts as $pt)
        {
            $header .= $slash; 
            $header .= '<a href="?p=';
            for($i=0;$i<=$pathCT;$i++)
            {
                $header .= $pathParts[$i].'/'; 
            }
            $header .= '">'.$pt.'</a>'; 
            $pathCT++;
        }
        
        $header .= '</div><div>';
        $header .= $totalFolders.' Folders';
        $header .= ', ';
        $header .= $totalFiles .' Files';
        $header .= '</div></div>'."\n";
        
        return $header;
    }
    

    
    /* Sort files
     *
     * @param     string array $files    : files/folders in the current tree
     * @param    string $sortMode : the current sorted column
     * @param    string $sortOrder : the current sort order. 'A' of 'D'
     * @return    string : the resulting sort
     */
    function sortFiles($files)
    {        
        // sort folders on top
        if($this->SETTING['separateFolders'])
        {
            $sortedFolders = $this->orderByColumn($files['folders'], '2');    
            
            $sortedFiles = $this->orderByColumn($files['files'], '1');
    
            // sort files depending on sort order
            if($this->SETTING['sortOrder'] == 'A')
            {
                ksort($sortedFolders);
                ksort($sortedFiles);
                $result = array_merge($sortedFolders, $sortedFiles);
            }
            else
            {
                krsort($sortedFolders);
                krsort($sortedFiles);
                $result = array_merge($sortedFiles, $sortedFolders);
            }
        }
        else
        // sort folders and files together
        {
            $files = array_merge($files['folders'], $files['files']);
            $result = $this->orderByColumn($files,'1');
                    
            // sort files depending on sort order
            $this->SETTING['sortOrder'] == 'A' ? ksort($result):krsort($result);
        }
        return $result;
    }
    
    /* Order By Column
     * 
     * @param    string array $input : the array to sort
     * @param    string $type : the type of array. 1 = files, 2 = folders
     */
    function orderByColumn($input, $type)
    {
        $column = $this->SETTING['sortMode'];
    
        $result = array();
        
        // available sort columns
        $columnList = array('N'=>'name', 
                            'S'=>'size', 
                            'T'=>'type', 
                            'M'=>'mtime');
        
        // row count 
        // each array key gets $rowcount and $type 
        // concatinated to account for duplicate array keys
        $rowcount = 0;
        
        // create new array with sort mode as the key
        foreach($input as $key=>$value)
        {
            // natural sort - make array keys lowercase
            if($this->SETTING['naturalSort'])
            {
                $col = $value[$columnList[$column]];
                $res = strtolower($col).'.'.$rowcount.$type;
                $result[$res] = $value;
            }
            // regular sort - uppercase values get sorted on top
            else 
            {
                $res = $value[$columnList[$column]].'.'.$rowcount.$type;
                $result[$res] = $value;
            }
            $rowcount++;
        }
        return $result;
    }
    
    
   /* List Files
    *
    * @param   string $path         : working dir
    * @param   string $files        : the files contined therin
    * @param   mixed array $options : user options
    * @return     none
    */
    function fileList($root, $path, $files)
    {
    
        // remove the './' from the path
        $root = substr($root, '2');
    
        // start of HTML file table    
        echo '<table class="filelist" cellspacing="0" border="0">';
        
        // sorting row
        echo $this->SETTING['sort'] ? $this->row('sort', null, $path) : '';
        
        // parent directory row (if inside a path)
        echo $path ? $this->row('parent', null, $path) : '';
        
        // total number of files
        $rowcount  = 1; 
        
        // total byte size of the current tree
        $totalsize = 0;    
        
        // rows of files
        foreach($files as $file) 
        {
            echo $this->row($file['rowType'], $root, $path, $rowcount, $file);
            $rowcount++; 
            $totalsize += $file['size'];
        }
        
        $this->SETTING['totalSize'] = $this->formatSize($totalsize);
        
        // footer row
        echo $this->SETTING['footer'] ? $this->row('footer') : '';
        
        // end of table
        echo '</table>';
    }
    
   /* file / folder row
    *
    * @param   string $type : either 'fr' or 'fl', representing a file row
    *                         or a folder row.
    * @param   string $path : working path
    * @param   int $rowcount : current count of the row for line numbering
    * @param   string $file : the file to be rendered on the row
    * @return     string : HTML table row
    *
    * notes: still need to edit code to wrap to 73 chars, hard to read at
    * the moment.
    *
    *
    *
    */    
    function row($type, $root=null, $path=null, $rowcount=null, $file=null)
    {
        // alternating row styles
        $rnum = $rowcount ? ($rowcount%2 == 0 ? ' r2' : ' r1') : null;
        
        //$emptyCell = '<td>&nbsp;</td>';
        
        // start row string variable to be returned
        $row = "\n".'<tr class="'.$type.$rnum.'">'."\n"; 
        
        switch($type)
        {
            // file / folder row
            case 'fl' :  
            case 'fr' : 
            
                // line number
                $row .= $this->SETTING['lineNumbers'] ? 
                        '<td class="ln">'.$rowcount.'</td>' : '';
                
                // filename
                $row .= '<td class="nm"><a href="';
                $row .= $this->SETTING['browse'] && $type == 'fr' ? 
                '?p='.$path.$file['name'].'/' : $root.$path.$file['name'];
                $row .= '">'.$file['name'].'</a></td>';
                
                // file size
                $row .= $this->SETTING['showFileSize'] ? 
                        '<td class="sz">'.$this->formatSize($file['size']).'
                         </td>' : '';
                
                // file type
                $row .= $this->SETTING['showFileType'] ? 
                        '<td class="tp">'.$file['type'].'</td>' : ''; 
                
                // date
                $row .= $this->SETTING['showFileModDate'] ? 
                        '<td class="dt">
                        '.date($this->SETTING['dateFormat'], $file['mtime']).'
                         </td>' : '';
                
                break;
                
            // sorting header    
            case 'sort' :
            
                // sort order. Setting ascending or descending for sorting links
                $N = ($this->SETTING['sortMode'] == 'N') ? 
                     ($this->SETTING['sortOrder'] == 'A' ? 'D' : 'A') : 'A';
                
                $S = ($this->SETTING['sortMode'] == 'S') ? 
                     ($this->SETTING['sortOrder'] == 'A' ? 'D' : 'A') : 'A';
                
                $T = ($this->SETTING['sortMode'] == 'T') ? 
                     ($this->SETTING['sortOrder'] == 'A' ? 'D' : 'A') : 'A';
                
                $M = ($this->SETTING['sortMode'] == 'M') ? 
                     ($this->SETTING['sortOrder'] == 'A' ? 'D' : 'A') : 'A';
                            
                $row .= $this->SETTING['lineNumbers'] ? 
                        '<td class="ln">&nbsp;</td>' : ''; 
                $row .= '<td><a href="?N='.$N.'&amp;p='.$path.'">Name</a></td>';
                $row .= $this->SETTING['showFileSize'] ?
                        '<td class="sz">
                         <a href="?S='.$S.'&amp;p='.$path.'">Size</a>
                         </td>' : '';
                $row .= $this->SETTING['showFileType'] ? 
                        '<td class="tp">
                         <a href="?T='.$T.'&amp;p='.$path.'">Type</a>
                         </td>' : '';
                $row .= $this->SETTING['showFileModDate'] ? 
                        '<td class="dt">
                         <a href="?M='.$M.'&amp;p='.$path.'">Last Modified</a>
                         </td>' : '';
                break;
                
            // parent directory row    
            case 'parent' : 
                $row .= $this->SETTING['lineNumbers'] ? 
                        '<td class="ln">&laquo;</td>' : '';
                $row .= '<td class="nm">
                         <a href="?p='.$this->parentDir($path).'">';
                $row .= 'Parent Directory';
                $row .= '</a></td>';
                $row .= $this->SETTING['showFileSize'] ? 
                        '<td class="sz">&nbsp;</td>' : '';
                $row .= $this->SETTING['showFileType'] ? 
                        '<td class="tp">&nbsp;</td>' : '';
                $row .= $this->SETTING['showFileModDate'] ? 
                        '<td class="dt">&nbsp;</td>' : '';
                break;
                
            // footer row
            case 'footer' : 
                $row .= $this->SETTING['lineNumbers'] ? 
                        '<td class="ln">&nbsp;</td>' : '';
                $row .= '<td class="nm">&nbsp;</td>';
                $row .= $this->SETTING['showFileSize'] ? 
                        '<td class="sz">'.$this->SETTING['totalSize'].'
                         </td>' : '';
                $row .= $this->SETTING['showFileType'] ? 
                        '<td class="tp">&nbsp;</td>' : '';
                $row .= $this->SETTING['showFileModDate'] ? 
                        '<td class="dt">&nbsp;</td>' : '';
                break;
        }
        
        $row .= '</tr>';
        return $row;
    }    
}

?>

<html>

<head>

<style type="text/css">

#DirectoryLister {
    font-family:sans-serif;
}

/* File Browser Table */
#DirectoryLister table
{
    width:100%;
}

/* rows */
#DirectoryLister table tr td 
{
    padding:1px; 
    font-size:12px;    
}

#DirectoryLister a 
{ 
    text-decoration:none;
}

#DirectoryLister a:hover 
{
    text-decoration:underline;
}

/* rows */
#DirectoryLister table tr.fr td, 
#DirectoryLister table tr.fl td 
{ 
    border-top:1px solid #fff;
    border-bottom:1px solid #ddd; 
}

/* folder row */
#DirectoryLister table tr.fr td.nm 
{ 
    font-weight:bold; 
}

/* parent row */
#DirectoryLister table tr.parent 
{ 
    font-weight:bold;
}
#DirectoryLister table tr.parent td 
{
    border-bottom:1px solid #ccc; 
    background:#efefd3;
}

/* header */
#DirectoryLister div.header 
{ 
    margin-bottom:10px; font-size:12px; 
}
#DirectoryLister div.header .breadcrumbs
{
    font-size:24px;
}

/* sorting row */

#DirectoryLister tr.sort td {  }

/* Columns */

/* line number */
#DirectoryLister table tr td.ln
{
    border-left:1px solid #ccc; 
    font-weight:normal; 
    text-align:right; 
    padding:0 10px 0 10px; width:10px;
    color: #999;
}

/* date  */
#DirectoryLister table tr td.dt 
{ 
    border-right:1px solid #ccc;
}

/* footer row */
#DirectoryLister table tr.footer td 
{
    border:0;    
    font-weight:bold;
}

/* sort row */
#DirectoryLister table tr.sort td 
{
    border:0; 
    border-bottom:1px solid #ccc; 
}

/* alternating Row Colors */
/* folders */
tr.fr.r1 
{
    background-color:#eee;
}
tr.fr.r2 { }
/* files */
tr.r1 
{
    background-color:#eee; 
}
tr.r2 {  }

</style>

</head>

<body>

<? 
    // list files in current directory
    new DirectoryLister();  
?>

</body>

</html>
