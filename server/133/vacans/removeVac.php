<?php
require_once '../api/api.php';

$user = Users::fetchByTokenAndDevice($_POST['token'], $_POST['device']);
if (!$user)
	exit('Access denied');

$gdpsId = $_GET['gdpsId'];

if ($gdpsId != 0) {
	$gdps = content::fetchById($gdpsId, 0);
	if (content::checkItem($user->userId,$gdpsId,0) == true || $user->priority > 0) {
		echo Vacans::removeVac($_GET['id']);
	}
}