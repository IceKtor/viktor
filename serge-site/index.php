<?php
$page_title = 'СЭРГЭ — строительство домов в Якутии. Дом, рассчитанный на Север';
$page_desc = 'Проектируем и строим частные дома в Якутии. Подберите проект и рассчитайте предварительную стоимость дома онлайн.';
$active = 'index';
$dark = true;
$preload_hero = true;   // фон первого экрана загружается с повышенным приоритетом (быстрее LCP)
require_once 'includes/header.php';

// TODO(DB): этапы берутся из data/stages.json через repo_stages()
$put = array();
foreach (repo_stages() as $st) $put[] = array($st['title'], $st['short']);
?>

<main>
<section class="pervyj">
    <div class="vnutri pervyj__vnutri poyavlenie">
        <div class="nadpis">СТРОИТЕЛЬСТВО ДОМОВ В ЯКУТИИ</div>
        <h1>Дом, рассчитанный на Север.</h1>
        <p class="podzag">Проектируем и строим частные дома в Якутии — от первого эскиза до ключей. Выберите проект и узнайте предварительную стоимость за пару минут.</p>
        <div class="knopki">
            <a class="knopka knopka--derevo" href="calculator.php">Спроектировать дом →</a>
            <a class="knopka knopka--kontur" href="catalog.php">Смотреть проекты</a>
        </div>
        <div class="cifry">
            <div><b>120+</b><span>домов построено</span></div>
            <div><b>12 лет</b><span>в строительстве</span></div>
            <div><b>5 лет</b><span>гарантии</span></div>
        </div>
    </div>
</section>

<section class="blok">
    <div class="vnutri">
        <div class="golova poyavlenie">
            <div>
                <h2>Один подрядчик.<br>Весь путь.</h2>
                <p class="seryj">От идеи и проекта до инженерии, отделки и передачи ключей.</p>
            </div>
            <a class="knopka knopka--ramka" href="stroitelstvo.php">Как мы строим →</a>
        </div>

        <div class="shagi poyavlenie">
            <?php foreach ($put as $i => $p): ?>
            <div class="shag<?= $i == 0 ? ' aktiv' : '' ?>" tabindex="0">
                <i><?= sprintf('%02d', $i + 1) ?></i>
                <b><?= e($p[0]) ?></b>
                <p><?= e($p[1]) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="blok blok--verh0">
    <div class="vnutri">
        <div class="golova poyavlenie">
            <h2>Дом начинается<br>с проекта.</h2>
            <a class="knopka knopka--ramka" href="catalog.php">Смотреть все проекты →</a>
        </div>
        <div class="doma poyavlenie">
            <?php foreach (array_slice($houses, 0, 3) as $h) echo karto4ka($h, $rates); ?>
        </div>
    </div>
</section>

<section class="prizyv">
    <div class="vnutri prizyv__vnutri">
        <h3>Хотите узнать, сколько стоит ваш дом?</h3>
        <a class="knopka knopka--cher" href="calculator.php">Рассчитать стоимость →</a>
    </div>
</section>

<section class="blok">
    <div class="vnutri">
        <div class="golova poyavlenie">
            <h2>Дом должен учитывать<br>место, в котором стоит.</h2>
        </div>
        <div class="standart poyavlenie">
            <div class="st">
                <div class="st__foto st1"></div>
                <h3>Климат</h3>
                <p>Утепление, теплопотери и температурные перепады учитываются ещё на этапе проекта.</p>
            </div>
            <div class="st">
                <div class="st__foto st2"></div>
                <h3>Основание</h3>
                <p>Тип фундамента подбирается под грунт и рельеф конкретного участка.</p>
            </div>
            <div class="st">
                <div class="st__foto st3"></div>
                <h3>Тепло</h3>
                <p>Система отопления рассчитывается под площадь дома и климатическую зону.</p>
            </div>
            <div class="st">
                <div class="st__foto st4"></div>
                <h3>Инженерия</h3>
                <p>Водоснабжение, электрика и вентиляция проектируются как единая система.</p>
            </div>
        </div>
    </div>
</section>

<section class="blok blok--verh0">
    <div class="vnutri">
        <div class="golova poyavlenie">
            <h2>Вы всегда знаете,<br>что происходит с домом.</h2>
        </div>
        <div class="panel poyavlenie">
            <div class="panel__verh">
                <div>
                    <div class="melko">Проект №СЭРГЭ-024</div>
                    <div class="procent">67%</div>
                </div>
                <div class="panel__pravo">
                    <div class="melko">Текущий этап</div>
                    <div class="etap">Инженерные работы</div>
                </div>
            </div>
            <div class="polosa"><div class="polosa__vnutri"></div></div>
            <div class="chipy">
                <span class="chip chip--ok">✓ Проект</span>
                <span class="chip chip--ok">✓ Фундамент</span>
                <span class="chip chip--ok">✓ Коробка</span>
                <span class="chip chip--ok">✓ Кровля</span>
                <span class="chip chip--seichas">Инженерия</span>
                <span class="chip">Отделка</span>
                <span class="chip">Ключи</span>
            </div>
            <div class="obnovlenie">
                <div class="obnovlenie__foto"></div>
                <div>
                    <div class="melko">Обновлено 08 сентября 2026 · демо-данные</div>
                    <div>Установлена система вентиляции первого этажа.</div>
                </div>
            </div>
        </div>
    </div>
</section>

</main>

<?php require_once 'includes/footer.php'; ?>
