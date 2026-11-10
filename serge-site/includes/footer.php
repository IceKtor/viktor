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
                <div class="futer__zag">Разделы</div>
                <a href="index.php">Главная</a>
                <a href="catalog.php">Каталог проектов</a>
                <a href="calculator.php">Калькулятор стоимости</a>
                <a href="stroitelstvo.php">Строительство</a>
                <a href="company.php">О компании</a>
                <a href="zhurnal.php">Журнал</a>
                <a href="tarify.php">Тарифы</a>
            </div>
            <div class="futer__kol">
                <div class="futer__zag">Проекты</div>
                <?php foreach (array_slice($houses, 0, 4) as $h): ?>
                    <a href="house.php?id=<?= e($h['id']) ?>"><?= e($h['name']) ?></a>
                <?php endforeach; ?>
            </div>
            <div class="futer__kol">
                <div class="futer__zag">Контакты</div>
                <a href="tel:<?= e(site_contacts()["phone_href"]) ?>"><?= e(site_contacts()["phone"]) ?></a>
                <a href="mailto:<?= e(site_contacts()["email"]) ?>"><?= e(site_contacts()["email"]) ?></a>
                <span><?= e(site_contacts()["address"]) ?></span>
                <span><?= e(site_contacts()["hours"]) ?></span>
                <?php if (ACCOUNTS_ENABLED): ?><a href="zayavka.php">Оставить заявку</a><?php endif; ?>
            </div>
        </div>
        <div class="futer__niz">
            <span class="futer__levo">© СЭРГЭ, 2026 <button class="tema" id="tema" type="button" aria-pressed="false"><svg class="tema__luna" viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true"><path d="M20 14.5A8 8 0 0 1 9.5 4a8 8 0 1 0 10.5 10.5z"/></svg><svg class="tema__solnce" viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2.5v2M12 19.5v2M4.6 4.6l1.4 1.4M18 18l1.4 1.4M2.5 12h2M19.5 12h2M4.6 19.4L6 18M18 6l1.4-1.4"/></svg><span class="tema__tekst">Тёмная тема</span></button></span>
            <span><?= company_is_demo() ? 'Контакты — демо-данные. ' : '' ?>Расчёт стоимости предварительный и не является сметой.<?php if (ACCOUNTS_ENABLED): ?> <a class="futer__ssylka" href="privacy.php">Политика конфиденциальности</a><?php endif; ?></span>
        </div>
    </div>
</footer>

<button class="naverh" id="naverh" type="button" aria-label="Наверх страницы" title="Наверх"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 19V5M5.5 11.5L12 5l6.5 6.5"/></svg></button>
<div class="nizpanel">
    <a class="nizpanel__zvonok" href="tel:<?= e(site_contacts()["phone_href"]) ?>">Позвонить</a>
    <a class="nizpanel__schet" href="calculator.php">Рассчитать</a>
</div>

<script src="<?= e(asset('assets/js/main.js')) ?>" defer></script>
<script src="<?= e(asset('assets/js/modal.js')) ?>" defer></script>
<?php foreach ($scripts as $s): ?>
<script src="<?= e(asset('assets/js/' . $s)) ?>" defer></script>
<?php endforeach; ?>
</body>
</html>
