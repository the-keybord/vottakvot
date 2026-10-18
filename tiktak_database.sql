-- TikTak Central Database Export
-- Generated: 2026-09-23 17:09:23
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- Table structure for `users`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Professor',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `users`
INSERT INTO `users` (`id`, `username`, `password_hash`, `name`, `created_at`) VALUES ('1', 'prof', '$2y$12$xpCQAsV/4sLfSQHnzdxFP.xhQg8CQe9vu3vN7Lv/zEdPAngsCUzum', 'Professor', '2026-09-22 21:11:45');

-- --------------------------------------------------------
-- Table structure for `resources`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `resources`;
CREATE TABLE `resources` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `type` enum('redirect','text','files','hosting','links') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'redirect',
  `content_link` text COLLATE utf8mb4_unicode_ci,
  `content_text` longtext COLLATE utf8mb4_unicode_ci,
  `visits_count` int NOT NULL DEFAULT '0',
  `expires_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `code_2` (`code`),
  KEY `expires_at` (`expires_at`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `resources`
INSERT INTO `resources` (`id`, `user_id`, `code`, `title`, `type`, `content_link`, `content_text`, `visits_count`, `expires_at`, `created_at`, `updated_at`) VALUES ('1', '1', 'YWEWKU', 'Baza de chestii', 'redirect', 'https://zece.info', '', '2', NULL, '2026-09-23 06:30:20', '2026-09-23 06:30:32');
INSERT INTO `resources` (`id`, `user_id`, `code`, `title`, `type`, `content_link`, `content_text`, `visits_count`, `expires_at`, `created_at`, `updated_at`) VALUES ('2', '1', 'SWXUTM', 'A fost odata', 'text', '', 'A fost o data ca niciodata si nu va mai fi', '2', NULL, '2026-09-23 06:31:05', '2026-09-23 06:31:42');
INSERT INTO `resources` (`id`, `user_id`, `code`, `title`, `type`, `content_link`, `content_text`, `visits_count`, `expires_at`, `created_at`, `updated_at`) VALUES ('3', '1', 'ABCDEF', 'Ceva', 'text', '', 'ceva mic', '2', NULL, '2026-09-23 06:33:49', '2026-09-23 06:34:14');
INSERT INTO `resources` (`id`, `user_id`, `code`, `title`, `type`, `content_link`, `content_text`, `visits_count`, `expires_at`, `created_at`, `updated_at`) VALUES ('4', '1', 'RGWLHZ', 'Ceva mic', 'redirect', 'https://google.com', '', '0', NULL, '2026-09-23 10:57:36', '2026-09-23 10:57:36');
INSERT INTO `resources` (`id`, `user_id`, `code`, `title`, `type`, `content_link`, `content_text`, `visits_count`, `expires_at`, `created_at`, `updated_at`) VALUES ('6', '1', 'URSGQC', '3D school test', 'files', '', '', '19', NULL, '2026-09-23 16:22:06', '2026-09-23 16:50:43');
INSERT INTO `resources` (`id`, `user_id`, `code`, `title`, `type`, `content_link`, `content_text`, `visits_count`, `expires_at`, `created_at`, `updated_at`) VALUES ('7', '1', 'APCTMK', '3D2', 'files', '', '', '2', '2026-09-23 17:31:46', '2026-09-23 16:31:46', '2026-09-23 16:39:30');

-- --------------------------------------------------------
-- Table structure for `resource_files`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `resource_files`;
CREATE TABLE `resource_files` (
  `id` int NOT NULL AUTO_INCREMENT,
  `resource_id` int NOT NULL,
  `code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stored_filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `s3_key` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_size` bigint NOT NULL,
  `mime_type` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `upload_date` date NOT NULL,
  `uploaded_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `ip_hash` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `downloads_count` int NOT NULL DEFAULT '0',
  `is_pinned` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `resource_id` (`resource_id`),
  KEY `code` (`code`),
  KEY `upload_date` (`upload_date`),
  KEY `uploaded_at` (`uploaded_at`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `resource_files`
INSERT INTO `resource_files` (`id`, `resource_id`, `code`, `original_filename`, `stored_filename`, `s3_key`, `file_size`, `mime_type`, `upload_date`, `uploaded_at`, `ip_hash`, `downloads_count`, `is_pinned`) VALUES ('4', '6', 'URSGQC', '2025-10-10 21_32_40-LEGO Education SPIKE.png', '2025-10-10 21_32_40-LEGO Education SPIKE.png', 'URSGQC/2025-10-10 21_32_40-LEGO Education SPIKE.png', '204234', 'image/png', '2026-09-23', '2026-09-23 16:22:44', '66bdec9161ed209049206a8a3936c771fcdd62c71ed57861d259229cbdaae65f', '0', '0');
INSERT INTO `resource_files` (`id`, `resource_id`, `code`, `original_filename`, `stored_filename`, `s3_key`, `file_size`, `mime_type`, `upload_date`, `uploaded_at`, `ip_hash`, `downloads_count`, `is_pinned`) VALUES ('5', '6', 'URSGQC', '2025-10-10 21_36_29-LEGO Education SPIKE.png', '2025-10-10 21_36_29-LEGO Education SPIKE.png', 'URSGQC/2025-10-10 21_36_29-LEGO Education SPIKE.png', '31469', 'image/png', '2026-09-23', '2026-09-23 16:22:55', '66bdec9161ed209049206a8a3936c771fcdd62c71ed57861d259229cbdaae65f', '2', '0');

-- --------------------------------------------------------
-- Table structure for `access_logs`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `access_logs`;
CREATE TABLE `access_logs` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `resource_id` int NOT NULL,
  `accessed_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `ip_hash` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `resource_id` (`resource_id`),
  KEY `accessed_at` (`accessed_at`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `access_logs`
INSERT INTO `access_logs` (`id`, `resource_id`, `accessed_at`, `ip_hash`, `user_agent`) VALUES ('1', '1', '2026-09-23 06:30:31', '66bdec9161ed209049206a8a3936c771fcdd62c71ed57861d259229cbdaae65f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36');
INSERT INTO `access_logs` (`id`, `resource_id`, `accessed_at`, `ip_hash`, `user_agent`) VALUES ('2', '1', '2026-09-23 06:30:32', '66bdec9161ed209049206a8a3936c771fcdd62c71ed57861d259229cbdaae65f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36');
INSERT INTO `access_logs` (`id`, `resource_id`, `accessed_at`, `ip_hash`, `user_agent`) VALUES ('3', '2', '2026-09-23 06:31:13', '66bdec9161ed209049206a8a3936c771fcdd62c71ed57861d259229cbdaae65f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36');
INSERT INTO `access_logs` (`id`, `resource_id`, `accessed_at`, `ip_hash`, `user_agent`) VALUES ('4', '2', '2026-09-23 06:31:42', '66bdec9161ed209049206a8a3936c771fcdd62c71ed57861d259229cbdaae65f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36');
INSERT INTO `access_logs` (`id`, `resource_id`, `accessed_at`, `ip_hash`, `user_agent`) VALUES ('5', '3', '2026-09-23 06:34:01', '66bdec9161ed209049206a8a3936c771fcdd62c71ed57861d259229cbdaae65f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36');
INSERT INTO `access_logs` (`id`, `resource_id`, `accessed_at`, `ip_hash`, `user_agent`) VALUES ('6', '3', '2026-09-23 06:34:14', '66bdec9161ed209049206a8a3936c771fcdd62c71ed57861d259229cbdaae65f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36');
INSERT INTO `access_logs` (`id`, `resource_id`, `accessed_at`, `ip_hash`, `user_agent`) VALUES ('7', '6', '2026-09-23 16:22:28', '66bdec9161ed209049206a8a3936c771fcdd62c71ed57861d259229cbdaae65f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36');
INSERT INTO `access_logs` (`id`, `resource_id`, `accessed_at`, `ip_hash`, `user_agent`) VALUES ('8', '6', '2026-09-23 16:23:33', '66bdec9161ed209049206a8a3936c771fcdd62c71ed57861d259229cbdaae65f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36');
INSERT INTO `access_logs` (`id`, `resource_id`, `accessed_at`, `ip_hash`, `user_agent`) VALUES ('9', '6', '2026-09-23 16:31:21', '66bdec9161ed209049206a8a3936c771fcdd62c71ed57861d259229cbdaae65f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36');
INSERT INTO `access_logs` (`id`, `resource_id`, `accessed_at`, `ip_hash`, `user_agent`) VALUES ('10', '7', '2026-09-23 16:31:57', '66bdec9161ed209049206a8a3936c771fcdd62c71ed57861d259229cbdaae65f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36');
INSERT INTO `access_logs` (`id`, `resource_id`, `accessed_at`, `ip_hash`, `user_agent`) VALUES ('11', '6', '2026-09-23 16:32:11', '66bdec9161ed209049206a8a3936c771fcdd62c71ed57861d259229cbdaae65f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36');
INSERT INTO `access_logs` (`id`, `resource_id`, `accessed_at`, `ip_hash`, `user_agent`) VALUES ('12', '6', '2026-09-23 16:33:28', '66bdec9161ed209049206a8a3936c771fcdd62c71ed57861d259229cbdaae65f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36');
INSERT INTO `access_logs` (`id`, `resource_id`, `accessed_at`, `ip_hash`, `user_agent`) VALUES ('13', '6', '2026-09-23 16:33:41', '66bdec9161ed209049206a8a3936c771fcdd62c71ed57861d259229cbdaae65f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36');
INSERT INTO `access_logs` (`id`, `resource_id`, `accessed_at`, `ip_hash`, `user_agent`) VALUES ('14', '7', '2026-09-23 16:39:30', '66bdec9161ed209049206a8a3936c771fcdd62c71ed57861d259229cbdaae65f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36');
INSERT INTO `access_logs` (`id`, `resource_id`, `accessed_at`, `ip_hash`, `user_agent`) VALUES ('15', '6', '2026-09-23 16:39:38', '66bdec9161ed209049206a8a3936c771fcdd62c71ed57861d259229cbdaae65f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36');
INSERT INTO `access_logs` (`id`, `resource_id`, `accessed_at`, `ip_hash`, `user_agent`) VALUES ('16', '6', '2026-09-23 16:40:41', '66bdec9161ed209049206a8a3936c771fcdd62c71ed57861d259229cbdaae65f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36');
INSERT INTO `access_logs` (`id`, `resource_id`, `accessed_at`, `ip_hash`, `user_agent`) VALUES ('17', '6', '2026-09-23 16:41:15', '66bdec9161ed209049206a8a3936c771fcdd62c71ed57861d259229cbdaae65f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36');
INSERT INTO `access_logs` (`id`, `resource_id`, `accessed_at`, `ip_hash`, `user_agent`) VALUES ('18', '6', '2026-09-23 16:41:38', '66bdec9161ed209049206a8a3936c771fcdd62c71ed57861d259229cbdaae65f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36');
INSERT INTO `access_logs` (`id`, `resource_id`, `accessed_at`, `ip_hash`, `user_agent`) VALUES ('19', '6', '2026-09-23 16:41:45', '66bdec9161ed209049206a8a3936c771fcdd62c71ed57861d259229cbdaae65f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36');
INSERT INTO `access_logs` (`id`, `resource_id`, `accessed_at`, `ip_hash`, `user_agent`) VALUES ('20', '6', '2026-09-23 16:42:07', '66bdec9161ed209049206a8a3936c771fcdd62c71ed57861d259229cbdaae65f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36');
INSERT INTO `access_logs` (`id`, `resource_id`, `accessed_at`, `ip_hash`, `user_agent`) VALUES ('21', '6', '2026-09-23 16:47:33', '66bdec9161ed209049206a8a3936c771fcdd62c71ed57861d259229cbdaae65f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36');
INSERT INTO `access_logs` (`id`, `resource_id`, `accessed_at`, `ip_hash`, `user_agent`) VALUES ('22', '6', '2026-09-23 16:47:50', '66bdec9161ed209049206a8a3936c771fcdd62c71ed57861d259229cbdaae65f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36');
INSERT INTO `access_logs` (`id`, `resource_id`, `accessed_at`, `ip_hash`, `user_agent`) VALUES ('23', '6', '2026-09-23 16:50:03', '66bdec9161ed209049206a8a3936c771fcdd62c71ed57861d259229cbdaae65f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36');
INSERT INTO `access_logs` (`id`, `resource_id`, `accessed_at`, `ip_hash`, `user_agent`) VALUES ('24', '6', '2026-09-23 16:50:08', '66bdec9161ed209049206a8a3936c771fcdd62c71ed57861d259229cbdaae65f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36');
INSERT INTO `access_logs` (`id`, `resource_id`, `accessed_at`, `ip_hash`, `user_agent`) VALUES ('25', '6', '2026-09-23 16:50:36', '66bdec9161ed209049206a8a3936c771fcdd62c71ed57861d259229cbdaae65f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36');
INSERT INTO `access_logs` (`id`, `resource_id`, `accessed_at`, `ip_hash`, `user_agent`) VALUES ('26', '6', '2026-09-23 16:50:39', '66bdec9161ed209049206a8a3936c771fcdd62c71ed57861d259229cbdaae65f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36');
INSERT INTO `access_logs` (`id`, `resource_id`, `accessed_at`, `ip_hash`, `user_agent`) VALUES ('27', '6', '2026-09-23 16:50:43', '66bdec9161ed209049206a8a3936c771fcdd62c71ed57861d259229cbdaae65f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36');

SET FOREIGN_KEY_CHECKS = 1;
