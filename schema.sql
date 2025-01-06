# Copyright 2022 Google LLC
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.

/*M!999999\- enable the sandbox mode */
-- MariaDB dump 10.19-11.4.3-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: 127.0.0.1    Database: fmnas_test
-- ------------------------------------------------------
-- Server version	11.4.3-MariaDB-1

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
-- Table structure for table `assets`
--

DROP TABLE IF EXISTS `assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `path` varchar(2048) DEFAULT NULL,
  `data` text DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `width` mediumint(9) DEFAULT NULL,
  `height` mediumint(9) DEFAULT NULL,
  `gcs` boolean NOT NULL DEFAULT FALSE,
  PRIMARY KEY (`id`),
  UNIQUE KEY `path` (`path`(768))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `config`
--

DROP TABLE IF EXISTS `config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `config` (
  `config_key` varchar(20) NOT NULL,
  `config_value` text DEFAULT NULL,
  PRIMARY KEY (`config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Global configuration values; these are cached by the backend';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `forms`
--

DROP TABLE IF EXISTS `forms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `forms` (
  `id` varchar(127) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) NOT NULL,
  `fillout_id` char(12) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `title` (`title`),
  UNIQUE KEY `fillout_id` (`fillout_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary table structure for view `listings`
--

DROP TABLE IF EXISTS `listings`;
/*!50001 DROP VIEW IF EXISTS `listings`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `listings` AS SELECT
 1 AS `id`,
  1 AS `name`,
  1 AS `species`,
  1 AS `breed`,
  1 AS `dob`,
  1 AS `sex`,
  1 AS `fee`,
  1 AS `photo`,
  1 AS `description`,
  1 AS `status`,
  1 AS `bonded`,
  1 AS `friend`,
  1 AS `adoption_date`,
  1 AS `order`,
  1 AS `legacy_path`,
  1 AS `path`,
  1 AS `modified`,
  1 AS `friend_name`,
  1 AS `friend_sex`,
  1 AS `friend_breed`,
  1 AS `friend_dob`,
  1 AS `pic_id`,
  1 AS `pic_data`,
  1 AS `pic_path`,
  1 AS `pic_type`,
  1 AS `pic_width`,
  1 AS `pic_height`,
  1 AS `pic_gcs`,
  1 AS `friend_pic_id`,
  1 AS `friend_pic_data`,
  1 AS `friend_pic_path`,
  1 AS `friend_pic_type`,
  1 AS `friend_pic_width`,
  1 AS `friend_pic_height`,
  1 AS `friend_pic_gcs`,
  1 AS `dsc_id`,
  1 AS `dsc_data`,
  1 AS `dsc_path`,
  1 AS `dsc_type`,
  1 AS `listing_path` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `pets`
--

DROP TABLE IF EXISTS `pets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pets` (
  `id` varchar(15) NOT NULL,
  `name` varchar(255) NOT NULL,
  `species` tinyint(4) DEFAULT NULL,
  `breed` varchar(1023) DEFAULT NULL COMMENT 'or other description',
  `dob` date DEFAULT NULL,
  `sex` tinyint(4) DEFAULT NULL,
  `fee` varchar(255) DEFAULT NULL,
  `photo` int(11) DEFAULT NULL,
  `description` int(11) DEFAULT NULL,
  `status` smallint(6) NOT NULL DEFAULT 1,
  `bonded` tinyint(2) NOT NULL DEFAULT 0,
  `friend` varchar(15) DEFAULT NULL,
  `adoption_date` date DEFAULT NULL,
  `order` int(11) DEFAULT NULL,
  `legacy_path` varchar(255) DEFAULT NULL,
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
  `pet` varchar(255) NOT NULL,
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
  `name` varchar(127) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `species`
--

DROP TABLE IF EXISTS `species`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `species` (
  `id` tinyint(4) NOT NULL AUTO_INCREMENT,
  `name` varchar(127) NOT NULL,
  `plural` varchar(127) DEFAULT NULL,
  `young` varchar(127) DEFAULT NULL,
  `young_plural` varchar(127) DEFAULT NULL,
  `old` varchar(127) DEFAULT NULL,
  `old_plural` varchar(127) DEFAULT NULL,
  `age_unit_cutoff` smallint(6) DEFAULT NULL COMMENT 'in months',
  `young_cutoff` smallint(6) DEFAULT NULL COMMENT 'in months',
  `old_cutoff` smallint(6) DEFAULT NULL COMMENT 'in months',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `statuses`
--

DROP TABLE IF EXISTS `statuses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `statuses` (
  `id` smallint(6) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `display` tinyint(1) DEFAULT NULL,
  `listed` tinyint(1) NOT NULL DEFAULT 1,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Final view structure for view `listings`
--

/*!50001 DROP VIEW IF EXISTS `listings`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `listings` AS select `lpet`.`id` AS `id`,`lpet`.`name` AS `name`,`lpet`.`species` AS `species`,`lpet`.`breed` AS `breed`,`lpet`.`dob` AS `dob`,`lpet`.`sex` AS `sex`,`lpet`.`fee` AS `fee`,`lpet`.`photo` AS `photo`,`lpet`.`description` AS `description`,`lpet`.`status` AS `status`,`lpet`.`bonded` AS `bonded`,`lpet`.`friend` AS `friend`,`lpet`.`adoption_date` AS `adoption_date`,`lpet`.`order` AS `order`,`lpet`.`legacy_path` AS `legacy_path`,`lpet`.`path` AS `path`,`lpet`.`modified` AS `modified`,`rpet`.`name` AS `friend_name`,`rpet`.`sex` AS `friend_sex`,`rpet`.`breed` AS `friend_breed`,`rpet`.`dob` AS `friend_dob`,`lpic`.`id` AS `pic_id`,`lpic`.`data` AS `pic_data`,`lpic`.`path` AS `pic_path`,`lpic`.`type` AS `pic_type`,`lpic`.`width` AS `pic_width`,`lpic`.`height` AS `pic_height`,`lpic`.`gcs` AS `pic_gcs`,`rpic`.`id` AS `friend_pic_id`,`rpic`.`data` AS `friend_pic_data`,`rpic`.`path` AS `friend_pic_path`,`rpic`.`type` AS `friend_pic_type`,`rpic`.`width` AS `friend_pic_width`,`rpic`.`height` AS `friend_pic_height`,`rpic`.`gcs` AS `friend_pic_gcs`,`dsc`.`id` AS `dsc_id`,`dsc`.`data` AS `dsc_data`,`dsc`.`path` AS `dsc_path`,`dsc`.`type` AS `dsc_type`,if(`lpet`.`bonded` = 1,concat(`lpet`.`id`,replace(`lpet`.`name`,' ',''),`rpet`.`id`,replace(`rpet`.`name`,' ','')),concat(`lpet`.`id`,replace(`lpet`.`name`,' ',''))) AS `listing_path` from ((((`pets` `lpet` left join `pets` `rpet` on(`lpet`.`friend` = `rpet`.`id` and `lpet`.`bonded` = 1)) left join `assets` `dsc` on(`lpet`.`description` = `dsc`.`id`)) left join `assets` `lpic` on(`lpet`.`photo` = `lpic`.`id`)) left join `assets` `rpic` on(`rpet`.`photo` = `rpic`.`id`)) where `lpet`.`bonded` < 2 */;
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

-- Dump completed on 2025-01-05 12:17:43
