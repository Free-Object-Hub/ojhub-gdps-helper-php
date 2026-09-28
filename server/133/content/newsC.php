<?php
require_once '../api/api.php';

$userId = 0;
if (isset($_POST['token'])) {
	$user = Users::fetchByToken($_POST['token']);
	$userId = $user->userId;
}

$news = Comments::newsFetchById($_GET['id'], $userId);

if ($news == null) {
	exit('["NONE"]');
}

$uder = Users::fetchById($news->userId);
$comms = Comments::getComments(3, $_GET['id'], $userId, 0);


$json = [
	'gdps' => [
		'n'.$news->ID => $news->newsRenderObj()
	],
	'comments' => []
];

foreach ($comms as $comm)
	$json['comments']['c'.$comm->ID] = $comm->COMMrender();

echo renderJson($json);