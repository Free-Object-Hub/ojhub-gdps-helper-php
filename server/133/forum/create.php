<?php
require_once '../api/api.php';

$user = Users::fetchByTokenAndDevice($_POST['token'], $_POST['device']);
if (!$user)
	exit('Access denied');

if ($user->activated == 0)
	exit();

if (content::checkItem($user->userId,$_POST['id'],-1) == false)
	exit();

$forumId = Forums::createForum($_POST['id'], time());

User::TGwebhookLog($FORUadd.$forumId);

echo $forumId;