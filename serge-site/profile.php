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

$page_title = 'Профиль — СЭРГЭ';
$active = '';
require __DIR__ . '/includes/header.php';
?>
<main class="stranica">
    <div class="vnutri vnutri--forma">
        <div class="nadpis nadpis--derevo">ПРОФИЛЬ</div>
        <h1 class="zag-stranicy"><?= $force ? 'Смените пароль' : 'Мой профиль' ?></h1>
        <?php if ($force): ?><div class="podskazka">Вам выдан временный пароль. Придумайте свой, чтобы продолжить.</div><?php endif; ?>
        <?php if ($ok): ?><div class="uspeh"><?= e($ok) ?></div><?php endif; ?>

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
        <p class="melko"><a class="ssylka" href="<?= $u['role'] === 'admin' ? 'admin/' : 'cabinet.php' ?>">← Назад</a></p>
    </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
