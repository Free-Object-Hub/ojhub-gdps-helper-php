<?php 
require_once './api/api.php';

if (!isset($_POST['token']))
exit('Access denied');

$user = Users::fetchByToken($_POST['token']);

$uText = '; user- '.$user->userId;

if ($user->priority < 1)
exit('Access denied');

switch ($_GET['type']) {
	case 0:
		$where = 'gdpses';
		break;
	case 1:
		$where = 'guides';
		break;
	case 2:
		$where = 'wikis';
		break;
	default: exit('Access denied');
}

switch ($_GET['action']) {
	case 'activate':
		$conn->query('UPDATE `'.$where.'` SET `checked` = 1 WHERE `'.$where.'`.`ID` = ' . (int)$_GET['id'])->execute();
		echo 1;
		if ($_GET['type'] == 0)
			User::TGwebhookLog($GDPSunban.$_GET['id'].$uText);
		elseif ($_GET['type'] == 1)
			User::TGwebhookLog($TEXTunban.$_GET['id'].$uText);
		else
			User::TGwebhookLog($GUIDunban.$_GET['id'].$uText);
		break;
	case 'ban':
		$conn->query('UPDATE `'.$where.'` SET `checked` = -1 WHERE `'.$where.'`.`ID` = ' . (int)$_GET['id'])->execute();
		echo -1;
		if ($_GET['type'] == 0)
			User::TGwebhookLog($GDPSban.$_GET['id'].$uText);
		elseif ($_GET['type'] == 1)
			User::TGwebhookLog($TEXTban.$_GET['id'].$uText);
		else
			User::TGwebhookLog($GUIDban.$_GET['id'].$uText);
		break;
	case 'delete':
		if ($user->priority < 2)
			exit('Access denied');
		$conn->query('DELETE FROM `'.$where.'` WHERE `'.$where.'`.`ID` = ' . (int)$_GET['id'])->execute();
		echo -2;
		if ($_GET['type'] == 0)
			User::TGwebhookLog($GDPSdelete.$_GET['id'].$uText);
		elseif ($_GET['type'] == 1)
			User::TGwebhookLog($TEXTdelete.$_GET['id'].$uText);
		else
			User::TGwebhookLog($GUIDdelete.$_GET['id'].$uText);
		break;
	case 'setlgbt':
		$conn->query('UPDATE `'.$where.'` SET `hasLgbt` = 1 WHERE `'.$where.'`.`ID` = ' . (int)$_GET['id'])->execute();
		echo 0;
		if ($_GET['type'] == 0)
			User::TGwebhookLog($GDPSlgbt1.$_GET['id'].$uText);
		elseif ($_GET['type'] == 1)
			User::TGwebhookLog($TEXTlgbt1.$_GET['id'].$uText);
		else
			User::TGwebhookLog($GUIDlgbt1.$_GET['id'].$uText);
		break;
	case 'dellgbt':
		$conn->query('UPDATE `'.$where.'` SET `hasLgbt` = 0 WHERE `'.$where.'`.`ID` = ' . (int)$_GET['id'])->execute();
		echo 0;
		if ($_GET['type'] == 0)
			User::TGwebhookLog($GDPSlgbt2.$_GET['id'].$uText);
		elseif ($_GET['type'] == 1)
			User::TGwebhookLog($TEXTlgbt2.$_GET['id'].$uText);
		else
			User::TGwebhookLog($GUIDlgbt2.$_GET['id'].$uText);
		break;
	default: exit('Access denied');
}