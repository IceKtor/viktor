<?php
// заявка: «Перезвоните мне», «Получить точный расчёт», вопрос по проекту, восстановление доступа.
// Заявка попадает в базу (таблица leads) и видна администратору; аккаунт при этом НЕ создаётся.
// Доступ в кабинет выдаётся после заключения договора (админ-панель → заявка → «Создать кабинет»).
require_once __DIR__ . '/includes/account_page.php';
$u = current_user();

$topic = isset($_GET['topic']) ? $_GET['topic'] : (isset($_POST['topic']) ? $_POST['topic'] : '');
$hid = isset($_GET['house']) ? $_GET['house'] : (isset($_POST['house']) ? $_POST['house'] : '');
$house = $hid !== '' ? naiti_dom($hid) : null;

// параметры расчёта: пересчитываем на сервере, значениям из формы не верим
$calc = null; $src = array('area', 'floors', 'material', 'foundation');
$in = array(); foreach ($src as $k) { if (isset($_REQUEST[$k]) && $_REQUEST[$k] !== '') $in[$k] = (string)$_REQUEST[$k]; }
if (count($in) === 4 && !proverka($in, $rates)) {
    $r = raschet($in['area'], $in['floors'], $in['material'], $in['foundation'], $rates);
    $calc = array('area' => $r['area'], 'floors' => $r['floors'], 'material' => $in['material'], 'foundation' => $in['foundation'],
        'total' => $r['total'], 'low' => $r['low'], 'high' => $r['high'], 'date' => $r['date']);
    $calc_text = ruChislo($r['area']) . ' м², ' . floors_text($r['floors']) . ', ' . $rates['materials'][$in['material']]['name'] . ', ' . mb_strtolower($rates['foundations'][$in['foundation']]['name']) . ' фундамент — ≈ ' . rub($r['total']);
}
function ruChislo($n) { return str_replace('.', ',', (string)$n); }

$err = array();
$v = array('name' => $u ? $u['name'] : '', 'phone' => ($u && $u['phone']) ? fmt_phone($u['phone']) : '', 'email' => $u ? (string)$u['email'] : '', 'message' => '');
if ($topic === 'reset') $v['message'] = 'Прошу восстановить доступ в личный кабинет.';
elseif ($house) $v['message'] = 'Интересует проект ' . $house['name'] . '.';
elseif ($calc) $v['message'] = 'Хочу получить точный расчёт по параметрам из калькулятора.';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    foreach (array('name', 'phone', 'email', 'message') as $k) $v[$k] = trim(isset($_POST[$k]) ? $_POST[$k] : '');
    $phone = norm_phone($v['phone']);
    $email = $v['email'] === '' ? null : norm_email($v['email']);
    if (mb_strlen($v['name']) < 2 || mb_strlen($v['name']) > 100) $err['name'] = 'Укажите имя: от 2 до 100 символов';
    if (!$phone) $err['phone'] = 'Укажите российский номер телефона, например +7 900 000-00-00';
    if ($v['email'] !== '' && !$email) $err['email'] = 'Проверьте адрес почты';
    if (mb_strlen($v['message']) > 1000) $err['message'] = 'Сообщение не длиннее 1000 символов';
    if (empty($_POST['consent'])) $err['consent'] = 'Нужно согласие на обработку данных';
    if (!empty($_POST['website'])) redirect('zayavka.php?ok=1');   // скрытое поле-ловушка для роботов: человек его не видит и не заполняет; роботу «успех», заявку не сохраняем
    if (!$err && (rl_count('lead', rl_ip(), 3600) >= 5 || rl_count('lead', rl_key('ph|' . $phone), 86400) >= 3)) $err['common'] = 'Вы уже отправляли заявки. Мы свяжемся с вами, или позвоните нам по телефону.';
    if (!$err) {
        rl_hit('lead', rl_ip()); rl_hit('lead', rl_key('ph|' . $phone));
        $source = $topic === 'reset' ? 'reset' : ($house ? 'house:' . $house['id'] : ($calc ? 'calculator' : 'site'));
        $params = $calc; if ($calc && $house) $params['house'] = $house['id'];
        $lid = lead_save(array('name' => $v['name'], 'phone' => $phone, 'email' => $email, 'message' => $v['message'], 'source' => $source, 'params' => $params, 'user_id' => $u ? $u['id'] : null));
        if ($topic !== 'reset') notify_lead_manager($lid, $v['name'], fmt_phone($phone), $v['message'], $calc ? $calc_text : '');
        // подтверждение клиенту на почту (если указал и почта настроена); на один адрес не чаще 3 писем в сутки, чтобы форму нельзя было использовать для спама
        if ($email && MAIL_ENABLED && $topic !== 'reset' && rl_count('mail', rl_key('em|' . $email), 86400) < 3) {
            rl_hit('mail', rl_key('em|' . $email));
            notify_lead_confirm($email, $v['name'], $lid, $calc ? $calc_text : '');
        }
        redirect('zayavka.php?ok=1');
    }
}

$page_title = 'Оставить заявку — СЭРГЭ';
$page_desc = 'Оставьте заявку, и мы перезвоним: ответим на вопросы, уточним расчёт и подберём проект.';
$active = '';
require __DIR__ . '/includes/header.php';
?>
<main class="stranica">
    <div class="vnutri vnutri--forma">
        <?php if (isset($_GET['ok'])): ?>
            <h1 class="zag-stranicy">Заявка принята</h1>
            <div class="uspeh" data-modal="ok">Спасибо! Мы перезвоним вам в рабочее время (пн–сб, 9:00–19:00).</div>
            <?php if (!$u): ?>
                <div class="vspom">
                    <p>Чтобы следить за заявкой и ходом строительства, <a class="ssylka" href="register.php">создайте аккаунт</a> или войдите. Если вы заключите с нами договор, менеджер сам выдаст доступ в кабинет.</p>
                </div>
            <?php endif; ?>
            <div class="knopki"><a class="knopka knopka--cher" href="catalog.php">Смотреть проекты</a><a class="knopka knopka--ramka" href="index.php">На главную</a></div>
        <?php else: ?>
            <h1 class="zag-stranicy"><?= $topic === 'reset' ? 'Восстановление доступа' : 'Оставить заявку' ?></h1>
            <p class="seryj"><?= $topic === 'reset' ? 'Оставьте номер телефона, который вы указывали при регистрации или в договоре, и менеджер выдаст новый пароль.' : 'Оставьте контакты, и мы перезвоним: ответим на вопросы и уточним расчёт. Регистрироваться для этого не нужно.' ?></p>
            <?php if ($calc): ?><div class="podskazka">Ваш расчёт: <?= e($calc_text) ?></div><?php endif; ?>
            <?php if ($house && !$calc): ?><div class="podskazka">Проект: <?= e($house['name']) ?>, <?= (int)$house['area'] ?> м²</div><?php endif; ?>

            <form class="forma forma--uzkaya" method="post" action="zayavka.php" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="topic" value="<?= e($topic) ?>"><input type="hidden" name="house" value="<?= e($house ? $house['id'] : '') ?>">
                <?php if ($calc) foreach ($src as $k): ?><input type="hidden" name="<?= e($k) ?>" value="<?= e($in[$k]) ?>"><?php endforeach; ?>
                <div class="lovushka" aria-hidden="true"><label>Сайт<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
                <?= isset($err['common']) ? '<p class="oshibka oshibka--obshaya" role="alert" data-modal="err">' . e($err['common']) . '</p>' : '' ?>
                <div class="pole<?= isset($err['name']) ? ' est-oshibka' : '' ?>"><label for="name">Имя</label><input type="text" id="name" name="name" autocomplete="name" value="<?= e($v['name']) ?>" required><?= f_err($err, 'name') ?></div>
                <div class="pole<?= isset($err['phone']) ? ' est-oshibka' : '' ?>"><label for="phone">Телефон</label><input type="tel" id="phone" name="phone" autocomplete="tel" inputmode="tel" placeholder="+7 900 000-00-00" value="<?= e($v['phone']) ?>" required><?= f_err($err, 'phone') ?></div>
                <div class="pole<?= isset($err['email']) ? ' est-oshibka' : '' ?>"><label for="email">Почта <span class="melko">(необязательно)</span></label><input type="email" id="email" name="email" autocomplete="email" value="<?= e($v['email']) ?>"><?= f_err($err, 'email') ?></div>
                <div class="pole<?= isset($err['message']) ? ' est-oshibka' : '' ?>"><label for="message">Комментарий <span class="melko">(необязательно)</span></label><textarea id="message" name="message" rows="4" maxlength="1000"><?= e($v['message']) ?></textarea><?= f_err($err, 'message') ?></div>
                <div class="pole pole--galka<?= isset($err['consent']) ? ' est-oshibka' : '' ?>">
                    <label class="galka"><input type="checkbox" name="consent" value="1"<?= !empty($_POST['consent']) ? ' checked' : '' ?>> <span>Даю согласие на обработку моих персональных данных (имя, телефон, почта) для связи со мной и работы с заявкой на условиях <a class="ssylka" href="privacy.php" target="_blank" rel="noopener">Политики обработки персональных данных</a>. Согласие можно отозвать, написав нам на почту</span></label>
                    <?= f_err($err, 'consent') ?>
                </div>
                <div class="knopki knopki--forma"><button class="knopka knopka--cher" type="submit">Отправить заявку</button></div>
            </form>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
