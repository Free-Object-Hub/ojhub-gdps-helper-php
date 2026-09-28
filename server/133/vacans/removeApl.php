<?php
require_once '../api/api.php';

$user = Users::fetchByTokenAndDevice($_POST['token'], $_POST['device']);
if (!$user)
	exit('Access denied');

if (isset($_GET['gdpsId']))
	$gdpsId = $_GET['gdpsId'];

if (isset($_GET['gdpsId']) && $gdpsId != 0) {
	$gdps = content::fetchById($gdpsId, 0);
	if (content::checkItem($user->userId,$gdpsId,0) == true)
		echo Vacans::removeApl($_GET['id']);
} else
	echo Vacans::removeAplsByUser($user->userId, $_GET['id']);