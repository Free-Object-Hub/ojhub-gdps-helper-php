<?php
session_start();
require_once '../api/api.php';

/*
@ описани е
*/

error_reporting(E_ALL);

$userId = 0;
if (isset($_POST['token'])) {
	$user = Users::fetchByToken($_POST['token']);
	$userId = $user->userId;
}

$tags = $_GET['tags'] ?? [];
$os = $_GET['os'] ?? [];
$name = $_GET['name'] ?? '';

if (isset($_SESSION['lgbtBan']))
	$lgbtVal = $_SESSION['lgbtBan'];
else 
	$lgbtVal = 1;

$gdpses = NewGdpsFinder(
	$userId,
	$_GET['method'],
	$_GET['channel'],
	$_GET['page'],
	$tags,
	$os,
	$name,
	$lgbtVal);

$_SESSION['findKey'] = "method=".$_GET['method']."&page=".$_GET['page'];

isset($_GET['tags']) ?
$_SESSION['findKey'] .= "&tags=".implode('&tags=', $_GET['tags'])
: null;

isset($_GET['os']) ?
$_SESSION['findKey'] .= "&os=".implode('&os=', $_GET['os'])
: null;

isset($_GET['name']) ?
$_SESSION['findKey'] .= "&name=".$_GET['name']
: null;

$result = [];

foreach ($gdpses as $gdps) {
	switch ($_GET['channel']) {
		case 2:
			$result['p'.$gdps->ID] = $gdps->GDPSrenderLT('p', $userId);
			// echo $gdps->GDPSrenderLT('p', $userId);
			break;
		case 1:
			$result['s'.$gdps->ID] = $gdps->GDPSrenderLT('s', $userId);
			// echo $gdps->GDPSrenderLT('s', $userId);
			break;
		case 0:
			$result['c'.$gdps->ID] = $gdps->GDPSrenderLT('c', $userId);
			// echo $gdps->GDPSrenderLT('c', $userId);
			break;
		case -1:
			$result['w'.$gdps->ID] = $gdps->renderWiki($userId);
			// echo '"w'.$gdps->ID.'":'.$gdps->renderWiki($userId);
			break;
		case -5:
			$result['v'.$gdps->ID] = $gdps->renderVacanMini($userId);
			// echo '"v'.$gdps->ID.'":'.$gdps->renderVacanMini($userId);
			break;
	}
}

echo json_encode($result);