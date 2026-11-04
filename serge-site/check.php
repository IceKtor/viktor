<?php
ini_set('display_errors', 1); error_reporting(E_ALL);
echo 'PHP ' . PHP_VERSION . "<br>";
echo 'config.local.php: ' . (is_file(__DIR__ . '/includes/config.local.php') ? 'найден' : 'НЕ НАЙДЕН') . "<br>";
require __DIR__ . '/includes/config.php';
echo 'USE_DB: ' . (USE_DB ? 'true' : 'false') . "<br>";
echo 'pdo_mysql: ' . (extension_loaded('pdo_mysql') ? 'есть' : 'НЕТ') . "<br>";
try {
    require __DIR__ . '/includes/db.php';
    echo 'таблиц в базе: ' . count(db_all('SHOW TABLES')) . "<br>";
    echo 'настроек: ' . db_val('SELECT COUNT(*) FROM settings') . "<br>";
} catch (Throwable $e) { echo 'ОШИБКА: ' . htmlspecialchars($e->getMessage()); }