<?php
require_once __DIR__ . '/../includes/admin_ui.php';
$rows = db_all('SELECT o.*, u.name AS uname, u.phone AS uphone FROM objects o JOIN users u ON u.id = o.user_id ORDER BY o.status = \'done\', o.created_at DESC');
adm_head('Объекты строительства', 'objects');
?>
<p><a class="knopka knopka--derevo" href="object.php">+ Новый объект</a></p>
<div class="adm-scroll"><table class="atab">
<tr><th>Код</th><th>Название</th><th>Клиент</th><th>Статус</th><th>Готовность</th><th>Плановая сдача</th></tr>
<?php foreach ($rows as $o): ?>
<tr><td><a href="object.php?id=<?= (int)$o['id'] ?>"><?= h($o['code']) ?></a></td><td><?= h($o['title']) ?></td><td><a href="client.php?id=<?= (int)$o['user_id'] ?>"><?= h($o['uname']) ?></a></td>
<td><span class="st st--<?= h($o['status']) ?>"><?= h(object_statuses()[$o['status']]) ?></span></td><td><?= (int)$o['progress'] ?>%</td><td><?= h(d_ru($o['planned_end'])) ?></td></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="6" class="melko-t">Объектов нет. Добавьте первый: клиент увидит его в личном кабинете.</td></tr><?php endif; ?>
</table></div>
<?php adm_foot();
