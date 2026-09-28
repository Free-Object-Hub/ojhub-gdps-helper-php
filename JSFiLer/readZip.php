<?php require_once '!chkpass.php';
function printZipStructure($zipFilePath, $indent = '') {
    $zip = new ZipArchive;

    // Открываем ZIP-архив
    if ($zip->open($zipFilePath) === true) {
        // Цикл по всем файлам в архиве
        for ($i = 0; $i < $zip->numFiles; $i++) {
            // Получаем имя файла
            $fileName = $zip->getNameIndex($i);
 
            // Проверяем, является ли файл папкой
            if (substr($fileName, -1) === '/') {
                // Выводим имя папки
                echo $indent . ' - ' . basename($fileName) . "
";

                // Рекурсивно вызываем функцию для подпапок
                printZipStructure($zipFilePath, $indent . '  ');
            } else {
                // Выводим имя файла
                echo $indent . ' - ' . $fileName . "
";
            }
        }

        // Закрываем архив
        $zip->close();
    } else {
        echo 'Ошибка при открытии ZIP-архива.';
    }
}

printZipStructure($_GET['dir']);