<?php
// страница одной статьи журнала: statya.php?id=<slug>
// TODO(DB): repo_journal_post() читает таблицу journal_posts
require_once __DIR__ . '/includes/functions.php';

$id = isset($_GET['id']) ? (string)$_GET['id'] : '';
$s = repo_journal_post($id);
$active = 'journal';

if (!$s) {
    http_response_code(404);
    $page_title = 'Статья не найдена — СЭРГЭ';
    $noindex = true;
    require_once __DIR__ . '/includes/header.php';
    ?>
    <main class="stranica">
        <div class="vnutri nenaiden">
            <h1 class="zag-stranicy">Такой статьи нет</h1>
            <p class="seryj">Возможно, ссылка устарела. Все статьи — в журнале.</p>
            <a class="knopka knopka--cher" href="zhurnal.php">← В журнал</a>
        </div>
    </main>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$page_title = $s['title'] . ' — СЭРГЭ';
$page_desc = $s['preview'];
$og_type = 'article'; $og_image = $s['image']; $ld_extra = article_ld($s);
$crumbs = array(array('Главная', 'index.php'), array('Журнал', 'zhurnal.php'), array($s['title'], 'statya.php?id=' . $s['slug']));
require_once __DIR__ . '/includes/header.php';

$drugie = array();
foreach (repo_journal_posts() as $p) if ($p['slug'] !== $s['slug']) $drugie[] = $p;
$drugie = array_slice($drugie, 0, 3);
?>

<main class="stranica">
    <article class="vnutri vnutri--uzko statya">
        <a class="nazad" href="zhurnal.php">← Все статьи</a>
        <div class="statya__meta"><span class="metka"><?= e($s['category']) ?></span><?= data_ru($s['published_at']) ?> · <?= (int)$s['read_min'] ?> мин чтения</div>
        <h1 class="zag-stranicy zag-stranicy--dom"><?= e($s['title']) ?></h1>
        <p class="statya__lid"><?= e($s['preview']) ?></p>
        <img class="statya__oblozhka" src="<?= e($s['image']) ?>" alt="" width="1000" height="750">
        <div class="statya__tekst">
            <?php foreach ($s['body'] as $abz): ?>
                <?php if (strpos($abz, '## ') === 0): ?>
                <h2><?= e(substr($abz, 3)) ?></h2>
                <?php else: ?>
                <p><?= e($abz) ?></p>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <div class="kartochka-prizyv">
            <div>
                <h2 class="h2--malenkij">Сколько будет стоить ваш дом?</h2>
                <p>Сравните материалы и фундаменты в калькуляторе.</p>
            </div>
            <div class="knopki">
                <a class="knopka knopka--derevo" href="calculator.php">Рассчитать стоимость</a>
            </div>
        </div>
    </article>

    <?php if ($drugie): ?>
    <section class="blok blok--vnutr">
        <div class="vnutri">
            <div class="golova">
                <h2 class="h2--malenkij">Читайте также</h2>
                <a class="knopka knopka--ramka" href="zhurnal.php">Весь журнал</a>
            </div>
            <div class="stati">
                <?php foreach ($drugie as $p) echo karto4ka_stati($p, 3); ?>
            </div>
        </div>
    </section>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
