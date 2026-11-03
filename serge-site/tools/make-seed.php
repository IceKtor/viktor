<?php
// Делает database/seed.sql из data/*.json: php tools/make-seed.php > database/seed.sql
// Нужен, если в JSON что-то поменяли и хочется заново получить начальные данные для базы.
$root = dirname(__DIR__) . '/data/';
function j($f) { global $root; return json_decode(file_get_contents($root . $f), true); }
function q($s) { return "'" . str_replace(array("\\", "'"), array("\\\\", "''"), (string)$s) . "'"; }
$rates = j('rates.json'); $houses = j('houses.json'); $stages = j('stages.json'); $posts = j('journal.json'); $co = j('company.json');
echo "-- Начальные данные (создано tools/make-seed.php из data/*.json). Импортировать ПОСЛЕ schema.sql.\nSET NAMES utf8mb4;\n\n";
foreach (array('area_min','area_max','floors_max','delta','round_step','k_reg','k_floors_many','date') as $k)
    echo "INSERT INTO settings (name, value) VALUES (" . q($k) . ", " . q($rates[$k]) . ") ON DUPLICATE KEY UPDATE value = VALUES(value);\n";
echo "\n";
foreach (array('materials' => 'materials', 'foundations' => 'foundations') as $key => $tbl) {
    $i = 0;
    foreach ($rates[$key] as $code => $m) { $i++;
        echo "INSERT INTO $tbl (code, name, price, sort_order) VALUES (" . q($code) . ", " . q($m['name']) . ", " . (int)$m['price'] . ", $i) ON DUPLICATE KEY UPDATE name = VALUES(name), price = VALUES(price);\n"; }
}
echo "\n";
$i = 0;
foreach ($houses as $h) { $i++;
    echo "INSERT INTO houses (id, name, area, floors, bedrooms, bathrooms, material_code, foundation_code, garage, terrace, description, sort_order) VALUES ("
        . q($h['id']) . ", " . q($h['name']) . ", " . (int)$h['area'] . ", " . (int)$h['floors'] . ", " . (int)$h['bedrooms'] . ", " . (int)$h['bathrooms'] . ", "
        . q($h['material']) . ", " . q($h['foundation']) . ", " . (int)$h['garage'] . ", " . (int)$h['terrace'] . ", " . q($h['description']) . ", $i) ON DUPLICATE KEY UPDATE name = VALUES(name);\n"; }
echo "\n";
$i = 0;
foreach ($stages as $s) { $i++;
    echo "INSERT INTO stages (id, title, short, text, time, sort_order) VALUES ($i, " . q($s['title']) . ", " . q($s['short']) . ", " . q($s['text']) . ", " . q($s['time']) . ", $i) ON DUPLICATE KEY UPDATE title = VALUES(title);\n"; }
echo "\n";
foreach ($posts as $p) {
    echo "INSERT INTO journal_posts (slug, title, preview, image, category, read_min, body, published_at) VALUES ("
        . q($p['slug']) . ", " . q($p['title']) . ", " . q($p['preview']) . ", " . q($p['image']) . ", " . q($p['category']) . ", " . (int)$p['read_min'] . ", "
        . q(implode("\n\n", $p['body'])) . ", " . q($p['published_at']) . ") ON DUPLICATE KEY UPDATE title = VALUES(title);\n"; }
echo "\n";
foreach ($co as $k => $v)
    echo "INSERT INTO company_info (k, v) VALUES (" . q($k) . ", " . q(json_encode($v, JSON_UNESCAPED_UNICODE)) . ") ON DUPLICATE KEY UPDATE v = VALUES(v);\n";
