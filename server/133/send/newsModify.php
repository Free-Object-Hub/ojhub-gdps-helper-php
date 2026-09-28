<?php
require_once '../api/api.php';

$user = Users::fetchByTokenAndDevice($_POST['token'], $_POST['device']);
if (!$user)
	exit('Access denied');

if ($user->activated == 0)
	exit();
if (content::checkItem($user->userId,$_POST['gdps'],0) == false || $user->priority > 0)
	exit('-2'); //нет прав


if(isset($_POST['text']) && isset($_POST['title'])) {
	User::TGwebhookLog($GDPScomm31.$_POST['gdps'].' '.$_POST['id'].$GDPScomm2.$_POST['text']);
	$text2 = exploitPatch($_POST['text']);
	$text = base64_encode($text2);
	$title = exploitPatch($_POST['title']);
	$date = time();
	Comments::NEWSpostModify($_POST['id'], $text, $title);
	echo renderJson([
		$title,
		$text2
	]);
}