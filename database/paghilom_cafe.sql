-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 03, 2025 at 01:10 AM
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
-- Database: `paghilom_cafe`
--

-- --------------------------------------------------------

--
-- Table structure for table `addons`
--

CREATE TABLE `addons` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Product add-ons like extra shots, syrups, etc';

--
-- Dumping data for table `addons`
--

INSERT INTO `addons` (`id`, `name`, `description`, `price`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'Extra Espresso Shot', 'Add an extra shot of espresso', 25.00, 1, 1, '2025-10-26 16:08:55', '2025-10-26 16:08:55'),
(2, 'Vanilla Syrup', 'Sweet vanilla flavoring', 20.00, 1, 2, '2025-10-26 16:08:55', '2025-10-26 16:08:55'),
(3, 'Caramel Syrup', 'Rich caramel flavoring', 20.00, 1, 3, '2025-10-26 16:08:55', '2025-10-26 16:08:55'),
(4, 'Hazelnut Syrup', 'Nutty hazelnut flavoring', 20.00, 1, 4, '2025-10-26 16:08:55', '2025-10-26 16:08:55'),
(5, 'Chocolate Sauce', 'Rich chocolate drizzle', 15.00, 1, 5, '2025-10-26 16:08:55', '2025-10-26 16:08:55'),
(6, 'Whipped Cream', 'Fresh whipped cream topping', 15.00, 1, 6, '2025-10-26 16:08:55', '2025-10-26 16:08:55'),
(7, 'Extra Milk', 'Add extra milk', 10.00, 1, 7, '2025-10-26 16:08:55', '2025-10-26 16:08:55'),
(8, 'Oat Milk Upgrade', 'Switch to oat milk', 30.00, 1, 8, '2025-10-26 16:08:55', '2025-10-26 16:08:55'),
(9, 'Almond Milk Upgrade', 'Switch to almond milk', 30.00, 1, 9, '2025-10-26 16:08:55', '2025-10-26 16:08:55'),
(10, 'Cinnamon Powder', 'Sprinkle of cinnamon', 5.00, 1, 10, '2025-10-26 16:08:55', '2025-10-26 16:08:55'),
(11, 'Caramel Drizzle', 'Extra caramel drizzle on top', 15.00, 1, 11, '2025-10-26 16:08:55', '2025-10-26 16:08:55'),
(12, 'Chocolate Chips', 'Add chocolate chips', 20.00, 1, 12, '2025-10-26 16:08:55', '2025-10-26 16:08:55');

-- --------------------------------------------------------

--
-- Table structure for table `attendance_records`
--

CREATE TABLE `attendance_records` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `status` enum('Present','Absent','Leave') NOT NULL DEFAULT 'Present',
  `note` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `low_stock_threshold` int(11) DEFAULT 10,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `image`, `low_stock_threshold`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Coffee', 'Hot and cold coffee beverages', NULL, 10, 1, 1, '2025-10-26 16:08:55', '2025-10-26 16:08:55'),
(2, 'Non-Coffee', 'Refreshing non-coffee drinks and milky beverages', NULL, 10, 2, 1, '2025-10-26 16:08:55', '2025-10-26 16:08:55'),
(3, 'Matcha Series', 'Premium matcha-based drinks', NULL, 10, 3, 1, '2025-10-26 16:08:55', '2025-10-26 16:08:55'),
(4, 'Pastries', 'Fresh baked pastries, cheesecakes and breads', NULL, 10, 4, 1, '2025-10-26 16:08:55', '2025-10-26 16:08:55'),
(5, 'Snacks', 'Light snacks and treats', NULL, 10, 5, 1, '2025-10-26 16:08:55', '2025-10-26 16:08:55'),
(6, 'Add-ons', 'Extra ingredients and modifications', NULL, 10, 6, 1, '2025-10-26 16:08:55', '2025-10-26 16:08:55');

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `id` int(11) NOT NULL,
  `phone` varchar(32) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `points_balance` int(11) NOT NULL DEFAULT 0,
  `photo` varchar(255) DEFAULT NULL,
  `qr_code` varchar(255) DEFAULT NULL,
  `total_orders` int(11) NOT NULL DEFAULT 0,
  `total_spent` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `emp_id` varchar(64) NOT NULL,
  `name` varchar(160) NOT NULL,
  `role` varchar(80) DEFAULT NULL,
  `contact` varchar(120) DEFAULT NULL,
  `date_hired` date DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gallery`
--

CREATE TABLE `gallery` (
  `id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `caption` varchar(255) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `gallery`
--

INSERT INTO `gallery` (`id`, `filename`, `caption`, `sort_order`, `is_active`, `created_at`) VALUES
(1, '1761525275_571273531_122183043416568106_901434902952808309_n.jpg', '', 0, 1, '2025-10-27 00:34:35'),
(2, '1761525283_cafe_interior.jpg', '', 0, 1, '2025-10-27 00:34:43'),
(3, '1761525356_0_Hero.jpeg', '', 0, 1, '2025-10-27 00:35:56'),
(4, '1761525356_1_received_765194589612224.jpeg', '', 1, 1, '2025-10-27 00:35:56'),
(5, '1761525356_2_received_777879608357677.jpeg', '', 2, 1, '2025-10-27 00:35:56'),
(6, '1761525356_3_received_781526668001928.jpeg', '', 3, 1, '2025-10-27 00:35:56'),
(7, '1761525356_4_received_789633547209252.jpeg', '', 4, 1, '2025-10-27 00:35:56'),
(8, '1761525356_5_received_821305283575872.jpeg', '', 5, 1, '2025-10-27 00:35:56'),
(9, '1761525356_6_received_845916724669911.jpeg', '', 6, 1, '2025-10-27 00:35:56'),
(10, '1761525356_7_received_855556970410537.jpeg', '', 7, 1, '2025-10-27 00:35:56'),
(11, '1761525356_8_received_1052091283531669.jpeg', '', 8, 1, '2025-10-27 00:35:56'),
(12, '1761525356_9_received_1068576788476459.jpeg', '', 9, 1, '2025-10-27 00:35:56'),
(13, '1761525356_10_received_1076733874534128.jpeg', '', 10, 1, '2025-10-27 00:35:56'),
(14, '1761525356_11_received_1299784088304764.jpeg', '', 11, 1, '2025-10-27 00:35:56'),
(15, '1761525356_12_received_1481001112951583.jpeg', '', 12, 1, '2025-10-27 00:35:56'),
(16, '1761525356_13_received_1489446412388089.jpeg', '', 13, 1, '2025-10-27 00:35:56'),
(17, '1761525356_14_received_1729526137726232.jpeg', '', 14, 1, '2025-10-27 00:35:56'),
(18, '1761525356_15_received_1744798239554991.jpeg', '', 15, 1, '2025-10-27 00:35:56'),
(19, '1761525356_16_received_1756235535766025.jpeg', '', 16, 1, '2025-10-27 00:35:56'),
(20, '1761525356_17_received_1794799487830306.jpeg', '', 17, 1, '2025-10-27 00:35:56'),
(21, '1761525356_18_received_1831975791066587.jpeg', '', 18, 1, '2025-10-27 00:35:56'),
(22, '1761525356_19_received_1901860830675028.jpeg', '', 19, 1, '2025-10-27 00:35:56'),
(23, '1761528504_0_CoffeeMenu.jpeg', '', 0, 1, '2025-10-27 01:28:24'),
(25, '1761528504_2_Non-CoffeeMatchaSeries.jpeg', '', 2, 1, '2025-10-27 01:28:24'),
(28, '1762102429_8795.jpg', '', 0, 1, '2025-11-02 16:53:49');

-- --------------------------------------------------------

--
-- Table structure for table `ingredients`
--

CREATE TABLE `ingredients` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `unit` varchar(20) NOT NULL DEFAULT 'pc',
  `min_stock` int(11) NOT NULL DEFAULT 0,
  `current_stock` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ingredient_movements`
--

CREATE TABLE `ingredient_movements` (
  `id` int(11) NOT NULL,
  `ingredient_id` int(11) NOT NULL,
  `change_qty` decimal(10,2) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `ref_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `success` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loyalty_ledger`
--

CREATE TABLE `loyalty_ledger` (
  `id` int(11) NOT NULL,
  `client_id` int(11) DEFAULT NULL,
  `phone` varchar(32) NOT NULL,
  `points_change` int(11) NOT NULL,
  `php_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `transaction_type` enum('earn','redeem','adjust','expire') NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `milks`
--

CREATE TABLE `milks` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `milks`
--

INSERT INTO `milks` (`id`, `name`, `price`, `is_active`, `sort_order`, `created_at`) VALUES
(1, 'Oat Milk', 20.00, 1, 1, '2025-10-26 16:08:55'),
(2, 'Almond Milk', 20.00, 1, 2, '2025-10-26 16:08:55'),
(3, 'Soy Milk', 20.00, 1, 3, '2025-10-26 16:08:55'),
(4, 'Coconut Milk', 20.00, 1, 4, '2025-10-26 16:08:55'),
(5, 'Fresh Milk', 0.00, 1, 5, '2025-10-26 16:08:55');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `phone` varchar(32) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `table_no` varchar(16) DEFAULT NULL,
  `order_type` enum('dine_in','takeout','pickup','delivery') NOT NULL DEFAULT 'pickup',
  `pickup_store_id` int(11) DEFAULT NULL,
  `pickup_time` datetime DEFAULT NULL,
  `delivery_address` text DEFAULT NULL,
  `status` enum('pending','queued','in_progress','ready','paid','fulfilled','completed','cancelled') DEFAULT 'pending',
  `payment_status` varchar(20) DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT 'cash',
  `payment_reference` varchar(100) DEFAULT NULL,
  `paymongo_session_id` varchar(255) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `tax_amount` decimal(10,2) DEFAULT 0.00,
  `points_awarded` int(11) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `fulfilled_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `paid_at` datetime DEFAULT NULL,
  `claimed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `code`, `user_id`, `client_id`, `customer_name`, `phone`, `email`, `table_no`, `order_type`, `pickup_store_id`, `pickup_time`, `delivery_address`, `status`, `payment_status`, `payment_method`, `payment_reference`, `paymongo_session_id`, `total_amount`, `discount_amount`, `tax_amount`, `points_awarded`, `notes`, `fulfilled_at`, `created_at`, `updated_at`, `paid_at`, `claimed_at`) VALUES
(1, 'ORDA3FE0C84', NULL, NULL, 'Rashed Dizon', '09105180061', NULL, NULL, 'pickup', 1, '2025-10-27 01:33:00', NULL, 'completed', 'paid', 'cash', NULL, NULL, 120.00, 0.00, 0.00, 24, '', NULL, '2025-10-26 17:33:23', '2025-10-27 00:57:00', NULL, NULL),
(2, 'ORDC297B2E2', NULL, NULL, 'Rashed Dizon', '09105180061', NULL, NULL, 'pickup', 1, '2025-10-27 01:41:00', NULL, 'completed', 'paid', 'cash', NULL, NULL, 240.00, 0.00, 0.00, 96, '', NULL, '2025-10-26 17:41:05', '2025-10-27 01:02:14', NULL, NULL),
(3, 'ORD5CA6A1B6', NULL, NULL, 'Rashed Dizon', '09105180061', NULL, NULL, 'pickup', 1, '2025-10-27 01:43:00', NULL, 'completed', 'paid', 'cash', NULL, NULL, 120.00, 0.00, 0.00, 48, '', NULL, '2025-10-26 17:43:24', '2025-10-28 05:08:54', NULL, NULL),
(4, 'ORD0A602AB8', NULL, NULL, 'Kurt Andrew', '09105180061', NULL, NULL, 'pickup', 1, '2025-10-27 09:17:00', NULL, 'completed', 'paid', 'cash', NULL, NULL, 120.00, 0.00, 0.00, 24, '', NULL, '2025-10-27 01:18:11', '2025-10-27 01:23:45', NULL, NULL),
(5, 'ORDA0C96A00', NULL, NULL, 'Rashed Dizon', '09105180061', NULL, NULL, 'pickup', 1, '2025-10-28 13:10:00', NULL, 'completed', 'successful', 'cash', NULL, NULL, 130.00, 0.00, 0.00, 0, '', NULL, '2025-10-28 05:10:11', '2025-11-02 13:06:12', '2025-11-02 21:05:18', NULL),
(6, 'ORD64AD0947', NULL, NULL, 'Rashed Dizon', '09105180061', NULL, NULL, 'pickup', 1, '2025-10-28 13:19:00', NULL, 'completed', 'successful', 'cash', NULL, NULL, 130.00, 0.00, 0.00, 0, '', NULL, '2025-10-28 05:19:14', '2025-11-02 13:06:29', '2025-11-02 21:06:29', NULL),
(7, 'ORDD76A34F6', NULL, NULL, 'Rashed Dizon', '09105180061', NULL, NULL, 'pickup', 1, '2025-10-28 15:22:00', NULL, 'completed', 'successful', 'cash', NULL, NULL, 130.00, 0.00, 0.00, 0, '', NULL, '2025-10-28 05:20:29', '2025-11-02 13:06:52', '2025-11-02 21:06:52', NULL),
(8, 'ORD5E0FEB3B', NULL, NULL, 'Rashed Dizon', '09105180061', NULL, NULL, 'pickup', 1, '2025-10-28 16:58:00', NULL, 'completed', 'successful', 'cash', NULL, NULL, 95.00, 0.00, 0.00, 0, '', NULL, '2025-10-28 07:58:10', '2025-11-02 13:06:39', '2025-11-02 21:05:53', NULL),
(9, 'ORDDCAB2502', NULL, NULL, 'Rashed Dizon', '09105180061', NULL, NULL, 'pickup', 1, '2025-10-28 16:02:00', NULL, 'completed', 'successful', 'cash', NULL, NULL, 130.00, 0.00, 0.00, 0, '', NULL, '2025-10-28 08:02:59', '2025-11-02 13:06:45', '2025-11-02 21:05:22', NULL),
(10, 'ORD420966CF', NULL, NULL, 'Rashed Dizon', '', NULL, NULL, 'pickup', 1, '2025-10-29 06:14:16', NULL, '', '', 'cash', NULL, NULL, 260.00, 0.00, 0.00, 0, '', NULL, '2025-10-29 05:14:16', '2025-10-29 05:14:16', NULL, NULL),
(11, 'ORD4F6BB1E4', 2, NULL, 'rash', '09105180061', NULL, NULL, 'pickup', 1, '2025-10-29 06:48:24', NULL, '', '', 'cash', NULL, NULL, 130.00, 0.00, 0.00, 0, '', NULL, '2025-10-29 05:48:24', '2025-10-29 05:48:24', NULL, NULL),
(12, 'ORD3CDFD0F1', NULL, NULL, 'Rashed Dizon', '09105180061', NULL, NULL, 'pickup', 1, '2025-10-29 08:53:24', NULL, '', '', 'cash', NULL, NULL, 130.00, 0.00, 0.00, 0, '', NULL, '2025-10-29 07:53:24', '2025-10-29 07:53:24', NULL, NULL),
(13, 'ORD37BE672E', NULL, NULL, 'Rashed Dizon', '09105180061', NULL, NULL, 'pickup', 1, '2025-10-29 09:17:16', NULL, '', '', 'cash', NULL, NULL, 6240.00, 0.00, 0.00, 0, '', NULL, '2025-10-29 08:17:16', '2025-10-29 08:17:16', NULL, NULL),
(14, 'ORD1456BE62', NULL, NULL, 'Rashed Dizon', '09105180061', NULL, NULL, 'pickup', 1, '2025-10-29 10:24:53', NULL, 'paid', '', 'cash', NULL, NULL, 130.00, 0.00, 0.00, 0, '', NULL, '2025-10-29 09:24:53', '2025-10-29 09:24:53', NULL, NULL),
(15, 'ORD3B59D0A4', NULL, NULL, 'Dizon, Rashed O.', '09105180061', NULL, NULL, 'pickup', 1, '2025-11-02 06:17:25', NULL, '', 'pending', 'cash', NULL, NULL, 95.00, 0.00, 0.00, 0, '', NULL, '2025-11-02 05:17:25', '2025-11-02 05:17:25', NULL, NULL),
(16, 'ORD67D255D6', 1, NULL, 'rash', '09105180061', NULL, NULL, 'pickup', 1, '2025-11-02 10:04:20', NULL, '', 'pending', 'cash', NULL, NULL, 130.00, 0.00, 0.00, 0, '', NULL, '2025-11-02 09:04:20', '2025-11-02 09:04:20', NULL, NULL),
(17, 'ORD23F55FEB', NULL, NULL, 'Dizon, Rashed O.', '09105180061', NULL, NULL, 'pickup', 1, '2025-11-02 10:30:35', NULL, '', 'pending', 'cash', NULL, NULL, 120.00, 0.00, 0.00, 0, '', NULL, '2025-11-02 09:30:35', '2025-11-02 09:30:35', NULL, NULL),
(18, 'ORDE0487492', NULL, NULL, 'Dizon, Rashed O.', '09105180061', NULL, NULL, 'pickup', 1, '2025-11-02 11:12:11', NULL, '', 'pending', 'cash', NULL, NULL, 260.00, 0.00, 0.00, 0, '', NULL, '2025-11-02 10:12:11', '2025-11-02 10:12:11', NULL, NULL),
(19, 'ORD8D125088', NULL, NULL, 'Dizon, Rashed O.', '09105180061', NULL, NULL, 'pickup', 1, '2025-11-02 11:57:52', NULL, '', 'pending', 'cash', NULL, NULL, 95.00, 0.00, 0.00, 0, '', NULL, '2025-11-02 10:57:52', '2025-11-02 10:57:52', NULL, NULL),
(20, 'ORDAFDA3F0D', NULL, NULL, 'Dizon, Rashed O.', '09105180061', NULL, NULL, 'pickup', 1, '2025-11-02 12:33:17', NULL, 'paid', 'pending', 'cash', NULL, NULL, 95.00, 0.00, 0.00, 0, '', NULL, '2025-11-02 11:33:17', '2025-11-02 11:41:19', NULL, NULL),
(21, 'ORDCD57B5D6', 9, NULL, 'Rashed Dizon', '09105180061', NULL, NULL, 'pickup', 1, '2025-11-03 00:56:57', NULL, '', 'pending', 'cash', NULL, NULL, 140.00, 0.00, 0.00, 0, '', NULL, '2025-11-02 23:56:57', '2025-11-02 23:56:57', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_addons`
--

CREATE TABLE `order_addons` (
  `id` int(11) NOT NULL,
  `order_item_id` int(11) NOT NULL COMMENT 'Links to order_items.id',
  `addon_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `price_each` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Add-ons attached to specific order items';

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `subtotal` decimal(10,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `name`, `price`, `qty`, `subtotal`, `notes`, `created_at`) VALUES
(1, 1, 3, '0', 120.00, 1, 120.00, NULL, '2025-10-26 17:33:23'),
(2, 2, 3, '0', 120.00, 1, 120.00, NULL, '2025-10-26 17:41:05'),
(3, 2, 5, '0', 120.00, 1, 120.00, NULL, '2025-10-26 17:41:05'),
(4, 3, 3, '0', 120.00, 1, 120.00, NULL, '2025-10-26 17:43:24'),
(5, 4, 3, '0', 120.00, 1, 120.00, NULL, '2025-10-27 01:18:11'),
(6, 5, 7, '0', 130.00, 1, 130.00, NULL, '2025-10-28 05:10:11'),
(7, 6, 7, '0', 130.00, 1, 130.00, NULL, '2025-10-28 05:19:14'),
(8, 7, 7, '0', 130.00, 1, 130.00, NULL, '2025-10-28 05:20:29'),
(9, 8, 8, '0', 95.00, 1, 95.00, NULL, '2025-10-28 07:58:10'),
(10, 9, 7, '0', 130.00, 1, 130.00, NULL, '2025-10-28 08:02:59'),
(11, 10, 7, '0', 130.00, 2, 260.00, NULL, '2025-10-29 05:14:16'),
(12, 11, 7, '0', 130.00, 1, 130.00, NULL, '2025-10-29 05:48:24'),
(13, 12, 7, '0', 130.00, 1, 130.00, NULL, '2025-10-29 07:53:24'),
(14, 13, 7, '0', 130.00, 48, 6240.00, NULL, '2025-10-29 08:17:16'),
(15, 14, 7, '0', 130.00, 1, 130.00, NULL, '2025-10-29 09:24:53'),
(16, 15, 8, '0', 95.00, 1, 95.00, NULL, '2025-11-02 05:17:25'),
(17, 16, 7, '0', 130.00, 1, 130.00, NULL, '2025-11-02 09:04:20'),
(18, 17, 3, '0', 120.00, 1, 120.00, NULL, '2025-11-02 09:30:35'),
(19, 18, 7, '0', 130.00, 2, 260.00, NULL, '2025-11-02 10:12:11'),
(20, 19, 8, '0', 95.00, 1, 95.00, NULL, '2025-11-02 10:57:53'),
(21, 20, 8, '0', 95.00, 1, 95.00, NULL, '2025-11-02 11:33:17'),
(22, 21, 10, '0', 140.00, 1, 140.00, NULL, '2025-11-02 23:56:57');

-- --------------------------------------------------------

--
-- Table structure for table `order_item_options`
--

CREATE TABLE `order_item_options` (
  `id` int(11) NOT NULL,
  `order_item_id` int(11) NOT NULL,
  `option_type` varchar(50) NOT NULL,
  `option_name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pages`
--

CREATE TABLE `pages` (
  `id` int(11) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `title` varchar(255) NOT NULL,
  `body` mediumtext NOT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pages`
--

INSERT INTO `pages` (`id`, `slug`, `title`, `body`, `is_published`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'about', 'About Paghilom Café', '<p>Born in Sta. Cruz, Laguna, Paghilom Café is a cozy nook for conversations and quiet moments. We serve premium coffee and treats made with love.</p>', 1, 1, '2025-10-26 16:08:55', '2025-10-26 16:08:55'),
(2, 'faqs', 'Frequently Asked Questions', '<h5>Common Questions</h5><p>Find answers to frequently asked questions about our cafe, menu, and services.</p>', 1, 2, '2025-10-26 16:08:55', '2025-10-26 16:08:55'),
(3, 'contact', 'Contact Us', '<p>Reach us any time at 0928 719 7722 or visit us at 4091 Sitio 2 Barangay Bagumbayan, Sta. Cruz, Laguna.</p>', 1, 3, '2025-10-26 16:08:55', '2025-10-26 16:08:55');

-- --------------------------------------------------------

--
-- Table structure for table `point_transactions`
--

CREATE TABLE `point_transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `client_id` int(11) DEFAULT NULL COMMENT 'Link to clients table',
  `points` int(11) NOT NULL COMMENT 'Positive for earning, negative for spending',
  `type` enum('earn','redeem','adjust','bonus','expire') NOT NULL DEFAULT 'earn',
  `ref_type` varchar(50) DEFAULT NULL COMMENT 'order, redemption, manual, etc',
  `ref_id` int(11) DEFAULT NULL COMMENT 'Reference to order_id, redemption_id, etc',
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Point transaction history';

--
-- Triggers `point_transactions`
--
DELIMITER $$
CREATE TRIGGER `pt_earn_after_paid_bi` BEFORE INSERT ON `point_transactions` FOR EACH ROW BEGIN
  IF NEW.type='earn' AND NEW.ref_type='order' THEN
    IF (SELECT payment_status FROM orders WHERE id=NEW.ref_id) <> 'paid' THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Cannot earn points before payment';
    END IF;
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `pt_earn_after_paid_bu` BEFORE UPDATE ON `point_transactions` FOR EACH ROW BEGIN
  IF NEW.type='earn' AND NEW.ref_type='order' THEN
    IF (SELECT payment_status FROM orders WHERE id=NEW.ref_id) <> 'paid' THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Cannot earn points before payment';
    END IF;
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cost_price` decimal(10,2) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `stock_qty` int(11) NOT NULL DEFAULT 0,
  `low_stock_threshold` int(11) NOT NULL DEFAULT 10,
  `restock_target` int(11) NOT NULL DEFAULT 0,
  `lead_time_days` int(11) NOT NULL DEFAULT 0,
  `last_low_alert_at` datetime DEFAULT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `badge` varchar(50) DEFAULT '',
  `image` varchar(255) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `allow_hot` tinyint(1) NOT NULL DEFAULT 0,
  `allow_cold` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `cost_price`, `category_id`, `stock_qty`, `low_stock_threshold`, `restock_target`, `lead_time_days`, `last_low_alert_at`, `is_featured`, `is_active`, `badge`, `image`, `sort_order`, `created_at`, `updated_at`, `allow_hot`, `allow_cold`) VALUES
(3, 'Milky Choco', '', 120.00, NULL, 2, 1000, 5, 0, 0, NULL, 1, 1, 'Popular', NULL, 100, '2025-10-26 17:09:10', '2025-10-26 17:09:10', 0, 0),
(5, 'Milky Blueberry', '', 120.00, NULL, 2, 1000, 5, 0, 0, NULL, 1, 1, 'Popular', NULL, 100, '2025-10-26 17:10:20', '2025-10-26 17:10:20', 0, 0),
(7, 'Cafe Latte', '', 130.00, NULL, 1, 10000, 5, 0, 0, NULL, 1, 1, 'Popular', NULL, 1000, '2025-10-26 17:12:19', '2025-10-26 17:12:19', 0, 0),
(8, 'Americano', '', 95.00, NULL, 1, 0, 0, 0, 0, NULL, 1, 1, 'Popular', NULL, 1, '2025-10-27 00:52:02', '2025-10-27 00:52:02', 1, 0),
(9, 'Milky Caramel', '', 130.00, NULL, 2, 0, 0, 0, 0, NULL, 1, 1, 'New', NULL, 1, '2025-10-27 01:25:12', '2025-10-27 01:25:12', 0, 0),
(10, 'Salted Caramel', '', 140.00, NULL, 2, 0, 10, 0, 0, NULL, 1, 1, '', NULL, 0, '2025-11-02 16:18:30', '2025-11-02 23:51:20', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `product_addons`
--

CREATE TABLE `product_addons` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `addon_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Links products to their available addons';

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `is_cover` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `filename`, `is_cover`, `sort_order`, `created_at`) VALUES
(1, 3, '1761498550_MilkyChocolate.jpeg', 1, 0, '2025-10-26 17:09:10'),
(2, 5, '1761498620_MilkyBlueberry.jpeg', 1, 0, '2025-10-26 17:10:20'),
(4, 7, '1761498739_CafeMocha.jpeg', 1, 0, '2025-10-26 17:12:19'),
(5, 8, '1761526322_Americano.jpeg', 1, 0, '2025-10-27 00:52:02'),
(6, 9, '1761528312_MilkyCaramel.jpeg', 1, 0, '2025-10-27 01:25:12'),
(7, 10, '1762100310_7283.jpeg', 1, 0, '2025-11-02 16:18:30');

-- --------------------------------------------------------

--
-- Table structure for table `product_milks`
--

CREATE TABLE `product_milks` (
  `product_id` int(11) NOT NULL,
  `milk_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_recipes`
--

CREATE TABLE `product_recipes` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `ingredient_id` int(11) NOT NULL,
  `qty_per_unit` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `promos`
--

CREATE TABLE `promos` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `body` text DEFAULT NULL,
  `starts_at` date DEFAULT NULL,
  `ends_at` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `redemptions`
--

CREATE TABLE `redemptions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `client_id` int(11) DEFAULT NULL,
  `reward_id` int(11) NOT NULL,
  `points_spent` int(11) NOT NULL,
  `status` enum('pending','approved','claimed','rejected','expired','issued','cancelled') NOT NULL DEFAULT 'pending',
  `voucher_code` varchar(50) DEFAULT NULL COMMENT 'Unique code for voucher redemption',
  `redeemed_at` timestamp NULL DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `claimed_at` datetime DEFAULT NULL,
  `rejected_reason` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='User reward redemptions';

-- --------------------------------------------------------

--
-- Table structure for table `rewards`
--

CREATE TABLE `rewards` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `required_points` int(11) NOT NULL DEFAULT 0,
  `reward_type` enum('free_item','discount','deal','voucher') NOT NULL DEFAULT 'free_item',
  `value` varchar(255) DEFAULT NULL,
  `thumb` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rewards`
--

INSERT INTO `rewards` (`id`, `name`, `description`, `required_points`, `reward_type`, `value`, `thumb`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'Cafe Latte', 'Free cafe latte', 150, '', 'Free Drinks', '1761528478_CafeLatte.jpeg', 1, 1, '2025-10-27 01:27:58', '2025-10-29 05:45:27');

-- --------------------------------------------------------

--
-- Table structure for table `reward_catalog`
--

CREATE TABLE `reward_catalog` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `points_cost` int(11) NOT NULL COMMENT 'Points required to redeem',
  `reward_type` enum('voucher','discount','free_item','upgrade','deal') NOT NULL DEFAULT 'voucher',
  `value` decimal(10,2) DEFAULT NULL COMMENT 'Monetary value if applicable',
  `terms` text DEFAULT NULL COMMENT 'Terms and conditions',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `stock_qty` int(11) DEFAULT NULL COMMENT 'NULL = unlimited, number = limited stock',
  `valid_days` int(11) DEFAULT 30 COMMENT 'Days until reward expires after redemption',
  `image` varchar(255) DEFAULT NULL,
  `thumb` varchar(255) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Available rewards catalog';

--
-- Dumping data for table `reward_catalog`
--

INSERT INTO `reward_catalog` (`id`, `name`, `description`, `points_cost`, `reward_type`, `value`, `terms`, `is_active`, `stock_qty`, `valid_days`, `image`, `thumb`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'Free Espresso', 'Enjoy a complimentary single shot espresso', 30, 'free_item', 50.00, 'Valid for 30 days from redemption. Not combinable with other offers.', 1, NULL, 30, NULL, NULL, 0, '2025-10-26 16:08:55', '2025-10-26 16:08:55'),
(2, '₱50 Discount Voucher', 'Get ₱50 off your next purchase', 100, 'discount', 50.00, 'Minimum purchase of ₱200 required. Valid for 30 days.', 1, NULL, 30, NULL, NULL, 0, '2025-10-26 16:08:55', '2025-10-26 16:08:55'),
(3, 'Free Pastry', 'Choose any pastry from our selection', 40, 'free_item', 60.00, 'Valid for 30 days from redemption. Based on availability.', 1, NULL, 30, NULL, NULL, 0, '2025-10-26 16:08:55', '2025-10-26 16:08:55'),
(4, '₱100 Discount Voucher', 'Get ₱100 off your next purchase', 200, 'discount', 100.00, 'Minimum purchase of ₱500 required. Valid for 30 days.', 1, NULL, 30, NULL, NULL, 0, '2025-10-26 16:08:55', '2025-10-26 16:08:55'),
(5, 'Free Iced Coffee', 'Complimentary iced coffee of your choice', 50, 'free_item', 80.00, 'Valid for 30 days from redemption.', 1, NULL, 30, NULL, NULL, 0, '2025-10-26 16:08:55', '2025-10-26 16:08:55'),
(6, '₱200 Discount Voucher', 'Get ₱200 off your next purchase', 400, 'discount', 200.00, 'Minimum purchase of ₱1000 required. Valid for 30 days.', 1, NULL, 30, NULL, NULL, 0, '2025-10-26 16:08:55', '2025-10-26 16:08:55'),
(7, 'Size Upgrade', 'Upgrade any drink to the next size free', 25, 'upgrade', 30.00, 'Valid for 30 days from redemption.', 1, NULL, 30, NULL, NULL, 0, '2025-10-26 16:08:55', '2025-10-26 16:08:55'),
(8, 'Free Meal Combo', 'Get a complete meal combo free', 150, 'free_item', 250.00, 'Includes drink, main item, and side. Valid for 30 days.', 1, NULL, 30, NULL, NULL, 0, '2025-10-26 16:08:55', '2025-10-26 16:08:55');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `key` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `type` varchar(50) DEFAULT 'string',
  `group` varchar(50) DEFAULT 'general',
  `label` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `key`, `value`, `type`, `group`, `label`, `description`, `created_at`, `updated_at`) VALUES
(1, 'site_name', 'Paghilom Café', 'string', 'general', 'Site Name', NULL, '2025-10-26 16:08:55', '2025-10-27 01:27:12'),
(2, 'site_tagline', 'Hayaang sarili ay MAGHILOM', 'string', 'general', 'Site Tagline', NULL, '2025-10-26 16:08:55', '2025-10-26 16:08:55'),
(3, 'contact_email', 'contact@paghilom.cafe', 'string', 'contact', 'Contact Email', NULL, '2025-10-26 16:08:55', '2025-10-26 16:08:55'),
(4, 'contact_phone', '09287197722', 'string', 'contact', 'Contact Phone', NULL, '2025-10-26 16:08:55', '2025-10-26 16:08:55'),
(5, 'address', '4009 Sitio 2 Barangay Bagumbayan Sta Cruz Laguna', 'string', 'contact', 'Physical Address', NULL, '2025-10-26 16:08:55', '2025-10-26 23:57:35'),
(6, 'opening_hours', 'Mon-Sun 10:00 AM - 12:00 PM', 'string', 'general', 'Opening Hours', NULL, '2025-10-26 16:08:55', '2025-10-26 23:57:35'),
(7, 'points_per_peso', '5', 'integer', 'loyalty', 'Pesos per Point (₱5 = 1 point)', NULL, '2025-10-26 16:08:55', '2025-10-26 16:08:55'),
(8, 'facebook_url', 'https://www.facebook.com/profile.php?id=61567043202024', 'string', 'social', 'Facebook URL', NULL, '2025-10-26 16:08:55', '2025-10-26 16:08:55'),
(9, 'instagram_url', 'https://www.instagram.com/paghilom_cafe', 'string', 'social', 'Instagram URL', NULL, '2025-10-26 16:08:55', '2025-10-29 05:46:46'),
(10, 'alert_email', 'admin@paghilom.cafe', 'string', 'notifications', 'Alert Email', NULL, '2025-10-26 16:08:55', '2025-10-26 16:08:55'),
(11, 'ga_id', '', 'string', 'analytics', 'Google Analytics ID', NULL, '2025-10-26 16:08:55', '2025-10-26 16:08:55'),
(12, 'smtp_host', 'smtp.gmail.com', 'string', 'general', NULL, NULL, '2025-10-26 16:49:04', '2025-10-26 16:49:04'),
(13, 'smtp_port', '465', 'string', 'general', NULL, NULL, '2025-10-26 16:49:04', '2025-10-26 16:49:04'),
(14, 'smtp_secure', 'ssl', 'string', 'general', NULL, NULL, '2025-10-26 16:49:04', '2025-10-26 16:49:04'),
(15, 'smtp_user', '', 'string', 'general', NULL, NULL, '2025-10-26 16:49:04', '2025-10-28 17:20:33'),
(16, 'smtp_pass', '', 'string', 'general', NULL, NULL, '2025-10-26 16:49:04', '2025-10-28 17:20:33'),
(17, 'smtp_from', '', 'string', 'general', NULL, NULL, '2025-10-26 16:49:04', '2025-10-28 17:20:33'),
(18, 'smtp_from_name', 'Paghilom Cafe', 'string', 'general', NULL, NULL, '2025-10-26 16:49:04', '2025-10-28 17:20:33'),
(26, 'lat', '', 'string', 'general', NULL, NULL, '2025-10-26 23:57:35', '2025-10-26 23:57:35'),
(27, 'lng', '', 'string', 'general', NULL, NULL, '2025-10-26 23:57:35', '2025-10-26 23:57:35'),
(37, 'logo', 'uploads/logo.png', 'string', 'general', NULL, NULL, '2025-10-27 00:31:40', '2025-10-27 00:31:40'),
(85, 'cafe_name', 'Paghilom Cafe', 'string', 'general', NULL, NULL, '2025-11-02 16:51:19', '2025-11-02 16:51:19'),
(86, 'logo_url', 'http://localhost/paghilom/assets/uploads/logo.png', 'string', 'general', NULL, NULL, '2025-11-02 16:51:19', '2025-11-02 16:51:19'),
(87, 'contact', '09287197722', 'string', 'general', NULL, NULL, '2025-11-02 16:51:19', '2025-11-02 16:55:42'),
(88, 'tax_rate', '0', 'string', 'general', NULL, NULL, '2025-11-02 16:51:19', '2025-11-02 16:51:19'),
(89, 'theme', 'dark', 'string', 'general', NULL, NULL, '2025-11-02 16:51:19', '2025-11-02 16:51:19'),
(125, 'open_time', '10:00', 'string', 'general', NULL, NULL, '2025-11-02 16:59:01', '2025-11-02 17:04:15'),
(126, 'close_time', '12:00', 'string', 'general', NULL, NULL, '2025-11-02 16:59:01', '2025-11-02 16:59:01'),
(149, 'open_days', 'Mon–Sun', 'string', 'general', NULL, NULL, '2025-11-02 17:04:05', '2025-11-02 23:55:41');

-- --------------------------------------------------------

--
-- Table structure for table `stock_movements`
--

CREATE TABLE `stock_movements` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `change_qty` int(11) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `ref_order_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stores`
--

CREATE TABLE `stores` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `address` text NOT NULL,
  `city` varchar(100) NOT NULL,
  `phone` varchar(32) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `hours` varchar(255) DEFAULT NULL COMMENT 'Opening hours',
  `opening_hours` text DEFAULT NULL COMMENT 'Detailed schedule',
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stores`
--

INSERT INTO `stores` (`id`, `name`, `address`, `city`, `phone`, `email`, `hours`, `opening_hours`, `latitude`, `longitude`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Paghilom Café - Main Branch', '4009 Sitio 2 Barangay Bagumbayan Sta Cruz Laguna', 'Sta. Cruz, Laguna', '09287197722', NULL, 'Mon-Sun 8:00 AM - 9:00 PM', NULL, NULL, NULL, 1, '2025-10-26 16:08:55', '2025-10-29 08:07:07');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) DEFAULT NULL COMMENT 'Hashed password for login',
  `password_hash` varchar(255) DEFAULT NULL COMMENT 'Alternative column name',
  `password_set` tinyint(1) NOT NULL DEFAULT 0,
  `profile_photo` varchar(255) DEFAULT NULL,
  `phone` varchar(32) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `role` enum('customer','staff','admin') NOT NULL DEFAULT 'customer',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `verification_code` varchar(10) DEFAULT NULL,
  `verification_code_expiry` datetime DEFAULT NULL,
  `email_verification_token` varchar(255) DEFAULT NULL,
  `password_reset_token` varchar(255) DEFAULT NULL,
  `password_reset_expires` datetime DEFAULT NULL,
  `remember_token` varchar(255) DEFAULT NULL,
  `remember_expires` datetime DEFAULT NULL,
  `oauth_provider` varchar(50) DEFAULT NULL,
  `oauth_id` varchar(255) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `login_attempts` int(11) NOT NULL DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `password_hash`, `password_set`, `profile_photo`, `phone`, `avatar`, `role`, `is_active`, `email_verified`, `verification_code`, `verification_code_expiry`, `email_verification_token`, `password_reset_token`, `password_reset_expires`, `remember_token`, `remember_expires`, `oauth_provider`, `oauth_id`, `last_login`, `login_attempts`, `locked_until`, `created_at`, `updated_at`) VALUES
(1, 'Admin', 'paghilomcafeadmin@gmail.com', '$2y$10$hlwj0TjwEelUxsA.WIytbO/OxK93dODRR7/MsYtQ0DQm7w536udyG', '$2y$10$hlwj0TjwEelUxsA.WIytbO/OxK93dODRR7/MsYtQ0DQm7w536udyG', 0, NULL, NULL, NULL, 'admin', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-03 07:56:30', 0, NULL, '2025-10-26 16:08:55', '2025-11-02 23:56:30'),
(2, 'Admin 2', 'paghilomcafeowner@gmail.com', '$2y$10$EtWxroAJ1M3LzN5NYfcr1ugpyr7CVrshnClf4HyKg5OcoGFDaI8h6', '$2y$10$EtWxroAJ1M3LzN5NYfcr1ugpyr7CVrshnClf4HyKg5OcoGFDaI8h6', 0, 'user_2_1761731179.jpg', NULL, NULL, 'admin', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-03 08:02:30', 0, NULL, '2025-10-26 16:08:55', '2025-11-03 00:02:30'),
(9, 'Rashed Dizon', 'rasheddizon0@gmail.com', NULL, '$2y$10$5PWAmYYyDUWTLFwoYr7C/.WN4h.S3v48fYlxpwPvxeJAzF5lhbXEa', 1, 'user_9_1762127688.jpg', NULL, 'https://lh3.googleusercontent.com/a/ACg8ocJ6T9urwKPFFuOrGDOuDG3XWTuScefT8pAYFqd1FdsXfjXC6Kxz=s96-c', 'customer', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'google', '104154898517610240811', '2025-11-03 07:54:48', 0, NULL, '2025-11-02 23:54:48', '2025-11-02 23:55:26');

-- --------------------------------------------------------

--
-- Table structure for table `user_carts`
--

CREATE TABLE `user_carts` (
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_carts`
--

INSERT INTO `user_carts` (`user_id`, `product_id`, `qty`, `updated_at`) VALUES
(3, 8, 1, '2025-10-28 12:38:33'),
(7, 7, 1, '2025-10-29 09:24:39'),
(7, 8, 3, '2025-10-29 09:24:39'),
(7, 9, 2, '2025-10-29 09:24:39');

-- --------------------------------------------------------

--
-- Table structure for table `vouchers`
--

CREATE TABLE `vouchers` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `client_id` int(11) NOT NULL,
  `reward_id` int(11) NOT NULL,
  `points_cost` int(11) NOT NULL,
  `status` enum('issued','claimed','expired','cancelled') DEFAULT 'issued',
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `claimed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `addons`
--
ALTER TABLE `addons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `attendance_records`
--
ALTER TABLE `attendance_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_emp_date` (`employee_id`,`date`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_sort_order` (`sort_order`);

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD KEY `idx_phone` (`phone`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_points` (`points_balance`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `emp_id` (`emp_id`);

--
-- Indexes for table `gallery`
--
ALTER TABLE `gallery`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `ingredients`
--
ALTER TABLE `ingredients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `ingredient_movements`
--
ALTER TABLE `ingredient_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_ingredient` (`ingredient_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email_ip` (`email`,`ip_address`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `loyalty_ledger`
--
ALTER TABLE `loyalty_ledger`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `idx_client` (`client_id`),
  ADD KEY `idx_phone` (`phone`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `milks`
--
ALTER TABLE `milks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `pickup_store_id` (`pickup_store_id`),
  ADD KEY `idx_code` (`code`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_client` (`client_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_payment_status` (`payment_status`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_orders_user_id` (`user_id`);

--
-- Indexes for table `order_addons`
--
ALTER TABLE `order_addons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_item_id` (`order_item_id`),
  ADD KEY `idx_addon_id` (`addon_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order` (`order_id`),
  ADD KEY `idx_product` (`product_id`),
  ADD KEY `idx_order_items_order_id` (`order_id`),
  ADD KEY `idx_order_items_product_id` (`product_id`);

--
-- Indexes for table `order_item_options`
--
ALTER TABLE `order_item_options`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_item` (`order_item_id`);

--
-- Indexes for table `pages`
--
ALTER TABLE `pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_is_published` (`is_published`);

--
-- Indexes for table `point_transactions`
--
ALTER TABLE `point_transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_order_earn` (`ref_type`,`ref_id`,`type`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_client_id` (`client_id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category_id`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_is_featured` (`is_featured`),
  ADD KEY `idx_stock` (`stock_qty`);

--
-- Indexes for table `product_addons`
--
ALTER TABLE `product_addons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_addon_unique` (`product_id`,`addon_id`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_addon_id` (`addon_id`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product` (`product_id`),
  ADD KEY `idx_is_cover` (`is_cover`);

--
-- Indexes for table `product_milks`
--
ALTER TABLE `product_milks`
  ADD PRIMARY KEY (`product_id`,`milk_id`),
  ADD KEY `milk_id` (`milk_id`);

--
-- Indexes for table `product_recipes`
--
ALTER TABLE `product_recipes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product` (`product_id`),
  ADD KEY `idx_ingredient` (`ingredient_id`);

--
-- Indexes for table `promos`
--
ALTER TABLE `promos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_dates` (`starts_at`,`ends_at`);

--
-- Indexes for table `redemptions`
--
ALTER TABLE `redemptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_client_id` (`client_id`),
  ADD KEY `idx_reward_id` (`reward_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_voucher_code` (`voucher_code`);

--
-- Indexes for table `rewards`
--
ALTER TABLE `rewards`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_required_points` (`required_points`);

--
-- Indexes for table `reward_catalog`
--
ALTER TABLE `reward_catalog`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_points_cost` (`points_cost`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `key` (`key`),
  ADD UNIQUE KEY `unique_key` (`key`);

--
-- Indexes for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_product` (`product_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `stores`
--
ALTER TABLE `stores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `unique_oauth` (`oauth_provider`,`oauth_id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_users_role` (`role`);

--
-- Indexes for table `user_carts`
--
ALTER TABLE `user_carts`
  ADD PRIMARY KEY (`user_id`,`product_id`);

--
-- Indexes for table `vouchers`
--
ALTER TABLE `vouchers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `reward_id` (`reward_id`),
  ADD KEY `idx_code` (`code`),
  ADD KEY `idx_client` (`client_id`),
  ADD KEY `idx_status` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `addons`
--
ALTER TABLE `addons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `attendance_records`
--
ALTER TABLE `attendance_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gallery`
--
ALTER TABLE `gallery`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `ingredients`
--
ALTER TABLE `ingredients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ingredient_movements`
--
ALTER TABLE `ingredient_movements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `loyalty_ledger`
--
ALTER TABLE `loyalty_ledger`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `milks`
--
ALTER TABLE `milks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `order_addons`
--
ALTER TABLE `order_addons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `order_item_options`
--
ALTER TABLE `order_item_options`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pages`
--
ALTER TABLE `pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `point_transactions`
--
ALTER TABLE `point_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `product_addons`
--
ALTER TABLE `product_addons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `product_recipes`
--
ALTER TABLE `product_recipes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `promos`
--
ALTER TABLE `promos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `redemptions`
--
ALTER TABLE `redemptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `rewards`
--
ALTER TABLE `rewards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `reward_catalog`
--
ALTER TABLE `reward_catalog`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=186;

--
-- AUTO_INCREMENT for table `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stores`
--
ALTER TABLE `stores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `vouchers`
--
ALTER TABLE `vouchers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `ingredient_movements`
--
ALTER TABLE `ingredient_movements`
  ADD CONSTRAINT `ingredient_movements_ibfk_1` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ingredient_movements_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `loyalty_ledger`
--
ALTER TABLE `loyalty_ledger`
  ADD CONSTRAINT `loyalty_ledger_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `loyalty_ledger_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`pickup_store_id`) REFERENCES `stores` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_addons`
--
ALTER TABLE `order_addons`
  ADD CONSTRAINT `order_addons_ibfk_1` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_addons_ibfk_2` FOREIGN KEY (`addon_id`) REFERENCES `addons` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_item_options`
--
ALTER TABLE `order_item_options`
  ADD CONSTRAINT `order_item_options_ibfk_1` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `point_transactions`
--
ALTER TABLE `point_transactions`
  ADD CONSTRAINT `point_transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `point_transactions_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `product_addons`
--
ALTER TABLE `product_addons`
  ADD CONSTRAINT `product_addons_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_addons_ibfk_2` FOREIGN KEY (`addon_id`) REFERENCES `addons` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_milks`
--
ALTER TABLE `product_milks`
  ADD CONSTRAINT `product_milks_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_milks_ibfk_2` FOREIGN KEY (`milk_id`) REFERENCES `milks` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_recipes`
--
ALTER TABLE `product_recipes`
  ADD CONSTRAINT `product_recipes_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_recipes_ibfk_2` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `redemptions`
--
ALTER TABLE `redemptions`
  ADD CONSTRAINT `redemptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `redemptions_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `redemptions_ibfk_3` FOREIGN KEY (`reward_id`) REFERENCES `reward_catalog` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD CONSTRAINT `stock_movements_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_movements_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `vouchers`
--
ALTER TABLE `vouchers`
  ADD CONSTRAINT `vouchers_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `vouchers_ibfk_2` FOREIGN KEY (`reward_id`) REFERENCES `reward_catalog` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
