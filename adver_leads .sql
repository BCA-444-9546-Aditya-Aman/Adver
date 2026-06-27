-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Jun 25, 2026 at 12:58 PM
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
-- Database: `adver_leads`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `password`, `created_at`) VALUES
(1, 'admin', '$2y$10$5nYoLF.6UtTE2nehqN/6bO/MCNAB4su6L8j2np44LuuGKvWasD6LG', '2026-06-24 06:45:11');

-- --------------------------------------------------------

--
-- Table structure for table `automation_leads`
--

CREATE TABLE `automation_leads` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `business_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `business_type` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `automation_leads`
--

INSERT INTO `automation_leads` (`id`, `name`, `business_name`, `email`, `phone`, `business_type`, `message`, `created_at`, `is_read`) VALUES
(2, 'Aditya', 'clothing store', 'aditya@email.com', '9113399421', 'Education / Coaching', '', '2026-06-24 11:39:46', 1),
(3, 'Ankit', 'clothing store', 'ankit@gmail.com', '9876543210', 'Education / Coaching', '', '2026-06-25 10:12:01', 1);

-- --------------------------------------------------------

--
-- Table structure for table `seo_leads`
--

CREATE TABLE `seo_leads` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `business_name` varchar(255) NOT NULL,
  `website` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `seo_need` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `seo_leads`
--

INSERT INTO `seo_leads` (`id`, `name`, `business_name`, `website`, `email`, `phone`, `seo_need`, `created_at`, `is_read`) VALUES
(1, 'Amresh', 'Litti Hut', 'http://littihut.com', 'info@litti.com', '7779932178', 'On-Page & Technical SEO', '2026-06-25 06:44:02', 1);

-- --------------------------------------------------------

--
-- Table structure for table `smm_leads`
--

CREATE TABLE `smm_leads` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `business_name` varchar(255) NOT NULL,
  `instagram_or_website` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `smm_need` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `smm_leads`
--

INSERT INTO `smm_leads` (`id`, `name`, `business_name`, `instagram_or_website`, `email`, `phone`, `smm_need`, `created_at`, `is_read`) VALUES
(1, 'Aditya', 'Pepsico', 'www.pepsico.com', 'pepsico@gmail.com', '7779932178', 'Strategy & Analytics', '2026-06-24 06:54:45', 1),
(2, 'jagdeep', 'jagdeep food plaza', 'www.instagram/jagdeep/dlk.com', 'jay@email.com', '6256448764', 'Strategy & Analytics', '2026-06-25 07:11:43', 1);

-- --------------------------------------------------------

--
-- Table structure for table `web_leads`
--

CREATE TABLE `web_leads` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `service` varchar(100) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `web_leads`
--

INSERT INTO `web_leads` (`id`, `name`, `email`, `phone`, `service`, `message`, `created_at`, `is_read`) VALUES
(1, 'Rajesh', 'Rajesh@email.com', '6987546515', 'growth', 'want to build a website', '2026-06-24 06:56:09', 1),
(2, 'rmau', 'jakk@gmail.com', '9876543210', 'growth', 'hi ek l lkflj', '2026-06-25 07:12:13', 1),
(3, 'aditya Singh', 'fklz2@dlkl.com', '9876543210', 'premium', '', '2026-06-25 07:12:43', 1),
(4, 'aditya Singh', 'ak@doomsday.in', '9876543210', 'starter', 'hi how are you', '2026-06-25 07:33:58', 1),
(5, 'aditya Singh', 'ak@doomsday.in', '9876543210', 'custom', '', '2026-06-25 07:36:31', 1),
(6, 'ramesh', 'ak@gmail.com', '9876543210', 'growth', '', '2026-06-25 07:37:58', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `automation_leads`
--
ALTER TABLE `automation_leads`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `seo_leads`
--
ALTER TABLE `seo_leads`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `smm_leads`
--
ALTER TABLE `smm_leads`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `web_leads`
--
ALTER TABLE `web_leads`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `automation_leads`
--
ALTER TABLE `automation_leads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `seo_leads`
--
ALTER TABLE `seo_leads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `smm_leads`
--
ALTER TABLE `smm_leads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `web_leads`
--
ALTER TABLE `web_leads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
