<?php
require_once '../api/api.php';

$guide = Guides::fetchById($_GET['id']);
$user = Users::fetchByTokenAndDevice($_POST['token'], $_POST['device']);
if (!$user)
	exit('Access denied');

if (Guides::checkPerms($user->userId, $guide->wikiChannel) == false)
	exit('-2');

$tag = exploitPatch($_GET['tag'], true);

if (preg_match('/^\d/', mb_substr($tag, 0, 1)) == false) {
	if (preg_match('/^[a-zA-Z-\d]+$/', $tag) == true) {
		echo Guides::setWikiTag($_GET['id'], $_GET['wiki'], $tag);
	} else exit('-2');
} else exit('-1');