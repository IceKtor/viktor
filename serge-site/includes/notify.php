<?php
// Отправка сообщений клиентам.
//  - почта: работает через SMTP (ваш Gmail и т.п., настройки MAIL_* в includes/config.local.php), без внешних библиотек;
//  - SMS: ЗАГЛУШКА. TODO(SMS): подключить SMS-шлюз (SMS.ru, SMSC, SMS Aero) внутри notify_send('sms', ...).
// Любая ошибка отправки только пишется в журнал сервера и возвращает false: заявки и вход из-за почты не ломаются.
require_once __DIR__ . '/config.php';

// $channel: 'email' | 'sms'; $to - адрес или номер; возвращает true, если сообщение ушло
function notify_send($channel, $to, $text, $subject = 'Сообщение от СЭРГЭ') {
    if ($channel === 'email') {
        if (!MAIL_ENABLED) return false;
        $err = null;
        $ok = smtp_send($to, $subject, $text, $err);
        if (!$ok) error_log('СЭРГЭ почта: не отправлено на ' . $to . ': ' . $err);
        return $ok;
    }
    return false;   // TODO(SMS)
}

// ---------- минимальный SMTP-клиент ----------
function smtp_read($fp, &$code) {
    $all = '';
    while (($ln = fgets($fp, 1024)) !== false) {
        $all .= $ln;
        if (strlen($ln) < 4 || $ln[3] !== '-') break;   // последняя строка ответа: «250 текст» (без дефиса)
    }
    $code = (int)substr($all, 0, 3);
    return $all;
}
function smtp_cmd($fp, $cmd, $expect, &$err) {
    if ($cmd !== null) fwrite($fp, $cmd . "\r\n");
    $r = smtp_read($fp, $code);
    if (!in_array($code, (array)$expect, true)) { $err = trim($r) !== '' ? trim(preg_replace('/\s+/', ' ', $r)) : 'нет ответа сервера'; return false; }
    return true;
}
function smtp_send($to, $subject, $body, &$err) {
    $to = trim($to);
    if (!filter_var($to, FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/', $to . $subject)) { $err = 'неверный адрес'; return false; }
    $remote = (MAIL_SECURE === 'ssl' ? 'ssl://' : 'tcp://') . MAIL_HOST . ':' . (int)MAIL_PORT;
    $fp = @stream_socket_client($remote, $en, $es, 10, STREAM_CLIENT_CONNECT);
    if (!$fp) { $err = 'нет соединения с ' . MAIL_HOST . ' (' . $es . ')'; return false; }
    stream_set_timeout($fp, 10);
    $domain = isset($_SERVER['SERVER_NAME']) ? preg_replace('/[^a-z0-9.-]/i', '', $_SERVER['SERVER_NAME']) : 'localhost';
    $ok = smtp_cmd($fp, null, 220, $err) && smtp_cmd($fp, 'EHLO ' . ($domain ?: 'localhost'), 250, $err);
    if ($ok && MAIL_SECURE === 'tls') {
        $ok = smtp_cmd($fp, 'STARTTLS', 220, $err);
        if ($ok && !@stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) { $err = 'не удалось включить шифрование'; $ok = false; }
        if ($ok) $ok = smtp_cmd($fp, 'EHLO ' . ($domain ?: 'localhost'), 250, $err);
    }
    if ($ok && MAIL_PASS !== '') $ok = smtp_cmd($fp, 'AUTH LOGIN', 334, $err) && smtp_cmd($fp, base64_encode(MAIL_USER), 334, $err) && smtp_cmd($fp, base64_encode(MAIL_PASS), 235, $err);
    $ok = $ok && smtp_cmd($fp, 'MAIL FROM:<' . MAIL_FROM . '>', 250, $err) && smtp_cmd($fp, 'RCPT TO:<' . $to . '>', array(250, 251), $err) && smtp_cmd($fp, 'DATA', 354, $err);
    if ($ok) {
        $h = 'From: =?UTF-8?B?' . base64_encode(MAIL_FROM_NAME) . '?= <' . MAIL_FROM . ">\r\n"
            . 'To: <' . $to . ">\r\n"
            . 'Subject: =?UTF-8?B?' . base64_encode($subject) . "?=\r\n"
            . 'Date: ' . date('r') . "\r\n"
            . 'Message-ID: <' . bin2hex(random_bytes(8)) . '@' . ($domain ?: 'localhost') . ">\r\n"
            . "MIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n";
        fwrite($fp, $h . chunk_split(base64_encode($body), 76, "\r\n") . "\r\n.\r\n");
        $ok = smtp_cmd($fp, null, 250, $err);
    }
    if ($fp) { @fwrite($fp, "QUIT\r\n"); @fclose($fp); }
    return $ok;
}

// ---------- тексты писем ----------
// текст сообщения с выданным доступом
function notify_access_text($name, $login, $password) {
    return 'Здравствуйте, ' . $name . '! Ваш доступ в личный кабинет СЭРГЭ: ' . site_url() . '/login.php' . "\n"
        . 'Логин: ' . $login . "\n" . 'Временный пароль: ' . $password . "\n"
        . 'При первом входе замените пароль на свой.';
}

// подтверждение заявки клиенту (только имя, номер заявки и сводка расчёта; свободный текст клиента в письмо не попадает)
function notify_lead_confirm($email, $name, $leadId, $calcText) {
    $c = site_contacts();
    $t = 'Здравствуйте, ' . $name . "!\n\nМы получили вашу заявку № " . (int)$leadId . ' и скоро перезвоним (' . $c['hours'] . ").\n";
    if ($calcText) $t .= "\nВаш расчёт: " . $calcText . "\nЭто предварительная оценка, а не смета: точную стоимость мы назовём после проектирования и осмотра участка.\n";
    $t .= "\nЕсли захотите что-то уточнить, позвоните нам: " . $c['phone'] . ".\n\nСЭРГЭ — дом, рассчитанный на Север.\n" . $c['address'] . "\n\n"
        . 'Письмо отправлено автоматически, потому что вы оставили заявку на сайте ' . site_url() . '. Если это были не вы, просто проигнорируйте письмо.';
    return notify_send('email', $email, $t, 'СЭРГЭ: мы получили вашу заявку № ' . (int)$leadId);
}

// уведомление менеджеру о новой заявке (на MAIL_NOTIFY)
function notify_lead_manager($leadId, $name, $phone, $message, $calcText) {
    if (!MAIL_NOTIFY) return false;
    $t = 'Новая заявка № ' . (int)$leadId . " с сайта.

Имя: " . $name . "
Телефон: " . $phone . "
";
    if ($message) $t .= 'Сообщение: ' . $message . "
";
    if ($calcText) $t .= 'Расчёт: ' . $calcText . "
";
    $t .= "
Открыть: " . site_url() . '/admin/lead.php?id=' . (int)$leadId . "
";
    return notify_send('email', MAIL_NOTIFY, $t, 'СЭРГЭ: новая заявка № ' . (int)$leadId);
}

// клиенту: заявка взята в работу или заключён договор (для других статусов письмо не нужно)
function notify_lead_status($email, $name, $leadId, $status) {
    $text = array('in_work' => 'взята в работу, менеджер свяжется с вами', 'deal' => 'закрыта заключением договора, спасибо, что выбрали СЭРГЭ');
    if (!isset($text[$status])) return false;
    $c = site_contacts();
    $t = 'Здравствуйте, ' . $name . "!

Ваша заявка № " . (int)$leadId . ' ' . $text[$status] . ".
Вопросы можно задать по телефону " . $c['phone'] . ".

СЭРГЭ";
    return notify_send('email', $email, $t, 'СЭРГЭ: заявка № ' . (int)$leadId . ' – статус изменён');
}

// клиенту: подключён VIP-тариф
function notify_vip($email, $name) {
    $t = 'Здравствуйте, ' . $name . "!

Вам подключён VIP-тариф СЭРГЭ: фотоотчёты по объекту, подробная смета, сравнение расчётов, приоритетная заявка и личный менеджер. Что входит в тариф: " . site_url() . "/tarify.php

СЭРГЭ";
    return notify_send('email', $email, $t, 'СЭРГЭ: подключён VIP-тариф');
}
