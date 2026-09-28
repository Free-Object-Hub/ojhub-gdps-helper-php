<?php
require_once '../api/api.php';

$user = Users::fetchByTokenAndDevice($_POST['token'], $_POST['device']);
if (!$user)
	exit('Access denied');

$gdpsId = $_POST['id'];

if ($gdpsId != 0) {
	$gdps = content::fetchById($gdpsId, 0);
	if (content::checkItem($user->userId,$gdpsId,0) == true) {
		$title = exploitPatch($_POST['title']);
		$text = exploitPatch($_POST['text']);
		$short = exploitPatch($_POST['short']);
		$tags = json_encode($_POST['tags']);
		$mask = createBitmask($_POST['tags']);
		$checked = $gdps->checked;
		$hasLgbt = $gdps->hasLgbt;
		$date = time();

		echo Vacans::addVac([$title, $text, $short, $tags, $mask, $gdpsId, $checked, $hasLgbt, $date]);
	}
}