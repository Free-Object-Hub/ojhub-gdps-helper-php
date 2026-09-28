<?php
require_once '../api/api.php';

$gdpses = content::getMyContent($_GET['id'], $_GET['type']);

$timeUser = Users::fetchById($_GET['id']);
$json = [
	$timeUser->getNickname(),
	[]
];

foreach ($gdpses[0] as $gdps2) {
	$gdps = content::fetchById($gdps2->ID, $_GET['type']);
	if ($gdps->ID != null)
		$json[1]['g'.$gdps->ID] = $gdps->GDPSrenderLT();
}

echo renderJson($json);