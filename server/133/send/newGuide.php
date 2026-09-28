<?php
require_once '../api/api.php';

$user = Users::fetchByTokenAndDevice($_POST['token'], $_POST['device']);
if (!$user)
	exit('Access denied');

if (Guides::checkPerms($user->userId, $_POST['wikiId']) == false) 
	exit('-2');

if (isset($_POST['title'])) {
	$guidetitle = exploitPatch($_POST['title']);
	$language = exploitPatch($_POST['language']);
	$aftertext = exploitPatch($_POST['aftertext']);
	$subtitlePre = $_POST['subtitle'];
	$subtextPre = $_POST['subtext'];
	$img = exploitPatchUrl($_POST['img']);
	$wikiId = $_POST['wikiId'];
	$guideinfo = '[';

	$allTemplates = [];

	$count = count($subtitlePre) - 1;
	for ($i = 0; $i <= $count; $i++) {
		$subtitle = exploitPatch($subtitlePre[$i]);
		$subtext = exploitPatch($subtextPre[$i]);
		$guideinfo .= $i == 0 ? '' : ',';
		$guideinfo .= "[\"$subtitle\",\"$subtext\"]";
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
	
	$guidId = Guides::uploadGuide($user->userId, $guidetitle, $aftertext, $guideinfo, $language, $img, $templatesString, time(), $wikiId);
	User::TGwebhookLog($GUIDadd.$guidId);
	echo $guidId;
}
