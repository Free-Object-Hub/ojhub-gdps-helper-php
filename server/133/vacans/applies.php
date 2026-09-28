<?php
require_once '../api/api.php';

$user = Users::fetchByToken($_POST['token']);
$gdpsId = $_GET['id'];
$page = $_GET['page'] ?? 0;
$vacId = $_GET['vacid'];

if ($gdpsId != 0) {
	$gdps = content::fetchById($gdpsId, 0);
	if (content::checkItem($user->userId,$gdpsId,0) == true) {
		$applies = Vacans::fetchApplies($vacId, $page);

		$vacan = Vacans::fetchVacanById($vacId);
		$json = [
			'gdpsdata' => [
				$vacan->title
			],
			'applies' => []
		];
		foreach($applies as $a)
			$json['applies']['a'.$a->ID] = $a->renderApply();
		echo renderJson($json);
	}
}