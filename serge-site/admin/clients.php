<?php
require_once __DIR__ . '/../includes/admin_ui.php';
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = max(1, get_int('p', 1)); $per = 30;
$w = " WHERE role = 'client'"; $par = array();
if ($q !== '') { $d = preg_replace('/\D+/', '', $q); $w .= ' AND (name LIKE ? OR email LIKE ?' . ($d !== '' ? ' OR phone LIKE ?' : '') . ')'; $par[] = "%$q%"; $par[] = "%$q%"; if ($d !== '') $par[] = "%$d%"; }
$total = (int)db_val('SELECT COUNT(*) FROM users' . $w, $par);
$rows = db_all('SELECT u.*, (SELECT COUNT(*) FROM objects o WHERE o.user_id = u.id) AS objs FROM users u' . $w . ' ORDER BY created_at DESC LIMIT ' . $per . ' OFFSET ' . (($page - 1) * $per), $par);
adm_head('Клиенты', 'clients');
?>
<form class="adm-filters" method="get">
    <input type="search" name="q" value="<?= h($q) ?>" placeholder="Имя, телефон, почта" aria-label="Поиск">
    <button class="knopka knopka--ramka" type="submit">Найти</button>
    <a class="knopka knopka--derevo" href="client.php">+ Новый клиент</a>
    <span class="melko-t">Всего: <?= $total ?></span>
</form>
<div class="adm-scroll"><table class="atab">
<tr><th>Имя</th><th>Телефон</th><th>Почта</th><th>Создан</th><th>Объектов</th><th>Последний вход</th><th></th></tr>
<?php foreach ($rows as $u): ?>
<tr><td><a href="client.php?id=<?= (int)$u['id'] ?>"><?= h($u['name']) ?></a></td><td><?= h($u['phone'] ? fmt_phone($u['phone']) : '—') ?></td><td><?= h($u['email'] ?: '—') ?></td>
<td><?= h(d_ru($u['created_at'])) ?> <span class="melko-t"><?= $u['created_via'] === 'self' ? 'сам' : 'админ' ?></span></td><td><?= (int)$u['objs'] ?></td>
<td><?= h(dt_ru($u['last_login_at'])) ?></td><td><?= $u['is_active'] ? '' : '<span class="st">отключён</span>' ?><?= $u['must_change_password'] ? ' <span class="st st--in_work">временный пароль</span>' : '' ?></td></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="7" class="melko-t">Клиентов нет</td></tr><?php endif; ?>
</table></div>
<?= pager($total, $per, $page, 'clients.php?q=' . urlencode($q)) ?>
<?php adm_foot();
