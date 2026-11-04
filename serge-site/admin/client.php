<?php
require_once __DIR__ . '/../includes/admin_ui.php';
$id = get_int('id');
$u = $id ? db_row("SELECT * FROM users WHERE id = ? AND role = 'client'", array($id)) : null;
if ($id && !$u) { http_response_code(404); adm_head('Клиент не найден', 'clients'); echo '<p><a href="clients.php">← К клиентам</a></p>'; adm_foot(); exit; }
$err = array(); $v = $u ? array('name' => $u['name'], 'phone' => $u['phone'] ? fmt_phone($u['phone']) : '', 'email' => (string)$u['email']) : array('name' => '', 'phone' => '', 'email' => '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    adm_post_guard();
    $do = post('do');
    if ($do === 'save') {
        $v = array('name' => post('name'), 'phone' => post('phone'), 'email' => post('email'));
        $phone = norm_phone($v['phone']); $email = $v['email'] === '' ? null : norm_email($v['email']);
        if (mb_strlen($v['name']) < 2) $err['name'] = 'Укажите имя';
        if (!$phone) $err['phone'] = 'Укажите российский номер телефона';
        if ($v['email'] !== '' && !$email) $err['email'] = 'Проверьте адрес почты';
        if (!$err && db_val('SELECT id FROM users WHERE phone = ? AND id <> ?', array($phone, $id))) $err['phone'] = 'Этот телефон уже у другого клиента';
        if (!$err && $email && db_val('SELECT id FROM users WHERE email = ? AND id <> ?', array($email, $id))) $err['email'] = 'Эта почта уже используется';
        if (!$err) {
            if ($u) {
                db_exec('UPDATE users SET name = ?, phone = ?, email = ?, email_verified_at = COALESCE(email_verified_at, NOW()) WHERE id = ?', array($v['name'], $phone, $email, $id));
                audit('client_edit', 'user', $id);
                adm_back('client.php?id=' . $id, 'ok', 'Сохранено');
            }
            $pass = gen_password();
            $nid = user_create('client', $v['name'], $phone, $email, $pass, 'admin', 1);
            audit('client_create', 'user', $nid);
            adm_cred_set($v['name'], fmt_phone($phone), $pass);
            adm_back('client.php?id=' . $nid, 'ok', 'Клиент создан');
        }
    } elseif ($u && $do === 'reset') {
        $pass = gen_password();
        db_exec('UPDATE users SET password_hash = ?, must_change_password = 1 WHERE id = ?', array(password_hash($pass, PASSWORD_DEFAULT), $id));
        audit('password_reset', 'user', $id);
        $login = $u['phone'] ? fmt_phone($u['phone']) : $u['email'];
        adm_cred_set($u['name'], $login, $pass);
        $mailMsg = '';
        if (post('send_mail') && $u['email'] && MAIL_ENABLED) {
            $sent = notify_send('email', $u['email'], notify_access_text($u['name'], $login, $pass), 'Новый пароль для личного кабинета СЭРГЭ');
            $mailMsg = $sent ? ' Письмо отправлено на ' . $u['email'] . '.' : ' Письмо отправить не удалось, передайте пароль вручную.';
        }
        adm_back('client.php?id=' . $id, 'ok', 'Выдан новый временный пароль.' . $mailMsg);
    } elseif ($u && $do === 'toggle') {
        db_exec('UPDATE users SET is_active = 1 - is_active WHERE id = ?', array($id));
        audit('client_toggle', 'user', $id);
        adm_back('client.php?id=' . $id, 'ok', $u['is_active'] ? 'Доступ отключён' : 'Доступ включён');
    } elseif ($u && $do === 'tariff') {
        db_exec('UPDATE users SET tariff = ? WHERE id = ?', array($u['tariff'] === 'vip' ? 'free' : 'vip', $id));
        audit('client_tariff', 'user', $id);
        if ($u['tariff'] !== 'vip' && $u['email'] && MAIL_ENABLED) notify_vip($u['email'], $u['name']);
        adm_back('client.php?id=' . $id, 'ok', $u['tariff'] === 'vip' ? 'Тариф: базовый' : 'Тариф: сопровождение');
    } elseif ($u && $do === 'delete') {
        db_exec('DELETE FROM users WHERE id = ?', array($id));
        audit('client_delete', 'user', $id, $u['name']);
        adm_back('clients.php', 'ok', 'Клиент удалён вместе с его объектами');
    }
}
if ($u) $u = db_row('SELECT * FROM users WHERE id = ?', array($id));
adm_head($u ? $u['name'] : 'Новый клиент', 'clients');
adm_cred_show();
?>
<p><a href="clients.php">← К клиентам</a></p>
<section class="adm-card">
    <h2>Данные клиента</h2>
    <form class="adm-form" method="post">
        <?= csrf_field() ?><input type="hidden" name="do" value="save">
        <div class="adm-grid">
            <?= fld('name', 'Имя', $v['name'], array('req' => 1, 'err' => $err)) ?>
            <?= fld('phone', 'Телефон (логин)', $v['phone'], array('req' => 1, 'type' => 'tel', 'err' => $err, 'placeholder' => '+7 900 000-00-00')) ?>
            <?= fld('email', 'Почта (тоже подходит для входа)', $v['email'], array('type' => 'email', 'err' => $err)) ?>
        </div>
        <?php if (!$u): ?><p class="melko">Пароль сгенерируется автоматически и будет показан один раз после сохранения.</p><?php endif; ?>
        <div class="adm-actions"><button class="knopka knopka--cher" type="submit"><?= $u ? 'Сохранить' : 'Создать клиента' ?></button></div>
    </form>
</section>
<?php if ($u): $objs = db_all('SELECT * FROM objects WHERE user_id = ? ORDER BY created_at DESC', array($id)); $leads = db_all('SELECT id, created_at, source, status FROM leads WHERE user_id = ? ORDER BY created_at DESC', array($id)); ?>
<section class="adm-card">
    <h2>Доступ</h2>
    <dl class="adm-kv">
        <dt>Создан</dt><dd><?= h(dt_ru($u['created_at'])) ?> (<?= $u['created_via'] === 'self' ? 'зарегистрировался сам' : 'создан администратором' ?>)</dd>
        <dt>Последний вход</dt><dd><?= h(dt_ru($u['last_login_at'])) ?></dd>
        <dt>Пароль</dt><dd><?= $u['must_change_password'] ? 'выдан администратором, клиент ещё не сменил' : 'задан клиентом' ?></dd>
        <dt>Тариф</dt><dd><?= $u['tariff'] === 'vip' ? 'сопровождение (платные услуги)' : 'базовый' ?></dd>
        <dt>Состояние</dt><dd><?= $u['is_active'] ? 'доступ включён' : 'доступ отключён' ?></dd>
    </dl>
    <div class="adm-actions" style="margin-top:14px">
        <form method="post" data-confirm="Выдать новый пароль? Старый перестанет работать."><?= csrf_field() ?><input type="hidden" name="do" value="reset"><button class="knopka knopka--derevo" type="submit">Сбросить пароль</button>
            <?php if ($u['email'] && MAIL_ENABLED): ?><?= chk('send_mail', 'отправить письмом на ' . $u['email'], 0) ?><?php endif; ?></form>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="toggle"><button class="knopka knopka--ramka" type="submit"><?= $u['is_active'] ? 'Отключить доступ' : 'Включить доступ' ?></button></form>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="tariff"><button class="knopka knopka--ramka" type="submit"><?= $u['tariff'] === 'vip' ? 'Снять сопровождение' : 'Выдать сопровождение' ?></button></form>
        <form method="post" data-confirm="Удалить клиента и все его объекты? Это нельзя отменить."><?= csrf_field() ?><input type="hidden" name="do" value="delete"><button class="knopka knopka--ramka adm-danger" type="submit">Удалить</button></form>
    </div>
</section>
<section class="adm-card">
    <h2>Объекты строительства</h2>
    <?php if ($objs): ?><div class="adm-scroll"><table class="atab"><tr><th>Код</th><th>Название</th><th>Статус</th><th>Готовность</th></tr>
    <?php foreach ($objs as $o): ?><tr><td><a href="object.php?id=<?= (int)$o['id'] ?>"><?= h($o['code']) ?></a></td><td><?= h($o['title']) ?></td><td><span class="st st--<?= h($o['status']) ?>"><?= h(object_statuses()[$o['status']]) ?></span></td><td><?= (int)$o['progress'] ?>%</td></tr><?php endforeach; ?></table></div>
    <?php else: ?><p class="seryj">Объектов пока нет.</p><?php endif; ?>
    <p><a class="knopka knopka--ramka" href="object.php?user_id=<?= $id ?>">+ Добавить объект</a></p>
</section>
<?php if ($leads): ?><section class="adm-card"><h2>Заявки клиента</h2><ul><?php foreach ($leads as $l): ?><li><a class="ssylka" href="lead.php?id=<?= (int)$l['id'] ?>">№ <?= (int)$l['id'] ?></a> — <?= h(dt_ru($l['created_at'])) ?>, <?= h($l['source']) ?>, <?= h(lead_statuses()[$l['status']]) ?></li><?php endforeach; ?></ul></section><?php endif; ?>
<?php endif; adm_foot();
