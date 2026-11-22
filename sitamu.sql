-- =====================================================================
-- SiTamu: database bersih untuk deploy
-- Dibuat 19-11-2026 dari migrasi sampai 2026_11_14_000001_add_manual_entry_columns.
--
-- Isi: struktur semua tabel, catatan tabel `migrations` (agar `php artisan migrate`
-- berikutnya hanya menjalankan migrasi baru), dan 5 kelompok rentan bawaan.
-- Tidak ada data tamu, akun, atau data contoh. Tidak ada Super Admin: buat lewat
-- `php artisan db:seed --force` (isian SUPERADMIN_* di .env) atau
-- `php artisan sitamu:create-superadmin`.
--
-- Cara pakai: buat database kosong (utf8mb4_unicode_ci), lalu impor file ini, misalnya
--   mysql --default-character-set=utf8mb4 -u USER -p NAMA_DB < sitamu.sql
-- atau lewat menu Import di phpMyAdmin. File ini tidak berisi DROP TABLE, jadi impor ke
-- database yang sudah berisi tabel akan gagal dan tidak menghapus data.
--
-- Bila ada migrasi baru, buat ulang file ini (lihat README bagian "Database bersih").
-- =====================================================================

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `action` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entity_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `detail` json DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `audit_logs_user_id_foreign` (`user_id`),
  KEY `audit_logs_action_index` (`action`),
  KEY `audit_logs_created_at_index` (`created_at`),
  CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `departments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `location_id` bigint unsigned NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `departments_location_id_name_unique` (`location_id`,`name`),
  CONSTRAINT `departments_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `guest_documents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(254) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('ktp','selfie') COLLATE utf8mb4_unicode_ci NOT NULL,
  `image` mediumblob NOT NULL,
  `mime_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'image/jpeg',
  `width` smallint unsigned NOT NULL,
  `height` smallint unsigned NOT NULL,
  `size_bytes` int unsigned NOT NULL,
  `sha256` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `qr_code_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uploaded_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `guest_documents_qr_code_id_foreign` (`qr_code_id`),
  KEY `guest_documents_email_type_id_index` (`email`,`type`,`id`),
  KEY `guest_documents_uploaded_by_foreign` (`uploaded_by`),
  CONSTRAINT `guest_documents_qr_code_id_foreign` FOREIGN KEY (`qr_code_id`) REFERENCES `qr_codes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `guest_documents_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `guest_visits` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `location_id` bigint unsigned NOT NULL,
  `qr_code_id` bigint unsigned NOT NULL,
  `visit_date` date NOT NULL,
  `daily_number` int unsigned NOT NULL,
  `email` varchar(254) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` datetime DEFAULT NULL,
  `purpose_category_id` bigint unsigned DEFAULT NULL,
  `purpose_category_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `suggested_department_id` bigint unsigned DEFAULT NULL,
  `purpose` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `vulnerable_group` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `organization` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('waiting','processing','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'waiting',
  `check_in_at` datetime NOT NULL,
  `assigned_department_id` bigint unsigned DEFAULT NULL,
  `assigned_at` datetime DEFAULT NULL,
  `assigned_by` bigint unsigned DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL,
  `processed_by` bigint unsigned DEFAULT NULL,
  `served_by_staff_id` bigint unsigned DEFAULT NULL,
  `result` text COLLATE utf8mb4_unicode_ci,
  `completed_at` datetime DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `cancel_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `closed_by` bigint unsigned DEFAULT NULL,
  `consent_at` datetime NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entered_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `guest_visits_location_id_visit_date_daily_number_unique` (`location_id`,`visit_date`,`daily_number`),
  KEY `guest_visits_purpose_category_id_foreign` (`purpose_category_id`),
  KEY `guest_visits_suggested_department_id_foreign` (`suggested_department_id`),
  KEY `guest_visits_assigned_by_foreign` (`assigned_by`),
  KEY `guest_visits_processed_by_foreign` (`processed_by`),
  KEY `guest_visits_served_by_staff_id_foreign` (`served_by_staff_id`),
  KEY `guest_visits_closed_by_foreign` (`closed_by`),
  KEY `guest_visits_location_id_status_check_in_at_index` (`location_id`,`status`,`check_in_at`),
  KEY `guest_visits_qr_code_id_status_check_in_at_index` (`qr_code_id`,`status`,`check_in_at`),
  KEY `guest_visits_assigned_department_id_status_check_in_at_index` (`assigned_department_id`,`status`,`check_in_at`),
  KEY `guest_visits_email_check_in_at_index` (`email`,`check_in_at`),
  KEY `guest_visits_status_check_in_at_index` (`status`,`check_in_at`),
  KEY `guest_visits_check_in_at_index` (`check_in_at`),
  KEY `guest_visits_completed_at_index` (`completed_at`),
  KEY `guest_visits_entered_by_foreign` (`entered_by`),
  CONSTRAINT `guest_visits_assigned_by_foreign` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `guest_visits_assigned_department_id_foreign` FOREIGN KEY (`assigned_department_id`) REFERENCES `departments` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `guest_visits_closed_by_foreign` FOREIGN KEY (`closed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `guest_visits_entered_by_foreign` FOREIGN KEY (`entered_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `guest_visits_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `guest_visits_processed_by_foreign` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `guest_visits_purpose_category_id_foreign` FOREIGN KEY (`purpose_category_id`) REFERENCES `purpose_categories` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `guest_visits_qr_code_id_foreign` FOREIGN KEY (`qr_code_id`) REFERENCES `qr_codes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `guest_visits_served_by_staff_id_foreign` FOREIGN KEY (`served_by_staff_id`) REFERENCES `staff_members` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `guest_visits_suggested_department_id_foreign` FOREIGN KEY (`suggested_department_id`) REFERENCES `departments` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `locations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `locations_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purpose_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `location_id` bigint unsigned NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `suggested_department_id` bigint unsigned DEFAULT NULL,
  `sort_order` smallint unsigned NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `purpose_categories_location_id_name_unique` (`location_id`,`name`),
  KEY `purpose_categories_suggested_department_id_foreign` (`suggested_department_id`),
  CONSTRAINT `purpose_categories_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `purpose_categories_suggested_department_id_foreign` FOREIGN KEY (`suggested_department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `qr_codes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `location_id` bigint unsigned NOT NULL,
  `department_id` bigint unsigned DEFAULT NULL,
  `receptionist_id` bigint unsigned NOT NULL,
  `token` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive','revoked') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `revoked_at` datetime DEFAULT NULL,
  `last_used_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `qr_codes_token_unique` (`token`),
  KEY `qr_codes_location_id_foreign` (`location_id`),
  KEY `qr_codes_receptionist_id_foreign` (`receptionist_id`),
  KEY `qr_codes_created_by_foreign` (`created_by`),
  KEY `qr_codes_department_id_foreign` (`department_id`),
  CONSTRAINT `qr_codes_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `qr_codes_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `qr_codes_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `qr_codes_receptionist_id_foreign` FOREIGN KEY (`receptionist_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `staff_members` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `department_id` bigint unsigned NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `staff_members_department_id_name_unique` (`department_id`,`name`),
  CONSTRAINT `staff_members_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_locations` (
  `user_id` bigint unsigned NOT NULL,
  `location_id` bigint unsigned NOT NULL,
  `assigned_by` bigint unsigned DEFAULT NULL,
  `assigned_at` datetime NOT NULL,
  PRIMARY KEY (`user_id`,`location_id`),
  KEY `user_locations_location_id_foreign` (`location_id`),
  KEY `user_locations_assigned_by_foreign` (`assigned_by`),
  CONSTRAINT `user_locations_assigned_by_foreign` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `user_locations_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_locations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('super_admin','admin','receptionist','pic') COLLATE utf8mb4_unicode_ci NOT NULL,
  `department_id` bigint unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `failed_logins` tinyint unsigned NOT NULL DEFAULT '0',
  `locked_until` datetime DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_department_id_foreign` (`department_id`),
  CONSTRAINT `users_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `visit_assignments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `visit_id` bigint unsigned NOT NULL,
  `department_id` bigint unsigned NOT NULL,
  `assigned_by` bigint unsigned DEFAULT NULL,
  `assigned_at` datetime NOT NULL,
  `staff_member_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `visit_assignments_visit_id_foreign` (`visit_id`),
  KEY `visit_assignments_department_id_foreign` (`department_id`),
  KEY `visit_assignments_assigned_by_foreign` (`assigned_by`),
  KEY `visit_assignments_staff_member_id_foreign` (`staff_member_id`),
  CONSTRAINT `visit_assignments_assigned_by_foreign` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `visit_assignments_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `visit_assignments_staff_member_id_foreign` FOREIGN KEY (`staff_member_id`) REFERENCES `staff_members` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `visit_assignments_visit_id_foreign` FOREIGN KEY (`visit_id`) REFERENCES `guest_visits` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `visit_counters` (
  `location_id` bigint unsigned NOT NULL,
  `visit_date` date NOT NULL,
  `last_number` int unsigned NOT NULL,
  PRIMARY KEY (`location_id`,`visit_date`),
  CONSTRAINT `visit_counters_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `visit_signatures` (
  `visit_id` bigint unsigned NOT NULL,
  `image` mediumblob NOT NULL,
  `mime_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'image/png',
  `size_bytes` int unsigned NOT NULL,
  `sha256` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`visit_id`),
  CONSTRAINT `visit_signatures_visit_id_foreign` FOREIGN KEY (`visit_id`) REFERENCES `guest_visits` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `visit_status_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `visit_id` bigint unsigned NOT NULL,
  `location_id` bigint unsigned NOT NULL,
  `from_status` enum('waiting','processing','completed','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `to_status` enum('waiting','processing','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL,
  `changed_by` bigint unsigned DEFAULT NULL,
  `staff_member_id` bigint unsigned DEFAULT NULL,
  `note` varchar(2500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `visit_status_logs_visit_id_foreign` (`visit_id`),
  KEY `visit_status_logs_changed_by_foreign` (`changed_by`),
  KEY `visit_status_logs_staff_member_id_foreign` (`staff_member_id`),
  CONSTRAINT `visit_status_logs_changed_by_foreign` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `visit_status_logs_staff_member_id_foreign` FOREIGN KEY (`staff_member_id`) REFERENCES `staff_members` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `visit_status_logs_visit_id_foreign` FOREIGN KEY (`visit_id`) REFERENCES `guest_visits` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vulnerable_groups` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `priority` tinyint unsigned DEFAULT NULL,
  `sort_order` smallint unsigned NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vulnerable_groups_code_unique` (`code`),
  UNIQUE KEY `vulnerable_groups_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Data wajib

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'2026_10_18_000001_create_locations_table',1),(2,'2026_10_18_000002_create_departments_table',1),(3,'2026_10_18_000003_create_users_table',1),(4,'2026_10_18_000004_create_user_locations_table',1),(5,'2026_10_18_000005_create_qr_codes_table',1),(6,'2026_10_18_000006_create_purpose_categories_table',1),(7,'2026_10_18_000007_create_staff_members_table',1),(8,'2026_10_18_000008_create_guest_visits_table',1),(9,'2026_10_18_000009_create_visit_counters_table',1),(10,'2026_10_18_000010_create_visit_signatures_table',1),(11,'2026_10_18_000011_create_visit_assignments_table',1),(12,'2026_10_18_000012_create_visit_status_logs_table',1),(13,'2026_10_18_000013_create_audit_logs_table',1),(14,'2026_10_19_000001_add_department_id_to_qr_codes_table',1),(15,'2026_10_19_000014_widen_name_on_purpose_categories_table',1),(16,'2026_10_19_000015_widen_purpose_category_name_on_guest_visits_table',1),(17,'2026_11_12_000001_make_purpose_category_nullable_on_guest_visits_table',1),(18,'2026_11_12_000002_create_vulnerable_groups_table',1),(19,'2026_11_13_000001_create_guest_documents_table',1),(20,'2026_11_14_000001_add_manual_entry_columns',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;

/*!40000 ALTER TABLE `vulnerable_groups` DISABLE KEYS */;
INSERT INTO `vulnerable_groups` (`id`, `code`, `name`, `priority`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (1,'bukan','Bukan kelompok rentan',NULL,10,1,'2026-11-19 16:04:13','2026-11-19 16:04:13'),(2,'lansia','Lansia',1,20,1,'2026-11-19 16:04:13','2026-11-19 16:04:13'),(3,'disabilitas','Penyandang disabilitas',2,30,1,'2026-11-19 16:04:13','2026-11-19 16:04:13'),(4,'ibu_hamil','Ibu hamil',3,40,1,'2026-11-19 16:04:13','2026-11-19 16:04:13'),(5,'tidak_menyebutkan','Tidak ingin menyebutkan',NULL,50,1,'2026-11-19 16:04:13','2026-11-19 16:04:13');
/*!40000 ALTER TABLE `vulnerable_groups` ENABLE KEYS */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
