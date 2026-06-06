-- ============================================
-- Складський облік - SQL інсталяції
-- ============================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- ============================================
-- Таблиці
-- ============================================

CREATE TABLE IF NOT EXISTS `config` (
  `key` varchar(50) NOT NULL,
  `value` varchar(255) NOT NULL,
  `author` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `config_history` (
  `history_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `action` enum('UPDATE','DELETE') NOT NULL,
  `changed_at` timestamp NULL DEFAULT current_timestamp(),
  `changed_by` varchar(255) DEFAULT NULL,
  `key` varchar(50) NOT NULL,
  `value` varchar(255) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`history_id`),
  KEY `idx_config_history_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `materials` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `materials_history` (
  `history_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `action` enum('UPDATE','DELETE') NOT NULL,
  `changed_at` timestamp NULL DEFAULT current_timestamp(),
  `changed_by` varchar(255) DEFAULT NULL,
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`history_id`),
  KEY `idx_materials_history_id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `menu_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `controller` varchar(100) DEFAULT NULL,
  `label` varchar(255) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `icon_svg` text DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `is_enabled` tinyint(1) DEFAULT 1,
  `requires_date_range` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `author` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `controller` (`controller`),
  KEY `idx_parent` (`parent_id`),
  KEY `idx_order` (`sort_order`),
  KEY `idx_controller` (`controller`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `movements` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `movement_date` date NOT NULL,
  `warehouse_from_id` int(10) UNSIGNED DEFAULT NULL,
  `warehouse_to_id` int(10) UNSIGNED DEFAULT NULL,
  `material_id` int(10) UNSIGNED NOT NULL,
  `quantity` decimal(15,2) NOT NULL,
  `note` text DEFAULT NULL,
  `author` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `resource_log_id` int(10) UNSIGNED DEFAULT NULL,
  `resource_value` decimal(15,6) DEFAULT NULL,
  `resource_delta` decimal(15,6) DEFAULT NULL,
  `resource_rate` decimal(15,6) DEFAULT NULL,
  `resource_correction` decimal(6,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_movement_date` (`movement_date`),
  KEY `idx_warehouse_from` (`warehouse_from_id`),
  KEY `idx_warehouse_to` (`warehouse_to_id`),
  KEY `idx_material` (`material_id`),
  KEY `idx_resource_log` (`resource_log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `movements_history` (
  `history_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `action` enum('UPDATE','DELETE') NOT NULL,
  `changed_at` timestamp NULL DEFAULT current_timestamp(),
  `changed_by` varchar(255) DEFAULT NULL,
  `id` int(10) UNSIGNED NOT NULL,
  `movement_date` date NOT NULL,
  `warehouse_from_id` int(10) UNSIGNED DEFAULT NULL,
  `warehouse_to_id` int(10) UNSIGNED DEFAULT NULL,
  `material_id` int(10) UNSIGNED NOT NULL,
  `quantity` decimal(15,2) NOT NULL,
  `note` text DEFAULT NULL,
  `author` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `resource_log_id` int(10) UNSIGNED DEFAULT NULL,
  `resource_value` decimal(15,6) DEFAULT NULL,
  `resource_delta` decimal(15,6) DEFAULT NULL,
  `resource_rate` decimal(15,6) DEFAULT NULL,
  `resource_correction` decimal(6,2) DEFAULT NULL,
  PRIMARY KEY (`history_id`),
  KEY `idx_movements_history_id` (`id`),
  KEY `idx_movements_history_date` (`movement_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `resource_logs` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `warehouse_id` int(10) UNSIGNED NOT NULL,
  `resource_type_id` int(10) UNSIGNED NOT NULL,
  `log_date` date NOT NULL,
  `reading` decimal(15,6) NOT NULL,
  `prev_reading` decimal(15,6) DEFAULT NULL,
  `delta` decimal(15,6) DEFAULT NULL,
  `correction_pct` decimal(6,2) DEFAULT 0.00,
  `note` text DEFAULT NULL,
  `author` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_log_wh_res` (`warehouse_id`,`resource_type_id`,`log_date`),
  KEY `resource_type_id` (`resource_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `resource_logs_history` (
  `history_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `action` enum('UPDATE','DELETE') NOT NULL,
  `changed_at` timestamp NULL DEFAULT current_timestamp(),
  `changed_by` varchar(255) DEFAULT NULL,
  `id` int(10) UNSIGNED NOT NULL,
  `warehouse_id` int(10) UNSIGNED NOT NULL,
  `resource_type_id` int(10) UNSIGNED NOT NULL,
  `log_date` date NOT NULL,
  `reading` decimal(15,2) NOT NULL,
  `prev_reading` decimal(15,2) DEFAULT NULL,
  `delta` decimal(15,2) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `author` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`history_id`),
  KEY `idx_resource_logs_history_id` (`id`),
  KEY `idx_resource_logs_history_date` (`log_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `resource_rates` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `warehouse_id` int(10) UNSIGNED NOT NULL,
  `resource_type_id` int(10) UNSIGNED NOT NULL,
  `material_id` int(10) UNSIGNED NOT NULL,
  `rate` decimal(15,6) NOT NULL,
  `source_warehouse_id` int(10) UNSIGNED DEFAULT NULL,
  `spread_by_day` tinyint(1) NOT NULL DEFAULT 0,
  `author` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_rate` (`warehouse_id`,`resource_type_id`,`material_id`),
  KEY `resource_type_id` (`resource_type_id`),
  KEY `material_id` (`material_id`),
  KEY `source_warehouse_id` (`source_warehouse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `resource_rates_history` (
  `history_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `action` enum('UPDATE','DELETE') NOT NULL,
  `changed_at` timestamp NULL DEFAULT current_timestamp(),
  `changed_by` varchar(255) DEFAULT NULL,
  `id` int(10) UNSIGNED NOT NULL,
  `warehouse_id` int(10) UNSIGNED NOT NULL,
  `resource_type_id` int(10) UNSIGNED NOT NULL,
  `material_id` int(10) UNSIGNED NOT NULL,
  `rate` decimal(15,6) NOT NULL,
  `source_warehouse_id` int(10) UNSIGNED DEFAULT NULL,
  `spread_by_day` tinyint(1) NOT NULL DEFAULT 0,
  `author` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`history_id`),
  KEY `idx_resource_rates_history_id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `resource_types` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `unit` varchar(30) NOT NULL,
  `format` varchar(10) NOT NULL DEFAULT 'int',
  `show_hours` tinyint(1) DEFAULT 0,
  `author` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `resource_types_history` (
  `history_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `action` enum('UPDATE','DELETE') NOT NULL,
  `changed_at` timestamp NULL DEFAULT current_timestamp(),
  `changed_by` varchar(255) DEFAULT NULL,
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `unit` varchar(30) NOT NULL,
  `format` varchar(10) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`history_id`),
  KEY `idx_resource_types_history_id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_menu_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nc_user` varchar(255) NOT NULL,
  `menu_item_id` int(11) NOT NULL,
  `access_level` enum('none','view','edit') NOT NULL DEFAULT 'none',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `author` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_menu` (`nc_user`,`menu_item_id`),
  KEY `menu_item_id` (`menu_item_id`),
  KEY `idx_user` (`nc_user`),
  KEY `idx_level` (`access_level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nc_user` varchar(255) NOT NULL,
  `role` enum('manager','viewer','fuel') NOT NULL DEFAULT 'viewer',
  `allowed_warehouses` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`allowed_warehouses`)),
  `allowed_materials` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`allowed_materials`)),
  `allowed_resource_types` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`allowed_resource_types`)),
  `can_edit_rates` tinyint(1) DEFAULT 0,
  `can_export` tinyint(1) DEFAULT 1,
  `can_import` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `author` varchar(255) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `is_local` tinyint(1) DEFAULT 0,
  `email` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nc_user` (`nc_user`),
  KEY `idx_user` (`nc_user`),
  KEY `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `warehouses` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `warehouses_history` (
  `history_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `action` enum('UPDATE','DELETE') NOT NULL,
  `changed_at` timestamp NULL DEFAULT current_timestamp(),
  `changed_by` varchar(255) DEFAULT NULL,
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`history_id`),
  KEY `idx_warehouses_history_id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `warehouse_resources` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `warehouse_id` int(10) UNSIGNED NOT NULL,
  `resource_type_id` int(10) UNSIGNED NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_wh_res` (`warehouse_id`,`resource_type_id`),
  KEY `resource_type_id` (`resource_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `warehouse_resources_history` (
  `history_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `action` enum('UPDATE','DELETE') NOT NULL,
  `changed_at` timestamp NULL DEFAULT current_timestamp(),
  `changed_by` varchar(255) DEFAULT NULL,
  `id` int(10) UNSIGNED NOT NULL,
  `warehouse_id` int(10) UNSIGNED NOT NULL,
  `resource_type_id` int(10) UNSIGNED NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`history_id`),
  KEY `idx_warehouse_resources_history_id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

-- ============================================
-- Початкові дані
-- ============================================

INSERT INTO `config` (`key`, `value`, `author`) VALUES
('closed_date', '', ''),
('simple_materials', '[]', ''),
('simple_warehouse', '', ''),
('simple_warehouses', '[]', '');

INSERT INTO `menu_items` (`id`, `controller`, `label`, `parent_id`, `sort_order`, `icon_svg`, `url`, `is_enabled`, `requires_date_range`, `created_at`, `updated_at`, `author`) VALUES
(1, NULL, 'Документи', NULL, 10, NULL, NULL, 1, 0, NOW(), NOW(), ''),
(2, NULL, 'Звіти', NULL, 20, NULL, NULL, 1, 0, NOW(), NOW(), ''),
(3, NULL, 'Довідники', NULL, 30, NULL, NULL, 1, 0, NOW(), NOW(), ''),
(4, NULL, 'Система', NULL, 40, NULL, NULL, 1, 0, NOW(), NOW(), ''),
(47, 'dashboard', 'Головна', 1, 5, '<svg xmlns=\"http://www.w3.org/2000/svg\" height=\"24px\" viewBox=\"0 -960 960 960\" width=\"24px\" fill=\"#1f1f1f\"><path d=\"M600-160v-280h280v280H600ZM440-520v-280h440v280H440ZM80-160v-280h440v280H80Zm0-360v-280h280v280H80Zm440-80h280v-120H520v120ZM160-240h280v-120H160v120Zm520 0h120v-120H680v120ZM160-600h120v-120H160v120Zm360 0Zm-80 240Zm240 0ZM280-600Z\"/></svg>', NULL, 1, 0, NOW(), NOW(), ''),
(48, 'movements', 'Рух матеріалів', 1, 10, '<svg xmlns=\"http://www.w3.org/2000/svg\" height=\"24px\" viewBox=\"0 -960 960 960\" width=\"24px\" fill=\"#1f1f1f\"><path d=\"m280-120-56-56 63-66q-106-12-176.5-91.5T40-520q0-117 81.5-198.5T320-800h120v80H320q-83 0-141.5 58.5T120-520q0 72 46 127t117 69l-59-59 56-57 160 160-160 160Zm240-40v-280h360v280H520Zm0-360v-280h360v280H520Zm80-80h200v-120H600v120Z\"/></svg>', NULL, 1, 1, NOW(), NOW(), ''),
(49, 'resources', 'Витрата ресурсів', 1, 20, '<svg xmlns=\"http://www.w3.org/2000/svg\" height=\"24px\" viewBox=\"0 -960 960 960\" width=\"24px\" fill=\"#1f1f1f\"><path d=\"M205-160q-22 0-40.5-9.5T135-198q-27-46-41-97T81-400q0-94 41.5-177.5T239-718l21 87q-48 45-73.5 105T161-400q0 42 11 83t33 77h551q21-37 32.5-77.5T800-400q0-134-93-227t-227-93q-10 0-19.5.5T441-717l-53-72q23-5 46-8t46-3q83 0 156 31.5T763-683q54 54 85.5 127T880-400q0 54-14 104.5T826-198q-11 19-29.5 28.5T756-160H205Zm308-168q34-15 46-49t-8-63q-58-83-117.5-163T311-762q19 99 42.5 196T406-372q10 33 42.5 45.5T513-328Zm-32-73Z\"/></svg>', NULL, 1, 1, NOW(), NOW(), ''),
(50, 'reportWarehouse', 'Звіт по складу', 2, 10, '<svg xmlns=\"http://www.w3.org/2000/svg\" height=\"24px\" viewBox=\"0 -960 960 960\" width=\"24px\" fill=\"#1f1f1f\"><path d=\"M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h168q13-36 43.5-58t68.5-22q38 0 68.5 22t43.5 58h168q33 0 56.5 23.5T840-760v560q0 33-23.5 56.5T760-120H200Zm0-80h560v-560H200v560Zm80-80h280v-80H280v80Zm0-160h400v-80H280v80Zm0-160h400v-80H280v80Zm221.5-198.5Q510-807 510-820t-8.5-21.5Q493-850 480-850t-21.5 8.5Q450-833 450-820t8.5 21.5Q467-790 480-790t21.5-8.5ZM200-200v-560 560Z\"/></svg>', NULL, 1, 1, NOW(), NOW(), ''),
(51, 'reportMaterial', 'Звіт по матеріалу', 2, 20, '<svg xmlns=\"http://www.w3.org/2000/svg\" height=\"24px\" viewBox=\"0 -960 960 960\" width=\"24px\" fill=\"#1f1f1f\"><path d=\"M400-280h160v-80H400v80Zm0-160h280v-80H400v80ZM280-600h400v-80H280v80Zm200 120ZM265-80q-79 0-134.5-55.5T75-270q0-57 29.5-102t77.5-68H80v-80h240v240h-80v-97q-37 8-61 38t-24 69q0 46 32.5 78t77.5 32v80Zm135-40v-80h360v-560H200v160h-80v-160q0-33 23.5-56.5T200-840h560q33 0 56.5 23.5T840-760v560q0 33-23.5 56.5T760-120H400Z\"/></svg>', NULL, 1, 1, NOW(), NOW(), ''),
(52, 'reportResource', 'Звіт по ресурсу', 2, 30, '<svg xmlns=\"http://www.w3.org/2000/svg\" height=\"24px\" viewBox=\"0 -960 960 960\" width=\"24px\" fill=\"#1f1f1f\"><path d=\"M574.5-774.5Q560-789 560-810t14.5-35.5Q589-860 610-860t35.5 14.5Q660-831 660-810t-14.5 35.5Q631-760 610-760t-35.5-14.5Zm0 660Q560-129 560-150t14.5-35.5Q589-200 610-200t35.5 14.5Q660-171 660-150t-14.5 35.5Q631-100 610-100t-35.5-14.5Zm160-520Q720-649 720-670t14.5-35.5Q749-720 770-720t35.5 14.5Q820-691 820-670t-14.5 35.5Q791-620 770-620t-35.5-14.5Zm0 380Q720-269 720-290t14.5-35.5Q749-340 770-340t35.5 14.5Q820-311 820-290t-14.5 35.5Q791-240 770-240t-35.5-14.5Zm60-190Q780-459 780-480t14.5-35.5Q809-530 830-530t35.5 14.5Q880-501 880-480t-14.5 35.5Q851-430 830-430t-35.5-14.5ZM480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880v80q-134 0-227 93t-93 227q0 134 93 227t227 93v80Zm-56.5-343.5Q400-447 400-480q0-5 .5-10.5T403-501l-83-83 56-56 83 83q4-1 21-3 33 0 56.5 23.5T560-480q0 33-23.5 56.5T480-400q-33 0-56.5-23.5Z\"/></svg>', NULL, 1, 1, NOW(), NOW(), ''),
(53, 'warehouses', 'Склади', 3, 10, '<svg xmlns=\"http://www.w3.org/2000/svg\" height=\"24px\" viewBox=\"0 -960 960 960\" width=\"24px\" fill=\"#1f1f1f\"><path d=\"M160-200h80v-320h480v320h80v-426L480-754 160-626v426Zm-80 80v-560l400-160 400 160v560H640v-320H320v320H80Zm280 0v-80h80v80h-80Zm80-120v-80h80v80h-80Zm80 120v-80h80v80h-80ZM240-520h480-480Z\"/></svg>', NULL, 1, 0, NOW(), NOW(), ''),
(54, 'materials', 'Матеріали', 3, 20, '<svg xmlns=\"http://www.w3.org/2000/svg\" height=\"24px\" viewBox=\"0 -960 960 960\" width=\"24px\" fill=\"#1f1f1f\"><path d=\"M440-183v-274L200-596v274l240 139Zm80 0 240-139v-274L520-457v274Zm-80 92L160-252q-19-11-29.5-29T120-321v-318q0-22 10.5-40t29.5-29l280-161q19-11 40-11t40 11l280 161q19 11 29.5 29t10.5 40v318q0 22-10.5 40T800-252L520-91q-19 11-40 11t-40-11Zm200-528 77-44-237-137-78 45 238 136Zm-160 93 78-45-237-137-78 45 237 137Z\"/></svg>', NULL, 1, 0, NOW(), NOW(), ''),
(55, 'resourceTypes', 'Типи ресурсів', 3, 30, '<svg xmlns=\"http://www.w3.org/2000/svg\" height=\"24px\" viewBox=\"0 -960 960 960\" width=\"24px\" fill=\"#1f1f1f\"><path d=\"M160-200q-66 0-113-47T0-360q0-57 36.5-101t93.5-55l-28-24H0v-60h180l100 60 160-60h126l-62-80H400v-80h142l84 108 134-68v120h-92l70 92q15-6 30.5-9t31.5-3q66 0 113 47t47 113q0 66-47 113t-113 47q-66 0-113-47t-47-113q0-27 9.5-52.5T676-460l-20-24-136 204H400l-80-70q-5 63-51 106.5T160-200Zm0-80q33 0 56.5-23.5T240-360q0-33-23.5-56.5T160-440q-33 0-56.5 23.5T80-360q0 33 23.5 56.5T160-280Zm294-240-144 54 144-54h130-130Zm346 240q33 0 56.5-23.5T880-360q0-33-23.5-56.5T800-440q-33 0-56.5 23.5T720-360q0 33 23.5 56.5T800-280Zm-322-80 106-160H454l-144 54 120 106h48Z\"/></svg>', NULL, 1, 0, NOW(), NOW(), ''),
(56, 'resourceRates', 'Норми списання', 3, 40, '<svg xmlns=\"http://www.w3.org/2000/svg\" height=\"24px\" viewBox=\"0 -960 960 960\" width=\"24px\" fill=\"#1f1f1f\"><path d=\"M300-520q-58 0-99-41t-41-99q0-58 41-99t99-41q58 0 99 41t41 99q0 58-41 99t-99 41Zm0-80q25 0 42.5-17.5T360-660q0-25-17.5-42.5T300-720q-25 0-42.5 17.5T240-660q0 25 17.5 42.5T300-600Zm360 440q-58 0-99-41t-41-99q0-58 41-99t99-41q58 0 99 41t41 99q0 58-41 99t-99 41Zm42.5-97.5Q720-275 720-300t-17.5-42.5Q685-360 660-360t-42.5 17.5Q600-325 600-300t17.5 42.5Q635-240 660-240t42.5-17.5ZM216-160l-56-56 584-584 56 56-584 584Z\"/></svg>', NULL, 1, 0, NOW(), NOW(), ''),
(57, 'simple', 'Заправка', 4, 10, '<svg xmlns=\"http://www.w3.org/2000/svg\" height=\"24px\" viewBox=\"0 -960 960 960\" width=\"24px\" fill=\"#1f1f1f\"><path d=\"M160-120v-640q0-33 23.5-56.5T240-840h240q33 0 56.5 23.5T560-760v280h40q33 0 56.5 23.5T680-400v180q0 17 11.5 28.5T720-180q17 0 28.5-11.5T760-220v-288q-9 5-19 6.5t-21 1.5q-42 0-71-29t-29-71q0-32 17.5-57.5T684-694l-84-84 42-42 148 144q15 15 22.5 35t7.5 41v380q0 42-29 71t-71 29q-42 0-71-29t-29-71v-200h-60v300H160Zm80-440h240v-200H240v200Zm480 0q17 0 28.5-11.5T760-600q0-17-11.5-28.5T720-640q-17 0-28.5 11.5T680-600q0 17 11.5 28.5T720-560ZM240-200h240v-280H240v280Zm240 0H240h240Z\"/></svg>', NULL, 1, 0, NOW(), NOW(), ''),
(65, 'adminUsers', 'Користувачі', 4, 30, '<svg xmlns=\"http://www.w3.org/2000/svg\" height=\"24px\" viewBox=\"0 -960 960 960\" width=\"24px\" fill=\"#1f1f1f\"><path d=\"M40-160v-112q0-34 17.5-62.5T104-378q62-31 126-46.5T360-440q66 0 130 15.5T616-378q29 15 46.5 43.5T680-272v112H40Zm720 0v-120q0-44-24.5-84.5T666-434q51 6 96 20.5t84 35.5q36 20 55 44.5t19 53.5v120H760ZM247-527q-47-47-47-113t47-113q47-47 113-47t113 47q47 47 47 113t-47 113q-47 47-113 47t-113-47Zm466 0q-47 47-113 47-11 0-28-2.5t-28-5.5q27-32 41.5-71t14.5-81q0-42-14.5-81T544-792q14-5 28-6.5t28-1.5q66 0 113 47t47 113q0 66-47 113ZM120-240h480v-32q0-11-5.5-20T580-306q-54-27-109-40.5T360-360q-56 0-111 13.5T140-306q-9 5-14.5 14t-5.5 20v32Zm296.5-343.5Q440-607 440-640t-23.5-56.5Q393-720 360-720t-56.5 23.5Q280-673 280-640t23.5 56.5Q327-560 360-560t56.5-23.5ZM360-240Zm0-400Z\"/></svg>', NULL, 1, 0, NOW(), NOW(), ''),
(66, 'adminMenu', 'Пункти меню', 4, 40, '<svg xmlns=\"http://www.w3.org/2000/svg\" height=\"24px\" viewBox=\"0 -960 960 960\" width=\"24px\" fill=\"#1f1f1f\"><path d=\"M80-160v-160h160v160H80Zm240 0v-160h560v160H320ZM80-400v-160h160v160H80Zm240 0v-160h560v160H320ZM80-640v-160h160v160H80Zm240 0v-160h560v160H320Z\"/></svg>', NULL, 1, 0, NOW(), NOW(), ''),
(67, 'adminPermissions', 'Права доступу', 4, 50, '<svg xmlns=\"http://www.w3.org/2000/svg\" height=\"24px\" viewBox=\"0 -960 960 960\" width=\"24px\" fill=\"#1f1f1f\"><path d=\"M430-200h100v-180h60v-184q0-27-28.5-41.5T480-620q-53 0-81.5 14.5T370-564v184h60v180Zm-105 88.5q-73-31.5-127.5-86t-86-127.5Q80-398 80-480.5t31.5-155q31.5-72.5 86-127t127.5-86Q398-880 480.5-880t155 31.5q72.5 31.5 127 86t86 127Q880-563 880-480.5T848.5-325q-31.5 73-86 127.5t-127 86Q563-80 480.5-80T325-111.5Zm381.5-142Q800-347 800-480t-93.5-226.5Q613-800 480-800t-226.5 93.5Q160-613 160-480t93.5 226.5Q347-160 480-160t226.5-93.5ZM523-657q17-17 17-43t-17-43q-17-17-43-17t-43 17q-17 17-17 43t17 43q17 17 43 17t43-17Zm-43 177Z\"/></svg>', NULL, 1, 0, NOW(), NOW(), ''),
(68, 'adminBackup', 'Архівація БД', 4, 60, '<svg xmlns=\"http://www.w3.org/2000/svg\" height=\"24px\" viewBox=\"0 -960 960 960\" width=\"24px\" fill=\"#1f1f1f\"><path d=\"m480-240 160-160-56-56-64 64v-168h-80v168l-64-64-56 56 160 160ZM200-640v440h560v-440H200Zm0 520q-33 0-56.5-23.5T120-200v-499q0-14 4.5-27t13.5-24l50-61q11-14 27.5-21.5T250-840h460q18 0 34.5 7.5T772-811l50 61q9 11 13.5 24t4.5 27v499q0 33-23.5 56.5T760-120H200Zm16-600h528l-34-40H250l-34 40Zm264 300Z\"/></svg>', NULL, 1, 0, NOW(), NOW(), ''),
(69, 'adminRestore', 'Відновлення БД', 4, 70, '<svg xmlns=\"http://www.w3.org/2000/svg\" height=\"24px\" viewBox=\"0 -960 960 960\" width=\"24px\" fill=\"#1f1f1f\"><path d=\"M480-560 320-400l56 56 64-64v168h80v-168l64 64 56-56-160-160Zm-280-80v440h560v-440H200Zm0 520q-33 0-56.5-23.5T120-200v-499q0-14 4.5-27t13.5-24l50-61q11-14 27.5-21.5T250-840h460q18 0 34.5 7.5T772-811l50 61q9 11 13.5 24t4.5 27v499q0 33-23.5 56.5T760-120H200Zm16-600h528l-34-40H250l-34 40Zm264 300Z\"/></svg>', NULL, 1, 0, NOW(), NOW(), '');

INSERT INTO `resource_types` (`id`, `name`, `unit`, `format`, `show_hours`, `author`, `created_at`) VALUES
(1, 'Пробіг', 'км', 'int', 0, '', NOW()),
(2, 'Мотогодини', 'год', 'hm', 0, '', NOW()),
(3, 'Напрацювання', 'год', 'dec2', 1, '', NOW());

INSERT INTO `user_roles` (`id`, `nc_user`, `role`, `allowed_warehouses`, `allowed_materials`, `allowed_resource_types`, `can_edit_rates`, `can_export`, `can_import`, `created_at`, `updated_at`, `author`, `password_hash`, `is_local`, `email`) VALUES
(1, 'admin', 'manager', NULL, NULL, NULL, 1, 1, 1, NOW(), NOW(), '', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, '');

INSERT INTO `user_menu_permissions` (`nc_user`, `menu_item_id`, `access_level`, `created_at`, `updated_at`, `author`) VALUES
('admin', 47, 'edit', NOW(), NOW(), ''),
('admin', 48, 'edit', NOW(), NOW(), ''),
('admin', 49, 'edit', NOW(), NOW(), ''),
('admin', 50, 'edit', NOW(), NOW(), ''),
('admin', 51, 'edit', NOW(), NOW(), ''),
('admin', 52, 'edit', NOW(), NOW(), ''),
('admin', 53, 'edit', NOW(), NOW(), ''),
('admin', 54, 'edit', NOW(), NOW(), ''),
('admin', 55, 'edit', NOW(), NOW(), ''),
('admin', 56, 'edit', NOW(), NOW(), ''),
('admin', 57, 'edit', NOW(), NOW(), ''),
('admin', 65, 'edit', NOW(), NOW(), ''),
('admin', 66, 'edit', NOW(), NOW(), ''),
('admin', 67, 'edit', NOW(), NOW(), ''),
('admin', 68, 'edit', NOW(), NOW(), ''),
('admin', 69, 'edit', NOW(), NOW(), '');

-- ============================================
-- Зовнішні ключі
-- ============================================

ALTER TABLE `menu_items`
  ADD CONSTRAINT `menu_items_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `menu_items` (`id`) ON DELETE SET NULL;

ALTER TABLE `movements`
  ADD CONSTRAINT `fk_movement_material` FOREIGN KEY (`material_id`) REFERENCES `materials` (`id`),
  ADD CONSTRAINT `fk_movement_resource_log` FOREIGN KEY (`resource_log_id`) REFERENCES `resource_logs` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_movement_warehouse_from` FOREIGN KEY (`warehouse_from_id`) REFERENCES `warehouses` (`id`),
  ADD CONSTRAINT `fk_movement_warehouse_to` FOREIGN KEY (`warehouse_to_id`) REFERENCES `warehouses` (`id`);

ALTER TABLE `resource_logs`
  ADD CONSTRAINT `resource_logs_ibfk_1` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `resource_logs_ibfk_2` FOREIGN KEY (`resource_type_id`) REFERENCES `resource_types` (`id`) ON DELETE CASCADE;

ALTER TABLE `resource_rates`
  ADD CONSTRAINT `resource_rates_ibfk_1` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `resource_rates_ibfk_2` FOREIGN KEY (`resource_type_id`) REFERENCES `resource_types` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `resource_rates_ibfk_3` FOREIGN KEY (`material_id`) REFERENCES `materials` (`id`),
  ADD CONSTRAINT `resource_rates_ibfk_4` FOREIGN KEY (`source_warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL;

ALTER TABLE `user_menu_permissions`
  ADD CONSTRAINT `user_menu_permissions_ibfk_1` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE;

ALTER TABLE `warehouse_resources`
  ADD CONSTRAINT `warehouse_resources_ibfk_1` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `warehouse_resources_ibfk_2` FOREIGN KEY (`resource_type_id`) REFERENCES `resource_types` (`id`) ON DELETE CASCADE;
