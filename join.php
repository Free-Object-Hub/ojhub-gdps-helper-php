<?php
session_start();
require_once 'build.php';
require_once './server/'.$build.'/api/api.php';

error_reporting(E_ALL);

if (isset($_POST['token'])) $user = Users::fetchByToken($_SESSION['user']);

$gdps = content::fetchById($_GET['id'], 0);

$findKey = $_SESSION['findKey'] ?? 'id='.$_GET['id'];
if (isset($_GET['m']))
    if ($_GET['m'] == 1)
        $findKey = 'mainPage';

//if ($gdps->checked != 1)
    //exit("Пошёл нахуй");

$link = '';
$lastUrl = '';
$count = 0;
if (str_starts_with($gdps->link, '{'))
    foreach (json_decode(str_replace('\\"','"', $gdps->link)) as $type => $url) {
        $count += 1;
        $lastUrl = $url;
        $link .= "<a href=\"$url\">$type</a><br>";
        if (isset($_GET['type']) && $_GET['type'] == $type) {
            $count = 0;
            $findKey .= '&'.$_GET['type'];
            $link = '<script>location.href = "'.$url.'"</script>';
            break;
        }
    }
else
    $link = '<script>location.href = "'.$gdps->link.'"</script>';

if ($count == 1)
    $link = '<script>location.href = "'.$lastUrl.'"</script>';

if (isset($_POST['username']) && isset($_POST['password'])) {
    $user = Users::fetchByUsername($_POST['username']);
    if ($user != null) {
        if ($user->verifyPassword($_POST['password'])) {
            echo $link;
            Users::logJoin($_GET['id'], $user->userId, $findKey);
            exit();
        }
    }
}

if ($gdps->freejoin === 0) {
    echo $link;
    if (isset($_POST['token'])) 
        Users::logJoin($_GET['id'], $user->userId, $findKey);
    else
        Users::logJoin($_GET['id'], 0, $findKey);
    exit();
} else {
    if (isset($_POST['token'])) {
        echo $link;
        Users::logJoin($_GET['id'], $user->userId, $findKey);
        exit();
    }
}