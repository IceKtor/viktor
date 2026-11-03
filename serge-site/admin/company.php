<?php
// страница «О компании»: тексты, цифры, принципы, команда, контакты
require_once __DIR__ . '/../includes/admin_ui.php';
$co = repo_company();
function lines2($arr, $a, $b) { $o = array(); foreach ($arr as $r) $o[] = $r[$a] . ' | ' . $r[$b]; return implode("\n", $o); }
function parse2($s, $a, $b) { $o = array(); foreach (preg_split('/\R/u', trim($s)) as $ln) { $ln = trim($ln); if ($ln === '') continue; $p = array_map('trim', explode('|', $ln, 2)); if (count($p) < 2 || $p[0] === '') return null; $o[] = array($a => $p[0], $b => $p[1]); } return $o; }
$err = array();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    adm_post_guard();
    $about = array_values(array_filter(array_map('trim', preg_split('/\R{2,}/u', trim(isset($_POST['about']) ? (string)$_POST['about'] : ''))), 'strlen'));
    $fig = parse2(post('figures') ?: (isset($_POST['figures']) ? $_POST['figures'] : ''), 'value', 'label');
    $pr = parse2(isset($_POST['principles']) ? $_POST['principles'] : '', 'title', 'text');
    $team = parse2(isset($_POST['team']) ? $_POST['team'] : '', 'name', 'role');
    $ct = array('phone' => post('phone'), 'phone_href' => preg_replace('/[^\d+]/', '', post('phone')), 'email' => post('email'), 'address' => post('address'), 'hours' => post('hours'), 'requisites' => post('requisites'));
    if (!$about) $err['about'] = 'Нужен хотя бы один абзац';
    if ($fig === null) $err['figures'] = 'Каждая строка: «значение | подпись»';
    if ($pr === null) $err['principles'] = 'Каждая строка: «заголовок | текст»';
    if ($team === null) $err['team'] = 'Каждая строка: «имя | должность»';
    if ($ct['phone'] === '' || !norm_email($ct['email'])) $err['contacts'] = 'Укажите телефон и корректную почту';
    if (!$err) {
        $new = array('about' => $about, 'figures' => $fig, 'principles' => $pr, 'team' => $team, 'contacts' => $ct, 'demo_note' => post('demo_note'));
        foreach ($new as $k => $val) db_exec('INSERT INTO company_info (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)', array($k, json_encode($val, JSON_UNESCAPED_UNICODE)));
        audit('company_edit', 'company'); adm_back('company.php', 'ok', 'Страница «О компании» обновлена');
    }
    $co = array('about' => $about, 'figures' => $fig ?: array(), 'principles' => $pr ?: array(), 'team' => $team ?: array(), 'contacts' => $ct, 'demo_note' => post('demo_note'));
}
$c = isset($co['contacts']) ? $co['contacts'] : array('phone' => '', 'email' => '', 'address' => '', 'hours' => '', 'requisites' => '');
adm_head('О компании', 'company');
?>
<p class="melko">Содержимое страницы «О компании». Телефон, почта, адрес и часы работы из блока контактов показываются на этой странице, в шапке, подвале и на нижней панели телефона.</p>
<form class="adm-card adm-form" method="post">
    <?= csrf_field() ?>
    <?= fld('about', 'Описание компании', implode("\n\n", isset($co['about']) ? $co['about'] : array()), array('type' => 'textarea', 'rows' => 7, 'err' => $err, 'hint' => 'Абзацы разделяйте пустой строкой')) ?>
    <?= fld('figures', 'Цифры', lines2(isset($co['figures']) ? $co['figures'] : array(), 'value', 'label'), array('type' => 'textarea', 'rows' => 5, 'err' => $err, 'hint' => 'По одной на строку: «120+ | домов построено»')) ?>
    <?= fld('principles', 'Принципы работы', lines2(isset($co['principles']) ? $co['principles'] : array(), 'title', 'text'), array('type' => 'textarea', 'rows' => 6, 'err' => $err, 'hint' => 'По одному на строку: «Заголовок | пояснение»')) ?>
    <?= fld('team', 'Команда', lines2(isset($co['team']) ? $co['team'] : array(), 'name', 'role'), array('type' => 'textarea', 'rows' => 6, 'err' => $err, 'hint' => 'По одному на строку: «Имя Фамилия | должность»')) ?>
    <h2>Контакты</h2>
    <?= isset($err['contacts']) ? '<p class="oshibka" role="alert">' . h($err['contacts']) . '</p>' : '' ?>
    <div class="adm-grid">
        <?= fld('phone', 'Телефон', $c['phone']) ?><?= fld('email', 'Почта', $c['email'], array('type' => 'email')) ?><?= fld('address', 'Адрес', $c['address']) ?>
        <?= fld('hours', 'Часы работы', $c['hours']) ?><?= fld('requisites', 'Реквизиты', $c['requisites']) ?>
    </div>
    <?= fld('demo_note', 'Примечание внизу страницы (можно оставить пустым)', isset($co['demo_note']) ? $co['demo_note'] : '', array('type' => 'textarea', 'rows' => 2)) ?>
    <div class="adm-actions"><button class="knopka knopka--cher" type="submit">Сохранить</button> <a class="knopka knopka--ramka" href="../company.php" target="_blank" rel="noopener">Открыть страницу</a></div>
</form>
<?php adm_foot();
