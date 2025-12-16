-- SNS_2A Database Schema (MySQL/MariaDB)

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- Users Table
CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `display_name` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `avatar_url` varchar(255) DEFAULT NULL,
  `metadata` json DEFAULT NULL COMMENT 'Storage usage, preferences, etc.',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_username_unique` (`username`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Posts Table
CREATE TABLE IF NOT EXISTS `posts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `type` enum('feed', 'qa', 'blog') NOT NULL,
  `content_short` varchar(150) DEFAULT NULL,
  `content_long` text DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `qa_status` enum('open', 'resolved') DEFAULT 'open',
  `best_answer_id` bigint(20) unsigned DEFAULT NULL,
  `parent_post_id` bigint(20) unsigned DEFAULT NULL,
  `metadata` json DEFAULT NULL COMMENT 'Image URLs, extra data',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `posts_type_created_at_index` (`type`, `created_at`),
  KEY `posts_parent_post_id_index` (`parent_post_id`),
  CONSTRAINT `posts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Quotes Table
CREATE TABLE IF NOT EXISTS `quotes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `source_post_id` bigint(20) unsigned NOT NULL,
  `quoting_post_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `quotes_source_quoting_unique` (`source_post_id`, `quoting_post_id`),
  CONSTRAINT `quotes_source_post_id_foreign` FOREIGN KEY (`source_post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `quotes_quoting_post_id_foreign` FOREIGN KEY (`quoting_post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reactions Table
CREATE TABLE IF NOT EXISTS `reactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `post_id` bigint(20) unsigned NOT NULL,
  `emoji` varchar(32) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reactions_user_post_emoji_unique` (`user_id`, `post_id`, `emoji`),
  KEY `reactions_post_id_emoji_index` (`post_id`, `emoji`),
  CONSTRAINT `reactions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reactions_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
