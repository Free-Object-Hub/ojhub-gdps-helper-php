<?php
require_once '../api/api.php';

$user = Users::fetchByTokenAndDevice($_POST['token'], $_POST['device']);
if (!$user)
	exit('Access denied');

$socials = exploitPatch($_POST['name']);
$socials = str_replace("\\\\n", "\\n", $socials);

Users::setSocials($user->userId, $socials);

echo $socials;