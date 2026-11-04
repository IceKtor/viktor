<?php
// Статическая выгрузка сайта для GitHub Pages (там нет PHP).
// Запуск:  SERGE_SITE_URL=https://icektor.github.io/viktor php tools/build-static.php [папка, по умолчанию ../dist]
// Что делает: прогоняет страницы через PHP, сохраняет как .html, правит ссылки *.php -> *.html,
// копирует assets/, создаёт sitemap.xml, robots.txt, .nojekyll. Основной PHP-код не меняется.
$root = dirname(__DIR__);
$out = isset($argv[1]) ? $argv[1] : $root . '/../dist';
$site = getenv('SERGE_SITE_URL') ?: '';

function rrmdir($d) { if (!is_dir($d)) return; foreach (scandir($d) as $f) { if ($f === '.' || $f === '..') continue; $p = "$d/$f"; is_dir($p) ? rrmdir($p) : unlink($p); } rmdir($d); }
function rcopy($a, $b) { mkdir($b, 0777, true); foreach (scandir($a) as $f) { if ($f === '.' || $f === '..') continue; is_dir("$a/$f") ? rcopy("$a/$f", "$b/$f") : copy("$a/$f", "$b/$f"); } }
function render($root, $file, $id = null) {
    global $site;
    $cmd = array(PHP_BINARY, $root . '/tools/render.php', $file);
    if ($id !== null) $cmd[] = $id;
    $p = proc_open($cmd, array(1 => array('pipe', 'w'), 2 => array('pipe', 'w')), $pipes, $root, array('SERGE_SITE_URL' => $site));
    $html = stream_get_contents($pipes[1]); $err = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    if (proc_close($p) !== 0 || $err !== '' || strlen($html) < 500) { fwrite(STDERR, "Ошибка рендера $file $id: $err\n"); exit(1); }
    return $html;
}
// ссылки: house.php?id=X -> house-X.html, остальное *.php -> *.html
function fix($s) {
    $s = preg_replace('~house\.php\?id=([A-Za-z0-9_-]+)~', 'house-$1.html', $s);
    $s = preg_replace('~statya\.php\?id=([A-Za-z0-9_-]+)~', 'statya-$1.html', $s);
    return preg_replace('~\b(index|catalog|calculator|stroitelstvo|company|zhurnal|404)\.php\b~', '$1.html', $s);
}

rrmdir($out);
mkdir($out, 0777, true);
rcopy($root . '/assets', $out . '/assets');
// в статическую версию не нужны загрузки из админки и стили админ-панели
rrmdir($out . '/assets/uploads'); @unlink($out . '/assets/css/admin.css');
@unlink($out . '/assets/img/CREDITS.txt'); // оставляем: это лицензионные ссылки на фото
copy($root . '/assets/img/CREDITS.txt', $out . '/assets/img/CREDITS.txt');

// JS/CSS: ссылки внутри скриптов тоже на .html
foreach (glob($out . '/assets/js/*.js') as $f) file_put_contents($f, fix(file_get_contents($f)));

$houses = json_decode(file_get_contents($root . '/data/houses.json'), true);
if (isset($houses['houses'])) $houses = $houses['houses'];
$pages = array('index', 'catalog', 'calculator', 'stroitelstvo', 'company', 'zhurnal', 'tarify', '404');
$urls = array('', 'catalog.html', 'calculator.html', 'stroitelstvo.html', 'company.html', 'zhurnal.html');
foreach ($pages as $p) file_put_contents("$out/$p.html", fix(render($root, "$p.php")));
foreach ($houses as $h) {
    file_put_contents("$out/house-{$h['id']}.html", fix(render($root, 'house.php', $h['id'])));
    $urls[] = "house-{$h['id']}.html";
}
$posts = json_decode(file_get_contents($root . '/data/journal.json'), true);
foreach ($posts as $p) {
    file_put_contents("$out/statya-{$p['slug']}.html", fix(render($root, 'statya.php', $p['slug'])));
    $urls[] = "statya-{$p['slug']}.html";
}

$sm = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $u) $sm .= '  <url><loc>' . htmlspecialchars($site . '/' . $u, ENT_XML1) . "</loc></url>\n";
file_put_contents("$out/sitemap.xml", $sm . "</urlset>\n");
file_put_contents("$out/robots.txt", "User-agent: *\nAllow: /\n\nSitemap: $site/sitemap.xml\n");
file_put_contents("$out/.nojekyll", '');
echo "Готово: $out, страниц: " . (count($pages) + count($houses) + count($posts)) . "\n";
