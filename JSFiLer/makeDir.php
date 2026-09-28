<?php require_once '!chkpass.php';
if ($priority < 1) exit(-3);
$folderPath = $_GET['name'];
if (!file_exists($folderPath)) {
    mkdir($folderPath, 0777, true);
    include('findFiles.php');
} else {
    exit('-1');
}