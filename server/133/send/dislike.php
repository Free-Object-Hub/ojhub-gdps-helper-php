<?php
require_once '../api/api.php';

$user = Users::fetchByTokenAndDevice($_POST['token'], $_POST['device']);
if (!$user)
	exit('Access denied');

$type = content::liketype($_POST['type']);

if ($user->activated == 0) {
	echo content::getValue('disls', $type[0], $_POST['ide']);
	exit();
}
$chkLike = content::checkLike($_POST['ide'], $user->userId, $type[2]);
if (!$chkLike) {
	content::dislSet($_POST['ide'], $_POST['type'], $user->userId);
} else {
	content::removeLike($chkLike, $_POST['ide'], $_POST['type']);
}
echo '['.content::getValue('likes', $type[0], $_POST['ide']).','.
	content::getValue('disls', $type[0], $_POST['ide']).']';