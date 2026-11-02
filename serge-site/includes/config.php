<?php
// настройки сайта.
// TODO(DB): когда будет база (MySQL + phpMyAdmin в Open Server) - поставить USE_DB = true и вписать доступы.
// Пока сайт читает данные из data/*.json, подключения к базе нет.

define('USE_DB', false);

define('DB_HOST', 'localhost');
define('DB_NAME', 'serge');          // TODO(DB): имя базы, создать в phpMyAdmin
define('DB_USER', 'root');           // TODO(DB): в Open Server по умолчанию root без пароля, на хостинге заменить
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// полный адрес сайта без слэша в конце. Пусто = определять автоматически (для Open Server).
// TODO: при публикации на хостинге вписать, например 'https://example.ru'
define('SITE_URL', getenv('SERGE_SITE_URL') ?: '');   // для статической выгрузки адрес берётся из переменной окружения

// true = подключать *.min.css и *.min.js, если они есть (создаются скриптом tools/minify.sh); false = исходные файлы
define('ASSET_MIN', true);
