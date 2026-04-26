-- Migration: Admin roles (admin / superadmin)
-- Adds a `role` column to `admin_users`. Existing rows default to 'admin'
-- so behavior is unchanged. Superadmins bypass field-level locks and the
-- post-CSE-submission UI lockdown in the admin pages.
-- Run: mysql -u user -p database_name < database/migrations/2026_admin_roles.sql

ALTER TABLE `admin_users`
    ADD COLUMN `role` ENUM('admin','superadmin') NOT NULL DEFAULT 'admin' AFTER `password_hash`;
