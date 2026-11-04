<?php
require_once __DIR__ . '/../includes/admin_ui.php';
$id = get_int('id');
$l = db_row('SELECT * FROM leads WHERE id = ?', array($id));
if (!$l) { http_response_code(404); adm_head('Заявка не найдена', 'leads'); echo '<p><a href="leads.php">← К заявкам</a></p>'; adm_foot(); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    adm_post_guard();
    $do = post('do');
    if ($do === 'save') {
        $st = post('status'); if (!isset(lead_statuses()[$st])) $st = $l['status'];
        db_exec('UPDATE leads SET status = ?, admin_note = ?, updated_at = NOW() WHERE id = ?', array($st, post('admin_note') ?: null, $id));
        audit('lead_save', 'lead', $id, 'статус: ' . $st);
        if ($st !== $l['status'] && $l['email'] && MAIL_ENABLED) notify_lead_status($l['email'], $l['name'], $id, $st);
        adm_back('lead.php?id=' . $id, 'ok', 'Сохранено');
    } elseif ($do === 'make_account') {
        if ($l['user_id']) adm_back('lead.php?id=' . $id, 'info', 'Заявка уже привязана к клиенту');
        $user = db_row("SELECT * FROM users WHERE phone = ? AND role = 'client'", array($l['phone']));
        if ($user) {
            db_exec('UPDATE leads SET user_id = ?, updated_at = NOW() WHERE id = ?', array($user['id'], $id));
            audit('lead_link', 'lead', $id, 'клиент ' . $user['id']);
            adm_back('lead.php?id=' . $id, 'ok', 'Клиент с таким телефоном уже есть — заявка привязана к нему. Пароль не менялся.');
        }
        $email = $l['email'] ? norm_email($l['email']) : null;
        if ($email && db_val('SELECT id FROM users WHERE email = ?', array($email))) $email = null;
        $pass = gen_password();
        $uid = user_create('client', $l['name'], $l['phone'], $email, $pass, 'admin', 1);
        db_exec("UPDATE leads SET user_id = ?, status = IF(status IN ('new','in_work'), 'deal', status), updated_at = NOW() WHERE id = ?", array($uid, $id));
        audit('client_create', 'user', $uid, 'из заявки ' . $id);
        adm_cred_set($l['name'], fmt_phone($l['phone']), $pass);
        $mailMsg = '';
        if (post('send_mail') && $l['email'] && MAIL_ENABLED) {
            $sent = notify_send('email', $l['email'], notify_access_text($l['name'], fmt_phone($l['phone']), $pass), 'Доступ в личный кабинет СЭРГЭ');
            $mailMsg = $sent ? ' Письмо с доступом отправлено на ' . $l['email'] . '.' : ' Письмо отправить не удалось (проверьте почту на странице «Почта»), передайте доступ вручную.';
        }
        adm_back('lead.php?id=' . $id, 'ok', 'Кабинет клиента создан, статус заявки — «Договор заключён». Ниже создайте объект: без него в кабинете не будет хода строительства.' . $mailMsg);
    } elseif ($do === 'link') {
        $uid = post_int('user_id');
        if ($uid && db_val("SELECT id FROM users WHERE id = ? AND role = 'client'", array($uid))) {
            db_exec('UPDATE leads SET user_id = ?, updated_at = NOW() WHERE id = ?', array($uid, $id)); audit('lead_link', 'lead', $id, 'клиент ' . $uid);
            adm_back('lead.php?id=' . $id, 'ok', 'Заявка привязана');
        }
        adm_back('lead.php?id=' . $id, 'err', 'Выберите клиента');
    } elseif ($do === 'unlink') {
        db_exec('UPDATE leads SET user_id = NULL, updated_at = NOW() WHERE id = ?', array($id)); audit('lead_unlink', 'lead', $id);
        adm_back('lead.php?id=' . $id, 'ok', 'Привязка снята');
    } elseif ($do === 'delete') {
        db_exec('DELETE FROM leads WHERE id = ?', array($id)); audit('lead_delete', 'lead', $id);
        adm_back('leads.php', 'ok', 'Заявка удалена');
    }
}
$l = db_row('SELECT * FROM leads WHERE id = ?', array($id));
$p = $l['params'] ? json_decode($l['params'], true) : null;
$user = $l['user_id'] ? db_row('SELECT id, name, phone FROM users WHERE id = ?', array($l['user_id'])) : null;
$clients = $user ? array() : db_all("SELECT id, name, phone FROM users WHERE role = 'client' ORDER BY name LIMIT 300");
$rates = repo_rates();
adm_head('Заявка № ' . $id, 'leads');
adm_cred_show();
?>
<p><a href="leads.php">← К заявкам</a></p>
<div class="adm-grid adm-grid--2">
<section class="adm-card">
    <h2>Заявка</h2>
    <dl class="adm-kv">
        <dt>Создана</dt><dd><?= h(dt_ru($l['created_at'])) ?></dd>
        <dt>Имя</dt><dd><?= h($l['name']) ?></dd>
        <dt>Телефон</dt><dd><a href="tel:+<?= h($l['phone']) ?>"><?= h(fmt_phone($l['phone'])) ?></a></dd>
        <dt>Почта</dt><dd><?= $l['email'] ? '<a href="mailto:' . h($l['email']) . '">' . h($l['email']) . '</a>' : '—' ?></dd>
        <dt>Источник</dt><dd><?= h($l['source']) ?></dd>
        <dt>Сообщение</dt><dd><?= $l['message'] ? nl2br(h($l['message'])) : '—' ?></dd>
        <dt>Согласие на ПДн</dt><dd><?= h(dt_ru($l['consent_at'])) ?></dd>
        <?php if ($p): ?>
        <dt>Расчёт</dt><dd><?= h(str_replace('.', ',', $p['area'])) ?> м², <?= h(floors_text($p['floors'])) ?>, <?= h(isset($rates['materials'][$p['material']]) ? $rates['materials'][$p['material']]['name'] : $p['material']) ?>, <?= h(isset($rates['foundations'][$p['foundation']]) ? mb_strtolower($rates['foundations'][$p['foundation']]['name']) : $p['foundation']) ?><br><b>≈ <?= h(rub($p['total'])) ?></b> <span class="melko-t">(<?= h(rub($p['low'])) ?> — <?= h(rub($p['high'])) ?>)</span></dd>
        <?php endif; ?>
    </dl>
</section>
<section class="adm-card">
    <h2>Работа с заявкой</h2>
    <form class="adm-form" method="post">
        <?= csrf_field() ?><input type="hidden" name="do" value="save">
        <?= fld('status', 'Статус', $l['status'], array('type' => 'select', 'opts' => lead_statuses())) ?>
        <?= fld('admin_note', 'Заметка менеджера (клиент её не видит)', $l['admin_note'], array('type' => 'textarea', 'rows' => 4)) ?>
        <div class="adm-actions"><button class="knopka knopka--cher" type="submit">Сохранить</button></div>
    </form>
</section>
</div>

<section class="adm-card">
    <h2>Кабинет клиента</h2>
    <?php if ($user): ?>
        <p>Заявка привязана к клиенту <a class="ssylka" href="client.php?id=<?= (int)$user['id'] ?>"><?= h($user['name']) ?></a>, <?= h(fmt_phone($user['phone'])) ?>. Клиент видит эту заявку в своём кабинете.</p>
        <form method="post" class="adm-actions"><?= csrf_field() ?><input type="hidden" name="do" value="unlink"><button class="knopka knopka--ramka" type="submit">Снять привязку</button></form>
    <?php else: ?>
        <p>После того как клиент согласился на сделку, создайте ему кабинет: логин — этот номер телефона, пароль сгенерируется автоматически. Вы передадите его клиенту (звонок, мессенджер), при первом входе клиент заменит пароль на свой. Если клиент с таким телефоном уже зарегистрировался сам, заявка просто привяжется к нему.</p>
        <form method="post" class="adm-actions"><?= csrf_field() ?><input type="hidden" name="do" value="make_account"><button class="knopka knopka--derevo" type="submit">Создать кабинет по этой заявке</button>
            <?php if ($l['email'] && MAIL_ENABLED): ?><?= chk('send_mail', 'Отправить доступ письмом на ' . $l['email'], 0) ?><?php endif; ?></form>
        <?php if ($clients): ?>
        <form method="post" class="adm-actions" style="margin-top:14px"><?= csrf_field() ?><input type="hidden" name="do" value="link">
            <select name="user_id" aria-label="Клиент"><option value="">— или привязать к существующему клиенту —</option><?php foreach ($clients as $c): ?><option value="<?= (int)$c['id'] ?>"><?= h($c['name']) ?> · <?= h(fmt_phone($c['phone'])) ?></option><?php endforeach; ?></select>
            <button class="knopka knopka--ramka" type="submit">Привязать</button></form>
        <?php endif; ?>
    <?php endif; ?>
</section>
<?php if ($user):
    $objs = db_all('SELECT * FROM objects WHERE user_id = ? ORDER BY created_at DESC', array($user['id']));
    $stg = array_filter(repo_stages(), function ($s) { return isset($s['id']); });
?>
<section class="adm-card">
    <h2>Ход строительства</h2>
    <?php if (!$objs): ?>
        <p>У клиента ещё нет объекта, поэтому в кабинете нет хода строительства. Создайте объект: клиент и проект подставятся из заявки.</p>
    <?php else: foreach ($objs as $o): ?>
        <div class="adm-obj">
            <p><a class="ssylka" href="object.php?id=<?= (int)$o['id'] ?>"><b><?= h($o['code']) ?></b> · <?= h($o['title']) ?></a> — <?= h(object_statuses()[$o['status']]) ?>, <?= (int)$o['progress'] ?>%</p>
            <form class="adm-actions" method="post" action="object.php?id=<?= (int)$o['id'] ?>"><?= csrf_field() ?><input type="hidden" name="do" value="quick_stage"><input type="hidden" name="lead" value="<?= $id ?>">
                <select name="stage_id" aria-label="Этап"><option value="">— перевести на этап —</option><?php foreach ($stg as $s): ?><option value="<?= (int)$s['id'] ?>"<?= (int)$s['id'] === (int)$o['current_stage_id'] ? ' selected' : '' ?>><?= h($s['title']) ?></option><?php endforeach; ?></select>
                <button class="knopka knopka--derevo" type="submit">Сменить этап</button>
                <a class="knopka knopka--ramka" href="object.php?id=<?= (int)$o['id'] ?>">Новости и фото</a></form>
        </div>
    <?php endforeach; endif; ?>
    <p><a class="knopka knopka--ramka" href="object.php?lead=<?= $id ?>">+ Создать объект по заявке</a></p>
</section>
<?php endif; ?>
<form method="post" data-confirm="Удалить заявку № <?= $id ?>? Это нельзя отменить."><?= csrf_field() ?><input type="hidden" name="do" value="delete"><button class="knopka knopka--ramka adm-danger" type="submit">Удалить заявку</button></form>
<?php adm_foot();
