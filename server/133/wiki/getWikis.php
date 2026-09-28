<?php
session_start();
require_once '../api/api.php';

$page = $_GET['page'] ?? 0;

$userId = 0;
if (isset($_POST['token'])) {
	$user = Users::fetchByToken($_POST['token']);
	$userId = $user->userId;
}

$guides = NewGdpsFinder($userId,0,-1,0);

$return = [];

$_SESSION['lgbtBan'] = 0;

foreach ($guides as $g) {
	if ($_SESSION['lgbtBan'] !== 0 && $g->hasLgbt != 0) 
		continue;
	$return['w'.$g->ID] = $g->renderWiki($userId);
}

echo renderJson($return);