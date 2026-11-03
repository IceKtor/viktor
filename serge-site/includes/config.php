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
