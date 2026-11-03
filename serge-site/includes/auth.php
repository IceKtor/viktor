<?php
// Сессии, вход, проверка прав, защита форм (CSRF), ограничение частоты, загрузка картинок.
// Работает только при USE_DB = true (пользователи и заявки лежат в MySQL, см. database/schema.sql).
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// ---------- сессия ----------
function acc_session() {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_name('serge_sid');
    session_set_cookie_params(array('lifetime' => 0, 'path' => '/', 'secure' => $https, 'httponly' => true, 'samesite' => 'Lax'));
    session_start();
}

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

function flash_set($type, $text) { acc_session(); $_SESSION['flash'][] = array($type, $text); }
function flash_get() { acc_session(); $f = isset($_SESSION['flash']) ? $_SESSION['flash'] : array(); unset($_SESSION['flash']); return $f; }

// ---------- CSRF: у каждой формы скрытое поле с токеном сессии ----------
function csrf_token() { acc_session(); if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(24)); return $_SESSION['csrf']; }
function csrf_field() { return '<input type="hidden" name="csrf" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">'; }
function csrf_ok() {
    acc_session();
    $t = isset($_POST['csrf']) ? (string)$_POST['csrf'] : '';
    return $t !== '' && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $t);
}
// для POST-обработчиков: при неверном токене прекращаем работу
function csrf_require() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_ok()) {
        http_response_code(400);
        exit('Форма устарела. Вернитесь назад, обновите страницу и повторите.');
    }
}

// ---------- телефон ----------
// "8 (999) 123-45-67", "+7 999 123 45 67", "9991234567" -> "79991234567"; null, если это не российский номер
function norm_phone($s) {
    $d = preg_replace('/\D+/', '', (string)$s);
    if (strlen($d) === 10) $d = '7' . $d;
    if (strlen($d) === 11 && $d[0] === '8') $d = '7' . substr($d, 1);
    return (strlen($d) === 11 && $d[0] === '7') ? $d : null;
}
function fmt_phone($d) {
    $d = (string)$d;
    if (strlen($d) !== 11) return $d;
    return '+7 (' . substr($d, 1, 3) . ') ' . substr($d, 4, 3) . '-' . substr($d, 7, 2) . '-' . substr($d, 9, 2);
}
function norm_email($s) {
    $s = mb_strtolower(trim((string)$s));
    return ($s !== '' && filter_var($s, FILTER_VALIDATE_EMAIL) && strlen($s) <= 120) ? $s : null;
}

// ---------- пароли ----------
function password_problem($p) {
    if (mb_strlen($p) < 8) return 'Пароль не короче 8 символов';
    if (!preg_match('/\p{L}/u', $p) || !preg_match('/\d/', $p)) return 'В пароле нужны буква и цифра';
    if (mb_strlen($p) > 72) return 'Пароль не длиннее 72 символов';
    return null;
}
// выдаваемый пароль: без похожих символов (0/O, 1/l/I), удобно продиктовать по телефону
function gen_password($len = 10) {
    $a = 'abcdefghjkmnpqrstuvwxyz'; $b = 'ABCDEFGHJKMNPQRSTUVWXYZ'; $c = '23456789';
    $all = $a . $b . $c; $p = $a[random_int(0, strlen($a) - 1)] . $b[random_int(0, strlen($b) - 1)] . $c[random_int(0, strlen($c) - 1)];
    while (strlen($p) < $len) $p .= $all[random_int(0, strlen($all) - 1)];
    return str_shuffle($p);
}

// ---------- ограничение частоты (против подбора паролей и спама заявок) ----------
function rl_key($s) { return hash('sha256', 'serge|' . $s); }
function rl_ip() { return rl_key('ip|' . (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '')); }
function rl_count($bucket, $key, $window) { return (int)db_val('SELECT COUNT(*) FROM rate_limits WHERE bucket = ? AND k = ? AND created_at > ?', array($bucket, $key, time() - $window)); }
function rl_hit($bucket, $key) {
    db_exec('INSERT INTO rate_limits (bucket, k, created_at) VALUES (?, ?, ?)', array($bucket, $key, time()));
    if (random_int(1, 50) === 1) db_exec('DELETE FROM rate_limits WHERE created_at < ?', array(time() - 86400));
}
function rl_clear($bucket, $key) { db_exec('DELETE FROM rate_limits WHERE bucket = ? AND k = ?', array($bucket, $key)); }

// ---------- текущий пользователь ----------
function current_user() {
    static $u = false;
    if ($u !== false) return $u;
    $u = null;
    if (!isset($_COOKIE['serge_sid'])) return null;   // посетителям без входа сессию (и cookie) не создаём
    acc_session();
    if (!empty($_SESSION['uid'])) {
        $row = db_row('SELECT * FROM users WHERE id = ? AND is_active = 1', array((int)$_SESSION['uid']));
        if ($row) $u = $row; else { $_SESSION = array(); }
    }
    return $u;
}
function is_admin() { $u = current_user(); return $u && $u['role'] === 'admin'; }

function require_login() {
    $u = current_user();
    if (!$u) redirect('login.php');
    // выданный администратором пароль нужно сменить при первом входе
    if ($u['must_change_password'] && basename($_SERVER['SCRIPT_NAME']) !== 'profile.php' && basename($_SERVER['SCRIPT_NAME']) !== 'logout.php') redirect(site_root_rel() . 'profile.php?force=1');
    return $u;
}
function require_admin() {
    $u = current_user();
    if (!$u) redirect(site_root_rel() . 'login.php');
    if ($u['role'] !== 'admin') { http_response_code(403); exit('Нет доступа'); }
    if ($u['must_change_password']) redirect(site_root_rel() . 'profile.php?force=1');
    header('X-Robots-Tag: noindex');
    return $u;
}
// относительный путь к корню сайта из текущей страницы: '' на обычных страницах, '../' в admin/
function site_root_rel() { return (basename(dirname($_SERVER['SCRIPT_NAME'])) === 'admin') ? '../' : ''; }

// ---------- вход ----------
// возвращает array(пользователь|null, текст ошибки|null)
function auth_login($login, $password) {
    $login = trim((string)$login);
    $isMail = strpos($login, '@') !== false;
    $id = $isMail ? norm_email($login) : norm_phone($login);
    $kLogin = rl_key('login|' . ($id !== null ? $id : $login));
    $kIp = rl_ip();
    if (rl_count('login', $kLogin, 900) >= 5 || rl_count('login', $kIp, 900) >= 20) {
        return array(null, 'Слишком много неудачных попыток. Подождите 15 минут или позвоните нам.');
    }
    $row = null;
    if ($id !== null) $row = db_row('SELECT * FROM users WHERE ' . ($isMail ? 'email' : 'phone') . ' = ? AND is_active = 1', array($id));
    // проверяем пароль и для несуществующего пользователя, чтобы время ответа не выдавало, есть ли такой логин
    static $dummy = null;
    if ($dummy === null) $dummy = password_hash('serge-dummy', PASSWORD_DEFAULT);
    $hash = $row ? $row['password_hash'] : $dummy;
    $ok = password_verify((string)$password, $hash);
    if (!$row || !$ok) {
        rl_hit('login', $kLogin); rl_hit('login', $kIp);
        return array(null, 'Неверный телефон, почта или пароль');
    }
    rl_clear('login', $kLogin);
    session_regenerate_id(true);
    $_SESSION['uid'] = (int)$row['id'];
    db_exec('UPDATE users SET last_login_at = NOW() WHERE id = ?', array($row['id']));
    if (password_needs_rehash($row['password_hash'], PASSWORD_DEFAULT)) db_exec('UPDATE users SET password_hash = ? WHERE id = ?', array(password_hash($password, PASSWORD_DEFAULT), $row['id']));
    return array($row, null);
}
function auth_logout() {
    acc_session();
    $_SESSION = array();
    if (ini_get('session.use_cookies')) { $p = session_get_cookie_params(); setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']); }
    session_destroy();
}

// создаёт пользователя; возвращает id. $mustChange = 1, если пароль выдан администратором
function user_create($role, $name, $phone, $email, $password, $via, $mustChange) {
    return db_insert('INSERT INTO users (role, name, phone, email, password_hash, must_change_password, created_via, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())',
        array($role, $name, $phone, $email, password_hash($password, PASSWORD_DEFAULT), $mustChange ? 1 : 0, $via));
}

function audit($action, $entity = null, $entityId = null, $details = null) {
    $u = current_user();
    try { db_exec('INSERT INTO audit_log (user_id, action, entity, entity_id, details, created_at) VALUES (?, ?, ?, ?, ?, NOW())', array($u ? $u['id'] : null, $action, $entity, $entityId === null ? null : (string)$entityId, $details === null ? null : mb_substr($details, 0, 500))); }
    catch (Exception $e) { error_log('СЭРГЭ audit: ' . $e->getMessage()); }
}

// ---------- загрузка картинок ----------
// возвращает array(путь|null, ошибка|null); если файл не выбран - array(null, null)
function upload_image($field, $subdir) {
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) return array(null, null);
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) return array(null, 'Файл не загрузился (код ' . (int)$f['error'] . '). Проверьте размер: не более 5 МБ.');
    if ($f['size'] > 5 * 1024 * 1024) return array(null, 'Файл больше 5 МБ');
    if (!is_uploaded_file($f['tmp_name'])) return array(null, 'Файл не загрузился');
    $info = @getimagesize($f['tmp_name']);
    $ext = array(IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp');
    if (!$info || !isset($ext[$info[2]])) return array(null, 'Подходят только картинки JPG, PNG или WebP');
    $dir = dirname(__DIR__) . '/' . UPLOAD_DIR . '/' . $subdir;
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) return array(null, 'Не удалось создать папку загрузок');
    $name = bin2hex(random_bytes(8)) . '.' . $ext[$info[2]];
    if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) return array(null, 'Не удалось сохранить файл');
    return array(UPLOAD_DIR . '/' . $subdir . '/' . $name, null);
}

// ---------- подписи и общие помощники ----------
function lead_statuses() { return array('new' => 'Новая', 'in_work' => 'В работе', 'deal' => 'Договор заключён', 'rejected' => 'Отказ', 'spam' => 'Спам'); }
function object_statuses() { return array('design' => 'Проектирование', 'building' => 'Строительство', 'done' => 'Сдан'); }
function f_err($err, $k) { return isset($err[$k]) ? '<p class="oshibka" role="alert">' . htmlspecialchars($err[$k], ENT_QUOTES, 'UTF-8') . '</p>' : ''; }
function dt_ru($s) { return $s ? date('d.m.Y H:i', strtotime($s)) : '—'; }
function d_ru($s) { return $s ? date('d.m.Y', strtotime($s)) : '—'; }

// заявка с сайта. $d: name, phone (только цифры), email, message, source, params (массив или null), user_id
function lead_save($d) {
    return db_insert('INSERT INTO leads (name, phone, email, message, source, params, status, user_id, consent_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, \'new\', ?, NOW(), NOW(), NOW())',
        array($d['name'], $d['phone'], $d['email'], $d['message'], $d['source'], $d['params'] ? json_encode($d['params'], JSON_UNESCAPED_UNICODE) : null, $d['user_id']));
}
