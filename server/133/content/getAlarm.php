<?php
require_once '../api/api.php';

if (isset($_POST['token'])) {
	$user = Users::fetchByToken($_POST['token']);
} else {
	exit('-1');
}

$alarm = Alarms::getFullAlarm($_GET['id']);

if ($user->userId == $alarm->userId || $user->priority != 0 && $alarm->userId == 0)
	if ($alarm->public !== 0)
		echo renderJson([
			'ID' => $alarm->ID,
			'title' => $alarm->title,
			'text' => str_replace("\n",'\\n', $alarm->text),
			'date' => $alarm->date,
			'adminName' => $alarm->adminName,
			'adminId' => $alarm->adminId,
		]);
else
	echo '{}';