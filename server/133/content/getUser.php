<?php
require_once '../api/api.php';

$user = Users::fetchById($_GET['id']);

if ($user != null) {
	$json = [
		'username' => $user->getNickname(),
		'ID' => $user->userId,
		'role' => $user->priority,
		'isActive' => $user->activated,
		'resume' => $user->resume,
		'socials' => $user->socials,
	];
	echo renderJson($json);
}