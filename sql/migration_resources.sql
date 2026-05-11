-- ================================================
-- Міграція: Ресурси (пробіг, мотогодини тощо)
-- ================================================

-- Типи ресурсів (км, мотогодини тощо)
CREATE TABLE IF NOT EXISTS resource_types (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,        -- "Пробіг", "Мотогодини"
    unit VARCHAR(30) NOT NULL,         -- "км", "год"
    format VARCHAR(10) NOT NULL DEFAULT 'int',  -- 'int', 'dec2', 'hm' (hours:minutes)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Які ресурси відслідковуються на якому складі
-- (склад OSHKOSH 74-25 має ресурс "Пробіг")
CREATE TABLE IF NOT EXISTS warehouse_resources (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    warehouse_id INT UNSIGNED NOT NULL,
    resource_type_id INT UNSIGNED NOT NULL,
    UNIQUE KEY uq_wh_res (warehouse_id, resource_type_id),
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE,
    FOREIGN KEY (resource_type_id) REFERENCES resource_types(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Норми списання: скільки матеріалу списати на одиницю ресурсу
-- (OSHKOSH 74-25 + Пробіг: 0.35 л Дизельного пального на 1 км, 
--  списати зі Складу ПММ)
CREATE TABLE IF NOT EXISTS resource_rates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    warehouse_id INT UNSIGNED NOT NULL,
    resource_type_id INT UNSIGNED NOT NULL,
    material_id INT UNSIGNED NOT NULL,
    rate DECIMAL(15,6) NOT NULL,         -- к-сть матеріалу на 1 одиницю ресурсу
    source_warehouse_id INT UNSIGNED DEFAULT NULL,  -- куди віднести (NULL = просто списання)
    spread_by_day TINYINT(1) NOT NULL DEFAULT 0,    -- 1 = рознести по днях
    UNIQUE KEY uq_rate (warehouse_id, resource_type_id, material_id),
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE,
    FOREIGN KEY (resource_type_id) REFERENCES resource_types(id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE RESTRICT,
    FOREIGN KEY (source_warehouse_id) REFERENCES warehouses(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Журнал показників ресурсу (одометр, лічильник мотогодин)
CREATE TABLE IF NOT EXISTS resource_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    warehouse_id INT UNSIGNED NOT NULL,
    resource_type_id INT UNSIGNED NOT NULL,
    log_date DATE NOT NULL,
    reading DECIMAL(15,2) NOT NULL,       -- поточний показник (одометр тощо)
    prev_reading DECIMAL(15,2) DEFAULT NULL, -- попередній показник (заповнюється автоматично)
    delta DECIMAL(15,2) DEFAULT NULL,     -- різниця (заповнюється автоматично)
    note TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_log_wh_res (warehouse_id, resource_type_id, log_date),
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE,
    FOREIGN KEY (resource_type_id) REFERENCES resource_types(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Зв'язок руху з журналом ресурсу (які рухи створені автоматично)
ALTER TABLE movements ADD COLUMN IF NOT EXISTS resource_log_id INT UNSIGNED DEFAULT NULL;
ALTER TABLE movements ADD COLUMN IF NOT EXISTS resource_value DECIMAL(15,6) DEFAULT NULL;
ALTER TABLE movements ADD COLUMN IF NOT EXISTS resource_delta DECIMAL(15,6) DEFAULT NULL;
ALTER TABLE movements ADD COLUMN IF NOT EXISTS resource_rate DECIMAL(15,6) DEFAULT NULL;
ALTER TABLE movements ADD CONSTRAINT fk_movement_resource_log 
    FOREIGN KEY (resource_log_id) REFERENCES resource_logs(id) ON DELETE SET NULL;
ALTER TABLE movements ADD INDEX idx_resource_log (resource_log_id);

-- ================================================
-- Демо: типи ресурсів
-- ================================================
INSERT INTO resource_types (name, unit, format) VALUES
    ('Пробіг', 'км', 'int'),
    ('Мотогодини', 'год', 'hm');
