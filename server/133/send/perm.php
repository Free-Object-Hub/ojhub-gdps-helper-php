<?php
require_once '../api/api.php';

$user = Users::fetchByTokenAndDevice($_POST['token'], $_POST['device']);
if (!$user)
	exit('Access denied');

$g = content::checkItem($user->userId,$_GET['gdps'],$_GET['type']);

if ($g == 2) {
	content::deleteOwner($_GET['gdps'], $_GET['id'], $_GET['type']);
	echo renderJson([$g]);
} else {
	echo '-2';
	exit();
}?>