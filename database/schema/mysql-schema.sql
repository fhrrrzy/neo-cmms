/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `api_sync_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_sync_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sync_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','running','completed','failed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL,
  `records_processed` int NOT NULL DEFAULT '0',
  `records_success` int NOT NULL DEFAULT '0',
  `records_failed` int NOT NULL DEFAULT '0',
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `sync_started_at` timestamp NULL DEFAULT NULL,
  `sync_completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `api_sync_logs_sync_type_status_index` (`sync_type`,`status`),
  KEY `api_sync_logs_sync_started_at_index` (`sync_started_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `breezy_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `breezy_sessions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `authenticatable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `authenticatable_id` bigint unsigned NOT NULL,
  `panel_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guard` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `expires_at` timestamp NULL DEFAULT NULL,
  `two_factor_secret` text COLLATE utf8mb4_unicode_ci,
  `two_factor_recovery_codes` text COLLATE utf8mb4_unicode_ci,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `breezy_sessions_authenticatable_type_authenticatable_id_index` (`authenticatable_type`,`authenticatable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `equipment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `equipment` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `api_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mandt` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `baujj` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `groes` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `herst` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mrnug` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'MRNGU from API',
  `eqtyp` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'EQTYP from API',
  `eqart` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'EQART from API',
  `maintenance_planner_group` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'MAINTAINANCE_PLANNER_GROUP from API',
  `maintenance_work_center` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'MAINTAINANCE_WORK_CENTER from API',
  `functional_location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'FUNCTIONAL_LOCATION from API',
  `description_func_location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'DESCRIPTION_FUNC_LOCATION from API',
  `equipment_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `plant_id` bigint unsigned NOT NULL,
  `station_id` bigint unsigned DEFAULT NULL,
  `equipment_group_id` bigint unsigned DEFAULT NULL,
  `company_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `equipment_description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `object_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `point` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `api_created_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `equipment_equipment_number_unique` (`equipment_number`),
  KEY `equipment_plant_id_equipment_group_id_index` (`plant_id`,`equipment_group_id`),
  KEY `equipment_equipment_number_index` (`equipment_number`),
  KEY `equipment_equipment_group_id_foreign` (`equipment_group_id`),
  KEY `equipment_station_id_foreign` (`station_id`),
  KEY `equipment_api_id_equipment_number_index` (`api_id`,`equipment_number`),
  KEY `equipment_functional_location_maintenance_work_center_index` (`functional_location`,`maintenance_work_center`),
  KEY `equipment_functional_location_index` (`functional_location`),
  CONSTRAINT `equipment_equipment_group_id_foreign` FOREIGN KEY (`equipment_group_id`) REFERENCES `equipment_groups` (`id`) ON DELETE SET NULL,
  CONSTRAINT `equipment_plant_id_foreign` FOREIGN KEY (`plant_id`) REFERENCES `plants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `equipment_station_id_foreign` FOREIGN KEY (`station_id`) REFERENCES `stations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `equipment_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `equipment_groups` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `equipment_materials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `equipment_materials` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ims_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `plant_id` bigint unsigned DEFAULT NULL,
  `equipment_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `material_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reservation_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reservation_item` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reservation_type` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `requirement_type` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reservation_status` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deletion_flag` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `goods_receipt_flag` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `final_issue_flag` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `error_flag` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `storage_location` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `production_supply_area` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `batch_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `storage_bin` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `special_stock_indicator` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `requirement_date` date DEFAULT NULL,
  `requirement_qty` decimal(18,3) DEFAULT NULL,
  `unit_of_measure` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `debit_credit_indicator` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `issued_qty` decimal(18,3) DEFAULT NULL,
  `withdrawn_qty` decimal(18,3) DEFAULT NULL,
  `withdrawn_value` decimal(18,2) DEFAULT NULL,
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entry_qty` decimal(18,3) DEFAULT NULL,
  `entry_uom` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `planned_order` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `purchase_requisition` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `purchase_requisition_item` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `production_order` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `movement_type` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gl_account` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receiving_storage_loc` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receiving_plant` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `api_created_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `equipment_materials_ims_id_unique` (`ims_id`),
  KEY `equipment_materials_plant_id_requirement_date_index` (`plant_id`,`requirement_date`),
  KEY `equipment_materials_equipment_number_index` (`equipment_number`),
  KEY `equipment_materials_material_number_index` (`material_number`),
  KEY `equipment_materials_production_order_index` (`production_order`),
  CONSTRAINT `equipment_materials_plant_id_foreign` FOREIGN KEY (`plant_id`) REFERENCES `plants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `equipment_work_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `equipment_work_orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ims_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reservation` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `requirement_type` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reservation_status` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `item_deleted` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `movement_allowed` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `final_issue` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `missing_part` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `material` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `plant_id` bigint unsigned DEFAULT NULL,
  `equipment_id` bigint unsigned DEFAULT NULL,
  `storage_location` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `requirements_date` date DEFAULT NULL,
  `requirement_quantity` decimal(18,3) DEFAULT NULL,
  `base_unit_of_measure` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `debit_credit_ind` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity_is_fixed` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity_withdrawn` decimal(18,3) DEFAULT NULL,
  `value_withdrawn` decimal(18,2) DEFAULT NULL,
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qty_in_unit_of_entry` decimal(18,3) DEFAULT NULL,
  `unit_of_entry` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `movement_type` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gl_account` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receiving_plant` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receiving_storage_location` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qty_for_avail_check` decimal(18,3) DEFAULT NULL,
  `goods_recipient` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `material_group` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `acct_manually` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `commitment_item_1` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `funds_center` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_time` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `end_time` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `service_duration` decimal(10,2) DEFAULT NULL,
  `service_dur_unit` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `api_updated_at` timestamp NULL DEFAULT NULL,
  `commitment_item_2` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `equipment_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `equipment_work_orders_ims_id_unique` (`ims_id`),
  KEY `equipment_work_orders_plant_id_requirements_date_index` (`plant_id`,`requirements_date`),
  KEY `equipment_work_orders_equipment_number_index` (`equipment_number`),
  KEY `equipment_work_orders_equipment_id_index` (`equipment_id`),
  CONSTRAINT `equipment_work_orders_equipment_id_foreign` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`) ON DELETE SET NULL,
  CONSTRAINT `equipment_work_orders_plant_id_foreign` FOREIGN KEY (`plant_id`) REFERENCES `plants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_id` bigint unsigned NOT NULL,
  `data` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `plants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `plants` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `plant_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `regional_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kaps_terpasang` int unsigned NOT NULL,
  `faktor_koreksi_kaps` int unsigned NOT NULL,
  `faktor_koreksi_utilitas` int unsigned NOT NULL,
  `unit` tinyint unsigned NOT NULL,
  `instalasi_bunch_press` tinyint(1) NOT NULL,
  `pln_isasi` tinyint(1) NOT NULL,
  `cofiring` tinyint(1) NOT NULL,
  `hidden_pica_cost` tinyint(1) NOT NULL,
  `hidden_pica_cpo` tinyint(1) NOT NULL,
  `jenis` tinyint unsigned NOT NULL,
  `kaps_terpasang_sf` int unsigned NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `plants_plant_code_unique` (`plant_code`),
  KEY `plants_regional_id_foreign` (`regional_id`),
  CONSTRAINT `plants_regional_id_foreign` FOREIGN KEY (`regional_id`) REFERENCES `regions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `queue_monitors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `queue_monitors` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `job_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `finished_at` timestamp NULL DEFAULT NULL,
  `failed` tinyint(1) NOT NULL DEFAULT '0',
  `attempt` int NOT NULL DEFAULT '0',
  `progress` int DEFAULT NULL,
  `exception_message` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `queue_monitors_job_id_index` (`job_id`),
  KEY `queue_monitors_started_at_index` (`started_at`),
  KEY `queue_monitors_failed_index` (`failed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `regions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `regions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `no` int unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `equipment_group_id` bigint unsigned DEFAULT NULL,
  `equipment_id` bigint unsigned DEFAULT NULL,
  `rules` json NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rules_equipment_id_foreign` (`equipment_id`),
  KEY `rules_equipment_group_id_equipment_id_index` (`equipment_group_id`,`equipment_id`),
  CONSTRAINT `rules_equipment_group_id_foreign` FOREIGN KEY (`equipment_group_id`) REFERENCES `equipment_groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `rules_equipment_id_foreign` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `running_times`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `running_times` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ims_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `api_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `equipment_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` date NOT NULL,
  `plant_id` bigint unsigned DEFAULT NULL,
  `date_time` datetime DEFAULT NULL,
  `running_hours` decimal(10,2) DEFAULT NULL,
  `counter_reading` decimal(15,2) DEFAULT NULL,
  `maintenance_text` text COLLATE utf8mb4_unicode_ci,
  `company_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `equipment_description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `object_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `api_created_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `running_times_equipment_number_date_unique` (`equipment_number`,`date`),
  UNIQUE KEY `running_times_api_id_unique` (`api_id`),
  UNIQUE KEY `running_times_ims_id_unique` (`ims_id`),
  KEY `running_times_plant_id_date_index` (`plant_id`,`date`),
  KEY `running_times_equipment_number_date_index` (`equipment_number`,`date`),
  KEY `running_times_api_id_index` (`api_id`),
  KEY `running_times_ims_id_index` (`ims_id`),
  CONSTRAINT `running_times_plant_id_foreign` FOREIGN KEY (`plant_id`) REFERENCES `plants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `plant_id` bigint unsigned NOT NULL,
  `cost_center` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `keterangan` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'OBJEK',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stations_plant_id_cost_center_unique` (`plant_id`,`cost_center`),
  KEY `stations_plant_id_cost_center_index` (`plant_id`,`cost_center`),
  CONSTRAINT `stations_plant_id_foreign` FOREIGN KEY (`plant_id`) REFERENCES `plants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `two_factor_secret` text COLLATE utf8mb4_unicode_ci,
  `two_factor_recovery_codes` text COLLATE utf8mb4_unicode_ci,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `role` enum('superadmin','pks') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pks',
  `plant_id` bigint unsigned DEFAULT NULL,
  `theme` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'default',
  `theme_color` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_plant_id_foreign` (`plant_id`),
  CONSTRAINT `users_plant_id_foreign` FOREIGN KEY (`plant_id`) REFERENCES `plants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `work_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `work_orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ims_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mandt` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `order_type` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_on` date DEFAULT NULL,
  `change_date_for_order_master` date DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `company_code` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `plant_id` bigint unsigned DEFAULT NULL,
  `station_id` bigint unsigned DEFAULT NULL,
  `plant_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `responsible_cctr` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order_status` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `technical_completion` date DEFAULT NULL,
  `cost_center` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `profit_center` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `object_class` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `main_work_center` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notification` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cause` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cause_text` text COLLATE utf8mb4_unicode_ci,
  `code_group_problem` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `item_text` text COLLATE utf8mb4_unicode_ci,
  `created` timestamp NULL DEFAULT NULL,
  `released` timestamp NULL DEFAULT NULL,
  `completed` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `closed` timestamp NULL DEFAULT NULL,
  `planned_release` timestamp NULL DEFAULT NULL,
  `planned_completion` timestamp NULL DEFAULT NULL,
  `planned_closing_date` timestamp NULL DEFAULT NULL,
  `release` timestamp NULL DEFAULT NULL,
  `close` timestamp NULL DEFAULT NULL,
  `api_updated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `work_orders_ims_id_unique` (`ims_id`),
  UNIQUE KEY `work_orders_order_unique` (`order`),
  KEY `work_orders_plant_id_order_status_index` (`plant_id`,`order_status`),
  KEY `work_orders_created_on_order_type_index` (`created_on`,`order_type`),
  KEY `work_orders_order_index` (`order`),
  KEY `work_orders_station_id_foreign` (`station_id`),
  KEY `work_orders_plant_id_station_id_index` (`plant_id`,`station_id`),
  CONSTRAINT `work_orders_plant_id_foreign` FOREIGN KEY (`plant_id`) REFERENCES `plants` (`id`) ON DELETE SET NULL,
  CONSTRAINT `work_orders_station_id_foreign` FOREIGN KEY (`station_id`) REFERENCES `stations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'0001_01_01_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2025_08_14_170933_add_two_factor_columns_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2025_09_22_030000_create_regions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2025_09_22_030303_create_plants_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2025_09_22_030530_create_equipment_groups_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2025_09_22_030531_create_equipment_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2025_09_22_030532_create_equipment_running_times_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2025_09_22_030551_create_api_sync_logs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2025_09_22_045008_create_rules_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2025_09_22_045410_add_roles_and_plant_id_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2025_09_22_065524_create_filament-jobs-monitor_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2025_09_22_074928_create_notifications_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2025_09_22_150000_make_equipment_group_nullable_on_equipment_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2025_09_25_021630_create_work_orders_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2025_09_25_022807_update_api_sync_logs_add_work_order_type',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2025_09_25_023715_create_running_times_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2025_09_25_095554_create_breezy_sessions_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2025_09_25_100214_add_themes_settings_to_users_table',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2025_09_25_120000_add_api_id_to_running_times_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2025_09_25_000001_create_stations_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2025_09_25_000002_add_station_id_to_equipment_and_work_orders',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2025_09_30_000001_create_equipment_work_orders_table',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2025_09_30_000002_create_equipment_materials_table',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2025_09_30_120000_alter_api_sync_logs_sync_type_to_string',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2025_10_01_022849_add_equipment_id_to_equipment_work_orders_table',13);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2025_10_01_055623_remove_is_active_from_equipment_table',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2025_10_02_064154_add_additional_columns_to_equipment_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2025_10_02_064622_add_all_missing_api_fields_to_equipment_table',16);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2025_10_02_090703_add_ims_id_column_to_running_times_table',17);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2025_10_02_105456_drop_equipment_running_times_table',18);
