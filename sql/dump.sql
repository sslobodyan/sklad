/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.8.6-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: sklad
-- ------------------------------------------------------
-- Server version	11.8.6-MariaDB-0+deb13u1 from Debian

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `config`
--

DROP TABLE IF EXISTS `config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `config` (
  `key` varchar(50) NOT NULL,
  `value` varchar(255) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `config`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `config` WRITE;
/*!40000 ALTER TABLE `config` DISABLE KEYS */;
INSERT INTO `config` VALUES
('closed_date','2026-05-07','2026.05.11, Сергій Слободян'),
('simple_materials','[18,17]','2026.05.12, Сергій Слободян'),
('simple_warehouse','5','2026.05.12, Сергій Слободян'),
('simple_warehouses','[47,48,49,8,9,10,11,12,13,37,35,29,38,41,42,43,39,40,24,30,36,25,27,28,31,32,26,33,50]','2026.05.12, Сергій Слободян');
/*!40000 ALTER TABLE `config` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_uca1400_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`sklad`@`localhost`*/ /*!50003 TRIGGER config_before_update
BEFORE UPDATE ON config
FOR EACH ROW
BEGIN
    INSERT INTO config_history (action, changed_by, `key`, `value`, `author`)
    VALUES ('UPDATE', @current_user, OLD.`key`, OLD.`value`, OLD.`author`);
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_uca1400_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`sklad`@`localhost`*/ /*!50003 TRIGGER config_before_delete
BEFORE DELETE ON config
FOR EACH ROW
BEGIN
    INSERT INTO config_history (action, changed_by, `key`, `value`, `author`)
    VALUES ('DELETE', @current_user, OLD.`key`, OLD.`value`, OLD.`author`);
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `config_history`
--

DROP TABLE IF EXISTS `config_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `config_history` (
  `history_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `action` enum('UPDATE','DELETE') NOT NULL,
  `changed_at` timestamp NULL DEFAULT current_timestamp(),
  `changed_by` varchar(255) DEFAULT NULL,
  `key` varchar(50) NOT NULL,
  `value` varchar(255) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`history_id`),
  KEY `idx_config_history_key` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `config_history`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `config_history` WRITE;
/*!40000 ALTER TABLE `config_history` DISABLE KEYS */;
INSERT INTO `config_history` VALUES
(1,'UPDATE','2026-05-11 19:28:32','2026.05.11, Сергій Слободян','closed_date','2026-05-07',NULL),
(2,'UPDATE','2026-05-11 20:07:48','2026.05.11, Сергій Слободян','closed_date','2026-05-07','2026.05.11, Сергій Слободян'),
(3,'UPDATE','2026-05-12 10:00:35','2026.05.12, Сергій Слободян','simple_warehouse','5','2026.05.11, Сергій Слободян'),
(4,'UPDATE','2026-05-12 10:00:35','2026.05.12, Сергій Слободян','simple_materials','[15,17]','2026.05.11, Сергій Слободян'),
(5,'UPDATE','2026-05-12 10:05:11','2026.05.12, Сергій Слободян','simple_warehouse','5','2026.05.12, Сергій Слободян'),
(6,'UPDATE','2026-05-12 10:05:11','2026.05.12, Сергій Слободян','simple_materials','[18]','2026.05.12, Сергій Слободян'),
(7,'UPDATE','2026-05-12 10:05:26','2026.05.12, Сергій Слободян','simple_warehouse','5','2026.05.12, Сергій Слободян'),
(8,'UPDATE','2026-05-12 10:05:26','2026.05.12, Сергій Слободян','simple_materials','[18]','2026.05.12, Сергій Слободян'),
(9,'UPDATE','2026-05-12 10:05:46','2026.05.12, Сергій Слободян','simple_warehouse','5','2026.05.12, Сергій Слободян'),
(10,'UPDATE','2026-05-12 10:05:46','2026.05.12, Сергій Слободян','simple_materials','[18]','2026.05.12, Сергій Слободян'),
(11,'UPDATE','2026-05-12 10:07:27',NULL,'simple_materials','[0]','2026.05.12, Сергій Слободян'),
(12,'UPDATE','2026-05-12 12:28:03','2026.05.12, Сергій Слободян','simple_warehouse','5','2026.05.12, Сергій Слободян'),
(13,'UPDATE','2026-05-12 12:28:03','2026.05.12, Сергій Слободян','simple_materials','[17,18]','2026.05.12, Сергій Слободян'),
(14,'UPDATE','2026-05-12 12:29:27','2026.05.12, Сергій Слободян','simple_warehouse','5','2026.05.12, Сергій Слободян'),
(15,'UPDATE','2026-05-12 12:29:27','2026.05.12, Сергій Слободян','simple_materials','[18,15,17]','2026.05.12, Сергій Слободян'),
(16,'UPDATE','2026-05-12 12:30:32','2026.05.12, Сергій Слободян','simple_warehouse','5','2026.05.12, Сергій Слободян'),
(17,'UPDATE','2026-05-12 12:30:32','2026.05.12, Сергій Слободян','simple_materials','[18,15,17]','2026.05.12, Сергій Слободян'),
(18,'UPDATE','2026-05-12 12:31:15','2026.05.12, Сергій Слободян','simple_warehouse','5','2026.05.12, Сергій Слободян'),
(19,'UPDATE','2026-05-12 12:31:15','2026.05.12, Сергій Слободян','simple_materials','[18,15,17]','2026.05.12, Сергій Слободян'),
(20,'UPDATE','2026-05-12 12:31:25','2026.05.12, Сергій Слободян','simple_warehouse','5','2026.05.12, Сергій Слободян'),
(21,'UPDATE','2026-05-12 12:31:25','2026.05.12, Сергій Слободян','simple_materials','[18,17]','2026.05.12, Сергій Слободян'),
(22,'UPDATE','2026-05-12 12:32:17','2026.05.12, Сергій Слободян','simple_warehouse','5','2026.05.12, Сергій Слободян'),
(23,'UPDATE','2026-05-12 12:32:17','2026.05.12, Сергій Слободян','simple_materials','[18,17,16]','2026.05.12, Сергій Слободян'),
(24,'UPDATE','2026-05-12 12:32:36','2026.05.12, Сергій Слободян','simple_warehouse','5','2026.05.12, Сергій Слободян'),
(25,'UPDATE','2026-05-12 12:32:36','2026.05.12, Сергій Слободян','simple_materials','[18,15,17,16]','2026.05.12, Сергій Слободян'),
(26,'UPDATE','2026-05-12 12:34:12','2026.05.12, Сергій Слободян','simple_warehouse','5','2026.05.12, Сергій Слободян'),
(27,'DELETE','2026-05-12 12:34:12','2026.05.12, Сергій Слободян','simple_materials','[18,17]','2026.05.12, Сергій Слободян'),
(28,'UPDATE','2026-05-12 12:35:25','2026.05.12, Сергій Слободян','simple_warehouse','5','2026.05.12, Сергій Слободян'),
(29,'UPDATE','2026-05-12 15:42:41','2026.05.12, Сергій Слободян','simple_warehouse','5','2026.05.12, Сергій Слободян'),
(30,'UPDATE','2026-05-12 15:42:41','2026.05.12, Сергій Слободян','simple_materials','[18,17]','2026.05.12, Сергій Слободян');
/*!40000 ALTER TABLE `config_history` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `materials`
--

DROP TABLE IF EXISTS `materials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `materials` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `materials`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `materials` WRITE;
/*!40000 ALTER TABLE `materials` DISABLE KEYS */;
INSERT INTO `materials` VALUES
(11,'Олива 0W40',NULL,'2026-05-08 06:56:05','2026-05-08 06:56:05'),
(12,'Олива 80W90',NULL,'2026-05-08 06:56:05','2026-05-08 06:56:05'),
(13,'Олива 15W40',NULL,'2026-05-08 06:56:05','2026-05-08 06:56:05'),
(14,'Мастило М10Г2К',NULL,'2026-05-08 06:56:05','2026-05-08 06:56:05'),
(15,'Гідравліка','2026.05.11, Сергій Слободян','2026-05-08 06:56:05','2026-05-11 17:16:40'),
(16,'Мастило М6З10В',NULL,'2026-05-08 06:56:05','2026-05-08 06:56:05'),
(17,'Дизпаливо',NULL,'2026-05-09 14:40:11','2026-05-09 14:40:11'),
(18,'Бензин','2026.05.12, Сергій Слободян','2026-05-12 10:00:20','2026-05-12 10:00:20');
/*!40000 ALTER TABLE `materials` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_uca1400_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`sklad`@`localhost`*/ /*!50003 TRIGGER materials_before_update
BEFORE UPDATE ON materials
FOR EACH ROW
BEGIN
    INSERT INTO materials_history (action, changed_by, id, name, author, created_at)
    VALUES ('UPDATE', @current_user, OLD.id, OLD.name, OLD.author, OLD.created_at);
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_uca1400_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`sklad`@`localhost`*/ /*!50003 TRIGGER materials_before_delete
BEFORE DELETE ON materials
FOR EACH ROW
BEGIN
    INSERT INTO materials_history (action, changed_by, id, name, author, created_at)
    VALUES ('DELETE', @current_user, OLD.id, OLD.name, OLD.author, OLD.created_at);
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `materials_history`
--

DROP TABLE IF EXISTS `materials_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `materials_history` (
  `history_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `action` enum('UPDATE','DELETE') NOT NULL,
  `changed_at` timestamp NULL DEFAULT current_timestamp(),
  `changed_by` varchar(255) DEFAULT NULL,
  `id` int(10) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`history_id`),
  KEY `idx_materials_history_id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `materials_history`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `materials_history` WRITE;
/*!40000 ALTER TABLE `materials_history` DISABLE KEYS */;
INSERT INTO `materials_history` VALUES
(1,'UPDATE','2026-05-11 17:16:40','2026.05.11, Сергій Слободян',15,'Гідравліка',NULL,'2026-05-08 06:56:05',NULL);
/*!40000 ALTER TABLE `materials_history` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `movements`
--

DROP TABLE IF EXISTS `movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=149 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `movements`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `movements` WRITE;
/*!40000 ALTER TABLE `movements` DISABLE KEYS */;
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
(121,'2026-05-01',49,NULL,12,66.00,'',NULL,'2026-05-10 13:56:11','2026-05-10 13:56:11',NULL,NULL,NULL,NULL,NULL),
(140,'2026-05-12',NULL,5,17,5000.00,'Надходження (заправка)','2026.05.12, ip 100.127.38.75','2026-05-12 07:02:12','2026-05-12 07:02:12',NULL,NULL,NULL,NULL,NULL),
(141,'2026-05-12',5,47,17,100.00,'Видача (заправка)','2026.05.12, ip 100.127.38.75','2026-05-12 07:02:45','2026-05-12 07:02:45',NULL,NULL,NULL,NULL,NULL),
(142,'2026-05-11',NULL,5,17,1000.00,'Надходження (заправка)','2026.05.12, ip 100.127.38.75','2026-05-12 07:03:18','2026-05-12 07:03:18',NULL,NULL,NULL,NULL,NULL),
(143,'2026-05-12',NULL,5,17,1000.00,'Надходження (заправка)','2026.05.12, Сергій Слободян','2026-05-12 08:21:42','2026-05-12 08:21:42',NULL,NULL,NULL,NULL,NULL),
(144,'2026-05-13',NULL,5,17,159.00,'Надходження (заправка)','2026.05.12, Сергій Слободян','2026-05-12 08:27:35','2026-05-12 08:27:35',NULL,NULL,NULL,NULL,NULL),
(145,'2026-05-12',5,11,17,130.00,'Видача (заправка)','2026.05.12, ip 100.127.38.75','2026-05-12 14:50:00','2026-05-12 14:50:00',NULL,NULL,NULL,NULL,NULL),
(146,'2026-05-15',5,41,17,200.00,'Видача (заправка)','2026.05.12, ip 100.127.38.75','2026-05-12 14:54:53','2026-05-12 14:54:53',NULL,NULL,NULL,NULL,NULL),
(147,'2026-05-15',NULL,5,17,60000.00,'Надходження (заправка)','2026.05.12, ip 100.127.38.75','2026-05-12 14:55:45','2026-05-12 14:55:45',NULL,NULL,NULL,NULL,NULL),
(148,'2026-05-12',NULL,5,17,2000.00,'Надходження (заправка)','2026.05.12, ip 100.127.38.75','2026-05-12 16:53:45','2026-05-12 16:53:45',NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `movements` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_uca1400_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`sklad`@`localhost`*/ /*!50003 TRIGGER movements_before_update
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
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_uca1400_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`sklad`@`localhost`*/ /*!50003 TRIGGER movements_before_delete
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
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `movements_history`
--

DROP TABLE IF EXISTS `movements_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `movements_history` (
  `history_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `action` enum('UPDATE','DELETE') NOT NULL,
  `changed_at` timestamp NULL DEFAULT current_timestamp(),
  `changed_by` varchar(255) DEFAULT NULL,
  `id` int(10) unsigned NOT NULL,
  `movement_date` date NOT NULL,
  `warehouse_from_id` int(10) unsigned DEFAULT NULL,
  `warehouse_to_id` int(10) unsigned DEFAULT NULL,
  `material_id` int(10) unsigned NOT NULL,
  `quantity` decimal(15,2) NOT NULL,
  `note` text DEFAULT NULL,
  `author` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `resource_log_id` int(10) unsigned DEFAULT NULL,
  `resource_value` decimal(15,6) DEFAULT NULL,
  `resource_delta` decimal(15,6) DEFAULT NULL,
  `resource_rate` decimal(15,6) DEFAULT NULL,
  `resource_correction` decimal(6,2) DEFAULT NULL,
  PRIMARY KEY (`history_id`),
  KEY `idx_movements_history_id` (`id`),
  KEY `idx_movements_history_date` (`movement_date`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `movements_history`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `movements_history` WRITE;
/*!40000 ALTER TABLE `movements_history` DISABLE KEYS */;
INSERT INTO `movements_history` VALUES
(1,'UPDATE','2026-05-11 17:03:29','2026.05.11, Сергій Слободян',124,'2026-05-09',5,47,17,200.00,'',NULL,'2026-05-11 09:00:45','2026-05-11 09:00:45',NULL,NULL,NULL,NULL,NULL),
(2,'UPDATE','2026-05-11 17:03:35','2026.05.11, Сергій Слободян',124,'2026-05-09',5,47,17,200.01,'','2026.05.11, Сергій Слободян','2026-05-11 09:00:45','2026-05-11 17:03:29',NULL,NULL,NULL,NULL,NULL),
(3,'DELETE','2026-05-11 18:34:59','2026.05.11, Сергій Слободян',129,'2026-05-11',5,49,17,20.00,'Видача (заправка)','2026.05.11, Сергій Слободян','2026-05-11 18:20:39',NULL,NULL,NULL,NULL,NULL,NULL),
(4,'DELETE','2026-05-11 18:35:02','2026.05.11, Сергій Слободян',128,'2026-05-11',NULL,5,17,333.00,'Надходження (заправка)','2026.05.11, Сергій Слободян','2026-05-11 18:20:26',NULL,NULL,NULL,NULL,NULL,NULL),
(5,'DELETE','2026-05-11 18:35:09','2026.05.11, Сергій Слободян',127,'2026-05-11',5,47,17,20.00,'Видача (заправка)','2026.05.11, Сергій Слободян','2026-05-11 18:09:56',NULL,NULL,NULL,NULL,NULL,NULL),
(6,'DELETE','2026-05-11 18:35:19','2026.05.11, Сергій Слободян',126,'2026-05-11',NULL,5,17,100.00,'Надходження (заправка)','2026.05.11, Сергій Слободян','2026-05-11 18:08:54',NULL,NULL,NULL,NULL,NULL,NULL),
(7,'DELETE','2026-05-11 18:35:26','2026.05.11, Сергій Слободян',124,'2026-05-09',5,47,17,200.00,'','2026.05.11, Сергій Слободян','2026-05-11 09:00:45',NULL,NULL,NULL,NULL,NULL,NULL),
(8,'DELETE','2026-05-11 18:35:35',NULL,123,'2026-05-11',47,NULL,17,102.00,'Розр. (200 км, Нр 0.51), прогон',NULL,'2026-05-11 08:59:54',NULL,21,1200.000000,200.000000,0.510000,0.00),
(9,'DELETE','2026-05-11 19:00:18','2026.05.11, Сергій Слободян',131,'2026-05-11',NULL,5,17,5000.00,'Надходження (заправка)','2026.05.11, Сергій Слободян','2026-05-11 18:41:40',NULL,NULL,NULL,NULL,NULL,NULL),
(10,'DELETE','2026-05-11 19:22:39','2026.05.11, Сергій Слободян',130,'2026-05-08',NULL,5,17,12000.00,'','2026.05.11, Сергій Слободян','2026-05-11 18:41:07',NULL,NULL,NULL,NULL,NULL,NULL),
(11,'DELETE','2026-05-11 19:22:44','2026.05.11, Сергій Слободян',132,'2026-05-11',5,47,17,100.00,'Видача (заправка)','2026.05.11, Сергій Слободян','2026-05-11 18:41:52',NULL,NULL,NULL,NULL,NULL,NULL),
(12,'DELETE','2026-05-11 19:39:41','2026.05.11, Сергій Слободян',133,'2026-05-11',NULL,5,17,1.00,'','2026.05.11, Сергій Слободян','2026-05-11 19:39:15',NULL,NULL,NULL,NULL,NULL,NULL),
(13,'DELETE','2026-05-11 19:39:49','2026.05.11, Сергій Слободян',134,'2026-05-11',NULL,5,17,2.00,'','2026.05.11, Сергій Слободян','2026-05-11 19:39:26',NULL,NULL,NULL,NULL,NULL,NULL),
(14,'DELETE','2026-05-12 07:01:02','2026.05.12, Сергій Слободян',135,'2026-05-08',NULL,5,17,10000.00,'','2026.05.11, Сергій Слободян','2026-05-11 21:14:55',NULL,NULL,NULL,NULL,NULL,NULL),
(15,'DELETE','2026-05-12 07:01:04','2026.05.12, Сергій Слободян',136,'2026-05-10',NULL,5,17,200.00,'Надходження (заправка)','2026.05.11, ip 100.127.38.75','2026-05-11 21:17:39',NULL,NULL,NULL,NULL,NULL,NULL),
(16,'DELETE','2026-05-12 07:01:06','2026.05.12, Сергій Слободян',139,'2026-05-10',5,29,17,200.00,'Видача (заправка)','2026.05.11, ip 100.127.38.75','2026-05-11 22:06:14',NULL,NULL,NULL,NULL,NULL,NULL),
(17,'DELETE','2026-05-12 07:01:08','2026.05.12, Сергій Слободян',137,'2026-05-11',5,49,17,123.00,'Видача (заправка)','2026.05.11, Сергій Слободян','2026-05-11 21:36:20',NULL,NULL,NULL,NULL,NULL,NULL),
(18,'DELETE','2026-05-12 07:01:11','2026.05.12, Сергій Слободян',138,'2026-05-11',5,24,17,580.00,'Видача (заправка)','2026.05.11, ip 100.127.38.75','2026-05-11 21:37:23',NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `movements_history` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `resource_logs`
--

DROP TABLE IF EXISTS `resource_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `resource_logs`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `resource_logs` WRITE;
/*!40000 ALTER TABLE `resource_logs` DISABLE KEYS */;
INSERT INTO `resource_logs` VALUES
(22,47,1,'2026-05-08',1000.000000,NULL,NULL,0.00,'','2026.05.12, Сергій Слободян','2026-05-12 18:56:54'),
(23,47,1,'2026-05-09',1200.000000,NULL,NULL,0.00,'','2026.05.12, Сергій Слободян','2026-05-12 18:57:13');
/*!40000 ALTER TABLE `resource_logs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_uca1400_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`sklad`@`localhost`*/ /*!50003 TRIGGER resource_logs_before_update
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
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_uca1400_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`sklad`@`localhost`*/ /*!50003 TRIGGER resource_logs_before_delete
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
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `resource_logs_history`
--

DROP TABLE IF EXISTS `resource_logs_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `resource_logs_history` (
  `history_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `action` enum('UPDATE','DELETE') NOT NULL,
  `changed_at` timestamp NULL DEFAULT current_timestamp(),
  `changed_by` varchar(255) DEFAULT NULL,
  `id` int(10) unsigned NOT NULL,
  `warehouse_id` int(10) unsigned NOT NULL,
  `resource_type_id` int(10) unsigned NOT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `resource_logs_history`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `resource_logs_history` WRITE;
/*!40000 ALTER TABLE `resource_logs_history` DISABLE KEYS */;
INSERT INTO `resource_logs_history` VALUES
(1,'DELETE','2026-05-11 18:39:55',NULL,21,47,1,'2026-05-11',1200.00,1000.00,200.00,'прогон',NULL,'2026-05-11 08:59:54'),
(2,'DELETE','2026-05-11 18:39:58',NULL,16,47,1,'2026-05-10',1000.00,NULL,NULL,'',NULL,'2026-05-10 12:32:32'),
(3,'UPDATE','2026-05-12 19:34:27','2026.05.12, Сергій Слободян',23,47,1,'2026-05-09',1100.00,NULL,NULL,'','2026.05.12, Сергій Слободян','2026-05-12 18:57:13'),
(4,'UPDATE','2026-05-12 19:34:34','2026.05.12, Сергій Слободян',23,47,1,'2026-05-09',1100.00,NULL,NULL,'','2026.05.12, Сергій Слободян','2026-05-12 18:57:13'),
(5,'UPDATE','2026-05-12 19:35:01','2026.05.12, Сергій Слободян',23,47,1,'2026-05-09',1100.00,NULL,NULL,'','2026.05.12, Сергій Слободян','2026-05-12 18:57:13'),
(6,'UPDATE','2026-05-12 20:00:58','2026.05.12, Сергій Слободян',23,47,1,'2026-05-09',1200.00,NULL,NULL,'','2026.05.12, Сергій Слободян','2026-05-12 18:57:13'),
(7,'UPDATE','2026-05-12 20:01:08','2026.05.12, Сергій Слободян',23,47,1,'2026-05-09',1200.00,NULL,NULL,'','2026.05.12, Сергій Слободян','2026-05-12 18:57:13'),
(8,'UPDATE','2026-05-12 20:02:48','2026.05.12, Сергій Слободян',23,47,1,'2026-05-09',1200.00,NULL,NULL,'','2026.05.12, Сергій Слободян','2026-05-12 18:57:13'),
(9,'UPDATE','2026-05-12 20:03:23','2026.05.12, Сергій Слободян',23,47,1,'2026-05-09',1200.00,NULL,NULL,'','2026.05.12, Сергій Слободян','2026-05-12 18:57:13'),
(10,'UPDATE','2026-05-12 20:04:29','2026.05.12, Сергій Слободян',23,47,1,'2026-05-09',1200.00,NULL,NULL,'','2026.05.12, Сергій Слободян','2026-05-12 18:57:13');
/*!40000 ALTER TABLE `resource_logs_history` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `resource_rates`
--

DROP TABLE IF EXISTS `resource_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `resource_rates`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `resource_rates` WRITE;
/*!40000 ALTER TABLE `resource_rates` DISABLE KEYS */;
INSERT INTO `resource_rates` VALUES
(10,47,1,17,0.510000,NULL,0,'2026.05.11, Сергій Слободян'),
(11,47,2,14,1.000000,NULL,0,NULL);
/*!40000 ALTER TABLE `resource_rates` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_uca1400_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`sklad`@`localhost`*/ /*!50003 TRIGGER resource_rates_before_update
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
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_uca1400_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`sklad`@`localhost`*/ /*!50003 TRIGGER resource_rates_before_delete
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
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `resource_rates_history`
--

DROP TABLE IF EXISTS `resource_rates_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `resource_rates_history` (
  `history_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `action` enum('UPDATE','DELETE') NOT NULL,
  `changed_at` timestamp NULL DEFAULT current_timestamp(),
  `changed_by` varchar(255) DEFAULT NULL,
  `id` int(10) unsigned NOT NULL,
  `warehouse_id` int(10) unsigned NOT NULL,
  `resource_type_id` int(10) unsigned NOT NULL,
  `material_id` int(10) unsigned NOT NULL,
  `rate` decimal(15,6) NOT NULL,
  `source_warehouse_id` int(10) unsigned DEFAULT NULL,
  `spread_by_day` tinyint(1) NOT NULL DEFAULT 0,
  `author` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`history_id`),
  KEY `idx_resource_rates_history_id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `resource_rates_history`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `resource_rates_history` WRITE;
/*!40000 ALTER TABLE `resource_rates_history` DISABLE KEYS */;
INSERT INTO `resource_rates_history` VALUES
(1,'UPDATE','2026-05-11 17:16:54','2026.05.11, Сергій Слободян',10,47,1,17,0.510000,NULL,0,NULL);
/*!40000 ALTER TABLE `resource_rates_history` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `resource_types`
--

DROP TABLE IF EXISTS `resource_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `resource_types` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `unit` varchar(30) NOT NULL,
  `format` varchar(10) NOT NULL DEFAULT 'int',
  `author` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `resource_types`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `resource_types` WRITE;
/*!40000 ALTER TABLE `resource_types` DISABLE KEYS */;
INSERT INTO `resource_types` VALUES
(1,'Пробіг','км','int',NULL,'2026-05-09 14:38:37'),
(2,'Мотогодини','год','hm',NULL,'2026-05-09 14:38:37');
/*!40000 ALTER TABLE `resource_types` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_uca1400_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`sklad`@`localhost`*/ /*!50003 TRIGGER resource_types_before_update
BEFORE UPDATE ON resource_types
FOR EACH ROW
BEGIN
    INSERT INTO resource_types_history (action, changed_by, id, name, unit, format, author, created_at)
    VALUES ('UPDATE', @current_user, OLD.id, OLD.name, OLD.unit, OLD.format, OLD.author, OLD.created_at);
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_uca1400_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`sklad`@`localhost`*/ /*!50003 TRIGGER resource_types_before_delete
BEFORE DELETE ON resource_types
FOR EACH ROW
BEGIN
    INSERT INTO resource_types_history (action, changed_by, id, name, unit, format, author, created_at)
    VALUES ('DELETE', @current_user, OLD.id, OLD.name, OLD.unit, OLD.format, OLD.author, OLD.created_at);
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `resource_types_history`
--

DROP TABLE IF EXISTS `resource_types_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `resource_types_history` (
  `history_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `action` enum('UPDATE','DELETE') NOT NULL,
  `changed_at` timestamp NULL DEFAULT current_timestamp(),
  `changed_by` varchar(255) DEFAULT NULL,
  `id` int(10) unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  `unit` varchar(30) NOT NULL,
  `format` varchar(10) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`history_id`),
  KEY `idx_resource_types_history_id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `resource_types_history`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `resource_types_history` WRITE;
/*!40000 ALTER TABLE `resource_types_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `resource_types_history` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Temporary table structure for view `v_movements_history`
--

DROP TABLE IF EXISTS `v_movements_history`;
/*!50001 DROP VIEW IF EXISTS `v_movements_history`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `v_movements_history` AS SELECT
 1 AS `history_id`,
  1 AS `action`,
  1 AS `changed_at`,
  1 AS `changed_by`,
  1 AS `movement_id`,
  1 AS `movement_date`,
  1 AS `warehouse_from`,
  1 AS `warehouse_to`,
  1 AS `material`,
  1 AS `quantity`,
  1 AS `note`,
  1 AS `author`,
  1 AS `created_at`,
  1 AS `resource_value`,
  1 AS `resource_delta`,
  1 AS `resource_rate` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `warehouse_resources`
--

DROP TABLE IF EXISTS `warehouse_resources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `warehouse_resources`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `warehouse_resources` WRITE;
/*!40000 ALTER TABLE `warehouse_resources` DISABLE KEYS */;
INSERT INTO `warehouse_resources` VALUES
(4,47,1,NULL);
/*!40000 ALTER TABLE `warehouse_resources` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_uca1400_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`sklad`@`localhost`*/ /*!50003 TRIGGER warehouse_resources_before_update
BEFORE UPDATE ON warehouse_resources
FOR EACH ROW
BEGIN
    INSERT INTO warehouse_resources_history (action, changed_by, id, warehouse_id, resource_type_id, author)
    VALUES ('UPDATE', @current_user, OLD.id, OLD.warehouse_id, OLD.resource_type_id, OLD.author);
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_uca1400_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`sklad`@`localhost`*/ /*!50003 TRIGGER warehouse_resources_before_delete
BEFORE DELETE ON warehouse_resources
FOR EACH ROW
BEGIN
    INSERT INTO warehouse_resources_history (action, changed_by, id, warehouse_id, resource_type_id, author)
    VALUES ('DELETE', @current_user, OLD.id, OLD.warehouse_id, OLD.resource_type_id, OLD.author);
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `warehouse_resources_history`
--

DROP TABLE IF EXISTS `warehouse_resources_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `warehouse_resources_history` (
  `history_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `action` enum('UPDATE','DELETE') NOT NULL,
  `changed_at` timestamp NULL DEFAULT current_timestamp(),
  `changed_by` varchar(255) DEFAULT NULL,
  `id` int(10) unsigned NOT NULL,
  `warehouse_id` int(10) unsigned NOT NULL,
  `resource_type_id` int(10) unsigned NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`history_id`),
  KEY `idx_warehouse_resources_history_id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `warehouse_resources_history`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `warehouse_resources_history` WRITE;
/*!40000 ALTER TABLE `warehouse_resources_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `warehouse_resources_history` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `warehouses`
--

DROP TABLE IF EXISTS `warehouses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `warehouses` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `warehouses`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `warehouses` WRITE;
/*!40000 ALTER TABLE `warehouses` DISABLE KEYS */;
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
/*!40000 ALTER TABLE `warehouses` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_uca1400_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`sklad`@`localhost`*/ /*!50003 TRIGGER warehouses_before_update
BEFORE UPDATE ON warehouses
FOR EACH ROW
BEGIN
    INSERT INTO warehouses_history (action, changed_by, id, name, author, created_at)
    VALUES ('UPDATE', @current_user, OLD.id, OLD.name, OLD.author, OLD.created_at);
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_uca1400_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`sklad`@`localhost`*/ /*!50003 TRIGGER warehouses_before_delete
BEFORE DELETE ON warehouses
FOR EACH ROW
BEGIN
    INSERT INTO warehouses_history (action, changed_by, id, name, author, created_at)
    VALUES ('DELETE', @current_user, OLD.id, OLD.name, OLD.author, OLD.created_at);
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `warehouses_history`
--

DROP TABLE IF EXISTS `warehouses_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `warehouses_history` (
  `history_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `action` enum('UPDATE','DELETE') NOT NULL,
  `changed_at` timestamp NULL DEFAULT current_timestamp(),
  `changed_by` varchar(255) DEFAULT NULL,
  `id` int(10) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`history_id`),
  KEY `idx_warehouses_history_id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `warehouses_history`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `warehouses_history` WRITE;
/*!40000 ALTER TABLE `warehouses_history` DISABLE KEYS */;
INSERT INTO `warehouses_history` VALUES
(1,'UPDATE','2026-05-11 17:16:18','2026.05.11, Сергій Слободян',5,'Cклад ПММ','2026.05.11, Сергій Слободян','2026-05-08 06:51:05',NULL),
(2,'UPDATE','2026-05-11 17:16:21','2026.05.11, Сергій Слободян',47,'DAEWOO 72-45','2026.05.11, Сергій Слободян','2026-05-08 06:51:05',NULL),
(3,'UPDATE','2026-05-11 17:16:24','2026.05.11, Сергій Слободян',48,'DAEWOO 72-47','2026.05.11, ip 100.100.123.77','2026-05-08 06:51:05',NULL),
(4,'UPDATE','2026-05-11 17:16:27','2026.05.11, Сергій Слободян',49,'DAEWOO 72-50',NULL,'2026-05-08 06:51:05',NULL),
(5,'UPDATE','2026-05-11 17:16:30','2026.05.11, Сергій Слободян',16,'DLN №275002',NULL,'2026-05-08 06:51:05',NULL),
(6,'UPDATE','2026-05-11 17:16:35','2026.05.11, Сергій Слободян',5,'Cклад ПММ','2026.05.11, Сергій Слободян','2026-05-08 06:51:05',NULL);
/*!40000 ALTER TABLE `warehouses_history` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Final view structure for view `v_movements_history`
--

/*!50001 DROP VIEW IF EXISTS `v_movements_history`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_uca1400_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`sklad`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_movements_history` AS select `h`.`history_id` AS `history_id`,`h`.`action` AS `action`,`h`.`changed_at` AS `changed_at`,`h`.`changed_by` AS `changed_by`,`h`.`id` AS `movement_id`,`h`.`movement_date` AS `movement_date`,`wf`.`name` AS `warehouse_from`,`wt`.`name` AS `warehouse_to`,`m`.`name` AS `material`,`h`.`quantity` AS `quantity`,`h`.`note` AS `note`,`h`.`author` AS `author`,`h`.`created_at` AS `created_at`,`h`.`resource_value` AS `resource_value`,`h`.`resource_delta` AS `resource_delta`,`h`.`resource_rate` AS `resource_rate` from (((`movements_history` `h` left join `warehouses` `wf` on(`wf`.`id` = `h`.`warehouse_from_id`)) left join `warehouses` `wt` on(`wt`.`id` = `h`.`warehouse_to_id`)) left join `materials` `m` on(`m`.`id` = `h`.`material_id`)) order by `h`.`changed_at` desc */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-05-12 23:07:55
