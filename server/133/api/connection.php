<?php
require_once 'secretCfg.php';

error_reporting(E_ALL);

$dbHost = defined('DB_HOST') ? DB_HOST : '';
$dbPort = defined('DB_PORT') ? DB_PORT : '';
$dbName = defined('DB_NAME') ? DB_NAME : '';
$dbUser = defined('DB_USER') ? DB_USER : '';
$dbPass = defined('DB_PASS') ? DB_PASS : '';

try {
	$dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName}";
	$conn = new PDO($dsn, $dbUser, $dbPass, array(
		PDO::ATTR_PERSISTENT => true
	));
	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$conn->exec("SET NAMES 'utf8mb4'");
} catch (PDOException $e) {
	// Don't expose database connection details in error messages
	error_log("Database connection error: " . $e->getMessage());
	exit("Database connection failed. Please check your configuration.");
}
