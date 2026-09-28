<?php
require_once '../api/api.php';

$user = Users::fetchByTokenAndDevice($_POST['token'], $_POST['device']);
if (!$user)
	exit('Access denied');

if ($user->activated == 0)
	exit();
if (content::checkItem($user->userId,$_POST['gdps'],0) == false)
	exit('-2'); //нет прав

if(isset($_POST['text']) && isset($_POST['title'])) {
	User::TGwebhookLog($GDPScomm3.$_POST['gdps'].$GDPScomm2.$_POST['text']);
	$text2 = exploitPatch($_POST['text']);
	$text = base64_encode($text2);
	$title = exploitPatch($_POST['title']);
	$date = time();
	$gdps = content::fetchById($_POST['gdps'],0);
	if ($gdps->checked) {
		$gettype = GDPSswitchChannel2($gdps->channel);
		$textTG = "*===> $_POST[title]*\n\n$_POST[text]\n\n[$gdps->title](https://objecthub.xyz/?$gettype=$gdps->ID)";
		User::TGwebhook($TGnewsResender, $textTG);
	}
	$hasFile = '';
	if (isset($_FILES['files']))
		if ($_FILES['files']['error'] === UPLOAD_ERR_OK)
			$hasFile = pathinfo($_FILES['files']['name'], PATHINFO_EXTENSION);
	$filename = Comments::NEWSpost($user->userId, $_POST['gdps'], $text, $date, $title, $gdps->checked, $hasFile);

	$maxFileSize = 6291456;
	$allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
	$uploadDir = FILES_PATH.'customnews/';

	if (isset($_FILES['files'])) {
		$file = exploitPatchFile($_FILES['files']);
		if ($file['error'] === UPLOAD_ERR_OK) {
			if ($file['size'] <= $maxFileSize) {
				$fileMimeType = mime_content_type($file['tmp_name']);
				if (in_array($fileMimeType, $allowedMimeTypes)) {
					$fileName = $filename.'.'.pathinfo($file['name'], PATHINFO_EXTENSION);
					$destination = $uploadDir . $fileName;
					$img = HELPER_URL.'imgs/customuser/'.$fileName;
					move_uploaded_file($file['tmp_name'], $destination);
				} else
					exit('-3');
			}
		}
	}
}
