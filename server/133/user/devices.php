<?php
require_once '../api/api.php';

$user = Users::fetchByToken($_POST['token']);

$devices = $user->getDevices();

$json = [];

foreach ($devices as $d) {
	$json[$d['ID']] = [
		'userAgent' => $d['userAgent'],
		'country' => $d['country'],
		'city' => $d['city'],
		'platform' => $d['platform'],
		'browser' => $d['browser'],
	];
}
echo renderJson($json);