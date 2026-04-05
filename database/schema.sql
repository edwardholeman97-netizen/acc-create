-- CDS Account Submissions & Admin Users
-- Run: mysql -u user -p database_name < database/schema.sql

CREATE TABLE IF NOT EXISTS `cds_submissions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `account_id` VARCHAR(20) NOT NULL,
    `form_data` JSON NOT NULL,
    `image_paths` JSON DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_account_id` (`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `admin_users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(255) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
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
