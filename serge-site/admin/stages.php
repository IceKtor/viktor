<?php
require_once __DIR__ . '/../includes/admin_ui.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    adm_post_guard();
    $do = post('do'); $sid = post_int('sid');
    if ($do === 'save' || $do === 'add') {
        $title = post('title'); $short = post('short'); $text = post('text'); $time = post('time'); $sort = post('sort');
        if ($title === '' || $short === '' || !ctype_digit($sort)) adm_back('stages.php', 'err', 'Заполните название, краткое описание и порядок (число)');
        if ($do === 'save') { db_exec('UPDATE stages SET title=?, short=?, text=?, time=?, sort_order=? WHERE id=?', array($title, $short, $text, $time, (int)$sort, $sid)); audit('stage_edit', 'stage', $sid); adm_back('stages.php', 'ok', 'Этап сохранён'); }
        $nid = db_insert('INSERT INTO stages (title, short, text, time, sort_order) VALUES (?, ?, ?, ?, ?)', array($title, $short, $text, $time, (int)$sort)); audit('stage_add', 'stage', $nid); adm_back('stages.php', 'ok', 'Этап добавлен');
    } elseif ($do === 'delete') {
        db_exec('DELETE FROM stages WHERE id = ?', array($sid)); audit('stage_delete', 'stage', $sid); adm_back('stages.php', 'ok', 'Этап удалён');
    }
}
$rows = db_all('SELECT * FROM stages ORDER BY sort_order, id');
adm_head('Этапы строительства', 'stages');
?>
<p class="melko">Этапы показываются на главной странице, на странице «Строительство» и в кабинетах клиентов (цепочка этапов объекта). Порядок — по возрастанию числа.</p>
<?php foreach ($rows as $r): ?>
<details>
    <summary><?= (int)$r['sort_order'] ?>. <?= h($r['title']) ?> <span class="melko-t">— <?= h($r['time']) ?></span></summary>
    <form class="adm-form" method="post" style="margin-top:10px">
        <?= csrf_field() ?><input type="hidden" name="do" value="save"><input type="hidden" name="sid" value="<?= (int)$r['id'] ?>">
        <div class="adm-grid"><?= fld('title', 'Название', $r['title'], array('req' => 1)) ?><?= fld('time', 'Срок', $r['time']) ?><?= fld('sort', 'Порядок', $r['sort_order'], array('type' => 'number', 'min' => 0)) ?></div>
        <?= fld('short', 'Коротко (для главной)', $r['short'], array('req' => 1)) ?>
        <?= fld('text', 'Подробно (страница «Строительство»)', $r['text'], array('type' => 'textarea', 'rows' => 3)) ?>
        <div class="adm-actions"><button class="knopka knopka--cher" type="submit">Сохранить</button></div>
    </form>
    <form method="post" data-confirm="Удалить этап?" style="margin-top:8px"><?= csrf_field() ?><input type="hidden" name="do" value="delete"><input type="hidden" name="sid" value="<?= (int)$r['id'] ?>"><button class="knopka knopka--ramka adm-danger" type="submit">Удалить этап</button></form>
</details>
<?php endforeach; ?>
<h2>Добавить этап</h2>
<form class="adm-card adm-form" method="post">
    <?= csrf_field() ?><input type="hidden" name="do" value="add">
    <div class="adm-grid"><?= fld('title', 'Название', '', array('req' => 1)) ?><?= fld('time', 'Срок', '') ?><?= fld('sort', 'Порядок', count($rows) + 1, array('type' => 'number', 'min' => 0)) ?></div>
    <?= fld('short', 'Коротко (для главной)', '', array('req' => 1)) ?>
    <?= fld('text', 'Подробно', '', array('type' => 'textarea', 'rows' => 3)) ?>
    <div class="adm-actions"><button class="knopka knopka--derevo" type="submit">Добавить</button></div>
</form>
<?php adm_foot();
