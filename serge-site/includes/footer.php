<?php
// подвал, внизу подключаются скрипты. если нужен свой скрипт - кладу его имя в $scripts
if (!isset($scripts)) $scripts = array();
?>
<footer class="futer">
    <div class="vnutri">
        <div class="futer__setka">
            <div class="futer__brend">
                <b>СЭРГЭ</b>
                <p>Дом, рассчитанный на Север.</p>
            </div>
            <div class="futer__kol">
                <h4>Разделы</h4>
                <a href="index.php">Главная</a>
                <a href="catalog.php">Каталог проектов</a>
                <a href="calculator.php">Калькулятор стоимости</a>
                <a href="stroitelstvo.php">Строительство</a>
                <a href="company.php">О компании</a>
                <a href="zhurnal.php">Журнал</a>
            </div>
            <div class="futer__kol">
                <h4>Проекты</h4>
                <?php foreach (array_slice($houses, 0, 4) as $h): ?>
                    <a href="house.php?id=<?= e($h['id']) ?>"><?= e($h['name']) ?></a>
                <?php endforeach; ?>
            </div>
            <div class="futer__kol">
                <h4>Контакты</h4>
                <a href="tel:+79999999999">+7 (999) 999-99-99</a>
                <a href="mailto:info@serge-house.ru">info@serge-house.ru</a>
                <span>г. Якутск, ул. Ленина, 1</span>
                <span>Пн–Сб, 9:00–19:00</span>
            </div>
        </div>
        <div class="futer__niz">
            <span>© СЭРГЭ, 2026</span>
            <span>Контакты — демо-данные. Расчёт стоимости предварительный и не является сметой.</span>
        </div>
    </div>
</footer>

<div class="nizpanel">
    <a class="nizpanel__zvonok" href="tel:+79999999999">Позвонить</a>
    <a class="nizpanel__schet" href="calculator.php">Рассчитать</a>
</div>

<script src="assets/js/main.js" defer></script>
<?php foreach ($scripts as $s): ?>
<script src="assets/js/<?= e($s) ?>" defer></script>
<?php endforeach; ?>
</body>
</html>
