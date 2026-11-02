<?php
require_once __DIR__ . '/includes/functions.php';

$id = isset($_GET['id']) ? $_GET['id'] : '';
$h = naiti_dom($id);

$active = 'catalog';

if (!$h) {
    http_response_code(404);
    $page_title = 'Проект не найден — СЭРГЭ';
    $noindex = true;
    require_once 'includes/header.php';
    ?>
    <main class="stranica">
        <div class="vnutri nenaiden">
            <h1 class="zag-stranicy">Такого проекта нет</h1>
            <p class="seryj">Возможно, ссылка устарела. Посмотрите все проекты в каталоге.</p>
            <a class="knopka knopka--cher" href="catalog.php">← Вернуться в каталог</a>
        </div>
    </main>
    <?php
    require_once 'includes/footer.php';
    exit;
}

$page_title = $h['name'] . ' — проект дома, ' . $h['area'] . ' м² — СЭРГЭ';
$page_desc = $h['description'] . ' Площадь ' . $h['area'] . ' м², ' . floors_text($h['floors']) . '.';
$crumbs = array(array('Главная', 'index.php'), array('Проекты домов', 'catalog.php'), array($h['name'], 'house.php?id=' . $h['id']));
require_once 'includes/header.php';
$scripts = array('carousel.js');

$mat = $rates['materials'][$h['material']]['name'];
$fnd = $rates['foundations'][$h['foundation']]['name'];
$podpisi = array('Фасад', 'Интерьер', 'Планировка');
$ssylka_calc = 'calculator.php?' . http_build_query(array(
    'area' => $h['area'],
    'floors' => $h['floors'],
    'material' => $h['material'],
    'foundation' => $h['foundation']
));
?>

<main class="stranica">
    <div class="vnutri">
        <a class="nazad" href="catalog.php">← Назад к проектам</a>

        <div class="dom-stranica">
            <div class="karusel" id="gallery" tabindex="0" aria-label="Фотографии дома">
                <div class="karusel__okno">
                    <div class="karusel__lenta" id="lenta">
                        <?php foreach ($h['gallery'] as $i => $src): ?>
                        <div class="karusel__slaid">
                            <img src="<?= e($src) ?>" width="800" height="600" alt="<?= e($h['name'] . ' — ' . mb_strtolower($podpisi[$i])) ?>">
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button class="karusel__strelka karusel__strelka--nazad" id="k-nazad" type="button" aria-label="Предыдущее фото">←</button>
                    <button class="karusel__strelka karusel__strelka--vpered" id="k-vpered" type="button" aria-label="Следующее фото">→</button>
                </div>
                <div class="karusel__niz">
                    <div class="karusel__tochki">
                        <?php foreach ($podpisi as $i => $p): ?>
                        <button type="button" class="karusel__tochka<?= $i == 0 ? ' aktiv' : '' ?>" data-i="<?= $i ?>" aria-label="<?= e($p) ?>"></button>
                        <?php endforeach; ?>
                    </div>
                    <div class="karusel__podpis" id="k-podpis"><?= e($podpisi[0]) ?></div>
                </div>
            </div>

            <div class="dom-info">
                <div class="nadpis nadpis--derevo">ПРОЕКТ</div>
                <h1 class="zag-stranicy zag-stranicy--dom"><?= e($h['name']) ?></h1>
                <p class="seryj"><?= e($h['description']) ?></p>
                <div class="dom-cena">от <?= rub($h['price']) ?></div>
                <div class="melko melko--temnyj">Предварительно, по модели калькулятора. Не является сметой.</div>

                <table class="tablica">
                    <tr><td>Общая площадь</td><td><?= (int)$h['area'] ?> м²</td></tr>
                    <tr><td>Этажность</td><td><?= floors_text($h['floors']) ?></td></tr>
                    <tr><td>Спальни</td><td><?= (int)$h['bedrooms'] ?></td></tr>
                    <tr><td>Санузлы</td><td><?= (int)$h['bathrooms'] ?></td></tr>
                    <tr><td>Материал стен</td><td><?= e($mat) ?></td></tr>
                    <tr><td>Тип фундамента</td><td><?= e($fnd) ?></td></tr>
                    <tr><td>Гараж</td><td><?= $h['garage'] ? 'Есть' : 'Нет' ?></td></tr>
                    <tr><td>Терраса</td><td><?= $h['terrace'] ? 'Есть' : 'Нет' ?></td></tr>
                </table>

                <div class="knopki knopki--dom">
                    <a class="knopka knopka--derevo" href="<?= e($ssylka_calc) ?>">Рассчитать для моего участка →</a>
                    <a class="knopka knopka--ramka" href="#gallery" data-slide="2">Получить планировку</a>
                </div>
            </div>
        </div>
    </div>

    <section class="blok blok--verh0 blok--malo">
        <div class="vnutri">
            <div class="golova">
                <h2 class="h2--malenkij">Другие проекты</h2>
                <a class="knopka knopka--ramka" href="catalog.php">Весь каталог →</a>
            </div>
            <div class="doma">
                <?php
                $kol = 0;
                foreach ($houses as $drugoj) {
                    if ($drugoj['id'] === $h['id']) continue;
                    echo karto4ka($drugoj, $rates);
                    $kol++;
                    if ($kol == 3) break;
                }
                ?>
            </div>
        </div>
    </section>
</main>

<?php require_once 'includes/footer.php'; ?>
