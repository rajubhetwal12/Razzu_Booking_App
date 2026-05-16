-- LuxStay Complete Schema v2
CREATE DATABASE IF NOT EXISTS `luxstay` DEFAULT CHARACTER SET utf8mb4;
USE `luxstay`;
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS google_users,room_availability,coupons,review_images,hotel_facilities,facilities,room_images,hotel_images,wishlists,notifications,reviews,payments,bookings,rooms,hotels,users;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE `users` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(25) DEFAULT NULL,
  `role` ENUM('admin','manager','customer') NOT NULL DEFAULT 'customer',
  `address` TEXT DEFAULT NULL,
  `nationality` VARCHAR(80) DEFAULT NULL,
  `google_id` VARCHAR(100) DEFAULT NULL,
  `profile_photo` VARCHAR(500) DEFAULT NULL,
  `login_provider` ENUM('email','google') NOT NULL DEFAULT 'email',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `hotels` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `owner_id` INT DEFAULT NULL,
  `name` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(200) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `short_desc` VARCHAR(350) DEFAULT NULL,
  `category` ENUM('budget','standard','luxury','boutique','resort') DEFAULT 'luxury',
  `stars` TINYINT NOT NULL DEFAULT 5,
  `cover_image` VARCHAR(600) NOT NULL DEFAULT '',
  `city` VARCHAR(100) DEFAULT NULL,
  `country` VARCHAR(100) NOT NULL DEFAULT 'Nepal',
  `address` TEXT DEFAULT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `latitude` DECIMAL(10,7) DEFAULT NULL,
  `longitude` DECIMAL(10,7) DEFAULT NULL,
  `check_in_time` VARCHAR(10) DEFAULT '14:00',
  `check_out_time` VARCHAR(10) DEFAULT '11:00',
  `min_price` DECIMAL(10,2) NOT NULL DEFAULT 3000.00,
  `max_price` DECIMAL(10,2) NOT NULL DEFAULT 50000.00,
  `rating` DECIMAL(3,1) NOT NULL DEFAULT 4.5,
  `review_count` INT NOT NULL DEFAULT 0,
  `discount` TINYINT NOT NULL DEFAULT 0,
  `is_verified` TINYINT(1) NOT NULL DEFAULT 1,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `hotel_images` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `hotel_id` INT NOT NULL,
  `image_url` VARCHAR(600) NOT NULL,
  `caption` VARCHAR(200) DEFAULT NULL,
  `is_cover` TINYINT(1) NOT NULL DEFAULT 0,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`hotel_id`) REFERENCES `hotels`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `facilities` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `icon` VARCHAR(10) DEFAULT '✓',
  `category` VARCHAR(60) DEFAULT 'General',
  UNIQUE KEY `uk_fname` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `hotel_facilities` (
  `hotel_id` INT NOT NULL,
  `facility_id` INT NOT NULL,
  PRIMARY KEY (`hotel_id`,`facility_id`),
  FOREIGN KEY (`hotel_id`) REFERENCES `hotels`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`facility_id`) REFERENCES `facilities`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `rooms` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `hotel_id` INT NOT NULL,
  `type` ENUM('standard','deluxe','luxury','presidential','couple','family') NOT NULL DEFAULT 'standard',
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `base_price` DECIMAL(10,2) NOT NULL,
  `quantity` INT NOT NULL DEFAULT 5,
  `max_guests` TINYINT NOT NULL DEFAULT 2,
  `max_adults` TINYINT NOT NULL DEFAULT 2,
  `max_children` TINYINT NOT NULL DEFAULT 0,
  `room_size_sqm` INT DEFAULT NULL,
  `image` VARCHAR(600) DEFAULT NULL,
  `is_refundable` TINYINT(1) NOT NULL DEFAULT 1,
  `breakfast_included` TINYINT(1) NOT NULL DEFAULT 0,
  `cancellation_policy` TEXT DEFAULT NULL,
  `is_available` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`hotel_id`) REFERENCES `hotels`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `room_images` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `room_id` INT NOT NULL,
  `hotel_id` INT NOT NULL,
  `image_url` VARCHAR(600) NOT NULL,
  `caption` VARCHAR(200) DEFAULT NULL,
  `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `bookings` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `booking_ref` VARCHAR(25) NOT NULL,
  `customer_id` INT NOT NULL,
  `hotel_id` INT NOT NULL,
  `room_id` INT DEFAULT NULL,
  `check_in` DATE NOT NULL,
  `check_out` DATE NOT NULL,
  `nights` INT NOT NULL DEFAULT 1,
  `adults` TINYINT NOT NULL DEFAULT 1,
  `children` TINYINT NOT NULL DEFAULT 0,
  `room_cost` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `extra_cost` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `tax_amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `service_charge` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `total_amount` DECIMAL(10,2) NOT NULL,
  `coupon_code` VARCHAR(30) DEFAULT NULL,
  `special_requests` TEXT DEFAULT NULL,
  `status` ENUM('pending','confirmed','checked_in','checked_out','cancelled') NOT NULL DEFAULT 'pending',
  `payment_status` ENUM('unpaid','paid','refunded') NOT NULL DEFAULT 'unpaid',
  `payment_method` VARCHAR(50) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_ref` (`booking_ref`),
  FOREIGN KEY (`customer_id`) REFERENCES `users`(`id`),
  FOREIGN KEY (`hotel_id`) REFERENCES `hotels`(`id`),
  FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `payments` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `booking_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `method` VARCHAR(50) DEFAULT 'esewa',
  `transaction_id` VARCHAR(120) DEFAULT NULL,
  `status` ENUM('pending','success','failed','refunded') NOT NULL DEFAULT 'pending',
  `paid_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `reviews` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `hotel_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `booking_id` INT DEFAULT NULL,
  `rating` TINYINT NOT NULL,
  `title` VARCHAR(200) DEFAULT NULL,
  `comment` TEXT DEFAULT NULL,
  `manager_reply` TEXT DEFAULT NULL,
  `replied_at` TIMESTAMP NULL DEFAULT NULL,
  `is_hidden` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`hotel_id`) REFERENCES `hotels`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `review_images` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `review_id` INT NOT NULL,
  `image_url` VARCHAR(600) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`review_id`) REFERENCES `reviews`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `notifications` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `message` TEXT DEFAULT NULL,
  `type` ENUM('booking','payment','system','promo') NOT NULL DEFAULT 'system',
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `wishlists` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `hotel_id` INT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_wishlist` (`user_id`,`hotel_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`hotel_id`) REFERENCES `hotels`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `coupons` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(30) NOT NULL,
  `description` VARCHAR(200) DEFAULT NULL,
  `discount_type` ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
  `discount_value` DECIMAL(10,2) NOT NULL DEFAULT 10,
  `min_amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `max_uses` INT NOT NULL DEFAULT 100,
  `used_count` INT NOT NULL DEFAULT 0,
  `valid_from` DATE DEFAULT NULL,
  `valid_until` DATE DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `room_availability` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `room_id` INT NOT NULL,
  `hotel_id` INT NOT NULL,
  `date` DATE NOT NULL,
  `available` INT NOT NULL DEFAULT 5,
  `booked` INT NOT NULL DEFAULT 0,
  `price_override` DECIMAL(10,2) DEFAULT NULL,
  UNIQUE KEY `uk_room_date` (`room_id`,`date`),
  FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `google_users` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `google_id` VARCHAR(100) NOT NULL,
  `access_token` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_google` (`google_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── FACILITIES DATA ───────────────────────────────────────────
INSERT INTO `facilities` (name,icon,category) VALUES
('Free WiFi','📶','Connectivity'),('Parking','🅿️','Transport'),
('Airport Pickup','🚗','Transport'),('Swimming Pool','🏊','Recreation'),
('Gym','💪','Recreation'),('Spa','💆','Wellness'),
('Restaurant','🍽️','Dining'),('Bar','🍸','Dining'),
('Room Service','🛎️','Service'),('Pet Friendly','🐾','Policy'),
('Breakfast Included','🍳','Dining'),('Air Conditioning','❄️','Comfort'),
('Balcony','🌅','Views'),('Mountain View','⛰️','Views'),
('City View','🌆','Views'),('Lake View','🏞️','Views'),
('Fireplace','🔥','Comfort'),('Laundry','👕','Service'),
('Concierge','🎩','Service'),('Business Center','💼','Business'),
('Kids Club','👶','Recreation'),('Jungle Safari','🦒','Recreation'),
('Rooftop','🏙️','Views'),('EV Charging','⚡','Transport');

-- ── DEMO HOTELS ───────────────────────────────────────────────
INSERT INTO `hotels` (owner_id,name,slug,description,short_desc,category,stars,cover_image,city,country,address,phone,email,latitude,longitude,min_price,max_price,rating,review_count,discount,is_verified,is_featured) VALUES
(2,'Kings Hotel','kings-hotel','Experience unparalleled luxury at Kings Hotel, a five-star sanctuary nestled in the heart of Kathmandu. Where traditional Nepali artistry meets contemporary elegance.','Five-star sanctuary in the heart of Kathmandu with traditional Nepali elegance.','luxury',5,'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=1200&q=80','Kathmandu','Nepal','Thamel, Kathmandu 44600','+977-1-4000001','info@kingshotel.com',27.7172,85.3240,8000,45000,4.8,124,15,1,1),

(2,'Royal Palace Hotel','royal-palace-hotel','Breathtaking lake-view luxury suites where you wake up to the stunning Annapurna mountain range reflecting in serene Phewa Lake.','Breathtaking lake-view suites with Annapurna panorama in Pokhara.','luxury',5,'https://images.unsplash.com/photo-1571003123894-1f0594d2b5d9?w=1200&q=80','Pokhara','Nepal','Lakeside, Pokhara 33700','+977-61-4000002','info@royalpalace.com',28.2096,83.9856,12000,60000,4.9,200,0,1,1),

(2,'Everest Luxury Resort','everest-luxury-resort','An alpine luxury resort perched in the breathtaking Himalayas with 360° panoramic mountain views. The perfect premium base for trekkers.','Alpine luxury resort at 3440m with 360° Himalayan panoramic views.','resort',5,'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?w=1200&q=80','Namche Bazaar','Nepal','Namche Bazaar, Solukhumbu 56001','+977-38-4000003','info@everestresort.com',27.8069,86.7141,15000,80000,4.7,89,10,1,1),

(2,'Himalayan Suites','himalayan-suites','Eco-luxury suites in harmony with Chitwan National Park. Wake to the sounds of the jungle and spot rhinos from your private balcony.','Eco-luxury jungle suites with private balconies in Chitwan National Park.','resort',4,'https://images.unsplash.com/photo-1523906834658-6e24ef2386f9?w=1200&q=80','Chitwan','Nepal','Sauraha, Chitwan 44204','+977-56-4000004','info@himalayansuites.com',27.5291,84.4979,5000,25000,4.6,156,5,1,0),

(2,'Lumbini Peace Resort','lumbini-peace-resort','A serene luxury resort near the sacred birthplace of Buddha. Immerse yourself in spiritual tranquility with modern comforts, meditation gardens, and panoramic views of the holy site.','Luxury spiritual resort at Buddha birthplace with meditation gardens.','boutique',4,'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?w=1200&q=80','Lumbini','Nepal','Sacred Garden Zone, Lumbini 32909','+977-71-4000005','info@lumbiniresort.com',27.4833,83.2750,6000,30000,4.5,67,0,1,0);

-- ── HOTEL IMAGES ──────────────────────────────────────────────
INSERT INTO `hotel_images` (hotel_id,image_url,caption,is_cover,sort_order) VALUES
-- Kings Hotel
(1,'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=1200&q=80','Hotel Exterior',1,1),
(1,'https://images.unsplash.com/photo-1582719508461-905c673771fd?w=1200&q=80','Grand Lobby',0,2),
(1,'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=1200&q=80','Deluxe Room',0,3),
(1,'https://images.unsplash.com/photo-1560347876-aeef00ee58a1?w=1200&q=80','Rooftop Pool',0,4),
(1,'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=1200&q=80','Fine Dining',0,5),
-- Royal Palace Hotel
(2,'https://images.unsplash.com/photo-1571003123894-1f0594d2b5d9?w=1200&q=80','Lake View Facade',1,1),
(2,'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?w=1200&q=80','Infinity Pool',0,2),
(2,'https://images.unsplash.com/photo-1631049421450-348ccd7f8949?w=1200&q=80','Mountain Suite',0,3),
(2,'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=1200&q=80','Luxury Bath',0,4),
(2,'https://images.unsplash.com/photo-1584132915807-fd1f5fbc078f?w=1200&q=80','Spa Center',0,5),
-- Everest Resort
(3,'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?w=1200&q=80','Himalayan Vista',1,1),
(3,'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=1200&q=80','Mountain Sunrise',0,2),
(3,'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=1200&q=80','Alpine Suite',0,3),
(3,'https://images.unsplash.com/photo-1571896349842-33c89424de2d?w=1200&q=80','Cozy Interior',0,4),
-- Himalayan Suites
(4,'https://images.unsplash.com/photo-1523906834658-6e24ef2386f9?w=1200&q=80','Jungle Lodge',1,1),
(4,'https://images.unsplash.com/photo-1472214103451-9374bd1c798e?w=1200&q=80','Wildlife View',0,2),
(4,'https://images.unsplash.com/photo-1596394516093-501ba68a0ba6?w=1200&q=80','Safari Bungalow',0,3),
(4,'https://images.unsplash.com/photo-1540541338287-41700207dee6?w=1200&q=80','Pool Area',0,4),
-- Lumbini Peace Resort
(5,'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?w=1200&q=80','Resort Exterior',1,1),
(5,'https://images.unsplash.com/photo-1545569341-9eb8b30979d9?w=1200&q=80','Meditation Garden',0,2),
(5,'https://images.unsplash.com/photo-1600011689032-8b628b8a8747?w=1200&q=80','Deluxe Room',0,3),
(5,'https://images.unsplash.com/photo-1584132967334-10e028bd69f7?w=1200&q=80','Restaurant',0,4);

-- ── ROOMS ─────────────────────────────────────────────────────
INSERT INTO `rooms` (hotel_id,type,name,description,base_price,quantity,max_guests,max_adults,max_children,room_size_sqm,image,is_refundable,breakfast_included,cancellation_policy) VALUES
-- Kings Hotel
(1,'standard','Standard Room','Elegant room with city views, king bed, 55" TV, minibar.',8000,10,2,2,0,32,'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=800&q=80',1,0,'Free cancellation up to 24h before check-in'),
(1,'deluxe','Deluxe Room','Spacious room with panoramic city views and premium furnishings.',12000,6,3,2,1,48,'https://images.unsplash.com/photo-1631049421450-348ccd7f8949?w=800&q=80',1,1,'Free cancellation up to 48h before check-in'),
(1,'luxury','Luxury Suite','Junior suite with separate living area and mountain views.',22000,4,2,2,0,65,'https://images.unsplash.com/photo-1582719508461-905c673771fd?w=800&q=80',1,1,'Free cancellation up to 72h before check-in'),
(1,'family','Family Room','Two interconnected bedrooms with children amenities.',16000,4,5,2,3,70,'https://images.unsplash.com/photo-1591088398332-8a7791972843?w=800&q=80',1,1,'Free cancellation up to 48h before check-in'),
(1,'presidential','Presidential Suite','Private jacuzzi, butler service, panoramic views, 200sqm.',45000,1,6,4,2,200,'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=800&q=80',0,1,'Non-refundable'),
-- Royal Palace Hotel
(2,'standard','Lake View Room','Partial lake view with contemporary decor.',12000,8,2,2,0,35,'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=800&q=80',1,0,'Free cancellation up to 24h before check-in'),
(2,'deluxe','Mountain Suite','Full Annapurna + Phewa Lake panorama, private balcony.',20000,5,3,2,1,55,'https://images.unsplash.com/photo-1631049421450-348ccd7f8949?w=800&q=80',1,1,'Free cancellation up to 48h before check-in'),
(2,'couple','Honeymoon Suite','Romantic suite with rose petal turndown, jacuzzi, champagne.',28000,3,2,2,0,60,'https://images.unsplash.com/photo-1618773928121-c32242e63f39?w=800&q=80',1,1,'Free cancellation up to 72h before check-in'),
(2,'presidential','Royal Penthouse','Top-floor 2-level penthouse, private pool, 360° views.',60000,1,6,4,2,220,'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=800&q=80',0,1,'Non-refundable'),
-- Everest Resort
(3,'standard','Himalayan Room','Stunning Everest views through floor-to-ceiling windows.',15000,8,2,2,0,38,'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=800&q=80',1,1,'Free cancellation up to 48h before check-in'),
(3,'deluxe','Alpine Suite','Fireplace, panoramic windows, heated floors.',28000,4,4,2,2,70,'https://images.unsplash.com/photo-1631049421450-348ccd7f8949?w=800&q=80',1,1,'Free cancellation up to 72h before check-in'),
-- Himalayan Suites
(4,'standard','Jungle Cottage','Private balcony facing the forest.',5000,10,2,2,0,28,'https://images.unsplash.com/photo-1596394516093-501ba68a0ba6?w=800&q=80',1,0,'Free cancellation up to 24h before check-in'),
(4,'family','Safari Bungalow','2-bedroom private bungalow with outdoor fire pit.',13000,4,6,2,4,90,'https://images.unsplash.com/photo-1591088398332-8a7791972843?w=800&q=80',1,1,'Free cancellation up to 48h before check-in'),
-- Lumbini Resort
(5,'standard','Garden Room','Peaceful room overlooking meditation gardens.',6000,10,2,2,0,30,'https://images.unsplash.com/photo-1600011689032-8b628b8a8747?w=800&q=80',1,1,'Free cancellation up to 24h before check-in'),
(5,'deluxe','Heritage Suite','Traditional design with modern comforts and garden view.',12000,5,3,2,1,55,'https://images.unsplash.com/photo-1631049421450-348ccd7f8949?w=800&q=80',1,1,'Free cancellation up to 48h before check-in'),
(5,'couple','Serenity Suite','Romantic suite for couples, yoga mat and spa access included.',18000,3,2,2,0,58,'https://images.unsplash.com/photo-1618773928121-c32242e63f39?w=800&q=80',1,1,'Free cancellation up to 72h before check-in');

-- ── HOTEL FACILITIES ──────────────────────────────────────────
INSERT INTO `hotel_facilities` (hotel_id,facility_id)
SELECT h.id, f.id FROM hotels h, facilities f
WHERE (h.slug='kings-hotel' AND f.name IN ('Free WiFi','Swimming Pool','Spa','Gym','Parking','Restaurant','Air Conditioning','Laundry','Airport Pickup','Rooftop','Bar','City View'))
OR (h.slug='royal-palace-hotel' AND f.name IN ('Free WiFi','Swimming Pool','Restaurant','Parking','Spa','Gym','Air Conditioning','Room Service','Lake View','Bar','Breakfast Included','Balcony'))
OR (h.slug='everest-luxury-resort' AND f.name IN ('Free WiFi','Spa','Gym','Restaurant','Airport Pickup','Laundry','Fireplace','Mountain View','Bar','Rooftop'))
OR (h.slug='himalayan-suites' AND f.name IN ('Free WiFi','Parking','Restaurant','Swimming Pool','Air Conditioning','Jungle Safari','Pet Friendly','Balcony'))
OR (h.slug='lumbini-peace-resort' AND f.name IN ('Free WiFi','Parking','Restaurant','Spa','Air Conditioning','Breakfast Included','Balcony','Gym'));

-- ── COUPONS ───────────────────────────────────────────────────
INSERT INTO `coupons` (code,description,discount_type,discount_value,min_amount,max_uses,valid_until) VALUES
('WELCOME10','First booking 10% off','percent',10,0,500,'2026-12-31'),
('SAVE500','NPR 500 off bookings above NPR 5000','fixed',500,5000,200,'2026-12-31'),
('LUXSTAY20','20% off luxury rooms','percent',20,10000,100,'2026-09-30'),
('NEPAL2026','Nepal Tourism Year 2026 - 15% off','percent',15,0,1000,'2026-12-31');
