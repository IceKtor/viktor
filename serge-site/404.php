<?php
// страница 404, подключается через .htaccess (ErrorDocument). пути в ней считаются от корня сайта
http_response_code(404);
$page_title = 'Страница не найдена — СЭРГЭ';
$page_desc = 'Такой страницы нет. Посмотрите проекты домов или рассчитайте стоимость.';
$noindex = true;
$base_href = true;
require_once __DIR__ . '/includes/header.php';
?>

<main class="stranica">
    <div class="vnutri nenaiden">
        <div class="nadpis nadpis--derevo">ОШИБКА 404</div>
        <h1 class="zag-stranicy">Страница не найдена</h1>
        <p class="seryj">Возможно, ссылка устарела или адрес набран с ошибкой.</p>
        <div class="knopki">
            <a class="knopka knopka--cher" href="catalog.php">Смотреть проекты →</a>
            <a class="knopka knopka--ramka" href="index.php">На главную</a>
        </div>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>
