<?php
require_once '../api/api.php';

$user = Users::fetchByTokenAndDevice($_POST['token'], $_POST['device']);
if (!$user)
	exit('Access denied');

if ($_POST['id'] != 0) {
	$wiki = Guides::fetchWikiById($_POST['id']);
	if (content::checkItem($user->userId,$_POST['connectTo'],0) == true) {
		if (masterConnect(0, $_POST['id'], $_POST['connectTo']))
			echo content::getValue('connectedGdps', 'wikis', $wiki->ID);
	}
} else {
	dropConnectGdps($_POST['connectTo']);
}