<?php

require_once('../DirectoryLister/DirectoryLister.php')

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
// settings
\quilime\DirList\setting('calcFolderSizes', false); // note: This is recursive! Be careful with large file trees.
\quilime\DirList\setting('showLineNumbers', true); // hide/show line numbers
\quilime\DirList\setting('showFileSize', true); // hide/show file size
\quilime\DirList\setting('showFileType', true); // hide/show file type (based on extension)
\quilime\DirList\setting('showFileModDate', true); // hide/show modification date
\quilime\DirList\setting('dateFormat', 'F d, Y g:i A'); // modification date format
\quilime\DirList\setting('naturalSort', true); // Ignore case when sorting (default: true)
\quilime\DirList\setting('separateFolders', true); // seperate folders in sort (true = folders on top)
\quilime\DirList\setting('ignoreDotfiles', true); // ignore dotfiles (hidden files) and folders like '.DS_Store' or '.git/'
\quilime\DirList\setting('excludes', [basename(__FILE__), 'DirectoryLister.php']); // ignore files and folders

// Folder to Process
// examples;

// Default) -- resolves to current folder, ie, dirname(__FILE__)
\quilime\DirList\renderFileList();

// A few directories up: 
// \quilime\DirList\renderFileList('./../../');

// A specific directory: 
// \quilime\DirList\renderFileList('./some/directory/elsewhere');

?>
</div>
</body>
</html>