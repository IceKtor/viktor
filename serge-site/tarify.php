<?php
// тарифы: как сайт зарабатывает и что получает клиент бесплатно и в VIP-версии
require_once __DIR__ . '/includes/functions.php';
$active = '';
$page_title = 'Тарифы: бесплатная и VIP-версия — СЭРГЭ';
$page_desc = 'Что входит в бесплатную версию сайта СЭРГЭ и что получает клиент с VIP-тарифом.';
$crumbs = array(array('Главная', 'index.php'), array('Тарифы', 'tarify.php'));
$free = array('Каталог проектов и карточки домов', 'Калькулятор предварительной стоимости', 'Информационные разделы и журнал', 'Заявка и личный кабинет с ходом строительства');
$vip = array('Фотоотчёты по объекту с каждого этапа', 'Подробная смета в PDF с разбивкой по статьям', 'Сохранение и сравнение нескольких расчётов', 'Заявка в приоритете: ответ менеджера в течение рабочего дня', 'Личный менеджер на весь срок строительства');
require __DIR__ . '/includes/header.php';
?>
<main class="stranica">
    <div class="vnutri">
        <div class="nadpis nadpis--derevo">ТАРИФЫ</div>
        <h1 class="zag-stranicy">Бесплатная и VIP-версия</h1>
        <p class="seryj">Посмотреть проекты и оценить стоимость можно бесплатно. VIP добавляет сервис на время строительства.</p>
        <div class="tarify poyavlenie">
            <div class="princip">
                <i>БЕСПЛАТНО</i>
                <h3>Базовая версия</h3>
                <p class="tarif__cena">0 ₽</p>
                <ul class="tarif__spisok"><?php foreach ($free as $f): ?><li><?= e($f) ?></li><?php endforeach; ?></ul>
            </div>
            <div class="princip princip--vip">
                <i>VIP</i>
                <h3>VIP-версия</h3>
                <p class="tarif__cena">от 15 000 ₽</p>
                <ul class="tarif__spisok"><?php foreach ($vip as $f): ?><li><?= e($f) ?></li><?php endforeach; ?></ul>
            </div>
        </div>
        <h2 class="h2--malenkij cab-zag">Как зарабатывает платформа</h2>
        <p class="seryj">Основной доход даёт комиссия с договоров строительства, заключённых по заявкам с сайта. Второй источник – VIP-тариф для клиентов. Цены условные и подлежат уточнению.</p>
        <p class="melko tarif__note">Онлайн-оплата не подключена: VIP-тариф оформляет менеджер при заключении договора и отмечает в личном кабинете клиента.</p>
        <div class="knopki"><a class="knopka knopka--cher" href="zayavka.php">Оставить заявку →</a><a class="knopka knopka--ramka" href="calculator.php">Рассчитать стоимость</a></div>
    </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
