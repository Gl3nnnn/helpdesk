-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: helpdesk
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

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
-- Table structure for table `attachments`
--

DROP TABLE IF EXISTS `attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attachments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `filepath` varchar(255) NOT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`),
  KEY `uploaded_by` (`uploaded_by`),
  CONSTRAINT `attachments_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attachments_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attachments`
--

LOCK TABLES `attachments` WRITE;
/*!40000 ALTER TABLE `attachments` DISABLE KEYS */;
INSERT INTO `attachments` VALUES (1,9,'c08ab717-57f1-4b57-8961-8a5c36f58b56.jpg','uploads/c08ab717-57f1-4b57-8961-8a5c36f58b56.jpg',2,'2025-10-09 02:27:36'),(2,10,'c08ab717-57f1-4b57-8961-8a5c36f58b56.jpg','uploads/c08ab717-57f1-4b57-8961-8a5c36f58b56.jpg',2,'2025-10-09 02:27:43'),(3,11,'c08ab717-57f1-4b57-8961-8a5c36f58b56 (1).jpg','uploads/c08ab717-57f1-4b57-8961-8a5c36f58b56 (1).jpg',2,'2025-10-09 02:29:01'),(4,12,'c08ab717-57f1-4b57-8961-8a5c36f58b56 (1).jpg','uploads/c08ab717-57f1-4b57-8961-8a5c36f58b56 (1).jpg',2,'2025-10-09 02:29:44'),(5,13,'c08ab717-57f1-4b57-8961-8a5c36f58b56 (1).jpg','uploads/c08ab717-57f1-4b57-8961-8a5c36f58b56 (1).jpg',2,'2025-10-09 02:29:54'),(6,14,'c08ab717-57f1-4b57-8961-8a5c36f58b56 (1).jpg','uploads/c08ab717-57f1-4b57-8961-8a5c36f58b56 (1).jpg',2,'2025-10-09 02:30:46'),(7,15,'c08ab717-57f1-4b57-8961-8a5c36f58b56 (1).jpg','uploads/c08ab717-57f1-4b57-8961-8a5c36f58b56 (1).jpg',2,'2025-10-09 02:30:47'),(8,16,'c08ab717-57f1-4b57-8961-8a5c36f58b56 (1).jpg','uploads/c08ab717-57f1-4b57-8961-8a5c36f58b56 (1).jpg',2,'2025-10-09 02:30:48'),(9,17,'c08ab717-57f1-4b57-8961-8a5c36f58b56 (1).jpg','uploads/c08ab717-57f1-4b57-8961-8a5c36f58b56 (1).jpg',2,'2025-10-09 02:31:27'),(10,18,'c08ab717-57f1-4b57-8961-8a5c36f58b56 (1).jpg','uploads/c08ab717-57f1-4b57-8961-8a5c36f58b56 (1).jpg',2,'2025-10-09 02:33:11'),(11,19,'CABANSAG-OT-September-8-24-2025.pdf','uploads/CABANSAG-OT-September-8-24-2025.pdf',2,'2025-10-09 02:43:54'),(12,20,'CABAR-OT-September-25-October-7-2025.pdf','uploads/CABAR-OT-September-25-October-7-2025.pdf',2,'2025-10-09 02:44:50'),(13,21,'muichiro-tokito-3840x2160-16957.jpg','uploads/muichiro-tokito-3840x2160-16957.jpg',2,'2025-10-09 02:50:41'),(14,22,'form cctv.pdf','uploads/form cctv.pdf',2,'2025-10-09 05:08:51'),(15,23,'c08ab717-57f1-4b57-8961-8a5c36f58b56 (1) (3).jpg','uploads/c08ab717-57f1-4b57-8961-8a5c36f58b56 (1) (3).jpg',2,'2025-10-09 05:42:47'),(16,24,'Screenshot 2025-10-09 143252.png','uploads/Screenshot 2025-10-09 143252.png',2,'2025-10-09 06:33:01');
/*!40000 ALTER TABLE `attachments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`),
  CONSTRAINT `audit_logs_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (1,1,1,'Assignment Change','Unassigned','admin','2025-10-05 09:21:33'),(2,2,1,'Assignment Change','Unassigned','admin','2025-10-05 10:12:28'),(3,3,1,'Assignment Change','Unassigned','admin','2025-10-05 10:52:45'),(4,3,1,'Status Change','open','closed','2025-10-05 10:52:52'),(5,3,1,'Status Change','closed','assigned','2025-10-05 10:57:07'),(6,3,1,'Status Change','assigned','closed','2025-10-05 10:57:12'),(7,2,1,'Status Change','open','assigned','2025-10-06 04:25:05'),(8,5,1,'Assignment Change','Unassigned','admin','2025-10-06 05:05:48'),(9,5,1,'Department Change','IT','HR','2025-10-06 05:07:20'),(10,5,1,'Assignment Change','admin','Unassigned','2025-10-06 05:07:33'),(11,5,1,'Assignment Change','Unassigned','admin','2025-10-06 05:08:40'),(12,4,1,'Assignment Change','Unassigned','admin','2025-10-06 05:40:09'),(13,6,1,'Status Change','open','assigned','2025-10-06 06:13:38'),(14,6,1,'Assignment Change','Unassigned','admin','2025-10-06 06:13:38'),(15,6,1,'Status Change','assigned','closed','2025-10-06 06:15:08'),(16,5,1,'Status Change','open','closed','2025-10-09 01:03:26'),(17,4,1,'Status Change','open','assigned','2025-10-09 01:55:53'),(18,1,1,'Status Change','open','closed','2025-10-09 02:15:43'),(19,1,1,'Status Change','open','closed','2025-10-09 02:15:53'),(20,7,1,'Status Change','open','closed','2025-10-09 02:15:59'),(21,1,1,'Status Change','open','assigned','2025-10-09 02:16:11'),(22,4,1,'Status Change','assigned','closed','2025-10-09 02:16:20'),(23,2,1,'Status Change','assigned','closed','2025-10-09 02:16:34'),(24,1,1,'Status Change','open','closed','2025-10-09 02:16:40'),(25,1,1,'Status Change','open','closed','2025-10-09 02:16:41'),(26,1,1,'Status Change','open','closed','2025-10-09 02:16:41'),(27,1,1,'Status Change','open','closed','2025-10-09 02:16:42'),(28,1,1,'Status Change','open','closed','2025-10-09 02:16:42'),(29,1,1,'Status Change','open','closed','2025-10-09 02:16:53'),(30,14,1,'Status Change','open','assigned','2025-10-09 03:10:23'),(31,24,1,'Status Change','open','closed','2025-10-09 06:41:53'),(32,24,1,'Assignment Change','Unassigned','admin','2025-10-09 06:41:53'),(33,23,1,'Department Change','IT','HR','2025-10-09 06:42:22');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (2,'Billing'),(3,'General Inquiry'),(1,'Technical Support'),(4,'teemo');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `departments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
INSERT INTO `departments` VALUES (1,'IT','Information Technology Support'),(2,'HR','Human Resources'),(3,'Facilities','Facilities and Maintenance');
/*!40000 ALTER TABLE `departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `faq`
--

DROP TABLE IF EXISTS `faq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `faq` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question` varchar(255) NOT NULL,
  `answer` text NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `author_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `author_id` (`author_id`),
  CONSTRAINT `faq_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `faq`
--

LOCK TABLES `faq` WRITE;
/*!40000 ALTER TABLE `faq` DISABLE KEYS */;
/*!40000 ALTER TABLE `faq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`),
  CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=81 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `messages`
--

LOCK TABLES `messages` WRITE;
/*!40000 ALTER TABLE `messages` DISABLE KEYS */;
INSERT INTO `messages` VALUES (1,1,2,'try tesssssssssadcxascx','2025-10-05 09:21:06'),(2,1,1,'asdsadasda','2025-10-05 09:21:22'),(3,1,2,'dasfdasfasdf','2025-10-05 09:21:49'),(4,1,1,'asdasdasd','2025-10-05 09:22:03'),(5,1,1,'asdasdasdasdasdasd','2025-10-05 09:22:53'),(6,1,2,'asdasdasdas','2025-10-05 09:23:11'),(7,1,1,'asdasdasd','2025-10-05 09:23:32'),(8,1,2,'asdasdasdasd','2025-10-05 09:24:56'),(9,1,2,'asdasdasd','2025-10-05 09:24:58'),(10,1,1,'hello','2025-10-05 09:26:30'),(11,1,1,'asdasdasdxascxvfbnbnm,,','2025-10-05 09:28:21'),(12,1,2,'kaching','2025-10-05 09:28:57'),(13,1,1,'sadasdas','2025-10-05 09:30:33'),(14,1,1,'asdasdwqw','2025-10-05 09:31:01'),(15,1,1,'21213','2025-10-05 09:32:02'),(16,1,1,'asdasdasd','2025-10-05 09:32:49'),(17,1,2,'adsad','2025-10-05 09:33:35'),(18,1,1,'sdfsdfsdf','2025-10-05 09:33:42'),(19,1,2,'asdas','2025-10-05 09:34:38'),(20,1,1,'asdasd','2025-10-05 09:34:42'),(21,1,1,'asdasdasdasd222','2025-10-05 09:36:32'),(22,2,1,'asdsadasdasdasd','2025-10-05 10:12:21'),(23,2,2,'afsddfasdsad','2025-10-05 10:12:48'),(24,2,2,'asdasd','2025-10-05 10:13:33'),(25,2,2,'asdasdas','2025-10-05 10:14:43'),(26,2,2,'asdasdas','2025-10-05 10:15:59'),(27,3,2,'asdsadasdasd','2025-10-05 10:26:28'),(28,3,1,'sadsad','2025-10-05 10:26:39'),(29,5,1,'jksdfajksdfajkjklasd','2025-10-06 05:10:33'),(30,5,2,'asdas','2025-10-06 05:10:46'),(31,5,1,'sadasdas','2025-10-06 05:12:13'),(32,5,2,'asdasd','2025-10-06 05:12:25'),(33,5,1,'asdasd','2025-10-06 05:13:31'),(34,5,1,'asdasd','2025-10-06 05:13:46'),(35,5,1,'asdasdasd','2025-10-06 05:14:05'),(36,5,1,'sadsa','2025-10-06 05:15:10'),(37,5,2,'asdsada','2025-10-06 05:15:39'),(38,5,1,'asdasd','2025-10-06 05:17:53'),(39,5,2,'asdasdasd','2025-10-06 05:18:09'),(40,5,2,'l','2025-10-06 05:22:14'),(41,5,2,'zxczxc','2025-10-06 05:28:41'),(42,4,1,'asdasdasd','2025-10-06 05:40:16'),(43,4,2,'asdasd','2025-10-06 05:40:29'),(44,6,1,'123','2025-10-06 06:13:18'),(45,6,1,'123','2025-10-06 06:13:54'),(46,5,2,'fdsfsdfsd','2025-10-06 08:49:30'),(47,4,1,'DSAdsaD','2025-10-09 01:30:43'),(48,4,2,'asxXCAZDC','2025-10-09 01:34:25'),(49,4,1,'ASDSADAS','2025-10-09 01:36:04'),(50,4,2,'ASDASDASD','2025-10-09 01:42:51'),(51,4,2,'ASDASD','2025-10-09 01:47:58'),(52,4,2,'ASDASDASD','2025-10-09 01:50:08'),(53,4,1,'SASDAASD','2025-10-09 01:50:25'),(54,1,1,'zxczx','2025-10-09 02:16:45'),(55,18,1,'asdsa','2025-10-09 02:40:32'),(56,18,2,'dsadasd','2025-10-09 02:40:39'),(57,18,2,'sadsadas','2025-10-09 02:40:44'),(58,18,2,'sadsad','2025-10-09 02:40:57'),(59,18,1,'asdsadasd','2025-10-09 02:41:26'),(60,18,2,'asdsa','2025-10-09 02:41:33'),(61,18,2,'sdaas','2025-10-09 02:42:28'),(62,18,2,'asdsadasd','2025-10-09 02:43:32'),(63,21,2,'sadasd','2025-10-09 04:34:05'),(64,21,2,'sefd','2025-10-09 04:34:32'),(65,22,1,'fgsssfdds','2025-10-09 05:13:04'),(66,22,2,'sdfsdfdsf','2025-10-09 05:13:11'),(67,22,1,'sdadasdasd','2025-10-09 05:16:11'),(68,22,1,'asdasdasdas','2025-10-09 05:17:55'),(69,22,2,'asdsadasdasd','2025-10-09 05:18:09'),(70,22,2,'asdasdasasdasd','2025-10-09 05:19:45'),(71,22,1,'ASDSADSA','2025-10-09 05:20:06'),(72,22,2,'ASDDASDAS','2025-10-09 05:22:16'),(73,22,2,'ASASASD','2025-10-09 05:22:30'),(74,22,2,'sadasdasdasd','2025-10-09 05:28:55'),(75,22,2,'asdsadsadasdsa','2025-10-09 05:30:52'),(76,22,1,'asdasdasdasdsdsd','2025-10-09 05:38:20'),(77,22,1,'asdasdasdas','2025-10-09 05:38:28'),(78,22,1,'asdasdas','2025-10-09 05:38:29'),(79,23,1,'asasdasdasdasd','2025-10-09 05:55:18'),(80,24,1,'hellleo','2025-10-09 06:41:46');
/*!40000 ALTER TABLE `messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` enum('new_ticket','ticket_update','ticket_resolution','new_message') NOT NULL,
  `ticket_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `ticket_id` (`ticket_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=80 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (58,1,'new_message',21,'New message on ticket #21',1,'2025-10-09 04:34:05'),(59,1,'new_message',21,'New message on ticket #21',1,'2025-10-09 04:34:32'),(60,1,'new_ticket',22,'New ticket submitted: #22 - sdfsdg',1,'2025-10-09 05:08:51'),(62,1,'new_message',22,'New message on ticket #22',1,'2025-10-09 05:13:11'),(64,2,'new_message',22,'New message on your ticket #22',1,'2025-10-09 05:17:55'),(65,1,'new_message',22,'New message on ticket #22',1,'2025-10-09 05:18:09'),(66,1,'new_message',22,'New message on ticket #22',1,'2025-10-09 05:19:45'),(67,2,'new_message',22,'New message on your ticket #22',1,'2025-10-09 05:20:06'),(68,1,'new_message',22,'New message on ticket #22',1,'2025-10-09 05:22:16'),(69,1,'new_message',22,'New message on ticket #22',1,'2025-10-09 05:22:30'),(70,1,'new_message',22,'New message on ticket #22',1,'2025-10-09 05:28:55'),(71,1,'new_message',22,'New message on ticket #22',1,'2025-10-09 05:30:52'),(72,2,'new_message',22,'New message on your ticket #22',1,'2025-10-09 05:38:20'),(73,2,'new_message',22,'New message on your ticket #22',1,'2025-10-09 05:38:28'),(74,2,'new_message',22,'New message on your ticket #22',1,'2025-10-09 05:38:29'),(75,1,'new_ticket',23,'New ticket submitted: #23 - sdfsdf',1,'2025-10-09 05:42:47'),(76,2,'new_message',23,'New message on your ticket #23',1,'2025-10-09 05:55:18'),(77,1,'new_ticket',24,'New ticket submitted: #24 - printer',1,'2025-10-09 06:33:01'),(78,2,'new_message',24,'New message on your ticket #24',0,'2025-10-09 06:41:46'),(79,2,'ticket_resolution',24,'Your ticket #24 has been resolved',0,'2025-10-09 06:41:53');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `priorities`
--

DROP TABLE IF EXISTS `priorities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `priorities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `level` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `priorities`
--

LOCK TABLES `priorities` WRITE;
/*!40000 ALTER TABLE `priorities` DISABLE KEYS */;
INSERT INTO `priorities` VALUES (1,'Low',1),(2,'Medium',2),(3,'High',3);
/*!40000 ALTER TABLE `priorities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sla_rules`
--

DROP TABLE IF EXISTS `sla_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sla_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) DEFAULT NULL,
  `priority_id` int(11) DEFAULT NULL,
  `response_time_hours` int(11) DEFAULT NULL,
  `resolution_time_hours` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  KEY `priority_id` (`priority_id`),
  CONSTRAINT `sla_rules_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  CONSTRAINT `sla_rules_ibfk_2` FOREIGN KEY (`priority_id`) REFERENCES `priorities` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sla_rules`
--

LOCK TABLES `sla_rules` WRITE;
/*!40000 ALTER TABLE `sla_rules` DISABLE KEYS */;
INSERT INTO `sla_rules` VALUES (1,1,3,2,24),(2,1,2,4,48),(3,1,1,8,72),(4,2,3,1,12),(5,2,2,2,24),(6,2,1,4,48),(7,3,3,4,48),(8,3,2,8,72),(9,3,1,12,96);
/*!40000 ALTER TABLE `sla_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tickets`
--

DROP TABLE IF EXISTS `tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `status` enum('open','assigned','closed') DEFAULT 'open',
  `user_id` int(11) NOT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `closed_at` timestamp NULL DEFAULT NULL,
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `assigned_to` (`assigned_to`),
  KEY `fk_tickets_department_id` (`department_id`),
  CONSTRAINT `fk_tickets_department_id` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `tickets_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tickets`
--

LOCK TABLES `tickets` WRITE;
/*!40000 ALTER TABLE `tickets` DISABLE KEYS */;
INSERT INTO `tickets` VALUES (22,'sdfsdg','dfgdfgdfgdfg','Technical Support','high','open',2,NULL,1,'2025-10-09 05:08:51','2025-10-09 05:08:51',NULL,0),(23,'sdfsdf','sdfsdfsdf','Technical Support','medium','open',2,NULL,2,'2025-10-09 05:42:47','2025-10-09 06:42:22',NULL,0),(24,'printer','printer','teemo','high','closed',2,1,2,'2025-10-09 06:33:01','2025-10-09 06:41:53','2025-10-09 06:41:53',1);
/*!40000 ALTER TABLE `tickets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','inactive') DEFAULT 'active',
  `profile_picture` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','admin@helpdesk.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','admin','2025-10-05 09:03:39','active','profile_1_1759985968.jpg'),(2,'Nani','admin@admin.com','$2y$10$Uzgng0hIYSs6tORVLaCZxefzTOHqsJ2M7qh0enEKWWEwaeumy7z2W','user','2025-10-05 09:04:06','active','profile_2_1759660220.jpg'),(4,'qweqwe','gcabansag@usa.edu.ph','$2y$10$meBrxyc5KamwBbZeH8R3aerL5IvWYrSWe9M5sbT1WKnVm0B6Wo8z6','user','2025-10-09 00:35:22','inactive',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-10-09 14:43:51
