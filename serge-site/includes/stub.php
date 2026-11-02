<?php
// общая заглушка для разделов из меню, которых в ТЗ нет (Строительство, О компании, Журнал).
// Перед подключением задать: $stub_label, $stub_title, $stub_text, $page_title, $page_desc, $active
// TODO(DB): когда появится база - заменить заглушку на данные из includes/repository.php
$noindex = true;
$crumbs = array(array('Главная', 'index.php'), array($stub_title, basename($_SERVER['SCRIPT_NAME'])));
require_once __DIR__ . '/header.php';
?>

<main class="stranica">
    <div class="vnutri">
        <div class="nadpis nadpis--derevo"><?= e($stub_label) ?></div>
        <h1 class="zag-stranicy"><?= e($stub_title) ?></h1>
        <div class="zaglushka">
            <h2 class="h3">Раздел в разработке</h2>
            <p><?= e($stub_text) ?></p>
            <div class="knopki">
                <a class="knopka knopka--cher" href="catalog.php">Смотреть проекты →</a>
                <a class="knopka knopka--ramka" href="calculator.php">Рассчитать стоимость</a>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/footer.php'; ?>
