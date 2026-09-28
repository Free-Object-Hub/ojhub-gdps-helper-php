<?php
require_once '../api/api.php';

error_reporting(E_ALL);

$user = Users::fetchByTokenAndDevice($_POST['token'], $_POST['device']);
if (!$user)
	exit('Access denied');

if ($user->activated == 0) {
	exit('Access denied');
}

$maxFileSize = 6291456;
$allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
$uploadDir = FILES_PATH.'customuser/';

if (isset($_POST['title']) && isset($_POST['description'])) {
	$title = exploitPatch($_POST['title']);

	if (!isset($_FILES['img']))
		$img = exploitPatch($_POST['img']);
	else
		$img = 'FILE';
	if (!isset($_FILES['ban']))
		$ban = exploitPatch($_POST['ban']);
	else
		$ban = 'FILE';
	
	$description = exploitPatch($_POST['description']);
	$short = exploitPatch($_POST['short']);
	//$link = exploitPatch($_POST['link']);

	$links = $_POST['links'];
	$link = '{';
	for ($i = 0; $i < count($links); $i += 2) {
		$link .= $i ? ',' : '';
		$link .= 
		'\\"'.
			exploitPatch($links[$i]).
		'\\":\\"'.
			exploitPatch($links[$i+1]).
		'\\"';
	};
	$link .= '}';

	$tags = json_encode($_POST['tags']);
	$os = json_encode($_POST['os']);
	$mask = createBitmask(
		mergeAndSortTags(
			$_POST['tags'],
			$_POST['os']
		)
	);
	$language  = exploitPatch($_POST['language']);

		   //channel,	  title,  link,  img,  ban,  description,  short,  tags,  os,  mask,  author,		username,			 language
	$data = [$contentType, $title, $link, $img, $ban, $description, $short, $tags, $os, $mask, $user->userId, $user->getNickname(), $language];

	$gdpsId = content::newItem($data);

	if (isset($_FILES['img'])) {
		$file = exploitPatchFile($_FILES['img']);
		if ($file['error'] === UPLOAD_ERR_OK) {
			if ($file['size'] <= $maxFileSize) {
				$fileMimeType = mime_content_type($file['tmp_name']);
				if (in_array($fileMimeType, $allowedMimeTypes)) {
					$fileName = 'i'.$gdpsId.'.'.pathinfo($file['name'], PATHINFO_EXTENSION);
					$destination = $uploadDir . $fileName;
					$img = HELPER_URL.'imgs/customuser/'.$fileName;
					move_uploaded_file($file['tmp_name'], $destination);
				} else
					exit('-3');
			}
		}
	}
	if (isset($_FILES['ban'])) {
		$file2 = exploitPatchFile($_FILES['ban']);
		if ($file2['error'] === UPLOAD_ERR_OK) {
			if ($file2['size'] <= $maxFileSize) {
				$fileMimeType2 = mime_content_type($file2['tmp_name']);
				if (in_array($fileMimeType2, $allowedMimeTypes)) {
					$fileName2 = 'b'.$gdpsId.'.'.pathinfo($file2['name'], PATHINFO_EXTENSION);
					$destination = $uploadDir . $fileName2;
					$ban = HELPER_URL.'imgs/customuser/'.$fileName2;
					move_uploaded_file($file2['tmp_name'], $destination);
				} else
					exit('-3');
			}
		}
	}
	if (!content::editGdpsPictures($gdpsId, $img, $ban))
		exit('-1');
	
	User::TGwebhookLog($GDPSadd.$gdpsId.'/'.$contentType);
	loginT($_POST['token']);
	exit();
} else echo '-27';
