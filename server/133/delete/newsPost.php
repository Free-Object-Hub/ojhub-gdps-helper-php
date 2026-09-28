<?php
require_once '../api/api.php';

$user = Users::fetchByTokenAndDevice($_POST['token'], $_POST['device']);
if (!$user)
	exit('Access denied');

$news = content::fetchById($_GET['ide'], -3);
if ($news->userId == $user->userId || content::checkItem($user->userId,$news->gdpsId,0) == 2  || $user->priority > 0) {
	User::TGwebhookLog($GDPSuncomm2.' ID '.$_GET['ide']);

	Comments::deleteNews($_GET['ide']);
	echo $_GET['ide'];

	$comms = Comments::getAllComments(2, $_GET['ide']);

	foreach ($comms as $comm) {
		$commChannel = content::liketype(5);
		Comments::deleteComm($comm->ID, $commChannel[1], $commChannel[2]);
	}
} else echo '-1';