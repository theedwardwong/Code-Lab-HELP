-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 24, 2025 at 08:04 AM
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
-- Database: `codelab@help`
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) UNSIGNED NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('student','instructor','admin') NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `first_login` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `role`, `password`, `created_at`, `first_login`) VALUES
(1, 'Admin', 'admin@help.com', 'admin', '$2y$10$1nOnHwZ/JA.4Gj.oANFS6uHFKuth1E3VBZH7SnBv3xX0Q3C0t6IJS', '2025-04-08 06:36:24', 0),
(2, 'test', 'test@live.com', 'instructor', '$2y$10$SGl/OMjZl6laeJvw.U4IeuM.vVE0OeepAYJ3f391.g773dcsWRq6.', '2025-04-08 06:59:31', 0),
(3, 'Test Student', 'student@help.com', 'student', '$2y$10$Pn0uUIu638gPU1sXIvXZP.j4Gbv8pzTqcS6LnSNoQ7HWHnzNsYvHe', '2025-04-08 07:00:05', 0),
(4, 'Kok Chye Hock', 'kok@help.com', 'instructor', '$2y$10$iE4piciOlET5ugjrlKsdceTnDCHthWJu8Tg0BOua3u/o/5clkf.tK', '2025-04-09 04:27:47', 0);

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
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
