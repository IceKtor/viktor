<?php
require_once __DIR__ . '/../includes/admin_ui.php';
$id = get_int('id');
$p = $id ? db_row('SELECT * FROM journal_posts WHERE id = ?', array($id)) : null;
if ($id && !$p) { http_response_code(404); adm_head('Статья не найдена', 'journal'); echo '<p><a href="journal.php">← К журналу</a></p>'; adm_foot(); exit; }
$def = array('slug' => '', 'title' => '', 'preview' => '', 'image' => 'assets/img/etap.webp', 'category' => '', 'read_min' => 4, 'body' => '', 'published_at' => date('Y-m-d'), 'is_published' => 1);
$v = $p ? $p : $def; $err = array();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    adm_post_guard();
    $do = post('do');
    if ($do === 'save') {
        foreach (array('title', 'preview', 'category', 'read_min', 'published_at') as $k) $v[$k] = post($k);
        $v['body'] = str_replace("\r\n", "\n", trim(isset($_POST['body']) ? (string)$_POST['body'] : '')); $v['is_published'] = post('is_published') ? 1 : 0;
        if (!$p) { $v['slug'] = strtolower(post('slug')); if (!preg_match('/^[a-z0-9][a-z0-9-]{2,98}$/', $v['slug'])) $err['slug'] = 'Латиница, цифры и дефис, например fundament-na-merzlote'; elseif (db_val('SELECT id FROM journal_posts WHERE slug = ?', array($v['slug']))) $err['slug'] = 'Такой адрес уже занят'; }
        if ($v['title'] === '') $err['title'] = 'Укажите заголовок';
        if (!ctype_digit((string)$v['read_min']) || $v['read_min'] < 1 || $v['read_min'] > 60) $err['read_min'] = 'Минуты от 1 до 60';
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $v['published_at'])) $err['published_at'] = 'Дата ГГГГ-ММ-ДД';
        if ($v['body'] === '') $err['body'] = 'Напишите текст статьи';
        list($img, $ierr) = upload_image('image_file', 'journal'); if ($ierr) $err['image_file'] = $ierr;
        if ($img) $v['image'] = $img; elseif (post('image_path') !== '') $v['image'] = post('image_path');
        if (!$err) {
            $par = array($v['title'], $v['preview'], $v['image'], $v['category'], (int)$v['read_min'], $v['body'], $v['published_at'], $v['is_published']);
            if ($p) { $par[] = $id; db_exec('UPDATE journal_posts SET title=?, preview=?, image=?, category=?, read_min=?, body=?, published_at=?, is_published=? WHERE id=?', $par); audit('post_edit', 'post', $id); adm_back('post.php?id=' . $id, 'ok', 'Сохранено'); }
            array_unshift($par, $v['slug']);
            $nid = db_insert('INSERT INTO journal_posts (slug, title, preview, image, category, read_min, body, published_at, is_published) VALUES (?,?,?,?,?,?,?,?,?)', $par);
            audit('post_create', 'post', $nid); adm_back('post.php?id=' . $nid, 'ok', 'Статья добавлена');
        }
    } elseif ($p && $do === 'delete') {
        db_exec('DELETE FROM journal_posts WHERE id = ?', array($id)); audit('post_delete', 'post', $id, $p['title']); adm_back('journal.php', 'ok', 'Статья удалена');
    }
}
adm_head($p ? $p['title'] : 'Новая статья', 'journal');
?>
<p><a href="journal.php">← К журналу</a><?= $p ? ' · <a href="../statya.php?id=' . h($p['slug']) . '" target="_blank" rel="noopener">открыть на сайте</a>' : '' ?></p>
<form class="adm-card adm-form" method="post" enctype="multipart/form-data">
    <?= csrf_field() ?><input type="hidden" name="do" value="save">
    <div class="adm-grid">
        <?= $p ? '<div class="pole"><label>Адрес статьи</label><input type="text" value="' . h($p['slug']) . '" disabled></div>' : fld('slug', 'Адрес статьи (латиницей)', $v['slug'], array('req' => 1, 'err' => $err, 'placeholder' => 'kak-vybrat-fundament')) ?>
        <?= fld('title', 'Заголовок', $v['title'], array('req' => 1, 'err' => $err)) ?>
        <?= fld('category', 'Рубрика', $v['category'], array('placeholder' => 'Фундамент')) ?>
        <?= fld('published_at', 'Дата', $v['published_at'], array('type' => 'date', 'err' => $err)) ?>
        <?= fld('read_min', 'Время чтения, мин', $v['read_min'], array('type' => 'number', 'min' => 1, 'err' => $err)) ?>
    </div>
    <?= fld('preview', 'Краткое описание (для списка)', $v['preview'], array('type' => 'textarea', 'rows' => 2)) ?>
    <div class="adm-grid">
        <div class="pole<?= isset($err['image_file']) ? ' est-oshibka' : '' ?>"><label for="f_image_file">Обложка (JPG, PNG, WebP до 5 МБ)</label>
            <div class="adm-img"><img src="../<?= h($v['image']) ?>" alt="" onerror="this.style.visibility='hidden'"></div>
            <input type="file" id="f_image_file" name="image_file" accept="image/jpeg,image/png,image/webp"><?= f_err($err, 'image_file') ?></div>
        <?= fld('image_path', 'или путь к готовой картинке на сайте', $v['image'], array('hint' => 'Например assets/img/teplo.webp')) ?>
    </div>
    <?= fld('body', 'Текст статьи', $v['body'], array('type' => 'textarea', 'rows' => 16, 'err' => $err, 'hint' => 'Абзацы разделяйте пустой строкой. Строка, начинающаяся с «## », — подзаголовок.')) ?>
    <div class="adm-actions"><?= chk('is_published', 'Показывать на сайте', $v['is_published']) ?></div>
    <div class="adm-actions"><button class="knopka knopka--cher" type="submit"><?= $p ? 'Сохранить' : 'Опубликовать' ?></button></div>
</form>
<?php if ($p): ?><form method="post" onsubmit="return confirm('Удалить статью?')"><?= csrf_field() ?><input type="hidden" name="do" value="delete"><button class="knopka knopka--ramka adm-danger" type="submit">Удалить статью</button></form><?php endif; adm_foot();
