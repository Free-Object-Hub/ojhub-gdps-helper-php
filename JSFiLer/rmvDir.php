<?php require_once '!chkpass.php';
function deleteFilesAndFolders($dir) {
    $foldersToDelete = array();
    $files = scandir($dir);
    
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            $path = $dir . '/' . $file;
            
            if (is_dir($path)) {
                $foldersToDelete[] = $path;
            } else {
                unlink($path);
                //echo 'Удален файл: ' . $path . PHP_EOL;
            }
        }
    }
    
    // Удаление папок начиная с самых "глубоких"
    rsort($foldersToDelete);
    foreach ($foldersToDelete as $folder) {
        deleteFilesAndFolders($folder);
        rmdir($folder);
        //echo 'Удалена папка: ' . $folder . PHP_EOL;
    }
}

// Пример использования
$directoryToDelete = $_GET['name'];
if (is_dir($directoryToDelete)) {
    //echo 'Удаление файлов и папок в указанной директории:' . PHP_EOL;
    deleteFilesAndFolders($directoryToDelete);
    rmdir($directoryToDelete);
    //echo 'Удалена указанная директория: ' . $directoryToDelete;
} else {
    //echo 'Указанная директория не существует.';
}

include 'findFiles.php';