<?php
/**
 * SECRETS CONFIGURATION FILE
 * 
 * ⚠️ SECURITY WARNING ⚠️
 * 
 * This file contains sensitive credentials. Follow these rules:
 * 1. NEVER commit this file to version control (Git, SVN, etc.)
 * 2. Keep file permissions restricted (chmod 600 recommended)
 * 3. Store this file outside web root if possible
 * 4. Use different credentials for development and production
 * 5. Rotate credentials regularly
 * 
 * INSTRUCTIONS:
 * 1. Copy this file to secretCfg.php (without .example)
 * 2. Fill in your actual credentials
 * 3. Add secretCfg.php to .gitignore
 * 4. Set file permissions: chmod 600 secretCfg.php
 */

// Database Configuration
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'objecthub');
define('DB_USER', 'ojhub');
define('DB_PASS', '');

$cityInclPath = __DIR__.'/../../../country/city.php';
$uaparserInclPath = __DIR__.'/../../../uaparser/autoload.php';

// Telegram Bot Configuration
define('TG_BOT_TOKEN', 'bot');
define('TG_NEWS_RESENDER', -1003033986460);
define('TG_LOGS_CHAT', -1002563753011);
define('TG_LOGS_THEME', 3520);
define('TG_NEW_WEBHOOKS', '');

// reCAPTCHA Configuration
define('RECAPTCHA_SITE_KEY', '6Ldrt0grAAAAAMdteG7pq6LZ1UYeMvkElvUV7Qhx');
define('RECAPTCHA_SECRET_KEY', '');

// Discord Webhooks
define('DISCORD_WEBHOOK_COMMS', 'YOUR_DISCORD_WEBHOOK_URL_FOR_COMMENTS');
define('DISCORD_WEBHOOK_REPORTS', 'YOUR_DISCORD_WEBHOOK_URL_FOR_REPORTS');
define('DISCORD_WEBHOOK_CONTENT', 'YOUR_DISCORD_WEBHOOK_URL_FOR_CONTENT');
define('DISCORD_WEBHOOK_ADMIN', 'YOUR_DISCORD_WEBHOOK_URL_FOR_ADMIN');

