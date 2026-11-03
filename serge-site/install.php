<?php
// Первая установка: создаёт первого администратора. Работает один раз и только по ключу SETUP_KEY из includes/config.local.php.
require_once __DIR__ . '/includes/account_page.php';
header('X-Robots-Tag: noindex');
$have = (int)db_val("SELECT COUNT(*) FROM users WHERE role = 'admin'");
$err = array(); $v = array('name' => '', 'email' => '');
$closed = ($have > 0) || SETUP_KEY === '';
if (!$closed && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $key = isset($_POST['key']) ? (string)$_POST['key'] : '';
    $v['name'] = trim(isset($_POST['name']) ? $_POST['name'] : '');
    $v['email'] = trim(isset($_POST['email']) ? $_POST['email'] : '');
    $mail = norm_email($v['email']); $pass = isset($_POST['password']) ? (string)$_POST['password'] : '';
    if (!hash_equals(SETUP_KEY, $key)) { $err['key'] = 'Неверный ключ установки'; rl_hit('login', rl_ip()); }
    if (rl_count('login', rl_ip(), 900) >= 20) $err['key'] = 'Слишком много попыток. Подождите 15 минут.';
    if (mb_strlen($v['name']) < 2) $err['name'] = 'Укажите имя';
    if (!$mail) $err['email'] = 'Укажите почту: она будет логином администратора';
    if (($m = password_problem($pass)) !== null) $err['password'] = $m;
    if (!$err) {
        $id = user_create('admin', $v['name'], null, $mail, $pass, 'install', 0);
        session_regenerate_id(true); acc_session(); $_SESSION['uid'] = $id;
        audit('install', 'user', $id, 'создан первый администратор');
        flash_set('ok', 'Администратор создан. Теперь можно стереть SETUP_KEY из config.local.php.');
        redirect('admin/');
    }
}
$page_title = 'Установка — СЭРГЭ'; $active = '';
require __DIR__ . '/includes/header.php';
?>
<main class="stranica"><div class="vnutri vnutri--forma">
    <div class="nadpis nadpis--derevo">УСТАНОВКА</div>
    <h1 class="zag-stranicy">Первый администратор</h1>
    <?php if ($have > 0): ?>
        <div class="podskazka">Администратор уже создан, установка закрыта. <a class="ssylka" href="login.php">Войти</a></div>
    <?php elseif (SETUP_KEY === ''): ?>
        <div class="podskazka">Страница закрыта. Откройте includes/config.local.php, добавьте строку <code>define('SETUP_KEY', 'ваш-длинный-ключ');</code> и обновите страницу.</div>
    <?php else: ?>
        <form class="forma forma--uzkaya" method="post" action="install.php" novalidate>
            <?= csrf_field() ?>
            <div class="pole<?= isset($err['key']) ? ' est-oshibka' : '' ?>"><label for="key">Ключ установки (SETUP_KEY)</label><input type="password" id="key" name="key" required><?= f_err($err, 'key') ?></div>
            <div class="pole<?= isset($err['name']) ? ' est-oshibka' : '' ?>"><label for="name">Имя</label><input type="text" id="name" name="name" value="<?= e($v['name']) ?>" required><?= f_err($err, 'name') ?></div>
            <div class="pole<?= isset($err['email']) ? ' est-oshibka' : '' ?>"><label for="email">Почта (логин)</label><input type="email" id="email" name="email" value="<?= e($v['email']) ?>" required><?= f_err($err, 'email') ?></div>
            <div class="pole<?= isset($err['password']) ? ' est-oshibka' : '' ?>"><label for="password">Пароль</label><input type="password" id="password" name="password" autocomplete="new-password" required><p class="melko">Не короче 8 символов, с буквой и цифрой</p><?= f_err($err, 'password') ?></div>
            <div class="knopki knopki--forma"><button class="knopka knopka--cher" type="submit">Создать администратора</button></div>
        </form>
    <?php endif; ?>
</div></main>
<?php require __DIR__ . '/includes/footer.php'; ?>
