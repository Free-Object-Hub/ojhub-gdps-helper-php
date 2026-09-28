<?php
require_once '../api/api.php';

$gdps = content::fetchById($_GET['id'], 0);
$user = Users::fetchByTokenAndDevice($_POST['token'], $_POST['device']);
if (!$user)
	exit('Access denied');

if (content::checkItem($user->userId,$_GET['id'],$contentType) == false)
	exit('-2'); //нет прав

$maxFileSize = 6291456;
$allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
$uploadDir = FILES_PATH.'customuser/';
	
if (isset($_POST['title']) && isset($_POST['description'])) {
	if ($user->activated == 0) {
		exit('Access denied');
	}

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

	$tags = $_POST['tags'];
	$os = $_POST['os'];
	$mask = createBitmask(
		mergeAndSortTags(
			$tags,
			$os
		)
	);
	$gdpsId = $_GET['id'];
	$language  = exploitPatch($_POST['language']);
	
		   //channel,	  title,  link,  img,  ban,  description,  short,  tags,					  os,					  mask,  language,  gdpsId
	$data = [$contentType, $title, $link, $img, $ban, $description, $short, json_encode($tags), json_encode($os), $mask, $language, $gdpsId];

	if (!content::editItem($data)) {
		exit('-1'); //хз просто
	}

	$post['imgOld'] = '';
	if (isset($_FILES['img'])) {
		$file = exploitPatchFile($_FILES['img']);
		if ($file['error'] === UPLOAD_ERR_OK) {
			if ($file['size'] <= $maxFileSize) {
				$fileMimeType = mime_content_type($file['tmp_name']);
				if (in_array($fileMimeType, $allowedMimeTypes)) {
					$fileName = 'i'.$gdpsId.'-'.$gdps->editCount.'.'.pathinfo($file['name'], PATHINFO_EXTENSION);
					$destination = $uploadDir . $fileName;
					$img = HELPER_URL.'imgs/customuser/'.$fileName;
					move_uploaded_file($file['tmp_name'], $destination);
				} else
					exit('-3');
			}
		}
	}
	$post['banOld'] = '';
	if (isset($_FILES['ban'])) {
		$file2 = exploitPatchFile($_FILES['ban']);
		if ($file2['error'] === UPLOAD_ERR_OK) {
			if ($file2['size'] <= $maxFileSize) {
				$fileMimeType2 = mime_content_type($file2['tmp_name']);
				if (in_array($fileMimeType2, $allowedMimeTypes)) {
					$fileName2 = 'b'.$gdpsId.'-'.$gdps->editCount.'.'.pathinfo($file2['name'], PATHINFO_EXTENSION);
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

	User::TGwebhookLog($GDPSedit.$gdpsId.'/'.$contentType);

	loginT($_POST['token']);
	exit(); // нет ошибок
}

$links = str_replace('\"', '"', $gdps->link);
if (str_starts_with($links, '{'))
	$links = json_decode($links);

echo renderJson([
	$gdps->title,
	$gdps->description,
	$gdps->short,
	$links,
	$gdps->img,
	$gdps->ban,
	$gdps->tags,
	$gdps->os,
	$gdps->language
]);
