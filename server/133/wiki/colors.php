<?php
require_once '../api/api.php';

$wiki = Guides::fetchWikiById($_POST['wiki']);
$user = Users::fetchByToken($_POST['token']);

if (Guides::checkPerms($user->userId, $wiki->ID) == false)
	exit('-2');

$color = exploitPatch($_POST['color']);

echo Guides::setColor($_POST['wiki'], $color);