<?php
// состояние почты и тестовое письмо: чтобы проверить настройки почты, не дожидаясь настоящей заявки
require_once __DIR__ . '/../includes/admin_ui.php';
$to = $ADM['email'] ?: '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    adm_post_guard();
    $addr = norm_email(post('to'));
    if (!MAIL_ENABLED) adm_back('mail.php', 'err', 'Почта не настроена');
    if (!$addr) adm_back('mail.php', 'err', 'Укажите корректный адрес');
    $err = null;
    $ok = smtp_send($addr, 'СЭРГЭ: проверка почты', "Это тестовое письмо из админ-панели СЭРГЭ.\nЕсли вы его видите, отправка писем работает.", $err);
    audit('mail_test', 'mail', null, $ok ? 'ok' : (string)$err);
    adm_back('mail.php', $ok ? 'ok' : 'err', $ok ? 'Тестовое письмо отправлено на ' . $addr . '. Проверьте входящие и папку «Спам».' : 'Не отправилось: ' . $err);
}
adm_head('Почта', 'mail');
?>
<section class="adm-card">
    <h2>Состояние</h2>
    <?php if (MAIL_ENABLED): ?>
        <dl class="adm-kv"><dt>Отправка писем</dt><dd>включена</dd><dt>SMTP-сервер</dt><dd><?= h(MAIL_HOST) ?>:<?= (int)MAIL_PORT ?> (<?= h(MAIL_SECURE ?: 'без шифрования') ?>)</dd><dt>Отправитель</dt><dd><?= h(MAIL_FROM_NAME) ?> &lt;<?= h(MAIL_FROM) ?>&gt;</dd></dl>
        <form class="adm-form" method="post" style="margin-top:16px"><?= csrf_field() ?>
            <?= fld('to', 'Отправить тестовое письмо на', $to, array('type' => 'email', 'req' => 1)) ?>
            <div class="adm-actions"><button class="knopka knopka--cher" type="submit">Отправить тест</button></div></form>
    <?php else: ?>
        <p>Отправка писем выключена. Чтобы включить, добавьте в <code>includes/config.local.php</code> настройки (образец — в <code>config.local.php.example</code>):</p>
        <pre class="adm-pre">define('MAIL_HOST', 'smtp.yandex.ru');
define('MAIL_PORT', 465);
define('MAIL_SECURE', 'ssl');
define('MAIL_USER', 'ваш.адрес@yandex.ru');
define('MAIL_PASS', 'пароль приложения из 16 букв');</pre>
        <p class="melko">Пароль приложения Яндекса: id.yandex.ru → Безопасность → Пароли приложений → создать пароль для «Почта»; в настройках ящика должен быть разрешён доступ по протоколу IMAP/SMTP. Для Gmail подходят smtp.gmail.com и пароль приложения Google. Основной пароль от аккаунта сюда не вписывайте.</p>
    <?php endif; ?>
</section>
<section class="adm-card">
    <h2>Какие письма уходят</h2>
    <ul>
        <li>Клиенту, который оставил почту в заявке: подтверждение «мы получили вашу заявку» (не чаще 3 писем в сутки на один адрес).</li>
        <li>Менеджеру (на адрес MAIL_NOTIFY, по умолчанию MAIL_USER): уведомление о каждой новой заявке с сайта.</li>
        <li>Клиенту с почтой в заявке: письмо, когда заявка переведена в «В работе» или «Договор заключён».</li>
        <li>Клиенту при подключении платного сопровождения в админке.</li>
        <li>Клиенту при выдаче или сбросе пароля — только если в админке отмечена галочка «отправить письмом».</li>
    </ul>
    <p class="melko">Ошибки отправки не мешают приёму заявок: они записываются в журнал ошибок PHP. SMS пока не подключены (includes/notify.php).</p>
</section>
<?php adm_foot();
