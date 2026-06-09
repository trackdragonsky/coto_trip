-- Database export for the PHP version of Cô Tô Trip.
-- Import this file in phpMyAdmin/XAMPP; it creates and uses the `db` database by default.

CREATE DATABASE IF NOT EXISTS `db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `db`;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `ai_messages`;
DROP TABLE IF EXISTS `itineraries`;
DROP TABLE IF EXISTS `gallery`;
DROP TABLE IF EXISTS `collections`;
DROP TABLE IF EXISTS `expenses`;
DROP TABLE IF EXISTS `members`;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE `members` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(120) NOT NULL,
  `avatar` VARCHAR(255) NULL,
  `role_name` VARCHAR(120) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `expenses` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(180) NOT NULL,
  `category` VARCHAR(120) NOT NULL,
  `note` TEXT NULL,
  `payer_id` INT NOT NULL,
  `amount` DECIMAL(14,2) NOT NULL,
  `split_evenly` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_expenses_payer_id` (`payer_id`),
  CONSTRAINT `fk_expenses_payer`
    FOREIGN KEY (`payer_id`) REFERENCES `members` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `collections` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `member_id` INT NOT NULL,
  `amount` DECIMAL(14,2) NOT NULL,
  `collected_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_collections_member` (`member_id`),
  CONSTRAINT `fk_collections_member`
    FOREIGN KEY (`member_id`) REFERENCES `members` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `gallery` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `image_url` VARCHAR(500) NOT NULL,
  `caption` VARCHAR(255) NULL,
  `uploaded_by` INT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_gallery_uploaded_by` (`uploaded_by`),
  CONSTRAINT `fk_gallery_uploaded_by`
    FOREIGN KEY (`uploaded_by`) REFERENCES `members` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `itineraries` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(180) NOT NULL,
  `trip_date` DATE NOT NULL,
  `trip_time` TIME NOT NULL,
  `activity_type` VARCHAR(120) NOT NULL,
  `detail` TEXT NULL,
  `created_by` INT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_itineraries_created_by` (`created_by`),
  INDEX `idx_itineraries_trip_time` (`trip_date`, `trip_time`),
  CONSTRAINT `fk_itineraries_created_by`
    FOREIGN KEY (`created_by`) REFERENCES `members` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ai_messages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sender` VARCHAR(40) NOT NULL,
  `message` TEXT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_ai_messages_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `members` (`id`, `name`, `avatar`, `role_name`) VALUES
  (1, 'Long', 'static/images/long.jpg', 'Leader'),
  (2, 'Hoa', 'static/images/hoa.jpg', 'Photographer'),
  (3, 'Linh', 'static/images/linh.jpg', 'Member'),
  (4, 'LAnh', 'static/images/lanh.jpg', 'Member'),
  (5, 'Lan', 'static/images/lan.jpg', 'Planner'),
  (6, 'Bắc', 'static/images/bac.jpg', 'Member');

INSERT INTO `itineraries` (`title`, `trip_date`, `trip_time`, `activity_type`, `detail`, `created_by`) VALUES
  ('Tàu cao tốc ra đảo', '2026-06-20', '08:00:00', 'Di chuyển', 'Bến Vân Đồn, chuẩn bị căn cước để làm thủ tục.', 1),
  ('Check-in khách sạn', '2026-06-20', '11:30:00', 'Khách sạn', 'Gửi hành lý, nghỉ ngơi và ăn trưa.', 5);
