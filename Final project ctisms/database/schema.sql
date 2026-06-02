-- ============================================================
-- CTISMS - Customer Ticketing & IT Support Management System
-- Database Schema v1.0
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS `ctisms` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `ctisms`;

-- ============================================================
-- TABLE: users
-- ============================================================
CREATE TABLE `users` (
  `id`             INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `name`           VARCHAR(100)     NOT NULL,
  `email`          VARCHAR(191)     NOT NULL,
  `password`       VARCHAR(255)     NOT NULL,
  `role`           ENUM('customer','technician','admin') NOT NULL DEFAULT 'customer',
  `phone`          VARCHAR(20)      DEFAULT NULL,
  `department`     VARCHAR(100)     DEFAULT NULL,
  `avatar`         VARCHAR(255)     DEFAULT NULL,
  `is_active`      TINYINT(1)       NOT NULL DEFAULT 1,
  `last_login`     DATETIME         DEFAULT NULL,
  `created_at`     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `idx_users_role` (`role`),
  KEY `idx_users_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: categories
-- ============================================================
CREATE TABLE `categories` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(100)  NOT NULL,
  `description` TEXT          DEFAULT NULL,
  `sla_hours`   INT           NOT NULL DEFAULT 24 COMMENT 'SLA response time in hours',
  `is_active`   TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categories_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: tickets
-- ============================================================
CREATE TABLE `tickets` (
  `id`              INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `ticket_number`   VARCHAR(20)      NOT NULL COMMENT 'Human-readable ticket ID e.g. TKT-00001',
  `title`           VARCHAR(255)     NOT NULL,
  `description`     TEXT             NOT NULL,
  `category_id`     INT UNSIGNED     NOT NULL,
  `priority`        ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `status`          ENUM('submitted','open','in_progress','awaiting_parts','completed','closed') NOT NULL DEFAULT 'submitted',
  `customer_id`     INT UNSIGNED     NOT NULL,
  `technician_id`   INT UNSIGNED     DEFAULT NULL,
  `sla_deadline`    DATETIME         DEFAULT NULL,
  `sla_breached`    TINYINT(1)       NOT NULL DEFAULT 0,
  `resolution`      TEXT             DEFAULT NULL,
  `closed_at`       DATETIME         DEFAULT NULL,
  `created_at`      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tickets_number` (`ticket_number`),
  KEY `idx_tickets_status` (`status`),
  KEY `idx_tickets_priority` (`priority`),
  KEY `idx_tickets_customer` (`customer_id`),
  KEY `idx_tickets_technician` (`technician_id`),
  KEY `idx_tickets_category` (`category_id`),
  KEY `idx_tickets_created` (`created_at`),
  CONSTRAINT `fk_tickets_customer`    FOREIGN KEY (`customer_id`)   REFERENCES `users`(`id`)       ON DELETE RESTRICT,
  CONSTRAINT `fk_tickets_technician`  FOREIGN KEY (`technician_id`) REFERENCES `users`(`id`)       ON DELETE SET NULL,
  CONSTRAINT `fk_tickets_category`    FOREIGN KEY (`category_id`)   REFERENCES `categories`(`id`)  ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: comments
-- ============================================================
CREATE TABLE `comments` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `ticket_id`   INT UNSIGNED  NOT NULL,
  `user_id`     INT UNSIGNED  NOT NULL,
  `body`        TEXT          NOT NULL,
  `is_internal` TINYINT(1)    NOT NULL DEFAULT 0 COMMENT 'Internal notes visible only to staff',
  `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_comments_ticket` (`ticket_id`),
  KEY `idx_comments_user` (`user_id`),
  CONSTRAINT `fk_comments_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_comments_user`   FOREIGN KEY (`user_id`)   REFERENCES `users`(`id`)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: attachments
-- ============================================================
CREATE TABLE `attachments` (
  `id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `ticket_id`     INT UNSIGNED  NOT NULL,
  `uploaded_by`   INT UNSIGNED  NOT NULL,
  `filename`      VARCHAR(255)  NOT NULL,
  `original_name` VARCHAR(255)  NOT NULL,
  `file_size`     INT UNSIGNED  NOT NULL,
  `mime_type`     VARCHAR(100)  NOT NULL,
  `created_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_attachments_ticket` (`ticket_id`),
  CONSTRAINT `fk_attachments_ticket`   FOREIGN KEY (`ticket_id`)   REFERENCES `tickets`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_attachments_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: notifications
-- ============================================================
CREATE TABLE `notifications` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `user_id`     INT UNSIGNED  NOT NULL,
  `ticket_id`   INT UNSIGNED  DEFAULT NULL,
  `type`        ENUM('ticket_created','ticket_assigned','status_changed','comment_added','sla_warning') NOT NULL,
  `title`       VARCHAR(255)  NOT NULL,
  `message`     TEXT          NOT NULL,
  `is_read`     TINYINT(1)    NOT NULL DEFAULT 0,
  `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notifications_user`   (`user_id`),
  KEY `idx_notifications_ticket` (`ticket_id`),
  KEY `idx_notifications_read`   (`is_read`),
  CONSTRAINT `fk_notifications_user`   FOREIGN KEY (`user_id`)   REFERENCES `users`(`id`)   ON DELETE CASCADE,
  CONSTRAINT `fk_notifications_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: activity_logs
-- ============================================================
CREATE TABLE `activity_logs` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `user_id`     INT UNSIGNED  DEFAULT NULL,
  `ticket_id`   INT UNSIGNED  DEFAULT NULL,
  `action`      VARCHAR(100)  NOT NULL,
  `description` TEXT          NOT NULL,
  `ip_address`  VARCHAR(45)   DEFAULT NULL,
  `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_logs_user`   (`user_id`),
  KEY `idx_logs_ticket` (`ticket_id`),
  KEY `idx_logs_action` (`action`),
  CONSTRAINT `fk_logs_user`   FOREIGN KEY (`user_id`)   REFERENCES `users`(`id`)   ON DELETE SET NULL,
  CONSTRAINT `fk_logs_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: password_resets
-- ============================================================
CREATE TABLE `password_resets` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED NOT NULL,
  `token`      VARCHAR(64)  NOT NULL,
  `expires_at` DATETIME     NOT NULL,
  `used`       TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_token` (`token`),
  KEY `idx_pr_user` (`user_id`),
  CONSTRAINT `fk_pr_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SAMPLE DATA
-- ============================================================

-- Categories
INSERT INTO `categories` (`name`, `description`, `sla_hours`) VALUES
('Hardware Issue',     'Problems with physical hardware components',          8),
('Software Issue',     'Software crashes, bugs, and application errors',      12),
('Network & Internet', 'Connectivity issues, VPN, Wi-Fi problems',           4),
('Account & Access',   'Password resets, account lockouts, permissions',     2),
('Email & Calendar',   'Email configuration, calendar sync issues',          8),
('Printer & Scanner',  'Printing queue, scanner connectivity issues',        24),
('Security Incident',  'Virus, malware, phishing, data breach reports',      1),
('General Enquiry',    'General IT questions and advice requests',           48);

-- ============================================================
-- DEMO USERS
-- ALL passwords = Admin@1234
--
-- NOTE: The hashes below are generated by setup.php running on
-- your local PHP. If login fails, run setup.php FIRST:
--   http://localhost/ctisms/public/setup.php
--
-- The hash below is a valid bcrypt hash for: Admin@1234
-- Generated with: password_hash('Admin@1234', PASSWORD_BCRYPT, ['cost'=>10])
-- ============================================================

INSERT INTO `users` (`name`, `email`, `password`, `role`, `phone`, `department`, `is_active`) VALUES
('System Administrator', 'admin@ctisms.com',     '$2y$10$8K1p/a0dR6F5.Qe7vJzOO.rBmBGPQHXJY3ysBm3N5jHhHGwcC7QGu', 'admin',      '555-0100', 'IT Department',   1),
('Alice Thompson',       'tech1@ctisms.com',     '$2y$10$8K1p/a0dR6F5.Qe7vJzOO.rBmBGPQHXJY3ysBm3N5jHhHGwcC7QGu', 'technician', '555-0101', 'IT Support',      1),
('Bob Martinez',         'tech2@ctisms.com',     '$2y$10$8K1p/a0dR6F5.Qe7vJzOO.rBmBGPQHXJY3ysBm3N5jHhHGwcC7QGu', 'technician', '555-0102', 'Network Team',    1),
('Carol Johnson',        'customer1@ctisms.com', '$2y$10$8K1p/a0dR6F5.Qe7vJzOO.rBmBGPQHXJY3ysBm3N5jHhHGwcC7QGu', 'customer',   '555-0103', 'Finance',         1),
('David Lee',            'customer2@ctisms.com', '$2y$10$8K1p/a0dR6F5.Qe7vJzOO.rBmBGPQHXJY3ysBm3N5jHhHGwcC7QGu', 'customer',   '555-0104', 'Marketing',       1),
('Eva Wilson',           'customer3@ctisms.com', '$2y$10$8K1p/a0dR6F5.Qe7vJzOO.rBmBGPQHXJY3ysBm3N5jHhHGwcC7QGu', 'customer',   '555-0105', 'Human Resources', 1);

-- Sample tickets
INSERT INTO `tickets` (`ticket_number`, `title`, `description`, `category_id`, `priority`, `status`, `customer_id`, `technician_id`, `sla_deadline`, `created_at`) VALUES
('TKT-00001', 'Laptop screen flickering intermittently',   'My laptop screen flickers every 5-10 minutes making it very hard to work. It started yesterday after a Windows update.',              1, 'high',     'in_progress',    4, 2, DATE_ADD(NOW(), INTERVAL -2 HOUR), NOW() - INTERVAL 3 DAY),
('TKT-00002', 'Cannot connect to VPN from home',           'I am unable to connect to the company VPN from my home network. The error message says "Authentication failed".',                   3, 'critical', 'open',           5, 3, DATE_ADD(NOW(), INTERVAL  1 HOUR), NOW() - INTERVAL 1 DAY),
('TKT-00003', 'Microsoft Teams not loading',               'Teams crashes immediately on startup. I have tried reinstalling but the issue persists. Using Windows 11 and Teams version 1.5.', 2, 'medium',  'submitted',      4, NULL, DATE_ADD(NOW(), INTERVAL 10 HOUR), NOW() - INTERVAL 5 HOUR),
('TKT-00004', 'Password reset request',                    'I have been locked out of my account after too many failed attempts. Please reset my password.',                                    4, 'high',    'completed',      6, 2, DATE_ADD(NOW(), INTERVAL 5 HOUR),  NOW() - INTERVAL 2 DAY),
('TKT-00005', 'Printer on Floor 3 not working',            'The shared printer HP LaserJet Pro on floor 3 is showing offline status. Multiple users are affected.',                            6, 'medium',  'awaiting_parts', 5, 2, DATE_ADD(NOW(), INTERVAL 20 HOUR), NOW() - INTERVAL 4 DAY),
('TKT-00006', 'Suspicious email received',                 'I received a suspicious email asking me to click a link and enter my credentials. I did not click it. Please investigate.',        7, 'critical', 'closed',         6, 3, DATE_ADD(NOW(), INTERVAL -5 HOUR), NOW() - INTERVAL 6 DAY),
('TKT-00007', 'Excel formula not calculating correctly',   'Formulas in my budget spreadsheet are returning wrong values after I copied cells from another workbook.',                           2, 'low',     'open',           4, NULL, DATE_ADD(NOW(), INTERVAL 8 HOUR), NOW() - INTERVAL 12 HOUR),
('TKT-00008', 'Email signature disappeared',               'My email signature has disappeared after the Outlook update this morning. Need it restored with company branding.',                 5, 'low',     'submitted',      5, NULL, DATE_ADD(NOW(), INTERVAL 6 HOUR), NOW() - INTERVAL 2 HOUR);

-- Sample comments
INSERT INTO `comments` (`ticket_id`, `user_id`, `body`, `is_internal`, `created_at`) VALUES
(1, 2, 'Hi Carol, I have reviewed your ticket. I will need remote access to your laptop to diagnose the screen issue. Please be available at 2pm today.', 0, NOW() - INTERVAL 2 DAY),
(1, 4, 'That works for me! I will be at my desk.', 0, NOW() - INTERVAL 2 DAY + INTERVAL 1 HOUR),
(1, 2, 'Screen driver appears to be corrupted. Attempting update now.', 1, NOW() - INTERVAL 1 DAY),
(4, 2, 'Password has been reset and unlock code sent via SMS. Please change your password immediately upon login.', 0, NOW() - INTERVAL 2 DAY),
(4, 6, 'Thank you! I have logged in and updated my password.', 0, NOW() - INTERVAL 1 DAY),
(6, 3, 'Phishing email confirmed. Sending alert to all staff. Email has been quarantined. No breach detected.', 0, NOW() - INTERVAL 5 DAY),
(6, 3, 'Internal: Reported to security team. IP origin traced to Eastern Europe. Blacklisted at gateway level.', 1, NOW() - INTERVAL 5 DAY);

-- Sample notifications
INSERT INTO `notifications` (`user_id`, `ticket_id`, `type`, `title`, `message`, `is_read`, `created_at`) VALUES
(4, 1, 'ticket_assigned',  'Ticket Assigned',      'Your ticket TKT-00001 has been assigned to Alice Thompson.',        1, NOW() - INTERVAL 2 DAY),
(2, 1, 'ticket_assigned',  'New Ticket Assigned',  'You have been assigned ticket TKT-00001: Laptop screen flickering.', 1, NOW() - INTERVAL 2 DAY),
(5, 2, 'ticket_created',   'Ticket Created',       'Your ticket TKT-00002 has been successfully submitted.',            1, NOW() - INTERVAL 1 DAY),
(4, 3, 'ticket_created',   'Ticket Created',       'Your ticket TKT-00003 has been successfully submitted.',            0, NOW() - INTERVAL 5 HOUR),
(6, 6, 'status_changed',   'Ticket Closed',        'Your ticket TKT-00006 has been resolved and closed.',               1, NOW() - INTERVAL 5 DAY);

-- Sample activity logs
INSERT INTO `activity_logs` (`user_id`, `ticket_id`, `action`, `description`, `ip_address`, `created_at`) VALUES
(4, 1, 'ticket_created',   'Customer Carol Johnson created ticket TKT-00001',                  '192.168.1.10', NOW() - INTERVAL 3 DAY),
(1, 1, 'ticket_assigned',  'Admin assigned TKT-00001 to technician Alice Thompson',            '192.168.1.1',  NOW() - INTERVAL 3 DAY + INTERVAL 30 MINUTE),
(2, 1, 'status_changed',   'Status changed from open to in_progress for ticket TKT-00001',    '192.168.1.11', NOW() - INTERVAL 2 DAY),
(4, 4, 'ticket_created',   'Customer Carol Johnson created ticket TKT-00004',                  '192.168.1.10', NOW() - INTERVAL 2 DAY),
(2, 4, 'status_changed',   'Status changed from in_progress to completed for ticket TKT-00004','192.168.1.11', NOW() - INTERVAL 1 DAY),
(6, 6, 'ticket_created',   'Customer Eva Wilson created ticket TKT-00006',                     '192.168.1.12', NOW() - INTERVAL 6 DAY),
(3, 6, 'status_changed',   'Status changed from open to closed for ticket TKT-00006',         '192.168.1.13', NOW() - INTERVAL 5 DAY);

COMMIT;
