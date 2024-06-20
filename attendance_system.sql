-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 20, 2024 at 09:36 PM
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
  `scan_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `mode` varchar(50) NOT NULL,
  `data` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `user_id`, `scan_time`, `mode`, `data`, `latitude`, `longitude`, `timestamp`) VALUES
(1, 1, '2024-06-18 14:23:25', '', NULL, NULL, NULL, '2024-06-20 16:03:00'),
(2, 2, '2024-06-19 14:27:05', '', NULL, NULL, NULL, '2024-06-20 16:03:00'),
(3, 1, '2024-06-19 14:46:36', '', NULL, NULL, NULL, '2024-06-20 16:03:00'),
(4, 3, '2024-06-19 15:11:39', '', NULL, NULL, NULL, '2024-06-20 16:03:00'),
(5, 1, '2024-06-20 16:03:54', 'Office', 'http://en.m.wikipedia.org', NULL, NULL, '2024-06-20 12:33:54'),
(6, 1, '2024-06-20 16:04:10', 'Outdoor', NULL, 19.13450000, 72.91170000, '2024-06-20 12:34:10'),
(7, 1, '2024-06-20 16:06:01', 'Outdoor', NULL, 19.13450000, 72.91170000, '2024-06-20 12:36:01'),
(8, 1, '2024-06-20 18:12:08', 'Outdoor', NULL, 19.13450000, 72.91170000, '2024-06-20 14:42:08'),
(9, 1, '2024-06-20 18:14:37', 'Outdoor', NULL, 19.13450000, 72.91170000, '2024-06-20 14:44:37'),
(10, 1, '2024-06-20 18:15:02', 'Outdoor', NULL, 19.13450000, 72.91170000, '2024-06-20 14:45:02'),
(11, 1, '2024-06-20 18:16:20', 'Outdoor', NULL, 19.13450000, 72.91170000, '2024-06-20 14:46:20'),
(12, 1, '2024-06-20 18:17:17', 'Outdoor', NULL, 19.13450000, 72.91170000, '2024-06-20 14:47:17'),
(13, 1, '2024-06-20 18:17:43', 'Outdoor', NULL, 19.13450000, 72.91170000, '2024-06-20 14:47:43'),
(14, 1, '2024-06-20 18:18:58', 'Outdoor', NULL, 19.13450000, 72.91170000, '2024-06-20 14:48:58'),
(15, 1, '2024-06-20 18:25:20', 'Outdoor', NULL, 19.13450000, 72.91170000, '2024-06-20 14:55:20'),
(16, 1, '2024-06-20 18:29:24', 'Outdoor', NULL, 19.13450000, 72.91170000, '2024-06-20 18:29:24');

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
  `device_id` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `employer_id`, `full_name`, `email`, `phone_number`, `passport_size_photo`, `address`, `device_id`) VALUES
(1, 'admin', '$2y$10$5AYG89NvsG47HC.fMgjjNOVvrbi7uqDFXeyNVFluTQ4kc9kh9V6oK', 'user', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 'admin1', '$2y$10$jx4hKb4SN8YeInl0GYBhCOiCNMict/EQ7jw7zNI/sICA6fceQmEGS', 'user', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 'admin2', '$2y$10$ga2WIAVzkDf8OpvfWsdovuDwpA8aEgZbbb48IqcsdMVjfQRYWVCbe', 'user', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(5, 'Mesco Admin', '$2y$10$rQohNR7CGWGen9iV9pXEEuwhwfa6Vy1Qt1d4HayiDfbo2L6SE.hvy', 'admin', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(7, 'Alfiya', '$2y$10$MQYU2mhsyuxVTvpWVMn/vOgAYgLPksxUJDxq1RwQB048pUWC2S1Iy', 'user', 82, 'Shaikh Alfiya', 'alfiya@gmail.com', '9136207148', 'uploads/1718910747_2 women.jpeg', 'Mumbai 400019', '6d76ac9d7e6a55b92ea4c67cac4aba6b'),
(8, 'muzaffar', '$2y$10$JiCdkJA/MmrE07eqKQ0Xk.KhaNciCWQrKECO6C//4mY1p.MWuTGYO', 'user', 46, 'Muzaffar Shaikh', 'muzaffar@gmail.com', '9136207140', 'uploads/1718910911_taylor s.jpeg', 'Mumbai 400039', '6d76ac9d7e6a55b92ea4c67cac4aba6b');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

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
