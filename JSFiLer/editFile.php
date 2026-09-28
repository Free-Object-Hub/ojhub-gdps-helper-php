<?php require_once '!chkpass.php';

//if (str_contains($_GET['dir'], '../..'))
    //exit('No more.');

if (filesize($_GET['dir']) > 1048576) exit('Too large file');
echo htmlspecialchars(file_get_contents($_GET['dir']));