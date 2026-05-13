DROP TABLE IF EXISTS `config`;
CREATE TABLE `config` (
  `key` varchar(50) NOT NULL,
  `value` varchar(255) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
INSERT INTO `config` VALUES
('closed_date','2026-05-07','2026.05.11, Сергій Слободян'),
('simple_materials','[18,17]','2026.05.13, Сергій Слободян'),
('simple_warehouse','5','2026.05.13, Сергій Слободян'),
('simple_warehouses','[47,48,49,8,9,10,11,12,13,37,35,29,38,41,42,43,39,40,24,30,36,25,27,28,31,32,26,33,50]','2026.05.13, Сергій Слободян');
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
DELIMITER ;;
BEFORE UPDATE ON config
FOR EACH ROW
BEGIN
    INSERT INTO config_history (action, changed_by, `key`, `value`, `author`)
    VALUES ('UPDATE', @current_user, OLD.`key`, OLD.`value`, OLD.`author`);
END */;;
DELIMITER ;
DELIMITER ;;
BEFORE DELETE ON config
FOR EACH ROW
BEGIN
    INSERT INTO config_history (action, changed_by, `key`, `value`, `author`)
    VALUES ('DELETE', @current_user, OLD.`key`, OLD.`value`, OLD.`author`);
END */;;
DELIMITER ;
DROP TABLE IF EXISTS `materials`;
CREATE TABLE `materials` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
INSERT INTO `materials` VALUES
(11,'Олива 0W40',NULL,'2026-05-08 06:56:05','2026-05-08 06:56:05'),
(12,'Олива 80W90',NULL,'2026-05-08 06:56:05','2026-05-08 06:56:05'),
(13,'Олива 15W40',NULL,'2026-05-08 06:56:05','2026-05-08 06:56:05'),
(14,'Мастило М10Г2К',NULL,'2026-05-08 06:56:05','2026-05-08 06:56:05'),
(15,'Гідравліка','2026.05.11, Сергій Слободян','2026-05-08 06:56:05','2026-05-11 17:16:40'),
(16,'Мастило М6З10В',NULL,'2026-05-08 06:56:05','2026-05-08 06:56:05'),
(17,'Дизпаливо',NULL,'2026-05-09 14:40:11','2026-05-09 14:40:11'),
(18,'Бензин','2026.05.12, Сергій Слободян','2026-05-12 10:00:20','2026-05-12 10:00:20');
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
DELIMITER ;;
BEFORE UPDATE ON materials
FOR EACH ROW
BEGIN
    INSERT INTO materials_history (action, changed_by, id, name, author, created_at)
    VALUES ('UPDATE', @current_user, OLD.id, OLD.name, OLD.author, OLD.created_at);
END */;;
DELIMITER ;
DELIMITER ;;
BEFORE DELETE ON materials
FOR EACH ROW
BEGIN
    INSERT INTO materials_history (action, changed_by, id, name, author, created_at)
    VALUES ('DELETE', @current_user, OLD.id, OLD.name, OLD.author, OLD.created_at);
END */;;
DELIMITER ;
DROP TABLE IF EXISTS `movements`;
CREATE TABLE `movements` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `movement_date` date NOT NULL,
  `warehouse_from_id` int(10) unsigned DEFAULT NULL,
  `warehouse_to_id` int(10) unsigned DEFAULT NULL,
  `material_id` int(10) unsigned NOT NULL,
  `quantity` decimal(15,2) NOT NULL,
  `note` text DEFAULT NULL,
  `author` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `resource_log_id` int(10) unsigned DEFAULT NULL,
  `resource_value` decimal(15,6) DEFAULT NULL,
  `resource_delta` decimal(15,6) DEFAULT NULL,
  `resource_rate` decimal(15,6) DEFAULT NULL,
  `resource_correction` decimal(6,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_movement_date` (`movement_date`),
  KEY `idx_warehouse_from` (`warehouse_from_id`),
  KEY `idx_warehouse_to` (`warehouse_to_id`),
  KEY `idx_material` (`material_id`),
  KEY `idx_resource_log` (`resource_log_id`),
  CONSTRAINT `fk_movement_material` FOREIGN KEY (`material_id`) REFERENCES `materials` (`id`),
  CONSTRAINT `fk_movement_resource_log` FOREIGN KEY (`resource_log_id`) REFERENCES `resource_logs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_movement_warehouse_from` FOREIGN KEY (`warehouse_from_id`) REFERENCES `warehouses` (`id`),
  CONSTRAINT `fk_movement_warehouse_to` FOREIGN KEY (`warehouse_to_id`) REFERENCES `warehouses` (`id`),
  CONSTRAINT `chk_at_least_one_warehouse` CHECK (`warehouse_from_id` is not null or `warehouse_to_id` is not null),
  CONSTRAINT `chk_different_warehouses` CHECK (`warehouse_from_id` is null or `warehouse_to_id` is null or `warehouse_from_id` <> `warehouse_to_id`),
  CONSTRAINT `chk_positive_quantity` CHECK (`quantity` > 0)
) ENGINE=InnoDB AUTO_INCREMENT=173 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
INSERT INTO `movements` VALUES
(12,'2025-01-01',5,47,13,20.00,'двигун на 4000 км',NULL,'2026-05-08 07:16:39','2026-05-08 07:16:39',NULL,NULL,NULL,NULL,NULL),
(13,'2025-01-01',5,47,12,18.00,'КПП на 4000 км',NULL,'2026-05-08 07:16:39','2026-05-08 07:16:39',NULL,NULL,NULL,NULL,NULL),
(14,'2025-01-01',5,49,13,20.00,'двигун на 4000 км',NULL,'2026-05-08 07:16:39','2026-05-08 07:16:39',NULL,NULL,NULL,NULL,NULL),
(15,'2025-01-01',5,49,12,18.00,'КПП на 4000 км',NULL,'2026-05-08 07:16:39','2026-05-08 07:16:39',NULL,NULL,NULL,NULL,NULL),
(16,'2025-01-01',5,48,13,20.00,'двигун на 4000 км',NULL,'2026-05-08 07:16:39','2026-05-08 07:16:39',NULL,NULL,NULL,NULL,NULL),
(17,'2025-01-01',5,48,12,18.00,'КПП на 4000 км',NULL,'2026-05-08 07:16:39','2026-05-08 07:16:39',NULL,NULL,NULL,NULL,NULL),
(18,'2025-01-01',NULL,5,13,60.00,'на заміни по DAEWOO',NULL,'2026-05-08 07:16:39','2026-05-09 20:15:45',NULL,NULL,NULL,NULL,NULL),
(19,'2025-01-01',NULL,5,12,54.00,'на заміни по DAEWOO',NULL,'2026-05-08 07:16:39','2026-05-09 20:15:41',NULL,NULL,NULL,NULL,NULL),
(20,'2025-06-01',5,47,12,8.00,'роздатка на 5000 км',NULL,'2026-05-08 07:16:39','2026-05-08 07:16:39',NULL,NULL,NULL,NULL,NULL),
(21,'2025-06-01',5,47,12,14.00,'передній міст на 5000 км',NULL,'2026-05-08 07:16:39','2026-05-08 07:16:39',NULL,NULL,NULL,NULL,NULL),
(22,'2025-06-01',5,47,12,14.00,'середній міст на 5000 км',NULL,'2026-05-08 07:16:39','2026-05-08 07:16:39',NULL,NULL,NULL,NULL,NULL),
(23,'2025-06-01',5,47,12,12.00,'задній міст на 5000 км',NULL,'2026-05-08 07:16:39','2026-05-08 07:16:39',NULL,NULL,NULL,NULL,NULL),
(24,'2025-06-01',5,49,12,8.00,'роздатка на 5000 км',NULL,'2026-05-08 07:16:39','2026-05-08 07:16:39',NULL,NULL,NULL,NULL,NULL),
(25,'2025-06-01',5,49,12,14.00,'передній міст на 5000 км',NULL,'2026-05-08 07:16:39','2026-05-08 07:16:39',NULL,NULL,NULL,NULL,NULL),
(26,'2025-06-01',5,49,12,14.00,'середній міст на 5000 км',NULL,'2026-05-08 07:16:39','2026-05-08 07:16:39',NULL,NULL,NULL,NULL,NULL),
(27,'2025-06-01',5,49,12,12.00,'задній міст на 5000 км',NULL,'2026-05-08 07:16:39','2026-05-08 07:16:39',NULL,NULL,NULL,NULL,NULL),
(28,'2025-06-01',5,48,12,8.00,'роздатка на 5000 км',NULL,'2026-05-08 07:16:39','2026-05-08 07:16:39',NULL,NULL,NULL,NULL,NULL),
(29,'2025-06-01',5,48,12,14.00,'передній міст на 5000 км',NULL,'2026-05-08 07:16:39','2026-05-08 07:16:39',NULL,NULL,NULL,NULL,NULL),
(30,'2025-06-01',5,48,12,14.00,'середній міст на 5000 км',NULL,'2026-05-08 07:16:39','2026-05-08 07:16:39',NULL,NULL,NULL,NULL,NULL),
(31,'2025-06-01',5,48,12,12.00,'задній міст на 5000 км',NULL,'2026-05-08 07:16:39','2026-05-08 07:16:39',NULL,NULL,NULL,NULL,NULL),
(32,'2026-03-31',NULL,5,13,230.00,'початкові дані',NULL,'2026-05-08 07:16:39','2026-05-08 07:16:39',NULL,NULL,NULL,NULL,NULL),
(33,'2026-03-31',NULL,5,14,30.00,'початкові дані',NULL,'2026-05-08 07:16:39','2026-05-08 07:16:39',NULL,NULL,NULL,NULL,NULL),
(34,'2026-03-31',NULL,5,15,50.00,'початкові дані',NULL,'2026-05-08 07:16:39','2026-05-08 07:16:39',NULL,NULL,NULL,NULL,NULL),
(35,'2026-04-23',NULL,5,11,20.00,'',NULL,'2026-05-08 07:16:39','2026-05-09 20:15:30',NULL,NULL,NULL,NULL,NULL),
(36,'2026-04-23',NULL,5,12,10.00,'',NULL,'2026-05-08 07:16:39','2026-05-09 20:15:24',NULL,NULL,NULL,NULL,NULL),
(37,'2026-04-24',5,32,13,40.00,'заміна в двигуні на 24908 км і долив в ГУР',NULL,'2026-05-08 07:16:39','2026-05-08 07:16:39',NULL,NULL,NULL,NULL,NULL),
(38,'2026-04-24',5,41,13,40.00,'заміна в двигуні на 31062 км і долив в АКПП і ГУР',NULL,'2026-05-08 07:16:39','2026-05-08 07:16:39',NULL,NULL,NULL,NULL,NULL),
(39,'2026-04-24',5,50,14,30.00,'на 43439 км',NULL,'2026-05-08 07:16:39','2026-05-08 07:16:39',NULL,NULL,NULL,NULL,NULL),
(40,'2026-05-02',NULL,5,14,20.00,'',NULL,'2026-05-08 07:16:39','2026-05-09 20:15:18',NULL,NULL,NULL,NULL,NULL),
(41,'2025-06-01',NULL,5,12,144.00,'на заміни по DAEWOO',NULL,'2026-05-08 07:16:39','2026-05-09 20:15:35',NULL,NULL,NULL,NULL,NULL),
(42,'2026-05-07',5,24,13,30.00,'заміна на 43369 км',NULL,'2026-05-08 07:16:39','2026-05-08 07:16:39',NULL,NULL,NULL,NULL,NULL),
(112,'2026-05-01',50,NULL,14,30.00,'',NULL,'2026-05-10 13:52:06','2026-05-10 13:52:06',NULL,NULL,NULL,NULL,NULL),
(113,'2026-05-01',47,NULL,13,20.00,'',NULL,'2026-05-10 13:53:00','2026-05-10 13:53:00',NULL,NULL,NULL,NULL,NULL),
(114,'2026-05-01',48,NULL,13,20.00,'',NULL,'2026-05-10 13:53:17','2026-05-10 13:53:17',NULL,NULL,NULL,NULL,NULL),
(115,'2026-05-01',49,NULL,13,20.00,'',NULL,'2026-05-10 13:53:34','2026-05-10 13:53:34',NULL,NULL,NULL,NULL,NULL),
(116,'2026-05-01',41,NULL,13,40.00,'',NULL,'2026-05-10 13:54:15','2026-05-10 14:01:58',NULL,NULL,NULL,NULL,NULL),
(117,'2026-05-07',24,NULL,13,30.00,'',NULL,'2026-05-10 13:54:45','2026-05-10 14:02:20',NULL,NULL,NULL,NULL,NULL),
(118,'2026-05-01',32,NULL,13,40.00,'',NULL,'2026-05-10 13:55:08','2026-05-10 13:55:08',NULL,NULL,NULL,NULL,NULL),
(119,'2026-05-01',47,NULL,12,66.00,'',NULL,'2026-05-10 13:55:44','2026-05-10 13:55:44',NULL,NULL,NULL,NULL,NULL),
(120,'2026-05-01',48,NULL,12,66.00,'',NULL,'2026-05-10 13:55:58','2026-05-10 13:55:58',NULL,NULL,NULL,NULL,NULL),
(121,'2026-05-01',49,NULL,12,66.00,'',NULL,'2026-05-10 13:56:11','2026-05-10 13:56:11',NULL,NULL,NULL,NULL,NULL);
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
DELIMITER ;;
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
END */;;
DELIMITER ;
DELIMITER ;;
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
END */;;
DELIMITER ;
DROP TABLE IF EXISTS `resource_logs`;
CREATE TABLE `resource_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `warehouse_id` int(10) unsigned NOT NULL,
  `resource_type_id` int(10) unsigned NOT NULL,
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
  KEY `resource_type_id` (`resource_type_id`),
  CONSTRAINT `resource_logs_ibfk_1` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `resource_logs_ibfk_2` FOREIGN KEY (`resource_type_id`) REFERENCES `resource_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
INSERT INTO `resource_logs` VALUES
(26,47,1,'2026-05-08',100.000000,NULL,NULL,0.00,'','2026.05.13, Сергій Слободян','2026-05-13 06:36:42'),
(28,47,1,'2026-05-09',400.000000,100.000000,300.000000,0.00,'','2026.05.13, Сергій Слободян','2026-05-13 06:53:57'),
(29,8,2,'2026-05-08',1.000000,NULL,NULL,0.00,'','2026.05.13, Сергій Слободян','2026-05-13 08:41:21'),
(30,8,2,'2026-05-13',11.000000,1.000000,10.000000,0.00,'','2026.05.13, Сергій Слободян','2026-05-13 08:41:37');
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
DELIMITER ;;
BEFORE UPDATE ON resource_logs
FOR EACH ROW
BEGIN
    INSERT INTO resource_logs_history (
        action, changed_by, id, warehouse_id, resource_type_id, log_date,
        reading, prev_reading, delta, note, author, created_at
    )
    VALUES (
        'UPDATE', @current_user, OLD.id, OLD.warehouse_id, OLD.resource_type_id, OLD.log_date,
        OLD.reading, OLD.prev_reading, OLD.delta, OLD.note, OLD.author, OLD.created_at
    );
END */;;
DELIMITER ;
DELIMITER ;;
BEFORE DELETE ON resource_logs
FOR EACH ROW
BEGIN
    INSERT INTO resource_logs_history (
        action, changed_by, id, warehouse_id, resource_type_id, log_date,
        reading, prev_reading, delta, note, author, created_at
    )
    VALUES (
        'DELETE', @current_user, OLD.id, OLD.warehouse_id, OLD.resource_type_id, OLD.log_date,
        OLD.reading, OLD.prev_reading, OLD.delta, OLD.note, OLD.author, OLD.created_at
    );
END */;;
DELIMITER ;
DROP TABLE IF EXISTS `resource_rates`;
CREATE TABLE `resource_rates` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `warehouse_id` int(10) unsigned NOT NULL,
  `resource_type_id` int(10) unsigned NOT NULL,
  `material_id` int(10) unsigned NOT NULL,
  `rate` decimal(15,6) NOT NULL,
  `source_warehouse_id` int(10) unsigned DEFAULT NULL,
  `spread_by_day` tinyint(1) NOT NULL DEFAULT 0,
  `author` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_rate` (`warehouse_id`,`resource_type_id`,`material_id`),
  KEY `resource_type_id` (`resource_type_id`),
  KEY `material_id` (`material_id`),
  KEY `source_warehouse_id` (`source_warehouse_id`),
  CONSTRAINT `resource_rates_ibfk_1` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `resource_rates_ibfk_2` FOREIGN KEY (`resource_type_id`) REFERENCES `resource_types` (`id`) ON DELETE CASCADE,
  CONSTRAINT `resource_rates_ibfk_3` FOREIGN KEY (`material_id`) REFERENCES `materials` (`id`),
  CONSTRAINT `resource_rates_ibfk_4` FOREIGN KEY (`source_warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
INSERT INTO `resource_rates` VALUES
(16,47,1,17,0.500000,NULL,0,'2026.05.13, Сергій Слободян'),
(17,8,2,17,13.000000,NULL,1,'2026.05.13, Сергій Слободян');
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
DELIMITER ;;
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
END */;;
DELIMITER ;
DELIMITER ;;
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
END */;;
DELIMITER ;
DROP TABLE IF EXISTS `resource_types`;
CREATE TABLE `resource_types` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `unit` varchar(30) NOT NULL,
  `format` varchar(10) NOT NULL DEFAULT 'int',
  `author` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
INSERT INTO `resource_types` VALUES
(1,'Пробіг','км','int',NULL,'2026-05-09 14:38:37'),
(2,'Мотогодини','год','hm',NULL,'2026-05-09 14:38:37');
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
DELIMITER ;;
BEFORE UPDATE ON resource_types
FOR EACH ROW
BEGIN
    INSERT INTO resource_types_history (action, changed_by, id, name, unit, format, author, created_at)
    VALUES ('UPDATE', @current_user, OLD.id, OLD.name, OLD.unit, OLD.format, OLD.author, OLD.created_at);
END */;;
DELIMITER ;
DELIMITER ;;
BEFORE DELETE ON resource_types
FOR EACH ROW
BEGIN
    INSERT INTO resource_types_history (action, changed_by, id, name, unit, format, author, created_at)
    VALUES ('DELETE', @current_user, OLD.id, OLD.name, OLD.unit, OLD.format, OLD.author, OLD.created_at);
END */;;
DELIMITER ;
DROP TABLE IF EXISTS `warehouse_resources`;
CREATE TABLE `warehouse_resources` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `warehouse_id` int(10) unsigned NOT NULL,
  `resource_type_id` int(10) unsigned NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_wh_res` (`warehouse_id`,`resource_type_id`),
  KEY `resource_type_id` (`resource_type_id`),
  CONSTRAINT `warehouse_resources_ibfk_1` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `warehouse_resources_ibfk_2` FOREIGN KEY (`resource_type_id`) REFERENCES `resource_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
INSERT INTO `warehouse_resources` VALUES
(7,47,1,'2026.05.13, Сергій Слободян'),
(8,8,2,'2026.05.13, Сергій Слободян');
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
DELIMITER ;;
BEFORE UPDATE ON warehouse_resources
FOR EACH ROW
BEGIN
    INSERT INTO warehouse_resources_history (action, changed_by, id, warehouse_id, resource_type_id, author)
    VALUES ('UPDATE', @current_user, OLD.id, OLD.warehouse_id, OLD.resource_type_id, OLD.author);
END */;;
DELIMITER ;
DELIMITER ;;
BEFORE DELETE ON warehouse_resources
FOR EACH ROW
BEGIN
    INSERT INTO warehouse_resources_history (action, changed_by, id, warehouse_id, resource_type_id, author)
    VALUES ('DELETE', @current_user, OLD.id, OLD.warehouse_id, OLD.resource_type_id, OLD.author);
END */;;
DELIMITER ;
DROP TABLE IF EXISTS `warehouses`;
CREATE TABLE `warehouses` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
INSERT INTO `warehouses` VALUES
(5,'Cклад ПММ','2026.05.11, Сергій Слободян','2026-05-08 06:51:05','2026-05-11 14:38:54'),
(6,'ВCP №730203',NULL,'2026-05-08 06:51:05','2026-05-08 11:22:43'),
(7,'СWAR №720201',NULL,'2026-05-08 06:51:05','2026-05-08 11:22:33'),
(8,'MEP-1071 A160906228',NULL,'2026-05-08 06:51:05','2026-05-08 06:51:05'),
(9,'MEP-1071 A160906230',NULL,'2026-05-08 06:51:05','2026-05-08 06:51:05'),
(10,'MEP-1071 A160906238',NULL,'2026-05-08 06:51:05','2026-05-08 06:51:05'),
(11,'MEP-1071A C200746325',NULL,'2026-05-08 06:51:05','2026-05-08 06:51:05'),
(12,'MEP-1071A C200746326',NULL,'2026-05-08 06:51:05','2026-05-08 06:51:05'),
(13,'MEP-1071A C200746327',NULL,'2026-05-08 06:51:05','2026-05-08 06:51:05'),
(14,'HPI 740293',NULL,'2026-05-08 06:51:05','2026-05-08 06:51:05'),
(15,'HPI 720201',NULL,'2026-05-08 06:51:05','2026-05-08 06:51:05'),
(16,'DLN №275002','2026.05.11, Сергій Слободян','2026-05-08 06:51:05','2026-05-11 17:16:30'),
(17,'DLN №340122',NULL,'2026-05-08 06:51:05','2026-05-08 06:51:05'),
(18,'DLN №380240',NULL,'2026-05-08 06:51:05','2026-05-08 06:51:05'),
(19,'DLN №380242',NULL,'2026-05-08 06:51:05','2026-05-08 06:51:05'),
(20,'DLN №400276',NULL,'2026-05-08 06:51:05','2026-05-08 06:51:05'),
(21,'DLN №400319',NULL,'2026-05-08 06:51:05','2026-05-08 06:51:05'),
(22,'LOADER №10056',NULL,'2026-05-08 06:51:05','2026-05-08 06:51:05'),
(23,'LOADER №600027',NULL,'2026-05-08 06:51:05','2026-05-08 06:51:05'),
(24,'OSHKOSH 74-25',NULL,'2026-05-08 06:51:05','2026-05-08 06:51:05'),
(25,'OSHKOSH 74-32',NULL,'2026-05-08 06:51:05','2026-05-08 06:51:05'),
(26,'OSHKOSH 74-38',NULL,'2026-05-08 06:51:05','2026-05-08 06:51:05'),
(27,'OSHKOSH 74-33',NULL,'2026-05-08 06:51:05','2026-05-08 06:51:05'),
(28,'OSHKOSH 74-35',NULL,'2026-05-08 06:51:05','2026-05-08 06:51:05'),
(29,'OSHKOSH 74-12',NULL,'2026-05-08 06:51:05','2026-05-08 06:51:05'),
(30,'OSHKOSH 74-28',NULL,'2026-05-08 06:51:05','2026-05-08 06:51:05'),
(31,'OSHKOSH 74-36',NULL,'2026-05-08 06:51:05','2026-05-08 06:51:05'),
(32,'OSHKOSH 74-37',NULL,'2026-05-08 06:51:05','2026-05-08 06:51:05'),
(33,'OSHKOSH 74-39',NULL,'2026-05-08 06:51:05','2026-05-08 06:51:05'),
(35,'OSHKOSH 74-10',NULL,'2026-05-08 06:51:05','2026-05-08 06:51:05'),
(36,'OSHKOSH 74-29',NULL,'2026-05-08 06:51:05','2026-05-08 06:51:05'),
(37,'OSHKOSH 74-06',NULL,'2026-05-08 06:51:05','2026-05-08 06:51:05'),
(38,'OSHKOSH 74-14',NULL,'2026-05-08 06:51:05','2026-05-08 06:51:05'),
(39,'OSHKOSH 74-21',NULL,'2026-05-08 06:51:05','2026-05-08 06:51:05'),
(40,'OSHKOSH 74-23',NULL,'2026-05-08 06:51:05','2026-05-08 06:51:05'),
(41,'OSHKOSH 74-17',NULL,'2026-05-08 06:51:05','2026-05-08 06:51:05'),
(42,'OSHKOSH 74-18',NULL,'2026-05-08 06:51:05','2026-05-08 06:51:05'),
(43,'OSHKOSH 74-20',NULL,'2026-05-08 06:51:05','2026-05-08 06:51:05'),
(44,'PLS on Trailer №00934',NULL,'2026-05-08 06:51:05','2026-05-08 06:51:05'),
(45,'PLS on Trailer №70248',NULL,'2026-05-08 06:51:05','2026-05-08 06:51:05'),
(46,'ЗУ 23-2',NULL,'2026-05-08 06:51:05','2026-05-08 06:51:05'),
(47,'DAEWOO 72-45','2026.05.11, Сергій Слободян','2026-05-08 06:51:05','2026-05-11 14:55:03'),
(48,'DAEWOO 72-47','2026.05.11, Сергій Слободян','2026-05-08 06:51:05','2026-05-11 17:16:24'),
(49,'DAEWOO 72-50','2026.05.11, Сергій Слободян','2026-05-08 06:51:05','2026-05-11 17:16:27'),
(50,'Урал 70-39',NULL,'2026-05-08 06:51:05','2026-05-08 06:51:05');
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
DELIMITER ;;
BEFORE UPDATE ON warehouses
FOR EACH ROW
BEGIN
    INSERT INTO warehouses_history (action, changed_by, id, name, author, created_at)
    VALUES ('UPDATE', @current_user, OLD.id, OLD.name, OLD.author, OLD.created_at);
END */;;
DELIMITER ;
DELIMITER ;;
BEFORE DELETE ON warehouses
FOR EACH ROW
BEGIN
    INSERT INTO warehouses_history (action, changed_by, id, name, author, created_at)
    VALUES ('DELETE', @current_user, OLD.id, OLD.name, OLD.author, OLD.created_at);
END */;;
DELIMITER ;
