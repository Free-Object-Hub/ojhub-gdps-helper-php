<?php 
session_start();
require_once '../api/api.php';
require_once $cityInclPath;

$user = Users::fetchByToken($_POST['token']);
$cityData = getCity($_SERVER['REMOTE_ADDR']);

if ($user->checkDevice($_SERVER['HTTP_USER_AGENT'], $cityData[0], $cityData[1], $_POST['device']))
	$user->removeDevice($_SERVER['HTTP_USER_AGENT'], $_SERVER['REMOTE_ADDR'], $cityData[0], $cityData[1]);