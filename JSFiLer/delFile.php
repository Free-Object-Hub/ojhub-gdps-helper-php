<?php require_once '!chkpass.php';
if ($priority < 1) exit(-3);
$filePath = $_GET['name'];
if (file_exists($filePath) && !is_dir($filePath)) {
    unlink($filePath);
    include('findFiles.php');
} else {
    exit('-1');
}