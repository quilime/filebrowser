<?php

include 'File_Browser.php';
$filebrowser = new File_Browser( array (
	'title' => 'media'
,   'root'  => './files/'
, 	'webroot' => 'http://media.quilime.com/'
));
$filebrowser->parent_dir = true;

?>

<html>
<head>
<link rel="StyleSheet" href="style.css" TYPE="text/css" />
<title><?=$filebrowser->title;?>/<?=$filebrowser->p; ?></title>

</head>
<body>

    <div id="nav">
        <div id="logo">
        <?php echo $filebrowser->logo; ?>
        </div>
        <ul id="breadcrumbs">
        <?php echo $filebrowser->breadcrumbs; ?>
        </ul>
    </div>

    <div id="content">
    <?php $filebrowser->html(); ?>
    <div>

</body>
</html>
