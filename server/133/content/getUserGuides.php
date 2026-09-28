<?php
require_once '../api/api.php';

$guides = Guides::fetchMyWikis($_GET['id']);

$timeUser = Users::fetchById($_GET['id']);
$json = [
	$timeUser->getNickname(),
	[]
];

if ($guides != null)
	foreach ($guides as $guid)
		if ($guid != null)
			$json[1]['w'.$guid->ID] = $guid->renderWikiLT2();

echo renderJson($json);