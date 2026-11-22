/*M!999999\- enable the sandbox mode */ 

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(80) NOT NULL,
  `slug` varchar(90) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES
(1,'Phones','phones'),
(2,'Laptops','laptops'),
(3,'Accessories','accessories'),
(4,'Home & Living','home-living'),
(6,'Wellness','wellness'),
(7,'Kitchen','kitchen'),
(8,'Stationery','stationery');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_items` (
  `order_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `product_name` varchar(150) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `quantity` int(10) unsigned NOT NULL CHECK (`quantity` > 0),
  PRIMARY KEY (`order_id`,`product_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(20) NOT NULL,
  `customer_name` varchar(120) NOT NULL,
  `email` varchar(160) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `total` decimal(12,2) NOT NULL,
  `status` enum('pending','paid','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference` (`reference`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(10) unsigned NOT NULL,
  `name` varchar(150) NOT NULL,
  `slug` varchar(170) NOT NULL,
  `description` text NOT NULL,
  `price` decimal(10,2) NOT NULL CHECK (`price` > 0),
  `stock` int(10) unsigned NOT NULL DEFAULT 0,
  `image_url` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES
(1,4,'Linen Cushion Cover','linen-cushion-cover','A soft, breathable linen cushion cover in a warm natural tone. A simple way to bring texture to your sofa or bed.',23000.00,18,'https://placehold.co/600x400?text=Linen%20Cushion%20Cover',1,'2026-11-22 11:27:41'),
(2,4,'Handwoven Storage Basket','handwoven-storage-basket','A sturdy handwoven basket for blankets, toys, or all the little things that need a home.',24000.00,12,'https://placehold.co/600x400?text=Handwoven%20Storage%20Basket',1,'2026-11-22 11:27:41'),
(3,4,'Ceramic Table Vase','ceramic-table-vase','A sculptural stoneware vase with a softly speckled glaze. Lovely with fresh stems or on its own.',15500.00,9,'https://placehold.co/600x400?text=Ceramic%20Table%20Vase',1,'2026-11-22 11:27:41'),
(4,4,'Cotton Throw Blanket','cotton-throw-blanket','A lightweight cotton throw with a relaxed weave, made for slow mornings and cool evenings.',32500.00,7,'https://placehold.co/600x400?text=Cotton%20Throw%20Blanket',1,'2026-11-22 11:27:41'),
(5,3,'Everyday Canvas Tote','everyday-canvas-tote','A roomy, durable cotton canvas tote with comfortable straps for errands and everyday carry.',12000.00,25,'https://placehold.co/600x400?text=Everyday%20Canvas%20Tote',1,'2026-11-22 11:27:41'),
(6,3,'Leather Card Holder','leather-card-holder','A slim card holder made from full-grain leather that softens beautifully with use.',19500.00,14,'https://placehold.co/600x400?text=Leather%20Card%20Holder',1,'2026-11-22 11:27:41'),
(7,3,'Minimalist Wristwatch','minimalist-wristwatch','A clean, easy-to-wear watch with a quiet dial and an adjustable genuine leather strap.',58000.00,6,'https://placehold.co/600x400?text=Minimalist%20Wristwatch',1,'2026-11-22 11:27:41'),
(8,6,'Botanical Hand Wash','botanical-hand-wash','A gentle plant-based hand wash with a fresh botanical scent and a nourishing finish.',8500.00,20,'https://placehold.co/600x400?text=Botanical%20Hand%20Wash',1,'2026-11-22 11:27:41'),
(9,6,'Scented Soy Candle','scented-soy-candle','A slow-burning soy candle with notes of cedar, citrus, and soft spice.',14500.00,15,'https://placehold.co/600x400?text=Scented%20Soy%20Candle',1,'2026-11-22 11:27:41'),
(10,6,'Reusable Water Bottle','reusable-water-bottle','A double-wall insulated stainless steel bottle that keeps drinks cold or warm on the go.',18000.00,11,'https://placehold.co/600x400?text=Reusable%20Water%20Bottle',1,'2026-11-22 11:27:41'),
(11,7,'Stoneware Coffee Mug','stoneware-coffee-mug','A comfortable, handcrafted stoneware mug with a generous handle and satin glaze.',9500.00,30,'https://placehold.co/600x400?text=Stoneware%20Coffee%20Mug',1,'2026-11-22 11:27:41'),
(12,7,'Acacia Serving Board','acacia-serving-board','A versatile acacia wood serving board for breakfast, snacks, or a relaxed shared table.',22000.00,8,'https://placehold.co/600x400?text=Acacia%20Serving%20Board',1,'2026-11-22 11:27:41'),
(13,7,'Glass Oil Dispenser','glass-oil-dispenser','A clear glass dispenser with a controlled pour spout to keep your cooking essentials close.',11000.00,0,'https://placehold.co/600x400?text=Glass%20Oil%20Dispenser',1,'2026-11-22 11:27:41'),
(14,8,'Undated Daily Planner','undated-daily-planner','An undated planner with space for priorities, notes, and small wins, ready whenever you are.',13500.00,17,'https://placehold.co/600x400?text=Undated%20Daily%20Planner',1,'2026-11-22 11:27:41'),
(15,8,'Hardcover Dot Journal','hardcover-dot-journal','A lay-flat, hardcover dot journal for sketches, lists, and everything in between.',10500.00,22,'https://placehold.co/600x400?text=Hardcover%20Dot%20Journal',1,'2026-11-22 11:27:41');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

