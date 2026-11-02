<?php
// шапка, подключается на каждой странице. перед ней надо задать $page_title, $active и $dark
require_once __DIR__ . '/functions.php';

if (!isset($page_title)) $page_title = 'СЭРГЭ — Дом, рассчитанный на Север';
if (!isset($page_desc)) $page_desc = 'СЭРГЭ — проекты частных домов в Якутии и онлайн-калькулятор предварительной стоимости строительства.';
if (!isset($active)) $active = '';
if (!isset($dark)) $dark = false;
if (!isset($crumbs)) $crumbs = null;      // хлебные крошки для Schema.org (не на главной)
if (!isset($noindex)) $noindex = false;
if (!isset($base_href)) $base_href = false; // true на 404.php: он открывается по любому адресу, пути надо считать от корня   // true для 404 и заглушек
// canonical: адрес страницы без лишних параметров (кроме id дома)
$canon_file = basename($_SERVER['SCRIPT_NAME']);
$canonical = site_url() . '/' . (($canon_file === 'index.php') ? '' : $canon_file);
if ($canon_file === 'house.php' && isset($_GET['id'])) $canonical .= '?id=' . rawurlencode($_GET['id']);

function pun($name, $active, $href, $text) {
    $cls = ($name === $active) ? ' class="aktiv" aria-current="page"' : '';
    return '<a href="' . $href . '"' . $cls . '>' . $text . '</a>';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php if ($base_href): ?><base href="<?= e(site_url()) ?>/"><?php endif; ?>
    <title><?= e($page_title) ?></title>
    <meta name="description" content="<?= e($page_desc) ?>">
    <meta property="og:title" content="<?= e($page_title) ?>">
    <meta property="og:description" content="<?= e($page_desc) ?>">
    <?php if ($noindex): ?><meta name="robots" content="noindex"><?php else: ?><link rel="canonical" href="<?= e($canonical) ?>"><?php endif; ?>
    <link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script>document.documentElement.className += ' js';</script>
    <?php if ($crumbs) echo breadcrumbs_ld($crumbs), "\n"; ?>
</head>
<body>

<header class="shapka<?= $dark ? ' shapka--temnaya' : '' ?>" id="shapka">
    <div class="vnutri shapka__vnutri">
        <a class="logo" href="index.php">
            <b>СЭРГЭ</b>
            <span>строительство домов</span>
        </a>

        <nav class="menyu" id="menyu" aria-label="Главное меню">
            <?= pun('catalog', $active, 'catalog.php', 'Дома') ?>
            <?= pun('build', $active, 'stroitelstvo.php', 'Строительство') ?>
            <?= pun('projects', $active, 'catalog.php', 'Проекты') ?>
            <?= pun('company', $active, 'company.php', 'О компании') ?>
            <?= pun('journal', $active, 'zhurnal.php', 'Журнал') ?>
            <a class="knopka knopka--derevo menyu__knopka" href="calculator.php">Рассчитать стоимость →</a>
        </nav>

        <div class="shapka__pravo">
            <a class="tel" href="tel:+79999999999">+7 (999) 999-99-99</a>
            <a class="knopka knopka--cher shapka__knopka" href="calculator.php">Рассчитать стоимость →</a>
            <button class="gamburger" id="gamburger" type="button" aria-label="Открыть меню" aria-expanded="false">
                <i></i><i></i><i></i>
            </button>
        </div>
    </div>
</header>
