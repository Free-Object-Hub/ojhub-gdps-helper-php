<?php
require_once './api/api.php';

if (isset($_POST['token'])) {
	loginT($_POST['token']);
} else {
	loginT();
}