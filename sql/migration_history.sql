-- ================================================
-- Міграція: Історія змін (audit log) з тригерами
-- ================================================
-- Автоматично зберігає попередні версії записів
-- при UPDATE та DELETE для всіх таблиць
--
-- Поля в *_history:
--   history_id  — PK записів історії
--   action      — UPDATE або DELETE
--   changed_at  — коли відбулась зміна (заміняє updated_at)
--   changed_by  — хто зробив зміну (з @current_user)
--   ...         — копія полів оригінальної таблиці (без updated_at)
-- ================================================

-- ================================================
-- 1. ТАБЛИЦІ ІСТОРІЇ
-- ================================================

-- Історія config
CREATE TABLE IF NOT EXISTS config_history (
    history_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    action ENUM('UPDATE', 'DELETE') NOT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    changed_by VARCHAR(255) DEFAULT NULL,
    `key` VARCHAR(50) NOT NULL,
    `value` VARCHAR(255) NOT NULL,
    `author` VARCHAR(255) DEFAULT NULL,
    INDEX idx_config_history_key (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Історія warehouses
CREATE TABLE IF NOT EXISTS warehouses_history (
    history_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    action ENUM('UPDATE', 'DELETE') NOT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    changed_by VARCHAR(255) DEFAULT NULL,
    id INT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    author VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NULL,
    INDEX idx_warehouses_history_id (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Історія materials
CREATE TABLE IF NOT EXISTS materials_history (
    history_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    action ENUM('UPDATE', 'DELETE') NOT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    changed_by VARCHAR(255) DEFAULT NULL,
    id INT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    author VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NULL,
    INDEX idx_materials_history_id (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Історія movements
CREATE TABLE IF NOT EXISTS movements_history (
    history_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    action ENUM('UPDATE', 'DELETE') NOT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    changed_by VARCHAR(255) DEFAULT NULL,
    id INT UNSIGNED NOT NULL,
    movement_date DATE NOT NULL,
    warehouse_from_id INT UNSIGNED DEFAULT NULL,
    warehouse_to_id INT UNSIGNED DEFAULT NULL,
    material_id INT UNSIGNED NOT NULL,
    quantity DECIMAL(15,2) NOT NULL,
    note TEXT DEFAULT NULL,
    author VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NULL,
    resource_log_id INT UNSIGNED DEFAULT NULL,
    resource_value DECIMAL(15,6) DEFAULT NULL,
    resource_delta DECIMAL(15,6) DEFAULT NULL,
    resource_rate DECIMAL(15,6) DEFAULT NULL,
    resource_correction DECIMAL(6,2) DEFAULT NULL,
    INDEX idx_movements_history_id (id),
    INDEX idx_movements_history_date (movement_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Історія resource_types
CREATE TABLE IF NOT EXISTS resource_types_history (
    history_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    action ENUM('UPDATE', 'DELETE') NOT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    changed_by VARCHAR(255) DEFAULT NULL,
    id INT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    unit VARCHAR(30) NOT NULL,
    format VARCHAR(10) NOT NULL,
    author VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NULL,
    INDEX idx_resource_types_history_id (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Історія warehouse_resources
CREATE TABLE IF NOT EXISTS warehouse_resources_history (
    history_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    action ENUM('UPDATE', 'DELETE') NOT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    changed_by VARCHAR(255) DEFAULT NULL,
    id INT UNSIGNED NOT NULL,
    warehouse_id INT UNSIGNED NOT NULL,
    resource_type_id INT UNSIGNED NOT NULL,
    author VARCHAR(255) DEFAULT NULL,
    INDEX idx_warehouse_resources_history_id (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Історія resource_rates
CREATE TABLE IF NOT EXISTS resource_rates_history (
    history_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    action ENUM('UPDATE', 'DELETE') NOT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    changed_by VARCHAR(255) DEFAULT NULL,
    id INT UNSIGNED NOT NULL,
    warehouse_id INT UNSIGNED NOT NULL,
    resource_type_id INT UNSIGNED NOT NULL,
    material_id INT UNSIGNED NOT NULL,
    rate DECIMAL(15,6) NOT NULL,
    source_warehouse_id INT UNSIGNED DEFAULT NULL,
    spread_by_day TINYINT(1) NOT NULL DEFAULT 0,
    author VARCHAR(255) DEFAULT NULL,
    INDEX idx_resource_rates_history_id (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Історія resource_logs
CREATE TABLE IF NOT EXISTS resource_logs_history (
    history_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    action ENUM('UPDATE', 'DELETE') NOT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    changed_by VARCHAR(255) DEFAULT NULL,
    id INT UNSIGNED NOT NULL,
    warehouse_id INT UNSIGNED NOT NULL,
    resource_type_id INT UNSIGNED NOT NULL,
    log_date DATE NOT NULL,
    reading DECIMAL(15,2) NOT NULL,
    prev_reading DECIMAL(15,2) DEFAULT NULL,
    delta DECIMAL(15,2) DEFAULT NULL,
    note TEXT DEFAULT NULL,
    author VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NULL,
    correction_pct DECIMAL(6,2) DEFAULT NULL,
    INDEX idx_resource_logs_history_id (id),
    INDEX idx_resource_logs_history_date (log_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ================================================
-- 2. ДОДАТИ ПОЛЕ author ДО ТАБЛИЦЬ, ДЕ ЙОГО НЕМАЄ
-- ================================================

ALTER TABLE resource_types 
    ADD COLUMN IF NOT EXISTS author VARCHAR(255) DEFAULT NULL;

ALTER TABLE warehouse_resources 
    ADD COLUMN IF NOT EXISTS author VARCHAR(255) DEFAULT NULL;

ALTER TABLE resource_rates 
    ADD COLUMN IF NOT EXISTS author VARCHAR(255) DEFAULT NULL;

ALTER TABLE resource_logs 
    ADD COLUMN IF NOT EXISTS author VARCHAR(255) DEFAULT NULL;


-- ================================================
-- 3. ТРИГЕРИ
-- ================================================

DELIMITER //

-- ========== CONFIG ==========

DROP TRIGGER IF EXISTS config_before_update//
CREATE TRIGGER config_before_update
BEFORE UPDATE ON config
FOR EACH ROW
BEGIN
    INSERT INTO config_history (action, changed_by, `key`, `value`, `author`)
    VALUES ('UPDATE', @current_user, OLD.`key`, OLD.`value`, OLD.`author`);
END//

DROP TRIGGER IF EXISTS config_before_delete//
CREATE TRIGGER config_before_delete
BEFORE DELETE ON config
FOR EACH ROW
BEGIN
    INSERT INTO config_history (action, changed_by, `key`, `value`, `author`)
    VALUES ('DELETE', @current_user, OLD.`key`, OLD.`value`, OLD.`author`);
END//


-- ========== WAREHOUSES ==========

DROP TRIGGER IF EXISTS warehouses_before_update//
CREATE TRIGGER warehouses_before_update
BEFORE UPDATE ON warehouses
FOR EACH ROW
BEGIN
    INSERT INTO warehouses_history (action, changed_by, id, name, author, created_at)
    VALUES ('UPDATE', @current_user, OLD.id, OLD.name, OLD.author, OLD.created_at);
END//

DROP TRIGGER IF EXISTS warehouses_before_delete//
CREATE TRIGGER warehouses_before_delete
BEFORE DELETE ON warehouses
FOR EACH ROW
BEGIN
    INSERT INTO warehouses_history (action, changed_by, id, name, author, created_at)
    VALUES ('DELETE', @current_user, OLD.id, OLD.name, OLD.author, OLD.created_at);
END//


-- ========== MATERIALS ==========

DROP TRIGGER IF EXISTS materials_before_update//
CREATE TRIGGER materials_before_update
BEFORE UPDATE ON materials
FOR EACH ROW
BEGIN
    INSERT INTO materials_history (action, changed_by, id, name, author, created_at)
    VALUES ('UPDATE', @current_user, OLD.id, OLD.name, OLD.author, OLD.created_at);
END//

DROP TRIGGER IF EXISTS materials_before_delete//
CREATE TRIGGER materials_before_delete
BEFORE DELETE ON materials
FOR EACH ROW
BEGIN
    INSERT INTO materials_history (action, changed_by, id, name, author, created_at)
    VALUES ('DELETE', @current_user, OLD.id, OLD.name, OLD.author, OLD.created_at);
END//


-- ========== MOVEMENTS ==========

DROP TRIGGER IF EXISTS movements_before_update//
CREATE TRIGGER movements_before_update
BEFORE UPDATE ON movements
FOR EACH ROW
BEGIN
    INSERT INTO movements_history (
        action, changed_by, id, movement_date, warehouse_from_id, warehouse_to_id,
        material_id, quantity, note, author, created_at,
        resource_log_id, resource_value, resource_delta, resource_rate, resource_correction
    )
    VALUES (
        'UPDATE', @current_user, OLD.id, OLD.movement_date, OLD.warehouse_from_id, OLD.warehouse_to_id,
        OLD.material_id, OLD.quantity, OLD.note, OLD.author, OLD.created_at,
        OLD.resource_log_id, OLD.resource_value, OLD.resource_delta, OLD.resource_rate, OLD.resource_correction
    );
END//

DROP TRIGGER IF EXISTS movements_before_delete//
CREATE TRIGGER movements_before_delete
BEFORE DELETE ON movements
FOR EACH ROW
BEGIN
    INSERT INTO movements_history (
        action, changed_by, id, movement_date, warehouse_from_id, warehouse_to_id,
        material_id, quantity, note, author, created_at,
        resource_log_id, resource_value, resource_delta, resource_rate, resource_correction
    )
    VALUES (
        'DELETE', @current_user, OLD.id, OLD.movement_date, OLD.warehouse_from_id, OLD.warehouse_to_id,
        OLD.material_id, OLD.quantity, OLD.note, OLD.author, OLD.created_at,
        OLD.resource_log_id, OLD.resource_value, OLD.resource_delta, OLD.resource_rate, OLD.resource_correction
    );
END//


-- ========== RESOURCE_TYPES ==========

DROP TRIGGER IF EXISTS resource_types_before_update//
CREATE TRIGGER resource_types_before_update
BEFORE UPDATE ON resource_types
FOR EACH ROW
BEGIN
    INSERT INTO resource_types_history (action, changed_by, id, name, unit, format, author, created_at)
    VALUES ('UPDATE', @current_user, OLD.id, OLD.name, OLD.unit, OLD.format, OLD.author, OLD.created_at);
END//

DROP TRIGGER IF EXISTS resource_types_before_delete//
CREATE TRIGGER resource_types_before_delete
BEFORE DELETE ON resource_types
FOR EACH ROW
BEGIN
    INSERT INTO resource_types_history (action, changed_by, id, name, unit, format, author, created_at)
    VALUES ('DELETE', @current_user, OLD.id, OLD.name, OLD.unit, OLD.format, OLD.author, OLD.created_at);
END//


-- ========== WAREHOUSE_RESOURCES ==========

DROP TRIGGER IF EXISTS warehouse_resources_before_update//
CREATE TRIGGER warehouse_resources_before_update
BEFORE UPDATE ON warehouse_resources
FOR EACH ROW
BEGIN
    INSERT INTO warehouse_resources_history (action, changed_by, id, warehouse_id, resource_type_id, author)
    VALUES ('UPDATE', @current_user, OLD.id, OLD.warehouse_id, OLD.resource_type_id, OLD.author);
END//

DROP TRIGGER IF EXISTS warehouse_resources_before_delete//
CREATE TRIGGER warehouse_resources_before_delete
BEFORE DELETE ON warehouse_resources
FOR EACH ROW
BEGIN
    INSERT INTO warehouse_resources_history (action, changed_by, id, warehouse_id, resource_type_id, author)
    VALUES ('DELETE', @current_user, OLD.id, OLD.warehouse_id, OLD.resource_type_id, OLD.author);
END//


-- ========== RESOURCE_RATES ==========

DROP TRIGGER IF EXISTS resource_rates_before_update//
CREATE TRIGGER resource_rates_before_update
BEFORE UPDATE ON resource_rates
FOR EACH ROW
BEGIN
    INSERT INTO resource_rates_history (
        action, changed_by, id, warehouse_id, resource_type_id, material_id,
        rate, source_warehouse_id, spread_by_day, author
    )
    VALUES (
        'UPDATE', @current_user, OLD.id, OLD.warehouse_id, OLD.resource_type_id, OLD.material_id,
        OLD.rate, OLD.source_warehouse_id, OLD.spread_by_day, OLD.author
    );
END//

DROP TRIGGER IF EXISTS resource_rates_before_delete//
CREATE TRIGGER resource_rates_before_delete
BEFORE DELETE ON resource_rates
FOR EACH ROW
BEGIN
    INSERT INTO resource_rates_history (
        action, changed_by, id, warehouse_id, resource_type_id, material_id,
        rate, source_warehouse_id, spread_by_day, author
    )
    VALUES (
        'DELETE', @current_user, OLD.id, OLD.warehouse_id, OLD.resource_type_id, OLD.material_id,
        OLD.rate, OLD.source_warehouse_id, OLD.spread_by_day, OLD.author
    );
END//


-- ========== RESOURCE_LOGS ==========

DROP TRIGGER IF EXISTS resource_logs_before_update//
CREATE TRIGGER resource_logs_before_update
BEFORE UPDATE ON resource_logs
FOR EACH ROW
BEGIN
    INSERT INTO resource_logs_history (
        action, changed_by, id, warehouse_id, resource_type_id, log_date,
        reading, prev_reading, delta, note, author, created_at, correction_pct
    )
    VALUES (
        'UPDATE', @current_user, OLD.id, OLD.warehouse_id, OLD.resource_type_id, OLD.log_date,
        OLD.reading, OLD.prev_reading, OLD.delta, OLD.note, OLD.author, OLD.created_at, OLD.correction_pct
    );
END//

DROP TRIGGER IF EXISTS resource_logs_before_delete//
CREATE TRIGGER resource_logs_before_delete
BEFORE DELETE ON resource_logs
FOR EACH ROW
BEGIN
    INSERT INTO resource_logs_history (
        action, changed_by, id, warehouse_id, resource_type_id, log_date,
        reading, prev_reading, delta, note, author, created_at, correction_pct
    )
    VALUES (
        'DELETE', @current_user, OLD.id, OLD.warehouse_id, OLD.resource_type_id, OLD.log_date,
        OLD.reading, OLD.prev_reading, OLD.delta, OLD.note, OLD.author, OLD.created_at, OLD.correction_pct
    );
END//

DELIMITER ;


-- ================================================
-- 4. КОРИСНІ VIEW ДЛЯ ПЕРЕГЛЯДУ ІСТОРІЇ
-- ================================================

CREATE OR REPLACE VIEW v_movements_history AS
SELECT 
    h.history_id,
    h.action,
    h.changed_at,
    h.changed_by,
    h.id AS movement_id,
    h.movement_date,
    wf.name AS warehouse_from,
    wt.name AS warehouse_to,
    m.name AS material,
    h.quantity,
    h.note,
    h.author,
    h.created_at,
    h.resource_value,
    h.resource_delta,
    h.resource_rate
FROM movements_history h
LEFT JOIN warehouses wf ON wf.id = h.warehouse_from_id
LEFT JOIN warehouses wt ON wt.id = h.warehouse_to_id
LEFT JOIN materials m ON m.id = h.material_id
ORDER BY h.changed_at DESC;


-- ================================================
-- ГОТОВО!
-- ================================================
