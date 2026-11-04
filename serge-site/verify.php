<?php
// подтверждение почты: переход по ссылке из письма и повторная отправка письма
require_once __DIR__ . '/includes/account_page.php';

$state = null; $msg = null;   // state: ok | bad | sent | wait
acc_session();
$wait_email = isset($_SESSION['verify_email']) ? $_SESSION['verify_email'] : null;   // адрес из только что пройденной регистрации
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $email = norm_email(isset($_POST['email']) ? $_POST['email'] : ($wait_email ?: ''));
    $ipk = rl_ip();
    if (!$email) {
        $msg = 'Проверьте адрес почты';
    } elseif (rl_count('verify', $ipk, 3600) >= 10) {
        $msg = 'Слишком много запросов. Попробуйте позже.';
    } else {
        rl_hit('verify', $ipk);
        $uid = db_val('SELECT id FROM users WHERE email = ? AND role = \'client\' AND email_verified_at IS NULL AND is_active = 1', array($email));
        // не чаще 3 писем в сутки на адрес; ответ один и тот же, чтобы страница не выдавала, есть ли такой адрес
        if ($uid && rl_count('mail', rl_key('vf|' . $email), 86400) < 3) {
            if (email_verify_send((int)$uid)) rl_hit('mail', rl_key('vf|' . $email));   // в лимит идут только отправленные письма
        }
        $state = $wait_email ? 'wait' : 'sent';
        if ($wait_email) $_SESSION['verify_sent'] = 1;
    }
} elseif (!empty($_GET['t']) && is_string($_GET['t'])) {
    $uid = db_val('SELECT id FROM users WHERE email_token = ? AND email_token_expires > NOW() AND email_verified_at IS NULL', array(hash('sha256', $_GET['t'])));
    if ($uid) {
        db_exec('UPDATE users SET email_verified_at = NOW(), email_token = NULL, email_token_expires = NULL WHERE id = ?', array($uid));
        $state = 'ok';
        unset($_SESSION['verify_email'], $_SESSION['verify_sent']);
    } else {
        $state = 'bad';
    }
}

if ($state === null && $wait_email) $state = 'wait';
$page_title = 'Подтверждение почты — СЭРГЭ';
$active = '';
require __DIR__ . '/includes/header.php';
?>
<main class="stranica">
    <div class="vnutri vnutri--forma">
        <div class="nadpis nadpis--derevo">ЛИЧНЫЙ КАБИНЕТ</div>
        <h1 class="zag-stranicy">Подтверждение почты</h1>
        <?php foreach (flash_get() as $f): ?><div class="<?= $f[0] === 'ok' ? 'uspeh' : 'podskazka' ?>" data-modal="<?= $f[0] === 'ok' ? 'ok' : ($f[0] === 'err' ? 'err' : 'info') ?>"><?= e($f[1]) ?></div><?php endforeach; ?>

        <?php if ($state === 'ok'): ?>
            <div class="uspeh" data-modal="ok">Почта подтверждена. Теперь можно войти в личный кабинет.</div>
            <div class="knopki"><a class="knopka knopka--cher" href="login.php">Войти →</a></div>
        <?php elseif ($state === 'wait'): ?>
            <p class="seryj">Последний шаг регистрации: подтвердите почту. <?= !empty($_SESSION['verify_sent']) ? 'Мы отправили письмо со ссылкой на <strong>' . e($wait_email) . '</strong>.' : 'Не удалось отправить письмо на <strong>' . e($wait_email) . '</strong>.' ?> Откройте ссылку в письме (проверьте и папку «Спам»), после этого можно войти.</p>
            <form class="forma forma--uzkaya" method="post" action="verify.php" novalidate>
                <?= csrf_field() ?>
                <div class="knopki knopki--forma">
                    <button class="knopka knopka--cher" type="submit">Отправить письмо ещё раз</button>
                    <a class="knopka knopka--ramka" href="login.php">Войти</a>
                </div>
            </form>
        <?php else: ?>
            <?php if ($state === 'bad'): ?><p class="oshibka oshibka--obshaya" role="alert" data-modal="err">Ссылка недействительна или устарела. Запросите новое письмо.</p><?php endif; ?>
            <?php if ($state === 'sent'): ?><div class="uspeh" data-modal="ok">Если такой адрес ждёт подтверждения, мы отправили на него письмо. Проверьте и папку «Спам».</div><?php endif; ?>
            <p class="seryj">Не пришло письмо со ссылкой? Укажите почту, которую вы вводили при регистрации.</p>
            <form class="forma forma--uzkaya" method="post" action="verify.php" novalidate>
                <?= csrf_field() ?>
                <?= $msg ? '<p class="oshibka oshibka--obshaya" role="alert" data-modal="err">' . e($msg) . '</p>' : '' ?>
                <div class="pole">
                    <label for="email">Почта</label>
                    <input type="email" id="email" name="email" autocomplete="email" placeholder="name@mail.ru" required>
                </div>
                <div class="knopki knopki--forma">
                    <button class="knopka knopka--cher" type="submit">Отправить письмо ещё раз</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
