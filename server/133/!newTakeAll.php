<?php 
require_once './api/api.php';

if (empty($_POST['token']))
exit('Access denied - No user Data');

$user = Users::fetchByToken($_POST['token']);

if ($user->priority == 0)
exit('Access denied - No perma');

error_reporting(81);

if (isset($_GET['wiki'])) {
	$return = '{';
	$guides = Guides::fetchAllGuides($_GET['wiki']);
	$first = true;
	foreach ($guides as $g) {
		$return .= $first ? '' : ',';
		$first = false;
		$return .= $g->renderGuideAdmin();
	}
	$return .= '}';
	exit($return);
}

$json = [
	'user' => [],
	'camps' => [],
	'shows' => [],
	'peres' => [],
	'wikis' => [],
];

$token = $_POST['token'];
if ($token !== '') {
	$user = Users::fetchByToken($token);
	if ($user != null)
		$json['user'] = [
			$user->getNickname(),
			$user->userId,
			$user->priority,
			$user->activated,
			Alarms::checkAlarms($user->userId),
			$token
		];
}
try {
	foreach (content::fetchAlls2(0) as $gdps) {
		if (!$gdps)
			continue;
		$json['camps']["c$gdps->ID"] = $gdps->GDPSrenderAdmin(true);
	}
	foreach (content::fetchAlls2(1) as $gdps) {
		if (!$gdps)
			continue;
		$json['shows']["s$gdps->ID"] = $gdps->GDPSrenderAdmin(true);
	}
	foreach (content::fetchAlls2(2) as $gdps) {
		if (!$gdps)
			continue;
		$json['peres']["p$gdps->ID"] = $gdps->GDPSrenderAdmin(true);
	}
	foreach (Guides::fetchAllWikis() as $gdps) {
		if (!$gdps)
			continue;
		$json['wikis']["w$gdps->ID"] = $gdps->renderWikiAdmin();
	}
	//foreach (Vacans::fetchAll() as $gdps) {
	//	if (!$gdps)
	//		continue;
	//	$json['wikis']["v$gdps->ID"] = $gdps->renderVacanMini();
	//}
} catch (Exception $e) {
	var_dump($e);
	echo $e->getMessage();
	exit();
}

echo renderJson($json);