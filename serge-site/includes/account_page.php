<?php
// Общая «шапка» страниц личного кабинета: проверяет, что кабинеты включены, и подключает auth.php.
// Перед подключением можно задать $page_title; страница будет закрыта от поисковиков.
$noindex = true;
if (!headers_sent()) { header('X-Frame-Options: DENY'); header('Cache-Control: no-store'); header('X-Robots-Tag: noindex'); }
require_once __DIR__ . '/functions.php';
if (!ACCOUNTS_ENABLED || !repo_db_ok()) {
    $page_title = 'Личный кабинет — СЭРГЭ';
    require __DIR__ . '/header.php';
    ?>
    <main class="stranica"><div class="vnutri nenaiden">
        <h1 class="zag-stranicy">Раздел недоступен</h1>
        <p class="seryj">Личные кабинеты и приём заявок работают на основном сайте, подключённом к базе данных. В демонстрационной версии они отключены.</p>
        <a class="knopka knopka--cher" href="catalog.php">Смотреть проекты</a>
    </div></main>
    <?php
    require __DIR__ . '/footer.php';
    exit;
}
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/notify.php';
acc_session(); // cookie сессии должен уйти до вывода страницы, иначе без output_buffering первая форма даёт «Форма устарела»
