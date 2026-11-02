<?php
// подключение к базе. ПОКА ЗАГЛУШКА - нигде не вызывается, пока USE_DB = false.
// TODO(DB): раскомментировать, когда появится база (схема - database/schema.sql)

require_once __DIR__ . '/config.php';

function db() {
    // TODO(DB):
    // static $pdo = null;
    // if ($pdo === null) {
    //     $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    //     $pdo = new PDO($dsn, DB_USER, DB_PASS, array(
    //         PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    //         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    //     ));
    // }
    // return $pdo;
    return null;
}
