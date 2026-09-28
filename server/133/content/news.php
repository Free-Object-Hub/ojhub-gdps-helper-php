<?php require_once '../api/api.php';

$page = $_GET['page'] ?? 0;

$userId = 0;
if (isset($_POST['token'])) {
	$user = Users::fetchByToken($_POST['token']);
	$userId = $user->userId;
}

$newz = content::fetchNews($_GET['id'], $userId, $page);

$json = [];

foreach ($newz as $news)
	$json['n'.$news->ID] = $news->newsRenderObj($userId);

echo renderJson($json);