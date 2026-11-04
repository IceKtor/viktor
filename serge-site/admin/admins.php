<?php
require_once __DIR__ . '/../includes/admin_ui.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    adm_post_guard();
    $do = post('do'); $uid = post_int('uid');
    if ($do === 'create') {
        $name = post('name'); $email = norm_email(post('email'));
        if (mb_strlen($name) < 2 || !$email) adm_back('admins.php', 'err', 'Укажите имя и корректную почту');
        if (db_val('SELECT id FROM users WHERE email = ?', array($email))) adm_back('admins.php', 'err', 'Эта почта уже используется');
        $pass = gen_password(); $nid = user_create('admin', $name, null, $email, $pass, 'admin', 1);
        audit('admin_create', 'user', $nid, $email); adm_cred_set($name, $email, $pass);
        adm_back('admins.php', 'ok', 'Администратор создан. Передайте ему доступ — пароль временный.');
    }
    $t = db_row("SELECT * FROM users WHERE id = ? AND role = 'admin'", array($uid));
    if (!$t) adm_back('admins.php', 'err', 'Администратор не найден');
    if ($do === 'reset') {
        $pass = gen_password(); db_exec('UPDATE users SET password_hash = ?, must_change_password = 1 WHERE id = ?', array(password_hash($pass, PASSWORD_DEFAULT), $uid));
        audit('password_reset', 'user', $uid); adm_cred_set($t['name'], $t['email'], $pass); adm_back('admins.php', 'ok', 'Выдан новый временный пароль');
    } elseif ($do === 'toggle') {
        if ((int)$uid === (int)$ADM['id']) adm_back('admins.php', 'err', 'Себя отключить нельзя');
        if ($t['is_active'] && (int)db_val("SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = 1") <= 1) adm_back('admins.php', 'err', 'Должен остаться хотя бы один активный администратор');
        db_exec('UPDATE users SET is_active = 1 - is_active WHERE id = ?', array($uid)); audit('admin_toggle', 'user', $uid); adm_back('admins.php', 'ok', 'Готово');
    }
}
$rows = db_all("SELECT * FROM users WHERE role = 'admin' ORDER BY created_at");
adm_head('Администраторы', 'admins');
adm_cred_show();
?>
<div class="adm-scroll"><table class="atab"><tr><th>Имя</th><th>Почта (логин)</th><th>Последний вход</th><th>Состояние</th><th></th></tr>
<?php foreach ($rows as $r): ?><tr><td><?= h($r['name']) ?></td><td><?= h($r['email']) ?></td><td><?= h(dt_ru($r['last_login_at'])) ?></td>
<td><?= $r['is_active'] ? 'активен' : '<span class="st">отключён</span>' ?><?= $r['must_change_password'] ? ' <span class="st st--in_work">временный пароль</span>' : '' ?></td>
<td class="adm-actions"><form method="post" data-confirm="Выдать новый пароль?"><?= csrf_field() ?><input type="hidden" name="do" value="reset"><input type="hidden" name="uid" value="<?= (int)$r['id'] ?>"><button class="knopka knopka--ramka" type="submit">Сбросить пароль</button></form>
<?php if ((int)$r['id'] !== (int)$ADM['id']): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="toggle"><input type="hidden" name="uid" value="<?= (int)$r['id'] ?>"><button class="knopka knopka--ramka" type="submit"><?= $r['is_active'] ? 'Отключить' : 'Включить' ?></button></form><?php endif; ?></td></tr>
<?php endforeach; ?></table></div>
<h2>Новый администратор</h2>
<form class="adm-card adm-form" method="post">
    <?= csrf_field() ?><input type="hidden" name="do" value="create">
    <div class="adm-grid"><?= fld('name', 'Имя', '', array('req' => 1)) ?><?= fld('email', 'Почта (логин)', '', array('type' => 'email', 'req' => 1)) ?></div>
    <p class="melko">Пароль сгенерируется автоматически; при первом входе администратор обязан заменить его своим.</p>
    <div class="adm-actions"><button class="knopka knopka--derevo" type="submit">Создать</button></div>
</form>
<?php adm_foot();
