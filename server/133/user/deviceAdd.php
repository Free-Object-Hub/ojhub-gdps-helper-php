<?php
require_once '../api/api.php';
require_once $cityInclPath;
require_once $uaparserInclPath;
use WhichBrowser\Parser;

$user = Users::fetchByToken($_POST['token']);

if (isset($_POST['password'])) {
	if ($user->verifyPassword($_POST['password'])) {
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
		loginT($user->token);
	} else {
		echo "-1";
	}
}