<?php
require_once '../api/api.php';

$userId = 0;
if (isset($_POST['token'])) {
	$user = Users::fetchByToken($_POST['token']);
	$userId = $user->userId;
}

$posts = Forums::getPosts($_GET['id']);

$forum = Forums::fetchById($_GET['id']);
$wiki = Guides::fetchWikiById($forum->wikiId);

$json = [
	$wiki->title,
];
foreach ($posts as $p)
	$json[] = $p->renderPost($userId);

echo renderJson($json);