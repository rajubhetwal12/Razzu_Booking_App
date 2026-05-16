-- ═══════════════════════════════════════════════════════════
-- LuxStay — Hotel Settings Migration
-- Run this in phpMyAdmin on the `luxstay` database
-- ═══════════════════════════════════════════════════════════
USE `luxstay`;

-- 1. Hotel Policies table
CREATE TABLE IF NOT EXISTS `hotel_policies` (
  `id`                  INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `hotel_id`            INT NOT NULL UNIQUE,
  `check_in_time`       VARCHAR(10) NOT NULL DEFAULT '14:00',
  `check_out_time`      VARCHAR(10) NOT NULL DEFAULT '11:00',
  `cancellation_policy` TEXT DEFAULT NULL,
  `smoking_policy`      ENUM('not_allowed','allowed','designated_areas') NOT NULL DEFAULT 'not_allowed',
  `pet_policy`          ENUM('not_allowed','allowed','on_request') NOT NULL DEFAULT 'not_allowed',
  `child_policy`        TEXT DEFAULT NULL,
  `extra_bed_policy`    TEXT DEFAULT NULL,
  `payment_methods`     VARCHAR(300) DEFAULT 'cash,card',
  `age_restriction`     INT NOT NULL DEFAULT 0,
  `important_info`      TEXT DEFAULT NULL,
  `updated_at`          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`hotel_id`) REFERENCES `hotels`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Hotel Highlights (USPs shown on hotel detail page)
CREATE TABLE IF NOT EXISTS `hotel_highlights` (
  `id`        INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `hotel_id`  INT NOT NULL,
  `icon`      VARCHAR(10) NOT NULL DEFAULT '✨',
  `title`     VARCHAR(100) NOT NULL,
  `detail`    VARCHAR(200) DEFAULT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  FOREIGN KEY (`hotel_id`) REFERENCES `hotels`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Hotel Type Packages (if not already created by hotel_types_migration.sql)
CREATE TABLE IF NOT EXISTS `hotel_type_packages` (
  `id`           INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `type_key`     ENUM('standard','deluxe','luxury','presidential','couple','family') NOT NULL,
  `display_name` VARCHAR(80) NOT NULL,
  `tagline`      VARCHAR(200) DEFAULT NULL,
  `icon`         VARCHAR(10) NOT NULL DEFAULT '🏨',
  `badge_color`  VARCHAR(30) NOT NULL DEFAULT '#d4a017',
  `price_label`  VARCHAR(60) DEFAULT NULL,
  `sort_order`   TINYINT NOT NULL DEFAULT 0,
  `is_active`    TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY `uk_type_key` (`type_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Seed packages if empty
INSERT IGNORE INTO `hotel_type_packages`
  (type_key,display_name,tagline,icon,badge_color,price_label,sort_order)
VALUES
('standard',   'Standard',      'Clean, comfortable stays for savvy travellers',              '🛏️',  '#6b7280', 'From NPR 5,000/night',  1),
('deluxe',     'Deluxe',        'Elevated comfort with premium views & amenities',             '✨',  '#3b82f6', 'From NPR 10,000/night', 2),
('luxury',     'Luxury',        'Unparalleled elegance, butler service & exclusive perks',    '👑',  '#d4a017', 'From NPR 20,000/night', 3),
('family',     'Family',        'Spacious rooms designed for families with kids',              '👨‍👩‍👧‍👦', '#10b981', 'From NPR 12,000/night', 4),
('couple',     'Couple',        'Romantic suites with jacuzzis, rose petals & champagne',     '💑',  '#ec4899', 'From NPR 18,000/night', 5),
('presidential','Presidential', 'The pinnacle of luxury — private pool, panoramic views',     '🏆',  '#f59e0b', 'From NPR 40,000/night', 6);

-- 5. Hotel-to-type mapping table (which types does each hotel offer)
CREATE TABLE IF NOT EXISTS `hotel_offered_types` (
  `hotel_id`  INT NOT NULL,
  `type_key`  ENUM('standard','deluxe','luxury','presidential','couple','family') NOT NULL,
  PRIMARY KEY (`hotel_id`, `type_key`),
  FOREIGN KEY (`hotel_id`) REFERENCES `hotels`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Seed demo hotel policies
INSERT IGNORE INTO `hotel_policies` (hotel_id,check_in_time,check_out_time,cancellation_policy,smoking_policy,pet_policy,child_policy,extra_bed_policy,payment_methods,age_restriction,important_info)
SELECT id,
  COALESCE(check_in_time,'14:00'),
  COALESCE(check_out_time,'11:00'),
  'Free cancellation up to 48 hours before check-in. After that, the first night will be charged.',
  'not_allowed','not_allowed',
  'Children of all ages welcome. Children under 5 stay free.',
  'Extra beds available on request for NPR 1,500/night.',
  'cash,card,esewa,khalti',
  18,
  'Please present a valid government-issued ID at check-in. The hotel reserves the right to pre-authorise your credit card.'
FROM hotels;
