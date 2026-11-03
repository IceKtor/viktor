<?php
// Общий каркас админ-панели: шапка, меню, подвал, помощники для форм.
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/notify.php';

if (!ACCOUNTS_ENABLED || !repo_db_ok()) { http_response_code(503); exit('Админ-панель работает только при подключённой базе данных (USE_DB = true в includes/config.local.php).'); }
$ADM = require_admin();   // не вошёл - на страницу входа, не админ - 403

function post($k, $d = '') { return isset($_POST[$k]) ? trim((string)$_POST[$k]) : $d; }
function post_int($k, $d = 0) { return (isset($_POST[$k]) && is_numeric($_POST[$k])) ? (int)$_POST[$k] : $d; }
function get_int($k, $d = 0) { return (isset($_GET[$k]) && is_numeric($_GET[$k])) ? (int)$_GET[$k] : $d; }
function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// поле формы. $o: type, req, hint, rows, opts (для select: значение => подпись), step, min, max, err (массив ошибок)
function fld($name, $label, $val = '', $o = array()) {
    $t = isset($o['type']) ? $o['type'] : 'text';
    $err = isset($o['err'][$name]) ? $o['err'][$name] : null;
    $req = !empty($o['req']) ? ' required' : '';
    $a = '';
    foreach (array('step', 'min', 'max', 'maxlength', 'placeholder') as $k) if (isset($o[$k])) $a .= ' ' . $k . '="' . h($o[$k]) . '"';
    if (!empty($o['readonly'])) $a .= ' readonly';
    $id = 'f_' . $name;
    $x = '<div class="pole' . ($err ? ' est-oshibka' : '') . '"><label for="' . $id . '">' . h($label) . '</label>';
    if ($t === 'textarea') $x .= '<textarea id="' . $id . '" name="' . h($name) . '" rows="' . (isset($o['rows']) ? (int)$o['rows'] : 4) . '"' . $req . $a . '>' . h($val) . '</textarea>';
    elseif ($t === 'select') {
        $x .= '<select id="' . $id . '" name="' . h($name) . '"' . $req . '>';
        foreach ($o['opts'] as $k => $l) $x .= '<option value="' . h($k) . '"' . ((string)$k === (string)$val ? ' selected' : '') . '>' . h($l) . '</option>';
        $x .= '</select>';
    } else $x .= '<input type="' . h($t) . '" id="' . $id . '" name="' . h($name) . '" value="' . h($val) . '"' . $req . $a . '>';
    if (!empty($o['hint'])) $x .= '<p class="melko">' . h($o['hint']) . '</p>';
    if ($err) $x .= '<p class="oshibka" role="alert">' . h($err) . '</p>';
    return $x . '</div>';
}
function chk($name, $label, $val) { return '<label class="galka"><input type="checkbox" name="' . h($name) . '" value="1"' . ($val ? ' checked' : '') . '> <span>' . h($label) . '</span></label>'; }
function adm_post_guard() { csrf_require(); }
function adm_back($url, $type, $text) { flash_set($type, $text); redirect($url); }

function adm_head($title, $active) {
    global $ADM;
    $new = (int)db_val("SELECT COUNT(*) FROM leads WHERE status = 'new'");
    $nav = array('index' => array('Обзор', 'index.php'), 'leads' => array('Заявки', 'leads.php'), 'clients' => array('Клиенты', 'clients.php'), 'objects' => array('Объекты стройки', 'objects.php'),
        'houses' => array('Проекты домов', 'houses.php'), 'rates' => array('Расценки калькулятора', 'rates.php'), 'stages' => array('Этапы строительства', 'stages.php'),
        'journal' => array('Журнал', 'journal.php'), 'company' => array('О компании', 'company.php'), 'admins' => array('Администраторы', 'admins.php'), 'audit' => array('Журнал действий', 'audit.php'));
    header('X-Robots-Tag: noindex'); header('X-Frame-Options: DENY');
    ?><!DOCTYPE html>
<html lang="ru"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
<title><?= h($title) ?> — админ-панель СЭРГЭ</title>
<link rel="icon" href="../assets/img/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="../<?= h(asset('assets/css/style.css')) ?>">
<link rel="stylesheet" href="../<?= h(asset('assets/css/admin.css')) ?>">
</head><body class="adm">
<header class="adm-top">
    <a class="adm-logo" href="index.php"><b>СЭРГЭ</b> <span>админ-панель</span></a>
    <div class="adm-user"><a href="../index.php">На сайт</a><a href="../profile.php"><?= h($ADM['name']) ?></a>
        <form method="post" action="../logout.php"><?= csrf_field() ?><button type="submit">Выйти</button></form></div>
</header>
<div class="adm-wrap">
<nav class="adm-nav" aria-label="Разделы админ-панели">
<?php foreach ($nav as $k => $n): ?><a href="<?= $n[1] ?>"<?= $k === $active ? ' class="aktiv" aria-current="page"' : '' ?>><?= h($n[0]) ?><?= ($k === 'leads' && $new) ? ' <i class="adm-bubble">' . $new . '</i>' : '' ?></a>
<?php endforeach; ?>
</nav>
<main class="adm-main">
<h1><?= h($title) ?></h1>
<?php foreach (flash_get() as $f): ?><div class="<?= $f[0] === 'ok' ? 'uspeh' : ($f[0] === 'err' ? 'oshibka oshibka--obshaya' : 'podskazka') ?>"><?= h($f[1]) ?></div><?php endforeach; ?>
<?php
}
function adm_foot() { echo "</main></div></body></html>"; }

// одноразовый показ выданных логина и пароля (хранятся только в сессии, в базу открытым текстом не попадают)
function adm_cred_set($name, $login, $password) { acc_session(); $_SESSION['cred'] = array('name' => $name, 'login' => $login, 'password' => $password); }
function adm_cred_show() {
    acc_session();
    if (empty($_SESSION['cred'])) return;
    $c = $_SESSION['cred']; unset($_SESSION['cred']);
    $text = notify_access_text($c['name'], $c['login'], $c['password']);
    echo '<section class="adm-cred"><h2>Доступ выдан — передайте его клиенту</h2><p>Пароль показывается один раз и нигде не сохраняется открытым текстом. При первом входе клиент должен будет сменить его.</p>'
        . '<textarea id="credtext" readonly rows="5">' . h($text) . '</textarea>'
        . '<p><button class="knopka knopka--cher" type="button" onclick="var t=document.getElementById(\'credtext\');t.select();(navigator.clipboard?navigator.clipboard.writeText(t.value):document.execCommand(\'copy\'));this.textContent=\'Скопировано ✓\'">Скопировать сообщение</button> '
        . '<span class="melko">Отправка SMS и почты пока не подключена (includes/notify.php) — передайте сообщение вручную.</span></p></section>';
}

function pager($total, $per, $page, $base) {
    $n = (int)ceil($total / $per); if ($n <= 1) return '';
    $x = '<nav class="adm-pager" aria-label="Страницы">';
    for ($i = 1; $i <= $n; $i++) $x .= '<a href="' . h($base . (strpos($base, '?') === false ? '?' : '&') . 'p=' . $i) . '"' . ($i === $page ? ' class="aktiv"' : '') . '>' . $i . '</a>';
    return $x . '</nav>';
}
