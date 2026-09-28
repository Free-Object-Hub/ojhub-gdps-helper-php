<?php
require_once '../api/api.php';

$userId = 0;
if (isset($_POST['token'])) {
	$user = Users::fetchByToken($_POST['token']);
	$userId = $user->userId;
}

$post = Forums::fetchPostById($_GET['id']);

if ($post == null) {
	exit('["NONE"]');
}

$comms = Comments::getComments(4, $_GET['id'], $userId, 0);

$json = [
	'post' => $post->renderPost($userId),
	'comments' => []
];
foreach ($comms as $comm)
	$json['comments']['c'.$comm->ID] = $comm->COMMrender();

echo renderJson($json);