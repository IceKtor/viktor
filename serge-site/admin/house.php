<?php
require_once __DIR__ . '/../includes/admin_ui.php';
$id = isset($_GET['id']) ? (string)$_GET['id'] : '';
$x = $id !== '' ? db_row('SELECT * FROM houses WHERE id = ?', array($id)) : null;
if ($id !== '' && !$x) { http_response_code(404); adm_head('Проект не найден', 'houses'); echo '<p><a href="houses.php">← К проектам</a></p>'; adm_foot(); exit; }
$rates = repo_rates();
$mOpts = array(); foreach ($rates['materials'] as $k => $m) $mOpts[$k] = $m['name'];
$fOpts = array(); foreach ($rates['foundations'] as $k => $m) $fOpts[$k] = $m['name'];
$def = array('id' => '', 'name' => '', 'area' => '', 'floors' => 1, 'bedrooms' => 2, 'bathrooms' => 1, 'material_code' => key($mOpts), 'foundation_code' => key($fOpts), 'garage' => 0, 'terrace' => 0, 'description' => '', 'img_facade' => '', 'img_interior' => '', 'img_plan' => '', 'sort_order' => (int)db_val('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM houses'), 'is_published' => 1);
$v = $x ? $x : $def; $err = array();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    adm_post_guard();
    $do = post('do');
    if ($do === 'save') {
        foreach (array('name', 'description') as $k) $v[$k] = post($k);
        foreach (array('area', 'floors', 'bedrooms', 'bathrooms', 'sort_order') as $k) $v[$k] = post($k);
        $v['material_code'] = post('material_code'); $v['foundation_code'] = post('foundation_code');
        $v['garage'] = post('garage') ? 1 : 0; $v['terrace'] = post('terrace') ? 1 : 0; $v['is_published'] = post('is_published') ? 1 : 0;
        if (!$x) { $v['id'] = strtolower(post('id')); if (!preg_match('/^[a-z0-9][a-z0-9-]{1,38}$/', $v['id'])) $err['id'] = 'Латиница, цифры и дефис, например serge-130'; elseif (db_val('SELECT id FROM houses WHERE id = ?', array($v['id']))) $err['id'] = 'Такой код уже есть'; }
        if ($v['name'] === '' || mb_strlen($v['name']) > 60) $err['name'] = 'Название до 60 символов';
        if (!ctype_digit((string)$v['area']) || $v['area'] < 10 || $v['area'] > 1000) $err['area'] = 'Площадь от 10 до 1000 м²';
        if (!ctype_digit((string)$v['floors']) || $v['floors'] < 1 || $v['floors'] > $rates['floors_max']) $err['floors'] = 'От 1 до ' . $rates['floors_max'];
        foreach (array('bedrooms', 'bathrooms') as $k) if (!ctype_digit((string)$v[$k]) || $v[$k] > 20) $err[$k] = 'Число от 0 до 20';
        if (!ctype_digit((string)$v['sort_order'])) $err['sort_order'] = 'Целое число';
        if (!isset($mOpts[$v['material_code']])) $err['material_code'] = 'Выберите материал';
        if (!isset($fOpts[$v['foundation_code']])) $err['foundation_code'] = 'Выберите фундамент';
        $imgs = array();
        foreach (array('img_facade' => 'facade', 'img_interior' => 'interior', 'img_plan' => 'plan') as $col => $fld) {
            list($path, $perr) = upload_image($fld, 'houses');
            if ($perr) $err[$fld] = $perr;
            $imgs[$col] = $path ? $path : (post('del_' . $fld) ? null : ($x ? $x[$col] : null));
        }
        if (!$err) {
            $par = array($v['name'], (int)$v['area'], (int)$v['floors'], (int)$v['bedrooms'], (int)$v['bathrooms'], $v['material_code'], $v['foundation_code'], $v['garage'], $v['terrace'], $v['description'], $imgs['img_facade'], $imgs['img_interior'], $imgs['img_plan'], (int)$v['sort_order'], $v['is_published']);
            if ($x) { $par[] = $id; db_exec('UPDATE houses SET name=?, area=?, floors=?, bedrooms=?, bathrooms=?, material_code=?, foundation_code=?, garage=?, terrace=?, description=?, img_facade=?, img_interior=?, img_plan=?, sort_order=?, is_published=? WHERE id=?', $par); audit('house_edit', 'house', $id); adm_back('house.php?id=' . $id, 'ok', 'Сохранено'); }
            array_unshift($par, $v['id']);
            db_exec('INSERT INTO houses (id, name, area, floors, bedrooms, bathrooms, material_code, foundation_code, garage, terrace, description, img_facade, img_interior, img_plan, sort_order, is_published) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', $par);
            audit('house_create', 'house', $v['id']); adm_back('house.php?id=' . $v['id'], 'ok', 'Проект добавлен на сайт');
        }
        foreach (array('img_facade', 'img_interior', 'img_plan') as $col) $v[$col] = $x ? $x[$col] : '';
    } elseif ($x && $do === 'delete') {
        db_exec('DELETE FROM houses WHERE id = ?', array($id)); audit('house_delete', 'house', $id); adm_back('houses.php', 'ok', 'Проект удалён');
    }
}
adm_head($x ? $x['name'] : 'Новый проект', 'houses');
$prev = $x && isset($mOpts[$x['material_code']]) && isset($fOpts[$x['foundation_code']]) ? rub(raschet($x['area'], $x['floors'], $x['material_code'], $x['foundation_code'], $rates)['total']) : null;
$defImg = array('img_facade' => 'assets/img/' . $v['id'] . '-1.webp', 'img_interior' => 'assets/img/' . $v['id'] . '-2.webp', 'img_plan' => 'assets/img/' . $v['id'] . '-3.svg');
?>
<p><a href="houses.php">← К проектам</a><?= $x ? ' · <a href="../house.php?id=' . h($id) . '" target="_blank" rel="noopener">открыть на сайте</a>' : '' ?></p>
<?php if ($prev): ?><div class="podskazka">Цена «от» на сайте по текущим расценкам: <b><?= h($prev) ?></b></div><?php endif; ?>
<form class="adm-card adm-form" method="post" enctype="multipart/form-data">
    <?= csrf_field() ?><input type="hidden" name="do" value="save">
    <div class="adm-grid">
        <?= $x ? '<div class="pole"><label>Код (адрес страницы)</label><input type="text" value="' . h($id) . '" disabled></div>' : fld('id', 'Код (латиницей, адрес страницы)', $v['id'], array('req' => 1, 'err' => $err, 'placeholder' => 'serge-130')) ?>
        <?= fld('name', 'Название', $v['name'], array('req' => 1, 'err' => $err, 'maxlength' => 60)) ?>
        <?= fld('area', 'Площадь, м²', $v['area'], array('type' => 'number', 'req' => 1, 'min' => 10, 'max' => 1000, 'err' => $err)) ?>
        <?= fld('floors', 'Этажей', $v['floors'], array('type' => 'number', 'req' => 1, 'min' => 1, 'max' => $rates['floors_max'], 'err' => $err)) ?>
        <?= fld('bedrooms', 'Спален', $v['bedrooms'], array('type' => 'number', 'min' => 0, 'err' => $err)) ?>
        <?= fld('bathrooms', 'Санузлов', $v['bathrooms'], array('type' => 'number', 'min' => 0, 'err' => $err)) ?>
        <?= fld('material_code', 'Материал стен', $v['material_code'], array('type' => 'select', 'opts' => $mOpts, 'err' => $err)) ?>
        <?= fld('foundation_code', 'Фундамент', $v['foundation_code'], array('type' => 'select', 'opts' => $fOpts, 'err' => $err)) ?>
        <?= fld('sort_order', 'Порядок в каталоге', $v['sort_order'], array('type' => 'number', 'min' => 0, 'err' => $err)) ?>
    </div>
    <?= fld('description', 'Описание', $v['description'], array('type' => 'textarea', 'rows' => 3)) ?>
    <div class="adm-actions"><?= chk('garage', 'Есть гараж', $v['garage']) ?> <?= chk('terrace', 'Есть терраса', $v['terrace']) ?> <?= chk('is_published', 'Показывать на сайте', $v['is_published']) ?></div>
    <h2>Изображения</h2>
    <p class="melko">Если картинка не загружена, используется файл по умолчанию assets/img/&lt;код&gt;-N. Подходят JPG, PNG, WebP до 5 МБ, лучше пропорции 4:3.</p>
    <div class="adm-grid adm-grid--3">
    <?php foreach (array('facade' => array('img_facade', 'Фасад'), 'interior' => array('img_interior', 'Интерьер'), 'plan' => array('img_plan', 'Планировка')) as $fld => $c): $col = $c[0]; $cur = $v[$col]; ?>
        <div class="pole<?= isset($err[$fld]) ? ' est-oshibka' : '' ?>">
            <label for="f_<?= $fld ?>"><?= h($c[1]) ?></label>
            <?php if ($x): ?><div class="adm-img"><img src="../<?= h($cur ?: $defImg[$col]) ?>" alt="" onerror="this.style.visibility='hidden'"><?= $cur ? chk('del_' . $fld, 'вернуть по умолчанию', 0) : '<span class="melko">по умолчанию</span>' ?></div><?php endif; ?>
            <input type="file" id="f_<?= $fld ?>" name="<?= $fld ?>" accept="image/jpeg,image/png,image/webp">
            <?= f_err($err, $fld) ?>
        </div>
    <?php endforeach; ?>
    </div>
    <div class="adm-actions"><button class="knopka knopka--cher" type="submit"><?= $x ? 'Сохранить' : 'Добавить проект' ?></button></div>
</form>
<?php if ($x): ?><form method="post" data-confirm="Удалить проект «<?= h($x['name']) ?>»?"><?= csrf_field() ?><input type="hidden" name="do" value="delete"><button class="knopka knopka--ramka adm-danger" type="submit">Удалить проект</button></form><?php endif; adm_foot();
