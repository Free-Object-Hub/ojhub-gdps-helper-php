<?php
require_once '../api/api.php';

$userId = 0;
if (isset($_POST['token'])) {
	$user = Users::fetchByToken($_POST['token']);
	$userId = $user->userId;
}

$vacans = Vacans::fetchVacanById($_GET['id'], $userId);

if ($vacans == null) {
	exit('["NONE"]');
}

$uder = Users::fetchById($userId);
$comms = Comments::getComments(5, $_GET['id'], $userId, 0);

$json = [
	'gdps' => [
		'v'.$vacans->ID => $vacans->renderVacan($userId)
	],
	'comments' => []
];

foreach ($comms as $comm)
	$json['comments']['c'.$comm->ID] = $comm->COMMrender();

echo renderJson($json);