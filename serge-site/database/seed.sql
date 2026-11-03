-- Начальные данные (создано tools/make-seed.php из data/*.json). Импортировать ПОСЛЕ schema.sql.
SET NAMES utf8mb4;

INSERT INTO settings (name, value) VALUES ('area_min', '10') ON DUPLICATE KEY UPDATE value = VALUES(value);
INSERT INTO settings (name, value) VALUES ('area_max', '1000') ON DUPLICATE KEY UPDATE value = VALUES(value);
INSERT INTO settings (name, value) VALUES ('floors_max', '3') ON DUPLICATE KEY UPDATE value = VALUES(value);
INSERT INTO settings (name, value) VALUES ('delta', '5') ON DUPLICATE KEY UPDATE value = VALUES(value);
INSERT INTO settings (name, value) VALUES ('round_step', '100') ON DUPLICATE KEY UPDATE value = VALUES(value);
INSERT INTO settings (name, value) VALUES ('k_reg', '1') ON DUPLICATE KEY UPDATE value = VALUES(value);
INSERT INTO settings (name, value) VALUES ('k_floors_many', '1.15') ON DUPLICATE KEY UPDATE value = VALUES(value);
INSERT INTO settings (name, value) VALUES ('date', '01.10.2023') ON DUPLICATE KEY UPDATE value = VALUES(value);

INSERT INTO materials (code, name, price, sort_order) VALUES ('gazobeton', 'Газобетон', 35000, 1) ON DUPLICATE KEY UPDATE name = VALUES(name), price = VALUES(price);
INSERT INTO materials (code, name, price, sort_order) VALUES ('brus', 'Брус', 45000, 2) ON DUPLICATE KEY UPDATE name = VALUES(name), price = VALUES(price);
INSERT INTO foundations (code, name, price, sort_order) VALUES ('svai', 'Свайно-ростверковый', 15000, 1) ON DUPLICATE KEY UPDATE name = VALUES(name), price = VALUES(price);
INSERT INTO foundations (code, name, price, sort_order) VALUES ('lenta', 'Ленточный', 12000, 2) ON DUPLICATE KEY UPDATE name = VALUES(name), price = VALUES(price);

INSERT INTO houses (id, name, area, floors, bedrooms, bathrooms, material_code, foundation_code, garage, terrace, description, sort_order) VALUES ('serge-120', 'СЭРГЭ 120', 120, 2, 3, 2, 'gazobeton', 'svai', 0, 0, 'Современный семейный дом для северного климата.', 1) ON DUPLICATE KEY UPDATE name = VALUES(name);
INSERT INTO houses (id, name, area, floors, bedrooms, bathrooms, material_code, foundation_code, garage, terrace, description, sort_order) VALUES ('serge-90', 'СЭРГЭ 90', 90, 1, 2, 1, 'brus', 'svai', 0, 0, 'Компактный одноэтажный дом: тепло, просто и без лишних метров.', 2) ON DUPLICATE KEY UPDATE name = VALUES(name);
INSERT INTO houses (id, name, area, floors, bedrooms, bathrooms, material_code, foundation_code, garage, terrace, description, sort_order) VALUES ('serge-160', 'СЭРГЭ 160', 160, 2, 4, 2, 'brus', 'svai', 0, 1, 'Просторный дом с большой гостиной и панорамными окнами.', 3) ON DUPLICATE KEY UPDATE name = VALUES(name);
INSERT INTO houses (id, name, area, floors, bedrooms, bathrooms, material_code, foundation_code, garage, terrace, description, sort_order) VALUES ('serge-140', 'СЭРГЭ 140', 140, 2, 3, 2, 'gazobeton', 'lenta', 1, 0, 'Двухэтажный дом с кабинетом и отдельной котельной.', 4) ON DUPLICATE KEY UPDATE name = VALUES(name);
INSERT INTO houses (id, name, area, floors, bedrooms, bathrooms, material_code, foundation_code, garage, terrace, description, sort_order) VALUES ('serge-100', 'СЭРГЭ 100', 100, 1, 3, 1, 'gazobeton', 'lenta', 0, 0, 'Одноэтажный дом на три спальни для небольшого участка.', 5) ON DUPLICATE KEY UPDATE name = VALUES(name);
INSERT INTO houses (id, name, area, floors, bedrooms, bathrooms, material_code, foundation_code, garage, terrace, description, sort_order) VALUES ('serge-200', 'СЭРГЭ 200', 200, 2, 5, 3, 'brus', 'svai', 1, 1, 'Большой дом для большой семьи, с террасой и местом под гараж.', 6) ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO stages (id, title, short, text, time, sort_order) VALUES (1, 'Консультация', 'Обсуждаем участок, бюджет и то, что вы хотите получить в итоге.', 'Встречаемся в офисе или на участке, обсуждаем состав семьи, желаемую площадь, бюджет и сроки. Подбираем подходящие проекты из каталога и делаем первый предварительный расчёт.', '1–3 дня', 1) ON DUPLICATE KEY UPDATE title = VALUES(title);
INSERT INTO stages (id, title, short, text, time, sort_order) VALUES (2, 'Проектирование', 'Делаем планировку и конструктив с учётом климата и грунта.', 'Адаптируем типовой проект или разрабатываем индивидуальный: планировки, фасады, конструктивные решения, теплотехнический расчёт стен и кровли.', '3–6 недель', 2) ON DUPLICATE KEY UPDATE title = VALUES(title);
INSERT INTO stages (id, title, short, text, time, sort_order) VALUES (3, 'Расчёт', 'Считаем предварительную стоимость, потом уточняем по смете.', 'По готовому проекту составляем смету по видам работ и материалам, фиксируем цену и график в договоре.', '3–5 дней', 3) ON DUPLICATE KEY UPDATE title = VALUES(title);
INSERT INTO stages (id, title, short, text, time, sort_order) VALUES (4, 'Подготовка участка', 'Геология, разметка, подъезд для техники.', 'Проводим инженерно-геологические изыскания, выносим оси дома на местность, организуем подъезд для техники и площадку для материалов.', '1–2 недели', 4) ON DUPLICATE KEY UPDATE title = VALUES(title);
INSERT INTO stages (id, title, short, text, time, sort_order) VALUES (5, 'Фундамент', 'Основание подбираем под участок, а не по шаблону.', 'Устраиваем свайно-ростверковый или ленточный фундамент — по результатам изысканий. На мерзлоте оставляем проветриваемое подполье, чтобы не прогревать грунт.', '2–4 недели', 5) ON DUPLICATE KEY UPDATE title = VALUES(title);
INSERT INTO stages (id, title, short, text, time, sort_order) VALUES (6, 'Дом', 'Стены, перекрытия и кровля — коробка дома.', 'Возводим стены из бруса или газобетона, перекрытия и кровлю, ставим окна и входные двери. Задача — закрыть тепловой контур до осенних дождей.', '1,5–3 месяца', 6) ON DUPLICATE KEY UPDATE title = VALUES(title);
INSERT INTO stages (id, title, short, text, time, sort_order) VALUES (7, 'Инженерия и отделка', 'Отопление, электрика, вентиляция, чистовая отделка.', 'Монтируем отопление, водоснабжение, канализацию, электрику и вентиляцию с рекуперацией, затем выполняем черновую и чистовую отделку.', '2–3 месяца', 7) ON DUPLICATE KEY UPDATE title = VALUES(title);
INSERT INTO stages (id, title, short, text, time, sort_order) VALUES (8, 'Ключи', 'Принимаем работу вместе с вами и передаём дом.', 'Проходим дом вместе с вами по чек-листу, передаём исполнительную документацию, инструкции по инженерным системам и ключи. Начинается гарантийный срок.', '1 день', 8) ON DUPLICATE KEY UPDATE title = VALUES(title);

INSERT INTO journal_posts (slug, title, preview, image, category, read_min, body, published_at) VALUES ('fundament-na-merzlote', 'Фундамент на вечной мерзлоте: сваи или лента', 'Почему в Якутии чаще строят на сваях и в каких случаях можно обойтись ленточным фундаментом.', 'assets/img/fund-svai.webp', 'Фундамент', 5, '## Главное правило — не греть грунт

Большая часть Якутии лежит в зоне многолетнемёрзлых грунтов. Пока грунт под домом остаётся мёрзлым, он прочный и надёжно держит нагрузку. Проблемы начинаются, когда тепло от дома подтапливает мерзлоту: основание начинает проседать неравномерно, по стенам идут трещины, перекашиваются окна и двери.

Поэтому задача фундамента на Севере — не только выдержать вес дома, но и сохранить грунт в мёрзлом состоянии. Отсюда главный приём: дом поднимают над землёй и оставляют под ним проветриваемое подполье.

## Свайно-ростверковый фундамент

Сваи погружают ниже слоя сезонного оттаивания, а сверху их объединяет ростверк — обвязка, на которой стоит дом. Между полом и землёй остаётся воздушный зазор, зимой его продувает морозным воздухом, и грунт не прогревается.

Это основной вариант для участков с мерзлотой, пучинистыми и влажными грунтами. В калькуляторе СЭРГЭ он стоит 15 000 ₽ за м² площади застройки (демо-расценка).

## Ленточный фундамент

Лента — это бетонная полоса по периметру и под несущими стенами. Она проще и дешевле в устройстве (12 000 ₽ за м² в калькуляторе), но подходит только для участков, где мерзлоты под домом нет или она залегает глубоко, а грунт непучинистый: например, песчаные возвышенности.

## Как выбрать

Тип фундамента определяют по результатам инженерно-геологических изысканий — бурения и анализа грунта на конкретном участке. Без изысканий выбирать фундамент «как у соседа» рискованно: условия могут отличаться даже на соседних участках.

На сайте можно сравнить стоимость обоих вариантов в калькуляторе — достаточно переключить тип фундамента и пересчитать.', '2026-09-14') ON DUPLICATE KEY UPDATE title = VALUES(title);
INSERT INTO journal_posts (slug, title, preview, image, category, read_min, body, published_at) VALUES ('gazobeton-ili-brus', 'Газобетон или брус: что выбрать для дома на Севере', 'Сравниваем два популярных материала стен по теплу, срокам строительства, усадке и цене.', 'assets/img/serge-160-1.webp', 'Материалы', 6, '## Брус

Деревянный дом — традиционный выбор для Якутии. Дерево хорошо держит тепло, дом быстро прогревается и в нём легко дышится. Клеёный и профилированный брус собирается быстро, коробку можно поставить за один сезон.

Минусы: дерево даёт усадку, поэтому часть отделочных работ откладывают, а окна и двери ставят с компенсационными зазорами. Нужна обработка от гниения и огнезащита. В калькуляторе стены из бруса считаются по 45 000 ₽ за м² площади дома.

## Газобетон

Газобетонные блоки — лёгкий каменный материал с хорошими теплоизоляционными свойствами. Не горит, не гниёт, почти не даёт усадки, поэтому отделку можно делать сразу после возведения коробки.

Для северного климата стены из газобетона дополнительно утепляют снаружи и защищают от влаги: материал впитывает воду, а в морозы это опасно. Блоки нужно класть на клей тонким швом, иначе швы становятся мостиками холода. В калькуляторе — 35 000 ₽ за м².

## Коротко

Хотите тёплый дом быстро и любите дерево — выбирайте брус. Нужен долговечный, негорючий дом без усадки — газобетон с утеплением. В обоих случаях решающими будут качество утепления, окон и вентиляции, а не только материал стен.', '2026-08-27') ON DUPLICATE KEY UPDATE title = VALUES(title);
INSERT INTO journal_posts (slug, title, preview, image, category, read_min, body, published_at) VALUES ('uteplenie-pri-minus-50', 'Утепление дома, рассчитанное на −50 °C', 'Где дом теряет больше всего тепла и на чём нельзя экономить при строительстве в Якутии.', 'assets/img/teplo.webp', 'Тепло', 4, '## Куда уходит тепло

Зимой разница температур между домом и улицей в Якутии может превышать 70 градусов. При таком перепаде любая щель или тонкое место в утеплении становится заметной утечкой тепла. Больше всего тепла теряется через кровлю, окна, пол над подпольем и узлы примыкания — места, где стена встречается с перекрытием, окном или фундаментом.

## Что важно

Утеплитель в стенах, кровле и полу подбирают по теплотехническому расчёту для климатической зоны, а не «на глаз». Окна — с многокамерным стеклопакетом и тёплым монтажом. Пол над проветриваемым подпольем утепляют не хуже кровли: снизу его постоянно обдувает морозный воздух.

Отдельное внимание — пароизоляции. Тёплый влажный воздух из дома не должен попадать в утеплитель, иначе влага замёрзнет внутри стены и утеплитель перестанет работать.

## Вентиляция

Плотный тёплый дом без вентиляции быстро становится душным и сырым. Чтобы не выпускать тепло через открытые форточки, ставят приточно-вытяжную вентиляцию с рекуперацией: уходящий воздух подогревает входящий.', '2026-08-05') ON DUPLICATE KEY UPDATE title = VALUES(title);
INSERT INTO journal_posts (slug, title, preview, image, category, read_min, body, published_at) VALUES ('kogda-nachinat-stroitelstvo', 'Когда начинать строительство: календарь стройки в Якутии', 'Короткое лето определяет весь график. Рассказываем, что делать зимой, а что — только в тёплый сезон.', 'assets/img/hero-t.webp', 'Планирование', 4, '## Зима — время проекта

Строительный сезон в Якутии короткий, поэтому к весне всё должно быть готово: проект, расчёт, договор, заказ материалов. Зиму удобно использовать для проектирования и согласований — тогда летом бригада выходит на участок без простоев.

## Весна и лето — фундамент и коробка

Изыскания и фундамент делают после схода снега. Сваи на мерзлоте часто погружают в конце зимы или ранней весной, пока грунт мёрзлый и по участку может пройти техника. Летом возводят коробку дома и кровлю — к осенним дождям дом должен быть под крышей.

## Осень и зима — инженерия и отделка

Когда контур закрыт, можно работать внутри: прокладывать инженерные сети, запускать отопление и вести отделку уже в тёплом доме.

## Сколько это занимает

Дом площадью 100–150 м² реально построить за 6–10 месяцев от начала проектирования. Точные сроки зависят от участка, материала стен и того, насколько заранее заказаны материалы.', '2026-07-18') ON DUPLICATE KEY UPDATE title = VALUES(title);

INSERT INTO company_info (k, v) VALUES ('name', '"СЭРГЭ"') ON DUPLICATE KEY UPDATE v = VALUES(v);
INSERT INTO company_info (k, v) VALUES ('about', '["СЭРГЭ — якутская компания, которая проектирует и строит частные дома для жизни в северном климате. Мы ведём объект от первой встречи до передачи ключей: проект, расчёт, фундамент, коробка, инженерия и отделка.","Название компании — от сэргэ, традиционной якутской коновязи. Сэргэ ставили у дома как символ основательности, рода и прочной связи с землёй. Так мы и строим: надолго и с уважением к месту."]') ON DUPLICATE KEY UPDATE v = VALUES(v);
INSERT INTO company_info (k, v) VALUES ('figures', '[{"value":"120+","label":"домов построено"},{"value":"12 лет","label":"в строительстве"},{"value":"5 лет","label":"гарантии на конструктив"},{"value":"45","label":"специалистов в штате"}]') ON DUPLICATE KEY UPDATE v = VALUES(v);
INSERT INTO company_info (k, v) VALUES ('principles', '[{"title":"Под участок, а не по шаблону","text":"Фундамент и конструктив подбираем по результатам изысканий конкретного участка."},{"title":"Понятная цена","text":"Предварительная стоимость считается по открытой формуле, смета — по видам работ."},{"title":"Прозрачная стройка","text":"Фотоотчёты по каждому этапу и личный кабинет с ходом строительства."},{"title":"Своя бригада","text":"Работы ведут штатные специалисты, а не случайные субподрядчики."}]') ON DUPLICATE KEY UPDATE v = VALUES(v);
INSERT INTO company_info (k, v) VALUES ('team', '[{"name":"Николай Саввинов","role":"Руководитель компании"},{"name":"Мария Егорова","role":"Главный архитектор"},{"name":"Айсен Петров","role":"Инженер-конструктор"},{"name":"Ольга Никифорова","role":"Руководитель проектов"}]') ON DUPLICATE KEY UPDATE v = VALUES(v);
INSERT INTO company_info (k, v) VALUES ('contacts', '{"phone":"+7 (999) 999-99-99","phone_href":"+79999999999","email":"info@serge-house.ru","address":"г. Якутск, ул. Ленина, 1","hours":"Пн–Сб, 9:00–19:00","requisites":"ООО «СЭРГЭ», ИНН 0000000000, ОГРН 0000000000000"}') ON DUPLICATE KEY UPDATE v = VALUES(v);
INSERT INTO company_info (k, v) VALUES ('demo_note', '"Цифры, сотрудники, контакты и реквизиты на этой странице — демонстрационные данные учебного проекта."') ON DUPLICATE KEY UPDATE v = VALUES(v);
