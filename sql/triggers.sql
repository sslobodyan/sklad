-- ============================================
-- Тригери
-- ============================================

DELIMITER $$

CREATE TRIGGER `config_before_delete` BEFORE DELETE ON `config` FOR EACH ROW BEGIN
    INSERT INTO config_history (action, changed_by, `key`, `value`, `author`)
    VALUES ('DELETE', @current_user, OLD.`key`, OLD.`value`, OLD.`author`);
END$$

CREATE TRIGGER `config_before_update` BEFORE UPDATE ON `config` FOR EACH ROW BEGIN
    INSERT INTO config_history (action, changed_by, `key`, `value`, `author`)
    VALUES ('UPDATE', @current_user, OLD.`key`, OLD.`value`, OLD.`author`);
END$$

CREATE TRIGGER `materials_before_delete` BEFORE DELETE ON `materials` FOR EACH ROW BEGIN
    INSERT INTO materials_history (action, changed_by, id, name, author, created_at)
    VALUES ('DELETE', @current_user, OLD.id, OLD.name, OLD.author, OLD.created_at);
END$$

CREATE TRIGGER `materials_before_update` BEFORE UPDATE ON `materials` FOR EACH ROW BEGIN
    INSERT INTO materials_history (action, changed_by, id, name, author, created_at)
    VALUES ('UPDATE', @current_user, OLD.id, OLD.name, OLD.author, OLD.created_at);
END$$

CREATE TRIGGER `movements_before_delete` BEFORE DELETE ON `movements` FOR EACH ROW BEGIN
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
END$$

CREATE TRIGGER `movements_before_update` BEFORE UPDATE ON `movements` FOR EACH ROW BEGIN
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
END$$

CREATE TRIGGER `resource_logs_before_delete` BEFORE DELETE ON `resource_logs` FOR EACH ROW BEGIN
    INSERT INTO resource_logs_history (
        action, changed_by, id, warehouse_id, resource_type_id, log_date,
        reading, prev_reading, delta, note, author, created_at
    )
    VALUES (
        'DELETE', @current_user, OLD.id, OLD.warehouse_id, OLD.resource_type_id, OLD.log_date,
        OLD.reading, OLD.prev_reading, OLD.delta, OLD.note, OLD.author, OLD.created_at
    );
END$$

CREATE TRIGGER `resource_logs_before_update` BEFORE UPDATE ON `resource_logs` FOR EACH ROW BEGIN
    INSERT INTO resource_logs_history (
        action, changed_by, id, warehouse_id, resource_type_id, log_date,
        reading, prev_reading, delta, note, author, created_at
    )
    VALUES (
        'UPDATE', @current_user, OLD.id, OLD.warehouse_id, OLD.resource_type_id, OLD.log_date,
        OLD.reading, OLD.prev_reading, OLD.delta, OLD.note, OLD.author, OLD.created_at
    );
END$$

CREATE TRIGGER `resource_rates_before_delete` BEFORE DELETE ON `resource_rates` FOR EACH ROW BEGIN
    INSERT INTO resource_rates_history (
        action, changed_by, id, warehouse_id, resource_type_id, material_id,
        rate, source_warehouse_id, spread_by_day, author
    )
    VALUES (
        'DELETE', @current_user, OLD.id, OLD.warehouse_id, OLD.resource_type_id, OLD.material_id,
        OLD.rate, OLD.source_warehouse_id, OLD.spread_by_day, OLD.author
    );
END$$

CREATE TRIGGER `resource_rates_before_update` BEFORE UPDATE ON `resource_rates` FOR EACH ROW BEGIN
    INSERT INTO resource_rates_history (
        action, changed_by, id, warehouse_id, resource_type_id, material_id,
        rate, source_warehouse_id, spread_by_day, author
    )
    VALUES (
        'UPDATE', @current_user, OLD.id, OLD.warehouse_id, OLD.resource_type_id, OLD.material_id,
        OLD.rate, OLD.source_warehouse_id, OLD.spread_by_day, OLD.author
    );
END$$

CREATE TRIGGER `resource_types_before_delete` BEFORE DELETE ON `resource_types` FOR EACH ROW BEGIN
    INSERT INTO resource_types_history (action, changed_by, id, name, unit, format, author, created_at)
    VALUES ('DELETE', @current_user, OLD.id, OLD.name, OLD.unit, OLD.format, OLD.author, OLD.created_at);
END$$

CREATE TRIGGER `resource_types_before_update` BEFORE UPDATE ON `resource_types` FOR EACH ROW BEGIN
    INSERT INTO resource_types_history (action, changed_by, id, name, unit, format, author, created_at)
    VALUES ('UPDATE', @current_user, OLD.id, OLD.name, OLD.unit, OLD.format, OLD.author, OLD.created_at);
END$$

CREATE TRIGGER `warehouses_before_delete` BEFORE DELETE ON `warehouses` FOR EACH ROW BEGIN
    INSERT INTO warehouses_history (action, changed_by, id, name, author, created_at)
    VALUES ('DELETE', @current_user, OLD.id, OLD.name, OLD.author, OLD.created_at);
END$$

CREATE TRIGGER `warehouses_before_update` BEFORE UPDATE ON `warehouses` FOR EACH ROW BEGIN
    INSERT INTO warehouses_history (action, changed_by, id, name, author, created_at)
    VALUES ('UPDATE', @current_user, OLD.id, OLD.name, OLD.author, OLD.created_at);
END$$

CREATE TRIGGER `warehouse_resources_before_delete` BEFORE DELETE ON `warehouse_resources` FOR EACH ROW BEGIN
    INSERT INTO warehouse_resources_history (action, changed_by, id, warehouse_id, resource_type_id, author)
    VALUES ('DELETE', @current_user, OLD.id, OLD.warehouse_id, OLD.resource_type_id, OLD.author);
END$$

CREATE TRIGGER `warehouse_resources_before_update` BEFORE UPDATE ON `warehouse_resources` FOR EACH ROW BEGIN
    INSERT INTO warehouse_resources_history (action, changed_by, id, warehouse_id, resource_type_id, author)
    VALUES ('UPDATE', @current_user, OLD.id, OLD.warehouse_id, OLD.resource_type_id, OLD.author);
END$$

DELIMITER ;
