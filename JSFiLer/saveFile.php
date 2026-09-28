<?php require_once '!chkpass.php';

if ($priority < 1) exit(-3);
if ($_POST['file'] == null)
    exit('-1');

$file = $_POST['file'];


if (file_put_contents($_GET['dir'], $file))
    echo file_get_contents($_GET['dir']);