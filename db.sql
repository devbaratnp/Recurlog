/*
SQLyog Community v13.3.0 (64 bit)
MySQL - 12.0.2-MariaDB : Database - recurlog
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
CREATE DATABASE /*!32312 IF NOT EXISTS*/`recurlog` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;

USE `recurlog`;

/*Table structure for table `fscrm_categories` */

DROP TABLE IF EXISTS `fscrm_categories`;

CREATE TABLE `fscrm_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `color` varchar(10) DEFAULT '#1DB954',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `fscrm_categories` */

insert  into `fscrm_categories`(`id`,`name`,`color`,`created_at`) values 
(1,'Annual Maintenance','#1DB954','2026-06-04 13:04:30'),
(2,'Filter Change','#0EA5E9','2026-06-04 13:04:30'),
(3,'Repair','#F59E0B','2026-06-04 13:04:30'),
(4,'Deep Cleaning','#8B5CF6','2026-06-04 13:04:30'),
(5,'Installation','#EC4899','2026-06-04 13:04:30'),
(6,'Inspection','#6366F1','2026-06-04 13:04:30');

/*Table structure for table `fscrm_customers` */

DROP TABLE IF EXISTS `fscrm_customers`;

CREATE TABLE `fscrm_customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `address` text DEFAULT NULL,
  `area` varchar(100) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `services_for` text DEFAULT NULL,
  `location_lat` decimal(10,7) DEFAULT NULL,
  `location_lng` decimal(10,7) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `fscrm_customers` */

insert  into `fscrm_customers`(`id`,`name`,`address`,`area`,`phone`,`services_for`,`location_lat`,`location_lng`,`created_at`,`updated_at`) values 
(1,'Sharma Family','Adarsh Nagar, Birgunj','Adarsh Nagar','+977-9801234001','RO,Refrigerator',27.0000000,84.0000000,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(2,'Gupta Electronics','Main Road, Birgunj','Station Road','+977-9801234002','TV,AC',27.0000000,84.0000000,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(3,'Hotel Makalu','Ghantaghar, Birgunj','Ghantaghar Chowk','+977-9801234003','AC,Refrigerator',27.0000000,84.0000000,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(4,'Patel Residence','Powerhouse Road, Birgunj','Powerhouse Road','+977-9801234004','RO',26.0000000,84.0000000,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(5,'Singh Niwas','Adarshanagar, Birgunj','Mahabirsthan','+977-9801234005','Washing Machine',27.0000000,84.0000000,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(6,'Modern Pharmacy','Adarsh Nagar, Birgunj','Adarsh Nagar','+977-9801234006','Refrigerator,AC',27.0000000,84.0000000,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(7,'Khanal House','Murli Chowk, Birgunj','Murli Chowk','+977-9801234007','RO,TV',26.0000000,84.0000000,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(8,'Birgunj Sweets','Maisthan, Birgunj','Maisthan','+977-9801234008','Refrigerator',27.0000000,84.0000000,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(9,'Devbarat Prasad Patel','Bahudramai-07, Phulkaul, Parsa','Mahabirsthan','+9779811144402','',27.0065000,84.8679000,'2026-06-04 15:11:42','2026-06-04 15:11:42');

/*Table structure for table `fscrm_localities` */

DROP TABLE IF EXISTS `fscrm_localities`;

CREATE TABLE `fscrm_localities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `fscrm_localities` */

insert  into `fscrm_localities`(`id`,`name`,`created_at`) values 
(1,'Adarsh Nagar','2026-06-04 13:04:30'),
(2,'Ghantaghar Chowk','2026-06-04 13:04:30'),
(3,'Mahabirsthan','2026-06-04 13:04:30'),
(4,'Maisthan','2026-06-04 13:04:30'),
(5,'Murli Chowk','2026-06-04 13:04:30'),
(6,'Powerhouse Road','2026-06-04 13:04:30'),
(7,'Station Road','2026-06-04 13:04:30');

/*Table structure for table `fscrm_notifications` */

DROP TABLE IF EXISTS `fscrm_notifications`;

CREATE TABLE `fscrm_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `text` text NOT NULL,
  `type` varchar(50) DEFAULT 'info',
  `related_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `fscrm_notifications` */

insert  into `fscrm_notifications`(`id`,`text`,`type`,`related_id`,`is_read`,`created_at`) values 
(1,'Water Quality Test completed for Patel Residence','task_completed',46,0,'2026-06-04 12:00:00'),
(2,'Drum Deep Cleaning missed at Singh Niwas','task_missed',59,0,'2026-06-04 12:00:00'),
(3,'RO System Audit completed for Khanal House','task_completed',91,0,'2026-06-04 12:00:00'),
(4,'Temperature Calibration Check missed at Birgunj Sweets','task_missed',105,1,'2026-06-04 12:00:00'),
(5,'Kitchen Exhaust Inspection missed at Hotel Makalu','task_missed',135,0,'2026-06-04 12:00:00'),
(6,'Ice Machine Cleaning completed for Birgunj Sweets','task_completed',165,0,'2026-06-04 12:00:00'),
(7,'New Display Unit Installation completed for Birgunj Sweets','task_completed',107,1,'2026-06-02 12:00:00'),
(8,'RO Preventive Inspection completed for Sharma Family','task_completed',117,0,'2026-06-02 12:00:00'),
(9,'Backup Cooler Temperature Check completed for Modern Pharmacy','task_completed',150,0,'2026-06-02 12:00:00'),
(10,'RO Faucet Installation completed for Patel Residence','task_completed',47,0,'2026-05-30 12:00:00'),
(11,'Temperature Log Inspection completed for Modern Pharmacy','task_completed',69,1,'2026-05-30 12:00:00'),
(12,'AC Drain Line Repair missed at Modern Pharmacy','task_missed',80,0,'2026-05-30 12:00:00'),
(13,'Drain Hose Replacement missed at Singh Niwas','task_missed',144,0,'2026-05-29 12:00:00'),
(14,'Door Hinge Replacement completed for Birgunj Sweets','task_completed',111,1,'2026-05-28 12:00:00'),
(15,'AC Thermostat Calibration completed for Hotel Makalu','task_completed',40,1,'2026-05-26 12:00:00'),
(16,'Coil Deep Cleaning completed for Modern Pharmacy','task_completed',74,0,'2026-05-26 12:00:00'),
(17,'Smart TV Setup Configuration completed for Khanal House','task_completed',156,1,'2026-05-23 12:00:00'),
(18,'Temperature Calibration Check completed for Birgunj Sweets','task_completed',104,0,'2026-05-21 12:00:00'),
(19,'Kitchen Exhaust Inspection completed for Hotel Makalu','task_completed',134,1,'2026-05-22 12:00:00'),
(20,'RO Pressure Check completed for Patel Residence','task_completed',139,0,'2026-05-22 12:00:00'),
(21,'RO Filter Change completed for Sharma Family','task_completed',3,0,'2026-05-21 12:00:00'),
(22,'TV Display Repair completed for Gupta Electronics','task_completed',24,1,'2026-05-20 12:00:00'),
(23,'New customer registered: Sharma Family','customer_added',NULL,0,'2026-04-30 00:00:00'),
(24,'New service added: AC Annual Maintenance for Gupta Electronics','service_added',NULL,0,'2026-05-07 00:00:00'),
(25,'New customer registered: Hotel Makalu','customer_added',NULL,0,'2026-05-15 00:00:00'),
(26,'New service added: RO Filter Change for Patel Residence','service_added',NULL,0,'2026-05-20 00:00:00'),
(27,'Ramesh Yadav completed 15 tasks this week','task_completed',NULL,0,'2026-06-01 00:00:00'),
(28,'New service \"Inspection - AC\" added for Devbarat Prasad Patel','service',71,0,'2026-06-04 15:11:59');

/*Table structure for table `fscrm_orders` */

DROP TABLE IF EXISTS `fscrm_orders`;

CREATE TABLE `fscrm_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `customer_name` varchar(200) DEFAULT NULL,
  `service_for` varchar(100) DEFAULT NULL,
  `problem` text DEFAULT NULL,
  `status` enum('pending','assigned','completed','cancelled') DEFAULT 'pending',
  `priority` enum('normal','urgent') DEFAULT 'normal',
  `assigned_to` int(11) DEFAULT NULL,
  `assigned_staff_name` varchar(200) DEFAULT NULL,
  `scheduled_date` date DEFAULT NULL,
  `completed_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `dispatch_date` date DEFAULT NULL,
  `dispatch_by` varchar(100) DEFAULT NULL,
  `received_name` varchar(100) DEFAULT NULL,
  `received_contact` varchar(100) DEFAULT NULL,
  `signature` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  KEY `assigned_to` (`assigned_to`),
  CONSTRAINT `fscrm_orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `fscrm_customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fscrm_orders_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `fscrm_staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `fscrm_orders` */

insert  into `fscrm_orders`(`id`,`customer_id`,`customer_name`,`service_for`,`problem`,`status`,`priority`,`assigned_to`,`assigned_staff_name`,`scheduled_date`,`completed_date`,`notes`,`dispatch_date`,`dispatch_by`,`received_name`,`received_contact`,`signature`,`created_at`,`updated_at`) values 
(1,1,'Sharma Family','RO','Water pressure very low, filter needs urgent check','pending','urgent',NULL,NULL,NULL,NULL,'',NULL,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(2,2,'Gupta Electronics','AC','AC not cooling properly, gas might be low','assigned','normal',1,'Ramesh Yadav','2026-06-05',NULL,'Customer called in the morning',NULL,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(3,3,'Hotel Makalu','Refrigerator','Commercial fridge making unusual noise, cooling inconsistent','assigned','normal',2,'Suresh Thakur','2026-06-04',NULL,'Priority customer - hotel business',NULL,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(4,4,'Patel Residence','RO','RO is leaking from the bottom, water all over the floor','completed','urgent',4,'Anita Devi','2026-05-26','2026-05-26','Leak fixed, replaced seal',NULL,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(5,5,'Singh Niwas','Washing Machine','Drum not spinning, error code E4 showing on display','pending','normal',NULL,NULL,NULL,NULL,'',NULL,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(6,6,'Modern Pharmacy','AC','AC installed last week but not blowing cold air','cancelled','urgent',3,'Bikash Sah','2026-05-24',NULL,'Customer cancelled - hired another service',NULL,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(7,7,'Khanal House','TV','TV screen flickering when connected to HDMI','pending','normal',NULL,NULL,NULL,NULL,'',NULL,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(8,8,'Birgunj Sweets','Refrigerator','Display cooler not maintaining temperature, sweets getting spoiled','assigned','urgent',5,'Manoj Kumar','2026-06-04',NULL,'URGENT - food safety concern',NULL,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(9,2,'Gupta Electronics','TV','TV not turning on, power light blinking','pending','normal',NULL,NULL,NULL,NULL,'',NULL,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(10,1,'Sharma Family','Refrigerator','Ice maker not working, water dispenser also jammed','completed','normal',2,'Suresh Thakur','2026-05-21','2026-05-21','Ice maker repaired, water line unclogged',NULL,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(11,4,'Patel Residence','RO','Bad taste in water, membrane might need replacement','pending','normal',NULL,NULL,NULL,NULL,'',NULL,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(12,6,'Modern Pharmacy','Refrigerator','Vaccine storage fridge temperature fluctuating','pending','urgent',NULL,NULL,NULL,NULL,'',NULL,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(13,3,'Hotel Makalu','AC','One AC unit in lobby making loud rattling sound','assigned','normal',4,'Anita Devi','2026-05-30',NULL,'May need fan motor replacement',NULL,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30');

/*Table structure for table `fscrm_service_types` */

DROP TABLE IF EXISTS `fscrm_service_types`;

CREATE TABLE `fscrm_service_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `fscrm_service_types` */

insert  into `fscrm_service_types`(`id`,`name`,`created_at`) values 
(1,'RO','2026-06-04 13:04:30'),
(2,'Chimney','2026-06-04 13:04:30'),
(3,'Refrigerator','2026-06-04 13:04:30'),
(4,'TV','2026-06-04 13:04:30'),
(5,'Washing Machine','2026-06-04 13:04:30'),
(6,'AC','2026-06-04 13:04:30'),
(7,'Other','2026-06-04 13:04:30');

/*Table structure for table `fscrm_services` */

DROP TABLE IF EXISTS `fscrm_services`;

CREATE TABLE `fscrm_services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `service_for` varchar(100) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `problem` text DEFAULT NULL,
  `is_recurring` tinyint(1) DEFAULT 0,
  `first_scheduled_date` date DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `rec_value` int(11) DEFAULT NULL,
  `rec_unit` varchar(20) DEFAULT NULL,
  `repeat_from` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  KEY `category_id` (`category_id`),
  KEY `assigned_to` (`assigned_to`),
  CONSTRAINT `fscrm_services_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `fscrm_customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fscrm_services_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `fscrm_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fscrm_services_ibfk_3` FOREIGN KEY (`assigned_to`) REFERENCES `fscrm_staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=72 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `fscrm_services` */

insert  into `fscrm_services`(`id`,`customer_id`,`category_id`,`service_for`,`title`,`problem`,`is_recurring`,`first_scheduled_date`,`assigned_to`,`notes`,`rec_value`,`rec_unit`,`repeat_from`,`created_at`,`updated_at`) values 
(1,1,2,'RO','RO Filter Change','',1,'2026-03-21',1,'Standard filter replacement done.',30,'days','last_service','2026-06-04 13:04:30','2026-06-04 13:04:30'),
(2,1,6,'RO','RO System Inspection','',1,'2026-03-16',4,'System checked; no major concerns found.',45,'days','last_service','2026-06-04 13:04:30','2026-06-04 13:04:30'),
(3,1,4,'Refrigerator','Refrigerator Deep Cleaning','',1,'2026-04-05',2,'Thorough cleaning completed.',90,'days','last_service','2026-06-04 13:04:30','2026-06-04 13:04:30'),
(4,1,1,'Refrigerator','Refrigerator Annual Maintenance','',1,'2026-03-26',1,'System running efficiently after service.',90,'days','last_service','2026-06-04 13:04:30','2026-06-04 13:04:30'),
(5,1,3,'RO','RO Membrane Replacement','',0,'2026-04-20',4,'Part needed to be ordered, repaired on revisit.',NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(6,1,5,'RO','RO System Installation','',0,'2026-02-04',3,'New unit installed successfully.',NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(7,1,6,'Refrigerator','Temperature Calibration Check','',1,'2026-03-26',5,'System checked; no major concerns found.',45,'days','last_service','2026-06-04 13:04:30','2026-06-04 13:04:30'),
(8,2,6,'TV','TV Calibration','',1,'2026-03-31',2,'Inspection report shared with customer.',90,'days','last_service','2026-06-04 13:04:30','2026-06-04 13:04:30'),
(9,2,1,'AC','AC Annual Maintenance','',1,'2026-03-21',1,'All components checked and functioning.',45,'days','last_service','2026-06-04 13:04:30','2026-06-04 13:04:30'),
(10,2,3,'AC','AC Gas Refill','',0,'2026-05-05',3,'Faulty component identified and replaced.',NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(11,2,2,'AC','AC Filter Cleaning','',1,'2026-03-16',4,'Customer advised on next filter change schedule.',30,'days','last_service','2026-06-04 13:04:30','2026-06-04 13:04:30'),
(12,2,5,'TV','TV Mounting Service','',0,'2026-03-06',5,'New unit installed successfully.',NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(13,2,6,'AC','AC Performance Inspection','',1,'2026-03-31',1,'System checked; no major concerns found.',45,'days','last_service','2026-06-04 13:04:30','2026-06-04 13:04:30'),
(14,2,3,'TV','TV Display Repair','',0,'2026-05-20',2,'Diagnosed issue and fixed on site.',NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(15,3,1,'AC','AC Annual Maintenance','',1,'2026-03-16',1,'Annual checkup completed without issues.',45,'days','last_service','2026-06-04 13:04:30','2026-06-04 13:04:30'),
(16,3,4,'Refrigerator','Refrigerator Deep Cleaning','',1,'2026-03-26',4,'Coils and vents cleaned thoroughly.',45,'days','last_service','2026-06-04 13:04:30','2026-06-04 13:04:30'),
(17,3,3,'AC','AC Compressor Repair','',0,'2026-04-10',3,'Part needed to be ordered, repaired on revisit.',NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(18,3,3,'Refrigerator','Door Seal Replacement','',0,'2026-04-25',2,'Repair completed successfully.',NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(19,3,6,'AC','AC Filter Inspection','',1,'2026-03-11',4,'Detailed inspection carried out.',30,'days','last_service','2026-06-04 13:04:30','2026-06-04 13:04:30'),
(20,3,6,'Refrigerator','Commercial Cooler Inspection','',1,'2026-03-21',1,'System checked; no major concerns found.',30,'days','last_service','2026-06-04 13:04:30','2026-06-04 13:04:30'),
(21,3,1,'Refrigerator','Walk-in Cooler Maintenance','',1,'2026-04-05',5,'Annual checkup completed without issues.',45,'days','last_service','2026-06-04 13:04:30','2026-06-04 13:04:30'),
(22,3,3,'AC','AC Thermostat Calibration','',0,'2026-05-25',2,'Repair completed successfully.',NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(23,4,2,'RO','RO Filter Change','',1,'2026-03-16',4,'Standard filter replacement done.',30,'days','last_service','2026-06-04 13:04:30','2026-06-04 13:04:30'),
(24,4,3,'RO','RO Membrane Replacement','',0,'2026-04-15',1,'Part needed to be ordered, repaired on revisit.',NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(25,4,6,'RO','Water Quality Test','',1,'2026-04-05',3,'Minor issues noted, customer advised.',60,'days','last_service','2026-06-04 13:04:30','2026-06-04 13:04:30'),
(26,4,5,'RO','RO Faucet Installation','',0,'2026-02-24',5,'Mounting and setup completed.',NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(27,4,6,'RO','RO System Inspection','',1,'2026-03-26',4,'Inspection report shared with customer.',45,'days','last_service','2026-06-04 13:04:30','2026-06-04 13:04:30'),
(28,5,3,'Washing Machine','Drum Bearing Repair','',0,'2026-04-30',3,'Repair completed successfully.',NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(29,5,5,'Washing Machine','Washing Machine Installation','',0,'2026-03-01',2,'Installation done as per customer preference.',NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(30,5,1,'Washing Machine','Annual Maintenance','',1,'2026-03-21',1,'All components checked and functioning.',45,'days','last_service','2026-06-04 13:04:30','2026-06-04 13:04:30'),
(31,5,6,'Washing Machine','Performance Inspection','',1,'2026-03-16',4,'System checked; no major concerns found.',30,'days','last_service','2026-06-04 13:04:30','2026-06-04 13:04:30'),
(32,5,4,'Washing Machine','Drum Deep Cleaning','',1,'2026-02-04',5,'Removed accumulated dust and debris.',60,'days','last_service','2026-06-04 13:04:30','2026-06-04 13:04:30'),
(33,5,3,'Washing Machine','Water Inlet Valve Replacement','',0,'2026-05-15',2,'Diagnosed issue and fixed on site.',NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(34,6,1,'Refrigerator','Vaccine Storage Unit Maintenance','',1,'2026-03-16',1,'Annual checkup completed without issues.',30,'days','last_service','2026-06-04 13:04:30','2026-06-04 13:04:30'),
(35,6,6,'Refrigerator','Temperature Log Inspection','',1,'2026-03-21',4,'Detailed inspection carried out.',14,'days','last_service','2026-06-04 13:04:30','2026-06-04 13:04:30'),
(36,6,1,'AC','AC Annual Maintenance','',1,'2026-03-31',2,'Preventive maintenance completed.',45,'days','last_service','2026-06-04 13:04:30','2026-06-04 13:04:30'),
(37,6,4,'Refrigerator','Coil Deep Cleaning','',1,'2026-03-26',3,'Removed accumulated dust and debris.',60,'days','last_service','2026-06-04 13:04:30','2026-06-04 13:04:30'),
(38,6,6,'AC','AC Air Quality Inspection','',1,'2026-03-16',4,'All parameters within normal range.',30,'days','last_service','2026-06-04 13:04:30','2026-06-04 13:04:30'),
(39,6,3,'Refrigerator','Thermostat Calibration Repair','',0,'2026-05-10',1,'Customer reported problem, resolved after inspection.',NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(40,6,5,'Refrigerator','Backup Unit Installation','',0,'2026-02-14',5,'Installation completed and tested.',NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(41,6,3,'AC','AC Drain Line Repair','',0,'2026-05-30',2,'Customer reported problem, resolved after inspection.',NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(42,7,2,'RO','RO Filter Change','',1,'2026-03-16',4,'Filter replaced with new unit.',30,'days','last_service','2026-06-04 13:04:30','2026-06-04 13:04:30'),
(43,7,6,'TV','TV Picture Calibration','',1,'2026-04-05',2,'Inspection report shared with customer.',90,'days','last_service','2026-06-04 13:04:30','2026-06-04 13:04:30'),
(44,7,6,'RO','RO Water Quality Inspection','',1,'2026-03-21',1,'Minor issues noted, customer advised.',45,'days','last_service','2026-06-04 13:04:30','2026-06-04 13:04:30'),
(45,7,3,'TV','TV Wall Mount Repair','',0,'2026-04-23',3,'Diagnosed issue and fixed on site.',NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(46,7,3,'RO','RO Pressure Pump Repair','',0,'2026-05-17',5,'Repair completed successfully.',NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(47,7,6,'RO','RO System Audit','',1,'2026-02-04',4,'System checked; no major concerns found.',60,'days','last_service','2026-06-04 13:04:30','2026-06-04 13:04:30'),
(48,8,4,'Refrigerator','Display Cooler Deep Cleaning','',1,'2026-03-21',1,'Thorough cleaning completed.',30,'days','last_service','2026-06-04 13:04:30','2026-06-04 13:04:30'),
(49,8,3,'Refrigerator','Compressor Repair','',0,'2026-04-15',3,'Faulty component identified and replaced.',NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(50,8,1,'Refrigerator','Commercial Fridge Maintenance','',1,'2026-03-16',4,'Annual checkup completed without issues.',30,'days','last_service','2026-06-04 13:04:30','2026-06-04 13:04:30'),
(51,8,6,'Refrigerator','Temperature Calibration Check','',1,'2026-03-26',2,'Detailed inspection carried out.',14,'days','last_service','2026-06-04 13:04:30','2026-06-04 13:04:30'),
(52,8,5,'Refrigerator','New Display Unit Installation','',0,'2026-03-11',5,'New unit installed successfully.',NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(53,8,6,'Refrigerator','Condenser Coil Inspection','',1,'2026-03-31',1,'Minor issues noted, customer advised.',45,'days','last_service','2026-06-04 13:04:30','2026-06-04 13:04:30'),
(54,8,4,'Refrigerator','Storage Room Cooler Cleaning','',1,'2026-03-06',4,'Cleaning completed; unit performing better.',60,'days','last_service','2026-06-04 13:04:30','2026-06-04 13:04:30'),
(55,8,3,'Refrigerator','Door Hinge Replacement','',0,'2026-05-27',2,'Customer reported problem, resolved after inspection.',NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(56,1,6,'RO','RO Preventive Inspection','',1,'2026-03-24',2,'All parameters within normal range.',14,'days','last_service','2026-06-04 13:04:30','2026-06-04 13:04:30'),
(57,1,4,'Refrigerator','Condenser Coil Cleaning','',1,'2026-03-18',5,'Deep cleaning performed with disinfectant.',30,'days','last_service','2026-06-04 13:04:30','2026-06-04 13:04:30'),
(58,2,4,'AC','AC Condenser Cleaning','',1,'2026-03-14',3,'Coils and vents cleaned thoroughly.',30,'days','last_service','2026-06-04 13:04:30','2026-06-04 13:04:30'),
(59,2,6,'TV','TV Surge Protector Check','',1,'2026-03-28',1,'Detailed inspection carried out.',45,'days','last_service','2026-06-04 13:04:30','2026-06-04 13:04:30'),
(60,3,6,'AC','Refrigerant Level Check','',1,'2026-03-20',4,'Inspection report shared with customer.',30,'days','last_service','2026-06-04 13:04:30','2026-06-04 13:04:30'),
(61,3,6,'Refrigerator','Kitchen Exhaust Inspection','',1,'2026-03-26',5,'All parameters within normal range.',14,'days','last_service','2026-06-04 13:04:30','2026-06-04 13:04:30'),
(62,4,6,'RO','RO Pressure Check','',1,'2026-03-22',2,'All parameters within normal range.',30,'days','last_service','2026-06-04 13:04:30','2026-06-04 13:04:30'),
(63,5,6,'Washing Machine','Belt Tension Inspection','',1,'2026-03-16',1,'System checked; no major concerns found.',30,'days','last_service','2026-06-04 13:04:30','2026-06-04 13:04:30'),
(64,5,3,'Washing Machine','Drain Hose Replacement','',1,'2026-03-30',3,'Part needed to be ordered, repaired on revisit.',60,'days','last_service','2026-06-04 13:04:30','2026-06-04 13:04:30'),
(65,6,6,'Refrigerator','Backup Cooler Temperature Check','',1,'2026-03-23',4,'System checked; no major concerns found.',14,'days','last_service','2026-06-04 13:04:30','2026-06-04 13:04:30'),
(66,6,4,'AC','AC Duct Deep Cleaning','',1,'2026-03-27',2,'Coils and vents cleaned thoroughly.',45,'days','last_service','2026-06-04 13:04:30','2026-06-04 13:04:30'),
(67,7,6,'RO','Storage Tank Pressure Check','',1,'2026-03-19',5,'Detailed inspection carried out.',45,'days','last_service','2026-06-04 13:04:30','2026-06-04 13:04:30'),
(68,7,5,'TV','Smart TV Setup Configuration','',0,'2026-05-23',1,'New unit installed successfully.',NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(69,8,6,'Refrigerator','Door Gasket Seal Check','',1,'2026-03-17',3,'Inspection report shared with customer.',30,'days','last_service','2026-06-04 13:04:30','2026-06-04 13:04:30'),
(70,8,4,'Refrigerator','Ice Machine Cleaning','',1,'2026-03-25',4,'Deep cleaning performed with disinfectant.',14,'days','last_service','2026-06-04 13:04:30','2026-06-04 13:04:30'),
(71,9,6,'AC','Inspection - AC','tiwofow',0,'2026-06-04',4,'',NULL,NULL,NULL,'2026-06-04 15:11:59','2026-06-04 15:11:59');

/*Table structure for table `fscrm_staff` */

DROP TABLE IF EXISTS `fscrm_staff`;

CREATE TABLE `fscrm_staff` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `avatar` varchar(500) DEFAULT NULL,
  `active_tasks` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `fscrm_staff` */

insert  into `fscrm_staff`(`id`,`name`,`phone`,`avatar`,`active_tasks`,`created_at`,`updated_at`) values 
(1,'Ramesh Yadav','+977-9812345001','https://ui-avatars.com/api/?name=Ramesh+Yadav&background=1DB954&color=fff&size=200',0,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(2,'Suresh Thakur','+977-9812345002','https://ui-avatars.com/api/?name=Suresh+Thakur&background=0B1E3D&color=fff&size=200',0,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(3,'Bikash patel','+977-9812345003','https://ui-avatars.com/api/?name=Bikash+Sah&background=F59E0B&color=fff&size=200',0,'2026-06-04 13:04:30','2026-06-04 15:21:10'),
(4,'Anita Devi','+977-9812345004','https://ui-avatars.com/api/?name=Anita+Devi&background=EF4444&color=fff&size=200',0,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(5,'Manoj Kumar','+977-9812345005','https://ui-avatars.com/api/?name=Manoj+Kumar&background=0EA5E9&color=fff&size=200',0,'2026-06-04 13:04:30','2026-06-04 13:04:30');

/*Table structure for table `fscrm_tasks` */

DROP TABLE IF EXISTS `fscrm_tasks`;

CREATE TABLE `fscrm_tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_id` int(11) DEFAULT NULL,
  `customer_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `status` enum('pending','completed','missed') DEFAULT 'pending',
  `scheduled_date` date DEFAULT NULL,
  `completed_date` date DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `completed_by` varchar(100) DEFAULT NULL,
  `received_name` varchar(100) DEFAULT NULL,
  `received_contact` varchar(100) DEFAULT NULL,
  `signature` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `service_id` (`service_id`),
  KEY `customer_id` (`customer_id`),
  KEY `assigned_to` (`assigned_to`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `fscrm_tasks_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `fscrm_services` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fscrm_tasks_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `fscrm_customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fscrm_tasks_ibfk_3` FOREIGN KEY (`assigned_to`) REFERENCES `fscrm_staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fscrm_tasks_ibfk_4` FOREIGN KEY (`category_id`) REFERENCES `fscrm_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=168 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `fscrm_tasks` */

insert  into `fscrm_tasks`(`id`,`service_id`,`customer_id`,`title`,`status`,`scheduled_date`,`completed_date`,`assigned_to`,`notes`,`category_id`,`completed_by`,`received_name`,`received_contact`,`signature`,`created_at`,`updated_at`) values 
(1,1,1,'RO Filter Change','completed','2026-03-21','2026-03-21',1,'Old filter was clogged, replaced successfully.',2,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(2,1,1,'RO Filter Change','completed','2026-04-20','2026-04-20',1,'Old filter was clogged, replaced successfully.',2,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(3,1,1,'RO Filter Change','completed','2026-05-20','2026-05-21',1,'Standard filter replacement done.',2,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(4,1,1,'RO Filter Change','pending','2026-06-19',NULL,1,'Old filter was clogged, replaced successfully.',2,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(5,2,1,'RO System Inspection','completed','2026-04-30','2026-04-30',4,'System checked; no major concerns found.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(6,2,1,'RO System Inspection','pending','2026-06-14',NULL,4,'System checked; no major concerns found.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(7,3,1,'Refrigerator Deep Cleaning','completed','2026-04-05','2026-04-05',2,'Cleaning completed; unit performing better.',4,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(8,4,1,'Refrigerator Annual Maintenance','completed','2026-03-26','2026-03-26',1,'All components checked and functioning.',1,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(9,5,1,'RO Membrane Replacement','completed','2026-04-20','2026-04-20',4,'Customer reported problem, resolved after inspection.',3,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(10,6,1,'RO System Installation','completed','2026-05-19','2026-05-19',3,'Installation completed and tested.',5,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(11,7,1,'Temperature Calibration Check','completed','2026-03-26','2026-03-26',5,'All parameters within normal range.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(12,7,1,'Temperature Calibration Check','completed','2026-05-10','2026-05-10',5,'Inspection report shared with customer.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(13,8,2,'TV Calibration','missed','2026-03-31',NULL,2,'Inspection report shared with customer.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(14,9,2,'AC Annual Maintenance','completed','2026-03-21','2026-03-21',1,'All components checked and functioning.',1,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(15,9,2,'AC Annual Maintenance','completed','2026-05-05','2026-05-06',1,'Annual checkup completed without issues.',1,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(16,9,2,'AC Annual Maintenance','pending','2026-06-19',NULL,1,'Routine annual maintenance performed.',1,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(17,10,2,'AC Gas Refill','completed','2026-05-05','2026-05-05',3,'Customer reported problem, resolved after inspection.',3,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(18,11,2,'AC Filter Cleaning','completed','2026-04-15','2026-04-16',4,'Standard filter replacement done.',2,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(19,11,2,'AC Filter Cleaning','completed','2026-05-15','2026-05-15',4,'Customer advised on next filter change schedule.',2,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(20,11,2,'AC Filter Cleaning','pending','2026-06-14',NULL,4,'Filter change completed.',2,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(21,12,2,'TV Mounting Service','completed','2026-04-06','2026-04-06',5,'Mounting and setup completed.',5,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(22,13,2,'AC Performance Inspection','completed','2026-03-31','2026-03-31',1,'Minor issues noted, customer advised.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(23,13,2,'AC Performance Inspection','completed','2026-05-15','2026-05-15',1,'All parameters within normal range.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(24,14,2,'TV Display Repair','completed','2026-05-20','2026-05-20',2,'Customer reported problem, resolved after inspection.',3,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(25,15,3,'AC Annual Maintenance','completed','2026-04-30','2026-04-30',1,'Annual checkup completed without issues.',1,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(26,15,3,'AC Annual Maintenance','pending','2026-06-14',NULL,1,'All components checked and functioning.',1,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(27,16,3,'Refrigerator Deep Cleaning','completed','2026-03-26','2026-03-26',4,'Deep cleaning performed with disinfectant.',4,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(28,16,3,'Refrigerator Deep Cleaning','completed','2026-05-10','2026-05-10',4,'Thorough cleaning completed.',4,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(29,17,3,'AC Compressor Repair','completed','2026-04-10','2026-04-11',3,'Repair completed successfully.',3,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(30,18,3,'Door Seal Replacement','completed','2026-04-25','2026-04-25',2,'Part needed to be ordered, repaired on revisit.',3,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(31,19,3,'AC Filter Inspection','completed','2026-04-10','2026-04-11',4,'All parameters within normal range.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(32,19,3,'AC Filter Inspection','completed','2026-05-10','2026-05-11',4,'Inspection report shared with customer.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(33,19,3,'AC Filter Inspection','pending','2026-06-09',NULL,4,'Minor issues noted, customer advised.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(34,20,3,'Commercial Cooler Inspection','completed','2026-03-21','2026-03-22',1,'Detailed inspection carried out.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(35,20,3,'Commercial Cooler Inspection','completed','2026-04-20','2026-04-20',1,'Detailed inspection carried out.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(36,20,3,'Commercial Cooler Inspection','completed','2026-05-20','2026-05-20',1,'Minor issues noted, customer advised.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(37,20,3,'Commercial Cooler Inspection','pending','2026-06-19',NULL,1,'System checked; no major concerns found.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(38,21,3,'Walk-in Cooler Maintenance','completed','2026-04-05','2026-04-05',5,'Annual checkup completed without issues.',1,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(39,21,3,'Walk-in Cooler Maintenance','missed','2026-05-20',NULL,5,'Routine annual maintenance performed.',1,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(40,22,3,'AC Thermostat Calibration','completed','2026-05-25','2026-05-26',2,'Repair completed successfully.',3,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(41,23,4,'RO Filter Change','completed','2026-04-15','2026-04-15',4,'Filter change completed.',2,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(42,23,4,'RO Filter Change','completed','2026-05-15','2026-05-16',4,'Filter replaced with new unit.',2,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(43,23,4,'RO Filter Change','pending','2026-06-14',NULL,4,'Customer advised on next filter change schedule.',2,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(44,24,4,'RO Membrane Replacement','completed','2026-04-15','2026-04-16',1,'Part needed to be ordered, repaired on revisit.',3,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(45,25,4,'Water Quality Test','missed','2026-04-05',NULL,3,'Minor issues noted, customer advised.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(46,25,4,'Water Quality Test','completed','2026-06-04','2026-06-04',3,'Inspection report shared with customer.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(47,26,4,'RO Faucet Installation','completed','2026-05-30','2026-05-30',5,'Customer trained on basic operation.',5,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(48,27,4,'RO System Inspection','completed','2026-03-26','2026-03-27',4,'Minor issues noted, customer advised.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(49,27,4,'RO System Inspection','completed','2026-05-10','2026-05-11',4,'Detailed inspection carried out.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(50,28,5,'Drum Bearing Repair','completed','2026-04-30','2026-04-30',3,'Customer reported problem, resolved after inspection.',3,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(51,29,5,'Washing Machine Installation','completed','2026-05-04','2026-05-04',2,'New unit installed successfully.',5,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(52,30,5,'Annual Maintenance','completed','2026-03-21','2026-03-21',1,'Routine annual maintenance performed.',1,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(53,30,5,'Annual Maintenance','completed','2026-05-05','2026-05-05',1,'All components checked and functioning.',1,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(54,30,5,'Annual Maintenance','pending','2026-06-19',NULL,1,'System running efficiently after service.',1,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(55,31,5,'Performance Inspection','completed','2026-04-15','2026-04-16',4,'Inspection report shared with customer.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(56,31,5,'Performance Inspection','completed','2026-05-15','2026-05-15',4,'All parameters within normal range.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(57,31,5,'Performance Inspection','pending','2026-06-14',NULL,4,'Minor issues noted, customer advised.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(58,32,5,'Drum Deep Cleaning','completed','2026-04-05','2026-04-05',5,'Removed accumulated dust and debris.',4,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(59,32,5,'Drum Deep Cleaning','missed','2026-06-04',NULL,5,'Removed accumulated dust and debris.',4,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(60,33,5,'Water Inlet Valve Replacement','completed','2026-05-15','2026-05-15',2,'Customer reported problem, resolved after inspection.',3,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(61,34,6,'Vaccine Storage Unit Maintenance','completed','2026-04-15','2026-04-15',1,'Annual checkup completed without issues.',1,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(62,34,6,'Vaccine Storage Unit Maintenance','completed','2026-05-15','2026-05-15',1,'Routine annual maintenance performed.',1,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(63,34,6,'Vaccine Storage Unit Maintenance','pending','2026-06-14',NULL,1,'Preventive maintenance completed.',1,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(64,35,6,'Temperature Log Inspection','completed','2026-03-21','2026-03-22',4,'All parameters within normal range.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(65,35,6,'Temperature Log Inspection','completed','2026-04-04','2026-04-04',4,'All parameters within normal range.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(66,35,6,'Temperature Log Inspection','completed','2026-04-18','2026-04-18',4,'Detailed inspection carried out.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(67,35,6,'Temperature Log Inspection','completed','2026-05-02','2026-05-03',4,'Minor issues noted, customer advised.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(68,35,6,'Temperature Log Inspection','completed','2026-05-16','2026-05-16',4,'All parameters within normal range.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(69,35,6,'Temperature Log Inspection','completed','2026-05-30','2026-05-30',4,'All parameters within normal range.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(70,35,6,'Temperature Log Inspection','pending','2026-06-13',NULL,4,'Inspection report shared with customer.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(71,36,6,'AC Annual Maintenance','missed','2026-03-31',NULL,2,'Annual checkup completed without issues.',1,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(72,36,6,'AC Annual Maintenance','completed','2026-05-15','2026-05-15',2,'System running efficiently after service.',1,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(73,37,6,'Coil Deep Cleaning','completed','2026-03-26','2026-03-26',3,'Coils and vents cleaned thoroughly.',4,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(74,37,6,'Coil Deep Cleaning','completed','2026-05-25','2026-05-26',3,'Removed accumulated dust and debris.',4,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(75,38,6,'AC Air Quality Inspection','missed','2026-04-15',NULL,4,'All parameters within normal range.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(76,38,6,'AC Air Quality Inspection','completed','2026-05-15','2026-05-15',4,'System checked; no major concerns found.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(77,38,6,'AC Air Quality Inspection','pending','2026-06-14',NULL,4,'All parameters within normal range.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(78,39,6,'Thermostat Calibration Repair','completed','2026-05-10','2026-05-10',1,'Customer reported problem, resolved after inspection.',3,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(79,40,6,'Backup Unit Installation','completed','2026-03-26','2026-03-27',5,'Installation done as per customer preference.',5,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(80,41,6,'AC Drain Line Repair','missed','2026-05-30',NULL,2,'Part needed to be ordered, repaired on revisit.',3,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(81,42,7,'RO Filter Change','completed','2026-04-15','2026-04-15',4,'Standard filter replacement done.',2,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(82,42,7,'RO Filter Change','completed','2026-05-15','2026-05-15',4,'Standard filter replacement done.',2,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(83,42,7,'RO Filter Change','pending','2026-06-14',NULL,4,'Standard filter replacement done.',2,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(84,43,7,'TV Picture Calibration','missed','2026-04-05',NULL,2,'Detailed inspection carried out.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(85,44,7,'RO Water Quality Inspection','completed','2026-03-21','2026-03-21',1,'System checked; no major concerns found.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(86,44,7,'RO Water Quality Inspection','missed','2026-05-05',NULL,1,'All parameters within normal range.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(87,44,7,'RO Water Quality Inspection','pending','2026-06-19',NULL,1,'Minor issues noted, customer advised.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(88,45,7,'TV Wall Mount Repair','completed','2026-04-23','2026-04-23',3,'Customer reported problem, resolved after inspection.',3,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(89,46,7,'RO Pressure Pump Repair','completed','2026-05-17','2026-05-18',5,'Repair completed successfully.',3,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(90,47,7,'RO System Audit','completed','2026-04-05','2026-04-05',4,'Detailed inspection carried out.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(91,47,7,'RO System Audit','completed','2026-06-04','2026-06-04',4,'System checked; no major concerns found.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(92,48,8,'Display Cooler Deep Cleaning','missed','2026-03-21',NULL,1,'Removed accumulated dust and debris.',4,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(93,48,8,'Display Cooler Deep Cleaning','completed','2026-04-20','2026-04-21',1,'Thorough cleaning completed.',4,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(94,48,8,'Display Cooler Deep Cleaning','completed','2026-05-20','2026-05-21',1,'Cleaning completed; unit performing better.',4,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(95,48,8,'Display Cooler Deep Cleaning','pending','2026-06-19',NULL,1,'Deep cleaning performed with disinfectant.',4,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(96,49,8,'Compressor Repair','completed','2026-04-15','2026-04-15',3,'Customer reported problem, resolved after inspection.',3,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(97,50,8,'Commercial Fridge Maintenance','completed','2026-04-15','2026-04-15',4,'Annual checkup completed without issues.',1,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(98,50,8,'Commercial Fridge Maintenance','completed','2026-05-15','2026-05-15',4,'Routine annual maintenance performed.',1,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(99,50,8,'Commercial Fridge Maintenance','pending','2026-06-14',NULL,4,'Annual checkup completed without issues.',1,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(100,51,8,'Temperature Calibration Check','completed','2026-03-26','2026-03-27',2,'System checked; no major concerns found.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(101,51,8,'Temperature Calibration Check','completed','2026-04-09','2026-04-09',2,'System checked; no major concerns found.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(102,51,8,'Temperature Calibration Check','missed','2026-04-23',NULL,2,'All parameters within normal range.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(103,51,8,'Temperature Calibration Check','completed','2026-05-07','2026-05-08',2,'Inspection report shared with customer.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(104,51,8,'Temperature Calibration Check','completed','2026-05-21','2026-05-21',2,'System checked; no major concerns found.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(105,51,8,'Temperature Calibration Check','missed','2026-06-04',NULL,2,'System checked; no major concerns found.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(106,51,8,'Temperature Calibration Check','pending','2026-06-18',NULL,2,'System checked; no major concerns found.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(107,52,8,'New Display Unit Installation','completed','2026-06-02','2026-06-02',5,'Customer trained on basic operation.',5,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(108,53,8,'Condenser Coil Inspection','missed','2026-03-31',NULL,1,'System checked; no major concerns found.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(109,53,8,'Condenser Coil Inspection','completed','2026-05-15','2026-05-15',1,'All parameters within normal range.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(110,54,8,'Storage Room Cooler Cleaning','completed','2026-05-05','2026-05-05',4,'Cleaning completed; unit performing better.',4,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(111,55,8,'Door Hinge Replacement','completed','2026-05-27','2026-05-28',2,'Repair completed successfully.',3,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(112,56,1,'RO Preventive Inspection','completed','2026-03-24','2026-03-24',2,'All parameters within normal range.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(113,56,1,'RO Preventive Inspection','completed','2026-04-07','2026-04-07',2,'System checked; no major concerns found.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(114,56,1,'RO Preventive Inspection','missed','2026-04-21',NULL,2,'Detailed inspection carried out.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(115,56,1,'RO Preventive Inspection','completed','2026-05-05','2026-05-05',2,'Detailed inspection carried out.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(116,56,1,'RO Preventive Inspection','completed','2026-05-19','2026-05-20',2,'System checked; no major concerns found.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(117,56,1,'RO Preventive Inspection','completed','2026-06-02','2026-06-02',2,'Detailed inspection carried out.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(118,56,1,'RO Preventive Inspection','pending','2026-06-16',NULL,2,'All parameters within normal range.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(119,57,1,'Condenser Coil Cleaning','completed','2026-04-17','2026-04-18',5,'Cleaning completed; unit performing better.',4,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(120,57,1,'Condenser Coil Cleaning','missed','2026-05-17',NULL,5,'Removed accumulated dust and debris.',4,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(121,57,1,'Condenser Coil Cleaning','pending','2026-06-16',NULL,5,'Coils and vents cleaned thoroughly.',4,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(122,58,2,'AC Condenser Cleaning','completed','2026-04-13','2026-04-13',3,'Thorough cleaning completed.',4,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(123,58,2,'AC Condenser Cleaning','completed','2026-05-13','2026-05-14',3,'Deep cleaning performed with disinfectant.',4,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(124,58,2,'AC Condenser Cleaning','pending','2026-06-12',NULL,3,'Removed accumulated dust and debris.',4,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(125,59,2,'TV Surge Protector Check','completed','2026-03-28','2026-03-29',1,'Inspection report shared with customer.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(126,59,2,'TV Surge Protector Check','completed','2026-05-12','2026-05-12',1,'System checked; no major concerns found.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(127,60,3,'Refrigerant Level Check','completed','2026-04-19','2026-04-19',4,'Detailed inspection carried out.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(128,60,3,'Refrigerant Level Check','completed','2026-05-19','2026-05-20',4,'All parameters within normal range.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(129,60,3,'Refrigerant Level Check','pending','2026-06-18',NULL,4,'Inspection report shared with customer.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(130,61,3,'Kitchen Exhaust Inspection','missed','2026-03-26',NULL,5,'Minor issues noted, customer advised.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(131,61,3,'Kitchen Exhaust Inspection','missed','2026-04-09',NULL,5,'Detailed inspection carried out.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(132,61,3,'Kitchen Exhaust Inspection','completed','2026-04-23','2026-04-23',5,'Inspection report shared with customer.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(133,61,3,'Kitchen Exhaust Inspection','missed','2026-05-07',NULL,5,'Inspection report shared with customer.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(134,61,3,'Kitchen Exhaust Inspection','completed','2026-05-21','2026-05-22',5,'All parameters within normal range.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(135,61,3,'Kitchen Exhaust Inspection','missed','2026-06-04',NULL,5,'All parameters within normal range.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(136,61,3,'Kitchen Exhaust Inspection','pending','2026-06-18',NULL,5,'System checked; no major concerns found.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(137,62,4,'RO Pressure Check','missed','2026-03-22',NULL,2,'Inspection report shared with customer.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(138,62,4,'RO Pressure Check','completed','2026-04-21','2026-04-21',2,'Detailed inspection carried out.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(139,62,4,'RO Pressure Check','completed','2026-05-21','2026-05-22',2,'Detailed inspection carried out.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(140,63,5,'Belt Tension Inspection','completed','2026-04-15','2026-04-15',1,'Inspection report shared with customer.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(141,63,5,'Belt Tension Inspection','completed','2026-05-15','2026-05-15',1,'System checked; no major concerns found.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(142,63,5,'Belt Tension Inspection','pending','2026-06-14',NULL,1,'All parameters within normal range.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(143,64,5,'Drain Hose Replacement','completed','2026-03-30','2026-03-30',3,'Faulty component identified and replaced.',3,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(144,64,5,'Drain Hose Replacement','missed','2026-05-29',NULL,3,'Faulty component identified and replaced.',3,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(145,65,6,'Backup Cooler Temperature Check','completed','2026-03-23','2026-03-23',4,'All parameters within normal range.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(146,65,6,'Backup Cooler Temperature Check','completed','2026-04-06','2026-04-06',4,'Minor issues noted, customer advised.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(147,65,6,'Backup Cooler Temperature Check','completed','2026-04-20','2026-04-20',4,'All parameters within normal range.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(148,65,6,'Backup Cooler Temperature Check','completed','2026-05-04','2026-05-04',4,'All parameters within normal range.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(149,65,6,'Backup Cooler Temperature Check','completed','2026-05-18','2026-05-18',4,'Inspection report shared with customer.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(150,65,6,'Backup Cooler Temperature Check','completed','2026-06-01','2026-06-02',4,'All parameters within normal range.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(151,65,6,'Backup Cooler Temperature Check','pending','2026-06-15',NULL,4,'Inspection report shared with customer.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(152,66,6,'AC Duct Deep Cleaning','missed','2026-03-27',NULL,2,'Coils and vents cleaned thoroughly.',4,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(153,66,6,'AC Duct Deep Cleaning','missed','2026-05-11',NULL,2,'Thorough cleaning completed.',4,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(154,67,7,'Storage Tank Pressure Check','missed','2026-05-03',NULL,5,'Minor issues noted, customer advised.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(155,67,7,'Storage Tank Pressure Check','pending','2026-06-17',NULL,5,'Detailed inspection carried out.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(156,68,7,'Smart TV Setup Configuration','completed','2026-05-23','2026-05-23',1,'Customer trained on basic operation.',5,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(157,69,8,'Door Gasket Seal Check','completed','2026-04-16','2026-04-17',3,'All parameters within normal range.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(158,69,8,'Door Gasket Seal Check','missed','2026-05-16',NULL,3,'Minor issues noted, customer advised.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(159,69,8,'Door Gasket Seal Check','pending','2026-06-15',NULL,3,'Minor issues noted, customer advised.',6,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(160,70,8,'Ice Machine Cleaning','completed','2026-03-25','2026-03-25',4,'Thorough cleaning completed.',4,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(161,70,8,'Ice Machine Cleaning','completed','2026-04-08','2026-04-08',4,'Coils and vents cleaned thoroughly.',4,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(162,70,8,'Ice Machine Cleaning','completed','2026-04-22','2026-04-22',4,'Thorough cleaning completed.',4,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(163,70,8,'Ice Machine Cleaning','completed','2026-05-06','2026-05-06',4,'Removed accumulated dust and debris.',4,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(164,70,8,'Ice Machine Cleaning','missed','2026-05-20',NULL,4,'Thorough cleaning completed.',4,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(165,70,8,'Ice Machine Cleaning','completed','2026-06-03','2026-06-04',4,'Removed accumulated dust and debris.',4,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(166,70,8,'Ice Machine Cleaning','pending','2026-06-17',NULL,4,'Coils and vents cleaned thoroughly.',4,NULL,NULL,NULL,NULL,'2026-06-04 13:04:30','2026-06-04 13:04:30'),
(167,71,9,'Inspection - AC','pending','2026-06-04',NULL,4,'',6,NULL,NULL,NULL,NULL,'2026-06-04 15:11:59','2026-06-04 15:11:59');

/*Table structure for table `fscrm_users` */

DROP TABLE IF EXISTS `fscrm_users`;

CREATE TABLE `fscrm_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'user',
  `staff_id` int(11) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `fscrm_users` */

insert  into `fscrm_users`(`id`,`name`,`email`,`password`,`role`,`staff_id`,`avatar`,`created_at`) values 
(1,'Admin User','admin@demo.com','$2y$12$QKRRQnnSRkak5wPaPRzC9OKB6.kCqqwEhmZVapSiwEL2EipEgJTNa','admin',NULL,NULL,'2026-06-04 13:04:30'),
(2,'Ramesh Yadav','ramesh@demo.com','$2y$12$DYEgy/C0M4SV3xmwIeDgN.04G6Uho6jbBZmNlSedaFjasnGpL1KMG','staff',1,NULL,'2026-06-04 15:19:22'),
(3,'Suresh Thakur','suresh@demo.com','$2y$12$DYEgy/C0M4SV3xmwIeDgN.04G6Uho6jbBZmNlSedaFjasnGpL1KMG','staff',2,NULL,'2026-06-04 15:19:22'),
(4,'Bikash Sah','bikash@demo.com','$2y$12$DYEgy/C0M4SV3xmwIeDgN.04G6Uho6jbBZmNlSedaFjasnGpL1KMG','staff',3,NULL,'2026-06-04 15:19:22'),
(5,'Anita Devi','anita@demo.com','$2y$12$DYEgy/C0M4SV3xmwIeDgN.04G6Uho6jbBZmNlSedaFjasnGpL1KMG','staff',4,NULL,'2026-06-04 15:19:22'),
(6,'Manoj Kumar','manoj@demo.com','$2y$12$DYEgy/C0M4SV3xmwIeDgN.04G6Uho6jbBZmNlSedaFjasnGpL1KMG','staff',5,NULL,'2026-06-04 15:19:22');

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
