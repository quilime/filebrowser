<?php

// display errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// folder to process. Default is root of current script (__FILE__).
$_ROOT = './../../'; 
$_PATH = $_GET['p'] ?? false;
$_IGNORE_DOTFILES = true;
$_DATE_FORMAT = 'F d, Y g:i A';

// verify path:  set to false if contains ../'s
$_PATH = preg_match("/\.\.\//", $_PATH) ? false : $_PATH;

function makeFileArray($cwd) {

  echo '$cwd:'. $cwd;
  // if ($this->verifyPath($path))  {
  //   // init
  //   $path = $root.$path;
  //   $dirArray = array();
  //   $folderArray = array();
  //   $folderInfo = array();
  //   $fileArray = array();
  //   $fileInfo = array();
      
  //   if ($handle = opendir($path)) {
  //     while (false !== ($file = readdir($handle))) {
  //       if ($file != '.' && $file != '..') {
  //         // show/hide hidden files
  //         if (!$this->SETTING['showHiddenFiles'] && substr($file, 0, 1) == '.') { continue; }
                      
  //         // is a folder
  //         if(is_dir($path.$file)) { 
  //           // store elements of folder in sub array
  //           $folderInfo['name']     = $file;
  //           $folderInfo['mtime'] = filemtime($path.$file);
  //           $folderInfo['type']  = 'Folder';
  //           $folderInfo['size']  = $this->SETTING['calcFolderSizes'] ? $this->folderSize($path.$file) : '-'; 
  //           $folderInfo['rowType'] = 'fr';
  //           $folderArray[] = $folderInfo;
  //         } else { 
  //           // is a file
  //           // store elements of file in sub array
  //           $fileInfo['name']  = $file;
  //           $fileInfo['mtime'] = filemtime($path.$file);
  //           $fileInfo['type']  = $this->SETTING['simpleType'] ? $this->getExtension($path.$file) : mime_content_type($path.$file);
  //           $fileInfo['size']  = filesize($path.$file);
  //           $fileInfo['rowType'] = 'fl';
  //           $fileArray[] = $fileInfo;
  //         }
  //       }
  //     }
  //     closedir($handle);    
  //   }    
  //   $dirArray['folders'] = $folderArray;
  //   $dirArray['files'] = $fileArray;
  //   return $dirArray;
  //   } else {
          
  //     }
  return array();
}    

$files = makeFileArray($_ROOT.$_PATH);

print_r($files);

echo "\n\nFolders\n\n";
$foldersIter = new DirectoryIterator(dirname($_ROOT));
foreach ($foldersIter as $fileinfo) {
  if (!$fileinfo->isDot() && $fileinfo->isDir()) {
    if ($_IGNORE_DOTFILES && substr($fileinfo->getFilename(), 0, 1) == '.') { continue; }
    print_r($fileinfo);
  }
}

echo "\n\nFiles\n\n";
$filesIter = new DirectoryIterator(dirname($_ROOT));
$files = Array();
foreach ($filesIter as $fileinfo) {
  if ($fileinfo->isFile()) {
    if ($_IGNORE_DOTFILES && substr($fileinfo->getFilename(), 0, 1) == '.') { continue; }
    print_r($fileinfo);
  }
}

?>


