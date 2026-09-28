<?php session_start();
error_reporting(E_ALL);

if (isset($_POST['token']))
    $_SESSION['userF'] = $_POST['token'];
/*
try {
    $conn = new PDO('mysql:host=127.0.0.1;port=3306;dbname=objecthub', 'ojhub', 'Fdoff#93@#f%gad7cbGhg7svj&(jc', array(
            PDO::ATTR_PERSISTENT => true
    ));
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    exit(__DIR__."\\connection.php | Connection error: " . $e->getMessage());
}*/

include '../server/132/api/connection.php';

global $conn;
$req = $conn->prepare('SELECT * FROM `users` WHERE `token` = ?');
$req->execute([$_SESSION['userF']]);
$user = $req->fetchObject();

$priority = $user->priority;

error_reporting(E_ALL);

if ($priority == 0) exit("$priority Access denied");
/*
$message = 'ip '.$_SERVER['REMOTE_ADDR'].'
here <https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'>
pc '.$_SERVER['HTTP_USER_AGENT'];
$webhookUrl = 'https://discord.com/api/webhooks/1215928500603850792/y6rVz_og96WTrjCAUSP7kySamVZGBRLpq0smY9Eqy2nbaJTCePHqo5_S0XfQ5RR6a-TL';

$data = array('content' => $message);
$jsonData = json_encode($data);
$ch = curl_init($webhookUrl);

curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

curl_exec($ch);
curl_close($ch);
*/
