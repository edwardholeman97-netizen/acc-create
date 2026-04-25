-- Migration: Pending review workflow + client edit-link tokens
-- Adds DB-only first-submission flow (status), separates internal id (submission_uid)
-- from CSE id (cse_account_id), and creates the token table for client edit links.
-- Run: mysql -u user -p database_name < database/migrations/2026_pending_review.sql

-- 1) Add new columns (idempotent guards via try/catch in app code; here assume fresh run).
ALTER TABLE `cds_submissions`
    ADD COLUMN `submission_uid` VARCHAR(40) NOT NULL DEFAULT '' AFTER `id`,
    ADD COLUMN `cse_account_id` VARCHAR(20) DEFAULT NULL AFTER `account_id`,
    ADD COLUMN `status` ENUM('pending_review','awaiting_edit','submitted_to_cse')
        NOT NULL DEFAULT 'submitted_to_cse' AFTER `image_paths`,
    ADD COLUMN `admin_note` TEXT DEFAULT NULL AFTER `status`,
    ADD COLUMN `submitted_to_cse_at` DATETIME DEFAULT NULL AFTER `admin_note`;

-- 2) Backfill existing rows (treat all pre-migration rows as already submitted to CSE).
UPDATE `cds_submissions`
   SET `submission_uid` = CONCAT('legacy-', `id`)
 WHERE `submission_uid` = '' OR `submission_uid` IS NULL;

UPDATE `cds_submissions`
   SET `cse_account_id` = `account_id`
 WHERE `cse_account_id` IS NULL AND `account_id` IS NOT NULL;

UPDATE `cds_submissions`
   SET `submitted_to_cse_at` = `created_at`
 WHERE `submitted_to_cse_at` IS NULL AND `status` = 'submitted_to_cse';

-- 3) Swap the unique constraint from account_id -> submission_uid.
ALTER TABLE `cds_submissions`
    DROP INDEX `uk_account_id`,
    MODIFY `account_id` VARCHAR(20) DEFAULT NULL,
    ADD UNIQUE KEY `uk_submission_uid` (`submission_uid`),
    ADD KEY `idx_account_id` (`account_id`),
    ADD KEY `idx_cse_account_id` (`cse_account_id`),
    ADD KEY `idx_status` (`status`);

-- 4) New table for client edit-link tokens (3-day default lifetime; the link
--    becomes useless on its own once the row flips to status=submitted_to_cse).
CREATE TABLE IF NOT EXISTS `submission_edit_tokens` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `submission_id` INT UNSIGNED NOT NULL,
    `token_hash` CHAR(64) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `created_by_admin_id` INT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `last_used_at` DATETIME DEFAULT NULL,
    UNIQUE KEY `uk_token_hash` (`token_hash`),
    KEY `idx_submission` (`submission_id`),
    CONSTRAINT `fk_token_submission` FOREIGN KEY (`submission_id`)
        REFERENCES `cds_submissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
