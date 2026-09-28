<?php 
require_once '../api/api.php';

error_reporting(E_ALL);
$wiki = Guides::fetchWikiById($_GET['id']);
$user = Users::fetchByTokenAndDevice($_POST['token'], $_POST['device']);
if (!$user)
	exit('Access denied');

if (Guides::checkPerms($user->userId, $wiki->ID) == false) 
	exit('-2');

if ($wiki == null) 
	exit('["NONE"]');
	
if (isset($_POST['title'])) {
	$title = exploitPatch($_POST['title']);
	$text = exploitPatch($_POST['text']);
	$language = exploitPatch($_POST['language']);
	$img = exploitPatchUrl($_POST['img']);
	
	Guides::editWiki($user->userId, $title, $text, $language, $img, $_POST['wikiId']);
	User::TGwebhookLog($WIKIedit.$wiki->ID);

	$wiki = Guides::fetchWikiById($_GET['id']);
	echo renderJson($wiki->renderWikiLT2());
}
