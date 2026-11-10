<?php
// страница "О компании"
// TODO(DB): данные из repo_company() (таблицы company_info, team)
$active = 'company';
$page_title = 'О компании СЭРГЭ — строительство частных домов в Якутии';
$page_desc = 'СЭРГЭ проектирует и строит частные дома для северного климата: команда, принципы работы, цифры и контакты.';
$crumbs = array(array('Главная', 'index.php'), array('О компании', 'company.php'));
require_once __DIR__ . '/includes/header.php';

$c = repo_company();
$k = $c['contacts'];

// инициалы для аватара: "Мария Егорова" -> "МЕ"
function inicialy($name) {
    $r = '';
    foreach (preg_split('/\s+/u', trim($name)) as $w) $r .= mb_substr($w, 0, 1);
    return mb_substr($r, 0, 2);
}
?>

<main class="stranica">
    <div class="vnutri">
        <h1 class="zag-stranicy">Строим дома, рассчитанные на Север</h1>

        <div class="o-nas">
            <div class="o-nas__tekst">
                <?php foreach ($c['about'] as $abz): ?>
                <p><?= e($abz) ?></p>
                <?php endforeach; ?>
                <div class="knopki">
                    <a class="knopka knopka--cher" href="catalog.php">Наши проекты</a>
                    <a class="knopka knopka--ramka" href="stroitelstvo.php">Как мы строим</a>
                </div>
            </div>
            <div class="o-nas__foto">
                <img src="assets/img/serge-200-1.webp" alt="Построенный дом из бруса" width="1000" height="750" loading="lazy">
            </div>
        </div>

        <div class="fakty poyavlenie">
            <?php foreach ($c['figures'] as $fg): ?>
            <div><b><?= e($fg['value']) ?></b><span><?= e($fg['label']) ?></span></div>
            <?php endforeach; ?>
        </div>
    </div>

    <section class="blok blok--vnutr">
        <div class="vnutri">
            <h2 class="golova poyavlenie">Как мы работаем</h2>
            <div class="principy poyavlenie">
                <?php foreach ($c['principles'] as $i => $pr): ?>
                <div class="princip">
                    <h3><?= e($pr['title']) ?></h3>
                    <p><?= e($pr['text']) ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="blok blok--verh0">
        <div class="vnutri">
            <h2 class="golova poyavlenie">Команда</h2>
            <div class="komanda poyavlenie">
                <?php foreach ($c['team'] as $t): ?>
                <div class="chelovek">
                    <div class="chelovek__ava" aria-hidden="true"><?= e(inicialy($t['name'])) ?></div>
                    <b><?= e($t['name']) ?></b>
                    <span><?= e($t['role']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="blok blok--verh0">
        <div class="vnutri">
            <div class="kontakty poyavlenie">
                <div>
                    <h2 class="h2--malenkij">Контакты</h2>
                    <p class="seryj">Приезжайте в офис или позвоните — подберём проект и посчитаем стоимость.</p>
                </div>
                <dl class="kontakty__spisok">
                    <div><dt>Телефон</dt><dd><a href="tel:<?= e($k['phone_href']) ?>"><?= e($k['phone']) ?></a></dd></div>
                    <div><dt>Почта</dt><dd><a href="mailto:<?= e($k['email']) ?>"><?= e($k['email']) ?></a></dd></div>
                    <div><dt>Офис</dt><dd><?= e($k['address']) ?></dd></div>
                    <div><dt>Часы работы</dt><dd><?= e($k['hours']) ?></dd></div>
                    <div><dt>Реквизиты</dt><dd><?= e($k['requisites']) ?></dd></div>
                </dl>
            </div>
            <p class="primechanie"><?= e($c['demo_note']) ?></p>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
