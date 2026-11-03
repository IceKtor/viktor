<?php
require_once __DIR__ . '/../includes/admin_ui.php';
$rows = db_all('SELECT a.*, u.name FROM audit_log a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.id DESC LIMIT 200');
adm_head('Журнал действий', 'audit');
?>
<p class="melko">Последние 200 действий администраторов: кто и что менял.</p>
<div class="adm-scroll"><table class="atab"><tr><th>Время</th><th>Кто</th><th>Действие</th><th>Объект</th><th>Подробности</th></tr>
<?php foreach ($rows as $r): ?><tr><td><?= h(dt_ru($r['created_at'])) ?></td><td><?= h($r['name'] ?: '—') ?></td><td><?= h($r['action']) ?></td><td><?= h(trim($r['entity'] . ' ' . $r['entity_id'])) ?></td><td><?= h($r['details']) ?></td></tr><?php endforeach; ?>
</table></div>
<?php adm_foot();
