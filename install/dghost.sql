-- 硬盘空间 数据库安装脚本
-- 直接使用已有的 yingpankongjian 数据库

CREATE TABLE IF NOT EXISTS `admins` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `email` VARCHAR(100),
    `login_attempts` INT DEFAULT 0,
    `locked_until` DATETIME DEFAULT NULL,
    `last_login` DATETIME DEFAULT NULL,
    `last_login_ip` VARCHAR(45),
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `folders` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `parent_id` INT UNSIGNED DEFAULT NULL,
    `sort_order` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`parent_id`) REFERENCES `folders`(`id`) ON DELETE CASCADE,
    INDEX `idx_parent` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `files` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `folder_id` INT UNSIGNED DEFAULT NULL,
    `original_name` VARCHAR(255) NOT NULL,
    `stored_name` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(500) DEFAULT NULL,
    `storage_type` VARCHAR(20) NOT NULL DEFAULT 'telegram',
    `telegram_file_id` VARCHAR(255) DEFAULT NULL,
    `telegram_unique_id` VARCHAR(255) DEFAULT NULL,
    `telegram_message_id` BIGINT DEFAULT NULL,
    `telegram_chat_id` VARCHAR(64) DEFAULT NULL,
    `telegram_file_name` VARCHAR(255) DEFAULT NULL,
    `telegram_file_size` BIGINT DEFAULT NULL,
    `telegram_mime_type` VARCHAR(100) DEFAULT NULL,
    `file_size` BIGINT UNSIGNED DEFAULT 0,
    `file_type` VARCHAR(50),
    `extension` VARCHAR(20),
    `description` TEXT,
    `download_count` INT UNSIGNED DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`folder_id`) REFERENCES `folders`(`id`) ON DELETE SET NULL,
    INDEX `idx_folder` (`folder_id`),
    INDEX `idx_extension` (`extension`),
    INDEX `idx_downloads` (`download_count`),
    INDEX `idx_storage_type` (`storage_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `download_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `file_id` INT UNSIGNED NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `user_agent` VARCHAR(500),
    `referer` VARCHAR(500),
    `downloaded_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`file_id`) REFERENCES `files`(`id`) ON DELETE CASCADE,
    INDEX `idx_file` (`file_id`),
    INDEX `idx_ip` (`ip_address`),
    INDEX `idx_time` (`downloaded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `visit_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `ip_address` VARCHAR(45) NOT NULL,
    `page` VARCHAR(255),
    `user_agent` VARCHAR(500),
    `visited_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ip` (`ip_address`),
    INDEX `idx_time` (`visited_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `admins` (`username`, `password`, `email`) VALUES 
('admin', '$2y$10$kcnMefBgkXLSPm2Bdtwl..q6Fz3OiqoBKv/g1AL8uGgryQ0CJ/XCm', 'www@dkewl.com');
