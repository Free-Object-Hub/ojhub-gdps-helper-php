<?php

require_once '!chkpass.php';

error_reporting(0);

if ($priority < 1) exit(-3);

$path = $_GET['dir'];
if (isset($_POST['folders']))
    $folders = explode('|', $_POST['folders']);
    if ($folders)
        foreach ($folders as $folder) {
            $folderPath = __DIR__.'/'.$path.$folder;
            if (!file_exists($folderPath))
                mkdir($folderPath, 0777, true);
        }

if (isset($_POST['paths']))
    foreach ($_POST['paths'] as $key => $filePath) {
        $destination = $path . $filePath;
        
        $dirPath = dirname($destination);
        if (!file_exists($dirPath)) {
            mkdir($dirPath, 0777, true);
        }
        
        file_put_contents($destination, $_POST['fileContents'][$key]);
    }

if (isset($_FILES['files']))
    foreach ($_FILES['files']['tmp_name'] as $key => $tempFilePath) {
        $fileName = $_FILES['files']['name'][$key];
        $destination = $path . $fileName;

        if ($_FILES['files']['error'][$key] === UPLOAD_ERR_OK)
            move_uploaded_file($tempFilePath, $destination);
    }

include 'findFiles.php';