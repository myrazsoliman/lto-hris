-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 01, 2026 at 07:50 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `lto_hris`
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `first_name`, `middle_name`, `last_name`, `created_at`) VALUES
(3, 'superadmin@lto.gov.ph', '$2y$10$Sw4BAtV.WEE1dkKEiGVbE.pjR0vTOZ/QoshiFavvlUE4gM95Nyt6e', 'Super', NULL, 'Admin', '2026-03-31 17:42:00'),
(4, 'admin@lto.gov.ph', '$2y$10$AxI6w9RfyRY.0Hb7Eyqbcul2QjbPDws/eM.YQ846BcphuYi7XdEpq', 'System', NULL, 'Administrator', '2026-03-31 17:42:00'),
(5, 'lto.employee@gmail.con', '$2y$10$naAOJtMiZT3IjyOzi/vBROaQePy09okqJrEkFmZdsU76E1tcierLK', 'John Rommel', NULL, 'Delos Angeles', '2026-04-01 03:09:48'),
(6, 'delosangelesjanjan@gmail.com', '$2y$10$3JYeg5Z4C8cGE.QIhADPXONQOwEkLBxzf2jPQDp3ELc.MsNavye.u', 'John Rommel', NULL, 'Delos Angeles', '2026-04-01 05:13:39');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
