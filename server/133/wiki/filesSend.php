<?php 
require_once '../api/api.php';
if (!isset($_POST['token'])) {
	exit(-27);
}

error_reporting(E_ALL);

$user = Users::fetchByTokenAndDevice($_POST['token'], $_POST['device']);
if (!$user)
	exit('Access denied');

if ($user->activated == 0) {
	exit();
}

$wiki = Guides::fetchWikiById($_GET['id']);
$wikiId = $wiki->ID;

$maxFileSize = 6291456;
$allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
$uploadDir = __DIR__."/../../../imgs/customwiki/$wikiId/";

if ($wikiId != 0) {
	if (content::checkItem($user->userId,$wikiId,-1) == true) {
		if (isset($_FILES['files'])) {
			$file = $_FILES['files'];
			if ($file['error'] === UPLOAD_ERR_OK) {
				if ($file['size'] <= $maxFileSize) {
					$fileMimeType = mime_content_type($file['tmp_name']);
					if (in_array($fileMimeType, $allowedMimeTypes)) {
						$fileName = exploitPatchFile($file['name']);
						$destination = $uploadDir . $fileName;

						$filesJson = $wiki->ReturnFilesJson();
						$wikiFiles = !empty($filesJson) ? json_decode($filesJson) : new stdClass();
						$title = $_POST['title'];
						$wikiFiles->$title = [
							$fileName,
							$user->userId
						];
						$totalSize = 0;
						foreach ($wikiFiles as $fileData) {
							$filename = $fileData[0];
							$filePath = $uploadDir . $filename;

							if (file_exists($filePath)) {
								$totalSize += filesize($filePath);
							}
						}
						$totalSize += $file['size'];
						
						if ($totalSize > 16777216)
							exit('-1');

						if (!file_exists($uploadDir)) {
							mkdir($uploadDir, 0777, true);
						}

						move_uploaded_file($file['tmp_name'], $destination);
						$wiki->ModifyJsonFiles($wikiFiles, $totalSize);
						$wikiFiles->$title[2] = $user->getNickname();
					} else exit('-3');
				} else exit('-2');
			}
		}
		echo renderJson(Guides::upgradeFilesJson(json_encode($wikiFiles), $totalSize));
	}
}
