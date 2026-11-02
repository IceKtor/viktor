<?php
$page_title = 'Проекты домов — каталог СЭРГЭ';
$page_desc = 'Каталог проектов частных домов для Якутии: площадь, этажность, материал стен и тип фундамента. Выберите проект и рассчитайте стоимость.';
$active = 'catalog';
$crumbs = array(array('Главная', 'index.php'), array('Проекты домов', 'catalog.php'));
require_once 'includes/header.php';
$scripts = array('catalog.js');
?>

<main class="stranica">
    <div class="vnutri">
        <div class="nadpis nadpis--derevo">КАТАЛОГ</div>
        <h1 class="zag-stranicy">Проекты домов</h1>
        <p class="seryj seryj--shirokij">Выберите готовую концепцию или адаптируйте проект под свой участок.</p>

        <?php
        // варианты фильтров берём из самих проектов, чтоб не расходились с данными
        $f_floors = array(); $f_beds = array(); $max_price = 0;
        foreach ($houses as $h) {
            $f_floors[$h['floors']] = true;
            $f_beds[$h['bedrooms']] = true;
            if ($h['price'] > $max_price) $max_price = $h['price'];
        }
        ksort($f_floors); ksort($f_beds);
        $price_step = 2000000;
        $price_to = (int)(ceil($max_price / $price_step) * $price_step);
        ?>
        <div class="filtry" id="filtry">
            <label>Сортировка
                <select id="f-sort">
                    <option value="">По умолчанию</option>
                    <option value="area-up">Площадь: по возрастанию</option>
                    <option value="area-down">Площадь: по убыванию</option>
                    <option value="price-up">Цена: по возрастанию</option>
                    <option value="price-down">Цена: по убыванию</option>
                </select>
            </label>
            <label>Этажность
                <select id="f-floors">
                    <option value="">Любая</option>
                    <?php foreach ($f_floors as $n => $_): ?>
                    <option value="<?= (int)$n ?>"><?= e(floors_text($n)) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Спальни
                <select id="f-bedrooms">
                    <option value="">Любое число</option>
                    <?php foreach ($f_beds as $n => $_): ?>
                    <option value="<?= (int)$n ?>"><?= e(bedrooms_text($n)) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Материал стен
                <select id="f-material">
                    <option value="">Любой</option>
                    <?php foreach ($rates['materials'] as $k => $m): ?>
                    <option value="<?= e($k) ?>"><?= e($m['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Фундамент
                <select id="f-foundation">
                    <option value="">Любой</option>
                    <?php foreach ($rates['foundations'] as $k => $m): ?>
                    <option value="<?= e($k) ?>"><?= e($m['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Цена, до
                <select id="f-price">
                    <option value="">Любая</option>
                    <?php for ($p = $price_step; $p <= $price_to; $p += $price_step): ?>
                    <option value="<?= $p ?>"><?= rub($p) ?></option>
                    <?php endfor; ?>
                </select>
            </label>
            <div class="filtry__galki">
                <label class="galka"><input type="checkbox" id="f-garage"> Гараж</label>
                <label class="galka"><input type="checkbox" id="f-terrace"> Терраса</label>
            </div>
            <button class="knopka knopka--ramka filtry__sbros" id="f-sbros" type="button">Сбросить</button>
        </div>

        <div class="naideno">Найдено проектов: <b id="naideno"><?= count($houses) ?></b></div>

        <div class="doma doma--katalog" id="spisok">
            <?php foreach ($houses as $h) echo karto4ka($h, $rates); ?>
        </div>

        <div class="pusto" id="pusto" hidden>
            <h3>По таким параметрам ничего не нашлось</h3>
            <p>Попробуйте убрать часть фильтров.</p>
            <button class="knopka knopka--cher" id="pusto-sbros" type="button">Сбросить фильтры</button>
        </div>

        <div class="primechanie">
            Цена «от» посчитана по той же модели, что и в калькуляторе, на расценках от <?= e($rates['date']) ?>. Это ориентир, а не смета.
        </div>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>
