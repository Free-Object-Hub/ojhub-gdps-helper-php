<?php
session_start();
require_once '../api/api.php';
require_once $cityInclPath;
require_once $uaparserInclPath;
use WhichBrowser\Parser;
$user = 0;

error_reporting(E_ALL);

function doRegister() {
	$username = htmlspecialchars($_POST['username'], ENT_QUOTES, 'UTF-8');
	$password = $_POST['password'];
	$email = htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8');
	$activated = generateUserVetifyCode();
	$token = hash('sha256', Users::randomString(16).$username);

	if (!User::hasUsed($email, $username)) {
		if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$token = Users::newUserToken($username, $password, $email, $activated, $token);
			$_SESSION['user'] = $token;
			$user = Users::fetchByToken($token);//json_decode('{"token":"'.$token.'"}');
			Users::sendMail($user->username, $_POST['email'], $activated);
		} else 
			$user = '-4';
	} else
		$user = '-4';

	$cityData = getCity($_SERVER['REMOTE_ADDR']);
	$deviceData = new Parser($_SERVER['HTTP_USER_AGENT']);
	if (gettype($user) !== 'string' && !$user->checkDevice($_SERVER['HTTP_USER_AGENT'], $cityData[0], $cityData[1], $_POST['device']))
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
		$user = doRegister();
	} elseif (IGNORE_CAP === false && isset($_POST['g-recaptcha-response'])) {
		$url = 'https://www.google.com/recaptcha/api/siteverify';
		$response = file_get_contents($url."?secret=".$secretKey."&response=".$_POST['g-recaptcha-response']."&remoteip=".$_SERVER['REMOTE_ADDR']);
		$data = json_decode($response);
		if($data->success) {
			if (isset($_POST['username']) && isset($_POST['password'])) {
				$user = doRegister();
			}
		} else {
			$user = '-2';
		}
	} else $user = '-2';
}
if (gettype($user) == 'string') 
	exit($user);

loginT($user->token, true);
