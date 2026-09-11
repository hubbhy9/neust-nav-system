-- ============================================================
-- NEUST Wayfinding — Schema Additions
-- Adds the building/floor/room/service data model on top of the
-- existing `capstone` database WITHOUT touching existing tables
-- (courses, offices, roles, staff_profiles, student_profiles, users).
-- Safe to run against the current database: every statement is
-- CREATE TABLE IF NOT EXISTS.
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Physical locations -------------------------------------------------

CREATE TABLE IF NOT EXISTS `buildings` (
  `building_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `code` VARCHAR(20) DEFAULT NULL,
  `description` TEXT,
  `map_svg_path` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `floors` (
  `floor_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `building_id` INT UNSIGNED NOT NULL,
  `label` VARCHAR(50) NOT NULL,        -- e.g. "1st Floor"
  `floor_number` INT NOT NULL,
  `plan_svg_path` VARCHAR(255) DEFAULT NULL,
  CONSTRAINT `fk_floors_building` FOREIGN KEY (`building_id`)
      REFERENCES `buildings`(`building_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `rooms` (
  `room_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `floor_id` INT UNSIGNED NOT NULL,
  `office_id` INT(11) DEFAULT NULL,        -- links to existing `offices` table when the room IS an office
  `room_number` VARCHAR(30) DEFAULT NULL,
  `name` VARCHAR(150) NOT NULL,
  `room_type` ENUM('office','classroom','laboratory','facility','other') DEFAULT 'other',
  `map_x` INT DEFAULT NULL,                -- coordinates on the floor SVG, for highlighting
  `map_y` INT DEFAULT NULL,
  CONSTRAINT `fk_rooms_floor` FOREIGN KEY (`floor_id`)
      REFERENCES `floors`(`floor_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rooms_office` FOREIGN KEY (`office_id`)
      REFERENCES `offices`(`office_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Services / procedures ----------------------------------------------

CREATE TABLE IF NOT EXISTS `services` (
  `service_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,             -- "Request Transcript of Records"
  `short_code` VARCHAR(30) DEFAULT NULL,    -- "TOR" for search matching
  `description` TEXT,
  `office_id` INT(11) DEFAULT NULL,         -- responsible office (existing `offices` table)
  `room_id` INT UNSIGNED DEFAULT NULL,      -- destination room, if known
  `category` VARCHAR(60) DEFAULT NULL,      -- Documents / Academic / Student Services / etc.
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_services_room` FOREIGN KEY (`room_id`)
      REFERENCES `rooms`(`room_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_services_office` FOREIGN KEY (`office_id`)
      REFERENCES `offices`(`office_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `service_requirements` (
  `requirement_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `service_id` INT UNSIGNED NOT NULL,
  `label` VARCHAR(150) NOT NULL,
  `sort_order` INT DEFAULT 0,
  CONSTRAINT `fk_req_service` FOREIGN KEY (`service_id`)
      REFERENCES `services`(`service_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `service_steps` (
  `step_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `service_id` INT UNSIGNED NOT NULL,
  `step_number` INT NOT NULL,
  `instruction` TEXT NOT NULL,
  CONSTRAINT `fk_step_service` FOREIGN KEY (`service_id`)
      REFERENCES `services`(`service_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Personalization / student-facing --------------------------------

CREATE TABLE IF NOT EXISTS `navigation_history` (
  `history_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT(11) NOT NULL,
  `destination_type` ENUM('room','service','building') NOT NULL,
  `destination_id` INT UNSIGNED NOT NULL,
  `visited_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_navhist_user` FOREIGN KEY (`user_id`)
      REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `favorites` (
  `favorite_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT(11) NOT NULL,
  `destination_type` ENUM('room','service','building') NOT NULL,
  `destination_id` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_fav` (`user_id`, `destination_type`, `destination_id`),
  CONSTRAINT `fk_favorites_user` FOREIGN KEY (`user_id`)
      REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `announcements` (
  `announcement_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `office_id` INT(11) DEFAULT NULL,   -- NULL = campus-wide
  `title` VARCHAR(200) NOT NULL,
  `body` TEXT NOT NULL,
  `is_navigation_notice` TINYINT(1) DEFAULT 0,  -- e.g. "Registrar temporarily relocated"
  `created_by` INT(11) DEFAULT NULL,  -- existing `users` table
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_announce_office` FOREIGN KEY (`office_id`)
      REFERENCES `offices`(`office_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_announce_user` FOREIGN KEY (`created_by`)
      REFERENCES `users`(`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- Notes:
-- * All FKs above were checked against the real column types in
--   db/capstone.sql (offices.office_id and users.user_id are both
--   INT(11)) so this should import cleanly on your existing DB.
-- * Seed `buildings`/`floors` using the SVG/PDF assets already in
--   /img (1st_floor.svg, 1s_floor.svg, perst Plor.pdf, etc.) via
--   the `plan_svg_path` column — worth renaming/cleaning those
--   filenames (typos: "1s_floor.svg", "perst Plor.pdf") once you
--   import them.
-- * `office_id = 0` shows up for several existing `users` rows —
--   that's not a valid office (offices start at 1); worth deciding
--   whether 0 should mean NULL/no office assigned.
-- ============================================================
