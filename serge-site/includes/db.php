<?php
// Подключение к MySQL (PDO). Вызывается только когда USE_DB = true.
require_once __DIR__ . '/config.php';

function db() {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ));
    }
    return $pdo;
}

// короткие помощники: все запросы только подготовленные, значения передаются отдельно
function db_all($sql, $params = array()) { $s = db()->prepare($sql); $s->execute($params); return $s->fetchAll(); }
function db_row($sql, $params = array()) { $s = db()->prepare($sql); $s->execute($params); $r = $s->fetch(); return $r === false ? null : $r; }
function db_val($sql, $params = array()) { $s = db()->prepare($sql); $s->execute($params); $r = $s->fetchColumn(); return $r === false ? null : $r; }
function db_exec($sql, $params = array()) { $s = db()->prepare($sql); $s->execute($params); return $s->rowCount(); }
function db_insert($sql, $params = array()) { db_exec($sql, $params); return (int)db()->lastInsertId(); }
