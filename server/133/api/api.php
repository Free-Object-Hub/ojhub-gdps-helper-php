<?php
error_reporting(-1);//81

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

include __DIR__ . '/../../../build.php';
define('HELPER_VER', $build);
//define('HELPER_URL', 'https://gdpshelper.xyz/');
define('HELPER_URL', 'https://objecthub.xyz/');

if(session_status() === PHP_SESSION_ACTIVE) {
	if(!isset($_SESSION['lgbtBan']))
		$_SESSION['lgbtBan'] = 1;
} 

require __DIR__ . '/gdpses.php';
require __DIR__ . '/search.php';
require __DIR__ . '/alarms.php';
require __DIR__ . '/users.php';
require_once __DIR__ . '/configs.php';

require_once $cityInclPath;

if (isset($_POST['token']))
	$_SESSION['user'] = $_POST['token'];

function GDPSswitchChannel(int $channel) {
	switch ($channel) {
		case 0: return 'c';
		case 1: return 's';
		case 2: return 'p';
	}
}
function GDPSswitchChannel2(int $channel) {
	switch ($channel) {
		case 0: return 'camp';
		case 1: return 'show';
		case 2: return 'pere';
	}
}
function loginT($token = '', bool $showToken = false) {
	$return = [
		[], // userData
		[[],[]], // ownerData, [0] is gdpses, [1] is wikis
		[], // cached gdpses
		[] // cached news
	];
	$userId = 0;
	$cityData = getCity($_SERVER['REMOTE_ADDR']);
	if(session_status() === PHP_SESSION_NONE) 
		session_start();
	$_SESSION['lgbtBan'] = 0;
	global $lgbtBanCountries;
	foreach ($lgbtBanCountries as $c) {
		if ($c == $cityData[0]) {
			$_SESSION['lgbtBan'] = 1;
			break;
		}
	}
	if ($token != '') {
		$user = Users::fetchByTokenAndDevice($token, $_POST['device']);
		if ($user !== false) {
			$userId = $user->userId;
			$return[0] = [
				'username' => $user->getNickname(),
				'ID' => $user->userId,
				'role' => $user->priority,
				'isActive' => $user->activated,
				'token' => $user->token,
				'resume' => $user->resume,
				'socials' => $user->socials,
				'cityData' => $cityData, // [country, city|'unknown']
				'hasAlarms' => Alarms::checkAlarms($userId)
			];

			if (isset($user)) {
				//your projects
				$gdpses = content::getAllMyContent($userId);
				foreach ($gdpses[0] as $gdps) {
					if ($gdps != null) {
						$gChannel = GDPSswitchChannel($gdps->channel);
						$return[1][0][$gChannel.$gdps->ID] = $gdps->GDPSrenderLT($gChannel);
					}
				}
				foreach ($gdpses[1] as $gdps) {
					if ($gdps != null) {
						$gChannel = GDPSswitchChannel($gdps->channel);
						$return[1][0][$gChannel.$gdps->ID] = $gdps->GDPSrenderLT($gChannel);
					}
				}

				$wikis = Guides::getMyContent($userId);
				foreach ($wikis[0] as $wiki) {
					if ($wiki != null) {
						$return[1][1]['w'.$wiki->ID] = $wiki->renderWikiLT2();
					}
				}
				foreach ($wikis[1] as $wiki) {
					if ($wiki != null) {
						$return[1][1]['w'.$wiki->ID] = $wiki->renderWikiLT2();
					}
				}
			}
		} else {
			$return = [
				[
					'username' => 'Object Hub',
					'ID' => 0,
					'role' => 0,
					'isActive' => 0,
					'hasAlarms' => 0,
					'resume' => '',
					'socials' => '',
					'token' => '',
					'cityData' => $cityData
				],
				[[],[]]
			];
		}
	} else {
		$return = [
			[
				'username' => 'Object Hub',
				'ID' => 0,
				'role' => 0,
				'isActive' => 0,
				'hasAlarms' => 0,
				'resume' => '',
				'socials' => '',
				'token' => '',
				'cityData' => $cityData
			],
			[[],[]]
		];
	}
	$gdpsesLast = NewGdpsFinder($userId,1,-2,0);
	foreach ($gdpsesLast as $gdps) {
		if ($gdps != null) {
			$gChannel = GDPSswitchChannel($gdps->channel);
			$return[2][$gChannel.$gdps->ID] = $gdps->GDPSrenderLT($gChannel);
		}
	}

	$gdpses = content::fetchAllNews($userId, 0);
	foreach ($gdpses as $gdps) {
		if ($gdps != null) {
			$return[3]['n'.$gdps->ID] = $gdps->newsRenderObj($userId);
		}
	}
	echo renderJson($return);
}

if (!function_exists('mime_content_type')) {
    function mime_content_type($filename) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if (!$finfo) {
            return 'application/octet-stream';
        }
        $mime = finfo_file($finfo, $filename);
        finfo_close($finfo);
        return $mime ?: 'application/octet-stream';
    }
}

$_HEADER = getallheaders();
if (isset($_HEADER['user-token']))
	$_POST['token'] = $_HEADER['user-token'];
if (isset($_HEADER['device-static']))
	$_POST['device'] = $_HEADER['device-static'];
if (isset($_HEADER['User-Token']))
	$_POST['token'] = $_HEADER['User-Token'];
if (isset($_HEADER['Device-Static']))
	$_POST['device'] = $_HEADER['Device-Static'];
