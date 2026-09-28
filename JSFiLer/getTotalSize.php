<?php
function calculateFilesAndFoldersSize($dir) {
    $folders = array();
    $files = scandir($dir);
    
    $totalSize = 0;
    $totalFolders = 0;
    $totalFiles = 0;
    
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            $path = $dir . '/' . $file;
            
            if (is_dir($path)) {
                $folders[] = $path;
                $totalFolders += 1;
            } else {
                $totalFiles += 1;
                $totalSize += filesize($path); 
            }
        }
    }
    
    rsort($folders);
    foreach ($folders as $folder) {
        $return = calculateFilesAndFoldersSize($folder);
        $totalSize += $return[0];
        $totalFolders += $return[1];
        $totalFiles += $return[2];
    }
    
    return [$totalSize, $totalFolders, $totalFiles];
}

$size = calculateFilesAndFoldersSize($_GET['dir']);

echo 'S'.$size[0].' FD'.$size[1].' FL'.$size[2];