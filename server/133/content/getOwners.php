<?php
require_once '../api/api.php';

$user = Users::fetchByToken($_POST['token']);

$joins = content::fetchOwned($_GET['id'], $_GET['type']);
if ($_GET['type'] != -1)
	$gdps = content::fetchById($_GET['id'], 0);
elseif ($_GET['type'] == -1)
	$gdps = Guides::fetchWikiById($_GET['id']);

$json = [
	[
		$gdps->title
	],
	[]
];

foreach ($joins as $join) {
	if ($join->userId != 0) {
		$uder = Users::fetchById($join->userId);
		$username = $uder->getNickname();
	}
	else
		$username = '???';
	$json[1][] = [
		$username,
		$user->userId
	];
}
echo renderJson($json);