<?php
// Настройки сайта. Свои значения (доступы к базе, ключ установки) кладите в includes/config.local.php
// (образец - config.local.php.example). Этот файл в git не попадает, поэтому пароли в репозиторий не уходят.
if (!defined('STATIC_BUILD') && is_file(__DIR__ . '/config.local.php')) require_once __DIR__ . '/config.local.php';   // в статической выгрузке всегда берутся data/*.json

// true = данные и личные кабинеты берутся из MySQL (схема - database/schema.sql). false = данные из data/*.json, без кабинетов.
if (!defined('USE_DB')) define('USE_DB', false);

if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'serge');
if (!defined('DB_USER')) define('DB_USER', 'root');   // в Open Server по умолчанию root без пароля, на хостинге заменить
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

// полный адрес сайта без слэша в конце. Пусто = определять автоматически (для Open Server).
if (!defined('SITE_URL')) define('SITE_URL', getenv('SERGE_SITE_URL') ?: '');

// true = подключать *.min.css и *.min.js, если они есть (создаются скриптом tools/minify.sh); false = исходные файлы
if (!defined('ASSET_MIN')) define('ASSET_MIN', true);

// ключ первой установки: нужен странице install.php, чтобы создать первого администратора. Пусто = страница закрыта.
if (!defined('SETUP_KEY')) define('SETUP_KEY', '');

// кабинеты и заявки работают только с базой и не в статической выгрузке (GitHub Pages)
if (!defined('ACCOUNTS_ENABLED')) define('ACCOUNTS_ENABLED', USE_DB && !defined('STATIC_BUILD'));

// публичная папка загрузок (фото домов, статей, этапов стройки); относительно корня сайта
if (!defined('UPLOAD_DIR')) define('UPLOAD_DIR', 'assets/uploads');

// Почта (SMTP). Пусто = письма не отправляются. Для Яндекса: MAIL_HOST smtp.yandex.ru, порт 465, MAIL_SECURE 'ssl',
// MAIL_USER - ваш адрес, MAIL_PASS - «пароль приложения» (не основной пароль). Значения - в includes/config.local.php.
// редакция политики и текста согласия: записывается вместе с согласием; при изменении текста обновите дату
if (!defined('CONSENT_VERSION')) define('CONSENT_VERSION', '2026-11-04');
if (!defined('MAIL_HOST')) define('MAIL_HOST', '');
if (!defined('MAIL_PORT')) define('MAIL_PORT', 465);
if (!defined('MAIL_SECURE')) define('MAIL_SECURE', 'ssl');      // 'ssl' (порт 465), 'tls' (STARTTLS, порт 587) или '' (без шифрования, только для проверки)
if (!defined('MAIL_USER')) define('MAIL_USER', '');
if (!defined('MAIL_PASS')) define('MAIL_PASS', '');
if (!defined('MAIL_FROM')) define('MAIL_FROM', MAIL_USER);       // адрес отправителя; у Яндекса и Gmail совпадает с MAIL_USER
if (!defined('MAIL_FROM_NAME')) define('MAIL_FROM_NAME', 'СЭРГЭ');
if (!defined('MAIL_NOTIFY')) define('MAIL_NOTIFY', MAIL_USER);    // куда слать уведомления менеджеру о новых заявках
if (!defined('MAIL_ENABLED')) define('MAIL_ENABLED', MAIL_HOST !== '' && MAIL_USER !== '' && MAIL_FROM !== '');
