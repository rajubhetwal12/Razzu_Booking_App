-- ═══════════════════════════════════════════════════════════
-- LuxStay — Hotel Type Packages Migration
-- Run this in phpMyAdmin on the `luxstay` database
-- ═══════════════════════════════════════════════════════════
USE `luxstay`;

-- 1. Package type definitions
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

-- 2. Facilities per package type
CREATE TABLE IF NOT EXISTS `hotel_type_facilities` (
  `type_key`    ENUM('standard','deluxe','luxury','presidential','couple','family') NOT NULL,
  `facility_id` INT NOT NULL,
  PRIMARY KEY (`type_key`,`facility_id`),
  FOREIGN KEY (`facility_id`) REFERENCES `facilities`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Seed packages (ignore if already exists)
INSERT IGNORE INTO `hotel_type_packages`
  (type_key,display_name,tagline,icon,badge_color,price_label,sort_order)
VALUES
('standard',   'Standard',     'Clean, comfortable stays for savvy travellers',             '🛏️',  '#6b7280', 'From NPR 5,000/night',  1),
('deluxe',     'Deluxe',       'Elevated comfort with premium views & amenities',            '✨',  '#3b82f6', 'From NPR 10,000/night', 2),
('luxury',     'Luxury',       'Unparalleled elegance, butler service & exclusive perks',   '👑',  '#d4a017', 'From NPR 20,000/night', 3),
('family',     'Family',       'Spacious rooms designed for families with kids',             '👨‍👩‍👧‍👦', '#10b981', 'From NPR 12,000/night', 4),
('couple',     'Couple',       'Romantic suites with jacuzzis, rose petals & champagne',    '💑',  '#ec4899', 'From NPR 18,000/night', 5),
('presidential','Presidential','The pinnacle of luxury — private pool, panoramic city views','🏆',  '#f59e0b', 'From NPR 40,000/night', 6);

-- 4. Seed facility mappings per type
-- Standard
INSERT IGNORE INTO `hotel_type_facilities` (type_key, facility_id)
SELECT 'standard', id FROM facilities WHERE name IN
('Free WiFi','Air Conditioning','Parking','Restaurant','Room Service','Laundry');

-- Deluxe
INSERT IGNORE INTO `hotel_type_facilities` (type_key, facility_id)
SELECT 'deluxe', id FROM facilities WHERE name IN
('Free WiFi','Air Conditioning','Parking','Restaurant','Room Service','Laundry',
 'Swimming Pool','Breakfast Included','City View','Gym');

-- Luxury
INSERT IGNORE INTO `hotel_type_facilities` (type_key, facility_id)
SELECT 'luxury', id FROM facilities WHERE name IN
('Free WiFi','Air Conditioning','Parking','Restaurant','Room Service','Laundry',
 'Swimming Pool','Breakfast Included','Spa','Gym','Balcony','Mountain View',
 'Concierge','Bar','Airport Pickup','Rooftop');

-- Family
INSERT IGNORE INTO `hotel_type_facilities` (type_key, facility_id)
SELECT 'family', id FROM facilities WHERE name IN
('Free WiFi','Air Conditioning','Parking','Restaurant','Room Service',
 'Swimming Pool','Breakfast Included','Kids Club','Gym','Laundry');

-- Couple
INSERT IGNORE INTO `hotel_type_facilities` (type_key, facility_id)
SELECT 'couple', id FROM facilities WHERE name IN
('Free WiFi','Air Conditioning','Room Service','Restaurant','Spa','Bar',
 'Balcony','Breakfast Included','Concierge','Fireplace');

-- Presidential
INSERT IGNORE INTO `hotel_type_facilities` (type_key, facility_id)
SELECT 'presidential', id FROM facilities WHERE name IN
('Free WiFi','Air Conditioning','Parking','Restaurant','Room Service','Laundry',
 'Swimming Pool','Breakfast Included','Spa','Gym','Balcony','Mountain View',
 'Concierge','Bar','Airport Pickup','Rooftop','Business Center',
 'Fireplace','City View','EV Charging');
