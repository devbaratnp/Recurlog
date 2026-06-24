CREATE TABLE IF NOT EXISTS `fscrm_recurring_completion_notes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task_id` int(11) NOT NULL,
  `recurring_task_id` int(11) DEFAULT NULL,
  `customer_id` int(11) NOT NULL,
  `series_key` varchar(191) NOT NULL,
  `note` text NOT NULL,
  `completed_date` date DEFAULT NULL,
  `noted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_recurring_completion_notes_task` (`task_id`),
  KEY `idx_recurring_completion_notes_series` (`series_key`),
  KEY `idx_recurring_completion_notes_recurring_task` (`recurring_task_id`),
  KEY `idx_recurring_completion_notes_customer` (`customer_id`),
  CONSTRAINT `fk_recurring_completion_note_task` FOREIGN KEY (`task_id`) REFERENCES `fscrm_tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_recurring_completion_note_recurring_task` FOREIGN KEY (`recurring_task_id`) REFERENCES `fscrm_recurring_tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_recurring_completion_note_customer` FOREIGN KEY (`customer_id`) REFERENCES `fscrm_customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
