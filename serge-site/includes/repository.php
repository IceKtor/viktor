<?php
// Единственное место, откуда публичные страницы сайта берут данные (дома, расценки, этапы, журнал, компания).
// USE_DB = true - из MySQL, иначе (или если база не отвечает) - из data/*.json. Остальной код про это не знает.
// Личные кабинеты, заявки и админка работают только с базой и обращаются к ней через includes/db.php.

require_once __DIR__ . '/config.php';

function repo_read_json($file) {
    $s = file_get_contents(__DIR__ . '/../data/' . $file);
    $d = json_decode($s, true);
    return is_array($d) ? $d : array();
}

// Если база не отвечает, публичные страницы показывают данные из data/*.json, чтобы сайт не падал.
function repo_db_ok() {
    static $ok = null;
    if ($ok === null) {
        $ok = false;
        if (USE_DB) {
            try { require_once __DIR__ . '/db.php'; db(); $ok = true; }
            catch (Exception $e) { error_log('СЭРГЭ: нет связи с базой: ' . $e->getMessage()); }
        }
    }
    return $ok;
}

// расценки и коэффициенты
function repo_rates() {
    if (repo_db_ok()) {
        try {
            $st = array();
            foreach (db_all('SELECT name, value FROM settings') as $r) $st[$r['name']] = $r['value'];
            $r = array(
                'area_min' => (int)$st['area_min'], 'area_max' => (int)$st['area_max'], 'floors_max' => (int)$st['floors_max'],
                'delta' => (int)$st['delta'], 'round_step' => (int)$st['round_step'],
                'k_reg' => (float)$st['k_reg'], 'k_floors_many' => (float)$st['k_floors_many'], 'date' => $st['date'],
                'materials' => array(), 'foundations' => array(),
            );
            foreach (db_all('SELECT code, name, price FROM materials WHERE is_active = 1 ORDER BY sort_order, code') as $m) $r['materials'][$m['code']] = array('name' => $m['name'], 'price' => (int)$m['price']);
            foreach (db_all('SELECT code, name, price FROM foundations WHERE is_active = 1 ORDER BY sort_order, code') as $m) $r['foundations'][$m['code']] = array('name' => $m['name'], 'price' => (int)$m['price']);
            if ($r['materials'] && $r['foundations']) return $r;
        } catch (Exception $e) { error_log('СЭРГЭ repo_rates: ' . $e->getMessage()); }
    }
    return repo_read_json('rates.json');
}

// проекты домов
function repo_houses($all = false) {
    if (repo_db_ok()) {
        try {
            $rows = db_all('SELECT * FROM houses ' . ($all ? '' : 'WHERE is_published = 1 ') . 'ORDER BY sort_order, id');
            $out = array();
            foreach ($rows as $r) $out[] = array(
                'id' => $r['id'], 'name' => $r['name'], 'area' => (int)$r['area'], 'floors' => (int)$r['floors'], 'bedrooms' => (int)$r['bedrooms'],
                'bathrooms' => (int)$r['bathrooms'], 'material' => $r['material_code'], 'foundation' => $r['foundation_code'],
                'description' => (string)$r['description'], 'garage' => (int)$r['garage'], 'terrace' => (int)$r['terrace'],
                'img_facade' => $r['img_facade'], 'img_interior' => $r['img_interior'], 'img_plan' => $r['img_plan'],
            );
            return $out;
        } catch (Exception $e) { error_log('СЭРГЭ repo_houses: ' . $e->getMessage()); }
    }
    return repo_read_json('houses.json');
}

// записи журнала, новые сверху
function repo_journal_posts() {
    if (repo_db_ok()) {
        try {
            $out = array();
            foreach (db_all('SELECT * FROM journal_posts WHERE is_published = 1 ORDER BY published_at DESC, id DESC') as $r) $out[] = repo_post_row($r);
            return $out;
        } catch (Exception $e) { error_log('СЭРГЭ repo_journal_posts: ' . $e->getMessage()); }
    }
    $posts = repo_read_json('journal.json');
    usort($posts, function ($a, $b) { return strcmp($b['published_at'], $a['published_at']); });
    return $posts;
}

function repo_post_row($r) {
    $body = preg_split('/\R{2,}/u', trim((string)$r['body']));
    return array('slug' => $r['slug'], 'title' => $r['title'], 'preview' => (string)$r['preview'], 'image' => (string)$r['image'],
        'category' => (string)$r['category'], 'read_min' => (int)$r['read_min'], 'published_at' => (string)$r['published_at'], 'body' => $body);
}

// одна статья журнала по slug, null если нет
function repo_journal_post($slug) {
    foreach (repo_journal_posts() as $p) {
        if ($p['slug'] === $slug) return $p;
    }
    return null;
}

// страница "О компании": текст, цифры, принципы, команда, контакты
function repo_company() {
    if (repo_db_ok()) {
        try {
            $out = array();
            foreach (db_all('SELECT k, v FROM company_info') as $r) $out[$r['k']] = json_decode($r['v'], true);
            if (isset($out['about'], $out['contacts'])) return $out;
        } catch (Exception $e) { error_log('СЭРГЭ repo_company: ' . $e->getMessage()); }
    }
    return repo_read_json('company.json');
}

// этапы строительства для главной и страницы "Строительство"
function repo_stages() {
    if (repo_db_ok()) {
        try {
            $rows = db_all('SELECT id, title, short, text, time FROM stages ORDER BY sort_order, id');
            if ($rows) return $rows;
        } catch (Exception $e) { error_log('СЭРГЭ repo_stages: ' . $e->getMessage()); }
    }
    return repo_read_json('stages.json');
}
