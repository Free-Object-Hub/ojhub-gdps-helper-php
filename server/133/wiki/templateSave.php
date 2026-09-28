<?php
require_once '../api/api.php';

$user = Users::fetchByTokenAndDevice($_POST['token'], $_POST['device']);
if (!$user)
	exit('Access denied');

if (!content::checkItem($user->userId,$_POST['id'],-1) == true)
	exit('-1');

function allowedMethods($method) {
	switch ($method) {
		case 'Markdown':
			return 'Markdown';
		case 'wikiText':
			return 'wikiText';
		default:
			return 'Markdown';
	}
}
function doSafeName($name) {
	$safeName = preg_replace('/[^a-zA-Z0-9_\-]/', '', $name);
	if (is_numeric($safeName[0]))
		return 'falseName';
	return $safeName;
}

function doSafeCode($mdCode, $args) {
	$mdCode = str_replace('`', '', $mdCode);
	if (preg_match_all('/\$\{([^}]+)\}/s', $mdCode, $matches)) {
		foreach ($matches[1] as $param) {
			if (!$args) {
				$mdCode = str_replace('${' . $param . '}', '', $mdCode);
			} elseif (!in_array(trim($param), $args)) {
				$mdCode = str_replace('${' . $param . '}', '', $mdCode);
			}
		}
	}
	$mdCode = preg_replace('/\{\{[^}]+\}\}/s', '', $mdCode);
	$mdCode = str_replace(
		['\u', '\x', '\\'],
		['u', 'x', '\\\\'],
		$mdCode
	);
	//$mdCode = json_encode($mdCode, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);

	//$mdCode = substr($mdCode, 1, -1);
	
	return $mdCode;
}

if ($_POST['id'] != 0) {
	$wiki = Guides::fetchWikiById($_POST['id']);
	$name = doSafeName($_POST['name']);
	$method = allowedMethods($_POST['method']);

	$args = '';

	if (isset($_POST['arg'])) {
		foreach ($_POST['arg'] as $a)
			$args .= ',"'.doSafeName($a).'"';
		$args = '['.trim($args, ',').']';
	} else 
		$args = '';

	$content = doSafeCode($_POST['content'], json_decode($args));

	$wiki = WikiTemplates::saveTemplate($wiki->ID, $name, $args, $method,$content);
	echo renderJson($wiki->renderTemplate());
}
