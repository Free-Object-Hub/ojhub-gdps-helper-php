<?php
require_once '../api/api.php';

$user = Users::fetchByTokenAndDevice($_POST['token'], $_POST['device']);
if (!$user)
	exit('Access denied');

if ($user->activated == 0) {
	$val = content::getValue('freejoin', 'gdpses', $_GET['id']);
	switch ($val) {
		case 0: 
			$val2 = 1;
			echo 'no';
			break;
		case 1:
			$val2 = 0;
			echo 'yes';
			break;
	}
	exit();
}

if (content::checkItem($user->userId, $_GET['id'], 0)) {
	$val = content::getValue('freejoin', 'gdpses', $_GET['id']);
	switch ($val) {
		case 0: 
			$val2 = 1;
			echo 'yes';
			break;
		case 1:
			$val2 = 0;
			echo 'no';
			break;
	}
	content::ChangeValue('freejoin', 'gdpses', $val2, $_GET['id']);
} else {
	exit('-2');
}?>