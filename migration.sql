-- ========================================
-- Recurlog Database Migration
-- Run once: SOURCE /path/to/migration.sql;
-- All statements use IF NOT EXISTS (safe to re-run)
-- ========================================

CREATE TABLE IF NOT EXISTS `fscrm_push_tokens` (
    `id` BIGINT NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT NOT NULL,
    `platform` ENUM('android','ios','web') NOT NULL,
    `expo_token` TEXT NULL,
    `endpoint` TEXT NULL,
    `p256dh` TEXT NULL,
    `auth` TEXT NULL,
    `device_name` VARCHAR(255) DEFAULT NULL,
    `app_version` VARCHAR(50) DEFAULT NULL,
    `notifications_enabled` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_platform` (`platform`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
