-- ============================================================
-- Migration: Link existing recurring tasks (idempotent)
-- Run in phpMyAdmin SQL tab
-- ============================================================

-- 1. Link existing task instances to recurring definitions
--    (handles collation mismatch between tables)
UPDATE `fscrm_tasks` t
JOIN `fscrm_recurring_tasks` rt
  ON rt.`customer_id` = t.`customer_id`
  AND rt.`title` = t.`title` COLLATE utf8mb4_unicode_ci
SET t.`recurring_task_id` = rt.`id`
WHERE t.`is_recurring` = 1
  AND t.`rec_value` IS NOT NULL
  AND t.`recurring_task_id` IS NULL;

-- 2. Verify
SELECT 'fscrm_recurring_tasks' AS `table`, COUNT(*) AS `count` FROM fscrm_recurring_tasks
UNION ALL
SELECT 'linked_tasks', COUNT(*) FROM fscrm_tasks WHERE recurring_task_id IS NOT NULL
UNION ALL
SELECT 'unlinked_recurring', COUNT(*) FROM fscrm_tasks WHERE is_recurring = 1 AND recurring_task_id IS NULL;
