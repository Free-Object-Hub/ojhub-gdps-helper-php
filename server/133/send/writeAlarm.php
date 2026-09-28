<?php
require_once '../api/api.php';

$user = Users::fetchByTokenAndDevice($_POST['token'], $_POST['device']);
if (!$user)
	exit('Access denied');

if (isset($_POST['title'])) {
	$title = exploitPatch($_POST['title']);
	$text = exploitPatch($_POST['text']);
	$userId = $_POST['user'];
	$date = time();
	if ($user->priority != 0 && isset($_POST['anonymus'])) {
		$adminName = 'Object Hub';
		$adminId = 0;
	} else {
		$adminName = $user->getNickname();
		$adminId = $user->userId;
	}
	if ($user->priority == 0)
		$userId = 0;

	Alarms::writeAlarm($title, $text, $userId, $date, $adminName, $adminId);
	echo 1;
}