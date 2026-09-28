<?php require_once '!chkpass.php';

function purgePath($string) {
    $lastSlashPos = strrpos($string, '/');
    if ($lastSlashPos !== false) {
        return substr($string, 0, $lastSlashPos);
    } else {
        return $string;
    }
}

$currentDirectory = isset($_GET['dir']) ? $_GET['dir'] : purgePath($_GET['name']).'/';

if ($currentDirectory == '')
    $currentDirectory = '../';

$returnFolders = '';
$returnFiles = '';

if (is_dir($currentDirectory)) {
    $files = glob($currentDirectory . '*');

    $folderFirst = true;
    $fileFirst = true;

    foreach ($files as $file) {
        if (is_dir($file)) {
            $returnFolders .= $folderFirst ? '' : ',';
            $folderFirst = false;

            $returnFolders .= '["'.$file.'/","'.basename($file).'"]';
        } else {
            $returnFiles .= $fileFirst ? '' : ',';
            $fileFirst = false;

            $returnFiles .= '["'.$file.'","'.basename($file).'",'.filesize($file).']';
        }
    }

    $return = '{"Folders":['.$returnFolders.'],"Files":['.$returnFiles.']}';

    exit($return);
} else {
    include 'editFile.php';
}
?>