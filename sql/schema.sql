-- ================================================
-- Складський облік — Схема бази даних MariaDB
-- ================================================

-- Створення бази даних (виконати від імені root):
-- CREATE DATABASE sklad CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- CREATE USER 'sklad'@'localhost' IDENTIFIED BY 'your_password_here';
-- GRANT ALL PRIVILEGES ON sklad.* TO 'sklad'@'localhost';
-- FLUSH PRIVILEGES;

USE sklad;

-- ================================================
-- Таблиці
-- ================================================

-- Довідник складів
CREATE TABLE IF NOT EXISTS warehouses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Довідник матеріалів
CREATE TABLE IF NOT EXISTS materials (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Рух матеріалів
CREATE TABLE IF NOT EXISTS movements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    movement_date DATE NOT NULL,
    warehouse_from_id INT UNSIGNED DEFAULT NULL,
    warehouse_to_id INT UNSIGNED DEFAULT NULL,
    material_id INT UNSIGNED NOT NULL,
    quantity DECIMAL(15,2) NOT NULL,
    note TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Зовнішні ключі
    CONSTRAINT fk_movement_warehouse_from 
        FOREIGN KEY (warehouse_from_id) REFERENCES warehouses(id) ON DELETE RESTRICT,
    CONSTRAINT fk_movement_warehouse_to 
        FOREIGN KEY (warehouse_to_id) REFERENCES warehouses(id) ON DELETE RESTRICT,
    CONSTRAINT fk_movement_material 
        FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE RESTRICT,
    
    -- Індекси
    INDEX idx_movement_date (movement_date),
    INDEX idx_warehouse_from (warehouse_from_id),
    INDEX idx_warehouse_to (warehouse_to_id),
    INDEX idx_material (material_id),
    
    -- Перевірки
    CONSTRAINT chk_at_least_one_warehouse 
        CHECK (warehouse_from_id IS NOT NULL OR warehouse_to_id IS NOT NULL),
    CONSTRAINT chk_different_warehouses 
        CHECK (warehouse_from_id IS NULL OR warehouse_to_id IS NULL OR warehouse_from_id <> warehouse_to_id),
    CONSTRAINT chk_positive_quantity 
        CHECK (quantity > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Налаштування (key-value)
CREATE TABLE IF NOT EXISTS config (
    `key` VARCHAR(50) PRIMARY KEY,
    `value` VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================
-- Демонстраційні дані (опціонально)
-- ================================================

INSERT INTO warehouses (name) VALUES
    ('Основний склад'),
    ('Склад ПММ'),
    ('Склад інструментів');

INSERT INTO materials (name) VALUES
    ('Дизельне пальне'),
    ('Бензин А-95'),
    ('Моторне мастило 10W-40'),
    ('Гідравлічне мастило');

INSERT INTO movements (movement_date, warehouse_from_id, warehouse_to_id, material_id, quantity, note) VALUES
    ('2025-01-05', NULL, 1, 1, 500.00, 'Початкове надходження'),
    ('2025-01-05', NULL, 1, 2, 300.00, 'Початкове надходження'),
    ('2025-01-10', NULL, 2, 3, 100.00, 'Закупівля'),
    ('2025-01-15', 1, 2, 1, 150.00, 'Переміщення на склад ПММ'),
    ('2025-01-20', NULL, 3, 4, 50.00, 'Закупівля гідравлічного мастила'),
    ('2025-02-01', 2, NULL, 1, 30.00, 'Списання - видача на техніку'),
    ('2025-02-10', NULL, 1, 1, 200.00, 'Поповнення запасів'),
    ('2025-02-15', 1, 3, 2, 50.00, 'Переміщення'),
    ('2025-03-01', 3, NULL, 4, 10.00, 'Списання'),
    ('2025-03-05', NULL, 2, 3, 50.00, 'Додаткова закупівля');
