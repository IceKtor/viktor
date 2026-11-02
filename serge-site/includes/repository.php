<?php
// единственное место, откуда сайт берёт данные (дома, расценки, журнал).
// Остальной код про JSON и базу не знает. Когда сделаем БД, меняются только тела функций тут.
// Все места под базу помечены TODO(DB), искать так: grep -rn "TODO(DB)" .

require_once __DIR__ . '/config.php';

function repo_read_json($file) {
    $s = file_get_contents(__DIR__ . '/../data/' . $file);
    $d = json_decode($s, true);
    return is_array($d) ? $d : array();
}

// расценки и коэффициенты (data/rates.json)
function repo_rates() {
    if (USE_DB) {
        // TODO(DB): SELECT из таблиц settings, materials, foundations (database/schema.sql),
        // собрать массив в том же формате, что сейчас в rates.json
    }
    return repo_read_json('rates.json');
}

// проекты домов (data/houses.json)
function repo_houses() {
    if (USE_DB) {
        // TODO(DB): SELECT * FROM houses ORDER BY sort_order; поля как в houses.json
    }
    return repo_read_json('houses.json');
}

// записи журнала для страницы "Журнал"
function repo_journal_posts() {
    if (USE_DB) {
        // TODO(DB): SELECT id, title, slug, preview, published_at FROM journal_posts WHERE is_published = 1
    }
    return array(); // пока записей нет, страница показывает заглушку
}

// данные страницы "О компании" (цифры, текст)
function repo_company() {
    if (USE_DB) {
        // TODO(DB): SELECT из таблицы company_info
    }
    return array(); // пока на странице заглушка
}

// заявки с сайта. В ТЗ форма заявки не входит (допущение А-08, сбор ПДн).
// TODO(DB): когда добавим форму - сначала проверить 152-ФЗ (согласие, политика), потом INSERT INTO leads
function repo_save_lead($data) {
    return false;
}
