<?php
require_once __DIR__ . '/../includes/admin_ui.php';
$c = array(
    'new' => (int)db_val("SELECT COUNT(*) FROM leads WHERE status = 'new'"),
    'work' => (int)db_val("SELECT COUNT(*) FROM leads WHERE status = 'in_work'"),
    'clients' => (int)db_val("SELECT COUNT(*) FROM users WHERE role = 'client'"),
    'objects' => (int)db_val("SELECT COUNT(*) FROM objects WHERE status <> 'done'"),
    'houses' => (int)db_val('SELECT COUNT(*) FROM houses WHERE is_published = 1'),
);
$last = db_all('SELECT * FROM leads ORDER BY created_at DESC LIMIT 8');
$rates = repo_rates();
adm_head('Обзор', 'index');
?>
<div class="adm-grid">
    <a class="adm-stat" href="leads.php?status=new"><b><?= $c['new'] ?></b><span>новых заявок</span></a>
    <a class="adm-stat" href="leads.php?status=in_work"><b><?= $c['work'] ?></b><span>заявок в работе</span></a>
    <a class="adm-stat" href="clients.php"><b><?= $c['clients'] ?></b><span>клиентов с аккаунтом</span></a>
    <a class="adm-stat" href="objects.php"><b><?= $c['objects'] ?></b><span>объектов в работе</span></a>
    <a class="adm-stat" href="houses.php"><b><?= $c['houses'] ?></b><span>проектов на сайте</span></a>
</div>
<h2>Последние заявки</h2>
<?php if (!$last): ?><p class="seryj">Заявок пока нет.</p><?php else: ?>
<div class="adm-scroll"><table class="atab"><tr><th>№</th><th>Дата</th><th>Имя</th><th>Телефон</th><th>Статус</th></tr>
<?php foreach ($last as $l): ?><tr><td><a href="lead.php?id=<?= (int)$l['id'] ?>"><?= (int)$l['id'] ?></a></td><td><?= h(dt_ru($l['created_at'])) ?></td><td><?= h($l['name']) ?></td><td><?= h(fmt_phone($l['phone'])) ?></td><td><span class="st st--<?= h($l['status']) ?>"><?= h(lead_statuses()[$l['status']]) ?></span></td></tr><?php endforeach; ?>
</table></div>
<?php endif; ?>
<h2>Быстрые действия</h2>
<div class="adm-actions">
    <a class="knopka knopka--ramka" href="house.php">+ Проект дома</a>
    <a class="knopka knopka--ramka" href="post.php">+ Статья журнала</a>
    <a class="knopka knopka--ramka" href="object.php">+ Объект стройки</a>
    <a class="knopka knopka--ramka" href="rates.php">Расценки (на <?= h($rates['date']) ?>)</a>
</div>
<?php adm_foot();
