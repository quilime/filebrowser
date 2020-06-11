<?php
/**
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
 * @category    File Browser
 * @author      Gabriel Dunne <gdunne [at] quilime [dot] com>
 * @copyright   2006-2020 Gabriel Dunne
 * @license     http://www.opensource.org/licenses/bsd-license.php
 * @link        http://quilime.com 
 * @source      https://github.com/quilime/filebrowser
 */

namespace quilime\DirList {

// START USER CONFIGURABLE OPTIONS
// Edit these settings for your configuration
$_SETTINGS = array();
$_SETTINGS['calcFolderSizes'] = false; // note: This is recursive! Be careful with large file trees.
$_SETTINGS['showLineNumbers'] = true; // hide/show line numbers
$_SETTINGS['showFileSize'] = true; // hide/show file size
$_SETTINGS['showFileType'] = true; // hide/show file type (based on extension)
$_SETTINGS['showFileModDate'] = true; // hide/show modification date
$_SETTINGS['dateFormat'] = 'F d, Y g:i A'; // modification date formate
$_SETTINGS['naturalSort'] = true; // Ignore case when sorting (default: true)
$_SETTINGS['separateFolders'] = true; // seperate folders in sort (true = folders on top)
$_SETTINGS['ignoreDotfiles'] = true; // ignore dotfiles (hidden files) and folders like '.DS_Store' or '.git/'
$_SETTINGS['excludes'] = array(); // ignore files and folders
// END USER CONFIGURABLE OPTIONS

$_SORTMODE = (isset($_GET['N']) ? 'N' : 
             (isset($_GET['S']) ? 'S' : 
             (isset($_GET['T']) ? 'T' : 
             (isset($_GET['M']) ? 'M' : 'N'))));
$_SORTORDER = isset($_GET[$_SORTMODE]) ? $_GET[$_SORTMODE] : 'A';


function setting($key, $value) {
  global $_SETTINGS;
  $_SETTINGS[$key] = $value;
}

function formatSize($bytes) {
  $formats = array("%d bytes", "%.1f kb", "%.1f mb", "%.1f gb", "%.1f tb");
  $logsize = min((int)(log($bytes) / log(1024)), count($formats) - 1);
  return sprintf($formats[$logsize], $bytes / pow(1024, $logsize));
}


function parentDir($path) {
  $p = dirname($path);
  return $p == '.' ? '' : $p . '/';
}


function getFolderSize($path) {
  $size = 0;
  foreach (glob(rtrim($path, '/') . '/*', GLOB_NOSORT) as $each) {
    $size += is_file($each) ? filesize($each) : getFolderSize($each);
  }
  return $size;
}


function makeFileArray($cwd) {
  global $_SETTINGS;

  $folderArray = array();
  $foldersIter = new \DirectoryIterator($cwd);
  foreach ($foldersIter as $fileinfo) {
    if (!$fileinfo->isDot() && $fileinfo->isDir()) {
      if ($_SETTINGS['ignoreDotfiles'] && substr($fileinfo->getFilename(), 0, 1) == '.') {
        continue;
      }
      if (in_array($fileinfo->getFilename(), $_SETTINGS['excludes'])) {
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
  $filesIter = new \DirectoryIterator($cwd);
  foreach ($filesIter as $fileinfo) {
    if ($fileinfo->isFile()) {
      if ($_SETTINGS['ignoreDotfiles'] && substr($fileinfo->getFilename(), 0, 1) == '.') {
        continue;
      }
      if (in_array($fileinfo->getFilename(), $_SETTINGS['excludes'])) {
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

function getPath() {
  $p = isset($_GET['p']) ? $_GET['p'] : false;
  // verify path (don't allow ../'s)
  return preg_match("/\.\.\//", $_PATH) ? false : $p;
}

function renderFileList($root = null) {

  global $_SETTINGS;

  $cwd = getPath();
  $root = $root == null ? dirname(__FILE__) : $root;
  $path = $root . '/' . $cwd;

  if (file_exists($path)) {
    $files = makeFileArray($path);
  } else {
    echo "Invalid Path";
    exit;
  }

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
  echo renderRow('sort', $cwd);

  // parent directory row (if inside a path)
  if ($cwd)
    echo renderRow('parent', $root, $cwd);

  $rowcount = 1; 

  // total byte size of the current tree
  $totalSize = 0;    

  // rows of files
  foreach ($sortedFiles as $file) {
    echo renderRow($file['rowType'], $root, $cwd, $rowcount, $file);
    $rowcount++;
    $totalSize += $file['size'];
  }

  // footer
  echo renderRow('footer', null, null, null, formatSize($totalSize));

  echo '</table>';
}


function renderRow($type, $root, $path = null, $rowcount = null, $file = null, $content = null) {
  global $_SETTINGS, $_SORTMODE, $_SORTORDER;

  $rnum = $rowcount ? ($rowcount % 2 == 0 ? 'r2' : 'r1') : '';  // alternating row styles

  $row = sprintf('<tr class="%s %s">', $type, $rnum);

  switch ($type) {
    
    // file / folder row
    case 'fl':
    case 'fr':
      $fsize = formatSize($file['size']);
      if ($type == 'fr' && !$_SETTINGS['calcFolderSizes']) $fsize = '-'; // replace '0' with '-' for folders unless calculating size
      $row .= $_SETTINGS['showLineNumbers'] ? '<td class="ln">' . $rowcount . '</td>' : '';
      $row .= '<td class="nm">';
      $row .= sprintf('<a href="%s">%s</a></td>', $type == 'fr' ? '?p=' . $path . $file['name'] . '/' : $root . $path . $file['name'], $file['name']);
      $row .= $_SETTINGS['showFileSize'] ? sprintf('<td class="sz">%s</td>', $fsize) : '';
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

      $row .= $_SETTINGS['showLineNumbers'] ? '<td class="ln">&nbsp;</td>' : '';
      $row .= sprintf('<td><a href="?N=%s&amp;p=%s">Name</a></td>', $N, $path);
      $row .= $_SETTINGS['showFileSize'] ? sprintf('<td class="sz"><a href="?S=%s&amp;p=%s">Size</a></td>', $S, $path) : '';
      $row .= $_SETTINGS['showFileType'] ? sprintf('<td class="tp"><a href="?T=%s&amp;p=%s">Type</a></td>', $T, $path) : '';
      $row .= $_SETTINGS['showFileModDate'] ? sprintf('<td class="dt"><a href="?M=%s&amp;p=%s">Last Modified</a></td>', $M, $path) : '';
      break;
                
    // parent directory row    
    case 'parent':
      $row .= $_SETTINGS['showLineNumbers'] ? '<td class="ln">&laquo;</td>' : '';
      $row .= sprintf('<td class="nm"><a href="?p=%s">', parentDir($path));
      $row .= 'Parent Directory';
      $row .= '</a></td>';
      $row .= $_SETTINGS['showFileSize'] ? '<td class="sz">&nbsp;</td>' : '';
      $row .= $_SETTINGS['showFileType'] ? '<td class="tp">&nbsp;</td>' : '';
      $row .= $_SETTINGS['showFileModDate'] ? '<td class="dt">&nbsp;</td>' : '';
      break;
        
    // footer row
    case 'footer':
      $row .= $_SETTINGS['showLineNumbers'] ? '<td class="ln">&nbsp;</td>' : '';
      $row .= '<td class="nm">&nbsp;</td>';
      $row .= $_SETTINGS['showFileSize'] ? sprintf('<td class="sz">%s</td>', $content) : '';
      $row .= $_SETTINGS['showFileType'] ? '<td class="tp">&nbsp;</td>' : '';
      $row .= $_SETTINGS['showFileModDate'] ? '<td class="dt">&nbsp;</td>' : '';
      break;
  }

  $row .= '</tr>';
  return $row;
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
  $columnList = array('N' => 'name', 'S' => 'size', 'T' => 'type', 'M' => 'mtime');
    
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
} // END quilime\DirList
?>