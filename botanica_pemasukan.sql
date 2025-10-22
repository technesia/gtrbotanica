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
-- Table structure for table `pemasukan`
--

DROP TABLE IF EXISTS `pemasukan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pemasukan` (
  `id` int NOT NULL AUTO_INCREMENT,
  `keterangan` text,
  `jumlah` int NOT NULL,
  `tanggal_input` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pemasukan`
--

LOCK TABLES `pemasukan` WRITE;
/*!40000 ALTER TABLE `pemasukan` DISABLE KEYS */;
INSERT INTO `pemasukan` VALUES (1,'Penerimaan IPL Bulan Januari 2024',5200000,'2025-10-19 06:53:46'),(2,'Penerimaan IPL Bulan Februari 2024',5400000,'2025-10-19 06:53:46'),(3,'Penerimaan IPL Bulan Maret 2024',5600000,'2025-10-19 06:53:46'),(4,'Penerimaan IPL Bulan April 2024',6000000,'2025-10-19 06:53:46'),(5,'Penerimaan IPL Bulan Mei 2024',5900000,'2025-10-19 06:53:46'),(6,'Penerimaan IPL Bulan Juni 2024',6150000,'2025-10-19 06:53:46'),(7,'Penerimaan IPL Bulan Juli 2024',6100000,'2025-10-19 06:53:46'),(8,'Penerimaan IPL Bulan Agustus 2024',6300000,'2025-10-19 06:53:46'),(9,'Penerimaan IPL Bulan September 2024',6200000,'2025-10-19 06:53:46'),(10,'Penerimaan IPL Bulan Oktober 2024',6400000,'2025-10-19 06:53:46'),(11,'Penerimaan IPL Bulan November 2024',6500000,'2025-10-19 06:53:46'),(12,'Penerimaan IPL Bulan Desember 2024',6700000,'2025-10-19 06:53:46'),(13,'Denda Keterlambatan IPL (berbagai bulan)',250000,'2025-10-19 06:53:46'),(14,'Sumbangan Warga untuk kegiatan sosial',500000,'2025-10-19 06:53:46'),(15,'Pendapatan dari sewa fasilitas umum (lapangan)',300000,'2025-10-19 06:53:46');
/*!40000 ALTER TABLE `pemasukan` ENABLE KEYS */;
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
