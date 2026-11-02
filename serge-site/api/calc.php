<?php
// сюда js отправляет форму калькулятора, тут проверка и расчёт
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(array('ok' => false, 'error' => 'Только POST'), JSON_UNESCAPED_UNICODE);
    exit;
}

$err = proverka($_POST, $rates);
if (count($err) > 0) {
    echo json_encode(array('ok' => false, 'errors' => $err), JSON_UNESCAPED_UNICODE);
    exit;
}

$res = raschet($_POST['area'], $_POST['floors'], $_POST['material'], $_POST['foundation'], $rates);
echo json_encode(array('ok' => true, 'result' => $res), JSON_UNESCAPED_UNICODE);
