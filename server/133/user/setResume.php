<?php
require_once '../api/api.php';

$user = Users::fetchByTokenAndDevice($_POST['token'], $_POST['device']);
if (!$user)
	exit('Access denied');

$resume = exploitPatch($_POST['name']);
$resume = str_replace("\\\\n", "\\n", $resume);

Users::setResume($user->userId, $resume);

echo $resume;