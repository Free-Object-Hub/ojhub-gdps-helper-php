<?php
require_once '../api/api.php';

$user = Users::fetchByTokenAndDevice($_POST['token'], $_POST['device']);
if (!$user)
	exit('Access denied');

if ($user->activated !== 0) {
	if (strlen($_POST['text']) < 10)
		exit('-4');
	$commUserId = content::getValue('userId', 'comments', $_POST['id']);
	if ($commUserId !== $user->userId && $user->priority == 0)
		exit('-3');
	$text = exploitPatch($_POST['text']);

	if (time() > $user->commTime || $user->priority > 0) {
		Comments::modifyComment(
			$user->userId,
			$_POST['id'],
			base64_encode($text),
			time()
		);
		$ide = content::getValue('whereIz', 'comments', $_POST['id']);
		User::TGwebhookLog($GDPScomm11.$_POST['type'].' '.$ide.' '.$_POST['id'].$GDPScomm2.$_POST['text']);
		exit($_POST['text']);
	}
}
