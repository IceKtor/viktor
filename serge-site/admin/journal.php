<?php
require_once __DIR__ . '/../includes/admin_ui.php';
$rows = db_all('SELECT id, slug, title, category, published_at, is_published FROM journal_posts ORDER BY published_at DESC, id DESC');
adm_head('Журнал', 'journal');
?>
<p><a class="knopka knopka--derevo" href="post.php">+ Новая статья</a></p>
<div class="adm-scroll"><table class="atab"><tr><th>Дата</th><th>Заголовок</th><th>Рубрика</th><th>На сайте</th></tr>
<?php foreach ($rows as $r): ?><tr><td><?= h(d_ru($r['published_at'])) ?></td><td><a href="post.php?id=<?= (int)$r['id'] ?>"><?= h($r['title']) ?></a></td><td><?= h($r['category']) ?></td><td><?= $r['is_published'] ? 'да' : '<span class="st">скрыта</span>' ?></td></tr><?php endforeach; ?>
</table></div>
<?php adm_foot();
