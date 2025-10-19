-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 19, 2025 at 10:23 AM
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
-- Database: `cake_shop`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `GetSalesReport` (IN `startDate` DATE, IN `endDate` DATE)   BEGIN
    SELECT 
        DATE(order_date) as order_day,
        COUNT(*) as total_orders,
        SUM(total_amount) as total_revenue,
        AVG(total_amount) as average_order_value,
        SUM(quantity) as total_items_sold
    FROM orders
    WHERE order_date BETWEEN startDate AND endDate
    AND status = 'completed'
    GROUP BY DATE(order_date)
    ORDER BY order_day DESC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `GetUserOrderHistory` (IN `userId` INT)   BEGIN
    SELECT 
        o.*,
        c.image as cake_image,
        c.category as cake_category
    FROM orders o
    LEFT JOIN cakes c ON o.cake_id = c.id
    WHERE o.user_id = userId
    ORDER BY o.order_date DESC;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `cakes`
--

CREATE TABLE `cakes` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `weight` varchar(50) DEFAULT NULL,
  `availability` enum('available','out_of_stock') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cakes`
--

INSERT INTO `cakes` (`id`, `name`, `description`, `price`, `image`, `category`, `weight`, `availability`, `created_at`, `updated_at`) VALUES
(1, 'Chocolate Fudge Cake', 'Rich chocolate cake with fudge frosting', 599.00, 'chocolate-fudge.jpg', 'Chocolate', '1 kg', 'available', '2025-10-19 02:36:51', '2025-10-19 02:36:51'),
(2, 'Vanilla Dream Cake', 'Classic vanilla sponge with buttercream', 499.00, 'vanilla-dream.jpg', 'Vanilla', '1 kg', 'available', '2025-10-19 02:36:51', '2025-10-19 02:36:51'),
(3, 'Red Velvet Delight', 'Smooth red velvet with cream cheese frosting', 699.00, 'red-velvet.jpg', 'Special', '1 kg', 'available', '2025-10-19 02:36:51', '2025-10-19 02:36:51'),
(4, 'Black Forest Cake', 'Traditional black forest with cherries', 649.00, 'black-forest.jpg', 'Chocolate', '1 kg', 'available', '2025-10-19 02:36:51', '2025-10-19 02:36:51'),
(5, 'Strawberry Surprise', 'Fresh strawberry cake with whipped cream', 599.00, 'strawberry.jpg', 'Fruit', '1 kg', 'available', '2025-10-19 02:36:51', '2025-10-19 02:36:51'),
(6, 'Butterscotch Bliss', 'Butterscotch flavored cake with caramel', 549.00, 'butterscotch.jpg', 'Caramel', '1 kg', 'available', '2025-10-19 02:36:51', '2025-10-19 02:36:51'),
(7, 'Pineapple Paradise', 'Tropical pineapple cake with cream', 499.00, 'pineapple.jpg', 'Fruit', '1 kg', 'available', '2025-10-19 02:36:51', '2025-10-19 02:36:51'),
(8, 'Coffee Walnut Cake', 'Coffee flavored cake with walnuts', 629.00, 'coffee-walnut.jpg', 'Special', '1 kg', 'available', '2025-10-19 02:36:51', '2025-10-19 02:36:51'),
(9, 'Mango Magic', 'Seasonal mango cake with fresh pulp', 699.00, 'mango.jpg', 'Fruit', '1 kg', 'available', '2025-10-19 02:36:51', '2025-10-19 02:36:51'),
(10, 'Chocolate Truffle', 'Premium chocolate truffle cake', 799.00, 'chocolate-truffle.jpg', 'Chocolate', '1 kg', 'available', '2025-10-19 02:36:51', '2025-10-19 02:36:51'),
(11, 'Oreo Cheesecake', 'Creamy cheesecake with oreo crumbs', 749.00, 'oreo-cheesecake.jpg', 'Cheesecake', '1 kg', 'available', '2025-10-19 02:36:51', '2025-10-19 02:36:51'),
(12, 'Blueberry Bliss', 'Fresh blueberry cake with cream cheese', 699.00, 'blueberry.jpg', 'Fruit', '1 kg', 'available', '2025-10-19 02:36:51', '2025-10-19 02:36:51'),
(13, 'xczv', '2433esx', 434.00, 'https://i.pinimg.com/originals/fd/49/15/fd49150a45f56427ddc5da1da08861f4.jpg', 'szx', '3', 'available', '2025-10-19 07:11:14', '2025-10-19 07:11:14');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `cake_id` int(11) NOT NULL,
  `cake_name` varchar(150) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_id` varchar(100) DEFAULT NULL,
  `razorpay_order_id` varchar(100) DEFAULT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_email` varchar(100) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `customer_address` text NOT NULL,
  `special_instructions` text DEFAULT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_date` timestamp NULL DEFAULT NULL,
  `status` enum('pending','completed','processing','delivered','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `cake_id`, `cake_name`, `price`, `quantity`, `total_amount`, `payment_id`, `razorpay_order_id`, `customer_name`, `customer_email`, `customer_phone`, `customer_address`, `special_instructions`, `order_date`, `payment_date`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Chocolate Fudge Cake', 599.00, 1, 599.00, 'pay_sample123', 'order_sample123', 'John Doe', 'john@example.com', '9876543210', '123 Main Street, Mumbai, Maharashtra 400001', 'Please deliver before 6 PM', '2025-10-19 02:36:51', '2025-10-19 02:36:51', 'completed', '2025-10-19 02:36:51', '2025-10-19 02:36:51'),
(2, 2, 3, 'Red Velvet Delight', 699.00, 2, 1398.00, 'pay_sample124', 'order_sample124', 'Jane Smith', 'jane@example.com', '9876543211', '456 Park Avenue, Delhi 110001', 'Add birthday candles', '2025-10-19 02:36:51', '2025-10-19 02:36:51', 'completed', '2025-10-19 02:36:51', '2025-10-19 02:36:51'),
(3, 1, 5, 'Strawberry Surprise', 599.00, 1, 599.00, 'pay_sample125', 'order_sample125', 'John Doe', 'john@example.com', '9876543210', '123 Main Street, Mumbai, Maharashtra 400001', NULL, '2025-10-19 02:36:51', '2025-10-19 02:36:51', 'processing', '2025-10-19 02:36:51', '2025-10-19 02:36:51'),
(6, 1, 10, 'Chocolate Truffle', 799.00, 1, 799.00, 'pay_RVAvHu8mTJWn7h', 'NA', 'Guest User', 'guest@example.com', '09539923291', 'pullolil(h),pookottumanna (po) chungathara, nilambur', '', '2025-10-19 02:59:23', '2025-10-19 02:59:23', 'completed', '2025-10-19 02:59:23', '2025-10-19 02:59:23'),
(7, 4, 5, 'Strawberry Surprise', 599.00, 1, 599.00, 'pay_RVBXlovoPIsZoq', 'NA', 'Drishyajose', 'drishyajose2027@mca.ajce.in', '3456789876', 'hg', '', '2025-10-19 03:35:49', '2025-10-19 03:35:49', 'completed', '2025-10-19 03:35:49', '2025-10-19 03:35:49'),
(8, 1, 5, 'Strawberry Surprise', 599.00, 1, 599.00, 'pay_RVFQQFX8oVOK1q', 'NA', 'Guest User', 'guest@example.com', '123456899', 'asdfgh', '', '2025-10-19 07:23:40', '2025-10-19 07:23:40', 'completed', '2025-10-19 07:23:40', '2025-10-19 07:23:40');

--
-- Triggers `orders`
--
DELIMITER $$
CREATE TRIGGER `after_order_insert` AFTER INSERT ON `orders` FOR EACH ROW BEGIN
    -- You can add logic here to update inventory or send notifications
    -- For example, log the order
    INSERT INTO order_logs (order_id, action, created_at) 
    VALUES (NEW.id, 'Order Created', NOW());
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `order_logs`
--

CREATE TABLE `order_logs` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_logs`
--

INSERT INTO `order_logs` (`id`, `order_id`, `action`, `created_at`) VALUES
(1, 6, 'Order Created', '2025-10-19 02:59:23'),
(2, 7, 'Order Created', '2025-10-19 03:35:49'),
(3, 8, 'Order Created', '2025-10-19 07:23:40');

-- --------------------------------------------------------

--
-- Stand-in structure for view `order_summary`
-- (See below for the actual view)
--
CREATE TABLE `order_summary` (
`id` int(11)
,`order_date` timestamp
,`user_name` varchar(100)
,`user_email` varchar(100)
,`cake_name` varchar(150)
,`quantity` int(11)
,`total_amount` decimal(10,2)
,`status` enum('pending','completed','processing','delivered','cancelled')
,`customer_name` varchar(100)
,`customer_phone` varchar(20)
,`customer_address` text
);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password`, `created_at`, `updated_at`) VALUES
(1, 'John Doe', 'john@example.com', '9876543210', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-10-19 02:36:51', '2025-10-19 02:36:51'),
(2, 'Jane Smith', 'jane@example.com', '9876543211', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-10-19 02:36:51', '2025-10-19 02:36:51'),
(3, 'Mike Wilson', 'mike@example.com', '9876543212', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-10-19 02:36:51', '2025-10-19 02:36:51'),
(4, 'Drishyajose', 'drishyajose2027@mca.ajce.in', '3456789876', '$2y$10$sSHo0jFhoRC3leadJA.2d.lYfaI5IAzJn4n/DPk.e.sFMyt/YdrWu', '2025-10-19 03:12:15', '2025-10-19 03:12:15');

-- --------------------------------------------------------

--
-- Structure for view `order_summary`
--
DROP TABLE IF EXISTS `order_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `order_summary`  AS SELECT `o`.`id` AS `id`, `o`.`order_date` AS `order_date`, `u`.`name` AS `user_name`, `u`.`email` AS `user_email`, `o`.`cake_name` AS `cake_name`, `o`.`quantity` AS `quantity`, `o`.`total_amount` AS `total_amount`, `o`.`status` AS `status`, `o`.`customer_name` AS `customer_name`, `o`.`customer_phone` AS `customer_phone`, `o`.`customer_address` AS `customer_address` FROM (`orders` `o` join `users` `u` on(`o`.`user_id` = `u`.`id`)) ORDER BY `o`.`order_date` DESC ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cakes`
--
ALTER TABLE `cakes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_availability` (`availability`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_cake_id` (`cake_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_order_date` (`order_date`),
  ADD KEY `idx_payment_id` (`payment_id`);

--
-- Indexes for table `order_logs`
--
ALTER TABLE `order_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cakes`
--
ALTER TABLE `cakes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `order_logs`
--
ALTER TABLE `order_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`cake_id`) REFERENCES `cakes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_logs`
--
ALTER TABLE `order_logs`
  ADD CONSTRAINT `order_logs_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
