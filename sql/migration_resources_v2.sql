-- ================================================
-- Міграція v2: точність ресурсів + поля в movements
-- ================================================

-- Вища точність для год:хв та денних розносок
ALTER TABLE resource_logs
    MODIFY reading DECIMAL(15,6) NOT NULL,
    MODIFY prev_reading DECIMAL(15,6) DEFAULT NULL,
    MODIFY delta DECIMAL(15,6) DEFAULT NULL;

-- Якщо ще не додано раніше
ALTER TABLE resource_rates
    ADD COLUMN IF NOT EXISTS spread_by_day TINYINT(1) NOT NULL DEFAULT 0 AFTER source_warehouse_id;

-- Поля автоматичних рухів для експорту та аудиту
ALTER TABLE movements
    ADD COLUMN IF NOT EXISTS resource_value DECIMAL(15,6) DEFAULT NULL AFTER resource_log_id,
    ADD COLUMN IF NOT EXISTS resource_delta DECIMAL(15,6) DEFAULT NULL AFTER resource_value,
    ADD COLUMN IF NOT EXISTS resource_rate DECIMAL(15,6) DEFAULT NULL AFTER resource_delta;
