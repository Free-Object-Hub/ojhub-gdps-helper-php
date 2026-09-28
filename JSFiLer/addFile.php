<?php require_once '!chkpass.php';
if ($priority < 1) exit(-3);
$filePath = $_GET['name'];
$file = fopen($filePath, 'w');
if ($file) {
    fclose($file);
    include('findFiles.php');
} else {
    exit('-1');
}