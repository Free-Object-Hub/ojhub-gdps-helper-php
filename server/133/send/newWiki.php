<?php
require_once '../api/api.php';

$user = Users::fetchByTokenAndDevice($_POST['token'], $_POST['device']);
if (!$user)
	exit('Access denied');

if (isset($_POST['title'])) {
	$title = exploitPatch($_POST['title']);
	$language = exploitPatch($_POST['language']);
	$text = exploitPatch($_POST['text']);
	$img = exploitPatchUrl($_POST['img']);

	$wikiId = Guides::uploadWiki($user->userId, $title, $text, $language, $img, time());
	User::TGwebhookLog($WIKIadd.$wikiId);
	echo renderJson(Guides::fetchWikiById($wikiId)->renderWikiLT2());
}
