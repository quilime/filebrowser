<?php

// display errors
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// folder to process. Default is root of current script (__FILE__).
/* examples;
 * script location (default): dirname(__FILE__)
 * a few directories up: './../../'
 * a specific directory: 'some/directory/elsewhere'
 */ 
$_ROOT = dirname(__FILE__);

// settings
$_SETTINGS = array();
$_SETTINGS['ignoreDotfiles'] = true;
$_SETTINGS['dateFormat'] = 'F d, Y g:i A';
$_SETTINGS['calcFolderSizes'] = true;
$_SETTINGS['lineNumbers'] = true;
$_SETTINGS['showFileSize'] = true;
$_SETTINGS['showFileType'] = true;
$_SETTINGS['showFileModDate'] = true;
$_SETTINGS['separateFolders'] = true;
$_SETTINGS['naturalSort'] = true;


// get sorting vars from URL, if nothing is set, sort by N [file Name]
$_SORTMODE = (isset($_GET['N']) ? 'N' : 
             (isset($_GET['S']) ? 'S' : 
             (isset($_GET['T']) ? 'T' : 
             (isset($_GET['M']) ? 'M' : 'N' ))));

// get sort ascending or descending            
$_SORTORDER = isset($_GET[$_SORTMODE]) ? $_GET[$_SORTMODE] : 'A'; 

$_PATH = $_GET['p'] ?? false;

$_TOTAL_SIZE = 0;

// verify path:  set to false if contains ../'s
$_PATH = preg_match("/\.\.\//", $_PATH) ? false : $_PATH;

function makeFileArray($cwd) {

  global $_SETTINGS;

  $folderArray = array();
  $foldersIter = new DirectoryIterator($cwd);
  foreach ($foldersIter as $fileinfo) {
    if (!$fileinfo->isDot() && $fileinfo->isDir()) {
      if ($_SETTINGS['ignoreDotfiles'] && substr($fileinfo->getFilename(), 0, 1) == '.') { continue; }
      $folderInfo = array();
      $folderInfo['name'] = $fileinfo->getFilename();
      $folderInfo['mtime'] = $fileinfo->getMTime();
      $folderInfo['type'] = 'Folder';
      $folderInfo['size'] = getFolderSize($fileinfo->getPath()); 
      $folderInfo['rowType'] = 'fr';
      $folderArray[] = $folderInfo;
    }
  }

  $fileArray = array();
  $filesIter = new DirectoryIterator($cwd);
  foreach ($filesIter as $fileinfo) {
    if ($fileinfo->isFile()) {
      if ($_SETTINGS['ignoreDotfiles'] && substr($fileinfo->getFilename(), 0, 1) == '.') { continue; }
      $fileInfo = array();
      $fileInfo['name'] = $fileinfo->getFilename();
      $fileInfo['mtime'] = $fileinfo->getMTime();
      $fileInfo['type'] = $fileinfo->getExtension();
      $fileInfo['size']  = $fileinfo->getSize();
      $fileInfo['rowType'] = 'fl';
      $fileArray[] = $fileInfo;      
    }
  }  

  $dirArray = array();
  $dirArray['folders'] = $folderArray;
  $dirArray['files'] = $fileArray;  

  return $dirArray;
}    

// TODO calculate folder size
function getFolderSize($path){
  
  global $_SETTINGS;

  if (!$_SETTINGS['calcFolderSizes']) return 0;

        $size = 0;
        if ($handle = opendir($path))  {
            while (false !== ($file = readdir($handle))) {
                if ($file != '.' && $file != '..') {
                    if(is_dir($path.'/'.$file)) {
                        $size += getFolderSize($path.'/'.$file);
                    } 
                    else {
                        $size += filesize($path.'/'.$file);
                    }
                }
            }
        }
        return $size;  
}

function renderHeader() {
    // function headerInfo($root, $path, $totalFolders, $totalFiles)
    
  global $_PATH, $_TOTAL_FOLDERS, $_TOTAL_FILES;  

  $slash = '&nbsp;/&nbsp;';
  echo '<div class="header">';
  echo '<div class="breadcrumbs">';
  echo '<a href="'.$_SERVER['PHP_SELF'].'">home</a>';

  // explode path into links
  $pathParts = explode("/", $_PATH); 

  // path counter
  $pathCT = 0; 

  // path parts for breadcrumbs
  foreach($pathParts as $pt)
  {
      echo $slash; 
      echo '<a href="?p=';
      for($i = 0; $i <= $pathCT; $i++) {
          echo $pathParts[$i] . '/'; 
      }
      echo '">' . $pt . '</a>'; 
      $pathCT++;
  }

  echo '</div><div>';
  echo $_TOTAL_FOLDERS .' Folders';
  echo ', ';
  echo $_TOTAL_FILES .' Files';
  echo '</div></div>'."\n";
}


function renderFileList($cwd, $files) {

  global $_SETTINGS, $_ROOT, $_TOTAL_SIZE;

  // remove the './' from the path
  // $root = substr($_SETTINGS['ROOT'], '2');
  // $root = $_SETTINGS['root'];

  // start of HTML file table    
  echo '<table class="filelist" cellspacing="0" border="0">';

  // sorting row
  renderRow('sort', $cwd);

  // parent directory row (if inside a path)
  if ($cwd) 
    renderRow('parent', $cwd);

  // total number of files
  $rowcount  = 1; 

  // total byte size of the current tree
  $_TOTAL_SIZE = 0;    

  // rows of files
  foreach($files as $file) {
      renderRow($file['rowType'], $cwd, $rowcount, $file);
      $rowcount++; 
      $_TOTAL_SIZE += $file['size'];
  }

  $_TOTAL_SIZE = formatSize($_TOTAL_SIZE); // $this->SETTING['totalSize'] = $this->formatSize($totalsize);

  renderRow('footer');

  echo '</table>';
}


function formatSize($bytes, $type = 'fl') 
{

  global $_SETTINGS;

  if(is_integer($bytes) && $bytes > 0) {
    $formats = array("%d bytes","%.1f kb","%.1f mb","%.1f gb","%.1f tb");
    $logsize = min((int)(log($bytes)/log(1024)), count($formats)-1);
    return sprintf($formats[$logsize], $bytes/pow(1024, $logsize));
  }

  // is a folder without calculated size
  else if($type = 'fr' && !$_SETTINGS['calcFolderSizes']) { 
    return '-';
  }
  else {
    return '0 bytes';
  }
}

   function parentDir($path) {
       $expl = explode("/", substr($path, 0, -1));
       return  substr($path, 0, -strlen($expl[(sizeof($expl)-1)].'/'));   
   }

function renderRow($type, $path=null, $rowcount=null, $file=null) {

  global $_ROOT, $_SETTINGS, $_SORTMODE, $_SORTORDER, $_TOTAL_SIZE;

  // alternating row styles
  $rnum = $rowcount ? ($rowcount%2 == 0 ? ' r2' : ' r1') : null;
        
  //$emptyCell = '<td>&nbsp;</td>';
        
  // start row string variable to be returned
  $row = "\n".'<tr class="'.$type.$rnum.'">'."\n"; 
        
  switch($type) {
    
    // file / folder row
    case 'fl' :  
    case 'fr' : 
      // line number
      $row .= $_SETTINGS['lineNumbers'] ? '<td class="ln">'.$rowcount.'</td>' : '';
      // filename
      $row .= '<td class="nm">';
      $row .= '<a href="';
      $row .= $type == 'fr' ? '?p=' . $path . $file['name'] . '/' :  $_ROOT . $path . $file['name'];
      $row .= '">'.$file['name'].'</a></td>';
      // folder size
      $row .= $_SETTINGS['showFileSize'] ? '<td class="sz">' . formatSize($file['size'], $type).'</td>' : '';
      // file type
      $row .= $_SETTINGS['showFileType'] ? '<td class="tp">'.$file['type'].'</td>' : ''; 
      // date
      $row .= $_SETTINGS['showFileModDate'] ? '<td class="dt">'. date($_SETTINGS['dateFormat'], $file['mtime']).'</td>' : '';
      break;
                
    // sorting header    
    case 'sort' :
      // sort order. Setting ascending or descending for sorting links
      $N = ($_SORTMODE == 'N') ? ($_SORTORDER == 'A' ? 'D' : 'A') : 'A';
      $S = ($_SORTMODE == 'S') ? ($_SORTORDER == 'A' ? 'D' : 'A') : 'A';
      $T = ($_SORTMODE == 'T') ? ($_SORTORDER == 'A' ? 'D' : 'A') : 'A';
      $M = ($_SORTMODE == 'M') ? ($_SORTORDER == 'A' ? 'D' : 'A') : 'A';

      $row .= $_SETTINGS['lineNumbers'] ? '<td class="ln">&nbsp;</td>' : ''; 
      $row .= '<td><a href="?N='.$N.'&amp;p='.$path.'">Name</a></td>';
      $row .= $_SETTINGS['showFileSize'] ?'<td class="sz"><a href="?S='.$S.'&amp;p='.$path.'">Size</a></td>' : '';
      $row .= $_SETTINGS['showFileType'] ? '<td class="tp"><a href="?T='.$T.'&amp;p='.$path.'">Type</a></td>' : '';
      $row .= $_SETTINGS['showFileModDate'] ? '<td class="dt"><a href="?M='.$M.'&amp;p='.$path.'">Last Modified</a></td>' : '';
      break;
                
    // parent directory row    
    case 'parent' : 
        $row .= $_SETTINGS['lineNumbers'] ? '<td class="ln">&laquo;</td>' : '';
        $row .= '<td class="nm"><a href="?p='.parentDir($path).'">';
        $row .= 'Parent Directory';
        $row .= '</a></td>';
        $row .= $_SETTINGS['showFileSize'] ? '<td class="sz">&nbsp;</td>' : '';
        $row .= $_SETTINGS['showFileType'] ? '<td class="tp">&nbsp;</td>' : '';
        $row .= $_SETTINGS['showFileModDate'] ? '<td class="dt">&nbsp;</td>' : '';
        break;
        
    // footer row
    case 'footer' : 
        $row .= $_SETTINGS['lineNumbers'] ? '<td class="ln">&nbsp;</td>' : '';
        $row .= '<td class="nm">&nbsp;</td>';
        $row .= $_SETTINGS['showFileSize'] ? '<td class="sz">'.$_TOTAL_SIZE.'</td>' : '';
        $row .= $_SETTINGS['showFileType'] ? '<td class="tp">&nbsp;</td>' : '';
        $row .= $_SETTINGS['showFileModDate'] ? '<td class="dt">&nbsp;</td>' : '';
        break;
  }
        
  $row .= '</tr>';
  echo $row;
}

function sortFiles($files) {        
  global $_SETTINGS, $_SORTORDER;
  // sort folders on top
  if($_SETTINGS['separateFolders']) {
    $sortedFolders = orderByColumn($files['folders'], '2');            
    $sortedFiles = orderByColumn($files['files'], '1');

    // sort files depending on sort order
    if($_SORTORDER == 'A') {
      ksort($sortedFolders);
      ksort($sortedFiles);
      $result = array_merge($sortedFolders, $sortedFiles);
    } else {
      krsort($sortedFolders);
      krsort($sortedFiles);
      $result = array_merge($sortedFiles, $sortedFolders);
    }
  } else {
    // sort folders and files together
    $files = array_merge($files['folders'], $files['files']);
    $result = $this->orderByColumn($files,'1');
                
    // sort files depending on sort order
    $_SORTORDER == 'A' ? ksort($result):krsort($result);
  }
  return $result;
}

function orderByColumn($input, $type) {
  global $_SETTINGS, $_SORTMODE;


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
  foreach($input as $key=>$value) {
        // natural sort - make array keys lowercase
    if($_SETTINGS['naturalSort']) {
      $col = $value[$columnList[$_SORTMODE]];
      $res = strtolower($col).'.'.$rowcount.$type;
      $result[$res] = $value;
    }
    // regular sort - uppercase values get sorted on top
    else {
      $res = $value[$columnList[$_SORTMODE]].'.'.$rowcount.$type;
      $result[$res] = $value;
    }
    $rowcount++;
  }
  return $result;
}

$files = makeFileArray($_ROOT . '/' . $_PATH);
$_TOTAL_FOLDERS = sizeof($files['folders']);
$_TOTAL_FILES = sizeof($files['files']);
$files = sortFiles($files);

?>
<!DOCTYPE html>
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

  <div id="DirectoryLister">
<?php 
  renderHeader(); 
  renderFileList($_PATH, $files);
?>
  </div>

</body>
</html>