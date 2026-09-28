<?php
require_once '../api/api.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
	$email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
	if (!$email) {
		echo '1';
		exit();
	}
	$date = time();
	$user = Users::fetchByEmail($email);
	$token = bin2hex(random_bytes(32));
	if ($user !== false) 
		User::dropPasswordPre($token, $date, $email, $user->username);
	echo '1';
}