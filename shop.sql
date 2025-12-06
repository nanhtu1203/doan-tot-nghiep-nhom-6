-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 06, 2025 at 01:21 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `shop`
--

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_code` varchar(50) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `customer_addr` varchar(255) DEFAULT NULL,
  `total_amount` int(11) NOT NULL DEFAULT 0,
  `status` varchar(50) NOT NULL DEFAULT 'Đang xử lý',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_code`, `user_id`, `customer_name`, `customer_phone`, `customer_addr`, `total_amount`, `status`, `created_at`) VALUES
(1, 'HD642E39', 1, 'assss', '0937731833', 'a', 400000, 'Đã giao', '2025-11-25 03:48:41'),
(2, 'HD1C0C1A', 1, 'edww', '0937731834', 'A', 400000, 'Đã trả hàng', '2025-11-27 03:50:09'),
(3, 'HDA02088', 1, 'assss', '0937731833', 'a', 6000000, 'Đã trả hàng', '2025-12-02 01:39:06'),
(4, 'HDB2670A', 1, 'asss', '0937731833', 'gf', 800000, 'Đã hủy', '2025-12-02 01:41:04'),
(5, 'HD3FBC99', 1, 'asss', '0937731833', 'aa', 200000, 'Đã hủy', '2025-12-02 01:54:54'),
(6, 'HDD18539', 1, 'long', '0932831312', 'ha noi', 500000, 'Đang xử lý', '2025-12-02 03:43:29');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`, `created_at`) VALUES
(1, 1, 0, 1, 400000, '2025-11-25 03:48:41'),
(2, 2, 0, 1, 400000, '2025-11-27 03:50:09'),
(3, 3, 0, 10, 300000, '2025-12-02 01:39:06'),
(4, 3, 0, 10, 300000, '2025-12-02 01:39:06'),
(5, 4, 0, 2, 400000, '2025-12-02 01:41:04'),
(6, 5, 0, 1, 200000, '2025-12-02 01:54:54'),
(7, 6, 0, 1, 500000, '2025-12-02 03:43:29');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `brand` varchar(100) DEFAULT 'TheGioiGiay',
  `price` decimal(10,2) NOT NULL,
  `old_price` decimal(10,2) DEFAULT NULL,
  `sale_percent` int(11) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `material` varchar(100) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `pattern` varchar(100) DEFAULT NULL,
  `sizes` text DEFAULT NULL,
  `image_main` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `image_1` varchar(255) DEFAULT NULL,
  `image_2` varchar(255) DEFAULT NULL,
  `image_3` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `brand_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `seller_id`, `name`, `brand`, `price`, `old_price`, `sale_percent`, `category`, `gender`, `material`, `color`, `pattern`, `sizes`, `image_main`, `created_at`, `image_1`, `image_2`, `image_3`, `description`, `brand_name`) VALUES
(8, 1, 'Giày Thể Thao Nam Nữ - Giày Nike Air Force 1 AF1 Trắng Cổ Thấp Thời Trang Full Box Bill', 'TheGioiGiay', 200000.00, 250000.00, 10, 'giay-the-thao-da-tong-hop', 'unisex', 'da', 'Trắng', 'trơn', '35,36,37,38,39,40,41,42', 'uploads/1764939490_da1.png', '2025-12-05 12:58:10', NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sellers`
--

CREATE TABLE `sellers` (
  `id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `shop_name` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sellers`
--

INSERT INTO `sellers` (`id`, `fullname`, `email`, `phone`, `shop_name`, `password_hash`, `created_at`) VALUES
(1, 'assss', 'quaydungdake@gmail.com', 'quaydungdake@gmail.c', 'c', '$2y$10$chTZ1bAC8fFiSYXo6JVDLeI3l.AoUlRl65Qq1h/Zz4TSCfunJYdLm', '2025-10-31 13:37:03'),
(3, 'asss', 'quaydungdakee@gmail.com', '0937731834', 'aa', '$2y$10$UXN4OhHilTOapvMO6v3yGeV8J/Iuw.9uA.olkBQFvzGMcV3ajm08i', '2025-10-31 13:37:40');

-- --------------------------------------------------------

--
-- Table structure for table `users_id`
--

CREATE TABLE `users_id` (
  `id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `verification_code` varchar(64) DEFAULT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users_id`
--

INSERT INTO `users_id` (`id`, `fullname`, `email`, `password_hash`, `created_at`, `verification_code`, `is_verified`) VALUES
(1, 'asss', 'quaydungdake@gmail.com', '$2y$10$rqHAQNpn1Bg8hD8aRKBzHOaTpJ8gOQ1b6GkDV/yiDXsJwjs3zjicS', '2025-10-30 13:08:59', NULL, 0),
(2, 'assss', 'quaydungdakee@gmail.com', '$2y$10$psCXFRgKxE.D/S62WW7Tq.eix40vM/YAqKUbTQoCgP47wUJZS.OmW', '2025-10-31 12:57:44', NULL, 0),
(3, 's', 'quaydungdakea@gmail.com', '$2y$10$/ZM8KTr59rGVnAj9GF37au0sEbzN0dm022H29sDZREWSsjzPsQChS', '2025-10-31 13:00:08', NULL, 0),
(4, 'assss', 'anhtu120304@gmail.com', '$2y$10$arQtbvs5Mfbzw4Pkl3ecKu51vJZ0PFGm0MEsZFHSaB.QErCiauIJK', '2025-11-14 13:52:26', '107286', 0),
(5, 'assss', 'nguyenanhtu2kpubg@gmail.com', '$2y$10$20CpHWOiBlgmAO4HV0uP5.z3ClKT5CmwiuBDxy.Yo1SaAwYeQi/7i', '2025-11-14 13:58:01', '528216', 0),
(6, 'edww', 'nguyentuananhdong@gmail.com', '$2y$10$1nWK1N7fd/xaNEMk.i65PezrZb4g0WQucYAIFgIh7FSjLmVRk3WhW', '2025-11-14 14:03:47', NULL, 1),
(7, 'assss', 'a@gmail.com', '$2y$10$/IxL10PJQuclta.u5ujlcevbH2BVE2aFgIJ1wNITy7fcvolfIK8t.', '2025-11-14 14:04:19', NULL, 1),
(8, 'assss', 'quaydungdakeee@gmail.com', '$2y$10$UBpiZ4gjhLx5h5XXKaEXYOugo.7UnGh3ZB43G9Zfm3YVWDmk9OEa2', '2025-11-22 01:26:25', NULL, 1),
(9, 'asss', 'quaydungdakeh@gmail.com', '$2y$10$kfb2M41ClGvbcspXSlV0NOuh/H2kFFMR9A0sHhEI9RBZ3OV4EZrxe', '2025-12-04 01:49:25', '703079', 0),
(10, 's', 'quaydungadake@gmail.com', '$2y$10$wgzqoaamdv0wPN16KZ9Lee1n7.JJwmaZaUea9oE8/67xowuHGIAxi', '2025-12-04 01:49:52', '553595', 0),
(11, 'assss', 'quaydungMdake@gmail.com', '$2y$10$Z/DOk2e7qJ0IbuQdNgy87OAnJjFx8BsGjEIpnzCPkfEkYPRgjHpJS', '2025-12-04 01:53:42', '129852', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_code` (`order_code`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_products_seller` (`seller_id`);

--
-- Indexes for table `sellers`
--
ALTER TABLE `sellers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `users_id`
--
ALTER TABLE `users_id`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `sellers`
--
ALTER TABLE `sellers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users_id`
--
ALTER TABLE `users_id`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_items_orders` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_seller` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
