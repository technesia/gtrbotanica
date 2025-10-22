-- MySQL dump 10.13  Distrib 8.0.43, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: botanica
-- ------------------------------------------------------
-- Server version	9.2.0

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `ipl`
--

DROP TABLE IF EXISTS `ipl`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ipl` (
  `id` int NOT NULL AUTO_INCREMENT,
  `blok` varchar(10) NOT NULL,
  `no_rumah` varchar(10) NOT NULL,
  `nama_warga` varchar(100) NOT NULL,
  `ipl_januari` int DEFAULT '0',
  `ipl_februari` int DEFAULT '0',
  `ipl_maret` int DEFAULT '0',
  `ipl_april` int DEFAULT '0',
  `ipl_mei` int DEFAULT '0',
  `ipl_juni` int DEFAULT '0',
  `ipl_juli` int DEFAULT '0',
  `ipl_agustus` int DEFAULT '0',
  `ipl_september` int DEFAULT '0',
  `ipl_oktober` int DEFAULT '0',
  `ipl_november` int DEFAULT '0',
  `ipl_desember` int DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ipl`
--

LOCK TABLES `ipl` WRITE;
/*!40000 ALTER TABLE `ipl` DISABLE KEYS */;
INSERT INTO `ipl` VALUES (1,'BA1','1','SUCI AMALIA',0,0,65000,100000,100000,0,0,0,0,0,0,0),(2,'BA1','2','RENDI DEWANTARA',0,0,100000,100000,100000,0,0,0,0,0,0,0),(3,'BA1','10','NIA JUNIATI',0,0,65000,65000,65000,0,0,0,0,0,0,0);
/*!40000 ALTER TABLE `ipl` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-10-19 15:02:57
