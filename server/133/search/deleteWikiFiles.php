<?php 
require_once '../api/api.php';

$user = Users::fetchByTokenAndDevice($_POST['token'], $_POST['device']);
if (!$user)
	exit('Access denied');

if ($user->activated == 0) {
	exit();
}

$wiki = Guides::fetchWikiById($_GET['id']);
$wikiId = $wiki->ID;

$uploadDir = __DIR__."/../../../imgs/customwiki/$wikiId/";

if ($_GET['id'] != 0) {
	if (content::checkItem($user->userId,$_GET['id'],-1) == true) {
		if (isset($_GET['file'])) {
			$wikiFiles = json_decode($wiki->ReturnFilesJson());
			$title = $_GET['file'];

			$fileName = $wikiFiles->$title[0];

			unset($wikiFiles->$title);

			$totalSize = 0;
			foreach ($wikiFiles as $fileData) {
				$filename = $fileData[0];
				$filePath = $uploadDir . $filename;

				if (file_exists($filePath)) {
					$totalSize += filesize($filePath);
				}
			}

			unlink($uploadDir . $fileName);
			$wiki->ModifyJsonFiles($wikiFiles, $totalSize);
			Guides::upgradeFilesJson(json_encode($wikiFiles), $totalSize);
			echo $totalSize;
		} else {
			echo $wiki->filesSize;
		}
	}
}