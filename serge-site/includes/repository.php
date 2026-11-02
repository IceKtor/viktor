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

// записи журнала для страницы "Журнал" (data/journal.json), новые сверху
function repo_journal_posts() {
    if (USE_DB) {
        // TODO(DB): SELECT slug, title, preview, image, category, read_min, body, published_at
        //           FROM journal_posts WHERE is_published = 1 ORDER BY published_at DESC
    }
    $posts = repo_read_json('journal.json');
    usort($posts, function ($a, $b) { return strcmp($b['published_at'], $a['published_at']); });
    return $posts;
}

// одна статья журнала по slug, null если нет
function repo_journal_post($slug) {
    if (USE_DB) {
        // TODO(DB): SELECT ... FROM journal_posts WHERE slug = ? AND is_published = 1 (подготовленный запрос!)
    }
    foreach (repo_journal_posts() as $p) {
        if ($p['slug'] === $slug) return $p;
    }
    return null;
}

// данные страницы "О компании": текст, цифры, принципы, команда, контакты (data/company.json)
function repo_company() {
    if (USE_DB) {
        // TODO(DB): SELECT из таблиц company_info, team (database/schema.sql)
    }
    return repo_read_json('company.json');
}

// этапы строительства для главной и страницы "Строительство" (data/stages.json)
function repo_stages() {
    if (USE_DB) {
        // TODO(DB): SELECT title, short, text, time FROM stages ORDER BY sort_order
    }
    return repo_read_json('stages.json');
}

// заявки с сайта. В ТЗ форма заявки не входит (допущение А-08, сбор ПДн).
// TODO(DB): когда добавим форму - сначала проверить 152-ФЗ (согласие, политика), потом INSERT INTO leads
function repo_save_lead($data) {
    return false;
}
