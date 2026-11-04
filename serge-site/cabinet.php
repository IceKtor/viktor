<?php
// личный кабинет клиента: ход строительства объектов и статус заявок
require_once __DIR__ . '/includes/account_page.php';
$u = require_login();
if ($u['role'] === 'admin') redirect('admin/');

$stages = repo_stages();
$objects = db_all('SELECT o.*, h.name AS house_name FROM objects o LEFT JOIN houses h ON h.id = o.house_id WHERE o.user_id = ? ORDER BY o.created_at DESC', array($u['id']));
$leads = db_all('SELECT id, source, message, status, created_at, params FROM leads WHERE user_id = ? ORDER BY created_at DESC LIMIT 20', array($u['id']));
$lead_text = array('new' => 'Принята, скоро перезвоним', 'in_work' => 'В работе у менеджера', 'deal' => 'Договор заключён', 'rejected' => 'Закрыта', 'spam' => 'Закрыта');

$page_title = 'Личный кабинет — СЭРГЭ';
$active = '';
require __DIR__ . '/includes/header.php';
?>
<main class="stranica">
    <div class="vnutri">
        <div class="nadpis nadpis--derevo">ЛИЧНЫЙ КАБИНЕТ</div>
        <div class="cab-golova">
            <h1 class="zag-stranicy">Здравствуйте, <?= e($u['name']) ?></h1>
            <div class="cab-deistviya">
                <a class="knopka knopka--ramka" href="profile.php">Профиль</a>
                <form method="post" action="logout.php"><?= csrf_field() ?><button class="knopka knopka--ramka" type="submit">Выйти</button></form>
            </div>
        </div>
        <p class="seryj">Тариф: <?= $u['tariff'] === 'vip' ? 'сопровождение' : 'базовый' ?>. <a href="tarify.php">Что входит в тарифы</a></p>
        <?php foreach (flash_get() as $f): ?><div class="<?= $f[0] === 'ok' ? 'uspeh' : 'podskazka' ?>" data-modal="<?= $f[0] === 'ok' ? 'ok' : ($f[0] === 'err' ? 'err' : 'info') ?>"><?= e($f[1]) ?></div><?php endforeach; ?>

        <h2 class="h2--malenkij cab-zag">Мои объекты</h2>
        <?php if (!$objects): ?>
            <div class="zaglushka">
                <h3 class="h3">Здесь появится ход строительства</h3>
                <p>Когда вы заключите договор, менеджер добавит ваш дом в кабинет: вы увидите текущий этап, процент готовности и фотоотчёты. Пока можно посчитать стоимость или оставить заявку.</p>
                <div class="knopki"><a class="knopka knopka--cher" href="calculator.php">Рассчитать стоимость →</a><a class="knopka knopka--ramka" href="zayavka.php">Оставить заявку</a></div>
            </div>
        <?php endif; ?>

        <?php foreach ($objects as $o):
            $ups = db_all('SELECT u.*, s.title AS stage_title FROM object_updates u LEFT JOIN stages s ON s.id = u.stage_id WHERE u.object_id = ? AND u.is_visible = 1 ORDER BY u.created_at DESC, u.id DESC', array($o['id']));
            $cur = -1; foreach ($stages as $i => $st) if ((int)$st['id'] === (int)$o['current_stage_id']) $cur = $i;
            $os = object_statuses();
        ?>
        <section class="panel cab-obekt">
            <div class="panel__verh">
                <div>
                    <div class="melko">Проект №<?= e($o['code']) ?> · <?= e($os[$o['status']]) ?></div>
                    <div class="etap cab-nazvanie"><?= e($o['title']) ?></div>
                    <?php if ($o['address']): ?><div class="melko"><?= e($o['address']) ?></div><?php endif; ?>
                </div>
                <div class="panel__pravo">
                    <div class="procent"><?= (int)$o['progress'] ?>%</div>
                    <div class="melko"><?= $o['status'] === 'done' ? 'Дом сдан' : ($cur >= 0 ? 'Текущий этап' : '') ?></div>
                    <?php if ($cur >= 0 && $o['status'] !== 'done'): ?><div class="etap"><?= e($stages[$cur]['title']) ?></div><?php endif; ?>
                </div>
            </div>
            <div class="polosa"><div class="polosa__vnutri" style="width: <?= max(0, min(100, (int)$o['progress'])) ?>%"></div></div>
            <div class="chipy">
                <?php foreach ($stages as $i => $st):
                    $cls = ($o['status'] === 'done' || ($cur >= 0 && $i < $cur)) ? 'chip chip--ok' : ($i === $cur ? 'chip chip--seichas' : 'chip'); ?>
                    <span class="<?= $cls ?>"><?= $cls === 'chip chip--ok' ? '✓ ' : '' ?><?= e($st['title']) ?></span>
                <?php endforeach; ?>
            </div>
            <dl class="cab-fakty">
                <?php if ($o['contract_no']): ?><div><dt>Договор</dt><dd>№ <?= e($o['contract_no']) ?></dd></div><?php endif; ?>
                <?php if ($o['house_name']): ?><div><dt>Проект дома</dt><dd><?= e($o['house_name']) ?></dd></div><?php endif; ?>
                <div><dt>Начало работ</dt><dd><?= e(d_ru($o['started_at'])) ?></dd></div>
                <div><dt>Плановая сдача</dt><dd><?= e(d_ru($o['planned_end'])) ?></dd></div>
            </dl>
            <?php if ($ups): ?>
            <div class="lenta">
                <h3 class="h3">Новости объекта</h3>
                <?php foreach ($ups as $up): ?>
                <article class="lenta__zapis">
                    <?php if ($up['photo']): ?><a href="<?= e($up['photo']) ?>" target="_blank" rel="noopener"><img class="lenta__foto" src="<?= e($up['photo']) ?>" alt="Фото с объекта" width="160" height="120" loading="lazy"></a><?php endif; ?>
                    <div>
                        <div class="melko"><?= e(dt_ru($up['created_at'])) ?><?= $up['stage_title'] ? ' · ' . e($up['stage_title']) : '' ?></div>
                        <div><?= nl2br(e($up['text'])) ?></div>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>
        <?php endforeach; ?>

        <h2 class="h2--malenkij cab-zag">Мои заявки</h2>
        <?php if (!$leads): ?>
            <p class="seryj">Заявок пока нет. <a class="ssylka" href="zayavka.php">Оставить заявку</a></p>
        <?php else: ?>
            <div class="zayavki">
            <?php foreach ($leads as $l): $p = $l['params'] ? json_decode($l['params'], true) : null; ?>
                <div class="zayavka">
                    <div><b>№ <?= (int)$l['id'] ?></b> <span class="melko">от <?= e(dt_ru($l['created_at'])) ?></span></div>
                    <div><?= $p ? 'Расчёт: ≈ ' . e(rub($p['total'])) : e(mb_strimwidth((string)$l['message'], 0, 90, '…')) ?></div>
                    <span class="metka"><?= e($lead_text[$l['status']]) ?></span>
                </div>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
