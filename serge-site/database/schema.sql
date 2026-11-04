-- Схема базы данных «СЭРГЭ». MySQL 5.7+ / MariaDB 10.3+, кодировка utf8mb4.
-- Как развернуть (phpMyAdmin в Open Server):
--   1) Создать базу: «Базы данных» → имя serge, сравнение utf8mb4_unicode_ci.
--   2) Выбрать базу → «Импорт» → файл database/schema.sql (структура), затем database/seed.sql (начальные данные из data/*.json).
--   3) Создать файл includes/config.local.php (образец - config.local.php.example): доступы к базе, USE_DB = true, SETUP_KEY.
--   4) Открыть в браузере /install.php, ввести SETUP_KEY и создать первого администратора.
-- Файл можно импортировать повторно: таблицы создаются только если их нет.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------- содержимое сайта (раньше data/*.json) ----------

CREATE TABLE IF NOT EXISTS settings (
    name  VARCHAR(50) PRIMARY KEY,      -- area_min, area_max, floors_max, delta, round_step, k_reg, k_floors_many, date
    value VARCHAR(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS materials (
    code       VARCHAR(30) PRIMARY KEY,   -- gazobeton, brus
    name       VARCHAR(60) NOT NULL,
    price      INT NOT NULL,              -- руб. за 1 м2 площади дома
    sort_order INT NOT NULL DEFAULT 0,
    is_active  TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS foundations (
    code       VARCHAR(30) PRIMARY KEY,   -- svai, lenta
    name       VARCHAR(60) NOT NULL,
    price      INT NOT NULL,              -- руб. за 1 м2 площади застройки
    sort_order INT NOT NULL DEFAULT 0,
    is_active  TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS houses (
    id              VARCHAR(40) PRIMARY KEY,   -- serge-120 (латиница, цифры, дефис)
    name            VARCHAR(60) NOT NULL,
    area            INT NOT NULL,
    floors          TINYINT NOT NULL,
    bedrooms        TINYINT NOT NULL,
    bathrooms       TINYINT NOT NULL,
    material_code   VARCHAR(30) NOT NULL,
    foundation_code VARCHAR(30) NOT NULL,
    garage          TINYINT(1) NOT NULL DEFAULT 0,
    terrace         TINYINT(1) NOT NULL DEFAULT 0,
    description     TEXT,
    img_facade      VARCHAR(200) NULL,         -- если пусто, берётся assets/img/<id>-1.webp
    img_interior    VARCHAR(200) NULL,         -- assets/img/<id>-2.webp
    img_plan        VARCHAR(200) NULL,         -- assets/img/<id>-3.svg
    sort_order      INT NOT NULL DEFAULT 0,
    is_published    TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT fk_house_material   FOREIGN KEY (material_code)   REFERENCES materials(code)   ON UPDATE CASCADE,
    CONSTRAINT fk_house_foundation FOREIGN KEY (foundation_code) REFERENCES foundations(code) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS stages (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    title      VARCHAR(100) NOT NULL,
    short      VARCHAR(255) NOT NULL,
    text       TEXT,
    time       VARCHAR(40),
    sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS journal_posts (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    slug         VARCHAR(100) NOT NULL UNIQUE,
    title        VARCHAR(200) NOT NULL,
    preview      TEXT,
    image        VARCHAR(200),
    category     VARCHAR(60),
    read_min     TINYINT,
    body         MEDIUMTEXT,                    -- абзацы через пустую строку; строка, начинающаяся с '## ', - подзаголовок
    published_at DATE,
    is_published TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS company_info (
    k VARCHAR(50) PRIMARY KEY,                  -- about, figures, principles, team, contacts, demo_note
    v MEDIUMTEXT NOT NULL                       -- значение в формате JSON
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- пользователи ----------

CREATE TABLE IF NOT EXISTS users (
    id                   INT AUTO_INCREMENT PRIMARY KEY,
    role                 ENUM('client','admin') NOT NULL DEFAULT 'client',
    name                 VARCHAR(100) NOT NULL,
    phone                VARCHAR(15) NULL UNIQUE,      -- только цифры, формат 79991234567
    email                VARCHAR(120) NULL UNIQUE,
    password_hash        VARCHAR(255) NOT NULL,
    must_change_password TINYINT(1) NOT NULL DEFAULT 0,  -- 1: пароль выдан администратором, при первом входе его нужно сменить
    is_active            TINYINT(1) NOT NULL DEFAULT 1,
    tariff               ENUM('free','vip') NOT NULL DEFAULT 'free',  -- VIP отмечает менеджер, онлайн-оплаты нет
    created_via          ENUM('self','admin','install') NOT NULL DEFAULT 'self',
    created_at           DATETIME NOT NULL,
    last_login_at        DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- заявки ----------

CREATE TABLE IF NOT EXISTS leads (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    phone      VARCHAR(15) NOT NULL,
    email      VARCHAR(120) NULL,
    message    TEXT,
    source     VARCHAR(60) NOT NULL DEFAULT 'site',   -- site, calculator, house:<id>, reset
    params     TEXT NULL,                             -- JSON: параметры расчёта (площадь, этажность, материал, фундамент, итог)
    status     ENUM('new','in_work','deal','rejected','spam') NOT NULL DEFAULT 'new',
    user_id    INT NULL,                              -- клиент, к которому привязана заявка
    admin_note TEXT NULL,
    consent_at DATETIME NOT NULL,                     -- когда дано согласие на обработку данных
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_leads_status (status, created_at),
    INDEX idx_leads_phone (phone),
    CONSTRAINT fk_lead_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- объекты строительства (то, что клиент видит в кабинете) ----------

CREATE TABLE IF NOT EXISTS objects (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    code             VARCHAR(30) NOT NULL UNIQUE,     -- СЭРГЭ-024
    user_id          INT NOT NULL,
    house_id         VARCHAR(40) NULL,
    title            VARCHAR(150) NOT NULL,
    address          VARCHAR(200) NULL,
    contract_no      VARCHAR(50) NULL,
    status           ENUM('design','building','done') NOT NULL DEFAULT 'design',
    current_stage_id INT NULL,
    progress         TINYINT NOT NULL DEFAULT 0,      -- 0..100
    started_at       DATE NULL,
    planned_end      DATE NULL,
    created_at       DATETIME NOT NULL,
    CONSTRAINT fk_obj_user  FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE,
    CONSTRAINT fk_obj_house FOREIGN KEY (house_id) REFERENCES houses(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_obj_stage FOREIGN KEY (current_stage_id) REFERENCES stages(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS object_updates (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    object_id  INT NOT NULL,
    stage_id   INT NULL,
    text       TEXT NOT NULL,
    photo      VARCHAR(200) NULL,
    is_visible TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    INDEX idx_upd_obj (object_id, created_at),
    CONSTRAINT fk_upd_obj   FOREIGN KEY (object_id) REFERENCES objects(id) ON DELETE CASCADE,
    CONSTRAINT fk_upd_stage FOREIGN KEY (stage_id)  REFERENCES stages(id)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- служебные ----------

CREATE TABLE IF NOT EXISTS rate_limits (        -- ограничение частоты: неудачные входы, заявки, регистрации
    id         INT AUTO_INCREMENT PRIMARY KEY,
    bucket     VARCHAR(30) NOT NULL,            -- login, lead, register
    k          CHAR(64) NOT NULL,               -- хэш ключа (телефон/почта/адрес), сам ключ не хранится
    created_at INT NOT NULL,                    -- unix time
    INDEX idx_rl (bucket, k, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS audit_log (          -- что и когда менял администратор
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NULL,
    action     VARCHAR(60) NOT NULL,
    entity     VARCHAR(40) NULL,
    entity_id  VARCHAR(40) NULL,
    details    VARCHAR(500) NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_audit (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
