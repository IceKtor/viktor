<?php
$page_title = 'Калькулятор стоимости дома — СЭРГЭ';
$page_desc = 'Рассчитайте предварительную стоимость дома по площади, этажности, материалу стен и типу фундамента без перезагрузки страницы.';
$active = 'calc';
$crumbs = array(array('Главная', 'index.php'), array('Калькулятор стоимости', 'calculator.php'));
require_once 'includes/header.php';
$scripts = array('calc.js');

// если пришли из карточки дома, то в адресе лежат параметры, подставляем их в форму
$z_area = isset($_GET['area']) ? $_GET['area'] : '';
$z_floors = isset($_GET['floors']) ? $_GET['floors'] : '';
$z_mat = isset($_GET['material']) ? $_GET['material'] : '';
$z_fnd = isset($_GET['foundation']) ? $_GET['foundation'] : '';
$iz_kartochki = ($z_area !== '' || $z_floors !== '' || $z_mat !== '' || $z_fnd !== '');
?>

<main class="stranica">
    <div class="vnutri">
        <div class="nadpis nadpis--derevo">КАЛЬКУЛЯТОР</div>
        <h1 class="zag-stranicy">Калькулятор стоимости</h1>
        <p class="seryj seryj--shirokij">Укажите параметры дома — расчёт выполняется сразу, без перезагрузки страницы.</p>

        <?php if ($iz_kartochki): ?>
        <div class="podskazka">Параметры подставлены из выбранного проекта. Их можно поменять.</div>
        <?php elseif (defined('STATIC_BUILD')): ?>
        <div class="podskazka" id="podskazka-karto4ka" hidden>Параметры подставлены из выбранного проекта. Их можно поменять.</div>
        <?php endif; ?>

        <div class="calc">
            <form class="forma" id="calc-form" action="api/calc.php" method="post" novalidate
                  data-area-min="<?= (int)$rates['area_min'] ?>" data-area-max="<?= (int)$rates['area_max'] ?>"
                  data-step="<?= (int)$rates['round_step'] ?>"<?php if (ACCOUNTS_ENABLED): ?> data-zayavka="zayavka.php"<?php endif; ?><?php if (defined('STATIC_BUILD')): ?> data-rates="<?= e(json_encode($rates, JSON_UNESCAPED_UNICODE)) ?>"<?php endif; ?>>

                <div class="pole" id="pole-area">
                    <label for="area">Общая площадь, м²</label>
                    <input type="text" id="area" name="area" inputmode="decimal" autocomplete="off"
                           placeholder="Введите значение от <?= (int)$rates['area_min'] ?> до <?= (int)$rates['area_max'] ?> м²" value="<?= e($z_area) ?>">
                    <p class="oshibka" id="err-area" role="alert" hidden></p>
                </div>

                <div class="pole" id="pole-floors">
                    <label for="floors">Этажность</label>
                    <select id="floors" name="floors">
                        <option value="">Выберите этажность</option>
                        <?php for ($i = 1; $i <= $rates['floors_max']; $i++): ?>
                        <option value="<?= $i ?>"<?= ((string)$z_floors === (string)$i) ? ' selected' : '' ?>><?= floors_text($i) ?></option>
                        <?php endfor; ?>
                    </select>
                    <p class="oshibka" id="err-floors" role="alert" hidden></p>
                </div>

                <div class="pole" id="pole-material">
                    <label for="material">Материал стен</label>
                    <select id="material" name="material">
                        <option value="">Выберите материал</option>
                        <?php foreach ($rates['materials'] as $k => $m): ?>
                        <option value="<?= e($k) ?>"<?= ($z_mat === $k) ? ' selected' : '' ?>><?= e($m['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="oshibka" id="err-material" role="alert" hidden></p>
                </div>

                <div class="pole" id="pole-foundation">
                    <label for="foundation">Тип фундамента</label>
                    <select id="foundation" name="foundation">
                        <option value="">Выберите фундамент</option>
                        <?php foreach ($rates['foundations'] as $k => $m): ?>
                        <option value="<?= e($k) ?>"<?= ($z_fnd === $k) ? ' selected' : '' ?>><?= e($m['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="oshibka" id="err-foundation" role="alert" hidden></p>
                </div>

                <p class="oshibka oshibka--obshaya" id="err-obshaya" role="alert" hidden></p>

                <div class="knopki knopki--forma">
                    <button class="knopka knopka--derevo" type="submit" id="btn-raschet">Рассчитать</button>
                    <button class="knopka knopka--ramka" type="button" id="btn-sbros">Сбросить</button>
                </div>
            </form>

            <div class="rezultat" id="rezultat" tabindex="-1" hidden>
                <!-- сюда calc.js кладёт результат -->
            </div>

            <div class="rezultat rezultat--pusto" id="rezultat-pusto">
                <h2 class="h3">Здесь появится результат</h2>
                <p>Заполните все четыре поля и нажмите «Рассчитать». Мы покажем предварительную стоимость, диапазон и из чего она состоит.</p>
            </div>
        </div>

        <div class="primechanie">
            Расценки актуальны на <?= e($rates['date']) ?>. Расчёт предварительный, ориентировочный и не является сметой.
        </div>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>
