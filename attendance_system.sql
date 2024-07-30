-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 30, 2024 at 09:57 PM
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
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `selfie_in` varchar(255) DEFAULT NULL,
  `type` enum('In','Out') NOT NULL,
  `in_time` datetime DEFAULT NULL,
  `out_time` datetime DEFAULT NULL,
  `is_present` tinyint(1) DEFAULT 0,
  `selfie_out` varchar(255) DEFAULT NULL,
  `data` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `user_id`, `mode`, `latitude`, `longitude`, `selfie_in`, `type`, `in_time`, `out_time`, `is_present`, `selfie_out`, `data`) VALUES
(1, 1, '', NULL, NULL, NULL, 'In', NULL, NULL, 0, NULL, NULL),
(2, 2, '', NULL, NULL, NULL, 'In', NULL, NULL, 0, NULL, NULL),
(3, 1, '', NULL, NULL, NULL, 'In', NULL, NULL, 0, NULL, NULL),
(4, 3, '', NULL, NULL, NULL, 'In', NULL, NULL, 0, NULL, NULL),
(5, 1, 'Office', NULL, NULL, NULL, 'In', NULL, NULL, 1, NULL, NULL),
(6, 1, 'Outdoor', 19.13450000, 72.91170000, NULL, 'In', NULL, NULL, 0, NULL, NULL),
(7, 1, 'Outdoor', 19.13450000, 72.91170000, NULL, 'In', NULL, NULL, 0, NULL, NULL),
(8, 1, 'Outdoor', 19.13450000, 72.91170000, NULL, 'In', NULL, NULL, 0, NULL, NULL),
(9, 1, 'Outdoor', 19.13450000, 72.91170000, NULL, 'In', NULL, NULL, 0, NULL, NULL),
(10, 1, 'Outdoor', 19.13450000, 72.91170000, NULL, 'In', NULL, NULL, 0, NULL, NULL),
(11, 1, 'Outdoor', 19.13450000, 72.91170000, NULL, 'In', NULL, NULL, 0, NULL, NULL),
(12, 1, 'Outdoor', 19.13450000, 72.91170000, NULL, 'In', NULL, NULL, 0, NULL, NULL),
(13, 1, 'Outdoor', 19.13450000, 72.91170000, NULL, 'In', NULL, NULL, 0, NULL, NULL),
(14, 1, 'Outdoor', 19.13450000, 72.91170000, NULL, 'In', NULL, NULL, 0, NULL, NULL),
(144, 2, 'Outdoor', 19.07480000, 72.88560000, NULL, 'In', '2024-07-22 23:51:38', '2024-07-23 21:51:49', 1, NULL, NULL),
(145, 5, 'Outdoor', 19.07480000, 72.88560000, NULL, 'In', '2024-07-23 00:04:46', '2024-07-24 00:03:16', 1, NULL, NULL),
(146, 5, 'Outdoor', 19.07480000, 72.88560000, NULL, 'In', '2024-07-23 21:48:18', '2024-07-24 00:03:16', 1, NULL, NULL),
(147, 5, 'Outdoor', 19.07480000, 72.88560000, NULL, 'In', '2024-07-23 21:49:43', '2024-07-24 00:03:16', 1, NULL, NULL),
(148, 2, 'Outdoor', 19.07480000, 72.88560000, NULL, 'In', '2024-07-23 21:51:44', '2024-07-23 21:51:49', 1, NULL, NULL),
(149, 2, 'Office', NULL, NULL, NULL, 'In', '2024-07-23 21:52:48', '2024-07-23 21:52:59', 1, NULL, 'http://en.m.wikipedia.org'),
(150, 8, 'Outdoor', 19.07480000, 72.88560000, NULL, 'In', '2024-07-23 22:20:53', '2024-07-23 22:20:58', 1, NULL, NULL),
(151, 7, 'Outdoor', 19.07480000, 72.88560000, NULL, 'In', '2024-07-23 22:34:46', '2024-07-23 22:34:52', 1, NULL, NULL),
(152, 5, 'Outdoor', 19.07480000, 72.88560000, NULL, 'In', '2024-07-24 00:03:10', '2024-07-24 00:21:25', 1, NULL, NULL),
(153, 2, 'Outdoor', 19.07480000, 72.88560000, NULL, 'In', '2024-07-24 00:09:28', '2024-07-24 00:20:25', 1, NULL, NULL),
(154, 5, 'Outdoor', 19.07480000, 72.88560000, NULL, 'In', '2024-07-24 00:21:11', '2024-07-24 00:21:25', 1, NULL, NULL),
(159, 5, 'Outdoor', 19.07480000, 72.88560000, NULL, 'In', '2024-07-25 00:52:50', '2024-07-25 00:58:16', 1, NULL, NULL),
(160, 5, 'Outdoor', 19.07480000, 72.88560000, NULL, 'In', '2024-07-25 00:58:10', '2024-07-25 00:58:16', 1, NULL, NULL),
(161, 5, 'Outdoor', 19.07480000, 72.88560000, NULL, 'In', '2024-07-26 21:28:31', '2024-07-26 21:28:36', 1, NULL, NULL),
(162, 2, 'Office', NULL, NULL, NULL, 'In', '2024-07-27 00:33:50', '2024-07-27 00:34:29', 1, NULL, 'http://en.m.wikipedia.org'),
(163, 2, 'Outdoor', 19.07480000, 72.88560000, NULL, 'In', '2024-07-27 00:35:30', '2024-07-27 00:35:49', 1, NULL, NULL),
(164, 5, 'Outdoor', 19.07480000, 72.88560000, NULL, 'In', '2024-07-29 21:05:20', '2024-07-29 23:58:57', 0, NULL, NULL),
(165, 5, 'Outdoor', 19.07480000, 72.88560000, NULL, 'In', '2024-07-29 21:25:24', '2024-07-29 23:58:57', 1, NULL, NULL),
(166, 5, 'Outdoor', 19.07480000, 72.88560000, 'admin/Selfies_in&out/Mesco Admin/Mesco Admin_in_Outdoor20240730_000249.jpg', 'In', '2024-07-30 00:02:49', '2024-07-30 22:58:05', 1, 'admin/Selfies_in&out/Mesco Admin/Mesco Admin_out_Outdoor20240730_225805.jpg', NULL),
(167, 5, 'Outdoor', 19.07480000, 72.88560000, 'admin/Selfies_in&out/Mesco Admin/Mesco Admin_in_Outdoor20240730_000253.jpg', 'In', '2024-07-30 00:02:53', '2024-07-30 22:58:05', 1, 'admin/Selfies_in&out/Mesco Admin/Mesco Admin_out_Outdoor20240730_225805.jpg', NULL),
(168, 2, 'Office', NULL, NULL, 'admin/Selfies_in&out/admin1/admin1_in_Office20240730_223534.jpg', 'In', '2024-07-30 22:35:34', NULL, 1, NULL, 'http://en.m.wikipedia.org'),
(169, 2, 'Outdoor', 19.07480000, 72.88560000, NULL, 'In', '2024-07-30 23:20:11', '2024-07-30 23:20:11', 0, 'admin/Selfies_in&out/admin1/admin1_out_Outdoor20240730_232011.jpg', NULL),
(170, 9, 'Outdoor', 19.07480000, 72.88560000, 'admin/Selfies_in&out/vasi/vasi_in_Outdoor20240730_233102.jpg', 'In', '2024-07-30 23:31:02', NULL, 1, NULL, NULL),
(171, 9, 'Outdoor', 19.07480000, 72.88560000, 'admin/Selfies_in&out/vasi/vasi_in_Outdoor20240731_002500.jpg', 'In', '2024-07-31 00:25:00', '2024-07-31 00:25:05', 1, 'admin/Selfies_in&out/vasi/vasi_out_Outdoor20240731_002505.jpg', NULL);

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
(9, '2024-07-16 00:59:36'),
(10, '2024-07-21 20:46:10'),
(11, '2024-07-22 02:01:05'),
(12, '2024-07-23 23:47:10'),
(13, '2024-07-24 00:08:44'),
(14, '2024-07-24 00:12:33'),
(15, '2024-07-24 00:22:34'),
(16, '2024-07-25 00:54:57'),
(17, '2024-07-29 23:27:41'),
(18, '2024-07-29 23:29:30'),
(19, '2024-07-29 23:58:31'),
(20, '2024-07-30 00:00:59');

-- --------------------------------------------------------

--
-- Table structure for table `final_attendance`
--

CREATE TABLE `final_attendance` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `first_in` datetime DEFAULT NULL,
  `last_out` datetime DEFAULT NULL,
  `first_mode` varchar(50) DEFAULT NULL,
  `last_mode` varchar(50) DEFAULT NULL,
  `total_hours` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `final_attendance`
--

INSERT INTO `final_attendance` (`id`, `user_id`, `date`, `first_in`, `last_out`, `first_mode`, `last_mode`, `total_hours`) VALUES
(1, 2, '2024-07-21', '2024-07-21 17:40:29', '2024-07-21 22:40:21', 'Office', 'Outdoor', NULL),
(18, 2, '2024-07-20', '2024-07-20 17:40:29', '2024-07-20 18:23:31', 'Office', 'Outdoor', NULL),
(19, 5, '2024-07-21', '2024-07-21 18:52:10', '2024-07-21 22:42:38', 'Outdoor', 'Office', NULL),
(60, 2, '2024-07-22', '2024-07-22 23:08:52', '2024-07-22 23:51:44', 'Outdoor', 'Outdoor', NULL),
(69, 5, '2024-07-23', '2024-07-23 00:04:46', '2024-07-23 21:49:50', 'Outdoor', 'Outdoor', NULL),
(75, 2, '2024-07-23', '2024-07-23 21:51:44', '2024-07-23 21:52:59', 'Outdoor', 'Office', NULL),
(79, 8, '2024-07-23', '2024-07-23 22:20:53', '2024-07-23 22:20:58', 'Outdoor', 'Outdoor', NULL),
(81, 7, '2024-07-23', '2024-07-23 22:34:46', '2024-07-23 22:34:52', 'Outdoor', 'Outdoor', NULL),
(83, 5, '2024-07-24', '2024-07-24 00:03:10', '2024-07-24 00:21:25', 'Outdoor', 'Outdoor', NULL),
(85, 2, '2024-07-24', '2024-07-24 00:09:28', '2024-07-24 00:20:25', 'Outdoor', 'Outdoor', NULL),
(90, 5, '2024-07-25', '2024-07-25 00:01:51', '2024-07-25 00:58:16', 'Outdoor', 'Outdoor', NULL),
(101, 5, '2024-07-26', '2024-07-26 21:28:31', '2024-07-26 21:28:36', 'Outdoor', 'Outdoor', NULL),
(103, 2, '2024-07-27', '2024-07-27 00:33:50', '2024-07-27 00:35:49', 'Office', 'Outdoor', NULL),
(107, 5, '2024-07-29', '2024-07-29 21:05:20', '2024-07-29 23:58:57', 'Outdoor', 'Outdoor', 2.88),
(116, 5, '2024-07-30', '2024-07-30 00:02:49', '2024-07-30 22:58:05', 'Outdoor', 'Outdoor', 22.92),
(119, 2, '2024-07-30', '2024-07-30 22:35:34', '2024-07-30 23:20:11', 'Office', 'Outdoor', 0.73),
(124, 9, '2024-07-30', '2024-07-30 23:31:02', NULL, 'Outdoor', NULL, NULL),
(125, 9, '2024-07-31', '2024-07-31 00:25:00', '2024-07-31 00:25:05', 'Outdoor', 'Outdoor', 0.00);

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
  `department` varchar(50) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `employer_id`, `full_name`, `email`, `phone_number`, `passport_size_photo`, `address`, `department`, `deleted_at`) VALUES
(1, 'admin', '$2y$10$UXmq0ZlDumehBETVIybuqu6tL2QwoUXV76UGNe79cgJoL/SOQbJ2K', 'admin', 8554, 'afedsvhyku', 'alsb@gmail.com', '87998844', NULL, 'ewrdfvd', 'RC Mahim', NULL),
(2, 'admin1', '$2y$10$6zHXLg8aX8Z43itLNEclIOXTnedgoQ.Pgo/hUIs/aum9F4VGcUPIa', 'user', 96546, 'admin herfedv', 'admin1523@gmail.com', '9136241545', NULL, 'rgevsdf', 'ROP', NULL),
(3, 'admin2', '$2y$10$ga2WIAVzkDf8OpvfWsdovuDwpA8aEgZbbb48IqcsdMVjfQRYWVCbe', 'user', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(5, 'Mesco Admin', '$2y$10$rQohNR7CGWGen9iV9pXEEuwhwfa6Vy1Qt1d4HayiDfbo2L6SE.hvy', 'admin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(7, 'Alfiya', '$2y$10$MQYU2mhsyuxVTvpWVMn/vOgAYgLPksxUJDxq1RwQB048pUWC2S1Iy', 'user', 82, 'Shaikh Alfiya', 'alfiya@gmail.com', '9136207148', 'uploads/1718910747_2 women.jpeg', 'Mumbai 400019', 'Education', NULL),
(8, 'muzaffar', '$2y$10$JiCdkJA/MmrE07eqKQ0Xk.KhaNciCWQrKECO6C//4mY1p.MWuTGYO', 'user', 46, 'Muzaffar Shaikh', 'muzaffar@gmail.com', '9136207140', 'uploads/1718910911_taylor s.jpeg', 'Mumbai 400039', 'Admin', NULL),
(9, 'vasi', '$2y$10$gNW6w1rokyP8P8qMc2Bhceqf65Z4ZPHVUXoPLTt.YlpIC9mQEirey', 'user', 69, 'Vasi Sayyed', 'Vasisayed09421@gmail.com', '8104771784', 'uploads/1718984381_5 women.jpeg', 'Kamla nagar', NULL, NULL),
(10, 'test', '$2y$10$VA9tbKFjeIakK6PRAEOc7ObHUUnHXyVUsojiFCTt1j4eCtz1xYWqq', 'user', 58, 'Test Jhon', 'test@gmail.com', '9136542025', 'uploads/1719329921_WhatsApp Image 2023-09-21 at 10.59.42 PM.jpeg', 'Near mahim', NULL, '2024-07-17 09:47:11'),
(11, 'new', '$2y$10$8GAcV17MJfXhg6.IJRQAte8hFXr46CQjTjZFkKrU8W6s/Ng4MwFIS', 'user', 12, 'new', 'new@gmail.com', '465464646', 'uploads/1719418665_hairr.png', 'nea new he', '0', NULL),
(12, 'hehe', '$2y$10$Taw44Z26Pd/duOtznFLc7.yMtLlpMeAXzSz3nyxUJOroSNjFSsgtq', 'user', 45, 'hehe', 'hehe@gmail.com', '5465454534', 'uploads/1719419506_1691778874508.jpg', '', 'Education', '2024-07-17 09:48:01'),
(14, 'xyz', '$2y$10$ynchnS7ONOnyCAP6bvFWvOSska1fHV9Lur17kPTiiPkNcOwvD5xJy', 'user', 52, 'xyx', 'xyz@gmail.com', '486864684', '../uploads/1719769769_taylor s.jpeg', 'edrgrfd', 'Medical', NULL),
(15, 'Zeeshan', '$2y$10$.TTBeh5GEwO72pQ9mw05WOkAt5RcFTla6h1Bf/vOyytWAw13/RJPC', 'user', 854, 'zeeshan Shaikh', 'zee@gmail.com', '6874684684', '../uploads/1720863668_hair.png', '&lt;script&gt;alert(&quot;Hello there&quot;);&lt;/script&gt;', 'Education', NULL),
(16, 'alfu', '$2y$10$cz5ymuwvhCrs48X6euMZZ.gTkPCLLflUe1gnD74GhEtu0UXEsphJu', 'user', 6654, 'alfu', 'alfu@mail.com', '546546546', '../uploads/1720864045_2 women.jpeg', '&lt;script&gt;alert(&quot;Hello&quot;);&lt;/script&gt;', 'Admin', NULL);

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
-- Indexes for table `final_attendance`
--
ALTER TABLE `final_attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`date`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=172;

--
-- AUTO_INCREMENT for table `deletion_log`
--
ALTER TABLE `deletion_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `final_attendance`
--
ALTER TABLE `final_attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=127;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

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
