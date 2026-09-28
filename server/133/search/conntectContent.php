<?php
require_once '../api/api.php';

$user = Users::fetchByTokenAndDevice($_POST['token'], $_POST['device']);
if (!$user)
	exit('Access denied');

if ($_POST['id'] != 0) {
	$gdps = content::fetchById($_POST['id'], 0);
	if (content::checkItem($user->userId,$_POST['connectTo'],-1) == true) {
		if (masterConnect(1, $_POST['connectTo'], $_POST['id']))
			echo content::getValue('connectedWiki', 'gdpses', $gdps->ID);
	}
} else {
	dropConnectWiki($_POST['connectTo']);
}