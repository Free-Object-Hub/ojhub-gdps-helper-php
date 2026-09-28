<?php
require_once '../api/api.php';

$user = Users::fetchByTokenAndDevice($_POST['token'], $_POST['device']);
if (!$user)
	exit('Access denied');

echo $user->removeDeviceById($_POST['id']);