<?php require_once '../api/api.php';

$userId = 0;
if (isset($_POST['token'])) {
	$user = Users::fetchByToken($_POST['token']);
	$userId = $user->userId;
}

$page = isset($_GET['page']) ? $_GET['page'] : 0;

$guides = Guides::fetchWiki($_GET['wiki'], $page);

$json = [];

foreach ($guides as $g)
	$json[] = $g->renderGuideMini($userId);

echo renderJson($json);