<?php

require_once '../api/api.php';

$user = Users::fetchByTokenAndDevice($_POST['token'], $_POST['device']);
if (!$user)
	exit('Access denied');

if (content::checkItem($user->userId, $_GET['gdps'], $_GET['type']) == 2) {
	$user2 = Users::fetchById($_GET['user']);

	content::addOwner($_GET['gdps'], $user2->userId, $_GET['type']);
	
	echo renderJson([$user2->getNickname(),(int)$user2->userId]);
} else {
	exit("-2");
}?>