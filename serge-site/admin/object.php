<?php
// объект строительства: карточка + лента новостей, которую видит клиент
require_once __DIR__ . '/../includes/admin_ui.php';
$id = get_int('id');
$o = $id ? db_row('SELECT * FROM objects WHERE id = ?', array($id)) : null;
if ($id && !$o) { http_response_code(404); adm_head('Объект не найден', 'objects'); echo '<p><a href="objects.php">← К объектам</a></p>'; adm_foot(); exit; }
$stages = repo_stages();
$stageOpts = array('' => '— не выбран —'); foreach ($stages as $s) $stageOpts[$s['id']] = $s['title'];
$clients = db_all("SELECT id, name, phone FROM users WHERE role = 'client' ORDER BY name");
$clientOpts = array('' => '— выберите клиента —'); foreach ($clients as $c) $clientOpts[$c['id']] = $c['name'] . ($c['phone'] ? ' · ' . fmt_phone($c['phone']) : '');
$houseOpts = array('' => '— не указан —'); foreach (repo_houses(true) as $hh) $houseOpts[$hh['id']] = $hh['name'];
$nextCode = 'СЭРГЭ-' . sprintf('%03d', (int)db_val('SELECT COALESCE(MAX(id), 0) + 1 FROM objects'));

// объект «по заявке»: клиент и проект берутся из заявки
$fromLead = get_int('lead') ? db_row('SELECT * FROM leads WHERE id = ?', array(get_int('lead'))) : null;
$leadHouse = ($fromLead && strpos($fromLead['source'], 'house:') === 0 && isset($houseOpts[substr($fromLead['source'], 6)])) ? substr($fromLead['source'], 6) : '';
$def = array('code' => $nextCode, 'user_id' => $fromLead && $fromLead['user_id'] ? $fromLead['user_id'] : (get_int('user_id') ?: ''), 'house_id' => $leadHouse, 'title' => $leadHouse ? 'Дом «' . $houseOpts[$leadHouse] . '»' : '', 'address' => '', 'contract_no' => '', 'status' => 'design', 'current_stage_id' => '', 'progress' => '', 'started_at' => '', 'planned_end' => '');
$v = $o ? $o : $def; $err = array();

// клиенту уходит письмо (если у него подтверждённая почта)
function obj_notify($objId, $line) {
    $r = db_row('SELECT u.name, u.email, u.email_verified_at, o.title FROM objects o JOIN users u ON u.id = o.user_id WHERE o.id = ?', array($objId));
    if ($r && $r['email'] && $r['email_verified_at'] && MAIL_ENABLED) notify_object($r['email'], $r['name'], $r['title'], $line);
}
// сданный объект остаётся на 100 %; письмо отправляется, только если $notify
function obj_move($objId, $sid, $stages, $notify = true) {
    $title = ''; foreach ($stages as $s) if ((int)$s['id'] === (int)$sid) $title = $s['title'];
    $prog = obj_progress($sid, $stages);
    db_exec("UPDATE objects SET current_stage_id = ?, progress = IF(status = 'done', 100, ?), status = IF(status = 'design', 'building', status) WHERE id = ?", array($sid, $prog, $objId));
    if ($notify && db_val("SELECT status FROM objects WHERE id = ?", array($objId)) !== 'done') obj_notify($objId, 'Начат этап «' . $title . '», готовность ' . $prog . '%.');
}
function obj_progress($stageId, $stages) { $n = count($stages); foreach ($stages as $i => $s) if ((int)$s['id'] === (int)$stageId) return (int)floor($i / max(1, $n) * 100); return 0; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    adm_post_guard();
    $do = post('do');
    if ($do === 'save') {
        foreach (array_keys($def) as $k) $v[$k] = post($k, '');
        if ($v['code'] === '' || mb_strlen($v['code']) > 30) $err['code'] = 'Укажите код объекта (до 30 символов)';
        elseif (db_val('SELECT id FROM objects WHERE code = ? AND id <> ?', array($v['code'], $id))) $err['code'] = 'Такой код уже есть';
        if (!$v['user_id'] || !db_val("SELECT id FROM users WHERE id = ? AND role = 'client'", array((int)$v['user_id']))) $err['user_id'] = 'Выберите клиента';
        if ($v['title'] === '') $err['title'] = 'Укажите название';
        if (!isset(object_statuses()[$v['status']])) $v['status'] = 'design';
        if ($v['house_id'] !== '' && !isset($houseOpts[$v['house_id']])) $err['house_id'] = 'Такого проекта нет';
        if ($v['current_stage_id'] !== '' && !isset($stageOpts[$v['current_stage_id']])) $err['current_stage_id'] = 'Такого этапа нет';
        if ($v['progress'] !== '' && (!ctype_digit($v['progress']) || (int)$v['progress'] > 100)) $err['progress'] = 'Число от 0 до 100';
        foreach (array('started_at', 'planned_end') as $dk) if ($v[$dk] !== '' && !(preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $v[$dk], $dm) && checkdate((int)$dm[2], (int)$dm[3], (int)$dm[1]))) $err[$dk] = 'Дата в формате ГГГГ-ММ-ДД';
        if (!isset($err['started_at']) && !isset($err['planned_end']) && $v['started_at'] !== '' && $v['planned_end'] !== '' && $v['planned_end'] < $v['started_at']) $err['planned_end'] = 'Сдача раньше начала работ';
        if (!$err) {
            $stage = $v['current_stage_id'] !== '' ? (int)$v['current_stage_id'] : null;
            $stageMoved = $o && $stage && (int)$o['current_stage_id'] !== $stage;
            // этап сменили, а готовность не трогали: считаем по этапу, а не оставляем старую
            if ($stageMoved && (string)$v['progress'] === (string)$o['progress']) $v['progress'] = '';
            $prog = $v['progress'] !== '' ? (int)$v['progress'] : ($v['status'] === 'done' ? 100 : ($stage ? obj_progress($stage, $stages) : 0));
            if ($v['status'] === 'done') $prog = 100;
            $par = array($v['code'], (int)$v['user_id'], $v['house_id'] !== '' ? $v['house_id'] : null, $v['title'], $v['address'] ?: null, $v['contract_no'] ?: null, $v['status'], $stage, $prog, $v['started_at'] ?: null, $v['planned_end'] ?: null);
            if ($o) { $par[] = $id; db_exec('UPDATE objects SET code=?, user_id=?, house_id=?, title=?, address=?, contract_no=?, status=?, current_stage_id=?, progress=?, started_at=?, planned_end=? WHERE id=?', $par); audit('object_edit', 'object', $id); if ($o['status'] !== 'done' && $v['status'] === 'done') obj_notify($id, 'Ваш дом сдан. Поздравляем!'); elseif ($stageMoved && $v['status'] !== 'done') obj_notify($id, 'Начат этап «' . $stageOpts[$stage] . '», готовность ' . $prog . '%.'); adm_back('object.php?id=' . $id, 'ok', 'Сохранено' . ($stageMoved ? '. Клиент увидит новый этап в кабинете' : '')); }
            $nid = db_insert('INSERT INTO objects (code, user_id, house_id, title, address, contract_no, status, current_stage_id, progress, started_at, planned_end, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?, NOW())', $par);
            audit('object_create', 'object', $nid); adm_back('object.php?id=' . $nid, 'ok', 'Объект создан. Клиент видит его в личном кабинете.');
        }
    } elseif ($o && $do === 'update_add') {
        $text = post('text'); $sid = post('stage_id') !== '' ? post_int('stage_id') : null;
        list($photo, $perr) = upload_image('photo', 'objects');
        if ($text === '') adm_back('object.php?id=' . $id, 'err', 'Напишите текст новости');
        if ($sid && !isset($stageOpts[$sid])) $sid = null;
        if ($perr) adm_back('object.php?id=' . $id, 'err', $perr);
        db_insert('INSERT INTO object_updates (object_id, stage_id, text, photo, is_visible, created_at) VALUES (?, ?, ?, ?, 1, NOW())', array($id, $sid, $text, $photo));
        if (post('set_stage') && $sid) obj_move($id, $sid, $stages, false);
        if (post('notify')) obj_notify($id, $text);
        audit('object_update', 'object', $id);
        adm_back('object.php?id=' . $id, 'ok', 'Новость добавлена' . (post('set_stage') && $sid ? ', этап объекта обновлён' : ''));
    } elseif ($o && $do === 'quick_stage') {
        $sid = post_int('stage_id'); $ret = post_int('lead') ? 'lead.php?id=' . post_int('lead') : 'object.php?id=' . $id;
        if (!isset($stageOpts[$sid])) adm_back($ret, 'err', 'Выберите этап');
        if ((int)$o['current_stage_id'] === $sid) adm_back($ret, 'info', 'Этот этап уже текущий');
        obj_move($id, $sid, $stages);
        db_insert('INSERT INTO object_updates (object_id, stage_id, text, photo, is_visible, created_at) VALUES (?, ?, ?, NULL, 1, NOW())', array($id, $sid, 'Начат этап «' . $stageOpts[$sid] . '»'));
        audit('object_stage', 'object', $id, $stageOpts[$sid]);
        adm_back($ret, 'ok', 'Этап обновлён, клиент увидит его в кабинете');
    } elseif ($o && $do === 'update_toggle') {
        db_exec('UPDATE object_updates SET is_visible = 1 - is_visible WHERE id = ? AND object_id = ?', array(post_int('uid'), $id)); adm_back('object.php?id=' . $id, 'ok', 'Готово');
    } elseif ($o && $do === 'update_delete') {
        db_exec('DELETE FROM object_updates WHERE id = ? AND object_id = ?', array(post_int('uid'), $id)); adm_back('object.php?id=' . $id, 'ok', 'Новость удалена');
    } elseif ($o && $do === 'delete') {
        db_exec('DELETE FROM objects WHERE id = ?', array($id)); audit('object_delete', 'object', $id, $o['code']); adm_back('objects.php', 'ok', 'Объект удалён');
    }
}
adm_head($o ? 'Объект ' . $o['code'] : 'Новый объект', 'objects');
?>
<p><a href="objects.php">← К объектам</a><?= $o ? ' · <a href="client.php?id=' . (int)$o['user_id'] . '">клиент</a>' : '' ?></p>
<section class="adm-card">
    <h2>Карточка объекта</h2>
    <form class="adm-form" method="post">
        <?= csrf_field() ?><input type="hidden" name="do" value="save">
        <div class="adm-grid">
            <?= fld('code', 'Код объекта', $v['code'], array('req' => 1, 'err' => $err)) ?>
            <?= fld('user_id', 'Клиент', $v['user_id'], array('type' => 'select', 'opts' => $clientOpts, 'err' => $err)) ?>
            <?= fld('house_id', 'Проект дома', $v['house_id'], array('type' => 'select', 'opts' => $houseOpts, 'err' => $err)) ?>
            <?= fld('title', 'Название для клиента', $v['title'], array('req' => 1, 'err' => $err, 'hint' => 'Например: «Дом СЭРГЭ 120, пос. Хатассы»')) ?>
            <?= fld('address', 'Адрес участка', $v['address']) ?>
            <?= fld('contract_no', 'Номер договора', $v['contract_no']) ?>
            <?= fld('status', 'Статус', $v['status'], array('type' => 'select', 'opts' => object_statuses())) ?>
            <?= fld('current_stage_id', 'Текущий этап', $v['current_stage_id'], array('type' => 'select', 'opts' => $stageOpts, 'err' => $err)) ?>
            <?= fld('progress', 'Готовность, %', $v['progress'], array('type' => 'number', 'min' => 0, 'max' => 100, 'err' => $err, 'hint' => 'Пусто — посчитается по этапу')) ?>
            <?= fld('started_at', 'Начало работ', $v['started_at'], array('type' => 'date', 'err' => $err)) ?>
            <?= fld('planned_end', 'Плановая сдача', $v['planned_end'], array('type' => 'date', 'err' => $err)) ?>
        </div>
        <div class="adm-actions"><button class="knopka knopka--cher" type="submit"><?= $o ? 'Сохранить' : 'Создать объект' ?></button></div>
    </form>
</section>
<?php if ($o): $ups = db_all('SELECT u.*, s.title AS st FROM object_updates u LEFT JOIN stages s ON s.id = u.stage_id WHERE u.object_id = ? ORDER BY u.created_at DESC, u.id DESC', array($id)); ?>
<section class="adm-card">
    <h2>Новости объекта (видны клиенту)</h2>
    <form class="adm-form" method="post" enctype="multipart/form-data">
        <?= csrf_field() ?><input type="hidden" name="do" value="update_add">
        <?= fld('text', 'Что произошло', '', array('type' => 'textarea', 'rows' => 3, 'req' => 1, 'maxlength' => 2000, 'placeholder' => 'Например: Залиты сваи под первый этаж')) ?>
        <div class="adm-grid">
            <?= fld('stage_id', 'Этап', $o['current_stage_id'], array('type' => 'select', 'opts' => $stageOpts)) ?>
            <div class="pole"><label for="photo">Фото (JPG, PNG, WebP до 5 МБ)</label><input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp"></div>
        </div>
        <?= chk('set_stage', 'Сделать выбранный этап текущим и пересчитать готовность', 0) ?>
        <?= chk('notify', 'Отправить клиенту письмом', 0) ?>
        <div class="adm-actions"><button class="knopka knopka--derevo" type="submit">Опубликовать</button></div>
    </form>
    <?php if ($ups): ?><div class="adm-scroll" style="margin-top:16px"><table class="atab"><tr><th>Дата</th><th>Этап</th><th>Текст</th><th></th></tr>
    <?php foreach ($ups as $up): ?><tr><td><?= h(dt_ru($up['created_at'])) ?></td><td><?= h($up['st'] ?: '—') ?></td>
        <td><?= nl2br(h($up['text'])) ?><?= $up['photo'] ? '<br><a href="../' . h($up['photo']) . '" target="_blank" rel="noopener">фото</a>' : '' ?></td>
        <td class="adm-actions"><form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="update_toggle"><input type="hidden" name="uid" value="<?= (int)$up['id'] ?>"><button class="knopka knopka--ramka" type="submit"><?= $up['is_visible'] ? 'Скрыть' : 'Показать' ?></button></form>
        <form method="post" data-confirm="Удалить новость?"><?= csrf_field() ?><input type="hidden" name="do" value="update_delete"><input type="hidden" name="uid" value="<?= (int)$up['id'] ?>"><button class="knopka knopka--ramka adm-danger" type="submit">Удалить</button></form></td></tr>
    <?php endforeach; ?></table></div><?php endif; ?>
</section>
<form method="post" data-confirm="Удалить объект вместе с новостями?"><?= csrf_field() ?><input type="hidden" name="do" value="delete"><button class="knopka knopka--ramka adm-danger" type="submit">Удалить объект</button></form>
<?php endif; adm_foot();
