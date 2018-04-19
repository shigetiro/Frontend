<?php
$user=  explode("/",$_SERVER['REQUEST_URI'])[3];
header("Content-Type: image/jpeg");
header("Content-Transfer-Encoding: binary");
$filename = "/avatars/$user.png";
if(filesize($filename) == 0) {
$filename = "/avatars/default";
$handle = fopen($filename, "r");
$contents = fread($handle, filesize($filename));
echo $contents;
    return;
}
$handle = fopen($filename, "r");
$contents = fread($handle, filesize($filename));
echo $contents;
?>