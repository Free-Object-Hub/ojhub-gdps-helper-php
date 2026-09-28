<?php
require_once './api/api.php';

$url = 'https://discord.com/api/webhooks/1278026501907808318/VfouS84pVICLMitvgfr8gkRKFmRk_Zyi0PA9VPGX_ZqNDg_Zj0U_I1Tpsu6-Mobl00Ln';
$text = 'Жалоба на сайт. её текст:

```'.mb_substr(
	str_replace(
		'<br>',
		"\n",
		$_POST['error']
	), 0, 1800).'```';

User::TGwebhookLog($text);