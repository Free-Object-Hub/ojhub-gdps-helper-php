<?php
require_once '../api/api.php';

$user = Users::fetchByToken($_POST['token']);

$page = $_GET['page'] ?? 0;

if ($_GET['id'] != 0) {
	$wiki = Guides::fetchWikiById($_GET['id']);
	if (content::checkItem($user->userId,$_GET['id'],-1) == true) {
		$json = [];
		$templates = WikiTemplates::getAllTemplatesByWiki($wiki->ID, $page);
		foreach ($templates as $t)
			$json[$t->name] = $t->renderTemplate();
		echo renderJson($json);
	}
}
