<?php
// профиль: имя, почта, смена пароля (для клиента и администратора)
require_once __DIR__ . '/includes/account_page.php';
$u = current_user();
if (!$u) redirect('login.php');
$force = (bool)$u['must_change_password'];

$err = array(); $ok = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $do = isset($_POST['do']) ? $_POST['do'] : '';
    if ($do === 'password') {
        $old = isset($_POST['old']) ? (string)$_POST['old'] : '';
        $new = isset($_POST['new']) ? (string)$_POST['new'] : '';
        if (!password_verify($old, $u['password_hash'])) $err['old'] = 'Текущий пароль указан неверно';
        elseif (($m = password_problem($new)) !== null) $err['new'] = $m;
        elseif ($new === $old) $err['new'] = 'Новый пароль должен отличаться от текущего';
        if (!$err) {
            db_exec('UPDATE users SET password_hash = ?, must_change_password = 0 WHERE id = ?', array(password_hash($new, PASSWORD_DEFAULT), $u['id']));
            session_regenerate_id(true);
            flash_set('ok', 'Пароль изменён');
            redirect($u['role'] === 'admin' ? 'admin/' : 'cabinet.php');
        }
    } elseif ($do === 'delete' && !$force && $u['role'] === 'client') {
        if (!password_verify(isset($_POST['pass']) ? (string)$_POST['pass'] : '', $u['password_hash'])) $err['del'] = 'Пароль указан неверно';
        elseif (empty($_POST['sure'])) $err['del'] = 'Отметьте, что понимаете последствия';
        if (!$err) {
            audit('account_delete', 'user', $u['id']);
            db_exec('DELETE FROM users WHERE id = ?', array($u['id']));   // объекты и новости удаляются каскадом, заявки остаются без привязки
            auth_logout();
            acc_session(); flash_set('ok', 'Аккаунт удалён');
            redirect('login.php');
        }
    } elseif ($do === 'data' && !$force) {
        $name = trim(isset($_POST['name']) ? $_POST['name'] : '');
        $email = trim(isset($_POST['email']) ? $_POST['email'] : '');
        $mail = $email === '' ? null : norm_email($email);
        if (mb_strlen($name) < 2 || mb_strlen($name) > 100) $err['name'] = 'Укажите имя: от 2 до 100 символов';
        if ($email !== '' && !$mail) $err['email'] = 'Проверьте адрес почты';
        if (!$err && $mail && db_val('SELECT id FROM users WHERE email = ? AND id <> ?', array($mail, $u['id']))) $err['email'] = 'Эта почта уже используется';
        if (!$err) { db_exec('UPDATE users SET name = ?, email = ? WHERE id = ?', array($name, $mail, $u['id'])); $ok = 'Данные сохранены'; $u['name'] = $name; if ($mail && $mail !== $u['email'] && MAIL_ENABLED) { $ok .= email_verify_send($u['id']) ? '. На новую почту отправлено письмо: подтвердите её, иначе при следующем входе понадобится подтверждение' : '. Письмо для подтверждения отправить не удалось'; } $u['email'] = $mail; }
    }
}

$noindex = true; $page_title = 'Профиль — СЭРГЭ';
$active = '';
require __DIR__ . '/includes/header.php';
?>
<main class="stranica">
    <div class="vnutri vnutri--forma">
        <h1 class="zag-stranicy"><?= $force ? 'Смените пароль' : 'Мой профиль' ?></h1>
        <?php if ($force): ?><div class="podskazka">Вам выдан временный пароль. Придумайте свой, чтобы продолжить.</div><?php endif; ?>
        <?php if ($ok): ?><div class="uspeh" data-modal="ok"><?= e($ok) ?></div><?php endif; ?>

        <?php if (!$force): ?>
        <form class="forma forma--uzkaya" method="post" action="profile.php" novalidate>
            <?= csrf_field() ?><input type="hidden" name="do" value="data">
            <h2 class="h3">Данные</h2>
            <div class="pole<?= isset($err['name']) ? ' est-oshibka' : '' ?>"><label for="name">Имя</label><input type="text" id="name" name="name" value="<?= e($u['name']) ?>" required><?= f_err($err, 'name') ?></div>
            <div class="pole"><label>Телефон</label><input type="text" value="<?= e($u['phone'] ? fmt_phone($u['phone']) : '—') ?>" disabled><p class="melko">Номер меняется через менеджера: он связан с договором и заявками.</p></div>
            <div class="pole<?= isset($err['email']) ? ' est-oshibka' : '' ?>"><label for="email">Почта</label><input type="email" id="email" name="email" value="<?= e($u['email']) ?>"><?= f_err($err, 'email') ?></div>
            <div class="knopki knopki--forma"><button class="knopka knopka--cher" type="submit">Сохранить</button></div>
        </form>
        <?php endif; ?>

        <form class="forma forma--uzkaya" method="post" action="profile.php" novalidate>
            <?= csrf_field() ?><input type="hidden" name="do" value="password">
            <h2 class="h3">Смена пароля</h2>
            <div class="pole<?= isset($err['old']) ? ' est-oshibka' : '' ?>"><label for="old"><?= $force ? 'Выданный пароль' : 'Текущий пароль' ?></label><input type="password" id="old" name="old" autocomplete="current-password" required><?= f_err($err, 'old') ?></div>
            <div class="pole<?= isset($err['new']) ? ' est-oshibka' : '' ?>"><label for="new">Новый пароль</label><input type="password" id="new" name="new" autocomplete="new-password" required><p class="melko">Не короче 8 символов, с буквой и цифрой</p><?= f_err($err, 'new') ?></div>
            <div class="knopki knopki--forma"><button class="knopka knopka--cher" type="submit">Сменить пароль</button></div>
        </form>
        <?php if (!$force && $u['role'] === 'client'): ?>
        <form class="forma forma--uzkaya" method="post" action="profile.php" novalidate data-confirm="Удалить аккаунт? Это нельзя отменить.">
            <?= csrf_field() ?><input type="hidden" name="do" value="delete">
            <h2 class="h3">Удаление аккаунта</h2>
            <p class="melko">Будут удалены ваш аккаунт, объекты строительства и новости по ним. Заявки останутся у компании без привязки к аккаунту и хранятся по правилам <a class="ssylka" href="privacy.php">политики конфиденциальности</a>.</p>
            <div class="pole<?= isset($err['del']) ? ' est-oshibka' : '' ?>"><label for="pass">Пароль для подтверждения</label><input type="password" id="pass" name="pass" autocomplete="current-password" required><?= f_err($err, 'del') ?></div>
            <div class="pole pole--galka"><label class="galka"><input type="checkbox" name="sure" value="1"> <span>Понимаю, что это нельзя отменить</span></label></div>
            <div class="knopki knopki--forma"><button class="knopka knopka--ramka" type="submit">Удалить аккаунт</button></div>
        </form>
        <?php endif; ?>
        <p class="melko"><a class="ssylka" href="<?= $u['role'] === 'admin' ? 'admin/' : 'cabinet.php' ?>">← Назад</a></p>
    </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
