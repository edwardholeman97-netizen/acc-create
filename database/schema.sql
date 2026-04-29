-- CDS Account Submissions & Admin Users
-- Run: mysql -u user -p database_name < database/schema.sql

CREATE TABLE IF NOT EXISTS `cds_submissions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `submission_uid` VARCHAR(40) NOT NULL,
    `account_id` VARCHAR(20) DEFAULT NULL,
    `cse_account_id` VARCHAR(20) DEFAULT NULL,
    `form_data` JSON NOT NULL,
    `image_paths` JSON DEFAULT NULL,
    `supporting_documents` JSON DEFAULT NULL,
    `status` ENUM('pending_review','awaiting_edit','submitted_to_cse') NOT NULL DEFAULT 'pending_review',
    `admin_note` TEXT DEFAULT NULL,
    `submitted_to_cse_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_submission_uid` (`submission_uid`),
    KEY `idx_account_id` (`account_id`),
    KEY `idx_cse_account_id` (`cse_account_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
    CONSTRAINT `fk_token_submission` FOREIGN KEY (`submission_id`) REFERENCES `cds_submissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `admin_users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(255) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('admin','superadmin') NOT NULL DEFAULT 'admin',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `admin_password_resets` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `admin_user_id` INT UNSIGNED NOT NULL,
    `token_hash` CHAR(64) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_token_hash` (`token_hash`),
    KEY `idx_admin_user_id` (`admin_user_id`),
    CONSTRAINT `fk_apr_admin` FOREIGN KEY (`admin_user_id`) REFERENCES `admin_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
