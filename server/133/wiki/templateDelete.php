<?php
require_once '../api/api.php';

$user = Users::fetchByTokenAndDevice($_POST['token'], $_POST['device']);
if (!$user)
	exit('Access denied');

if ($_GET['id'] != 0) {
	$wiki = Guides::fetchWikiById($_GET['id']);
	if (content::checkItem($user->userId,$_GET['id'],-1) == true) {
		echo WikiTemplates::deleteTemplate($wiki->ID, $_GET['name']);
	}
}
