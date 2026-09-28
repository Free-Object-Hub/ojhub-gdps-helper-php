<?php
require_once '../api/api.php';

$user = Users::fetchByToken($_POST['token']);

$first = true;

$joins = content::fetchOwned($_GET['id'], 4);

$gdps = content::fetchById($_GET['id'], 0);

$json = [
	[
		$gdps->title,
		'dontRenderMe',
	]
];

foreach ($joins as $join) {
	if ($join->userId != 0) {
		$uder = Users::fetchById($join->userId);
		$username = $uder->getNickname();
	}
	else
		$username = '???';
	$json[] = [
		$username,
		(int)$join->joinDate,
		$join->joinData
	];
}

echo renderJson($json);