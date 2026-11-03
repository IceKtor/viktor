<?php
require_once __DIR__ . '/../includes/admin_ui.php';
$houses = repo_houses(true); $rates = repo_rates();
adm_head('Проекты домов', 'houses');
?>
<p><a class="knopka knopka--derevo" href="house.php">+ Новый проект</a> <span class="melko-t">Цена «от» считается автоматически по расценкам калькулятора.</span></p>
<div class="adm-scroll"><table class="atab">
<tr><th>Проект</th><th>Площадь</th><th>Этажей</th><th>Спален</th><th>Материал</th><th>Фундамент</th><th>Цена «от»</th><th>На сайте</th></tr>
<?php foreach ($houses as $x):
    $price = (isset($rates['materials'][$x['material']]) && isset($rates['foundations'][$x['foundation']])) ? rub(raschet($x['area'], $x['floors'], $x['material'], $x['foundation'], $rates)['total']) : '—'; ?>
<tr><td><a href="house.php?id=<?= h($x['id']) ?>"><?= h($x['name']) ?></a></td><td><?= (int)$x['area'] ?> м²</td><td><?= (int)$x['floors'] ?></td><td><?= (int)$x['bedrooms'] ?></td>
<td><?= h(isset($rates['materials'][$x['material']]) ? $rates['materials'][$x['material']]['name'] : $x['material']) ?></td>
<td><?= h(isset($rates['foundations'][$x['foundation']]) ? $rates['foundations'][$x['foundation']]['name'] : $x['foundation']) ?></td><td><?= h($price) ?></td>
<td><?php $pub = db_val('SELECT is_published FROM houses WHERE id = ?', array($x['id'])); echo $pub ? 'да' : '<span class="st">скрыт</span>'; ?></td></tr>
<?php endforeach; ?>
</table></div>
<?php adm_foot();
