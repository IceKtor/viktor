СЭРГЭ — сайт на php/html/js/css

Как запустить в Open Server Panel:
1. Положить папку serge-site в OpenServer\domains\  (получится OpenServer\domains\serge-site)
2. Запустить Open Server, в меню "Перезапустить"
3. Открыть в браузере http://serge-site/
   (PHP версия 7.4 или выше, БД не нужна)

Страницы: index.php, catalog.php, house.php?id=..., calculator.php
Расценки и дома лежат в data/rates.json и data/houses.json - там можно менять цифры.
Расчёт считает api/calc.php (функции в includes/functions.php).
Телефон, адрес и цифры на главной - демо, заменить на настоящие.

---- Что добавлено по макету и ТЗ ----
Меню как в макете: Дома, Строительство, Проекты, О компании, Журнал. Страницы stroitelstvo.php, company.php, zhurnal.php - пока заглушки.
Каталог: фильтры по этажности, спальням, материалу, фундаменту, цене, гаражу и террасе + сортировка.
404.php, robots.txt и sitemap.xml (через .htaccess -> robots.php и sitemap.php), canonical, Schema.org хлебные крошки.
Для 404 и sitemap в Open Server должен работать mod_rewrite (по умолчанию включён).

---- План: база данных (потом) ----
Сейчас все данные берутся из data/*.json через includes/repository.php.
Места под базу помечены TODO(DB): grep -rn "TODO(DB)" .
Что есть: includes/config.php (USE_DB = false, доступы), includes/db.php (PDO, закомментирован),
database/schema.sql (заготовка таблиц для phpMyAdmin, не используется).
Когда будем делать БД: создать базу, импортировать schema.sql, перенести данные из JSON,
написать SQL в функциях repository.php, поставить USE_DB = true.
