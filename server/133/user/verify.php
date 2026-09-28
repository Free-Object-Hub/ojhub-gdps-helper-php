<?php
require_once '../api/api.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
	$user = Users::fetchByToken($_POST['token']);
	if ($user !== false)
		if ($user->verifyAcc($_POST['code']))
			echo $user->userId;
		else 
			echo '-1';
	else 
		echo '-2';
}