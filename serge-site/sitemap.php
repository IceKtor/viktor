<?php
// sitemap.xml формируется из списка страниц и проектов (в .htaccess /sitemap.xml ведёт сюда)
require_once __DIR__ . '/includes/functions.php';
header('Content-Type: application/xml; charset=utf-8');
$base = site_url();
$urls = array('index.php', 'catalog.php', 'calculator.php', 'stroitelstvo.php', 'company.php', 'zhurnal.php', 'zayavka.php', 'tarify.php');
$last = array();
foreach ($houses as $h) $urls[] = 'house.php?id=' . $h['id'];
foreach (repo_journal_posts() as $p) { $urls[] = 'statya.php?id=' . $p['slug']; $last['statya.php?id=' . $p['slug']] = $p['published_at']; }
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $u) {
    $loc = ($u === 'index.php') ? $base . '/' : $base . '/' . $u;
    echo '  <url><loc>' . htmlspecialchars($loc, ENT_XML1) . '</loc>' . (isset($last[$u]) ? '<lastmod>' . htmlspecialchars(substr($last[$u], 0, 10), ENT_XML1) . '</lastmod>' : '') . '</url>' . "\n";
}
echo '</urlset>' . "\n";
