-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: adver_leads
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
-- Table structure for table `admin_permissions`
--

DROP TABLE IF EXISTS `admin_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL,
  `tab` varchar(50) NOT NULL COMMENT 'web | seo | smm | automation | security',
  `can_access` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_admin_tab` (`admin_id`,`tab`),
  CONSTRAINT `fk_perm_admin_id` FOREIGN KEY (`admin_id`) REFERENCES `admin_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_permissions`
--

LOCK TABLES `admin_permissions` WRITE;
/*!40000 ALTER TABLE `admin_permissions` DISABLE KEYS */;
INSERT INTO `admin_permissions` VALUES (1,2,'web',1),(2,2,'seo',0),(3,2,'smm',0),(4,2,'automation',0),(5,2,'security',1);
/*!40000 ALTER TABLE `admin_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_users`
--

DROP TABLE IF EXISTS `admin_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_super_admin` tinyint(1) NOT NULL DEFAULT 0,
  `display_name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_users`
--

LOCK TABLES `admin_users` WRITE;
/*!40000 ALTER TABLE `admin_users` DISABLE KEYS */;
INSERT INTO `admin_users` VALUES (1,'admin','$2y$10$5nYoLF.6UtTE2nehqN/6bO/MCNAB4su6L8j2np44LuuGKvWasD6LG',1,'Super Admin','superadmin@adverdigify.com',NULL,'2026-06-24 06:45:11'),(2,'Aditya','$2y$10$sgH3pEDqIZrwunFnmKVbG.LJ.P2y.q1D9DFd4m5ER4G6kkTpX5736',0,'Aditya','aditya@adver.in',1,'2026-06-29 05:54:20');
/*!40000 ALTER TABLE `admin_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `automation_leads`
--

DROP TABLE IF EXISTS `automation_leads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `automation_leads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `business_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `business_type` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `automation_leads`
--

LOCK TABLES `automation_leads` WRITE;
/*!40000 ALTER TABLE `automation_leads` DISABLE KEYS */;
INSERT INTO `automation_leads` VALUES (2,'Aditya','clothing store','aditya@email.com','9113399421','Education / Coaching','','2026-06-24 11:39:46',1),(3,'Ankit','clothing store','ankit@gmail.com','9876543210','Education / Coaching','','2026-06-25 10:12:01',1);
/*!40000 ALTER TABLE `automation_leads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lead_status_updates`
--

DROP TABLE IF EXISTS `lead_status_updates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lead_status_updates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lead_type` varchar(20) NOT NULL,
  `lead_id` int(11) NOT NULL,
  `updated_by` int(11) NOT NULL,
  `status` varchar(60) NOT NULL COMMENT 'Qualified | Initial Contact Made | Proposal Sent | In Discussion | Follow-Up Scheduled | No Response | Closed - Won | Closed - Lost',
  `description` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_status_updated_by` (`updated_by`),
  KEY `idx_lead_status` (`lead_type`,`lead_id`),
  CONSTRAINT `fk_status_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `admin_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lead_status_updates`
--

LOCK TABLES `lead_status_updates` WRITE;
/*!40000 ALTER TABLE `lead_status_updates` DISABLE KEYS */;
/*!40000 ALTER TABLE `lead_status_updates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seo_leads`
--

DROP TABLE IF EXISTS `seo_leads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `seo_leads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `business_name` varchar(255) NOT NULL,
  `website` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `seo_need` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seo_leads`
--

LOCK TABLES `seo_leads` WRITE;
/*!40000 ALTER TABLE `seo_leads` DISABLE KEYS */;
INSERT INTO `seo_leads` VALUES (1,'Amresh','Litti Hut','http://littihut.com','info@litti.com','7779932178','On-Page & Technical SEO','2026-06-25 06:44:02',1);
/*!40000 ALTER TABLE `seo_leads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `smm_leads`
--

DROP TABLE IF EXISTS `smm_leads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `smm_leads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `business_name` varchar(255) NOT NULL,
  `instagram_or_website` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `smm_need` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `smm_leads`
--

LOCK TABLES `smm_leads` WRITE;
/*!40000 ALTER TABLE `smm_leads` DISABLE KEYS */;
INSERT INTO `smm_leads` VALUES (1,'Aditya','Pepsico','www.pepsico.com','pepsico@gmail.com','7779932178','Strategy & Analytics','2026-06-24 06:54:45',1),(2,'jagdeep','jagdeep food plaza','www.instagram/jagdeep/dlk.com','jay@email.com','6256448764','Strategy & Analytics','2026-06-25 07:11:43',1);
/*!40000 ALTER TABLE `smm_leads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `web_leads`
--

DROP TABLE IF EXISTS `web_leads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `web_leads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `service` varchar(100) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `web_leads`
--

LOCK TABLES `web_leads` WRITE;
/*!40000 ALTER TABLE `web_leads` DISABLE KEYS */;
INSERT INTO `web_leads` VALUES (1,'Rajesh','Rajesh@email.com','6987546515','growth','want to build a website','2026-06-24 06:56:09',1),(2,'rmau','jakk@gmail.com','9876543210','growth','hi ek l lkflj','2026-06-25 07:12:13',1),(3,'aditya Singh','fklz2@dlkl.com','9876543210','premium','','2026-06-25 07:12:43',1),(4,'aditya Singh','ak@doomsday.in','9876543210','starter','hi how are you','2026-06-25 07:33:58',1),(5,'aditya Singh','ak@doomsday.in','9876543210','custom','','2026-06-25 07:36:31',1),(6,'ramesh','ak@gmail.com','9876543210','growth','','2026-06-25 07:37:58',1);
/*!40000 ALTER TABLE `web_leads` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-29 17:53:08
