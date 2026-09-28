<?php
require_once '../api/api.php';

$userId = 0;
if (isset($_POST['token'])) {
	$user = Users::fetchByToken($_POST['token']);
	$userId = $user->userId;
}

$guide = Guides::fetchByIdOrTag($_GET['id'], $_GET['wiki']);

if ($guide == null) {
	exit('["NONE"]');
}

$guideId = $guide->ID;

$json = $guide->renderGuide();
$json['comments'] = [];
$json['templates'] = [];

$comms = Comments::getComments(2, $guideId, $userId, 0);
foreach ($comms as $comm)
	$json['comments']['c'.$comm->ID] = $comm->COMMrender();
	
if ($guide->templates != '') {
	$templates = explode(',', $guide->templates);
	if (count($templates) === 1) {
		$templatesDone = WikiTemplates::getOneTemplateByWiki($guide->wikiChannel, $guide->templates);
		$json['templates'][$templatesDone->name] = $templatesDone->renderTemplate();
	} else {
		$templatesDone = WikiTemplates::getTemplatesByWiki($guide->wikiChannel, $templates);
		foreach ($templatesDone as $t)
			$json['templates'][$t->name] = $t->renderTemplate();
	}

}

echo renderJson($json);