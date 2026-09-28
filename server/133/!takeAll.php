<?php 
require_once './api/api.php';

if (empty($_POST['token']))
exit('Access denied - No user Data');

$user = Users::fetchByToken($_POST['token']);

if ($user->priority == 0)
exit('Access denied - No perma');

error_reporting(81);

$f = false;

echo '[';

echo '[';
$gdpses = content::fetchAlls(0);
foreach ($gdpses as $gdps) {
	echo $f ? ',' : '';
	$f = true;;
		$text = $gdps->description;
//"g'.$gdps->ID.'"
		echo '
['.
		$gdps->ID.',"'.
		$gdps->title.'","'.
		str_replace('"', '\\"', $gdps->tags).'","'.
		str_replace('"', '\\"', $gdps->os).'","'.
		urlencode($gdps->database).'","'.
		urlencode($gdps->link).'",'.
		$gdps->checked.','.
		$gdps->author.','.
		$gdps->channel
		.']';
}
echo '],[';

$f = false;

foreach (content::fetchAlls(1) as $gdps) {
	echo $f ? ',' : '';
	$f = true;
		$text = $gdps->description;
		echo '
['.
		$gdps->ID.',"'.
		$gdps->title.'","'.
		str_replace('"', '\\"', $gdps->tags).'","'.
		str_replace('"', '\\"', $gdps->os).'","'.
		urlencode($gdps->database).'","'.
		urlencode($gdps->link).'",'.
		$gdps->checked.','.
		$gdps->author
		.']';
}

echo '],[';

$f = false;

foreach (content::fetchAlls(2) as $gdps) {
	echo $f ? ',' : '';
	$f = true;
		$text = $gdps->description;
		echo '
['.
		$gdps->ID.',"'.
		$gdps->title.'","'.
		urlencode($gdps->img).'",'.
		'null'.','.
		'null'.','.
		'null'.','.
		$gdps->checked.','.
		$gdps->userId
		.']';
}

echo '],[';

$f = false;

foreach (content::fetchAlls(3) as $gdps) {
	echo $f ? ',' : '';
	$f = true;
		$text = $gdps->description;
		echo '
['.
		$gdps->ID.',"'.
		$gdps->title.'","'.
		urlencode($gdps->img).'",'.
		'null'.','.
		'null'.','.
		'null'.','.
		$gdps->checked.','.
		$gdps->userId
		.']';
}


echo ']]';