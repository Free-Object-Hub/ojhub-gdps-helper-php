<?php 
require_once '../api/api.php';

$page = $_GET['page'] ?? 0;

$user = Users::fetchByToken($_POST['token']);

$guides = Guides::fetchWikiAdmin($_GET['wiki'], $page);

if (Guides::checkPerms($user->userId, $_GET['wiki']) == false) 
	exit('-2');

$json = [];

foreach ($guides as $g)
	if ($g)
		$json[] = $g->renderGuideMini();

echo renderJson($json);