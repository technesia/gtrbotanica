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
-- Temporary view structure for view `v_laporan_keuangan`
--

DROP TABLE IF EXISTS `v_laporan_keuangan`;
/*!50001 DROP VIEW IF EXISTS `v_laporan_keuangan`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_laporan_keuangan` AS SELECT 
 1 AS `tipe`,
 1 AS `keterangan`,
 1 AS `jumlah`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_pengeluaran_per_bulan`
--

DROP TABLE IF EXISTS `v_pengeluaran_per_bulan`;
/*!50001 DROP VIEW IF EXISTS `v_pengeluaran_per_bulan`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_pengeluaran_per_bulan` AS SELECT 
 1 AS `periode`,
 1 AS `nama_bulan`,
 1 AS `total_pengeluaran`*/;
SET character_set_client = @saved_cs_client;

--
-- Final view structure for view `v_laporan_keuangan`
--

/*!50001 DROP VIEW IF EXISTS `v_laporan_keuangan`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_laporan_keuangan` AS select 'Pemasukan' AS `tipe`,concat('IPL ',monthname(str_to_date(concat('2024-',`m`.`bulan`,'-01'),'%Y-%M-%d'))) AS `keterangan`,sum(`m`.`total`) AS `jumlah` from (select 'maret' AS `bulan`,sum(`ipl`.`ipl_maret`) AS `total` from `ipl` union all select 'april' AS `april`,sum(`ipl`.`ipl_april`) AS `SUM(ipl_april)` from `ipl` union all select 'mei' AS `mei`,sum(`ipl`.`ipl_mei`) AS `SUM(ipl_mei)` from `ipl`) `m` group by `m`.`bulan` union all select 'Pengeluaran' AS `tipe`,`pengeluaran`.`kategori` AS `kategori`,sum(`pengeluaran`.`jumlah`) AS `jumlah` from `pengeluaran` group by `pengeluaran`.`kategori` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_pengeluaran_per_bulan`
--

/*!50001 DROP VIEW IF EXISTS `v_pengeluaran_per_bulan`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_pengeluaran_per_bulan` AS select date_format(`pengeluaran`.`tanggal`,'%Y-%m') AS `periode`,date_format(`pengeluaran`.`tanggal`,'%M %Y') AS `nama_bulan`,sum(`pengeluaran`.`jumlah`) AS `total_pengeluaran` from `pengeluaran` group by date_format(`pengeluaran`.`tanggal`,'%Y-%m') order by min(`pengeluaran`.`tanggal`) */;
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

-- Dump completed on 2025-10-19 15:02:57
