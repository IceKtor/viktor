<?php
// страница "Строительство": этапы, фундаменты, материалы, сроки, вопросы
// TODO(DB): этапы - repo_stages() (таблица stages), цены - repo_rates()
$active = 'build';
$page_title = 'Строительство домов в Якутии: этапы, фундаменты и материалы — СЭРГЭ';
$page_desc = 'Как СЭРГЭ строит частные дома в Якутии: 8 этапов от консультации до ключей, фундаменты для мерзлоты, стены из бруса и газобетона, сроки и гарантия.';
$crumbs = array(array('Главная', 'index.php'), array('Строительство', 'stroitelstvo.php'));
require_once __DIR__ . '/includes/header.php';

$etapy = repo_stages();
$m = $rates['materials'];
$f = $rates['foundations'];

$voprosy = array(
    array('Можно ли строить зимой?', 'Да, часть работ зимой даже удобнее: сваи на мерзлоте погружают, пока грунт мёрзлый и по участку проходит техника. Внутренние работы ведут в доме с закрытым контуром и запущенным отоплением. А вот кладку газобетона и бетонные работы на морозе стараемся не делать.'),
    array('Нужны ли геологические изыскания?', 'Да. Без них нельзя правильно выбрать фундамент: условия меняются даже между соседними участками. Изыскания входят в этап «Подготовка участка».'),
    array('Можно ли изменить типовой проект?', 'Можно: передвинуть перегородки, добавить террасу или гараж, поменять материал стен. Изменения учитываются в проекте и в смете.'),
    array('Чем цена на сайте отличается от сметы?', 'Калькулятор считает ориентировочную стоимость по четырём параметрам: площади, этажности, материалу стен и фундаменту. Смета составляется по готовому проекту и учитывает участок, комплектацию и инженерные системы.'),
    array('Что входит в гарантию?', 'Гарантия 5 лет на конструктив: фундамент, стены, перекрытия и кровлю. На инженерные системы и отделку — гарантия производителей оборудования и 2 года на наши работы (демо-условия).'),
);
?>

<main class="stranica">
    <div class="vnutri">
        <div class="nadpis nadpis--derevo">СТРОИТЕЛЬСТВО</div>
        <h1 class="zag-stranicy">Как мы строим дом</h1>
        <p class="seryj seryj--shirokij">Восемь этапов — от первой встречи до передачи ключей. На каждом этапе понятно, что происходит, сколько это длится и что вы получаете в итоге.</p>

        <ol class="etapy">
            <?php foreach ($etapy as $i => $st): ?>
            <li class="etapy__punkt poyavlenie">
                <span class="etapy__nomer"><?= sprintf('%02d', $i + 1) ?></span>
                <div class="etapy__tekst">
                    <div class="etapy__verh">
                        <h2 class="h3"><?= e($st['title']) ?></h2>
                        <span class="metka"><?= e($st['time']) ?></span>
                    </div>
                    <p><?= e($st['text']) ?></p>
                </div>
            </li>
            <?php endforeach; ?>
        </ol>
    </div>

    <section class="blok blok--vnutr">
        <div class="vnutri">
            <div class="golova poyavlenie">
                <div>
                    <h2>Фундамент — под участок</h2>
                    <p class="seryj">Тип основания выбираем по результатам изысканий, а не по шаблону.</p>
                </div>
            </div>
            <div class="vybor poyavlenie">
                <article class="vybor__karta">
                    <div class="vybor__foto vybor__foto--shema"><img src="assets/img/fund-svai.webp" alt="Чертёж в разрезе: дом на ростверке, сваи проходят сквозь талый слой в вечную мерзлоту, под полом проветриваемое подполье" width="1280" height="720" loading="lazy"></div>
                    <div class="vybor__tekst">
                        <h3><?= e($f['svai']['name']) ?></h3>
                        <p>Сваи уходят ниже слоя сезонного оттаивания, дом стоит над землёй на ростверке. Проветриваемое подполье не даёт теплу дома подтапливать мерзлоту.</p>
                        <p class="vybor__dlya"><b>Подходит:</b> вечная мерзлота, пучинистые и влажные грунты, участки с уклоном.</p>
                        <div class="vybor__cena">от <b><?= rub($f['svai']['price']) ?></b> за м² площади застройки</div>
                    </div>
                </article>
                <article class="vybor__karta">
                    <div class="vybor__foto vybor__foto--shema"><img src="assets/img/fund-lenta.webp" alt="Чертёж в разрезе: дом на бетонной ленте с подошвой, заглублённой в песчаный грунт" width="1280" height="720" loading="lazy"></div>
                    <div class="vybor__tekst">
                        <h3><?= e($f['lenta']['name']) ?></h3>
                        <p>Монолитная железобетонная лента по периметру и под несущими стенами. Проще и дешевле в устройстве, но требовательна к грунту.</p>
                        <p class="vybor__dlya"><b>Подходит:</b> непучинистые песчаные грунты без мерзлоты под пятном дома.</p>
                        <div class="vybor__cena">от <b><?= rub($f['lenta']['price']) ?></b> за м² площади застройки</div>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <section class="blok blok--verh0">
        <div class="vnutri">
            <div class="golova poyavlenie">
                <div>
                    <h2>Стены — тёплые и надёжные</h2>
                    <p class="seryj">Оба материала проверены северными зимами. Выбор зависит от ваших приоритетов.</p>
                </div>
                <a class="knopka knopka--ramka" href="zhurnal.php">Сравнение в журнале →</a>
            </div>
            <div class="vybor poyavlenie">
                <article class="vybor__karta">
                    <div class="vybor__foto"><img src="assets/img/serge-160-1.webp" alt="Дом из бруса" width="1000" height="750" loading="lazy"></div>
                    <div class="vybor__tekst">
                        <h3><?= e($m['brus']['name']) ?></h3>
                        <p>Тёплый и «дышащий» дом, коробку можно поставить за один сезон. Дерево даёт усадку, поэтому часть отделки делается позже.</p>
                        <div class="vybor__cena">от <b><?= rub($m['brus']['price']) ?></b> за м² площади дома</div>
                    </div>
                </article>
                <article class="vybor__karta">
                    <div class="vybor__foto"><img src="assets/img/serge-140-1.webp" alt="Дом из газобетона с панорамными окнами" width="1000" height="750" loading="lazy"></div>
                    <div class="vybor__tekst">
                        <h3><?= e($m['gazobeton']['name']) ?></h3>
                        <p>Негорючий, не гниёт и почти не даёт усадки — отделку можно начинать сразу. Для Севера обязательно наружное утепление.</p>
                        <div class="vybor__cena">от <b><?= rub($m['gazobeton']['price']) ?></b> за м² площади дома</div>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <section class="blok blok--verh0">
        <div class="vnutri">
            <div class="fakty poyavlenie">
                <div><b>6–10</b><span>месяцев от проекта до ключей для дома 100–150 м²</span></div>
                <div><b>5 лет</b><span>гарантии на фундамент, стены и кровлю</span></div>
                <div><b>−50 °C</b><span>и ниже — зимы, под которые считаем утепление</span></div>
                <div><b>1 договор</b><span>на весь цикл работ — без лишних подрядчиков</span></div>
            </div>
        </div>
    </section>

    <section class="blok blok--verh0">
        <div class="vnutri">
            <h2 class="poyavlenie">Частые вопросы</h2>
            <div class="voprosy poyavlenie">
                <?php foreach ($voprosy as $v): ?>
                <details class="vopros">
                    <summary><?= e($v[0]) ?></summary>
                    <p><?= e($v[1]) ?></p>
                </details>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <div class="vnutri">
        <div class="kartochka-prizyv poyavlenie">
            <div>
                <h2 class="h2--malenkij">Посчитайте свой дом</h2>
                <p>Площадь, этажность, стены и фундамент — расчёт займёт минуту.</p>
            </div>
            <div class="knopki">
                <a class="knopka knopka--derevo" href="calculator.php">Рассчитать стоимость →</a>
                <a class="knopka knopka--kontur" href="catalog.php">Смотреть проекты</a>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
