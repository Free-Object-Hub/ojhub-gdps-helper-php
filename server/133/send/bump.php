<?php
require_once '../api/api.php';

$user = Users::fetchByTokenAndDevice($_POST['token'], $_POST['device']);
if (!$user)
	exit('Access denied');

$time = time() + 7200;
if ($user->activated == 0) {
	echo renderJson([$point, $time, $canBump]);
}

$cTime = time();

$point = content::setPoint($_GET['id'], $time, $cTime);

if ($point == false)
	exit('no');

$canBump = $cTime - $point;

echo renderJson([$point, $time, $canBump]);