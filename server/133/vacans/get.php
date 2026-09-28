<?php
require_once '../api/api.php';

$user = Users::fetchByToken($_POST['token']);
$page = $_GET['page'] ?? 0;
$gdpsId = $_GET['id'];

if ($gdpsId != 0) {
	$gdps = content::fetchById($gdpsId, 0);
	if (content::checkItem($user->userId,$gdpsId,0) == true)
		$vacans = Vacans::fetchVacansByGdps($gdpsId, $user->userId, true, $page);
	else 
		$vacans = Vacans::fetchVacansByGdps($gdpsId, $user->userId, false, $page);

	$json = [
		'gdpsdata' => [
			$gdps->title
		],
		'vacs' => [

		]
	];

	foreach ($vacans as $v)
		$json['vacs']['v'.$v->ID] = $v->renderVacan();
	echo renderJson($json);
}