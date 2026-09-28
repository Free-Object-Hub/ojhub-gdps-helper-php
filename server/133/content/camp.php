<?php
session_start();
require_once '../api/api.php';

$userId = 0;
if (isset($_POST['token'])) {
	$user = Users::fetchByToken($_POST['token']);
	$userId = $user->userId;
}

$gdps = content::fetchById($_GET['id'], 0);

if ($gdps == null) {
	exit('["NONE"]');
}

if ($_SESSION['lgbtBan'] !== 0 && $gdps->hasLgbt != 0) {
	exit('["NONE"]');
}


$json = [
	'gdps' => $gdps->GDPSrender(true, $userId),
	'comments' => [],
	'news' => []
];

$comms = Comments::getComments(0, $_GET['id'], $userId, 0);
foreach ($comms as $comm)
	$json['comments']['c'.$comm->ID] = $comm->COMMrender();

$newz = content::fetchNews($_GET['id'], $userId); 
foreach ($newz as $news)
	$json['news']['n'.$news->ID] = $news->newsRenderObj();

echo renderJson($json);