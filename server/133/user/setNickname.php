<?php
require_once '../api/api.php';

$user = Users::fetchByTokenAndDevice($_POST['token'], $_POST['device']);
if (!$user)
	exit('Access denied');

$nickname = exploitPatch($_GET['name']);

Users::setNickname($user->userId, $nickname);

echo $nickname;