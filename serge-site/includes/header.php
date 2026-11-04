<?php
// шапка, подключается на каждой странице. перед ней надо задать $page_title, $active и $dark
require_once __DIR__ . '/functions.php';
$hu = null;   // вошедший пользователь (если включены кабинеты)
if (ACCOUNTS_ENABLED && repo_db_ok()) { require_once __DIR__ . '/auth.php'; $hu = current_user(); }

if (!isset($page_title)) $page_title = 'СЭРГЭ — Дом, рассчитанный на Север';
if (!isset($page_desc)) $page_desc = 'СЭРГЭ — проекты частных домов в Якутии и онлайн-калькулятор предварительной стоимости строительства.';
if (!isset($active)) $active = '';
if (!isset($dark)) $dark = false;
if (!isset($crumbs)) $crumbs = null;      // хлебные крошки для Schema.org (не на главной)
if (!isset($noindex)) $noindex = false;
if (!isset($base_href)) $base_href = false; // true на 404.php: он открывается по любому адресу, пути надо считать от корня
// canonical: адрес страницы без лишних параметров (кроме id дома)
$canon_file = basename($_SERVER['SCRIPT_NAME']);
$canonical = site_url() . '/' . (($canon_file === 'index.php') ? '' : $canon_file);
if (($canon_file === 'house.php' || $canon_file === 'statya.php') && isset($_GET['id'])) $canonical .= '?id=' . rawurlencode($_GET['id']);

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
    <meta property="og:type" content="<?= isset($og_type) ? e($og_type) : 'website' ?>">
    <meta property="og:site_name" content="СЭРГЭ">
    <meta property="og:locale" content="ru_RU">
    <meta property="og:url" content="<?= e($canonical) ?>">
    <meta property="og:image" content="<?= e(site_url() . '/' . ltrim(isset($og_image) ? $og_image : 'assets/img/hero.webp', '/')) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta property="og:title" content="<?= e($page_title) ?>">
    <meta property="og:description" content="<?= e($page_desc) ?>">
    <?php if ($noindex): ?><meta name="robots" content="noindex"><?php else: ?><link rel="canonical" href="<?= e($canonical) ?>"><?php endif; ?>
    <link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml">
    <?php if (!empty($preload_hero)): ?>
    <link rel="preload" as="image" href="assets/img/hero.webp" media="(min-width: 1025px)" fetchpriority="high">
    <link rel="preload" as="image" href="assets/img/hero-t.webp" media="(min-width: 641px) and (max-width: 1024px)" fetchpriority="high">
    <link rel="preload" as="image" href="assets/img/hero-m.webp" media="(max-width: 640px)" fetchpriority="high">
    <?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <?php /* шрифт подгружается без блокировки отрисовки; пока он не пришёл, показывается Arial (display=swap) */ ?>
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap"></noscript>
    <link rel="stylesheet" href="<?= e(asset('assets/css/style.css')) ?>">
    <script>document.documentElement.className += ' js';setTimeout(function(){if(!window.sergeOk){document.documentElement.className=document.documentElement.className.replace(' js','');}},2500);</script>
    <?php if ($crumbs) echo breadcrumbs_ld($crumbs), "\n"; if (!empty($ld_extra)) echo $ld_extra, "\n"; ?>
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
            <?php if (ACCOUNTS_ENABLED): ?>
            <a class="menyu__vhod" href="<?= $hu ? ($hu['role'] === 'admin' ? 'admin/' : 'cabinet.php') : 'login.php' ?>"><?= $hu ? ($hu['role'] === 'admin' ? 'Админ-панель' : 'Личный кабинет') : 'Войти' ?></a>
            <?php endif; ?>
            <a class="knopka knopka--derevo menyu__knopka" href="calculator.php">Рассчитать стоимость →</a>
        </nav>

        <div class="shapka__pravo">
            <a class="tel" href="tel:<?= e(site_contacts()["phone_href"]) ?>"><?= e(site_contacts()["phone"]) ?></a>
            <a class="knopka knopka--cher shapka__knopka" href="calculator.php">Рассчитать стоимость →</a>
            <?php if (ACCOUNTS_ENABLED): ?>
            <a class="vhod<?= $hu ? ' vhod--est' : '' ?>" href="<?= $hu ? ($hu['role'] === 'admin' ? 'admin/' : 'cabinet.php') : 'login.php' ?>" title="<?= $hu ? ($hu['role'] === 'admin' ? 'Админ-панель' : 'Личный кабинет') : 'Войти' ?>" aria-label="<?= $hu ? ($hu['role'] === 'admin' ? 'Админ-панель' : 'Личный кабинет') : 'Войти в личный кабинет' ?>"><svg viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8.2" r="3.6"/><path d="M4.8 20c.9-3.7 3.7-5.6 7.2-5.6s6.3 1.9 7.2 5.6"/></svg></a>
            <?php endif; ?>
            <button class="gamburger" id="gamburger" type="button" aria-label="Открыть меню" aria-expanded="false">
                <i></i><i></i><i></i>
            </button>
        </div>
    </div>
</header>
