-- ========================================
-- Recurlog Database Migration
-- Run once: SOURCE /path/to/migration.sql;
-- All statements use IF NOT EXISTS (safe to re-run)
-- ========================================

CREATE TABLE IF NOT EXISTS `fscrm_localities` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `fscrm_assignment_history` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `entity_type` VARCHAR(20) NOT NULL,
    `entity_id` INT NOT NULL,
    `previous_assignee_id` INT DEFAULT NULL,
    `new_assignee_id` INT DEFAULT NULL,
    `changed_by` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_entity` (`entity_type`, `entity_id`),
    INDEX `idx_changed_by` (`changed_by`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `fscrm_users` ADD COLUMN IF NOT EXISTS `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `role`;
ALTER TABLE `fscrm_users` ADD COLUMN IF NOT EXISTS `created_by` VARCHAR(100) DEFAULT NULL AFTER `staff_id`;
