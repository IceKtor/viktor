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
        $bd = 'b' . bin2hex(random_bytes(8));
        list($plain, $html) = mail_wrap($body);
        $b64 = function ($t) { return chunk_split(base64_encode($t), 76, "\r\n"); };
        $h = 'From: =?UTF-8?B?' . base64_encode(MAIL_FROM_NAME) . '?= <' . MAIL_FROM . ">\r\n"
            . 'To: <' . $to . ">\r\n"
            . 'Subject: =?UTF-8?B?' . base64_encode($subject) . "?=\r\n"
            . 'Date: ' . date('r') . "\r\n"
            . 'Message-ID: <' . bin2hex(random_bytes(8)) . '@' . substr(strrchr(MAIL_FROM, '@'), 1) . ">\r\n"
            . "Auto-Submitted: auto-generated\r\n"
            . "MIME-Version: 1.0\r\nContent-Type: multipart/alternative; boundary=\"$bd\"\r\n\r\n";
        $msg = "--$bd\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . $b64($plain)
            . "--$bd\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . $b64($html)
            . "--$bd--\r\n";
        fwrite($fp, $h . $msg . ".\r\n");
        $ok = smtp_cmd($fp, null, 250, $err);
    }
    if ($fp) { @fwrite($fp, "QUIT\r\n"); @fclose($fp); }
    return $ok;
}

// адрес сайта для писем. Яндекс отклоняет письма, где есть ссылка с http:// на нерабочий домен вроде serge.local,
// поэтому для локальных адресов схема убирается (ссылку можно вставить в браузер); на настоящем домене остаётся https://...
function mail_site() {
    $u = site_url();
    $host = parse_url($u, PHP_URL_HOST);
    if ($host && !preg_match('/\.[a-z]{2,}$/i', $host) || preg_match('/\.(local|localhost|test|lan)$/i', (string)$host)) return preg_replace('~^https?://~i', '', $u);
    return $u;
}

// ---------- оформление писем ----------
// из текста письма делает два варианта: обычный (с подписью компании) и HTML (шапка, текст, реквизиты)
function mail_wrap($body) {
    $c = function_exists('site_contacts') ? site_contacts() : array('phone' => '', 'address' => '');
    $co = function_exists('repo_company') ? repo_company() : array();
    $req = isset($co['contacts']['requisites']) ? $co['contacts']['requisites'] : '';
    $sig = array_filter(array('СЭРГЭ — дом, рассчитанный на Север', $req, trim($c['address'] . ', ' . $c['phone'], ', '), 'Письмо отправлено автоматически, отвечать на него не нужно.'), 'strlen');
    $plain = $body . "\n\n-- \n" . implode("\n", $sig) . "\n";
    $esc = function ($t) { return htmlspecialchars($t, ENT_QUOTES, 'UTF-8'); };
    $text = preg_replace('~(https?://[^\s<]+)~u', '<a href="$1" style="color:#7a5a3c">$1</a>', nl2br($esc($body)));
    $foot = implode('<br>', array_map($esc, $sig));
    $html = '<!DOCTYPE html><html lang="ru"><body style="margin:0;padding:24px 12px;background:#f2f0eb;font-family:Arial,Helvetica,sans-serif;color:#111">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden">'
        . '<tr><td style="background:#111;color:#fff;padding:22px 28px;font-size:22px;letter-spacing:3px;font-weight:bold">СЭРГЭ<div style="font-size:11px;letter-spacing:2px;font-weight:normal;color:#bbb;margin-top:4px">СТРОИТЕЛЬСТВО ДОМОВ</div></td></tr>'
        . '<tr><td style="padding:28px;font-size:15px;line-height:1.6">' . $text . '</td></tr>'
        . '<tr><td style="padding:18px 28px;background:#faf9f6;border-top:1px solid #e5e1d8;font-size:12px;line-height:1.6;color:#777">' . $foot . '</td></tr>'
        . '</table></body></html>';
    return array($plain, $html);
}

// ---------- тексты писем ----------
// текст сообщения с выданным доступом
function notify_access_text($name, $login, $password) {
    return 'Здравствуйте, ' . $name . '! Ваш доступ в личный кабинет СЭРГЭ: ' . mail_site() . '/login.php' . "\n"
        . 'Логин: ' . $login . "\n" . 'Временный пароль: ' . $password . "\n"
        . 'При первом входе замените пароль на свой.';
}

// подтверждение заявки клиенту (только имя, номер заявки и сводка расчёта; свободный текст клиента в письмо не попадает)
function notify_lead_confirm($email, $name, $leadId, $calcText) {
    $c = site_contacts();
    $t = 'Здравствуйте, ' . $name . "!\n\nМы получили вашу заявку № " . (int)$leadId . ' и скоро перезвоним (' . $c['hours'] . ").\n";
    if ($calcText) $t .= "\nВаш расчёт: " . $calcText . "\nЭто предварительная оценка, а не смета: точную стоимость мы назовём после проектирования и осмотра участка.\n";
    $t .= "\nЕсли захотите что-то уточнить, позвоните нам: " . $c['phone'] . ".\n\n"
        . 'Вы получили это письмо, потому что оставили заявку на сайте ' . mail_site() . '. Если это были не вы, просто проигнорируйте письмо.';
    return notify_send('email', $email, $t, 'СЭРГЭ: мы получили вашу заявку № ' . (int)$leadId);
}

// уведомление менеджеру о новой заявке (на MAIL_NOTIFY)
function notify_lead_manager($leadId, $name, $phone, $message, $calcText) {
    if (!MAIL_NOTIFY) return false;
    $t = 'Новая заявка № ' . (int)$leadId . " с сайта.\n\nИмя: " . $name . "\nТелефон: " . $phone . "\n";
    if ($message) $t .= 'Сообщение: ' . $message . "\n";
    if ($calcText) $t .= 'Расчёт: ' . $calcText . "\n";
    $t .= "\nОткрыть заявку: " . mail_site() . '/admin/lead.php?id=' . (int)$leadId;
    return notify_send('email', MAIL_NOTIFY, $t, 'СЭРГЭ: новая заявка № ' . (int)$leadId);
}

// клиенту: заявка взята в работу или заключён договор (для других статусов письмо не нужно)
function notify_lead_status($email, $name, $leadId, $status) {
    $text = array('in_work' => 'взята в работу, менеджер свяжется с вами', 'deal' => 'закрыта заключением договора, спасибо, что выбрали СЭРГЭ');
    if (!isset($text[$status])) return false;
    $c = site_contacts();
    $t = 'Здравствуйте, ' . $name . "!\n\nВаша заявка № " . (int)$leadId . ' ' . $text[$status] . ".\nВопросы можно задать по телефону " . $c['phone'] . '.';
    return notify_send('email', $email, $t, 'СЭРГЭ: заявка № ' . (int)$leadId . ' – статус изменён');
}

// клиенту: подключено платное сопровождение
function notify_vip($email, $name) {
    $t = 'Здравствуйте, ' . $name . "!\n\nВам подключено сопровождение СЭРГЭ: проверка сметы, технадзор на этапах строительства, расчёт фундамента и личный менеджер. Состав и условия: " . mail_site() . '/tarify.php';
    return notify_send('email', $email, $t, 'СЭРГЭ: подключено сопровождение');
}

// клиенту: ссылка для подтверждения почты при регистрации
function notify_verify_email($email, $name, $link) {
    $t = 'Здравствуйте, ' . $name . "!\n\nЧтобы завершить регистрацию в СЭРГЭ, подтвердите почту. Откройте ссылку (она действует 24 часа):\n" . $link . "\n\nЕсли вы не регистрировались, просто проигнорируйте это письмо.";
    return notify_send('email', $email, $t, 'СЭРГЭ: подтвердите почту');
}
