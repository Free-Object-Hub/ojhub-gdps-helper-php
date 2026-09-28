<?php
require_once '../api/api.php';

$user = Users::fetchByTokenAndDevice($_POST['token'], $_POST['device']);
if (!$user)
	exit('Access denied');

$guide = Guides::fetchById($_GET['id']);
if (Guides::checkPerms($user->userId, $guide->wikiChannel) == false)
	exit('-2');

if (isset($_POST['title'])) {
	if ($user->activated == 0)
		exit('-45');

	$guidetitle = exploitPatch($_POST['title']);
	$language = exploitPatch($_POST['language']);
	$aftertext = exploitPatch($_POST['aftertext']);
	$subtitlePre = $_POST['subtitle'];
	$subtextPre = $_POST['subtext'];
	$img = exploitPatchUrl($_POST['img']);
	$guidId = $_GET['id'];
	$guideinfo = '[';

	$allTemplates = [];

	$count = count($subtitlePre) - 1;
	for ($i = 0; $i <= $count; $i++) {
		$subtitle = exploitPatch($subtitlePre[$i]);
		$subtext = exploitPatch($subtextPre[$i]);
		$guideinfo .= $i == 0 ? '' : ',';
		$guideinfo .= '["'.$subtitle.'","'.$subtext.'"]';
		// Ищем шаблоны в текущем subtext
		preg_match_all('/{{([^}|]+)/', $subtextPre[$i], $matches);
		
		// Добавляем найденные шаблоны в общий массив
		if (!empty($matches[1])) {
			foreach ($matches[1] as $template) {
				$template = trim($template); // Убираем лишние пробелы
				if (!in_array($template, $allTemplates)) {
					array_push($allTemplates, $template);
				}
			}
		}
	}
	$guideinfo .= ']';
	
	$templatesString = implode(',', $allTemplates);
	
	echo Guides::editGuide($user->userId, $guidetitle, $aftertext, $guideinfo, $language, $img, $templatesString, $guidId);
	User::TGwebhookLog($GUIDedit.$guidId);
	exit();
}

if ($guide == null) {
	exit('["NONE"]');
}

echo renderJson($guide->renderGuideLT());
