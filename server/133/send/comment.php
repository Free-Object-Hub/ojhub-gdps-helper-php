<?php
require_once '../api/api.php';

$user = Users::fetchByTokenAndDevice($_POST['token'], $_POST['device']);
if (!$user)
	exit('Access denied');

if ($user->activated !== 0) {
	if (strlen($_POST['text']) < 10)
		exit('-4');
	$text = exploitPatch($_POST['text']);

	if (time() > $user->commTime || $user->priority > 0) {
		Comments::addComment(
			$user->userId,
			$_POST['ide'],
			base64_encode($text),
			time(),
			$_POST['type']
		);
		User::TGwebhookLog($GDPScomm1.$_POST['type'].' '.$_POST['ide'].$GDPScomm2.$_POST['text']);
	}
}

$json = [];

$comms = Comments::getComments($_POST['type'], $_POST['ide'], $user->userId, 0);

foreach ($comms as $comm)
	$json['c'.$comm->ID] = $comm->COMMrender();

echo renderJson($json);