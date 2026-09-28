<?php
require_once '../api/api.php';

$user = Users::fetchByTokenAndDevice($_POST['token'], $_POST['device']);
if (!$user)
	exit('Access denied');

$gdpsId = $_POST['gdpsId'];
$vacId = $_POST['id'];

if ($gdpsId != 0) {
	$gdps = content::fetchById($gdpsId, 0);
	if (content::checkItem($user->userId,$gdpsId,0) == true || $user->priority > 0) {
		if (isset($_POST['title'])) {
			$title = exploitPatch($_POST['title']);
			$text = exploitPatch($_POST['text']);
			$short = exploitPatch($_POST['short']);
			$tags = json_encode($_POST['tags']);
			$mask = createBitmask($_POST['tags']);
			$checked = $gdps->checked;
			$hasLgbt = $gdps->hasLgbt;

			echo Vacans::editVac([$title, $text, $short, $tags, $mask, $gdpsId, $checked, $hasLgbt, $vacId]);
			exit();
		}
		$vacan = Vacans::fetchVacanById($vacId);
		echo renderJson($vacan->renderVacan());
	}
}