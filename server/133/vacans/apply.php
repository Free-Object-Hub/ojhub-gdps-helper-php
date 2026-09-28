<?php
require_once '../api/api.php';

$user = Users::fetchByTokenAndDevice($_POST['token'], $_POST['device']);
if (!$user)
	exit('Access denied');

$data = [$_GET['id'], $user->userId, time()];
$checkApply = Vacans::checkApply($_GET['id'], $user->userId);
if (!$checkApply)
	echo Vacans::applyVac($data);
else 
	echo '-1';