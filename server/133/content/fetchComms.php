<?php
require_once '../api/api.php';

$userId = 0;
if (isset($_POST['token'])) {
	$user = Users::fetchByToken($_POST['token']);
	$userId = $user->userId;
}

$json = [];

$comms = Comments::getComments($_GET['type'], $_GET['id'], $userId, $_GET['page']);

foreach ($comms as $comm)
	$json['c'.$comm->ID] = $comm->COMMrender();

echo renderJson($json);