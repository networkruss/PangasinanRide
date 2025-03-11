-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 25, 2025 at 12:02 AM
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
-- Database: `pr_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `booking`
--

CREATE TABLE `booking` (
  `id` int(11) NOT NULL,
  `owner_uuid` varchar(36) NOT NULL,
  `booker_uuid` varchar(36) NOT NULL,
  `car_id` int(11) NOT NULL,
  `status` enum('approved','declined','pending','completed','cancelled') NOT NULL DEFAULT 'pending',
  `amount` decimal(10,2) NOT NULL,
  `pickup_date` date NOT NULL,
  `dropoff_date` date NOT NULL,
  `pickup_location` varchar(255) NOT NULL,
  `dropoff_location` varchar(255) NOT NULL,
  `booked_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `car_listings`
--

CREATE TABLE `car_listings` (
  `id` int(11) NOT NULL,
  `owner_uuid` varchar(36) NOT NULL,
  `brand` varchar(100) NOT NULL,
  `model` varchar(100) NOT NULL,
  `year` year(4) NOT NULL,
  `rental` decimal(10,2) NOT NULL,
  `max_speed` int(11) NOT NULL,
  `fuel_type` enum('petrol','diesel','hybrid','electric') NOT NULL,
  `seats` int(11) NOT NULL,
  `car_image` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `availability` enum('booked','available') NOT NULL DEFAULT 'available',
  `status` enum('approved','declined','pending') NOT NULL DEFAULT 'pending',
  `location` varchar(255) NOT NULL,
  `listed_on` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `feedbacks`
--

CREATE TABLE `feedbacks` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `feedback` text NOT NULL,
  `rating` varchar(255) NOT NULL,
  `car_id` int(11) NOT NULL,
  `booker_uuid` varchar(36) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `version` varchar(255) NOT NULL,
  `class` varchar(255) NOT NULL,
  `group` varchar(255) NOT NULL,
  `namespace` varchar(255) NOT NULL,
  `time` int(11) NOT NULL,
  `batch` int(11) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES
(25, '2024-10-07-000035', 'App\\Database\\Migrations\\CreateUserAuthTable', 'default', 'App', 1737757457, 1),
(26, '2024-10-07-002715', 'App\\Database\\Migrations\\CreateUserInfoTable', 'default', 'App', 1737757457, 1),
(27, '2024-10-07-004908', 'App\\Database\\Migrations\\CreateUserAddressTable', 'default', 'App', 1737757457, 1),
(28, '2024-10-07-005620', 'App\\Database\\Migrations\\CreateCarListingsTable', 'default', 'App', 1737757457, 1),
(29, '2024-10-07-010830', 'App\\Database\\Migrations\\CreateBookingTable', 'default', 'App', 1737757457, 1),
(30, '2024-10-07-012129', 'App\\Database\\Migrations\\CreateTransactionsTable', 'default', 'App', 1737757457, 1),
(31, '2024-10-07-013453', 'App\\Database\\Migrations\\CreateNotificationsTable', 'default', 'App', 1737757457, 1),
(32, '2024-10-10-050403', 'App\\Database\\Migrations\\FeedbackMigration', 'default', 'App', 1737757457, 1);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `read` tinyint(1) NOT NULL DEFAULT 0,
  `notified_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `transaction_id` int(11) NOT NULL,
  `owner_uuid` varchar(36) NOT NULL,
  `booker_uuid` varchar(36) NOT NULL,
  `car_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','card','e-wallet') NOT NULL,
  `transaction_status` enum('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  `paid_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_address_tbl`
--

CREATE TABLE `user_address_tbl` (
  `uuid` varchar(36) NOT NULL,
  `house_no` varchar(20) NOT NULL,
  `street` varchar(100) NOT NULL,
  `barangay` varchar(100) NOT NULL,
  `city` varchar(100) NOT NULL,
  `zip` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_auth_tbl`
--

CREATE TABLE `user_auth_tbl` (
  `id` int(11) NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `verified` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_info_tbl`
--

CREATE TABLE `user_info_tbl` (
  `uuid` varchar(36) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `gender` enum('male','female','other') NOT NULL,
  `phone` varchar(15) NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `driver_license` varchar(50) NOT NULL,
  `driver_license_expiry` date DEFAULT NULL,
  `vehicle_plate_number` varchar(255) NOT NULL,
  `ownership` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `booking`
--
ALTER TABLE `booking`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_owner_uuid_foreign` (`owner_uuid`),
  ADD KEY `booking_booker_uuid_foreign` (`booker_uuid`),
  ADD KEY `booking_car_id_foreign` (`car_id`);

--
-- Indexes for table `car_listings`
--
ALTER TABLE `car_listings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `car_listings_owner_uuid_foreign` (`owner_uuid`);

--
-- Indexes for table `feedbacks`
--
ALTER TABLE `feedbacks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notifications_uuid_foreign` (`uuid`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `transactions_owner_uuid_foreign` (`owner_uuid`),
  ADD KEY `transactions_booker_uuid_foreign` (`booker_uuid`),
  ADD KEY `transactions_car_id_foreign` (`car_id`);

--
-- Indexes for table `user_address_tbl`
--
ALTER TABLE `user_address_tbl`
  ADD PRIMARY KEY (`uuid`);

--
-- Indexes for table `user_auth_tbl`
--
ALTER TABLE `user_auth_tbl`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_info_tbl`
--
ALTER TABLE `user_info_tbl`
  ADD PRIMARY KEY (`uuid`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `booking`
--
ALTER TABLE `booking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `car_listings`
--
ALTER TABLE `car_listings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `feedbacks`
--
ALTER TABLE `feedbacks`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_auth_tbl`
--
ALTER TABLE `user_auth_tbl`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `booking`
--
ALTER TABLE `booking`
  ADD CONSTRAINT `booking_booker_uuid_foreign` FOREIGN KEY (`booker_uuid`) REFERENCES `user_auth_tbl` (`uuid`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `booking_car_id_foreign` FOREIGN KEY (`car_id`) REFERENCES `car_listings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `booking_owner_uuid_foreign` FOREIGN KEY (`owner_uuid`) REFERENCES `user_auth_tbl` (`uuid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `car_listings`
--
ALTER TABLE `car_listings`
  ADD CONSTRAINT `car_listings_owner_uuid_foreign` FOREIGN KEY (`owner_uuid`) REFERENCES `user_auth_tbl` (`uuid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_uuid_foreign` FOREIGN KEY (`uuid`) REFERENCES `user_auth_tbl` (`uuid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_booker_uuid_foreign` FOREIGN KEY (`booker_uuid`) REFERENCES `user_auth_tbl` (`uuid`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `transactions_car_id_foreign` FOREIGN KEY (`car_id`) REFERENCES `car_listings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `transactions_owner_uuid_foreign` FOREIGN KEY (`owner_uuid`) REFERENCES `user_auth_tbl` (`uuid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_address_tbl`
--
ALTER TABLE `user_address_tbl`
  ADD CONSTRAINT `user_address_tbl_uuid_foreign` FOREIGN KEY (`uuid`) REFERENCES `user_auth_tbl` (`uuid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_info_tbl`
--
ALTER TABLE `user_info_tbl`
  ADD CONSTRAINT `user_info_tbl_uuid_foreign` FOREIGN KEY (`uuid`) REFERENCES `user_auth_tbl` (`uuid`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
