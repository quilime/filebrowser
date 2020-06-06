<?php

if (0) { // set to 1 to display errors
  ini_set('display_errors', 1);
  ini_set('display_startup_errors', 1);
  error_reporting(E_ALL);
}

// folder to process. Default is root of current script (__FILE__).
// examples;
//   - script location (default): dirname(__FILE__)
//   - a few directories up: './../../'
//   - a specific directory: 'some/directory/elsewhere'
define('ROOT', dirname(__FILE__));

// settings
$_SETTINGS = array();
$_SETTINGS['ignoreDotfiles'] = true;
$_SETTINGS['dateFormat'] = 'F d, Y g:i A';
$_SETTINGS['lineNumbers'] = true;
$_SETTINGS['separateFolders'] = true;
$_SETTINGS['calcFolderSizes'] = false; // note: This is recursive! Be careful with large file trees.
$_SETTINGS['showFileSize'] = true;
$_SETTINGS['showFileType'] = true;
$_SETTINGS['showFileModDate'] = true;
$_SETTINGS['naturalSort'] = true; // Ignore case when sorting (default: true)




$_SORTMODE = (isset($_GET['N']) ? 'N' : 
             (isset($_GET['S']) ? 'S' : 
             (isset($_GET['T']) ? 'T' : 
             (isset($_GET['M']) ? 'M' : 'N'))));
$_SORTORDER = isset($_GET[$_SORTMODE]) ? $_GET[$_SORTMODE] : 'A';
$_PATH = isset($_GET['p']) ? $_GET['p'] : false;
$_TOTAL_SIZE = 0;

// verify path (don't allow ../'s)
$_PATH = preg_match("/\.\.\//", $_PATH) ? false : $_PATH;

function makeFileArray($cwd) {
  global $_SETTINGS;

  $folderArray = array();
  $foldersIter = new DirectoryIterator($cwd);
  foreach ($foldersIter as $fileinfo) {
    if (!$fileinfo->isDot() && $fileinfo->isDir()) {
      if ($_SETTINGS['ignoreDotfiles'] && substr($fileinfo->getFilename(), 0, 1) == '.') {
        continue;
      }
      $folderInfo = array();
      $folderInfo['name'] = $fileinfo->getFilename();
      $folderInfo['mtime'] = $fileinfo->getMTime();
      $folderInfo['type'] = 'Folder';
      $folderInfo['size'] = $_SETTINGS['calcFolderSizes'] ? getFolderSize($fileinfo->getRealPath()) : 0;
      $folderInfo['rowType'] = 'fr';
      $folderArray[] = $folderInfo;
    }
  }

  $fileArray = array();
  $filesIter = new DirectoryIterator($cwd);
  foreach ($filesIter as $fileinfo) {
    if ($fileinfo->isFile()) {
      if ($_SETTINGS['ignoreDotfiles'] && substr($fileinfo->getFilename(), 0, 1) == '.') {
        continue;
      }
      $fileInfo = array();
      $fileInfo['name'] = $fileinfo->getFilename();
      $fileInfo['mtime'] = $fileinfo->getMTime();
      $fileInfo['type'] = $fileinfo->getExtension();
      $fileInfo['size'] = $fileinfo->getSize();
      $fileInfo['rowType'] = 'fl';
      $fileArray[] = $fileInfo;
    }
  }

  $res = array();
  $res['folders'] = $folderArray;
  $res['files'] = $fileArray;

  return $res;
}


function getFolderSize($path) {
  $size = 0;
  foreach (glob(rtrim($path, '/') . '/*', GLOB_NOSORT) as $each) {
    $size += is_file($each) ? filesize($each) : getFolderSize($each);
  }
  return $size;
}


function renderFileList($cwd, $files) {
  global $_SETTINGS, $_TOTAL_SIZE;

  $totalFolders = sizeof($files['folders']);
  $totalFiles = sizeof($files['files']);
  $sortedFiles = sortFiles($files);

  $slash = '&nbsp;/&nbsp;';
  echo '<div class="header">';
  echo '<div class="breadcrumbs">';
  echo '<a href="' . $_SERVER['PHP_SELF'] . '">home</a>';

  // explode path into links
  $pathParts = explode("/", $cwd); 

  // path parts for breadcrumbs
  $pathCT = 0; // path counter
  foreach ($pathParts as $pt) {
    echo $slash;
    echo '<a href="?p=';
    for ($i = 0; $i <= $pathCT; $i++) {
      echo $pathParts[$i] . '/';
    }
    echo '">' . $pt . '</a>';
    $pathCT++;
  }

  echo '</div><div>';
  echo sprintf("%s Folders, %s Files", $totalFolders, $totalFiles);
  echo '</div></div>';

  // start of HTML file table    
  echo '<table class="filelist" cellspacing="0" border="0">';

  // sorting row
  renderRow('sort', $cwd);

  // parent directory row (if inside a path)
  if ($cwd)
    renderRow('parent', $cwd);

  $rowcount = 1; 

  // total byte size of the current tree
  $_TOTAL_SIZE = 0;    

  // rows of files
  foreach ($sortedFiles as $file) {
    renderRow($file['rowType'], $cwd, $rowcount, $file);
    $rowcount++;
    $_TOTAL_SIZE += $file['size'];
  }

  $_TOTAL_SIZE = formatSize($_TOTAL_SIZE);

  renderRow('footer');

  echo '</table>';
}


function formatSize($bytes, $type = 'fl') {
  global $_SETTINGS;

  if (is_integer($bytes) && $bytes > 0) {
    $formats = array("%d bytes", "%.1f kb", "%.1f mb", "%.1f gb", "%.1f tb");
    $logsize = min((int)(log($bytes) / log(1024)), count($formats) - 1);
    return sprintf($formats[$logsize], $bytes / pow(1024, $logsize));
  }

  // is a folder without calculated size
  else if ($type == 'fr' && !$_SETTINGS['calcFolderSizes']) {
    return '-';
  } else {
    return '0 bytes';
  }
}


function parentDir($path) {
  $p = dirname($path);
  return $p == '.' ? '' : $p . '/';
}


function renderRow($type, $path = null, $rowcount = null, $file = null) {

  global $_SETTINGS, $_SORTMODE, $_SORTORDER, $_TOTAL_SIZE;

  // alternating row styles
  $rnum = $rowcount ? ($rowcount % 2 == 0 ? 'r2' : 'r1') : '';

  $row = sprintf('<tr class="%s %s">', $type, $rnum);

  switch ($type) {
    
    // file / folder row
    case 'fl':
    case 'fr': 
      $row .= $_SETTINGS['lineNumbers'] ? '<td class="ln">' . $rowcount . '</td>' : '';
      $row .= '<td class="nm">';
      $row .= sprintf('<a href="%s">%s</a></td>', $type == 'fr' ? '?p=' . $path . $file['name'] . '/' : ROOT . $path . $file['name'], $file['name']);
      $row .= $_SETTINGS['showFileSize'] ? sprintf('<td class="sz">%s</td>', formatSize($file['size'], $type)) : '';
      $row .= $_SETTINGS['showFileType'] ? sprintf('<td class="tp">%s</td>',  $file['type']) : ''; 
      $row .= $_SETTINGS['showFileModDate'] ? sprintf('<td class="dt">%s</td>', date($_SETTINGS['dateFormat'], $file['mtime'])) : '';
      break;
                
    // sorting header    
    case 'sort':
      // sort order. Setting ascending or descending for sorting links
      $N = ($_SORTMODE == 'N') ? ($_SORTORDER == 'A' ? 'D' : 'A') : 'A';
      $S = ($_SORTMODE == 'S') ? ($_SORTORDER == 'A' ? 'D' : 'A') : 'A';
      $T = ($_SORTMODE == 'T') ? ($_SORTORDER == 'A' ? 'D' : 'A') : 'A';
      $M = ($_SORTMODE == 'M') ? ($_SORTORDER == 'A' ? 'D' : 'A') : 'A';

      $row .= $_SETTINGS['lineNumbers'] ? '<td class="ln">&nbsp;</td>' : '';
      $row .= sprintf('<td><a href="?N=%s&amp;p=%s">Name</a></td>', $N, $path);
      $row .= $_SETTINGS['showFileSize'] ? sprintf('<td class="sz"><a href="?S=%s&amp;p=%s">Size</a></td>', $S, $path) : '';
      $row .= $_SETTINGS['showFileType'] ? sprintf('<td class="tp"><a href="?T=%s&amp;p=%s">Type</a></td>', $T, $path) : '';
      $row .= $_SETTINGS['showFileModDate'] ? sprintf('<td class="dt"><a href="?M=%s&amp;p=%s">Last Modified</a></td>', $M, $path) : '';
      break;
                
    // parent directory row    
    case 'parent':
      $row .= $_SETTINGS['lineNumbers'] ? '<td class="ln">&laquo;</td>' : '';
      $row .= sprintf('<td class="nm"><a href="?p=%s">', parentDir($path));
      $row .= 'Parent Directory';
      $row .= '</a></td>';
      $row .= $_SETTINGS['showFileSize'] ? '<td class="sz">&nbsp;</td>' : '';
      $row .= $_SETTINGS['showFileType'] ? '<td class="tp">&nbsp;</td>' : '';
      $row .= $_SETTINGS['showFileModDate'] ? '<td class="dt">&nbsp;</td>' : '';
      break;
        
    // footer row
    case 'footer':
      $row .= $_SETTINGS['lineNumbers'] ? '<td class="ln">&nbsp;</td>' : '';
      $row .= '<td class="nm">&nbsp;</td>';
      $row .= $_SETTINGS['showFileSize'] ? sprintf('<td class="sz">%s</td>', $_TOTAL_SIZE) : '';
      $row .= $_SETTINGS['showFileType'] ? '<td class="tp">&nbsp;</td>' : '';
      $row .= $_SETTINGS['showFileModDate'] ? '<td class="dt">&nbsp;</td>' : '';
      break;
  }

  $row .= '</tr>';
  echo $row;
}

function sortFiles($filesArray) {
  global $_SETTINGS, $_SORTORDER;

  // sort folders on top
  if ($_SETTINGS['separateFolders']) {
    $sortedFolders = orderByColumn($filesArray['folders'], '2');
    $sortedFiles = orderByColumn($filesArray['files'], '1');
    if ($_SORTORDER == 'A') {
      ksort($sortedFolders);
      ksort($sortedFiles);
    } else {
      krsort($sortedFolders);
      krsort($sortedFiles);
    }
    $result = array_merge($sortedFolders, $sortedFiles);
  } 
  // merge folders and files together before sorting
  else {
    $f = array_merge($filesArray['folders'], $filesArray['files']);
    $result = orderByColumn($f, '1');
    $_SORTORDER == 'A' ? ksort($result) : krsort($result);
  }
  return $result;
}

function orderByColumn($input, $type) {
  global $_SETTINGS, $_SORTMODE;

  $result = array();
    
  // available sort columns
  $columnList = array(
    'N' => 'name',
    'S' => 'size',
    'T' => 'type',
    'M' => 'mtime'
  );
    
  // row count 
  // each array key gets $rowcount and $type 
  $rowcount = 0;
    
  // create new array with sort mode as the key
  foreach ($input as $key => $value) {
    // natural sort - make array keys lowercase
    if ($_SETTINGS['naturalSort']) {
      $col = $value[$columnList[$_SORTMODE]];
      $res = strtolower($col) . '.' . $rowcount . $type;
      $result[$res] = $value;
    }
    // regular sort - uppercase values get sorted on top
    else {
      $res = $value[$columnList[$_SORTMODE]] . '.' . $rowcount . $type;
      $result[$res] = $value;
    }
    $rowcount++;
  }
  return $result;
}

$files = makeFileArray(ROOT . '/' . $_PATH);

?>
<!DOCTYPE html>
<html>
<head>
<style type="text/css">

#DirectoryLister {
  font-family:sans-serif;
}
/* File Browser Table */
#DirectoryLister table {
  width:100%;
}
/* rows */
#DirectoryLister table tr td {
  padding:1px;
  font-size:12px;
}
#DirectoryLister a {
  text-decoration:none;
}
#DirectoryLister a:hover {
  text-decoration:underline;
}
/* rows */
#DirectoryLister table tr.fr td, #DirectoryLister table tr.fl td {
  border-top:1px solid #fff;
  border-bottom:1px solid #ddd;
}
/* folder row */
#DirectoryLister table tr.fr td.nm {
  font-weight:bold;
}
/* parent row */
#DirectoryLister table tr.parent {
  font-weight:bold;
}
#DirectoryLister table tr.parent td {
  border-bottom:1px solid #ccc;
  background:#efefd3;
}
/* header */
#DirectoryLister div.header {
  margin-bottom:10px;
  font-size:12px;
}
#DirectoryLister div.header .breadcrumbs {
  font-size:24px;
}
/* sorting row */
#DirectoryLister tr.sort td {
}
/* Columns */
/* line number */
#DirectoryLister table tr td.ln {
  border-left:1px solid #ccc;
  font-weight:normal;
  text-align:right;
  padding:0 10px 0 10px;
  width:10px;
  color: #999;
}
/* date */
#DirectoryLister table tr td.dt {
  border-right:1px solid #ccc;
}
/* footer row */
#DirectoryLister table tr.footer td {
  border:0;
  font-weight:bold;
}
/* sort row */
#DirectoryLister table tr.sort td {
  border:0;
  border-bottom:1px solid #ccc;
}
/* alternating Row Colors */
/* folders */
tr.fr.r1 {
  background-color:#eee;
}
tr.fr.r2 {
}
/* files */
tr.r1 {
  background-color:#eee;
}
 tr.r2 {
}

</style>  
</head>
<body>

  <div id="DirectoryLister">
<?php
  renderFileList($_PATH, $files);
?>
  </div>

</body>
</html>