-- ЗАГОТОВКА схемы БД. Сайт её пока НЕ использует (USE_DB = false в includes/config.php).
-- Когда будем делать базу: создать БД serge в phpMyAdmin (utf8mb4_unicode_ci), импортировать этот файл,
-- перенести данные из data/*.json и переписать функции в includes/repository.php (искать TODO(DB)).
-- Типы и поля предварительные, перед использованием сверить с data/houses.json и data/rates.json.

CREATE TABLE IF NOT EXISTS settings (
    name  VARCHAR(50) PRIMARY KEY,      -- area_min, area_max, floors_max, delta, round_step, k_reg, k_floors_many, date
    value VARCHAR(50) NOT NULL
);

CREATE TABLE IF NOT EXISTS materials (
    code  VARCHAR(30) PRIMARY KEY,      -- gazobeton, brus
    name  VARCHAR(60) NOT NULL,
    price INT NOT NULL                  -- руб./м2
);

CREATE TABLE IF NOT EXISTS foundations (
    code  VARCHAR(30) PRIMARY KEY,      -- svai, lenta
    name  VARCHAR(60) NOT NULL,
    price INT NOT NULL                  -- руб./м2 площади застройки
);

CREATE TABLE IF NOT EXISTS houses (
    id            VARCHAR(30) PRIMARY KEY,   -- serge-120
    name          VARCHAR(60) NOT NULL,
    area          INT NOT NULL,
    floors        TINYINT NOT NULL,
    bedrooms      TINYINT NOT NULL,
    bathrooms     TINYINT NOT NULL,
    material_code VARCHAR(30) NOT NULL,
    foundation_code VARCHAR(30) NOT NULL,
    garage        TINYINT(1) NOT NULL DEFAULT 0,
    terrace       TINYINT(1) NOT NULL DEFAULT 0,
    description   TEXT,
    sort_order    INT NOT NULL DEFAULT 0,
    FOREIGN KEY (material_code) REFERENCES materials(code),
    FOREIGN KEY (foundation_code) REFERENCES foundations(code)
);

CREATE TABLE IF NOT EXISTS journal_posts (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    slug         VARCHAR(100) NOT NULL UNIQUE,
    title        VARCHAR(200) NOT NULL,
    preview      TEXT,
    body         MEDIUMTEXT,
    published_at DATETIME,
    is_published TINYINT(1) NOT NULL DEFAULT 0
);

-- TODO(DB): таблицы company_info и leads добавить, когда решим про страницу "О компании" и форму заявки (ПДн!)
