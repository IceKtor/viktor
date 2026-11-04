<?php
// самостоятельная регистрация клиента
// TODO(SMS): подтверждать телефон кодом из SMS. Пока подтверждения нет, поэтому регистрация НЕ привязывает к аккаунту
// ни заявки, ни объекты строительства по номеру телефона - это делает только администратор (иначе можно было бы
// зарегистрироваться на чужой номер и увидеть чужие данные).
require_once __DIR__ . '/includes/account_page.php';
if (current_user()) redirect('cabinet.php');

$err = array(); $v = array('name' => '', 'phone' => '', 'email' => '');
// без настроенной почты подтвердить адрес нельзя, поэтому регистрация закрыта
if (!MAIL_ENABLED) $err['common'] = 'Регистрация временно недоступна: на сайте не настроена отправка писем для подтверждения почты. Позвоните нам или оставьте заявку.';
if (MAIL_ENABLED && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $v['name'] = trim(isset($_POST['name']) ? $_POST['name'] : '');
    $v['phone'] = trim(isset($_POST['phone']) ? $_POST['phone'] : '');
    $v['email'] = trim(isset($_POST['email']) ? $_POST['email'] : '');
    $pass = isset($_POST['password']) ? (string)$_POST['password'] : '';
    $phone = norm_phone($v['phone']);
    $email = norm_email($v['email']);
    $ipk = rl_ip();
    if (mb_strlen($v['name']) < 2 || mb_strlen($v['name']) > 100) $err['name'] = 'Укажите имя: от 2 до 100 символов';
    if (!$phone) $err['phone'] = 'Укажите российский номер телефона, например +7 900 000-00-00';
    if (!$email) $err['email'] = $v['email'] === '' ? 'Укажите почту: на неё придёт письмо для подтверждения' : 'Проверьте адрес почты';
    if (($m = password_problem($pass)) !== null) $err['password'] = $m;
    if (empty($_POST['consent'])) $err['consent'] = 'Нужно согласие на обработку данных';
    if (!$err && rl_count('register', $ipk, 3600) >= 5) $err['common'] = 'Слишком много регистраций с вашего адреса. Попробуйте позже или позвоните нам.';
    if (!$err) {
        if (db_val('SELECT id FROM users WHERE phone = ?', array($phone))) $err['phone'] = 'Этот номер уже зарегистрирован. Войдите или восстановите доступ';
        elseif ($email && db_val('SELECT id FROM users WHERE email = ?', array($email))) $err['email'] = 'Эта почта уже зарегистрирована. Войдите или восстановите доступ';
    }
    if (!$err) {
        rl_hit('register', $ipk);
        $id = user_create('client', $v['name'], $phone, $email, $pass, 'self', 0);
        $sent = email_verify_send($id);
        acc_session(); $_SESSION['verify_email'] = $email; $_SESSION['verify_sent'] = $sent ? 1 : 0;
        redirect('verify.php');
    }
}

$noindex = true; $page_title = 'Регистрация — СЭРГЭ';
$active = '';
require __DIR__ . '/includes/header.php';
?>
<main class="stranica">
    <div class="vnutri vnutri--forma">
        <div class="nadpis nadpis--derevo">ЛИЧНЫЙ КАБИНЕТ</div>
        <h1 class="zag-stranicy">Регистрация</h1>
        <p class="seryj">Аккаунт нужен, чтобы видеть свои заявки и ход строительства.</p>

        <form class="forma forma--uzkaya" method="post" action="register.php" novalidate>
            <?= csrf_field() ?>
            <?= isset($err['common']) ? '<p class="oshibka oshibka--obshaya" role="alert" data-modal="err">' . e($err['common']) . '</p>' : '' ?>
            <div class="pole<?= isset($err['name']) ? ' est-oshibka' : '' ?>">
                <label for="name">Имя</label>
                <input type="text" id="name" name="name" autocomplete="name" value="<?= e($v['name']) ?>" required>
                <?= f_err($err, 'name') ?>
            </div>
            <div class="pole<?= isset($err['phone']) ? ' est-oshibka' : '' ?>">
                <label for="phone">Телефон</label>
                <input type="tel" id="phone" name="phone" autocomplete="tel" inputmode="tel" placeholder="+7 900 000-00-00" value="<?= e($v['phone']) ?>" required>
                <?= f_err($err, 'phone') ?>
            </div>
            <div class="pole<?= isset($err['email']) ? ' est-oshibka' : '' ?>">
                <label for="email">Почта</label>
                <input type="email" id="email" name="email" autocomplete="email" placeholder="name@mail.ru" value="<?= e($v['email']) ?>" required>
                <?= MAIL_ENABLED ? '<p class="melko">Пришлём письмо со ссылкой для подтверждения</p>' : '' ?>
                <?= f_err($err, 'email') ?>
            </div>
            <div class="pole<?= isset($err['password']) ? ' est-oshibka' : '' ?>">
                <label for="password">Пароль</label>
                <input type="password" id="password" name="password" autocomplete="new-password" required>
                <p class="melko">Не короче 8 символов, с буквой и цифрой</p>
                <?= f_err($err, 'password') ?>
            </div>
            <div class="pole pole--galka<?= isset($err['consent']) ? ' est-oshibka' : '' ?>">
                <label class="galka"><input type="checkbox" name="consent" value="1"<?= !empty($_POST['consent']) ? ' checked' : '' ?>> <span>Согласен(на) на <a class="ssylka" href="privacy.php" target="_blank" rel="noopener">обработку персональных данных</a></span></label>
                <?= f_err($err, 'consent') ?>
            </div>
            <div class="knopki knopki--forma">
                <button class="knopka knopka--cher" type="submit"<?= MAIL_ENABLED ? '' : ' disabled' ?>>Создать аккаунт</button>
            </div>
        </form>
        <div class="vspom"><p>Уже есть аккаунт? <a class="ssylka" href="login.php">Войти</a></p></div>
    </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
