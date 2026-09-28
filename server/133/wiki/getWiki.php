<?php 
session_start();
require_once '../api/api.php';

$page = $_GET['page'] ?? 0;

$userId = 0;
if (isset($_POST['token'])) {
	$user = Users::fetchByToken($_POST['token']);
	$userId = $user->userId;
}

$wiki = Guides::fetchWikiById($_GET['wiki']);
$guides = Guides::fetchWiki($_GET['wiki'], $page);

if ($_SESSION['lgbtBan'] !== 0 && $wiki->hasLgbt != 0) 
	exit('[]');

$json = [
	[
		'ID' => $wiki->forumId,
		'title' => $wiki->title,
		'color' => $wiki->colors
	],
];

foreach ($guides as $g) 
	$json[] = $g->renderGuideMini($userId);

echo renderJson($json);