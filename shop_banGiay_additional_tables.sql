-- ============================================
-- BẢNG BỔ SUNG CHO WEBSITE BÁN GIÀY
-- ============================================

-- --------------------------------------------------------
-- Table: categories (Danh mục sản phẩm)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `parent_id` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table: brands (Thương hiệu)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `brands` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table: product_stock (Tồn kho sản phẩm theo size)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `product_stock` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `size` varchar(10) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_size` (`product_id`, `size`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `fk_stock_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table: addresses (Địa chỉ giao hàng của user)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `addresses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` varchar(255) NOT NULL,
  `ward` varchar(100) DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_addresses_user` FOREIGN KEY (`user_id`) REFERENCES `users_id` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table: coupons (Mã giảm giá)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `coupons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `type` enum('percent','fixed') NOT NULL DEFAULT 'percent',
  `value` decimal(10,2) NOT NULL,
  `min_order` decimal(10,2) DEFAULT 0,
  `max_discount` decimal(10,2) DEFAULT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  `used_count` int(11) NOT NULL DEFAULT 0,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table: order_coupons (Mã giảm giá đã sử dụng trong đơn hàng)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `order_coupons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `coupon_id` int(11) NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `coupon_id` (`coupon_id`),
  CONSTRAINT `fk_order_coupons_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_order_coupons_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table: payments (Thông tin thanh toán)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `status` enum('pending','completed','failed','refunded') NOT NULL DEFAULT 'pending',
  `payment_data` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `fk_payments_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table: shipping_methods (Phương thức vận chuyển)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `shipping_methods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `cost` decimal(10,2) NOT NULL DEFAULT 0,
  `estimated_days` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table: order_shipping (Thông tin vận chuyển đơn hàng)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `order_shipping` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `shipping_method_id` int(11) NOT NULL,
  `tracking_number` varchar(100) DEFAULT NULL,
  `shipping_cost` decimal(10,2) NOT NULL DEFAULT 0,
  `estimated_delivery` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `shipping_method_id` (`shipping_method_id`),
  CONSTRAINT `fk_order_shipping_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_order_shipping_method` FOREIGN KEY (`shipping_method_id`) REFERENCES `shipping_methods` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- INSERT DỮ LIỆU MẪU
-- --------------------------------------------------------

-- Categories
INSERT INTO `categories` (`name`, `slug`, `parent_id`, `description`) VALUES
('Giày', 'giay', NULL, 'Tất cả các loại giày'),
('Giày Thể Thao', 'giay-the-thao', 1, 'Giày thể thao chính hãng'),
('Giày Bóng Rổ', 'giay-bong-ro', 1, 'Giày bóng rổ chuyên nghiệp'),
('Giày Chạy', 'giay-chay', 1, 'Giày chạy bộ'),
('Quần Áo', 'quan-ao', NULL, 'Quần áo thể thao'),
('Phụ Kiện', 'phu-kien', NULL, 'Phụ kiện thời trang');

-- Brands
INSERT INTO `brands` (`name`, `slug`, `description`) VALUES
('Nike', 'nike', 'Thương hiệu Nike'),
('Adidas', 'adidas', 'Thương hiệu Adidas'),
('Jordan', 'jordan', 'Air Jordan'),
('New Balance', 'new-balance', 'New Balance'),
('Converse', 'converse', 'Converse'),
('Puma', 'puma', 'Puma'),
('Vans', 'vans', 'Vans'),
('MLB', 'mlb', 'MLB');

-- Shipping Methods
INSERT INTO `shipping_methods` (`name`, `description`, `cost`, `estimated_days`) VALUES
('Giao hàng tiêu chuẩn', 'Giao hàng trong 3-5 ngày', 30000, 4),
('Giao hàng nhanh', 'Giao hàng trong 1-2 ngày', 50000, 2),
('Giao hàng siêu tốc', 'Giao hàng trong ngày', 100000, 1);

-- Coupons mẫu
INSERT INTO `coupons` (`code`, `type`, `value`, `min_order`, `max_discount`, `usage_limit`, `start_date`, `end_date`) VALUES
('WELCOME10', 'percent', 10.00, 500000, 100000, 1000, NOW(), DATE_ADD(NOW(), INTERVAL 1 YEAR)),
('FREESHIP', 'fixed', 50000, 500000, NULL, NULL, NOW(), DATE_ADD(NOW(), INTERVAL 6 MONTH)),
('SALE20', 'percent', 20.00, 1000000, 200000, 500, NOW(), DATE_ADD(NOW(), INTERVAL 3 MONTH));

