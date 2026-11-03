<?php
// расценки и коэффициенты калькулятора: всё, что влияет на сумму на сайте
require_once __DIR__ . '/../includes/admin_ui.php';
$err = array();

function rate_group($tbl) { return db_all("SELECT * FROM $tbl ORDER BY sort_order, code"); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    adm_post_guard();
    $do = post('do');
    if ($do === 'settings') {
        $s = array('area_min' => post('area_min'), 'area_max' => post('area_max'), 'floors_max' => post('floors_max'), 'delta' => post('delta'), 'round_step' => post('round_step'),
            'k_reg' => str_replace(',', '.', post('k_reg')), 'k_floors_many' => str_replace(',', '.', post('k_floors_many')), 'date' => post('date'));
        foreach (array('area_min', 'area_max', 'floors_max', 'delta', 'round_step') as $k) if (!ctype_digit($s[$k]) || (int)$s[$k] < 1) $err[$k] = 'Целое число больше нуля';
        foreach (array('k_reg', 'k_floors_many') as $k) if (!is_numeric($s[$k]) || (float)$s[$k] < 0.5 || (float)$s[$k] > 5) $err[$k] = 'Число от 0,5 до 5';
        if (!isset($err['area_min']) && !isset($err['area_max']) && (int)$s['area_min'] >= (int)$s['area_max']) $err['area_max'] = 'Максимум должен быть больше минимума';
        if (!isset($err['floors_max']) && (int)$s['floors_max'] > 5) $err['floors_max'] = 'Не больше 5';
        if (!isset($err['delta']) && (int)$s['delta'] > 50) $err['delta'] = 'Не больше 50 %';
        if (!preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $s['date'])) $err['date'] = 'Дата в формате ДД.ММ.ГГГГ';
        if (!$err) {
            foreach ($s as $k => $val) db_exec('INSERT INTO settings (name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)', array($k, (string)$val));
            audit('rates_settings', 'settings', null, json_encode($s, JSON_UNESCAPED_UNICODE));
            adm_back('rates.php', 'ok', 'Параметры расчёта сохранены. Калькулятор и цены в каталоге пересчитаны.');
        }
    } elseif ($do === 'materials' || $do === 'foundations') {
        $tbl = $do; $isMat = $do === 'materials';
        $rows = db_all("SELECT code FROM $tbl");
        $bad = array();
        foreach ($rows as $r) {
            $c = $r['code']; $name = post("name_$c"); $price = post("price_$c"); $sort = post("sort_$c"); $act = isset($_POST["act_$c"]) ? 1 : 0;
            if ($name === '' || !ctype_digit($price) || !ctype_digit($sort)) { $bad[] = $c; continue; }
            if (!$act && db_val('SELECT COUNT(*) FROM houses WHERE ' . ($isMat ? 'material_code' : 'foundation_code') . ' = ? AND is_published = 1', array($c))) { $bad[] = $c . ' (используется в проектах — сначала смените его у проектов)'; continue; }
            db_exec("UPDATE $tbl SET name = ?, price = ?, sort_order = ?, is_active = ? WHERE code = ?", array($name, (int)$price, (int)$sort, $act, $c));
        }
        audit('rates_' . $tbl, $tbl);
        if ($bad) adm_back('rates.php', 'err', 'Не сохранено: ' . implode(', ', $bad) . '. Остальное сохранено.');
        adm_back('rates.php', 'ok', 'Цены сохранены');
    } elseif ($do === 'add_material' || $do === 'add_foundation') {
        $tbl = $do === 'add_material' ? 'materials' : 'foundations';
        $code = strtolower(post('code')); $name = post('name'); $price = post('price');
        if (!preg_match('/^[a-z0-9][a-z0-9_-]{1,28}$/', $code) || $name === '' || !ctype_digit($price)) adm_back('rates.php', 'err', 'Заполните код (латиница), название и цену');
        if (db_val("SELECT code FROM $tbl WHERE code = ?", array($code))) adm_back('rates.php', 'err', 'Такой код уже есть');
        db_exec("INSERT INTO $tbl (code, name, price, sort_order) VALUES (?, ?, ?, (SELECT COALESCE(MAX(s), 0) + 1 FROM (SELECT sort_order AS s FROM $tbl) t))", array($code, $name, (int)$price));
        audit('rates_add', $tbl, $code); adm_back('rates.php', 'ok', 'Добавлено');
    }
}
$st = array(); foreach (db_all('SELECT name, value FROM settings') as $r) $st[$r['name']] = $r['value'];
if ($err) { foreach ($_POST as $k => $val) if (isset($st[$k])) $st[$k] = $val; }
adm_head('Расценки калькулятора', 'rates');
?>
<p class="melko">Эти значения используются в калькуляторе и для цены «от» в каталоге. После сохранения сайт сразу показывает новые суммы.</p>
<form class="adm-card adm-form" method="post">
    <?= csrf_field() ?><input type="hidden" name="do" value="settings">
    <h2>Параметры расчёта</h2>
    <div class="adm-grid">
        <?= fld('date', 'Расценки актуальны на (ДД.ММ.ГГГГ)', $st['date'], array('err' => $err, 'req' => 1)) ?>
        <?= fld('area_min', 'Минимальная площадь, м²', $st['area_min'], array('type' => 'number', 'err' => $err)) ?>
        <?= fld('area_max', 'Максимальная площадь, м²', $st['area_max'], array('type' => 'number', 'err' => $err)) ?>
        <?= fld('floors_max', 'Максимум этажей', $st['floors_max'], array('type' => 'number', 'err' => $err)) ?>
        <?= fld('k_floors_many', 'Коэффициент для 2 и более этажей', $st['k_floors_many'], array('err' => $err, 'hint' => 'Для одного этажа всегда 1,0')) ?>
        <?= fld('k_reg', 'Региональный коэффициент', $st['k_reg'], array('err' => $err, 'hint' => 'Умножает итог; 1 — без поправки')) ?>
        <?= fld('delta', 'Диапазон итога, ± %', $st['delta'], array('type' => 'number', 'err' => $err)) ?>
        <?= fld('round_step', 'Округление итога до, ₽', $st['round_step'], array('type' => 'number', 'err' => $err)) ?>
    </div>
    <div class="adm-actions"><button class="knopka knopka--cher" type="submit">Сохранить параметры</button></div>
</form>

<?php foreach (array('materials' => array('Материалы стен', 'Цена за 1 м² площади дома, ₽', 'add_material'), 'foundations' => array('Фундаменты', 'Цена за 1 м² площади застройки, ₽', 'add_foundation')) as $tbl => $t): $rows = rate_group($tbl); ?>
<form class="adm-card adm-form" method="post">
    <?= csrf_field() ?><input type="hidden" name="do" value="<?= $tbl ?>">
    <h2><?= h($t[0]) ?></h2>
    <div class="adm-scroll"><table class="atab"><tr><th>Код</th><th>Название</th><th><?= h($t[1]) ?></th><th>Порядок</th><th>Активен</th></tr>
    <?php foreach ($rows as $r): $c = $r['code']; ?>
    <tr><td><?= h($c) ?></td><td><input type="text" name="name_<?= h($c) ?>" value="<?= h($r['name']) ?>" aria-label="Название <?= h($c) ?>"></td>
    <td><input type="number" min="0" name="price_<?= h($c) ?>" value="<?= (int)$r['price'] ?>" aria-label="Цена <?= h($c) ?>"></td><td><input type="number" min="0" name="sort_<?= h($c) ?>" value="<?= (int)$r['sort_order'] ?>" aria-label="Порядок <?= h($c) ?>"></td>
    <td><input type="checkbox" name="act_<?= h($c) ?>" value="1"<?= $r['is_active'] ? ' checked' : '' ?> aria-label="Активен <?= h($c) ?>"></td></tr>
    <?php endforeach; ?></table></div>
    <div class="adm-actions"><button class="knopka knopka--cher" type="submit">Сохранить цены</button></div>
</form>
<form class="adm-card adm-form" method="post">
    <?= csrf_field() ?><input type="hidden" name="do" value="<?= $t[2] ?>">
    <h2>Добавить: <?= h(mb_strtolower($t[0])) ?></h2>
    <div class="adm-grid">
        <?= fld('code', 'Код (латиницей)', '', array('placeholder' => 'karkas')) ?><?= fld('name', 'Название', '') ?><?= fld('price', $t[1], '', array('type' => 'number', 'min' => 0)) ?>
    </div>
    <div class="adm-actions"><button class="knopka knopka--ramka" type="submit">Добавить</button></div>
</form>
<?php endforeach; adm_foot();
