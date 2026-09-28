<?php
require_once '../api/api.php';

$user = Users::fetchByToken($_POST['token']);

if (isset($_POST['password'])) {
	if ($user->verifyPassword($_POST['password'])) {
		echo renderJson([$user->username, $user->mail]);
	} else {
		echo "-1";
	}
}