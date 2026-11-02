<?php
// служебный: выводит HTML одной страницы в консоль (вызывается из build-static.php)
// php tools/render.php <файл.php> [id дома]
define('STATIC_BUILD', true);
$root = dirname(__DIR__);
chdir($root);
$file = $argv[1];
$_SERVER['SCRIPT_NAME'] = '/' . $file;
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = 'localhost';
if (isset($argv[2])) $_GET['id'] = $argv[2];
function http_response_code_stub() {}
include $root . '/' . $file;
