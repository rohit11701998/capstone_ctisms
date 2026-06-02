-- ============================================================
-- CTISMS Version 2 - Week 1 to 8 Development
-- Intermediate level — roles, comments, assignment added
-- ============================================================
-- Database name: ctisms_v2
-- ============================================================

CREATE DATABASE IF NOT EXISTS `ctisms_v2`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `ctisms_v2`;

-- Users (role system added Week 5)
CREATE TABLE `users` (
  `id`         INT          NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(100) NOT NULL,
  `email`      VARCHAR(150) NOT NULL,
  `password`   VARCHAR(255) NOT NULL,
  `role`       ENUM('customer','technician','admin') NOT NULL DEFAULT 'customer',
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tickets (technician assignment added Week 6)
CREATE TABLE `tickets` (
  `id`            INT          NOT NULL AUTO_INCREMENT,
  `title`         VARCHAR(255) NOT NULL,
  `description`   TEXT         NOT NULL,
  `status`        ENUM('Open','In Progress','Awaiting Parts','Completed','Closed')
                  NOT NULL DEFAULT 'Open',
  `priority`      ENUM('Low','Medium','High') NOT NULL DEFAULT 'Medium',
  `customer_id`   INT          NOT NULL,
  `technician_id` INT          DEFAULT NULL,
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `customer_id`   (`customer_id`),
  KEY `technician_id` (`technician_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Comments (added Week 5)
CREATE TABLE `comments` (
  `id`         INT      NOT NULL AUTO_INCREMENT,
  `ticket_id`  INT      NOT NULL,
  `user_id`    INT      NOT NULL,
  `message`    TEXT     NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Notifications (added Week 7)
CREATE TABLE `notifications` (
  `id`         INT          NOT NULL AUTO_INCREMENT,
  `user_id`    INT          NOT NULL,
  `message`    VARCHAR(255) NOT NULL,
  `is_read`    TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample users (password = Admin@1234, run setup.php)
INSERT INTO `users` (`name`,`email`,`password`,`role`) VALUES
('Admin User',    'admin@ctisms.com',    'PLACEHOLDER', 'admin'),
('Tech Alice',    'tech@ctisms.com',     'PLACEHOLDER', 'technician'),
('John Customer', 'customer@ctisms.com', 'PLACEHOLDER', 'customer');

-- Sample tickets
INSERT INTO `tickets` (`title`,`description`,`status`,`priority`,`customer_id`,`technician_id`) VALUES
('Email not working',    'Cannot send emails since this morning.',  'Open',        'High',   3, NULL),
('Printer offline',      'Printer on floor 2 shows offline.',       'In Progress', 'Medium', 3, 2),
('Slow computer',        'My laptop takes 10 minutes to start.',    'Open',        'Low',    3, NULL),
('VPN connection issue', 'Cannot connect to VPN from home office.', 'Completed',   'High',   3, 2);

-- Sample comments
INSERT INTO `comments` (`ticket_id`,`user_id`,`message`) VALUES
(2, 2, 'I have checked the printer. The driver needs to be reinstalled.'),
(2, 3, 'Thank you for looking into this!'),
(4, 2, 'VPN issue resolved. Updated the client software.');
