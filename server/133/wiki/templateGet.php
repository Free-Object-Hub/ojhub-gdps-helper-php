<?php
require_once '../api/api.php';

$user = Users::fetchByToken($_POST['token']);

if ($_GET['id'] != 0) {
	$wiki = Guides::fetchWikiById($_GET['id']);
	if (content::checkItem($user->userId,$_GET['id'],-1) == true) {
		$template = WikiTemplates::getOneTemplateByWiki($wiki->ID, $_GET['name']);
		echo renderJson($template->renderTemplate());
	}
}
