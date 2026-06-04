-- Recurlog Database Migration
-- Run this ONCE on the target database to create all tables.
-- Seed data is handled separately via api/seed.php or Settings > Reset Demo Data.

CREATE DATABASE IF NOT EXISTS `ektamultp_recurlog`
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `ektamultp_recurlog`;

-- -----------------------------------------------------------
-- Users (auth accounts; staff_id links to fscrm_staff)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `fscrm_users` (
  `id`       INT(11)      NOT NULL AUTO_INCREMENT,
  `name`     VARCHAR(100) NOT NULL,
  `email`    VARCHAR(100) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role`     VARCHAR(20)  NOT NULL DEFAULT 'user',
  `staff_id` INT(11)      DEFAULT NULL,
  `avatar`   VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME   DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- Staff (profile info)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `fscrm_staff` (
  `id`       INT(11)      NOT NULL AUTO_INCREMENT,
  `name`     VARCHAR(200) NOT NULL,
  `phone`    VARCHAR(50)  DEFAULT NULL,
  `avatar`   VARCHAR(500) DEFAULT NULL,
  `active_tasks` INT(11)  DEFAULT 0,
  `created_at` DATETIME   DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME   DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- Customers
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `fscrm_customers` (
  `id`           INT(11)      NOT NULL AUTO_INCREMENT,
  `name`         VARCHAR(200) NOT NULL,
  `address`      TEXT         DEFAULT NULL,
  `area`         VARCHAR(100) DEFAULT NULL,
  `phone`        VARCHAR(50)  DEFAULT NULL,
  `services_for` TEXT         DEFAULT NULL,
  `location_lat` DECIMAL(10,7) DEFAULT NULL,
  `location_lng` DECIMAL(10,7) DEFAULT NULL,
  `created_at`   DATETIME     DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- Categories
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `fscrm_categories` (
  `id`    INT(11)      NOT NULL AUTO_INCREMENT,
  `name`  VARCHAR(100) NOT NULL,
  `color` VARCHAR(10)  DEFAULT '#1DB954',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- Service Types
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `fscrm_service_types` (
  `id`   INT(11)      NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- Localities (areas for orders workflow)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `fscrm_localities` (
  `id`   INT(11)      NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(200) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- Services (recurring & one-time service definitions)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `fscrm_services` (
  `id`                  INT(11)      NOT NULL AUTO_INCREMENT,
  `customer_id`         INT(11)      NOT NULL,
  `category_id`         INT(11)      DEFAULT NULL,
  `service_for`         VARCHAR(100) DEFAULT NULL,
  `title`               VARCHAR(200) NOT NULL,
  `problem`             TEXT         DEFAULT NULL,
  `is_recurring`        TINYINT(1)   DEFAULT 0,
  `first_scheduled_date` DATE        DEFAULT NULL,
  `assigned_to`         INT(11)      DEFAULT NULL,
  `notes`               TEXT         DEFAULT NULL,
  `rec_value`           INT(11)      DEFAULT NULL,
  `rec_unit`            VARCHAR(20)  DEFAULT NULL,
  `repeat_from`         VARCHAR(50)  DEFAULT NULL,
  `created_at`          DATETIME     DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  KEY `category_id` (`category_id`),
  KEY `assigned_to` (`assigned_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- Tasks (individual service visits)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `fscrm_tasks` (
  `id`              INT(11)      NOT NULL AUTO_INCREMENT,
  `service_id`      INT(11)      DEFAULT NULL,
  `customer_id`     INT(11)      NOT NULL,
  `title`           VARCHAR(200) NOT NULL,
  `status`          ENUM('pending','completed','missed') DEFAULT 'pending',
  `scheduled_date`  DATE         DEFAULT NULL,
  `completed_date`  DATE         DEFAULT NULL,
  `assigned_to`     INT(11)      DEFAULT NULL,
  `notes`           TEXT         DEFAULT NULL,
  `category_id`     INT(11)      DEFAULT NULL,
  `completed_by`    VARCHAR(100) DEFAULT NULL,
  `received_name`   VARCHAR(100) DEFAULT NULL,
  `received_contact` VARCHAR(100) DEFAULT NULL,
  `signature`       TEXT         DEFAULT NULL,
  `created_at`      DATETIME     DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `service_id` (`service_id`),
  KEY `customer_id` (`customer_id`),
  KEY `assigned_to` (`assigned_to`),
  KEY `category_id` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- Orders (standalone work orders, separate from services/tasks)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `fscrm_orders` (
  `id`                  INT(11)      NOT NULL AUTO_INCREMENT,
  `customer_id`         INT(11)      NOT NULL,
  `customer_name`       VARCHAR(200) DEFAULT NULL,
  `service_for`         VARCHAR(100) DEFAULT NULL,
  `problem`             TEXT         DEFAULT NULL,
  `status`              ENUM('pending','assigned','completed','cancelled') DEFAULT 'pending',
  `priority`            ENUM('normal','urgent') DEFAULT 'normal',
  `assigned_to`         INT(11)      DEFAULT NULL,
  `assigned_staff_name` VARCHAR(200) DEFAULT NULL,
  `scheduled_date`      DATE         DEFAULT NULL,
  `completed_date`      DATE         DEFAULT NULL,
  `notes`               TEXT         DEFAULT NULL,
  `dispatch_date`       DATE         DEFAULT NULL,
  `dispatch_by`         VARCHAR(100) DEFAULT NULL,
  `received_name`       VARCHAR(100) DEFAULT NULL,
  `received_contact`    VARCHAR(100) DEFAULT NULL,
  `signature`           TEXT         DEFAULT NULL,
  `created_at`          DATETIME     DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  KEY `assigned_to` (`assigned_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- Notifications
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `fscrm_notifications` (
  `id`         INT(11)      NOT NULL AUTO_INCREMENT,
  `text`       TEXT         NOT NULL,
  `type`       VARCHAR(50)  DEFAULT 'info',
  `related_id` INT(11)      DEFAULT NULL,
  `is_read`    TINYINT(1)   DEFAULT 0,
  `created_at` DATETIME     DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
