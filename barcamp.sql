-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               5.6.26-log - MySQL Community Server (GPL)
-- Server OS:                    Win64
-- HeidiSQL Version:             9.2.0.4947
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

-- Dumping database structure for barcamp
CREATE DATABASE IF NOT EXISTS `barcamp` /*!40100 DEFAULT CHARACTER SET utf8 */;
USE `barcamp`;


-- Dumping structure for table barcamp.channel_messages
CREATE TABLE IF NOT EXISTS `channel_messages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user` varchar(50) DEFAULT NULL,
  `text` text,
  `ts` double DEFAULT NULL,
  `command_done` enum('0','1') NOT NULL DEFAULT '0',
  `hash` varchar(100) DEFAULT NULL,
  `date_insert` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ts` (`ts`,`command_done`),
  KEY `hash` (`hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Dumping data for table barcamp.channel_messages: ~0 rows (approximately)
DELETE FROM `channel_messages`;
/*!40000 ALTER TABLE `channel_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `channel_messages` ENABLE KEYS */;


-- Dumping structure for table barcamp.message
CREATE TABLE IF NOT EXISTS `message` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `text` text,
  `date_insert` datetime DEFAULT NULL,
  `date_sended` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `date_sended` (`date_sended`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Dumping data for table barcamp.message: ~0 rows (approximately)
DELETE FROM `message`;
/*!40000 ALTER TABLE `message` DISABLE KEYS */;
/*!40000 ALTER TABLE `message` ENABLE KEYS */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
