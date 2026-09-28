<?php
require_once '../api/api.php';

$user = Users::fetchByTokenAndDevice($_POST['token'], $_POST['device']);
if (!$user)
	exit('Access denied');

$localUID = content::getValue('userId', 'alarms', $_GET['id']);

if ($localUID != $user->userId)
	exit('-2');

if (Alarms::removeAlarm($user->userId, $_GET['id'])) 
	echo '1';
else
	echo '-3';