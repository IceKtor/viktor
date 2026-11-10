<?php
// страница "Журнал": список статей
// TODO(DB): статьи из repo_journal_posts() (таблица journal_posts)
$active = 'journal';
$page_title = 'Журнал СЭРГЭ — статьи о строительстве домов на Севере';
$page_desc = 'Статьи о строительстве частных домов в Якутии: фундаменты на мерзлоте, материалы стен, утепление и календарь стройки.';
$crumbs = array(array('Главная', 'index.php'), array('Журнал', 'zhurnal.php'));
require_once __DIR__ . '/includes/header.php';

$posts = repo_journal_posts();
$glavnaya = array_shift($posts);
?>

<main class="stranica">
    <div class="vnutri">
        <h1 class="zag-stranicy">Строим на Севере</h1>
        <p class="seryj seryj--shirokij">Коротко и по делу о фундаментах, материалах, утеплении и планировании стройки в Якутии.</p>

        <?php if ($glavnaya): ?>
        <a class="statya-glavnaya poyavlenie" href="statya.php?id=<?= e($glavnaya['slug']) ?>">
            <div class="statya-glavnaya__foto"><img src="<?= e($glavnaya['image']) ?>" alt="" width="1000" height="750"></div>
            <div class="statya-glavnaya__tekst">
                <div class="statya__meta"><span class="metka"><?= e($glavnaya['category']) ?></span><?= data_ru($glavnaya['published_at']) ?>, <?= (int)$glavnaya['read_min'] ?> мин чтения</div>
                <h2><?= e($glavnaya['title']) ?></h2>
                <p class="seryj"><?= e($glavnaya['preview']) ?></p>
                <span class="ssylka">Читать статью</span>
            </div>
        </a>
        <?php endif; ?>

        <div class="stati poyavlenie">
            <?php foreach ($posts as $p) echo karto4ka_stati($p); ?>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
