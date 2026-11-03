<?php
require_once __DIR__ . '/../includes/admin_ui.php';
$status = isset($_GET['status']) && isset(lead_statuses()[$_GET['status']]) ? $_GET['status'] : '';
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = max(1, get_int('p', 1)); $per = 30;
$where = array(); $par = array();
if ($status !== '') { $where[] = 'status = ?'; $par[] = $status; }
if ($q !== '') { $digits = preg_replace('/\D+/', '', $q); $where[] = '(name LIKE ? OR message LIKE ?' . ($digits !== '' ? ' OR phone LIKE ?' : '') . ')'; $par[] = "%$q%"; $par[] = "%$q%"; if ($digits !== '') $par[] = "%$digits%"; }
$w = $where ? ' WHERE ' . implode(' AND ', $where) : '';
$total = (int)db_val('SELECT COUNT(*) FROM leads' . $w, $par);
$rows = db_all('SELECT l.*, u.id AS uid FROM leads l LEFT JOIN users u ON u.id = l.user_id' . str_replace(array('status', 'name', 'message', 'phone'), array('l.status', 'l.name', 'l.message', 'l.phone'), $w) . ' ORDER BY l.created_at DESC LIMIT ' . $per . ' OFFSET ' . (($page - 1) * $per), $par);
adm_head('Заявки', 'leads');
?>
<form class="adm-filters" method="get">
    <select name="status" aria-label="Статус"><option value="">Все статусы</option><?php foreach (lead_statuses() as $k => $l): ?><option value="<?= h($k) ?>"<?= $k === $status ? ' selected' : '' ?>><?= h($l) ?></option><?php endforeach; ?></select>
    <input type="search" name="q" value="<?= h($q) ?>" placeholder="Имя, телефон, текст" aria-label="Поиск">
    <button class="knopka knopka--ramka" type="submit">Найти</button>
    <span class="melko-t">Найдено: <?= $total ?></span>
</form>
<div class="adm-scroll"><table class="atab">
<tr><th>№</th><th>Дата</th><th>Имя</th><th>Телефон</th><th>Источник</th><th>Статус</th><th>Кабинет</th></tr>
<?php foreach ($rows as $l): $p = $l['params'] ? json_decode($l['params'], true) : null; ?>
<tr>
    <td><a href="lead.php?id=<?= (int)$l['id'] ?>"><?= (int)$l['id'] ?></a></td>
    <td><?= h(dt_ru($l['created_at'])) ?></td>
    <td><?= h($l['name']) ?></td>
    <td><a href="tel:+<?= h($l['phone']) ?>"><?= h(fmt_phone($l['phone'])) ?></a></td>
    <td><?= h($l['source']) ?><?= $p ? '<br><span class="melko-t">≈ ' . h(rub($p['total'])) . '</span>' : '' ?></td>
    <td><span class="st st--<?= h($l['status']) ?>"><?= h(lead_statuses()[$l['status']]) ?></span></td>
    <td><?= $l['uid'] ? '<a href="client.php?id=' . (int)$l['uid'] . '">есть</a>' : '—' ?></td>
</tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="7" class="melko-t">Ничего не найдено</td></tr><?php endif; ?>
</table></div>
<?= pager($total, $per, $page, 'leads.php?status=' . urlencode($status) . '&q=' . urlencode($q)) ?>
<?php adm_foot();
