<?php
require_once '../api/api.php';

$user = Users::fetchByTokenAndDevice($_POST['token'], $_POST['device']);
if (!$user)
	exit('Access denied');

$wiki = Guides::fetchWikiById($_POST['wiki']);

if (Guides::checkPerms($user->userId, $wiki->ID) == false)
	exit('-2');
$guide = Guides::fetchByTag($_POST['guide'], $_POST['wiki']);

Echo Guides::setMainWiki($_POST['wiki'], $guide->ID);
