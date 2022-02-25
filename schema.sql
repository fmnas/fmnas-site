-- MariaDB dump 10.19  Distrib 10.6.5-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: fmnas_dev
-- ------------------------------------------------------
-- Server version	10.6.5-MariaDB-2

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `assets`
--

DROP TABLE IF EXISTS `assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `path` varchar(2048) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `width` mediumint(9) DEFAULT NULL,
  `height` mediumint(9) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `path` (`path`(768))
) ENGINE=InnoDB AUTO_INCREMENT=4186 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `config`
--

DROP TABLE IF EXISTS `config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `config` (
  `config_key` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `config_value` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Global configuration values; these are cached by the backend';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary table structure for view `listings`
--

DROP TABLE IF EXISTS `listings`;
/*!50001 DROP VIEW IF EXISTS `listings`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `listings` (
  `id` tinyint NOT NULL,
  `name` tinyint NOT NULL,
  `species` tinyint NOT NULL,
  `breed` tinyint NOT NULL,
  `dob` tinyint NOT NULL,
  `sex` tinyint NOT NULL,
  `fee` tinyint NOT NULL,
  `photo` tinyint NOT NULL,
  `description` tinyint NOT NULL,
  `status` tinyint NOT NULL,
  `bonded` tinyint NOT NULL,
  `friend` tinyint NOT NULL,
  `adoption_date` tinyint NOT NULL,
  `order` tinyint NOT NULL,
  `legacy_path` tinyint NOT NULL,
  `path` tinyint NOT NULL,
  `modified` tinyint NOT NULL,
  `friend_name` tinyint NOT NULL,
  `friend_sex` tinyint NOT NULL,
  `friend_breed` tinyint NOT NULL,
  `friend_dob` tinyint NOT NULL,
  `pic_id` tinyint NOT NULL,
  `pic_data` tinyint NOT NULL,
  `pic_path` tinyint NOT NULL,
  `pic_type` tinyint NOT NULL,
  `pic_width` tinyint NOT NULL,
  `pic_height` tinyint NOT NULL,
  `friend_pic_id` tinyint NOT NULL,
  `friend_pic_data` tinyint NOT NULL,
  `friend_pic_path` tinyint NOT NULL,
  `friend_pic_type` tinyint NOT NULL,
  `friend_pic_width` tinyint NOT NULL,
  `friend_pic_height` tinyint NOT NULL,
  `dsc_id` tinyint NOT NULL,
  `dsc_data` tinyint NOT NULL,
  `dsc_path` tinyint NOT NULL,
  `dsc_type` tinyint NOT NULL,
  `listing_path` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `pets`
--

DROP TABLE IF EXISTS `pets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pets` (
  `id` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `species` tinyint(4) DEFAULT NULL,
  `breed` varchar(1023) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'or other description',
  `dob` date DEFAULT NULL,
  `sex` tinyint(4) DEFAULT NULL,
  `fee` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `photo` int(11) DEFAULT NULL,
  `description` int(11) DEFAULT NULL,
  `status` smallint(6) NOT NULL DEFAULT 1,
  `bonded` tinyint(2) NOT NULL DEFAULT 0,
  `friend` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `adoption_date` date DEFAULT NULL,
  `order` int(11) DEFAULT NULL,
  `legacy_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `path` varchar(270) GENERATED ALWAYS AS (concat(`id`,replace(`name`,' ',''))) VIRTUAL,
  `modified` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `path` (`path`),
  KEY `name` (`name`),
  KEY `description` (`description`),
  KEY `photo` (`photo`),
  KEY `sex` (`sex`),
  KEY `species` (`species`),
  KEY `status` (`status`),
  KEY `pets_pets_id_fk` (`friend`),
  CONSTRAINT `pets_ibfk_1` FOREIGN KEY (`description`) REFERENCES `assets` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `pets_ibfk_2` FOREIGN KEY (`photo`) REFERENCES `assets` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `pets_ibfk_3` FOREIGN KEY (`sex`) REFERENCES `sexes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `pets_ibfk_4` FOREIGN KEY (`species`) REFERENCES `species` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `pets_ibfk_5` FOREIGN KEY (`status`) REFERENCES `statuses` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `pets_pets_id_fk` FOREIGN KEY (`friend`) REFERENCES `pets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `photos`
--

DROP TABLE IF EXISTS `photos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `photos` (
  `pet` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `photo` int(11) NOT NULL,
  `order` int(11) DEFAULT NULL,
  PRIMARY KEY (`pet`,`photo`),
  KEY `pet` (`pet`),
  KEY `photo` (`photo`),
  CONSTRAINT `photos_ibfk_1` FOREIGN KEY (`photo`) REFERENCES `assets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `photos_ibfk_2` FOREIGN KEY (`pet`) REFERENCES `pets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sexes`
--

DROP TABLE IF EXISTS `sexes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sexes` (
  `id` tinyint(4) NOT NULL AUTO_INCREMENT,
  `name` varchar(127) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `species`
--

DROP TABLE IF EXISTS `species`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `species` (
  `id` tinyint(4) NOT NULL AUTO_INCREMENT,
  `name` varchar(127) COLLATE utf8mb4_unicode_ci NOT NULL,
  `plural` varchar(127) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `young` varchar(127) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `young_plural` varchar(127) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `old` varchar(127) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `old_plural` varchar(127) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `age_unit_cutoff` smallint(6) DEFAULT NULL COMMENT 'in months',
  `young_cutoff` smallint(6) DEFAULT NULL COMMENT 'in months',
  `old_cutoff` smallint(6) DEFAULT NULL COMMENT 'in months',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `statuses`
--

DROP TABLE IF EXISTS `statuses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `statuses` (
  `id` smallint(6) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `display` tinyint(1) DEFAULT NULL,
  `listed` tinyint(1) NOT NULL DEFAULT 1,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Final view structure for view `listings`
--

/*!50001 DROP TABLE IF EXISTS `listings`*/;
/*!50001 DROP VIEW IF EXISTS `listings`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50001 VIEW `listings` AS select `lpet`.`id` AS `id`,`lpet`.`name` AS `name`,`lpet`.`species` AS `species`,`lpet`.`breed` AS `breed`,`lpet`.`dob` AS `dob`,`lpet`.`sex` AS `sex`,`lpet`.`fee` AS `fee`,`lpet`.`photo` AS `photo`,`lpet`.`description` AS `description`,`lpet`.`status` AS `status`,`lpet`.`bonded` AS `bonded`,`lpet`.`friend` AS `friend`,`lpet`.`adoption_date` AS `adoption_date`,`lpet`.`order` AS `order`,`lpet`.`legacy_path` AS `legacy_path`,`lpet`.`path` AS `path`,`lpet`.`modified` AS `modified`,`rpet`.`name` AS `friend_name`,`rpet`.`sex` AS `friend_sex`,`rpet`.`breed` AS `friend_breed`,`rpet`.`dob` AS `friend_dob`,`lpic`.`id` AS `pic_id`,`lpic`.`data` AS `pic_data`,`lpic`.`path` AS `pic_path`,`lpic`.`type` AS `pic_type`,`lpic`.`width` AS `pic_width`,`lpic`.`height` AS `pic_height`,`rpic`.`id` AS `friend_pic_id`,`rpic`.`data` AS `friend_pic_data`,`rpic`.`path` AS `friend_pic_path`,`rpic`.`type` AS `friend_pic_type`,`rpic`.`width` AS `friend_pic_width`,`rpic`.`height` AS `friend_pic_height`,`dsc`.`id` AS `dsc_id`,`dsc`.`data` AS `dsc_data`,`dsc`.`path` AS `dsc_path`,`dsc`.`type` AS `dsc_type`,if(`lpet`.`bonded` = 1,concat(`lpet`.`id`,replace(`lpet`.`name`,' ',''),`rpet`.`id`,replace(`rpet`.`name`,' ','')),concat(`lpet`.`id`,replace(`lpet`.`name`,' ',''))) AS `listing_path` from ((((`pets` `lpet` left join `pets` `rpet` on(`lpet`.`friend` = `rpet`.`id` and `lpet`.`bonded` = 1)) left join `assets` `dsc` on(`lpet`.`description` = `dsc`.`id`)) left join `assets` `lpic` on(`lpet`.`photo` = `lpic`.`id`)) left join `assets` `rpic` on(`rpet`.`photo` = `rpic`.`id`)) where `lpet`.`bonded` < 2 */;
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
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2022-02-24 22:25:34
