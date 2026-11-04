<?php
// robots.txt формируется скриптом, чтобы адрес sitemap был полным (в .htaccess /robots.txt ведёт сюда)
require_once __DIR__ . '/includes/functions.php';
header('Content-Type: text/plain; charset=utf-8');
echo "User-agent: *\n";
echo "Disallow: /admin/\n";
echo "Disallow: /api/\n";
echo "Disallow: /includes/\n";
echo "Disallow: /data/\n";
echo "Disallow: /database/\n\n";
echo 'Sitemap: ' . site_url() . "/sitemap.xml\n";
