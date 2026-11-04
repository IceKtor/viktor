<?php
// вход в личный кабинет и в админ-панель (одна форма для обеих ролей)
require_once __DIR__ . '/includes/account_page.php';

$u = current_user();
if ($u) redirect($u['role'] === 'admin' ? 'admin/' : 'cabinet.php');

$error = null; $login = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $login = isset($_POST['login']) ? trim($_POST['login']) : '';
    list($row, $error) = auth_login($login, isset($_POST['password']) ? $_POST['password'] : '');
    if ($row) {
        if ($row['must_change_password']) redirect('profile.php?force=1');
        redirect($row['role'] === 'admin' ? 'admin/' : 'cabinet.php');
    }
}

$noindex = true; $page_title = 'Вход — СЭРГЭ';
$page_desc = 'Вход в личный кабинет клиента СЭРГЭ.';
$active = '';
require __DIR__ . '/includes/header.php';
?>
<main class="stranica">
    <div class="vnutri vnutri--forma">
        <div class="nadpis nadpis--derevo">ЛИЧНЫЙ КАБИНЕТ</div>
        <h1 class="zag-stranicy">Вход</h1>
        <p class="seryj">Следите за ходом строительства вашего дома и статусом заявок.</p>

        <?php foreach (flash_get() as $f): ?><div class="<?= $f[0] === 'ok' ? 'uspeh' : 'podskazka' ?>" data-modal="<?= $f[0] === 'ok' ? 'ok' : ($f[0] === 'err' ? 'err' : 'info') ?>"><?= e($f[1]) ?></div><?php endforeach; ?>

        <form class="forma forma--uzkaya" method="post" action="login.php" novalidate>
            <?= csrf_field() ?>
            <?php if ($error): ?><p class="oshibka oshibka--obshaya" role="alert" data-modal="err"><?= e($error) ?><?= $error === UNVERIFIED_MSG ? ' <a class="ssylka" href="verify.php">Отправить письмо ещё раз</a>' : '' ?></p><?php endif; ?>
            <div class="pole">
                <label for="login">Телефон или e-mail</label>
                <input type="text" id="login" name="login" autocomplete="username" inputmode="email" placeholder="+7 900 000-00-00" value="<?= e($login) ?>" required autofocus>
            </div>
            <div class="pole">
                <label for="password">Пароль</label>
                <input type="password" id="password" name="password" autocomplete="current-password" required>
            </div>
            <div class="knopki knopki--forma">
                <button class="knopka knopka--cher" type="submit">Войти</button>
            </div>
            <p class="melko"><a class="ssylka" href="zayavka.php?topic=reset">Забыли пароль?</a></p>
        </form>

        <div class="vspom">
            <p>Нет аккаунта? <a class="ssylka" href="register.php">Зарегистрироваться</a></p>
            <p class="melko">Если вы уже заключили договор со СЭРГЭ, доступ в кабинет вам выдаст менеджер: логин — номер телефона из договора, пароль выдаётся при передаче и меняется при первом входе.</p>
        </div>
    </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
