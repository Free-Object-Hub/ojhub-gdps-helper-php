<?php require_once '!chkpass.php';
$old = $_GET['dir'].'/'.$_GET['old'];
$new = $_GET['dir'].'/'.$_GET['new'];

if (file_exists($old)) {
    if (rename($old, $new)) {
        include 'findFiles.php';
        exit();
    }
}

exit('-1');