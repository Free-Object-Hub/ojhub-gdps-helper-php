<?php
require_once '../api/api.php';

if (isset($_POST['token'])) {
	$user = Users::fetchByToken($_POST['token']);
} else {
	exit('-1');
}
$json = [];

if  ($user->priority != 0) {
	$alarms = Alarms::getAlarmsList(0, $_GET['page']);
	foreach ($alarms as $a)
		if ($a->public != 0) 
			$json[] = [
			$a->ID,
			$a->title,
			$a->public
		];
}

$alarms = Alarms::getAlarmsList($user->userId, $_GET['page']);
foreach ($alarms as $a)
	if ($a->public != 0)
		$json[] = [
			$a->ID,
			$a->title,
			$a->public
		];

echo renderJson($json);