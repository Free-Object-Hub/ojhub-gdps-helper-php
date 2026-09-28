<?php
require_once '../api/api.php';

$user = Users::fetchByTokenAndDevice($_POST['token'], $_POST['device']);
if (!$user)
	exit('Access denied');

if ($user->activated == 0) {
	echo content::getValue('likes', $type[0], $_POST['ide']);
	exit();
}
User::TGwebhookLog($forumUplo01.$_POST['forumId'].$GDPScomm2.$_POST['text']);

if(isset($_POST['text']) && isset($_POST['title'])) {
	$forumId = $_POST['forumId'];
	$text2 = htmlspecialchars($_POST['text'], ENT_QUOTES);
	$text = base64_encode($text2);
	$title = htmlspecialchars($_POST['title'], ENT_QUOTES);
	$date = time();
	
	$postId = Forums::uploadPost($forumId, $user->getNickname(), $user->userId, $title, $text, $date);
	echo "[$forumId,$postId]";
}