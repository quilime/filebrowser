<?php

require_once('../MediaBrowser/MediaBrowser.php')

?>
<!DOCTYPE html>
<head>
<style type="text/css">
html {
font-family:sans-serif;
font-size:12px;
line-height:18px;
}

body {
color:#999;
margin:40px 10px 80px 50px;
}

a {
color:#000;
text-decoration:none;
}

a:hover {
color:#f04 !important;
}

h1 {
font-weight:400;
margin-bottom:2em;
}

ul,ol,li {
list-style-type:none;
margin:0;
padding:0;
}

h3 {
font-size:1em;
border-bottom:1px solid #ddd;
margin:0 50px 1em 0;
}

#logo {
position:absolute;
top:20px;
left:-36px;
}

#nav {
font-size:22px;
padding-bottom:10px;
}

#nav li {
display:inline-block;
}

#breadcrumbs {
list-style-type:none;
}

#content {
margin-top:30px;
}

#media {
padding-bottom:5em;
}

#media li {
width:200px;
float:left;
margin-right:20px;
list-style-type:none;
}

#media li img {
width:100%;
}

#media li a span {
visibility:hidden;
font-style:italic;
}

#media li a:hover span {
visibility:visible;
}

#media li a img {
border:1px solid #ddd;
margin-top:30px;
}

.audio a {
font-size:0.75em;
font-weight:700;
font-style:italic;
margin-left:0.5em;
color:#900;
}

#readme {
color:#009;
margin-bottom:2em;
padding:2em 0;
}

#readme h2 {
margin-top:0;
font-size:1.2em;
}

#list li {
color:#aaa;
}

#list li.Folder {
padding:1px;
}
#list li.Folder span {
    font-weight:bold;
    background:#ddd;
    display:inline-block;
    padding:2px 5px;
}
#list li.Folder span:hover {
    background:#eee;
}

.parent {
font-weight:700;
margin-top:4em;
padding-bottom:1em;
display:none;
}

.parent a {
color:#999;
}

.parent a:hover {
color:#444;
}

a:visited,#media li a {
color:#666;
}
</style>

<title><?=$MediaBrowser->title;?>/<?=$MediaBrowser->p; ?></title>

</head>
<body>

    <div id="nav">
        <div id="logo">
        <?php echo $MediaBrowser->logo; ?>
        </div>
        <ul id="breadcrumbs">
        <?php echo $MediaBrowser->breadcrumbs; ?>
        </ul>
    </div>

    <div id="content">
    <?php $MediaBrowser->html(); ?>
    <div>

</body>
</html>