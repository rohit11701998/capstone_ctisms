-- ============================================================
-- CTISMS Version 1 - Week 1 to 4 Development
-- Simple database schema - beginner level
-- ============================================================
-- Import this into phpMyAdmin
-- Database name: ctisms_v1
-- ============================================================

CREATE DATABASE IF NOT EXISTS `ctisms_v1`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `ctisms_v1`;

-- Users table (basic - no roles yet, added in Week 3)
CREATE TABLE `users` (
  `id`         INT          NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(100) NOT NULL,
  `email`      VARCHAR(150) NOT NULL,
  `password`   VARCHAR(255) NOT NULL,
  `role`       VARCHAR(20)  NOT NULL DEFAULT 'customer',
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tickets table (basic - added Week 3-4)
CREATE TABLE `tickets` (
  `id`          INT          NOT NULL AUTO_INCREMENT,
  `title`       VARCHAR(255) NOT NULL,
  `description` TEXT         NOT NULL,
  `status`      VARCHAR(30)  NOT NULL DEFAULT 'Pending',
  `user_id`     INT          NOT NULL,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample user (password = Admin@1234, run setup.php to generate hash)
INSERT INTO `users` (`name`, `email`, `password`, `role`) VALUES
('Test User', 'test@ctisms.com', 'PLACEHOLDER', 'customer');

-- Sample tickets
INSERT INTO `tickets` (`title`, `description`, `status`, `user_id`) VALUES
('Cannot login to email', 'My email account keeps saying wrong password.', 'Pending', 1),
('Printer not working',   'The office printer shows offline status.',      'In Progress', 1);
