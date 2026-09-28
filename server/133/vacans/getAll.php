<?php
require_once '../api/api.php';

$userId = 0;
if (isset($_POST['token'])) {
	$user = Users::fetchByToken($_POST['token']);
	$userId = $user->userId;
}

$page = $_GET['page'] ?? 0;

$vacans = NewGdpsFinder($userId,0,-5,0);

$json = [];
foreach ($vacans as $v)
	$json['v'.$v->ID] = $v->renderVacanMini($userId);

echo renderJson($json);