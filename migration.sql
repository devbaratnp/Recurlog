-- Migration: Add plain_password column to fscrm_users
-- Run this on the production database

ALTER TABLE fscrm_users
  ADD COLUMN plain_password VARCHAR(255) DEFAULT NULL AFTER password;

-- Existing users will have NULL plain_password.
-- They'll need to have their passwords reset by admin via the app to populate this field.
