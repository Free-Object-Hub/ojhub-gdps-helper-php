<?php
session_start();
require_once '../api/api.php';
require_once $cityInclPath;
require_once $uaparserInclPath;
use WhichBrowser\Parser;
$user = 0;

function doLogin() {
	$username = htmlspecialchars($_POST['username'], ENT_QUOTES, 'UTF-8');
	$password = $_POST['password'];
	$user = null;
	
	if (!filter_var($username, FILTER_VALIDATE_EMAIL))
		$user = Users::fetchByUsername($username);
	else
		$user = Users::fetchByEmail($username);

	if ($user === false)
		return '-2';
	if (!$user->verifyPassword($password)) 
		$user = '-1';

	if ($user == '-1')
		return '-1';
	
	$cityData = getCity($_SERVER['REMOTE_ADDR']);
	$deviceData = new Parser($_SERVER['HTTP_USER_AGENT']);
	if (!$user->checkDevice($_SERVER['HTTP_USER_AGENT'], $cityData[0], $cityData[1], $_POST['device']))
		$user->addDevice(
			$_SERVER['HTTP_USER_AGENT'],
			$_SERVER['REMOTE_ADDR'],
			$cityData[0],
			$cityData[1],
			$deviceData->os->getName().' '.$deviceData->os->getVersion(),
			$deviceData->browser->getName().' '.$deviceData->browser->getVersion(),
			$_POST['device'],
			$_POST['deviceDynamic']
		);
	return $user;
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	if (IGNORE_CAP) {
		$user = doLogin();
	} elseif (IGNORE_CAP === false && isset($_POST['g-recaptcha-response'])) {
		$url = 'https://www.google.com/recaptcha/api/siteverify';
		$response = file_get_contents($url."?secret=".$secretKey."&response=".$_POST['g-recaptcha-response']."&remoteip=".$_SERVER['REMOTE_ADDR']);
		$data = json_decode($response);
		if($data->success) {
			if (isset($_POST['username']) && isset($_POST['password'])) {
				$user = doLogin();
			}
		} else {
			$user = '-3';
		}
	} else $user = '-3';
}
if (gettype($user) == 'string') 
	exit($user);

loginT($user->token, true);