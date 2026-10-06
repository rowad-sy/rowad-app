/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `academic_levels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `academic_levels` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `course_id` bigint(20) unsigned DEFAULT NULL,
  `name_ar` varchar(200) NOT NULL,
  `name_en` varchar(200) DEFAULT NULL,
  `code` varchar(100) DEFAULT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'grade',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `academic_levels_project_id_index` (`project_id`),
  KEY `academic_levels_course_id_index` (`course_id`),
  CONSTRAINT `academic_levels_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `academic_levels_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ad_design_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ad_design_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `center_id` bigint(20) unsigned DEFAULT NULL,
  `project_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pm2_review',
  `refer_to_pm2_id` bigint(20) unsigned DEFAULT NULL,
  `refer_to_rowaduna_id` bigint(20) unsigned DEFAULT NULL,
  `refer_to_designer_id` bigint(20) unsigned DEFAULT NULL,
  `refer_to_publisher_id` bigint(20) unsigned DEFAULT NULL,
  `design_url` varchar(255) DEFAULT NULL,
  `design_note` text DEFAULT NULL,
  `revision_note` text DEFAULT NULL,
  `publish_links` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`publish_links`)),
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `published_by` bigint(20) unsigned DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ad_design_requests_center_id_foreign` (`center_id`),
  KEY `ad_design_requests_project_id_foreign` (`project_id`),
  KEY `ad_design_requests_created_by_foreign` (`created_by`),
  KEY `ad_design_requests_refer_to_pm2_id_foreign` (`refer_to_pm2_id`),
  KEY `ad_design_requests_refer_to_rowaduna_id_foreign` (`refer_to_rowaduna_id`),
  KEY `ad_design_requests_refer_to_designer_id_foreign` (`refer_to_designer_id`),
  KEY `ad_design_requests_refer_to_publisher_id_foreign` (`refer_to_publisher_id`),
  KEY `ad_design_requests_approved_by_foreign` (`approved_by`),
  KEY `ad_design_requests_published_by_foreign` (`published_by`),
  KEY `ad_design_requests_status_created_by_index` (`status`,`created_by`),
  CONSTRAINT `ad_design_requests_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ad_design_requests_center_id_foreign` FOREIGN KEY (`center_id`) REFERENCES `centers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ad_design_requests_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ad_design_requests_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ad_design_requests_published_by_foreign` FOREIGN KEY (`published_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ad_design_requests_refer_to_designer_id_foreign` FOREIGN KEY (`refer_to_designer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ad_design_requests_refer_to_pm2_id_foreign` FOREIGN KEY (`refer_to_pm2_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ad_design_requests_refer_to_publisher_id_foreign` FOREIGN KEY (`refer_to_publisher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ad_design_requests_refer_to_rowaduna_id_foreign` FOREIGN KEY (`refer_to_rowaduna_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `annex_document_blocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `annex_document_blocks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `document_id` bigint(20) unsigned NOT NULL,
  `block_key` varchar(255) NOT NULL,
  `page_number` int(10) unsigned NOT NULL DEFAULT 1,
  `json_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`json_value`)),
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `locked` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `annex_document_blocks_document_id_block_key_unique` (`document_id`,`block_key`),
  KEY `annex_document_blocks_updated_by_foreign` (`updated_by`),
  CONSTRAINT `annex_document_blocks_document_id_foreign` FOREIGN KEY (`document_id`) REFERENCES `annex_documents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `annex_document_blocks_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `annex_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `annex_documents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `template_id` bigint(20) unsigned NOT NULL,
  `template_version` int(10) unsigned NOT NULL DEFAULT 1,
  `title` varchar(255) DEFAULT NULL,
  `project_id` bigint(20) unsigned DEFAULT NULL,
  `center_id` bigint(20) unsigned DEFAULT NULL,
  `period` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `page_count` int(10) unsigned NOT NULL DEFAULT 1,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `cover_path` varchar(255) DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `assigned_to` bigint(20) unsigned DEFAULT NULL,
  `signed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `annex_documents_template_id_foreign` (`template_id`),
  KEY `annex_documents_project_id_foreign` (`project_id`),
  KEY `annex_documents_center_id_foreign` (`center_id`),
  KEY `annex_documents_created_by_foreign` (`created_by`),
  KEY `annex_documents_assigned_to_foreign` (`assigned_to`),
  CONSTRAINT `annex_documents_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `annex_documents_center_id_foreign` FOREIGN KEY (`center_id`) REFERENCES `centers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `annex_documents_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `annex_documents_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `annex_documents_template_id_foreign` FOREIGN KEY (`template_id`) REFERENCES `annex_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `annex_signoffs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `annex_signoffs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `document_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `role_label` varchar(255) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `annex_signoffs_document_id_foreign` (`document_id`),
  KEY `annex_signoffs_user_id_foreign` (`user_id`),
  CONSTRAINT `annex_signoffs_document_id_foreign` FOREIGN KEY (`document_id`) REFERENCES `annex_documents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `annex_signoffs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `annex_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `annex_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `title_ar` varchar(255) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `version` int(10) unsigned NOT NULL DEFAULT 1,
  `default_page_count` int(10) unsigned NOT NULL DEFAULT 1,
  `json_definition` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`json_definition`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `annex_templates_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `attendance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attendance` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) unsigned NOT NULL,
  `date` date NOT NULL,
  `status` enum('present','absent','excused') NOT NULL,
  `note` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attendance_student_id_date_unique` (`student_id`,`date`),
  KEY `attendance_created_by_foreign` (`created_by`),
  KEY `attendance_date_index` (`date`),
  KEY `attendance_status_index` (`status`),
  CONSTRAINT `attendance_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `attendance_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `audit_changes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_changes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `audit_log_id` bigint(20) unsigned NOT NULL,
  `field` varchar(191) NOT NULL,
  `label` varchar(191) DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `value_type` varchar(20) NOT NULL DEFAULT 'string',
  `is_masked` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `audit_changes_audit_log_id_foreign` (`audit_log_id`),
  KEY `audit_changes_field_index` (`field`),
  CONSTRAINT `audit_changes_audit_log_id_foreign` FOREIGN KEY (`audit_log_id`) REFERENCES `audit_logs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `request_id` char(36) DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `user_type` varchar(20) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `model` varchar(191) NOT NULL,
  `model_name` varchar(191) NOT NULL,
  `model_id` bigint(20) unsigned DEFAULT NULL,
  `event` varchar(20) NOT NULL,
  `description` text DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `hash` char(64) NOT NULL,
  `prev_hash` char(64) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `audit_logs_model_model_id_index` (`model`,`model_id`),
  KEY `audit_logs_model_name_index` (`model_name`),
  KEY `audit_logs_user_id_index` (`user_id`),
  KEY `audit_logs_event_index` (`event`),
  KEY `audit_logs_request_id_index` (`request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `center_project`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `center_project` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `center_id` bigint(20) unsigned NOT NULL,
  `project_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `center_project_center_id_project_id_unique` (`center_id`,`project_id`),
  KEY `center_project_project_id_foreign` (`project_id`),
  CONSTRAINT `center_project_center_id_foreign` FOREIGN KEY (`center_id`) REFERENCES `centers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `center_project_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `centers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `centers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `certificate_designs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `certificate_designs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `course_id` bigint(20) unsigned DEFAULT NULL,
  `template_image` varchar(255) DEFAULT NULL,
  `fields_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`fields_config`)),
  `signatures_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`signatures_config`)),
  `year` int(11) NOT NULL,
  `font_family` varchar(100) DEFAULT NULL,
  `start_number` int(11) NOT NULL DEFAULT 1,
  `current_number` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `certificate_designs_course_id_foreign` (`course_id`),
  CONSTRAINT `certificate_designs_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `certificate_number_sequence`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `certificate_number_sequence` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `year` int(11) NOT NULL,
  `last_number` bigint(20) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `certificate_number_sequence_year_unique` (`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `certificate_signatory_sets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `certificate_signatory_sets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `course_id` bigint(20) unsigned DEFAULT NULL,
  `period_id` bigint(20) unsigned DEFAULT NULL,
  `center_id` bigint(20) unsigned DEFAULT NULL,
  `instructor_signer_id` bigint(20) unsigned DEFAULT NULL,
  `center_manager_signer_id` bigint(20) unsigned DEFAULT NULL,
  `project_manager_signer_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `certificate_signatory_sets_course_id_foreign` (`course_id`),
  KEY `certificate_signatory_sets_period_id_foreign` (`period_id`),
  KEY `certificate_signatory_sets_center_id_foreign` (`center_id`),
  KEY `certificate_signatory_sets_instructor_signer_id_foreign` (`instructor_signer_id`),
  KEY `certificate_signatory_sets_center_manager_signer_id_foreign` (`center_manager_signer_id`),
  KEY `certificate_signatory_sets_project_manager_signer_id_foreign` (`project_manager_signer_id`),
  CONSTRAINT `certificate_signatory_sets_center_id_foreign` FOREIGN KEY (`center_id`) REFERENCES `centers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `certificate_signatory_sets_center_manager_signer_id_foreign` FOREIGN KEY (`center_manager_signer_id`) REFERENCES `certificate_signers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `certificate_signatory_sets_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `certificate_signatory_sets_instructor_signer_id_foreign` FOREIGN KEY (`instructor_signer_id`) REFERENCES `certificate_signers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `certificate_signatory_sets_period_id_foreign` FOREIGN KEY (`period_id`) REFERENCES `periods` (`id`) ON DELETE SET NULL,
  CONSTRAINT `certificate_signatory_sets_project_manager_signer_id_foreign` FOREIGN KEY (`project_manager_signer_id`) REFERENCES `certificate_signers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `certificate_signers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `certificate_signers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(60) DEFAULT NULL,
  `name_ar` varchar(200) NOT NULL,
  `role` varchar(50) NOT NULL,
  `signature_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `certificate_signers_code_unique` (`code`),
  KEY `certificate_signers_role_index` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `certificates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `certificates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `certificate_number` varchar(15) NOT NULL,
  `design_id` bigint(20) unsigned DEFAULT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `enrollment_id` bigint(20) unsigned DEFAULT NULL,
  `signatory_set_id` bigint(20) unsigned DEFAULT NULL,
  `barcode_hash` varchar(64) NOT NULL,
  `issue_date` date NOT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `verified_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `certificates_certificate_number_unique` (`certificate_number`),
  UNIQUE KEY `certificates_barcode_hash_unique` (`barcode_hash`),
  KEY `certificates_design_id_foreign` (`design_id`),
  KEY `certificates_student_id_foreign` (`student_id`),
  KEY `certificates_enrollment_id_foreign` (`enrollment_id`),
  KEY `certificates_signatory_set_id_foreign` (`signatory_set_id`),
  CONSTRAINT `certificates_design_id_foreign` FOREIGN KEY (`design_id`) REFERENCES `certificate_designs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `certificates_enrollment_id_foreign` FOREIGN KEY (`enrollment_id`) REFERENCES `student_enrollments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `certificates_signatory_set_id_foreign` FOREIGN KEY (`signatory_set_id`) REFERENCES `certificate_signatory_sets` (`id`) ON DELETE SET NULL,
  CONSTRAINT `certificates_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cohorts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cohorts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `manager_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(200) NOT NULL,
  `shift` varchar(50) DEFAULT NULL,
  `code` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cohorts_manager_id_foreign` (`manager_id`),
  KEY `cohorts_project_id_index` (`project_id`),
  CONSTRAINT `cohorts_manager_id_foreign` FOREIGN KEY (`manager_id`) REFERENCES `hr_employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cohorts_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `course_offerings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `course_offerings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `course_id` bigint(20) unsigned NOT NULL,
  `period_id` bigint(20) unsigned NOT NULL,
  `instructor_id` bigint(20) unsigned DEFAULT NULL,
  `name_ar` varchar(200) DEFAULT NULL,
  `session_time` varchar(100) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `capacity` int(11) DEFAULT NULL,
  `status` enum('planned','active','completed','cancelled') NOT NULL DEFAULT 'planned',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `course_offerings_course_id_index` (`course_id`),
  KEY `course_offerings_period_id_index` (`period_id`),
  KEY `course_offerings_instructor_id_index` (`instructor_id`),
  CONSTRAINT `course_offerings_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `course_offerings_instructor_id_foreign` FOREIGN KEY (`instructor_id`) REFERENCES `hr_employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `course_offerings_period_id_foreign` FOREIGN KEY (`period_id`) REFERENCES `periods` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `course_period`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `course_period` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `course_id` bigint(20) unsigned NOT NULL,
  `period_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `course_period_course_id_period_id_unique` (`course_id`,`period_id`),
  KEY `course_period_period_id_foreign` (`period_id`),
  CONSTRAINT `course_period_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `course_period_period_id_foreign` FOREIGN KEY (`period_id`) REFERENCES `periods` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `courses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `courses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `name_ar` varchar(200) NOT NULL,
  `name_en` varchar(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `duration` int(11) DEFAULT NULL COMMENT 'المدة بالأيام',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `courses_project_id_foreign` (`project_id`),
  CONSTRAINT `courses_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `departments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name_ar` varchar(255) NOT NULL,
  `name_en` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `event_cards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `event_cards` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `project_id` bigint(20) unsigned DEFAULT NULL,
  `center_id` bigint(20) unsigned DEFAULT NULL,
  `event_date` date DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `organizer` varchar(255) DEFAULT NULL,
  `presenter` varchar(255) DEFAULT NULL,
  `expected_attendance` int(10) unsigned DEFAULT NULL,
  `objectives` text DEFAULT NULL,
  `schedule_place` varchar(255) DEFAULT NULL,
  `schedule_date` date DEFAULT NULL,
  `schedule_time` varchar(255) DEFAULT NULL,
  `tasks_projects` text DEFAULT NULL,
  `tasks_operations` text DEFAULT NULL,
  `tasks_mel` text DEFAULT NULL,
  `tasks_grants` text DEFAULT NULL,
  `content_items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`content_items`)),
  `logistics_items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`logistics_items`)),
  `purchases_items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`purchases_items`)),
  `media_items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`media_items`)),
  `hr_notes` text DEFAULT NULL,
  `transport_items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`transport_items`)),
  `budget_items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`budget_items`)),
  `budget_total` decimal(14,2) DEFAULT NULL,
  `post_evaluation` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'review',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `referred_user_id` bigint(20) unsigned DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `finalized_by` bigint(20) unsigned DEFAULT NULL,
  `finalized_at` timestamp NULL DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `event_cards_project_id_foreign` (`project_id`),
  KEY `event_cards_center_id_foreign` (`center_id`),
  KEY `event_cards_created_by_foreign` (`created_by`),
  KEY `event_cards_referred_user_id_foreign` (`referred_user_id`),
  KEY `event_cards_approved_by_foreign` (`approved_by`),
  KEY `event_cards_finalized_by_foreign` (`finalized_by`),
  CONSTRAINT `event_cards_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `event_cards_center_id_foreign` FOREIGN KEY (`center_id`) REFERENCES `centers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `event_cards_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `event_cards_finalized_by_foreign` FOREIGN KEY (`finalized_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `event_cards_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `event_cards_referred_user_id_foreign` FOREIGN KEY (`referred_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `group_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `group_user` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `center_id` bigint(20) unsigned DEFAULT NULL,
  `project_id` bigint(20) unsigned DEFAULT NULL,
  `cohort_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `group_user_group_id_user_id_unique` (`group_id`,`user_id`),
  KEY `group_user_user_id_foreign` (`user_id`),
  KEY `group_user_center_id_foreign` (`center_id`),
  KEY `group_user_project_id_foreign` (`project_id`),
  KEY `group_user_cohort_id_foreign` (`cohort_id`),
  CONSTRAINT `group_user_center_id_foreign` FOREIGN KEY (`center_id`) REFERENCES `centers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `group_user_cohort_id_foreign` FOREIGN KEY (`cohort_id`) REFERENCES `cohorts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `group_user_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `group_user_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `group_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `groups` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `kind` varchar(10) NOT NULL DEFAULT 'group',
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `groups_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hr_contracts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_contracts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `contract_type` varchar(50) DEFAULT NULL,
  `job_position_id` bigint(20) unsigned DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `contract_start` date DEFAULT NULL,
  `contract_end` date DEFAULT NULL,
  `leave_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hr_contracts_employee_id_foreign` (`employee_id`),
  KEY `hr_contracts_job_position_id_foreign` (`job_position_id`),
  CONSTRAINT `hr_contracts_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `hr_contracts_job_position_id_foreign` FOREIGN KEY (`job_position_id`) REFERENCES `hr_job_positions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hr_employee_attendances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_employee_attendances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `date` date NOT NULL,
  `status` enum('present','absent','excused') NOT NULL DEFAULT 'present',
  `leave_type_id` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hr_employee_attendances_employee_id_date_unique` (`employee_id`,`date`),
  KEY `hr_employee_attendances_leave_type_id_foreign` (`leave_type_id`),
  KEY `hr_employee_attendances_created_by_foreign` (`created_by`),
  CONSTRAINT `hr_employee_attendances_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `hr_employee_attendances_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `hr_employee_attendances_leave_type_id_foreign` FOREIGN KEY (`leave_type_id`) REFERENCES `hr_leave_types` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hr_employee_contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_employee_contacts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `type` varchar(50) NOT NULL,
  `value` varchar(200) NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hr_employee_contacts_employee_id_foreign` (`employee_id`),
  CONSTRAINT `hr_employee_contacts_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hr_employee_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_employee_documents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `document_type` varchar(50) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hr_employee_documents_employee_id_foreign` (`employee_id`),
  CONSTRAINT `hr_employee_documents_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hr_employee_educations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_employee_educations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `qualification` varchar(200) NOT NULL,
  `specialization` varchar(200) DEFAULT NULL,
  `university` varchar(200) DEFAULT NULL,
  `grade` varchar(50) DEFAULT NULL,
  `graduation_year` year(4) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hr_employee_educations_employee_id_foreign` (`employee_id`),
  CONSTRAINT `hr_employee_educations_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hr_employee_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_employee_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `note` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hr_employee_notes_employee_id_foreign` (`employee_id`),
  KEY `hr_employee_notes_user_id_foreign` (`user_id`),
  CONSTRAINT `hr_employee_notes_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `hr_employee_notes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hr_employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_employees` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `employee_code` varchar(20) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `id_number` varchar(50) DEFAULT NULL,
  `first_name_ar` varchar(100) NOT NULL,
  `last_name_ar` varchar(100) NOT NULL,
  `first_name_en` varchar(100) DEFAULT NULL,
  `last_name_en` varchar(100) DEFAULT NULL,
  `father_name_ar` varchar(100) DEFAULT NULL,
  `father_name_en` varchar(100) DEFAULT NULL,
  `mother_name_ar` varchar(100) DEFAULT NULL,
  `mother_name_en` varchar(100) DEFAULT NULL,
  `gender` enum('male','female') NOT NULL,
  `marital_status` enum('single','married','divorced','widowed') DEFAULT NULL,
  `children_count` int(11) NOT NULL DEFAULT 0,
  `birth_date` date DEFAULT NULL,
  `birth_place` varchar(100) DEFAULT NULL,
  `nationality` varchar(100) DEFAULT NULL,
  `center_id` bigint(20) unsigned DEFAULT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `project_id` bigint(20) unsigned DEFAULT NULL,
  `cohort_id` bigint(20) unsigned DEFAULT NULL,
  `has_photo` tinyint(1) NOT NULL DEFAULT 0,
  `has_cv` tinyint(1) NOT NULL DEFAULT 0,
  `has_id_copy` tinyint(1) NOT NULL DEFAULT 0,
  `has_qualification` tinyint(1) NOT NULL DEFAULT 0,
  `has_experience_certs` tinyint(1) NOT NULL DEFAULT 0,
  `has_offer_letter` tinyint(1) NOT NULL DEFAULT 0,
  `has_contract_doc` tinyint(1) NOT NULL DEFAULT 0,
  `has_employee_data` tinyint(1) NOT NULL DEFAULT 0,
  `has_job_description` tinyint(1) NOT NULL DEFAULT 0,
  `has_signature_movements` tinyint(1) NOT NULL DEFAULT 0,
  `has_security_audit` tinyint(1) NOT NULL DEFAULT 0,
  `has_reference_audit` tinyint(1) NOT NULL DEFAULT 0,
  `has_code_of_conduct` tinyint(1) NOT NULL DEFAULT 0,
  `has_clearance` tinyint(1) NOT NULL DEFAULT 0,
  `has_receipt` tinyint(1) NOT NULL DEFAULT 0,
  `has_resignation` tinyint(1) NOT NULL DEFAULT 0,
  `has_verbal_warning_doc` tinyint(1) NOT NULL DEFAULT 0,
  `has_written_warning_doc` tinyint(1) NOT NULL DEFAULT 0,
  `has_termination_warning_doc` tinyint(1) NOT NULL DEFAULT 0,
  `has_termination_doc` tinyint(1) NOT NULL DEFAULT 0,
  `has_blacklist_doc` tinyint(1) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hr_employees_employee_code_unique` (`employee_code`),
  KEY `hr_employees_user_id_foreign` (`user_id`),
  KEY `hr_employees_center_id_foreign` (`center_id`),
  KEY `hr_employees_department_id_foreign` (`department_id`),
  KEY `hr_employees_project_id_foreign` (`project_id`),
  KEY `hr_employees_cohort_id_foreign` (`cohort_id`),
  CONSTRAINT `hr_employees_center_id_foreign` FOREIGN KEY (`center_id`) REFERENCES `centers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `hr_employees_cohort_id_foreign` FOREIGN KEY (`cohort_id`) REFERENCES `cohorts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `hr_employees_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `hr_employees_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `hr_employees_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hr_job_positions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_job_positions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title_ar` varchar(255) NOT NULL,
  `title_en` varchar(255) DEFAULT NULL,
  `description_ar` text DEFAULT NULL,
  `description_en` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hr_leave_balances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_leave_balances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `leave_type_id` bigint(20) unsigned NOT NULL,
  `year` int(11) NOT NULL,
  `total_days` int(11) NOT NULL DEFAULT 0,
  `used_days` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hr_leave_balances_employee_id_leave_type_id_year_unique` (`employee_id`,`leave_type_id`,`year`),
  KEY `hr_leave_balances_leave_type_id_foreign` (`leave_type_id`),
  CONSTRAINT `hr_leave_balances_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `hr_leave_balances_leave_type_id_foreign` FOREIGN KEY (`leave_type_id`) REFERENCES `hr_leave_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hr_leave_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_leave_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `leave_type_id` bigint(20) unsigned NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `days_count` int(11) NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hr_leave_requests_employee_id_foreign` (`employee_id`),
  KEY `hr_leave_requests_leave_type_id_foreign` (`leave_type_id`),
  KEY `hr_leave_requests_approved_by_foreign` (`approved_by`),
  CONSTRAINT `hr_leave_requests_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `hr_leave_requests_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `hr_leave_requests_leave_type_id_foreign` FOREIGN KEY (`leave_type_id`) REFERENCES `hr_leave_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hr_leave_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_leave_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name_ar` varchar(255) NOT NULL,
  `annual_days` int(11) NOT NULL DEFAULT 0,
  `requires_approval` tinyint(1) NOT NULL DEFAULT 1,
  `approver_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`approver_ids`)),
  `color` varchar(255) NOT NULL DEFAULT '#0d6efd',
  `icon` varchar(255) NOT NULL DEFAULT 'bi-calendar',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hr_salaries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_salaries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'SYP',
  `salary_unit` varchar(50) DEFAULT NULL,
  `base_salary` decimal(12,2) NOT NULL DEFAULT 0.00,
  `study_allowance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `marriage_allowance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `experience_allowance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `transport_allowance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `food_allowance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `housing_allowance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `mobile_allowance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `risk_allowance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `overtime_rate` decimal(10,2) NOT NULL DEFAULT 0.00,
  `deduction` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_salary` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hr_salaries_employee_id_foreign` (`employee_id`),
  CONSTRAINT `hr_salaries_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hr_warnings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_warnings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `date` date NOT NULL,
  `reason` text NOT NULL,
  `level` enum('verbal','written','termination') NOT NULL,
  `is_folded` tinyint(1) NOT NULL DEFAULT 0,
  `fold_reason` text DEFAULT NULL,
  `folded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hr_warnings_employee_id_foreign` (`employee_id`),
  CONSTRAINT `hr_warnings_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hr_work_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_work_schedules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `day_of_week` tinyint(4) NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `is_day_off` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hr_work_schedules_employee_id_day_of_week_unique` (`employee_id`,`day_of_week`),
  CONSTRAINT `hr_work_schedules_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `level_subject_instructors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `level_subject_instructors` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `academic_level_id` bigint(20) unsigned NOT NULL,
  `subject_id` bigint(20) unsigned NOT NULL,
  `instructor_id` bigint(20) unsigned NOT NULL,
  `is_main` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_level_subject_instructor` (`academic_level_id`,`subject_id`,`instructor_id`),
  KEY `level_subject_instructors_subject_id_foreign` (`subject_id`),
  KEY `level_subject_instructors_instructor_id_foreign` (`instructor_id`),
  CONSTRAINT `level_subject_instructors_academic_level_id_foreign` FOREIGN KEY (`academic_level_id`) REFERENCES `academic_levels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `level_subject_instructors_instructor_id_foreign` FOREIGN KEY (`instructor_id`) REFERENCES `hr_employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `level_subject_instructors_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `level_subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `level_subjects` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `academic_level_id` bigint(20) unsigned NOT NULL,
  `subject_id` bigint(20) unsigned NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_level_subject` (`academic_level_id`,`subject_id`),
  KEY `level_subjects_subject_id_foreign` (`subject_id`),
  CONSTRAINT `level_subjects_academic_level_id_foreign` FOREIGN KEY (`academic_level_id`) REFERENCES `academic_levels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `level_subjects_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `logistics_approval_rule_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logistics_approval_rule_user` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `rule_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `logistics_approval_rule_user_rule_id_foreign` (`rule_id`),
  KEY `logistics_approval_rule_user_user_id_foreign` (`user_id`),
  CONSTRAINT `logistics_approval_rule_user_rule_id_foreign` FOREIGN KEY (`rule_id`) REFERENCES `logistics_approval_rules` (`id`) ON DELETE CASCADE,
  CONSTRAINT `logistics_approval_rule_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `logistics_approval_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logistics_approval_rules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `min_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `max_amount` decimal(12,2) DEFAULT NULL,
  `required_approvals` int(11) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `logistics_assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logistics_assets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `asset_code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `center_id` bigint(20) unsigned NOT NULL,
  `project_id` bigint(20) unsigned DEFAULT NULL,
  `room_number` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL,
  `notes` text DEFAULT NULL,
  `recipient_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `logistics_assets_asset_code_unique` (`asset_code`),
  KEY `logistics_assets_center_id_foreign` (`center_id`),
  KEY `logistics_assets_project_id_foreign` (`project_id`),
  KEY `logistics_assets_recipient_id_foreign` (`recipient_id`),
  CONSTRAINT `logistics_assets_center_id_foreign` FOREIGN KEY (`center_id`) REFERENCES `centers` (`id`),
  CONSTRAINT `logistics_assets_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`),
  CONSTRAINT `logistics_assets_recipient_id_foreign` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `logistics_deleted_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logistics_deleted_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `warehouse_id` bigint(20) unsigned DEFAULT NULL,
  `item_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `unit` varchar(255) NOT NULL,
  `delete_reason` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `logistics_deleted_items_warehouse_id_foreign` (`warehouse_id`),
  CONSTRAINT `logistics_deleted_items_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `logistics_warehouses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `logistics_purchase_request_approvals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logistics_purchase_request_approvals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_request_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `decided_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `logistics_purchase_request_approvals_purchase_request_id_foreign` (`purchase_request_id`),
  KEY `logistics_purchase_request_approvals_user_id_foreign` (`user_id`),
  CONSTRAINT `logistics_purchase_request_approvals_purchase_request_id_foreign` FOREIGN KEY (`purchase_request_id`) REFERENCES `logistics_purchase_requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `logistics_purchase_request_approvals_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `logistics_purchase_request_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logistics_purchase_request_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_request_id` bigint(20) unsigned NOT NULL,
  `description` text NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit` varchar(255) NOT NULL,
  `currency` varchar(4) NOT NULL DEFAULT 'USD',
  `unit_price` decimal(12,2) NOT NULL,
  `total_price` decimal(12,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `budget_line` varchar(60) DEFAULT NULL,
  `executed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `executed_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `logistics_purchase_request_items_purchase_request_id_foreign` (`purchase_request_id`),
  KEY `logistics_purchase_request_items_executed_by_foreign` (`executed_by`),
  CONSTRAINT `logistics_purchase_request_items_executed_by_foreign` FOREIGN KEY (`executed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `logistics_purchase_request_items_purchase_request_id_foreign` FOREIGN KEY (`purchase_request_id`) REFERENCES `logistics_purchase_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `logistics_purchase_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logistics_purchase_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `request_number` varchar(255) NOT NULL,
  `request_type` varchar(20) NOT NULL DEFAULT 'purchase',
  `pr_date` date DEFAULT NULL,
  `required_date` date DEFAULT NULL,
  `management_unit` varchar(255) DEFAULT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `specifications` text DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `unit` varchar(255) DEFAULT NULL,
  `expected_unit_price` decimal(12,2) DEFAULT NULL,
  `expected_total_price` decimal(12,2) NOT NULL,
  `center_id` bigint(20) unsigned DEFAULT NULL,
  `project_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `signature_path` varchar(255) DEFAULT NULL,
  `budget_number` varchar(255) DEFAULT NULL,
  `locked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `locked_by` bigint(20) unsigned DEFAULT NULL,
  `refer_to_logistics_id` bigint(20) unsigned DEFAULT NULL,
  `refer_to_direct_manager_id` bigint(20) unsigned DEFAULT NULL,
  `refer_to_pm2_id` bigint(20) unsigned DEFAULT NULL,
  `refer_to_finance_id` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `refer_to_executive_id` bigint(20) unsigned DEFAULT NULL,
  `finance_at` timestamp NULL DEFAULT NULL,
  `refer_to_approver1_id` bigint(20) unsigned DEFAULT NULL,
  `refer_to_approver2_id` bigint(20) unsigned DEFAULT NULL,
  `refer_to_approver3_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `logistics_purchase_requests_request_number_unique` (`request_number`),
  KEY `logistics_purchase_requests_user_id_foreign` (`user_id`),
  KEY `logistics_purchase_requests_center_id_foreign` (`center_id`),
  KEY `logistics_purchase_requests_project_id_foreign` (`project_id`),
  KEY `logistics_purchase_requests_locked_by_foreign` (`locked_by`),
  KEY `logistics_purchase_requests_refer_to_logistics_id_foreign` (`refer_to_logistics_id`),
  KEY `logistics_purchase_requests_refer_to_direct_manager_id_foreign` (`refer_to_direct_manager_id`),
  KEY `logistics_purchase_requests_refer_to_pm2_id_foreign` (`refer_to_pm2_id`),
  KEY `logistics_purchase_requests_refer_to_finance_id_foreign` (`refer_to_finance_id`),
  KEY `logistics_purchase_requests_refer_to_executive_id_foreign` (`refer_to_executive_id`),
  KEY `logistics_purchase_requests_refer_to_approver1_id_foreign` (`refer_to_approver1_id`),
  KEY `logistics_purchase_requests_refer_to_approver2_id_foreign` (`refer_to_approver2_id`),
  KEY `logistics_purchase_requests_refer_to_approver3_id_foreign` (`refer_to_approver3_id`),
  KEY `logistics_purchase_requests_request_type_index` (`request_type`),
  CONSTRAINT `logistics_purchase_requests_center_id_foreign` FOREIGN KEY (`center_id`) REFERENCES `centers` (`id`),
  CONSTRAINT `logistics_purchase_requests_locked_by_foreign` FOREIGN KEY (`locked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `logistics_purchase_requests_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`),
  CONSTRAINT `logistics_purchase_requests_refer_to_approver1_id_foreign` FOREIGN KEY (`refer_to_approver1_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `logistics_purchase_requests_refer_to_approver2_id_foreign` FOREIGN KEY (`refer_to_approver2_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `logistics_purchase_requests_refer_to_approver3_id_foreign` FOREIGN KEY (`refer_to_approver3_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `logistics_purchase_requests_refer_to_direct_manager_id_foreign` FOREIGN KEY (`refer_to_direct_manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `logistics_purchase_requests_refer_to_executive_id_foreign` FOREIGN KEY (`refer_to_executive_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `logistics_purchase_requests_refer_to_finance_id_foreign` FOREIGN KEY (`refer_to_finance_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `logistics_purchase_requests_refer_to_logistics_id_foreign` FOREIGN KEY (`refer_to_logistics_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `logistics_purchase_requests_refer_to_pm2_id_foreign` FOREIGN KEY (`refer_to_pm2_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `logistics_purchase_requests_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `logistics_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logistics_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `logistics_settings_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `logistics_warehouse_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logistics_warehouse_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `warehouse_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `unit` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `logistics_warehouse_items_warehouse_id_foreign` (`warehouse_id`),
  CONSTRAINT `logistics_warehouse_items_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `logistics_warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `logistics_warehouses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logistics_warehouses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `center_id` bigint(20) unsigned NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `logistics_warehouses_center_id_foreign` (`center_id`),
  CONSTRAINT `logistics_warehouses_center_id_foreign` FOREIGN KEY (`center_id`) REFERENCES `centers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `media_plan_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `media_plan_comments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `media_plan_event_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `media_plan_comments_media_plan_event_id_foreign` (`media_plan_event_id`),
  KEY `media_plan_comments_user_id_foreign` (`user_id`),
  CONSTRAINT `media_plan_comments_media_plan_event_id_foreign` FOREIGN KEY (`media_plan_event_id`) REFERENCES `media_plan_events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `media_plan_comments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `media_plan_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `media_plan_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `media_plan_id` bigint(20) unsigned NOT NULL,
  `event_date` date NOT NULL,
  `office` varchar(255) DEFAULT NULL,
  `event_name` varchar(255) NOT NULL,
  `day` varchar(255) DEFAULT NULL,
  `event_time` time NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `responsible_user_id` bigint(20) unsigned DEFAULT NULL,
  `summary` text DEFAULT NULL,
  `coverage_type` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `coverage_status` varchar(255) NOT NULL DEFAULT 'pending',
  `execution_status` varchar(255) DEFAULT NULL,
  `execution_note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `execution_by` bigint(20) unsigned DEFAULT NULL,
  `execution_at` timestamp NULL DEFAULT NULL,
  `refer_to_reporter_id` bigint(20) unsigned DEFAULT NULL,
  `not_covered_reason` text DEFAULT NULL,
  `coverage_note` text DEFAULT NULL,
  `media_items_url` varchar(255) DEFAULT NULL,
  `publish_status` varchar(255) NOT NULL DEFAULT 'none',
  `refer_to_publisher_id` bigint(20) unsigned DEFAULT NULL,
  `preview_url` varchar(255) DEFAULT NULL,
  `refer_to_reviewer_id` bigint(20) unsigned DEFAULT NULL,
  `preview_feedback` text DEFAULT NULL,
  `publish_links` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`publish_links`)),
  `preview_reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `preview_reviewed_at` timestamp NULL DEFAULT NULL,
  `published_by` bigint(20) unsigned DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `media_plan_events_no_conflict` (`media_plan_id`,`event_date`,`event_time`),
  KEY `media_plan_events_responsible_user_id_foreign` (`responsible_user_id`),
  KEY `media_plan_events_execution_by_foreign` (`execution_by`),
  KEY `media_plan_events_refer_to_publisher_id_foreign` (`refer_to_publisher_id`),
  KEY `media_plan_events_refer_to_reviewer_id_foreign` (`refer_to_reviewer_id`),
  KEY `media_plan_events_preview_reviewed_by_foreign` (`preview_reviewed_by`),
  KEY `media_plan_events_published_by_foreign` (`published_by`),
  KEY `media_events_reporter_slot` (`refer_to_reporter_id`,`event_date`,`event_time`),
  CONSTRAINT `media_plan_events_execution_by_foreign` FOREIGN KEY (`execution_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `media_plan_events_media_plan_id_foreign` FOREIGN KEY (`media_plan_id`) REFERENCES `media_plans` (`id`) ON DELETE CASCADE,
  CONSTRAINT `media_plan_events_preview_reviewed_by_foreign` FOREIGN KEY (`preview_reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `media_plan_events_published_by_foreign` FOREIGN KEY (`published_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `media_plan_events_refer_to_publisher_id_foreign` FOREIGN KEY (`refer_to_publisher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `media_plan_events_refer_to_reporter_id_foreign` FOREIGN KEY (`refer_to_reporter_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `media_plan_events_refer_to_reviewer_id_foreign` FOREIGN KEY (`refer_to_reviewer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `media_plan_events_responsible_user_id_foreign` FOREIGN KEY (`responsible_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `media_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `media_plans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `month_date` date NOT NULL,
  `center_id` bigint(20) unsigned DEFAULT NULL,
  `project_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `note` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'review',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `refer_to_direct_manager_id` bigint(20) unsigned DEFAULT NULL,
  `refer_to_pm2_id` bigint(20) unsigned DEFAULT NULL,
  `refer_to_media_manager_id` bigint(20) unsigned DEFAULT NULL,
  `refer_to_media_officer_id` bigint(20) unsigned DEFAULT NULL,
  `locked_at` timestamp NULL DEFAULT NULL,
  `locked_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `refer_to_rowaduna_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `media_plans_center_id_foreign` (`center_id`),
  KEY `media_plans_project_id_foreign` (`project_id`),
  KEY `media_plans_created_by_foreign` (`created_by`),
  KEY `media_plans_refer_to_direct_manager_id_foreign` (`refer_to_direct_manager_id`),
  KEY `media_plans_refer_to_pm2_id_foreign` (`refer_to_pm2_id`),
  KEY `media_plans_refer_to_media_manager_id_foreign` (`refer_to_media_manager_id`),
  KEY `media_plans_refer_to_media_officer_id_foreign` (`refer_to_media_officer_id`),
  KEY `media_plans_locked_by_foreign` (`locked_by`),
  KEY `media_plans_refer_to_rowaduna_id_foreign` (`refer_to_rowaduna_id`),
  CONSTRAINT `media_plans_center_id_foreign` FOREIGN KEY (`center_id`) REFERENCES `centers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `media_plans_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `media_plans_locked_by_foreign` FOREIGN KEY (`locked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `media_plans_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `media_plans_refer_to_direct_manager_id_foreign` FOREIGN KEY (`refer_to_direct_manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `media_plans_refer_to_media_manager_id_foreign` FOREIGN KEY (`refer_to_media_manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `media_plans_refer_to_media_officer_id_foreign` FOREIGN KEY (`refer_to_media_officer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `media_plans_refer_to_pm2_id_foreign` FOREIGN KEY (`refer_to_pm2_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `media_plans_refer_to_rowaduna_id_foreign` FOREIGN KEY (`refer_to_rowaduna_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `monthly_report_blocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `monthly_report_blocks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `report_id` bigint(20) unsigned NOT NULL,
  `block_key` varchar(255) NOT NULL,
  `page_number` int(10) unsigned NOT NULL DEFAULT 1,
  `json_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`json_value`)),
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `locked` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `monthly_report_blocks_report_id_block_key_unique` (`report_id`,`block_key`),
  KEY `monthly_report_blocks_updated_by_foreign` (`updated_by`),
  CONSTRAINT `monthly_report_blocks_report_id_foreign` FOREIGN KEY (`report_id`) REFERENCES `monthly_reports` (`id`) ON DELETE CASCADE,
  CONSTRAINT `monthly_report_blocks_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `monthly_report_signoffs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `monthly_report_signoffs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `report_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `role_label` varchar(255) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `monthly_report_signoffs_report_id_foreign` (`report_id`),
  KEY `monthly_report_signoffs_user_id_foreign` (`user_id`),
  CONSTRAINT `monthly_report_signoffs_report_id_foreign` FOREIGN KEY (`report_id`) REFERENCES `monthly_reports` (`id`) ON DELETE CASCADE,
  CONSTRAINT `monthly_report_signoffs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `monthly_report_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `monthly_report_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `title_ar` varchar(255) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `version` int(10) unsigned NOT NULL DEFAULT 1,
  `default_page_count` int(10) unsigned NOT NULL DEFAULT 1,
  `json_definition` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`json_definition`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `monthly_report_templates_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `monthly_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `monthly_reports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `template_id` bigint(20) unsigned NOT NULL,
  `template_version` int(10) unsigned NOT NULL DEFAULT 1,
  `title` varchar(255) DEFAULT NULL,
  `project_id` bigint(20) unsigned DEFAULT NULL,
  `period` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `page_count` int(10) unsigned NOT NULL DEFAULT 1,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `cover_path` varchar(255) DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `assigned_to` bigint(20) unsigned DEFAULT NULL,
  `signed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `monthly_reports_template_id_foreign` (`template_id`),
  KEY `monthly_reports_project_id_foreign` (`project_id`),
  KEY `monthly_reports_created_by_foreign` (`created_by`),
  KEY `monthly_reports_assigned_to_foreign` (`assigned_to`),
  CONSTRAINT `monthly_reports_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `monthly_reports_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `monthly_reports_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `monthly_reports_template_id_foreign` FOREIGN KEY (`template_id`) REFERENCES `monthly_report_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `movement_plan_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `movement_plan_entries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `movement_plan_id` bigint(20) unsigned NOT NULL,
  `movement_date` date NOT NULL,
  `departure_time` time DEFAULT NULL,
  `return_time` time DEFAULT NULL,
  `from_location` varchar(255) DEFAULT NULL,
  `to_location` varchar(255) DEFAULT NULL,
  `purpose` text NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `movement_plan_entries_movement_plan_id_movement_date_index` (`movement_plan_id`,`movement_date`),
  CONSTRAINT `movement_plan_entries_movement_plan_id_foreign` FOREIGN KEY (`movement_plan_id`) REFERENCES `movement_plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `movement_plan_recipients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `movement_plan_recipients` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `movement_plan_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `role_label` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `movement_plan_recipients_movement_plan_id_foreign` (`movement_plan_id`),
  KEY `movement_plan_recipients_user_id_foreign` (`user_id`),
  CONSTRAINT `movement_plan_recipients_movement_plan_id_foreign` FOREIGN KEY (`movement_plan_id`) REFERENCES `movement_plans` (`id`) ON DELETE CASCADE,
  CONSTRAINT `movement_plan_recipients_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `movement_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `movement_plans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `request_number` varchar(255) NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `center_id` bigint(20) unsigned DEFAULT NULL,
  `project_id` bigint(20) unsigned DEFAULT NULL,
  `plan_month` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `refer_to_movement_officer_id` bigint(20) unsigned DEFAULT NULL,
  `assigned_by` bigint(20) unsigned DEFAULT NULL,
  `assigned_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'review',
  `reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `refer_to_pm2_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `movement_plans_request_number_unique` (`request_number`),
  KEY `movement_plans_created_by_foreign` (`created_by`),
  KEY `movement_plans_center_id_foreign` (`center_id`),
  KEY `movement_plans_project_id_foreign` (`project_id`),
  KEY `movement_plans_refer_to_movement_officer_id_foreign` (`refer_to_movement_officer_id`),
  KEY `movement_plans_assigned_by_foreign` (`assigned_by`),
  KEY `movement_plans_refer_to_pm2_id_foreign` (`refer_to_pm2_id`),
  CONSTRAINT `movement_plans_assigned_by_foreign` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `movement_plans_center_id_foreign` FOREIGN KEY (`center_id`) REFERENCES `centers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `movement_plans_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `movement_plans_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `movement_plans_refer_to_movement_officer_id_foreign` FOREIGN KEY (`refer_to_movement_officer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `movement_plans_refer_to_pm2_id_foreign` FOREIGN KEY (`refer_to_pm2_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `periods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `periods` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `name_ar` varchar(200) NOT NULL,
  `year` year(4) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `periods_project_id_foreign` (`project_id`),
  CONSTRAINT `periods_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `group_id` bigint(20) unsigned DEFAULT NULL,
  `model_names` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`model_names`)),
  `model_id` bigint(20) unsigned DEFAULT NULL,
  `center_id` bigint(20) unsigned DEFAULT NULL,
  `project_id` bigint(20) unsigned DEFAULT NULL,
  `cohort_id` bigint(20) unsigned DEFAULT NULL,
  `can_view` tinyint(1) NOT NULL DEFAULT 0,
  `can_create` tinyint(1) NOT NULL DEFAULT 0,
  `can_edit` tinyint(1) NOT NULL DEFAULT 0,
  `can_delete` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `permissions_user_id_foreign` (`user_id`),
  KEY `permissions_group_id_foreign` (`group_id`),
  KEY `permissions_center_id_foreign` (`center_id`),
  KEY `permissions_project_id_foreign` (`project_id`),
  KEY `permissions_cohort_id_foreign` (`cohort_id`),
  CONSTRAINT `permissions_center_id_foreign` FOREIGN KEY (`center_id`) REFERENCES `centers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `permissions_cohort_id_foreign` FOREIGN KEY (`cohort_id`) REFERENCES `cohorts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `permissions_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `permissions_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `permissions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `physio_patients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `physio_patients` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `center_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `gender` enum('male','female') NOT NULL,
  `birth_date` date DEFAULT NULL,
  `medical_history` text DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `is_transferred` tinyint(1) NOT NULL DEFAULT 0,
  `transferred_at` date DEFAULT NULL,
  `registration_date` date NOT NULL,
  `therapist_id` bigint(20) unsigned DEFAULT NULL,
  `room_id` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `physio_patients_center_id_foreign` (`center_id`),
  KEY `physio_patients_therapist_id_foreign` (`therapist_id`),
  KEY `physio_patients_room_id_foreign` (`room_id`),
  KEY `physio_patients_created_by_foreign` (`created_by`),
  CONSTRAINT `physio_patients_center_id_foreign` FOREIGN KEY (`center_id`) REFERENCES `centers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `physio_patients_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `physio_patients_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `physio_rooms` (`id`) ON DELETE SET NULL,
  CONSTRAINT `physio_patients_therapist_id_foreign` FOREIGN KEY (`therapist_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `physio_rooms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `physio_rooms` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `center_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `physio_rooms_center_id_foreign` (`center_id`),
  CONSTRAINT `physio_rooms_center_id_foreign` FOREIGN KEY (`center_id`) REFERENCES `centers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `physio_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `physio_sessions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `patient_id` bigint(20) unsigned NOT NULL,
  `session_date` date NOT NULL,
  `session_number` int(10) unsigned NOT NULL DEFAULT 1,
  `what_done` text NOT NULL,
  `therapist_id` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `physio_sessions_patient_id_foreign` (`patient_id`),
  KEY `physio_sessions_therapist_id_foreign` (`therapist_id`),
  KEY `physio_sessions_created_by_foreign` (`created_by`),
  CONSTRAINT `physio_sessions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `physio_sessions_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `physio_patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `physio_sessions_therapist_id_foreign` FOREIGN KEY (`therapist_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_activities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned DEFAULT NULL,
  `center_id` bigint(20) unsigned DEFAULT NULL,
  `responsible` varchar(255) DEFAULT NULL,
  `activity_date` date NOT NULL,
  `beneficiary` varchar(255) DEFAULT NULL,
  `male_count` int(10) unsigned NOT NULL DEFAULT 0,
  `female_count` int(10) unsigned NOT NULL DEFAULT 0,
  `progress` text DEFAULT NULL,
  `obstacles` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `project_activities_project_id_foreign` (`project_id`),
  KEY `project_activities_center_id_foreign` (`center_id`),
  KEY `project_activities_created_by_foreign` (`created_by`),
  CONSTRAINT `project_activities_center_id_foreign` FOREIGN KEY (`center_id`) REFERENCES `centers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `project_activities_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `project_activities_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_paths`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_paths` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_student`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_student` (
  `project_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`project_id`,`student_id`),
  KEY `project_student_student_id_foreign` (`student_id`),
  CONSTRAINT `project_student_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_student_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_tasks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `purpose` text DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `needs_media_coverage` tinyint(1) NOT NULL DEFAULT 0,
  `needs_costs` tinyint(1) NOT NULL DEFAULT 0,
  `costs_details` text DEFAULT NULL,
  `needs_equipment` tinyint(1) NOT NULL DEFAULT 0,
  `equipment_details` text DEFAULT NULL,
  `assigned_to` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `center_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `executed` tinyint(1) DEFAULT NULL,
  `not_executed_reason` text DEFAULT NULL,
  `has_delay` tinyint(1) DEFAULT NULL,
  `delay_reason` text DEFAULT NULL,
  `media_coverage_done` tinyint(1) DEFAULT NULL,
  `no_media_coverage_reason` text DEFAULT NULL,
  `execution_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `project_tasks_assigned_to_foreign` (`assigned_to`),
  KEY `project_tasks_created_by_foreign` (`created_by`),
  KEY `project_tasks_center_id_foreign` (`center_id`),
  CONSTRAINT `project_tasks_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`),
  CONSTRAINT `project_tasks_center_id_foreign` FOREIGN KEY (`center_id`) REFERENCES `centers` (`id`),
  CONSTRAINT `project_tasks_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `projects` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `path_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `code` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `projects_path_id_foreign` (`path_id`),
  CONSTRAINT `projects_path_id_foreign` FOREIGN KEY (`path_id`) REFERENCES `project_paths` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `purchase_request_signatures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_request_signatures` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_request_id` bigint(20) unsigned NOT NULL,
  `role` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `position` varchar(255) DEFAULT NULL,
  `signed_at` timestamp NULL DEFAULT NULL,
  `signature_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `purchase_request_signatures_purchase_request_id_role_unique` (`purchase_request_id`,`role`),
  KEY `purchase_request_signatures_user_id_foreign` (`user_id`),
  CONSTRAINT `purchase_request_signatures_purchase_request_id_foreign` FOREIGN KEY (`purchase_request_id`) REFERENCES `logistics_purchase_requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_request_signatures_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `referrals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `referrals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `workable_type` varchar(255) NOT NULL,
  `workable_id` bigint(20) unsigned NOT NULL,
  `from_user_id` bigint(20) unsigned DEFAULT NULL,
  `to_user_id` bigint(20) unsigned DEFAULT NULL,
  `step` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `referrals_workable_type_workable_id_index` (`workable_type`,`workable_id`),
  KEY `referrals_from_user_id_foreign` (`from_user_id`),
  KEY `referrals_workable_type_workable_id_status_index` (`workable_type`,`workable_id`,`status`),
  KEY `referrals_to_user_id_index` (`to_user_id`),
  CONSTRAINT `referrals_from_user_id_foreign` FOREIGN KEY (`from_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `referrals_to_user_id_foreign` FOREIGN KEY (`to_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `student_enrollments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_enrollments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) unsigned NOT NULL,
  `course_id` bigint(20) unsigned NOT NULL,
  `period_id` bigint(20) unsigned NOT NULL,
  `enrollment_date` date NOT NULL,
  `status` enum('enrolled','passed','failed','dropped') NOT NULL DEFAULT 'enrolled',
  `grade` decimal(5,2) DEFAULT NULL,
  `is_certificate_eligible` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_enrollments_student_id_foreign` (`student_id`),
  KEY `student_enrollments_course_id_foreign` (`course_id`),
  KEY `student_enrollments_period_id_foreign` (`period_id`),
  KEY `student_enrollments_enrollment_date_index` (`enrollment_date`),
  CONSTRAINT `student_enrollments_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_enrollments_period_id_foreign` FOREIGN KEY (`period_id`) REFERENCES `periods` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_enrollments_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `student_subject_grades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_subject_grades` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_enrollment_id` bigint(20) unsigned NOT NULL,
  `subject_id` bigint(20) unsigned NOT NULL,
  `grade` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_student_enrollment_subject` (`student_enrollment_id`,`subject_id`),
  KEY `student_subject_grades_subject_id_foreign` (`subject_id`),
  CONSTRAINT `student_subject_grades_student_enrollment_id_foreign` FOREIGN KEY (`student_enrollment_id`) REFERENCES `student_enrollments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_subject_grades_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `students` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_code` varchar(20) NOT NULL,
  `identity_type` varchar(50) DEFAULT NULL,
  `identity_number` varchar(50) DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `first_name_ar` varchar(100) NOT NULL,
  `last_name_ar` varchar(100) NOT NULL,
  `first_name_en` varchar(100) DEFAULT NULL,
  `last_name_en` varchar(100) DEFAULT NULL,
  `father_name` varchar(100) DEFAULT NULL,
  `mother_name` varchar(100) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `birth_place` varchar(100) DEFAULT NULL,
  `gender` enum('male','female') NOT NULL,
  `nationality` varchar(100) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `center_id` bigint(20) unsigned DEFAULT NULL,
  `project_id` bigint(20) unsigned DEFAULT NULL,
  `cohort_id` bigint(20) unsigned DEFAULT NULL,
  `status` enum('active','inactive','graduated','suspended') NOT NULL DEFAULT 'active',
  `enrollment_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `students_student_code_unique` (`student_code`),
  KEY `students_user_id_foreign` (`user_id`),
  KEY `students_center_id_foreign` (`center_id`),
  KEY `students_project_id_foreign` (`project_id`),
  KEY `students_status_index` (`status`),
  KEY `students_gender_index` (`gender`),
  KEY `students_cohort_id_foreign` (`cohort_id`),
  CONSTRAINT `students_center_id_foreign` FOREIGN KEY (`center_id`) REFERENCES `centers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `students_cohort_id_foreign` FOREIGN KEY (`cohort_id`) REFERENCES `cohorts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `students_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `students_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `subject_exams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subject_exams` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `subject_id` bigint(20) unsigned NOT NULL,
  `name_ar` varchar(200) NOT NULL,
  `type` varchar(50) DEFAULT NULL COMMENT 'pre/post/quiz/final/other — قبلي/بعدي/دوري/نهائي/أخرى',
  `max_score` decimal(5,2) DEFAULT NULL COMMENT 'العلامة العليا للامتحان',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subject_exams_subject_id_index` (`subject_id`),
  CONSTRAINT `subject_exams_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subjects` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `course_id` bigint(20) unsigned DEFAULT NULL,
  `name_ar` varchar(200) NOT NULL,
  `name_en` varchar(200) DEFAULT NULL,
  `hours` decimal(5,2) DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subjects_course_id_index` (`course_id`),
  CONSTRAINT `subjects_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tech_equipment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tech_equipment` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` varchar(255) DEFAULT NULL,
  `serial_number` varchar(255) DEFAULT NULL,
  `condition` enum('a','b','c','d','e') NOT NULL DEFAULT 'c',
  `room` varchar(255) DEFAULT NULL,
  `center_id` bigint(20) unsigned NOT NULL,
  `project_id` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tech_equipment_center_id_foreign` (`center_id`),
  KEY `tech_equipment_project_id_foreign` (`project_id`),
  KEY `tech_equipment_type_index` (`type`),
  KEY `tech_equipment_condition_index` (`condition`),
  CONSTRAINT `tech_equipment_center_id_foreign` FOREIGN KEY (`center_id`) REFERENCES `centers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tech_equipment_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tech_issues`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tech_issues` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `center_id` bigint(20) unsigned NOT NULL,
  `project_id` bigint(20) unsigned DEFAULT NULL,
  `reported_by` bigint(20) unsigned NOT NULL,
  `assigned_to` bigint(20) unsigned DEFAULT NULL,
  `status` enum('open','in_progress','completed','blocked') NOT NULL DEFAULT 'open',
  `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `admin_response` text DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tech_issues_project_id_foreign` (`project_id`),
  KEY `tech_issues_reported_by_foreign` (`reported_by`),
  KEY `tech_issues_assigned_to_foreign` (`assigned_to`),
  KEY `tech_issues_status_index` (`status`),
  KEY `tech_issues_priority_index` (`priority`),
  KEY `tech_issues_center_id_index` (`center_id`),
  CONSTRAINT `tech_issues_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tech_issues_center_id_foreign` FOREIGN KEY (`center_id`) REFERENCES `centers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tech_issues_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tech_issues_reported_by_foreign` FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `training_plan_lessons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `training_plan_lessons` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `training_plan_id` bigint(20) unsigned NOT NULL,
  `academic_level_id` bigint(20) unsigned NOT NULL,
  `subject_id` bigint(20) unsigned NOT NULL,
  `instructor_id` bigint(20) unsigned DEFAULT NULL,
  `week_number` int(10) unsigned NOT NULL,
  `day_of_week` smallint(5) unsigned NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `lesson_name` varchar(200) DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `training_plan_lessons_academic_level_id_foreign` (`academic_level_id`),
  KEY `training_plan_lessons_subject_id_foreign` (`subject_id`),
  KEY `training_plan_lessons_training_plan_id_academic_level_id_index` (`training_plan_id`,`academic_level_id`),
  KEY `idx_lesson_conflict` (`instructor_id`,`day_of_week`,`start_time`,`end_time`),
  CONSTRAINT `training_plan_lessons_academic_level_id_foreign` FOREIGN KEY (`academic_level_id`) REFERENCES `academic_levels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `training_plan_lessons_instructor_id_foreign` FOREIGN KEY (`instructor_id`) REFERENCES `hr_employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `training_plan_lessons_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `training_plan_lessons_training_plan_id_foreign` FOREIGN KEY (`training_plan_id`) REFERENCES `training_plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `training_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `training_plans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `name_ar` varchar(200) NOT NULL,
  `name_en` varchar(200) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('draft','active','completed','cancelled') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `training_plans_project_id_index` (`project_id`),
  CONSTRAINT `training_plans_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `uploaded_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `uploaded_documents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(40) NOT NULL DEFAULT 'other',
  `document_date` date DEFAULT NULL,
  `center_id` bigint(20) unsigned DEFAULT NULL,
  `project_id` bigint(20) unsigned DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_size` bigint(20) unsigned DEFAULT NULL,
  `uploaded_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `uploaded_documents_center_id_foreign` (`center_id`),
  KEY `uploaded_documents_project_id_foreign` (`project_id`),
  KEY `uploaded_documents_uploaded_by_foreign` (`uploaded_by`),
  KEY `uploaded_documents_category_document_date_index` (`category`,`document_date`),
  CONSTRAINT `uploaded_documents_center_id_foreign` FOREIGN KEY (`center_id`) REFERENCES `centers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `uploaded_documents_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `uploaded_documents_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `official_email` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `must_change_password` tinyint(1) NOT NULL DEFAULT 0,
  `activation_email_sent_at` timestamp NULL DEFAULT NULL,
  `activation_email_count` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `type` varchar(20) DEFAULT NULL COMMENT 'employee, beneficiary',
  `job_title_id` bigint(20) unsigned DEFAULT NULL,
  `center_id` bigint(20) unsigned DEFAULT NULL,
  `project_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_official_email_index` (`official_email`),
  KEY `users_job_title_id_foreign` (`job_title_id`),
  KEY `users_center_id_foreign` (`center_id`),
  KEY `users_project_id_foreign` (`project_id`),
  CONSTRAINT `users_center_id_foreign` FOREIGN KEY (`center_id`) REFERENCES `centers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_job_title_id_foreign` FOREIGN KEY (`job_title_id`) REFERENCES `hr_job_positions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `workflow_actions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `workflow_actions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `workable_type` varchar(255) NOT NULL,
  `workable_id` bigint(20) unsigned NOT NULL,
  `action` varchar(255) NOT NULL,
  `from_user_id` bigint(20) unsigned DEFAULT NULL,
  `to_user_id` bigint(20) unsigned DEFAULT NULL,
  `note` text DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `workflow_actions_workable_type_workable_id_index` (`workable_type`,`workable_id`),
  KEY `workflow_actions_from_user_id_foreign` (`from_user_id`),
  KEY `workflow_actions_to_user_id_foreign` (`to_user_id`),
  CONSTRAINT `workflow_actions_from_user_id_foreign` FOREIGN KEY (`from_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `workflow_actions_to_user_id_foreign` FOREIGN KEY (`to_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
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
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2026_06_09_000000_add_is_active_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2026_06_09_000001_create_centers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2026_06_09_000002_create_projects_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2026_06_09_000003_create_center_project_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2026_06_09_000004_create_groups_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2026_06_09_000005_create_group_user_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2026_06_09_000006_create_permissions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2026_06_12_120327_update_permissions_change_model_name_to_json',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2026_06_12_132139_create_departments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2026_06_12_132416_create_hr_employees_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2026_06_12_132416_create_hr_job_positions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2026_06_12_132417_create_hr_employee_contacts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2026_06_12_132417_create_hr_employee_educations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2026_06_12_132418_create_hr_work_schedules_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2026_06_12_132423_create_hr_contracts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2026_06_12_132424_create_hr_employee_documents_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2026_06_12_132424_create_hr_salaries_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2026_06_12_132425_create_hr_employee_notes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2026_06_12_132425_create_hr_warnings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2026_06_13_093314_add_user_type_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2026_06_16_000001_create_students_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2026_06_16_000002_create_courses_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2026_06_16_000003_create_periods_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2026_06_16_000004_create_course_period_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2026_06_16_000005_create_student_enrollments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2026_06_16_000006_create_attendance_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2026_06_16_000007_create_certificate_designs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2026_06_16_000008_create_certificates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2026_06_17_000001_create_certificate_number_sequence_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2026_06_22_070414_add_indexes_to_students_enrollments_attendance',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2026_06_22_080335_create_tech_equipment_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2026_06_22_080335_create_tech_issues_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2026_06_22_080336_add_official_email_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2026_06_22_100001_create_hr_leave_types_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2026_06_22_100002_create_hr_leave_requests_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2026_06_22_100003_create_hr_leave_balances_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2026_06_22_100004_create_hr_employee_attendances_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2026_06_28_000007_create_project_tasks_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2026_06_29_000001_add_identity_fields_to_students_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2026_06_29_093949_create_project_student_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2026_07_06_000001_add_soft_deletes_to_project_student_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2026_08_05_000001_add_password_change_flags_to_users_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2026_08_11_000001_add_activation_email_count_to_users_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2026_08_19_075018_create_audit_logs_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2026_08_19_075019_create_audit_changes_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2026_08_22_000001_add_signatures_and_font_to_certificate_designs_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2026_08_23_000001_add_cancelled_at_to_certificates_table',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51,'2026_08_27_000001_create_subjects_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (52,'2026_08_27_000002_create_course_offerings_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2026_08_27_000003_create_student_subject_grades_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2026_08_27_000004_create_academic_levels_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (55,'2026_08_27_000005_create_level_subjects_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (56,'2026_08_27_000006_create_level_subject_instructors_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (57,'2026_08_27_000007_create_training_plans_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (58,'2026_08_27_000008_create_training_plan_lessons_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (59,'2026_08_30_000001_create_cohorts_table',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (60,'2026_08_30_000002_add_cohort_to_employees_table',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (61,'2026_09_01_075848_add_employee_fields_to_users_table',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (62,'2026_09_05_000001_make_audit_logs_model_id_nullable',13);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (63,'2026_06_28_000001_create_logistics_settings_table',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (64,'2026_06_28_000002_create_logistics_approval_rules_table',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (65,'2026_06_28_000003_create_logistics_purchase_requests_table',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (66,'2026_06_28_000004_create_logistics_warehouses_table',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (67,'2026_06_28_000005_create_logistics_assets_table',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (68,'2026_06_28_000006_create_logistics_purchase_request_items_table',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (69,'2026_09_05_000001_create_workflow_actions_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (70,'2026_09_05_000002_add_purchase_cycle_columns_to_logistics_purchase_requests_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (71,'2026_09_05_000003_create_annex_document_tables',16);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (72,'2026_09_05_000004_create_media_plans_table',17);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (73,'2026_09_05_000005_create_movement_plans_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (74,'2026_09_05_000006_create_subject_exams_table',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (75,'2026_09_05_000007_add_course_id_to_academic_levels_table',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (76,'2026_09_11_101753_add_page_fields_to_annex_tables',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (77,'2026_09_13_000001_create_referrals_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (78,'2026_09_13_000002_add_executive_step_to_purchase_requests_table',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (79,'2026_09_13_000003_add_reviewer_to_movement_plans_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (80,'2026_09_13_000004_add_review_cycle_to_media_plans_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (81,'2026_09_13_000004_create_monthly_report_tables',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (82,'2026_09_13_000005_create_project_activities_table',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (83,'2026_09_13_000006_create_physio_rooms_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (84,'2026_09_13_000007_create_physio_patients_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (85,'2026_09_13_000008_create_physio_sessions_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (86,'2026_09_13_000001_add_budget_line_to_logistics_purchase_request_items_table',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (87,'2026_09_19_000001_create_project_paths_table',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (88,'2026_09_19_000002_add_fields_to_projects_table',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (89,'2026_09_19_000003_create_event_cards_table',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (90,'2026_09_22_000001_add_page_layout_to_document_templates',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (91,'2026_09_23_000001_create_certificate_signers_table',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (92,'2026_09_23_000002_create_certificate_signatory_sets_table',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (93,'2026_09_23_000003_add_signatory_set_id_to_certificates_table',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (94,'2026_09_23_000004_add_code_to_certificate_signers_table',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (95,'2026_10_02_000001_add_scopes_to_group_user_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (96,'2026_10_02_000002_add_kind_to_groups_table',34);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (97,'2026_10_02_150000_convert_movement_plans_to_multi_movements',35);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (98,'2026_10_03_000001_rework_purchase_requests_cycle',36);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (99,'2026_10_03_120000_change_purchase_request_budget_line_to_string',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (100,'2026_10_03_140000_add_tasks_grants_to_event_cards_table',38);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (101,'2026_10_03_150000_add_unique_kind_name_to_groups_table',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (102,'2026_10_03_160000_merge_duplicate_group_names_and_enforce_global_unique',40);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (103,'2026_10_03_170000_add_request_type_to_purchase_requests_table',41);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (104,'2026_10_03_180000_add_cover_path_to_documents_and_monthly_reports_tables',42);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (105,'2026_10_03_000002_add_rowaduna_media_cycle',43);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (106,'2026_10_03_000003_create_ad_design_requests_table',43);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (107,'2026_10_03_000004_create_uploaded_documents_table',44);
