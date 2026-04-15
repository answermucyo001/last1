-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 26, 2026 at 11:16 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pharmacy_gold_health`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_activity_log`
--

CREATE TABLE `admin_activity_log` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_activity_log`
--

INSERT INTO `admin_activity_log` (`id`, `admin_id`, `action`, `details`, `ip_address`, `created_at`) VALUES
(1, 1, 'User logged out: admin', '', '::1', '2026-02-25 14:27:12'),
(2, 1, 'User logged out: admin', '', '::1', '2026-02-25 14:29:31'),
(3, 1, 'Updated system settings', '', '::1', '2026-02-25 14:41:50'),
(4, 1, 'Replied to message #2', '', '::1', '2026-02-25 14:43:33'),
(5, 1, 'Replied to message #3', '', '::1', '2026-02-25 14:48:57'),
(6, 1, 'Added medicine: Mucyo Answer', '', '::1', '2026-02-25 14:51:45'),
(7, 1, 'Added medicine: jhjutjik', '', '::1', '2026-02-25 14:52:45');

-- --------------------------------------------------------

--
-- Table structure for table `admin_settings`
--

CREATE TABLE `admin_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('text','number','boolean','json') DEFAULT 'text',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_settings`
--

INSERT INTO `admin_settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `updated_at`) VALUES
(1, 'site_name', 'Pharmacy GOLD Health', 'text', '2026-02-25 10:08:30'),
(2, 'site_email', 'info@pharmacygold.com', 'text', '2026-02-25 10:08:30'),
(3, 'site_phone', '+256 700 000000', 'text', '2026-02-25 10:08:30'),
(4, 'delivery_fee', '5000', 'number', '2026-02-25 10:08:30'),
(5, 'tax_rate', '18', 'number', '2026-02-25 10:08:30'),
(6, 'currency', 'UGX', 'text', '2026-02-25 10:08:30'),
(7, 'mtn_number', '0700 000 000', 'text', '2026-02-25 10:08:30'),
(8, 'airtel_number', '0750 000 000', 'text', '2026-02-25 10:08:30'),
(9, 'maintenance_mode', '0', 'boolean', '2026-02-25 10:08:30'),
(10, 'enable_reviews', '1', 'boolean', '2026-02-25 10:08:30'),
(11, 'max_order_quantity', '10', 'number', '2026-02-25 10:08:30'),
(12, 'order_prefix', 'ORD', 'text', '2026-02-25 10:08:30'),
(13, 'low_stock_threshold', '10', 'number', '2026-02-25 10:08:30');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medicines`
--

CREATE TABLE `medicines` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `generic_name` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `cost_price` decimal(10,2) DEFAULT 0.00,
  `stock` int(11) DEFAULT 0,
  `batch_number` varchar(50) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `manufacturer` varchar(100) DEFAULT NULL,
  `dosage_form` varchar(50) DEFAULT NULL,
  `strength` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `featured` tinyint(1) DEFAULT 0,
  `prescription_required` tinyint(1) DEFAULT 0,
  `discount` int(11) DEFAULT 0,
  `rating` decimal(2,1) DEFAULT 0.0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medicines`
--

INSERT INTO `medicines` (`id`, `name`, `generic_name`, `description`, `price`, `cost_price`, `stock`, `batch_number`, `expiry_date`, `location`, `image_url`, `category`, `manufacturer`, `dosage_form`, `strength`, `created_at`, `featured`, `prescription_required`, `discount`, `rating`) VALUES
(1, 'Paracetamol', NULL, 'Pain reliever and fever reducer', 5.99, 0.00, 100, NULL, NULL, NULL, 'images/paracetamol.jpg', 'Pain Relief', NULL, NULL, NULL, '2026-02-25 08:45:56', 1, 0, 0, 4.5),
(2, 'Amoxicillin', NULL, 'Antibiotic for bacterial infections', 12.99, 0.00, 37, NULL, NULL, NULL, 'images/amoxicillin.jpg', 'Antibiotics', NULL, NULL, NULL, '2026-02-25 08:45:56', 0, 0, 10, 4.5),
(3, 'Vitamin C', NULL, 'Immune system booster', 8.99, 0.00, 191, NULL, NULL, NULL, 'images/vitaminc.jpg', 'Vitamins', NULL, NULL, NULL, '2026-02-25 08:45:56', 1, 0, 0, 4.5),
(4, 'Ibuprofen', NULL, 'Anti-inflammatory pain reliever', 7.99, 0.00, 150, NULL, NULL, NULL, 'images/ibuprofen.jpg', 'Pain Relief', NULL, NULL, NULL, '2026-02-25 08:45:56', 0, 0, 10, 0.0),
(5, 'Cough Syrup', NULL, 'Relieves cough and cold symptoms', 9.99, 0.00, 75, NULL, NULL, NULL, 'images/coughsyrup.jpg', 'Cold & Flu', NULL, NULL, NULL, '2026-02-25 08:45:56', 1, 0, 0, 0.0),
(6, 'Antihistamine', NULL, 'Allergy relief', 11.99, 0.00, 119, NULL, NULL, NULL, 'images/antihistamine.jpg', 'Allergy', NULL, NULL, NULL, '2026-02-25 08:45:56', 0, 0, 0, 0.0),
(7, 'Mucyo Answer', 'feva', 'wewewew', 0.01, 0.07, 4, '35456', '2026-02-28', 'kigali', 'http://fry', 'Pain Relief', 'rwanna', 'Injection', '300g', '2026-02-25 14:51:45', 0, 0, 23, 0.0),
(8, 'jhjutjik', 'feva', 'hgfyfh', -0.01, -0.03, -3, '35456', '2026-02-06', 'kigali', 'http://fry', 'Pain Relief', 'rwanna', 'Capsule', '300g', '2026-02-25 14:52:45', 1, 1, 3, 0.0);

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('unread','read','replied') DEFAULT 'unread',
  `admin_reply` text DEFAULT NULL,
  `replied_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `user_id`, `name`, `email`, `subject`, `message`, `created_at`, `status`, `admin_reply`, `replied_at`) VALUES
(1, 3, 'mucyo answer', 'mucyoanswer@gmail.com', 'hge', 'hWGJI', '2026-02-25 09:00:23', 'unread', NULL, NULL),
(2, NULL, 'leoncie-irakoze', 'neza@gmail.com', 'hgfy', 'yt3g5uhio', '2026-02-25 13:27:19', 'replied', 'uryu3g', '2026-02-25 14:43:33'),
(3, 4, 'neza', 'neza@gmail.com', 'hgfy', 'wa,mbawe', '2026-02-25 14:47:42', 'replied', 'nawe', '2026-02-25 14:48:57');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `cart_id` int(11) DEFAULT NULL,
  `medicine_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','processing','shipped','delivered','completed','cancelled') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT 'MTN Mobile Money',
  `payment_status` enum('pending','paid','failed') DEFAULT 'pending',
  `transaction_id` varchar(100) DEFAULT NULL,
  `transaction_ref` varchar(100) DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `cart_id`, `medicine_id`, `quantity`, `total_amount`, `order_date`, `status`, `payment_method`, `payment_status`, `transaction_id`, `transaction_ref`, `paid_at`) VALUES
(1, 3, NULL, 2, 1, 12.99, '2026-02-25 08:56:58', 'pending', 'MTN Mobile Money', 'pending', NULL, NULL, NULL),
(2, 3, NULL, 2, 1, 12.99, '2026-02-25 08:57:10', 'pending', 'MTN Mobile Money', 'pending', NULL, NULL, NULL),
(4, 3, NULL, 5, 1, 9.99, '2026-02-25 09:01:31', 'pending', 'MTN Mobile Money', 'pending', NULL, NULL, NULL),
(5, 3, NULL, 2, 1, 12.99, '2026-02-25 09:08:41', 'pending', ' Mobile Money', 'pending', 'TXN17720105211239', NULL, NULL),
(6, 3, NULL, 2, 1, 12.99, '2026-02-25 09:09:17', 'pending', ' Mobile Money', 'pending', 'TXN17720105572108', NULL, NULL),
(7, 3, NULL, 5, 1, 9.99, '2026-02-25 09:33:59', 'pending', 'MTN Mobile Money', 'pending', 'TXN17720120396296', NULL, NULL),
(8, 3, NULL, 5, 1, 9.99, '2026-02-25 09:34:54', 'pending', 'MTN Mobile Money', 'pending', 'TXN17720120942741', NULL, NULL),
(9, 3, NULL, 5, 1, 9.99, '2026-02-25 09:35:34', 'pending', 'MTN Mobile Money', 'pending', 'TXN17720121344816', NULL, NULL),
(10, 3, NULL, 5, 1, 9.99, '2026-02-25 09:35:45', 'pending', 'MTN Mobile Money', 'pending', 'TXN17720121458347', NULL, NULL),
(11, 4, NULL, 2, 1, 12.99, '2026-02-25 09:43:43', 'pending', 'MTN Mobile Money', 'pending', 'TXN17720126238317', NULL, NULL),
(12, 4, NULL, 6, 1, 11.99, '2026-02-25 09:45:08', 'pending', 'AIRTEL Mobile Money', 'pending', 'TXN17720127081022', NULL, NULL),
(13, 4, NULL, 3, 1, 8.99, '2026-02-25 09:46:00', 'pending', 'MTN Mobile Money', 'pending', 'TXN17720127607983', NULL, NULL),
(14, 4, NULL, 3, 1, 8.99, '2026-02-25 09:59:36', 'pending', 'MTN Mobile Money', 'pending', 'TXN17720135766774', NULL, NULL),
(15, 4, NULL, 3, 1, 8.99, '2026-02-25 09:59:58', 'pending', 'MTN Mobile Money', 'pending', 'TXN17720135984950', NULL, NULL),
(16, 4, NULL, 3, 1, 8.99, '2026-02-25 10:00:45', 'pending', 'MTN Mobile Money', 'pending', 'TXN17720136457108', NULL, NULL),
(17, 4, NULL, 3, 1, 8.99, '2026-02-25 10:01:09', 'pending', 'MTN Mobile Money', 'pending', 'TXN17720136695516', NULL, NULL),
(18, 4, NULL, 3, 1, 8.99, '2026-02-25 10:01:46', 'processing', 'MTN Mobile Money', 'paid', 'txhgewh6r4645', 'txhgewh6r4645', '2026-02-25 10:04:13'),
(19, 4, NULL, 3, 1, 8.99, '2026-02-25 13:02:09', 'pending', 'MTN Mobile Money', 'pending', 'TXN17720245293860', NULL, NULL),
(20, 5, NULL, 3, 1, 8.99, '2026-02-25 14:39:41', 'processing', 'MTN Mobile Money', 'paid', 'yfty754751', 'yfty754751', '2026-02-25 14:39:56'),
(21, 4, NULL, 3, 1, 8.99, '2026-02-25 14:53:56', 'processing', 'MTN Mobile Money', 'paid', 'bvhgj67r4525', 'bvhgj67r4525', '2026-02-25 14:54:19'),
(22, 4, NULL, 2, 8, 103.92, '2026-02-25 14:59:52', 'processing', 'MTN Mobile Money', 'paid', 'nbhjk123', 'nbhjk123', '2026-02-25 15:00:09');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `transaction_ref` varchar(100) DEFAULT NULL,
  `status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `order_id`, `user_id`, `amount`, `payment_method`, `transaction_ref`, `status`, `created_at`, `completed_at`) VALUES
(1, 18, 4, 8.99, 'MTN Mobile Money', 'txhgewh6r4645', 'completed', '2026-02-25 10:04:13', NULL),
(2, 20, 5, 8.99, 'MTN Mobile Money', 'yfty754751', 'completed', '2026-02-25 14:39:56', NULL),
(3, 21, 4, 8.99, 'MTN Mobile Money', 'bvhgj67r4525', 'completed', '2026-02-25 14:54:19', NULL),
(4, 22, 4, 103.92, 'MTN Mobile Money', 'nbhjk123', 'completed', '2026-02-25 15:00:09', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `payment_methods`
--

CREATE TABLE `payment_methods` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `provider` varchar(50) NOT NULL,
  `number` varchar(20) NOT NULL,
  `instructions` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_methods`
--

INSERT INTO `payment_methods` (`id`, `name`, `provider`, `number`, `instructions`, `is_active`) VALUES
(1, 'MTN Mobile Money', 'MTN', '0700 000 000', 'Dial *165# and follow prompts', 1),
(2, 'Airtel Money', 'AIRTEL', '0750 000 000', 'Dial *185# and follow prompts', 1);

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `review_text` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('text','number','boolean','json') DEFAULT 'text',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `updated_at`) VALUES
(1, 'site_name', 'Pharmacy GOLD Health', 'text', '2026-02-25 14:41:50'),
(2, 'site_email', 'info@pharmacygold.com', 'text', '2026-02-25 14:41:50'),
(3, 'site_phone', '+256 700 000000', 'text', '2026-02-25 14:41:50'),
(4, 'address', 'Kigali, Rwanda', 'text', '2026-02-25 14:41:50'),
(5, 'mtn_number', '079 550 6895', 'text', '2026-02-25 14:41:50'),
(6, 'airtel_number', '072 88 12900', 'text', '2026-02-25 14:41:50'),
(7, 'delivery_fee', '5000', 'text', '2026-02-25 14:41:50'),
(8, 'tax_rate', '18', 'text', '2026-02-25 14:41:50'),
(9, 'low_stock_threshold', '10', 'text', '2026-02-25 14:41:50'),
(10, 'order_prefix', 'ORD', 'text', '2026-02-25 14:41:50'),
(11, 'currency', 'UGX', 'text', '2026-02-25 14:41:50'),
(12, 'timezone', 'Africa/Kampala', 'text', '2026-02-25 14:41:50'),
(13, 'maintenance_mode', '1', 'text', '2026-02-25 14:41:50'),
(14, 'enable_reviews', '1', 'text', '2026-02-25 14:41:50');

-- --------------------------------------------------------

--
-- Table structure for table `system_backups`
--

CREATE TABLE `system_backups` (
  `id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `size` int(11) DEFAULT NULL,
  `type` enum('database','files','full') DEFAULT 'database',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `phone`, `role`, `created_at`) VALUES
(1, 'admin', 'admin@pharmacygold.com', '$2y$10$YourHashedPasswordHere', '1234567890', 'admin', '2026-02-25 08:45:55'),
(2, 'answer', 'mucyoanswer@gmail.com', '$2y$10$zrfhIkRMa8CwalGAb2Zd7.6MPem4lTwcPAp0bah54nFtX2tFrwO4q', '0877676543', 'user', '2026-02-25 08:54:38'),
(3, 'mucyo answer', 'mucyoanswer@g.com', '$2y$10$9o0ujUc0Qptu0ip5hKwaROWKmXEw.z165sTjPMIsh7FgTLGpxUszC', '0877676543', 'user', '2026-02-25 08:56:22'),
(4, 'neza', 'neza@gmail.com', '$2y$10$TZpc2rW/9UspwQ8GWqifxeCDwrhEofX7umbzoaaHbnzjWceyLPumS', '087767654329', 'user', '2026-02-25 09:40:25'),
(5, 'humuza thania', 'humuzathania@gmail.com', '$2y$10$MPMGXTpPVu49S9bljqx9s.vY1wFF3Iiy1HZPOZrkLQbtWYFLJwFwS', '087767654329', 'user', '2026-02-25 14:38:21');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_activity_log`
--
ALTER TABLE `admin_activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `admin_settings`
--
ALTER TABLE `admin_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_medicine` (`user_id`,`medicine_id`),
  ADD KEY `medicine_id` (`medicine_id`);

--
-- Indexes for table `medicines`
--
ALTER TABLE `medicines`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `medicine_id` (`medicine_id`),
  ADD KEY `idx_order_user` (`user_id`,`status`),
  ADD KEY `idx_payment_status` (`payment_status`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `medicine_id` (`medicine_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `system_backups`
--
ALTER TABLE `system_backups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_activity_log`
--
ALTER TABLE `admin_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `admin_settings`
--
ALTER TABLE `admin_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medicines`
--
ALTER TABLE `medicines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `payment_methods`
--
ALTER TABLE `payment_methods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `system_backups`
--
ALTER TABLE `system_backups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_activity_log`
--
ALTER TABLE `admin_activity_log`
  ADD CONSTRAINT `admin_activity_log_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`);

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`),
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`);

--
-- Constraints for table `system_backups`
--
ALTER TABLE `system_backups`
  ADD CONSTRAINT `system_backups_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
