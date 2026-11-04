<?php
// тут функции которые нужны почти везде, подключается из шапки

require_once __DIR__ . '/repository.php';

// TODO(DB): сейчас данные из JSON, потом из базы - см. includes/repository.php
$rates = repo_rates();
$houses = repo_houses();

// контакты компании для шапки, подвала и нижней панели (редактируются в админ-панели: «О компании»)
function site_contacts() {
    static $c = null;
    if ($c === null) {
        $co = repo_company();
        $d = array('phone' => '+7 (999) 999-99-99', 'phone_href' => '+79999999999', 'email' => 'info@serge-house.ru', 'address' => 'г. Якутск, ул. Ленина, 1', 'hours' => 'Пн–Сб, 9:00–19:00');
        $c = (isset($co['contacts']) && is_array($co['contacts'])) ? array_merge($d, array_filter($co['contacts'], 'strlen')) : $d;
    }
    return $c;
}

// экранирование, чтоб ничего не сломали
function e($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

// 1 этаж, 2 этажа, 5 этажей. слова передаю сам
function sklon($n, $a, $b, $c) {
    $n = abs($n) % 100;
    $n1 = $n % 10;
    if ($n > 10 && $n < 20) return $c;
    if ($n1 > 1 && $n1 < 5) return $b;
    if ($n1 == 1) return $a;
    return $c;
}

function rub($n) {
    // неразрывный пробел чтоб сумма не рвалась на две строки
    return number_format($n, 0, '', "\xC2\xA0") . "\xC2\xA0₽";
}

// проверка того что ввёл пользователь, возвращает массив ошибок (пустой если всё ок)
function proverka($d, $rates) {
    $err = array();

    $area = isset($d['area']) ? trim($d['area']) : '';
    $area = str_replace(',', '.', $area);
    if (!preg_match('/^\d+(\.\d+)?$/', $area) || $area <= 0 || $area < $rates['area_min'] || $area > $rates['area_max']) {
        $err['area'] = 'Укажите площадь числом от ' . $rates['area_min'] . ' до ' . $rates['area_max'] . ' м²';
    }

    $floors = isset($d['floors']) ? trim($d['floors']) : '';
    if (!ctype_digit($floors) || $floors < 1 || $floors > $rates['floors_max']) {
        $err['floors'] = 'Выберите этажность из списка';
    }

    $mat = isset($d['material']) ? $d['material'] : '';
    if (!isset($rates['materials'][$mat])) {
        $err['material'] = 'Выберите материал стен';
    }

    $fnd = isset($d['foundation']) ? $d['foundation'] : '';
    if (!isset($rates['foundations'][$fnd])) {
        $err['foundation'] = 'Выберите тип фундамента';
    }

    return $err;
}

// сам расчёт по формулам из ТЗ (раздел 8.3)
function raschet($area, $floors, $mat, $fnd, $rates) {
    $area = (float)str_replace(',', '.', $area);
    $floors = (int)$floors;

    $s_zas = $area / $floors;                                   // площадь застройки
    $c_st = $area * $rates['materials'][$mat]['price'];         // стены
    $c_f = $s_zas * $rates['foundations'][$fnd]['price'];       // фундамент
    $c0 = $c_st + $c_f;

    $k_et = ($floors == 1) ? 1.0 : $rates['k_floors_many'];
    $k_reg = $rates['k_reg'];
    $c1 = $c0 * $k_et * $k_reg;

    $step = $rates['round_step'];
    $itog = round($c1 / $step) * $step;                         // округляем только итог

    $d = $rates['delta'] / 100;
    $low = round(round($itog * (1 - $d), 2) / $step) * $step;
    $high = round(round($itog * (1 + $d), 2) / $step) * $step;

    return array(
        'area' => $area,
        'floors' => $floors,
        'material' => $rates['materials'][$mat]['name'],
        'material_price' => $rates['materials'][$mat]['price'],
        'foundation' => $rates['foundations'][$fnd]['name'],
        'foundation_price' => $rates['foundations'][$fnd]['price'],
        's_zas' => round($s_zas, 2),
        'c_st' => (int)round($c_st),
        'c_f' => (int)round($c_f),
        'c0' => (int)round($c0),
        'k_et' => $k_et,
        'k_reg' => $k_reg,
        'c1' => (int)round($c1),
        'total' => (int)$itog,
        'low' => (int)$low,
        'high' => (int)$high,
        'delta' => $rates['delta'],
        'step' => $step,
        'date' => $rates['date']
    );
}

// цена дома в каталоге считаем той же формулой, чтоб не расходилось с калькулятором
foreach ($houses as $i => $h) {
    $r = raschet($h['area'], $h['floors'], $h['material'], $h['foundation'], $rates);
    $houses[$i]['price'] = $r['total'];
    // картинки берутся из базы (админка), а если там пусто - по имени файла: <id>-1.webp, -2.webp, -3.svg;
    // если и такого файла нет (новый проект без фото) - показываем фасад, а если и его нет - заглушку
    $g = array(
        !empty($h['img_facade']) ? $h['img_facade'] : 'assets/img/' . $h['id'] . '-1.webp',      // фасад (фото)
        !empty($h['img_interior']) ? $h['img_interior'] : 'assets/img/' . $h['id'] . '-2.webp',  // интерьер (фото)
        !empty($h['img_plan']) ? $h['img_plan'] : 'assets/img/' . $h['id'] . '-3.svg'            // планировка (чертёж)
    );
    $root = dirname(__DIR__) . '/';
    if (!is_file($root . $g[0])) $g[0] = 'assets/img/placeholder.svg';
    for ($k = 1; $k < 3; $k++) if (!is_file($root . $g[$k])) $g[$k] = $g[0];
    $houses[$i]['gallery'] = $g;
}

function naiti_dom($id) {
    global $houses;
    foreach ($houses as $h) {
        if ($h['id'] === $id) return $h;
    }
    return null;
}

function floors_text($n) {
    return $n . ' ' . sklon($n, 'этаж', 'этажа', 'этажей');
}

function bedrooms_text($n) {
    return $n . ' ' . sklon($n, 'спальня', 'спальни', 'спален');
}

// карточка дома, используется на главной и в каталоге
function karto4ka($h, $rates, $hl = 3) {
    $mat = $rates['materials'][$h['material']]['name'];
    $fnd = $rates['foundations'][$h['foundation']]['name'];
    ob_start();
    ?>
    <a class="dom" href="house.php?id=<?= e($h['id']) ?>"
       data-area="<?= (int)$h['area'] ?>" data-floors="<?= (int)$h['floors'] ?>"
       data-material="<?= e($h['material']) ?>" data-foundation="<?= e($h['foundation']) ?>"
       data-bedrooms="<?= (int)$h['bedrooms'] ?>" data-garage="<?= (int)$h['garage'] ?>" data-terrace="<?= (int)$h['terrace'] ?>"
       data-price="<?= (int)$h['price'] ?>">
        <div class="dom__foto"><img src="<?= e($h['gallery'][0]) ?>" alt="<?= e($h['name']) ?>" width="1000" height="750" loading="lazy"></div>
        <div class="dom__tekst">
            <h<?= (int)$hl ?> class="h3"><?= e($h['name']) ?></h<?= (int)$hl ?>>
            <div class="para">
                <span><?= (int)$h['area'] ?> м²</span>
                <span><?= floors_text($h['floors']) ?></span>
                <span><?= bedrooms_text($h['bedrooms']) ?></span>
            </div>
            <div class="para para--melko para--tekst"><?= e($mat) ?> · фундамент <?= e(mb_strtolower($fnd)) ?></div>
            <div class="cena">от <b><?= rub($h['price']) ?></b></div>
            <span class="ssylka">Смотреть дом →</span>
        </div>
    </a>
    <?php
    return ob_get_clean();
}

// дата из базы/JSON (2026-09-14) по-русски: 14 сентября 2026
function data_ru($ymd) {
    $mes = array(1 => 'января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря');
    $t = explode('-', $ymd);
    if (count($t) !== 3) return e($ymd);
    return (int)$t[2] . ' ' . $mes[(int)$t[1]] . ' ' . (int)$t[0];
}

// карточка статьи журнала (журнал и "Читайте также")
function karto4ka_stati($p, $hl = 2) {
    ob_start();
    ?>
    <a class="statya-karta" href="statya.php?id=<?= e($p['slug']) ?>">
        <div class="statya-karta__foto"><img src="<?= e($p['image']) ?>" alt="" width="1000" height="750" loading="lazy"></div>
        <div class="statya-karta__tekst">
            <div class="statya__meta"><span class="metka"><?= e($p['category']) ?></span><?= data_ru($p['published_at']) ?></div>
            <h<?= (int)$hl ?> class="h3"><?= e($p['title']) ?></h<?= (int)$hl ?>>
            <p><?= e($p['preview']) ?></p>
            <span class="ssylka">Читать →</span>
        </div>
    </a>
    <?php
    return ob_get_clean();
}

// адрес сайта для canonical и sitemap. на хостинге можно задать вручную в SITE_URL (config.php)
function site_url() {
    if (defined('SITE_URL') && SITE_URL !== '') return rtrim(SITE_URL, '/');
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
    if (substr($dir, -4) === '/api') $dir = substr($dir, 0, -4);
    if (substr($dir, -6) === '/admin') $dir = substr($dir, 0, -6);
    return ($https ? 'https://' : 'http://') . $host . $dir;
}

// разметка Schema.org "хлебные крошки": $crumbs = array(array('Название', 'страница.php'), ...)
function breadcrumbs_ld($crumbs) {
    $items = array();
    foreach ($crumbs as $i => $c) {
        $items[] = array('@type' => 'ListItem', 'position' => $i + 1, 'name' => $c[0], 'item' => site_url() . '/' . $c[1]);
    }
    $ld = array('@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items);
    return '<script type="application/ld+json">' . json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
}

// разметка Schema.org: организация (главная) и статья (журнал)
function ld_script($ld) { return '<script type="application/ld+json">' . json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>'; }
function organization_ld() {
    $c = site_contacts();
    return ld_script(array('@context' => 'https://schema.org', '@type' => 'HomeAndConstructionBusiness', 'name' => 'СЭРГЭ', 'url' => site_url() . '/',
        'logo' => site_url() . '/assets/img/favicon.svg', 'telephone' => $c['phone'], 'email' => $c['email'],
        'address' => array('@type' => 'PostalAddress', 'streetAddress' => $c['address'], 'addressCountry' => 'RU'),
        'areaServed' => 'Республика Саха (Якутия)', 'openingHours' => $c['hours']));
}
function article_ld($s) {
    return ld_script(array('@context' => 'https://schema.org', '@type' => 'Article', 'headline' => $s['title'], 'description' => $s['preview'],
        'image' => site_url() . '/' . ltrim($s['image'], '/'), 'datePublished' => $s['published_at'],
        'author' => array('@type' => 'Organization', 'name' => 'СЭРГЭ'), 'publisher' => array('@type' => 'Organization', 'name' => 'СЭРГЭ')));
}

// путь к CSS/JS: если рядом есть минимизированная версия (tools/minify.sh), берём её; добавляем версию для сброса кэша
function asset($path) {
    $root = __DIR__ . '/../';
    if (defined('ASSET_MIN') && ASSET_MIN) {
        $min = preg_replace('/\.(css|js)$/', '.min.$1', $path);
        if ($min !== $path && is_file($root . $min)) $path = $min;
    }
    return is_file($root . $path) ? $path . '?v=' . filemtime($root . $path) : $path;
}
