<?php


// Telegram Bot Configuration
$TGbotToken = defined('TG_BOT_TOKEN') ? TG_BOT_TOKEN : '';
$TGnewsResender = defined('TG_NEWS_RESENDER') ? TG_NEWS_RESENDER : -1003033986460;
$TGlogsChat = defined('TG_LOGS_CHAT') ? TG_LOGS_CHAT : -1002563753011;
$TGlogsTheme = defined('TG_LOGS_THEME') ? TG_LOGS_THEME : 3520;
$TGnewWebhoks = defined('TG_NEW_WEBHOOKS') ? TG_NEW_WEBHOOKS : '';

// reCAPTCHA Configuration
$siteKey = defined('RECAPTCHA_SITE_KEY') ? RECAPTCHA_SITE_KEY : '';
$secretKey = defined('RECAPTCHA_SECRET_KEY') ? RECAPTCHA_SECRET_KEY : '';

// Discord Webhooks
$WHcomms = defined('DISCORD_WEBHOOK_COMMS') ? DISCORD_WEBHOOK_COMMS : '';
$reportUrl = defined('DISCORD_WEBHOOK_REPORTS') ? DISCORD_WEBHOOK_REPORTS : '';

define('FILES_PATH', value: '/usr/local/ojhub/php/imgs/');

define('IGNORE_CAP', value: false);

function renderJson($json) {
	return json_encode($json, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}

function exploitPatch($input, $ignoreHtml = false) {
	if (!is_string($input))
		return $input;
	
	if ($input === '')
		return '';

	$input = str_replace('\\', '\\\\', $input);
	if (!$ignoreHtml)
		$input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
	return $input;
}

function exploitPatchUrl($url) {
    if (!is_string($url))
        return $url;
    if ($url === '')
        return '';

    // убираем null-байты и управляющие символы
    $url = preg_replace('/[\x00-\x1F\x7F]/', '', $url);

    // разрешаем только http/https (защита от javascript:, data:, file:, php: и т.п.)
    $parts = parse_url($url);
    if ($parts === false || !isset($parts['scheme']) || !isset($parts['host']))
        return '';

    $scheme = strtolower($parts['scheme']);
    if (!in_array($scheme, ['http', 'https']))
        return '';

    // host: только допустимые для домена символы
    $host = preg_replace('/[^a-zA-Z0-9.\-]/', '', $parts['host']);
    if ($host === '')
        return '';

    $port = isset($parts['port']) ? ':' . (int)$parts['port'] : '';

    // path: убираем traversal-паттерны, нормализуем множественные слэши
    $path = $parts['path'] ?? '';
    $path = str_replace('\\', '/', $path);          // бэкслэши в слэши (обход на Windows-based парсерах)
    $path = preg_replace('#/+#', '/', $path);        // схлопываем //
    $path = preg_replace('#(^|/)\.\.(/|$)#', '$1', $path); // убираем сегменты ".."
    // на случай вложенных/закодированных остатков — повторяем, пока не перестанет меняться
    do {
        $prev = $path;
        $path = preg_replace('#(^|/)\.\.(/|$)#', '$1', $path);
    } while ($path !== $prev);
    $path = preg_replace('#/\.(/|$)#', '/', $path);  // убираем одиночные "."

    // query и fragment: убираем управляющие символы, остальное оставляем как есть
    $query = isset($parts['query']) ? '?' . preg_replace('/[\x00-\x1F\x7F]/', '', $parts['query']) : '';
    $fragment = isset($parts['fragment']) ? '#' . preg_replace('/[\x00-\x1F\x7F]/', '', $parts['fragment']) : '';

    $result = $scheme . '://' . $host . $port . $path . $query . $fragment;

    return substr($result, 0, 2048);
}

function exploitPatchFile($filename) {
	if (!is_string($filename))
		return $filename;
	if ($filename == '')
		return '';
	$allowedExtensions = ['webp', 'jpg', 'png', 'pdf', 'txt'];
	$filename = str_replace("\x00", '', $filename);
	$basename = basename($filename);
	$ext = strtolower(pathinfo($basename, PATHINFO_EXTENSION));
	if (!in_array($ext, $allowedExtensions))
		exit('Invalid file type');
	$name = pathinfo($basename, PATHINFO_FILENAME);
	$cleanName = preg_replace('/[^a-zA-Z0-9_-]/', '', $name);
	$cleanName = trim($cleanName, '.-_');
	if (empty($cleanName))
		$cleanName = 'file';
	$result = $cleanName . '.' . $ext;
	return substr($result, 0, 255);
}

$userIp = $_SERVER['REMOTE_ADDR'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'];

$lgbtBanCountries = ['Russia'];

// Load secrets from secretCfg.php if it exists, otherwise use defaults (for development)

//ГДПСы, формируется в Webhook() как $GDPScomm1.$gdps->gdpsId.$GDPScomm2.$comm->text
$GDPScomm1 = 'Коммент к контенту (канал комментов; айди контента) ';
$GDPScomm11 = 'Изменён коммент (группа комментов; айди контента; айди коммента) ';
$GDPScomm2 = '
его текст
';
$GDPScomm3 = 'Новая новость!!! проектайди ';
$GDPScomm31 = 'Изменена новость!!! проектайди ';
$forumUplo01 = 'Новый пост в форуме!!! форумайди ';

$GDPSuncomm1 = 'Удалён комментарий, канал - ';
$GDPSuncomm2 = 'Удалена новость ';

//а это уже гайды, формируется как $GUIDcomm1.$whereiz.$GUIDcomm2.$comm->text
$GUIDcomm1 = 'Коммент к гайду под https://gdpshelper.xyz/guides/';
$GUIDcomm2 = '
его текст
';

//Новости гдпсов, дада надо
$NEWStext1 = 'Новый пост в новостях от ';
$NEWStext2 = '!
gdpsId - ';
$NEWStext3 = '
текст поста таков:
';

// формируется как User::Webhook($WHcomms, $NEWStext1.$user->username.$NEWStext2.$gdpsid.$NEWStext3.$text2);

//дальше идут логи для гдпсов и текстурпаков, прописал я им один вебхук
$WHcontent = defined('DISCORD_WEBHOOK_CONTENT') ? DISCORD_WEBHOOK_CONTENT : '';

$GDPSadd = 'Новый проект! Айди/Канал:
';
$GUIDadd = 'Новый гайд! Название:
';
$WIKIadd = 'Новый вики! Название:
';
$FORUadd = 'Новый форум! Айди:
';
$GDPSedit = 'Изменён проект! Айди/Канал:
';
$GUIDedit = 'Изменён гайд! Айди:
';
$WIKIedit = 'Изменён вики! Айди:
';
//У всех них генерация такая $?.$gdps->gdpsId

//У всех них генерация разная

//логи инструментов администрации

$WHadmin = defined('DISCORD_WEBHOOK_ADMIN') ? DISCORD_WEBHOOK_ADMIN : '';
$GDPSban = 'Забанен ПРОЕКТ - ';
$GDPSunban = 'Разбанен ПРОЕКТ - ';
$GDPSdelete = 'Удалён ПРОЕКТ - ';
$GDPSlgbt1 = 'это ЛГБТ ПРОЕКТ - ';
$GDPSlgbt2 = 'это НЕ ЛГБТ ПРОЕКТ - ';
$GUIDban = 'Забанен ГАЙД - ';
$GUIDunban = 'Разбанен ГАЙД - ';
$GUIDdelete = 'Удалён ГАЙД - ';
$GUIDlgbt1 = 'это ЛГБТ ГАЙД - ';
$GUIDlgbt2 = 'это НЕ ЛГБТ ГАЙД - ';
$WIKIban = 'Забанен ВИКИ - ';
$WIKIunban = 'Разбанен ВИКИ - ';
$WIKIdelete = 'Удалён ВИКИ - ';
$WIKIlgbt1 = 'это ЛГБТ ВИКИ - ';
$WIKIlgbt2 = 'это НЕ ЛГБТ ВИКИ - ';

//User::Webhook($WHadmin, $GDPSban.$_GET['id']);

function generateUserVetifyCode() {
	return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

$dropPass1 = 'Сброс пароля Object hub';
$dropPass2 = 'Здравствуйте ';$dropPass3 = '!

На ваш аккаунт был запрошен сброс пароля, чтобы установить новый пароль перейдите по данной ссылке:

';$dropPass4 = '

Ссылка действительна в течении 24 часов. Если вы не запрашивали сброс пароля проигнорируйте это письмо. Если у вас есть аккаунт рекомендуем перепроверить список устройств';

$verifyAcc1 = 'Подтверждение аккаунта Object hub';
$verifyAcc2 = 'Здравствуйте ';$verifyAcc3 = '!

Ваш адрес электронной почты был указан при регистрации на Object hub

Нажмите на ссылку для подтверждения вашего аккаунта: https://objecthub.xyz/verify.php
После введите следующий код в соответствующую строку:
';
$verifyAcc4 = '
Если вы не регистрировались на сайте, то проигнорируйте это сообщение';
