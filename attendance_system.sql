-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 15, 2024 at 09:35 PM
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
-- Database: `attendance_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `mode` varchar(50) NOT NULL,
  `data` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `selfie_in` mediumblob DEFAULT NULL,
  `type` enum('In','Out') NOT NULL,
  `in_time` datetime DEFAULT NULL,
  `out_time` datetime DEFAULT NULL,
  `is_present` tinyint(1) DEFAULT 0,
  `selfie_out` mediumblob DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `user_id`, `mode`, `data`, `latitude`, `longitude`, `selfie_in`, `type`, `in_time`, `out_time`, `is_present`, `selfie_out`) VALUES
(1, 1, '', NULL, NULL, NULL, NULL, 'In', NULL, NULL, 0, NULL),
(2, 2, '', NULL, NULL, NULL, NULL, 'In', NULL, NULL, 0, NULL),
(3, 1, '', NULL, NULL, NULL, NULL, 'In', NULL, NULL, 0, NULL),
(4, 3, '', NULL, NULL, NULL, NULL, 'In', NULL, NULL, 0, NULL),
(5, 1, 'Office', 'http://en.m.wikipedia.org', NULL, NULL, NULL, 'In', NULL, NULL, 1, NULL),
(6, 1, 'Outdoor', NULL, 19.13450000, 72.91170000, NULL, 'In', NULL, NULL, 0, NULL),
(7, 1, 'Outdoor', NULL, 19.13450000, 72.91170000, NULL, 'In', NULL, NULL, 0, NULL),
(8, 1, 'Outdoor', NULL, 19.13450000, 72.91170000, NULL, 'In', NULL, NULL, 0, NULL),
(9, 1, 'Outdoor', NULL, 19.13450000, 72.91170000, NULL, 'In', NULL, NULL, 0, NULL),
(10, 1, 'Outdoor', NULL, 19.13450000, 72.91170000, NULL, 'In', NULL, NULL, 0, NULL),
(11, 1, 'Outdoor', NULL, 19.13450000, 72.91170000, NULL, 'In', NULL, NULL, 0, NULL),
(12, 1, 'Outdoor', NULL, 19.13450000, 72.91170000, NULL, 'In', NULL, NULL, 0, NULL),
(13, 1, 'Outdoor', NULL, 19.13450000, 72.91170000, NULL, 'In', NULL, NULL, 0, NULL),
(14, 1, 'Outdoor', NULL, 19.13450000, 72.91170000, NULL, 'In', NULL, NULL, 0, NULL),
(108, 5, 'Outdoor', NULL, 19.07480000, 72.88560000, NULL, 'In', '2024-07-16 00:57:26', NULL, 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `deletion_log`
--

CREATE TABLE `deletion_log` (
  `id` int(11) NOT NULL,
  `last_deletion` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deletion_log`
--

INSERT INTO `deletion_log` (`id`, `last_deletion`) VALUES
(1, '2024-07-16 00:48:45'),
(2, '2024-07-16 00:52:54'),
(3, '2024-07-16 00:55:35'),
(4, '2024-07-16 00:55:42'),
(5, '2024-07-16 00:55:44'),
(6, '2024-07-16 00:55:48'),
(7, '2024-07-16 00:55:51'),
(8, '2024-07-16 00:55:54'),
(9, '2024-07-16 00:59:36');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `employer_id` int(11) DEFAULT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `passport_size_photo` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `department` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `employer_id`, `full_name`, `email`, `phone_number`, `passport_size_photo`, `address`, `department`) VALUES
(1, 'admin', '$2y$10$UXmq0ZlDumehBETVIybuqu6tL2QwoUXV76UGNe79cgJoL/SOQbJ2K', 'admin', 8554, 'afedsvhyku', 'alsb@gmail.com', '87998844', NULL, 'ewrdfvd', 'RC Mahim'),
(2, 'admin1', '$2y$10$6zHXLg8aX8Z43itLNEclIOXTnedgoQ.Pgo/hUIs/aum9F4VGcUPIa', 'user', 96546, 'admin herfedv', 'admin1523@gmail.com', '9136241545', NULL, 'rgevsdf', 'ROP'),
(3, 'admin2', '$2y$10$ga2WIAVzkDf8OpvfWsdovuDwpA8aEgZbbb48IqcsdMVjfQRYWVCbe', 'user', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(5, 'Mesco Admin', '$2y$10$rQohNR7CGWGen9iV9pXEEuwhwfa6Vy1Qt1d4HayiDfbo2L6SE.hvy', 'admin', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(7, 'Alfiya', '$2y$10$MQYU2mhsyuxVTvpWVMn/vOgAYgLPksxUJDxq1RwQB048pUWC2S1Iy', 'user', 82, 'Shaikh Alfiya', 'alfiya@gmail.com', '9136207148', 'uploads/1718910747_2 women.jpeg', 'Mumbai 400019', 'Education'),
(8, 'muzaffar', '$2y$10$JiCdkJA/MmrE07eqKQ0Xk.KhaNciCWQrKECO6C//4mY1p.MWuTGYO', 'user', 46, 'Muzaffar Shaikh', 'muzaffar@gmail.com', '9136207140', 'uploads/1718910911_taylor s.jpeg', 'Mumbai 400039', NULL),
(9, 'vasi', '$2y$10$gNW6w1rokyP8P8qMc2Bhceqf65Z4ZPHVUXoPLTt.YlpIC9mQEirey', 'user', 69, 'Vasi Sayyed', 'Vasisayed09421@gmail.com', '8104771784', 'uploads/1718984381_5 women.jpeg', 'Kamla nagar', NULL),
(10, 'test', '$2y$10$VA9tbKFjeIakK6PRAEOc7ObHUUnHXyVUsojiFCTt1j4eCtz1xYWqq', 'user', 58, 'Test Jhon', 'test@gmail.com', '9136542025', 'uploads/1719329921_WhatsApp Image 2023-09-21 at 10.59.42 PM.jpeg', 'Near mahim', NULL),
(11, 'new', '$2y$10$8GAcV17MJfXhg6.IJRQAte8hFXr46CQjTjZFkKrU8W6s/Ng4MwFIS', 'user', 12, 'new', 'new@gmail.com', '465464646', 'uploads/1719418665_hairr.png', 'nea new he', '0'),
(12, 'hehe', '$2y$10$Taw44Z26Pd/duOtznFLc7.yMtLlpMeAXzSz3nyxUJOroSNjFSsgtq', 'user', 45, 'hehe', 'hehe@gmail.com', '5465454534', 'uploads/1719419506_1691778874508.jpg', '', 'Education'),
(14, 'xyz', '$2y$10$ynchnS7ONOnyCAP6bvFWvOSska1fHV9Lur17kPTiiPkNcOwvD5xJy', 'user', 52, 'xyx', 'xyz@gmail.com', '486864684', '../uploads/1719769769_taylor s.jpeg', 'edrgrfd', 'Medical'),
(15, 'Zeeshan', '$2y$10$.TTBeh5GEwO72pQ9mw05WOkAt5RcFTla6h1Bf/vOyytWAw13/RJPC', 'user', 854, 'zeeshan Shaikh', 'zee@gmail.com', '6874684684', '../uploads/1720863668_hair.png', '&lt;script&gt;alert(&quot;Hello there&quot;);&lt;/script&gt;', 'Education'),
(16, 'alfu', '$2y$10$cz5ymuwvhCrs48X6euMZZ.gTkPCLLflUe1gnD74GhEtu0UXEsphJu', 'user', 6654, 'alfu', 'alfu@mail.com', '546546546', '../uploads/1720864045_2 women.jpeg', '&lt;script&gt;alert(&quot;Hello&quot;);&lt;/script&gt;', 'Admin');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `deletion_log`
--
ALTER TABLE `deletion_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=109;

--
-- AUTO_INCREMENT for table `deletion_log`
--
ALTER TABLE `deletion_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
