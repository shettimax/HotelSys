-- phpMyAdmin SQL Dump
-- version 4.8.5
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Aug 22, 2026 at 10:12 PM
-- Server version: 5.7.44
-- PHP Version: 5.6.40-103+ubuntu24.04.1+deb.sury.org+1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `xx2`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_logs`
--

CREATE TABLE `admin_logs` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `admin_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `action` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_logs`
--

INSERT INTO `admin_logs` (`id`, `admin_id`, `admin_name`, `action`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 2, 'shettimax', 'Login', 'Successful login', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 20:41:56'),
(3, 2, 'shettimax', 'Login', 'Successful login', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 20:47:15'),
(4, 2, 'shettimax', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 20:47:21'),
(5, 2, 'shettimax', 'Login', 'Successful login', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 20:48:31'),
(10, 2, 'shettimax', 'Mark Invoice Paid', 'Invoice #INV1765915435935 marked as paid', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 21:14:39'),
(11, 2, 'shettimax', 'Create Booking', 'Booking #66 created for LUCY SHETTIMA (Invoice #INV1765916327897)', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 21:18:47'),
(12, 2, 'shettimax', 'Mark Invoice Paid', 'Invoice #INV1765916327897 marked as paid', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 21:18:54'),
(13, 2, 'shettimax', 'Guest Check-in', 'Guest Sarah Williams checked into room 403', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 21:23:44'),
(14, 2, 'shettimax', 'Guest Checkout', 'Guest MASUD ADAMU checked out from room 303', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 21:31:12'),
(15, 2, 'shettimax', 'Update Customer', 'Updated customer LUCY SHETTIMAx', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 21:40:29'),
(19, 2, 'shettimax', 'Select Customer', 'Selected queen alhassan for booking', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 22:21:42'),
(20, 2, 'shettimax', 'Create Booking', 'Booking #67 created for queen alhassan (Invoice #INV1765920104272)', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 22:21:44'),
(21, 2, 'shettimax', 'Mark Invoice Paid', 'Invoice #INV1765920104272 marked as paid', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 22:21:49'),
(22, 2, 'shettimax', 'Guest Check-in', 'Guest LUCY SHETTIMAx checked into room 101', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 22:22:50'),
(24, 2, 'shettimax', 'Guest Check-in', 'Guest LUCY SHETTIMAx checked into room 201', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 22:29:20'),
(26, 2, 'shettimax', 'Guest Check-in', 'Guest LUCY SHETTIMAx checked into room 104', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 22:31:44'),
(27, 2, 'shettimax', 'Guest Check-in', 'Guest queen alhassan checked into room 202', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 22:33:13'),
(30, 2, 'shettimax', 'Select Customer', 'Selected Sarah Williams for booking', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 22:42:55'),
(31, 2, 'shettimax', 'Create Booking', 'Booking #68 created for Sarah Williams (Invoice #INV1765921377795)', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 22:42:57'),
(32, 2, 'shettimax', 'Mark Invoice Paid', 'Invoice #INV1765921377795 marked as paid', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 22:44:08'),
(33, 2, 'shettimax', 'Guest Check-in', 'Guest Sarah Williams checked into room 203', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 22:44:31'),
(35, 2, 'shettimax', 'Mark Invoice Paid', 'Invoice #INV1765914998317 marked as paid', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 22:51:12'),
(36, 2, 'shettimax', 'Guest Check-in', 'Guest MASUD ADAMU checked into room 103', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 22:51:25'),
(38, 2, 'shettimax', 'Mark Invoice Paid', 'Invoice #INV1765914840805 marked as paid', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 22:55:00'),
(39, 2, 'shettimax', 'Guest Check-in', 'Guest LUCY SHETTIMAx checked into room 102', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 22:55:11'),
(40, 2, 'shettimax', 'Login', 'Successful login', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 00:58:35'),
(41, 2, 'shettimax', 'Update Customer', 'Updated customer LUCY SHETTIMA', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 01:02:09'),
(42, 1, 'admin', 'Login', 'Successful login', '192.168.1.128', 'Mozilla/5.0 (X11; Linux x86_64; rv:146.0) Gecko/20100101 Firefox/146.0', '2025-12-17 08:39:19'),
(43, 1, 'admin', 'Update Customer', 'Updated customer LUCY SHETTIMA', '192.168.1.128', 'Mozilla/5.0 (X11; Linux x86_64; rv:146.0) Gecko/20100101 Firefox/146.0', '2025-12-17 08:42:04'),
(44, 1, 'admin', 'Guest Check-in', 'Guest Sarah Williams checked into room 303', '192.168.1.128', 'Mozilla/5.0 (X11; Linux x86_64; rv:146.0) Gecko/20100101 Firefox/146.0', '2025-12-17 08:57:51'),
(45, 1, 'admin', 'Guest Checkout', 'Guest Sarah Williams checked out from room 303', '192.168.1.128', 'Mozilla/5.0 (X11; Linux x86_64; rv:146.0) Gecko/20100101 Firefox/146.0', '2025-12-17 08:59:35'),
(46, 1, 'admin', 'Select Customer', 'Selected TSURAMAVA DANIEL for booking', '192.168.1.128', 'Mozilla/5.0 (X11; Linux x86_64; rv:146.0) Gecko/20100101 Firefox/146.0', '2025-12-17 09:02:48'),
(47, 1, 'admin', 'Create Booking', 'Booking #69 created for TSURAMAVA DANIEL (Invoice #INV1765958570570)', '192.168.1.128', 'Mozilla/5.0 (X11; Linux x86_64; rv:146.0) Gecko/20100101 Firefox/146.0', '2025-12-17 09:02:50'),
(48, 2, 'shettimax', 'Login', 'Successful login', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 12:51:49'),
(49, 2, 'shettimax', 'Mark Invoice Paid', 'Invoice #INV1765958570570 marked as paid', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 13:00:22'),
(50, 2, 'shettimax', 'Guest Check-in', 'Guest TSURAMAVA DANIEL checked into room 303', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 13:00:35'),
(51, 2, 'shettimax', 'Extend Stay', 'Booking ID 69 extended by 1 night(s). Guest: TSURAMAVA DANIEL, Room: 303, Amount: ₦41,000.00', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 13:08:21'),
(52, 2, 'shettimax', 'Select Customer', 'Selected oluwafemi adekolure for booking', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 13:11:04'),
(53, 2, 'shettimax', 'Create Booking', 'Booking #70 created for oluwafemi adekolure (Invoice #INV1765973481158)', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 13:11:21'),
(54, 2, 'shettimax', 'Mark Invoice Paid', 'Invoice #INV1765973481158 marked as paid', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 13:12:16'),
(55, 2, 'shettimax', 'Guest Check-in', 'Guest oluwafemi adekolure checked into room 101Ac', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 13:12:56'),
(56, 2, 'shettimax', 'Select Customer', 'Selected shettima johnson for booking', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 13:18:18'),
(57, 2, 'shettimax', 'Create Booking', 'Booking #71 created for shettima johnson (Invoice #INV1765973900774)', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 13:18:20'),
(58, 2, 'shettimax', 'Mark Invoice Paid', 'Invoice #INV1765973900774 marked as paid', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 13:19:00'),
(59, 2, 'shettimax', 'Guest Check-in', 'Guest shettima johnson checked into room 1337x', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 13:21:35'),
(60, 2, 'shettimax', 'Select Customer', 'Selected TSURAMAVA DANIEL for booking', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 13:24:23'),
(61, 2, 'shettimax', 'Create Booking', 'Booking #72 created for TSURAMAVA DANIEL (Invoice #INV1765974266337)', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 13:24:26'),
(62, 2, 'shettimax', 'Mark Invoice Paid', 'Invoice #INV1765974266337 marked as paid', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 13:33:24'),
(63, 2, 'shettimax', 'Guest Check-in', 'Guest TSURAMAVA DANIEL checked into room 666', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 13:33:42'),
(64, 2, 'shettimax', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 13:44:56'),
(65, 2, 'shettimax', 'Login', 'Successful login', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 13:45:01'),
(66, 2, 'shettimax', 'Guest Checkout', 'Guest queen alhassan checked out from room 202', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 13:46:05'),
(67, 2, 'shettimax', 'Guest Checkout', 'Guest Sarah Williams checked out from room 333', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 13:52:20'),
(68, 2, 'shettimax', 'Guest Checkout', 'Guest TSURAMAVA DANIEL checked out from room 666', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 13:58:25'),
(69, 2, 'shettimax', 'Login', 'Successful login', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 15:13:38'),
(71, 2, 'shettimax', 'Guest Checkout', 'Guest Sarah Williams checked out from room 137', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 16:13:17'),
(72, 2, 'shettimax', 'Select Customer', 'Selected LUCY SHETTIMA for booking', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 16:17:00'),
(73, 2, 'shettimax', 'Create Booking', 'Booking #73 created for LUCY SHETTIMA (Invoice #INV1765984623283)', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 16:17:03'),
(74, 2, 'shettimax', 'Mark Invoice Paid', 'Invoice #INV1765984623283 marked as paid', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 16:17:16'),
(75, 2, 'shettimax', 'Guest Check-in', 'Guest LUCY SHETTIMA checked into room 137', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 16:18:54'),
(76, 2, 'shettimax', 'Extend Stay', 'Booking ID 70 extended by 1 night(s). Guest: oluwafemi adekolure, Room: 101Ac, Amount: ₦60,000.00', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 16:23:25'),
(77, 2, 'shettimax', 'Guest Checkout', 'Guest LUCY SHETTIMA checked out from room 137', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 16:23:47'),
(78, 2, 'shettimax', 'Select Customer', 'Selected MASUD ADAMU for booking', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 16:24:04'),
(79, 2, 'shettimax', 'Create Booking', 'Booking #74 created for MASUD ADAMU (Invoice #INV1765985047799)', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 16:24:07'),
(80, 2, 'shettimax', 'Mark Invoice Paid', 'Invoice #INV1765985047799 marked as paid', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 16:24:12'),
(82, 2, 'shettimax', 'Guest Checkout', 'Guest TSURAMAVA DANIEL checked out from room 401', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 16:37:10'),
(83, 2, 'shettimax', 'Refund Processed', 'Refund of ₦2,000.00 processed for TSURAMAVA DANIEL (Room 401) via CASH', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 16:37:26'),
(84, 2, 'shettimax', 'Select Customer', 'Selected oluwafemi adekolure for booking', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 16:41:25'),
(85, 2, 'shettimax', 'Create Booking', 'Booking #75 created for oluwafemi adekolure (Invoice #INV1765986087676)', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 16:41:27'),
(86, 2, 'shettimax', 'Select Customer', 'Selected ROSE ADENIYI for booking', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 16:45:27'),
(87, 2, 'shettimax', 'Create Booking', 'Booking #76 created for ROSE ADENIYI (Invoice #INV1765986329851)', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 16:45:29'),
(88, 2, 'shettimax', 'Booking Cancelled', 'Booking #76 cancelled for ROSE ADENIYI (Room 137). Reason: bye suckers', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 16:46:09'),
(89, 2, 'shettimax', 'Select Customer', 'Selected queen alhassan for booking', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 16:51:12'),
(90, 2, 'shettimax', 'Create Booking', 'Booking #77 created for queen alhassan (Invoice #INV1765986674775)', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 16:51:14'),
(91, 2, 'shettimax', 'Booking Cancelled', 'Booking #77 cancelled for queen alhassan (Room 137). Reason: what tha fffffffffffff', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 16:52:13'),
(92, 2, 'shettimax', 'Select Customer', 'Selected ROSE ADENIYI for booking', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 16:55:42'),
(93, 2, 'shettimax', 'Create Booking', 'Booking #78 created for ROSE ADENIYI (Invoice #INV1765986943811)', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 16:55:43'),
(94, 2, 'shettimax', 'Booking Cancelled', 'Booking #78 cancelled for ROSE ADENIYI (Room 333). Reason: kawai nagadama ne', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 16:56:04'),
(95, 2, 'shettimax', 'Select Customer', 'Selected oluwafemi adekolure for booking', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 18:23:48'),
(96, 2, 'shettimax', 'Create Booking', 'Booking #79 created for oluwafemi adekolure (Invoice #INV1765992231388)', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 18:23:51'),
(97, 2, 'shettimax', 'Mark Invoice Paid', 'Invoice #INV1765992231388 marked as paid', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 18:24:56'),
(98, 2, 'shettimax', 'Guest Check-in', 'Guest oluwafemi adekolure checked into room 137', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 18:29:03'),
(99, 2, 'shettimax', 'Guest Checkout', 'Guest oluwafemi adekolure checked out from room 137', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 18:29:29'),
(100, 2, 'shettimax', 'Select Customer', 'Selected LUCY SHETTIMA for booking', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 18:34:19'),
(101, 2, 'shettimax', 'Create Booking', 'Booking #80 created for LUCY SHETTIMA (Invoice #INV1765992860242)', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 18:34:20'),
(102, 2, 'shettimax', 'Mark Invoice Paid', 'Invoice #INV1765992860242 marked as paid', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 18:34:41'),
(103, 2, 'shettimax', 'Select Customer', 'Selected LUCY SHETTIMA for booking', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 18:47:35'),
(104, 2, 'shettimax', 'Select Customer', 'Selected John Doe for booking', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 18:55:10'),
(105, 2, 'shettimax', 'Create Booking', 'Booking #81 created for John Doe (Invoice #INV1765994112952)', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 18:55:12'),
(106, 2, 'shettimax', 'Mark Invoice Paid', 'Invoice #INV1765994112952 marked as paid', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 18:56:41'),
(107, 2, 'shettimax', 'Add Customer', 'Added new customer JOHNSON SEBASTIAN', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 19:04:32'),
(108, 2, 'shettimax', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 19:43:57'),
(109, 2, 'shettimax', 'Login', 'Successful login', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 19:44:51'),
(110, 2, 'shettimax', 'Login', 'Successful login', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 09:52:13'),
(111, 2, 'shettimax', 'Guest Checkout', 'Guest Sarah Williams checked out from room 123', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 10:10:33'),
(112, 2, 'shettimax', 'Guest Checkout', 'Guest Mike Johnson checked out from room 301', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 10:11:17'),
(113, 2, 'shettimax', 'Guest Checkout', 'Guest Sarah Williams checked out from room 403', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 10:11:37'),
(114, 2, 'shettimax', 'Guest Checkout', 'Guest TSURAMAVA DANIEL checked out from room 1337', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 10:12:10'),
(115, 2, 'shettimax', 'Guest Checkout', 'Guest LUCY SHETTIMA checked out from room 402', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 10:14:13'),
(116, 2, 'shettimax', 'Guest Checkout', 'Guest Sarah Williams checked out from room 203', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 10:14:48'),
(117, 2, 'shettimax', 'Guest Checkout', 'Guest LUCY SHETTIMA checked out from room 101', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 10:18:43'),
(118, 2, 'shettimax', 'Guest Checkout', 'Guest LUCY SHETTIMA checked out from room 102', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 10:18:50'),
(119, 2, 'shettimax', 'Guest Checkout', 'Guest MASUD ADAMU checked out from room 103', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 10:19:00'),
(120, 2, 'shettimax', 'Select Customer', 'Selected MASUD ADAMU for booking', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 10:31:03'),
(121, 2, 'shettimax', 'Create Booking', 'Booking #82 created for MASUD ADAMU (Invoice #INV1766050264457)', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 10:31:04'),
(122, 2, 'shettimax', 'Mark Invoice Paid', 'Invoice #INV1766050264457 marked as paid', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 10:31:08'),
(123, 2, 'shettimax', 'Select Customer', 'Selected oluwafemi adekolure for booking', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 10:54:39'),
(124, 2, 'shettimax', 'Create Booking', 'Booking #83 created for oluwafemi adekolure (Invoice #INV1766051681576)', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 10:54:41'),
(125, 2, 'shettimax', 'Mark Invoice Paid', 'Invoice #INV1766051681576 marked as paid', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 10:54:47'),
(126, 2, 'shettimax', 'Guest Checkout', 'Guest LUCY SHETTIMA checked out from room 201', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 11:18:04'),
(127, 2, 'shettimax', 'Guest Checkout', 'Guest LUCY SHETTIMA checked out from room 104', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 11:18:11'),
(128, 2, 'shettimax', 'Guest Check-in', 'Guest oluwafemi adekolure checked into room 666', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 12:04:43'),
(129, 2, 'shettimax', 'Select Customer', 'Selected queen alhassan for booking', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 12:19:40'),
(130, 2, 'shettimax', 'Create Booking', 'Booking #84 created for queen alhassan (Invoice #INV1766056782968)', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 12:19:42'),
(131, 2, 'shettimax', 'Mark Invoice Paid', 'Invoice #INV1766056782968 marked as paid', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 12:19:47'),
(132, 2, 'shettimax', 'Select Customer', 'Selected TSURAMAVA DANIEL for booking', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 12:21:06'),
(133, 2, 'shettimax', 'Create Booking', 'Booking #85 created for TSURAMAVA DANIEL (Invoice #INV1766056868684)', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 12:21:08'),
(134, 2, 'shettimax', 'Mark Invoice Paid', 'Invoice #INV1766056868684 marked as paid', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 12:21:16'),
(135, 2, 'shettimax', 'Guest Check-in', 'Guest LUCY SHETTIMA checked into room 137', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 12:22:22'),
(136, 2, 'shettimax', 'Select Customer', 'Selected TSURAMAVA DANIEL for booking', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 12:32:13'),
(137, 2, 'shettimax', 'Create Booking', 'Booking #86 created for TSURAMAVA DANIEL (Invoice #INV1766057537784)', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 12:32:18'),
(138, 2, 'shettimax', 'Mark Invoice Paid', 'Invoice #INV1766057537784 marked as paid', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 12:32:55'),
(139, 2, 'shettimax', 'Select Customer', 'Selected LUCY SHETTIMA for booking', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 12:33:47'),
(140, 2, 'shettimax', 'Create Booking', 'Booking #87 created for LUCY SHETTIMA (Invoice #INV1766057652715)', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 12:34:12'),
(141, 2, 'shettimax', 'Mark Invoice Paid', 'Invoice #INV1766057652715 marked as paid', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 12:34:21'),
(142, 2, 'shettimax', 'Guest Check-in', 'Booking ID 84 (queen alhassan) checked into room 1337 by admin ID 2', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 12:52:12'),
(143, 2, 'shettimax', 'Select Customer', 'Selected Sarah Williams for booking', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 12:52:45'),
(144, 2, 'shettimax', 'Select Customer', 'Selected TSURAMAVA DANIEL for booking', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 12:52:52'),
(145, 2, 'shettimax', 'Create Booking', 'Booking #88 created for TSURAMAVA DANIEL (Invoice #INV1766058775397)', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 12:52:55'),
(146, 2, 'shettimax', 'Mark Invoice Paid', 'Invoice #INV1766058775397 marked as paid', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 12:53:01'),
(147, 2, 'shettimax', 'Select Customer', 'Selected Sarah Williams for booking', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 12:53:41'),
(148, 2, 'shettimax', 'Create Booking', 'Booking #89 created for Sarah Williams (Invoice #INV1766058823983)', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 12:53:43'),
(149, 2, 'shettimax', 'Mark Invoice Paid', 'Invoice #INV1766058823983 marked as paid', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 12:53:50'),
(150, 2, 'shettimax', 'Guest Check-in', 'Booking ID 89 (Sarah Williams) checked into room 101 by admin ID 2', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 12:54:06'),
(151, 2, 'shettimax', 'Guest Check-in', 'Booking ID 86 (TSURAMAVA DANIEL) checked into room 301 by admin ID 2', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 13:33:52'),
(152, 2, 'shettimax', 'Guest Check-in', 'Booking ID 87 (LUCY SHETTIMA) checked into room 402 by admin ID 2', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 13:34:03'),
(153, 2, 'shettimax', 'Guest Check-in', 'Booking ID 88 (TSURAMAVA DANIEL) checked into room 403 by admin ID 2', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 13:34:12'),
(154, 2, 'shettimax', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 13:52:36'),
(155, 2, 'shettimax', 'Login', 'Successful login', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 13:52:42'),
(156, 2, 'shettimax', 'Login', 'Successful login', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 10:24:33'),
(157, 2, 'shettimax', 'Guest Checkout', 'Guest ROSE ADENIYI checked out from room 302', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 10:52:15'),
(158, 2, 'shettimax', 'Guest Checkout', 'Guest TSURAMAVA DANIEL checked out from room 303', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 10:57:30'),
(159, 2, 'shettimax', 'Guest Checkout', 'Guest oluwafemi adekolure checked out from room 101Ac', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 10:57:43'),
(160, 2, 'shettimax', 'Guest Check-in', 'Booking ID 85 (TSURAMAVA DANIEL) checked into room 401 by admin ID 2', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 12:57:32'),
(161, 2, 'shettimax', 'Login', 'Successful login', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 15:17:48'),
(162, 2, 'shettimax', 'Guest Checkout', 'Guest Sarah Williams checked out from room 101', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 15:18:56'),
(163, 2, 'shettimax', 'Guest Checkout', 'Guest shettima johnson checked out from room 1337x', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 15:19:20'),
(164, 2, 'shettimax', 'Guest Checkout', 'Guest LUCY SHETTIMA checked out from room 137', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 15:19:39'),
(165, 2, 'shettimax', 'Guest Checkout', 'Guest TSURAMAVA DANIEL checked out from room 403', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 15:19:50'),
(166, 2, 'shettimax', 'Booking Cancelled', 'Booking #81 cancelled for John Doe (Room 333). Reason: him no show', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 15:21:11'),
(167, 2, 'shettimax', 'Guest Check-in', 'Booking ID 82 (MASUD ADAMU) checked into room 123 by admin ID 2', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 15:23:56'),
(168, 2, 'shettimax', 'Staff Clock Out', 'JOHNSON SHETTIMA (GE001) clocked out at 04:46 PM - Worked: 0.4 hours', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 16:46:08'),
(169, 2, 'shettimax', 'Staff Clock Out', 'JOHNSON SHETTIMA (GE001) clocked out at 04:46 PM - Worked: 0.4 hours', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 16:46:21'),
(170, 2, 'shettimax', 'Staff Clock In', 'ADMIN ADMINS (SE001) clocked in at 04:49 PM', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 16:49:03'),
(171, 2, 'shettimax', 'Staff Clock Out', 'ADMIN ADMINS (SE001) clocked out at 04:49 PM - Worked: 0 hours', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 16:49:14'),
(172, 2, 'shettimax', 'Staff Clock Out', 'ADMIN ADMINS (SE001) clocked out at 04:53 PM - Worked: 0.07 hours', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 16:53:01'),
(173, 2, 'shettimax', 'Staff Clock Out', 'ADMIN ADMINS (SE001) clocked out at 04:53 PM - Worked: 0.07 hours', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 16:53:03'),
(174, 2, 'shettimax', 'Staff Clock Out', 'ADMIN ADMINS (SE001) clocked out at 04:53 PM - Worked: 0.07 hours', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 16:53:15'),
(175, 2, 'shettimax', 'Login', 'Successful login', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 09:41:37'),
(176, 1, 'admin', 'Login', 'Successful login', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 10:04:47'),
(177, 1, 'admin', 'Login', 'Successful login', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 14:21:34'),
(178, 1, 'admin', 'Guest Checkout', 'Guest MASUD ADAMU checked out from room 123', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 14:22:53'),
(179, 1, 'admin', 'Guest Checkout', 'Guest oluwafemi adekolure checked out from room 666', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 14:23:08'),
(180, 1, 'admin', 'Guest Checkout', 'Guest LUCY SHETTIMA checked out from room 402', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 14:23:31'),
(181, 1, 'admin', 'Guest Checkout', 'Guest TSURAMAVA DANIEL checked out from room 301', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 14:23:50'),
(182, 1, 'admin', 'Guest Checkout', 'Guest TSURAMAVA DANIEL checked out from room 401', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 14:24:22'),
(183, 1, 'admin', 'Guest Checkout', 'Guest queen alhassan checked out from room 1337', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 14:24:40'),
(184, 1, 'admin', 'Refund Processed', 'Refund of ₦30,000.00 processed for oluwafemi adekolure (Room 137) via POS', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 14:38:55'),
(185, 1, 'admin', 'Select Customer', 'Selected JOHNSON SEBASTIAN for booking', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 14:41:23'),
(186, 1, 'admin', 'Create Booking', 'Booking #90 created for JOHNSON SEBASTIAN (Invoice #INV1766238087823)', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 14:41:27'),
(187, 1, 'admin', 'Mark Invoice Paid', 'Invoice #INV1766238087823 marked as paid', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 14:41:50'),
(188, 1, 'admin', 'Select Customer', 'Selected TSURAMAVA DANIEL for booking', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 14:43:29'),
(189, 1, 'admin', 'Create Booking', 'Booking #91 created for TSURAMAVA DANIEL (Invoice #INV1766238211195)', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 14:43:31'),
(190, 1, 'admin', 'Mark Invoice Paid', 'Invoice #INV1766238211195 marked as paid', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 14:43:39'),
(191, 1, 'admin', 'Guest Check-in', 'Booking ID 91 (TSURAMAVA DANIEL) checked into room 401 by admin ID 1', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 14:43:51'),
(192, 1, 'admin', 'Login', 'Successful login', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-20 15:09:48'),
(193, 1, 'admin', 'Logout', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', '2025-12-20 15:11:58'),
(194, 1, 'admin', 'Login', 'Successful login', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 16:04:16'),
(195, 1, 'admin', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 16:20:01'),
(196, 1, 'admin', 'Login', 'Successful login', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 10:25:19'),
(197, 1, 'admin', 'Add Customer', 'Added new customer ismail adamu', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 10:33:47'),
(198, 1, 'admin', 'Create Booking', 'Booking #92 created for ismail adamu (Invoice #INV1766914450112)', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 10:34:10'),
(199, 1, 'admin', 'Mark Invoice Paid', 'Invoice #INV1766914450112 marked as paid', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 10:37:29'),
(200, 1, 'admin', 'Guest Check-in', 'Booking ID 92 (ismail adamu) checked into room 666 by admin ID 1', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 10:40:31'),
(201, 1, 'admin', 'Extend Stay', 'Booking ID 92 extended by 2 night(s). Guest: ismail adamu, Room: 666, Amount: ₦120,000.00', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 10:44:29'),
(202, 1, 'admin', 'Select Customer', 'Selected JOHNSON SEBASTIAN for booking', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 10:51:24'),
(203, 1, 'admin', 'Create Booking', 'Booking #93 created for JOHNSON SEBASTIAN (Invoice #INV1766915486909)', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 10:51:26'),
(204, 1, 'admin', 'Mark Invoice Paid', 'Invoice #INV1766915486909 marked as paid', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 10:51:33'),
(205, 1, 'admin', 'Login', 'Successful login', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-20 15:16:37'),
(206, 1, 'admin', 'Booking Cancelled', 'Booking #93 cancelled for JOHNSON SEBASTIAN (Room 123). Reason: dddddddddddddddddddddddd', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-20 15:17:12'),
(207, 1, 'admin', 'Booking Cancelled', 'Booking #90 cancelled for JOHNSON SEBASTIAN (Room 401) with refund of ₦300.00. Reason: blah blah', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-20 15:17:27'),
(208, 1, 'admin', 'Guest Checkout', 'Guest ismail adamu checked out from room 666', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-20 15:17:40'),
(209, 1, 'admin', 'Guest Checkout', 'Guest TSURAMAVA DANIEL checked out from room 401', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-20 15:18:18'),
(210, 4, 'admin12', 'Login', 'Successful login', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 01:01:05'),
(211, 4, 'admin12', 'Login', 'Successful login', '192.168.0.3', 'Mozilla/5.0 (X11; Linux aarch64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/111.0.0.0 Safari/537.36', '2026-08-21 01:09:28'),
(212, 4, 'admin12', 'Login', 'Successful login', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 09:35:05'),
(213, 4, 'admin12', 'Update Hotel Settings', 'Updated hotel branding: ROYAL CHOICE HOTEL', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 10:00:11'),
(214, 4, 'admin12', 'Update Customer', 'Updated customer BLESSING JOHNSON', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 11:15:36'),
(215, 4, 'admin12', 'Update Customer', 'Updated customer BLESSING JOHNSON', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 11:15:56'),
(216, 4, 'admin12', 'Update Customer', 'Updated customer BLESSING JOHNSON', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 11:18:40'),
(217, 4, 'admin12', 'Select Customer', 'Selected LUCY SHETTIMA for booking', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 11:35:34'),
(218, 4, 'admin12', 'Create Booking', 'Booking #94 created for LUCY SHETTIMA (Invoice #INV1787308541641)', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 11:35:41'),
(219, 4, 'admin12', 'Mark Invoice Paid', 'Invoice #INV1787308541641 marked as paid', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 11:37:01'),
(220, 4, 'admin12', 'Refund Processed', 'Refund of ₦300.00 processed for JOHNSON SEBASTIAN (Room 401) via CASH', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 11:53:52'),
(221, 4, 'admin12', 'Guest Check-in', 'Booking ID 94 (LUCY SHETTIMA) checked into room 123 by admin ID 4', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 11:57:09'),
(222, 4, 'admin12', 'Guest Checkout', 'Guest LUCY SHETTIMA checked out from room 123', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 11:59:55'),
(223, 4, 'admin12', 'Select Customer', 'Selected shettima johnson for booking', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 15:14:20'),
(224, 4, 'admin12', 'Create Booking', 'Booking #95 created for shettima johnson (Invoice #INV1787321667489)', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 15:14:27'),
(225, 4, 'admin12', 'Mark Invoice Paid', 'Invoice #INV1787321667489 marked as paid', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 15:18:55'),
(226, 4, 'admin12', 'Guest Check-in', 'Booking ID 95 (shettima johnson) checked into room 123 by admin ID 4', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 15:36:22'),
(227, 4, 'admin12', 'Extend Stay', 'Booking ID 95 extended by 1 night(s). Guest: shettima johnson, Room: 123, Amount: ₦60,000.00', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 15:39:30'),
(228, 4, 'admin12', 'Select Customer', 'Selected John Doe for booking', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 16:17:18'),
(229, 4, 'admin12', 'Create Booking', 'Booking #96 created for John Doe (Invoice #INV1787325441680)', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 16:17:21'),
(230, 4, 'admin12', 'Select Customer', 'Selected TSURAMAVA DANIEL for booking', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 16:29:28'),
(231, 4, 'admin12', 'Create Booking', 'Booking #97 created for TSURAMAVA DANIEL (Invoice #INV1787326170135)', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 16:29:30'),
(232, 4, 'admin12', 'Mark Invoice Paid', 'Invoice #INV1787326170135 marked as paid', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 16:29:37'),
(233, 4, 'admin12', 'Guest Check-in', 'Booking ID 97 (TSURAMAVA DANIEL) checked into room 333 by admin ID 4', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 16:30:37'),
(234, 4, 'admin12', 'Guest Checkout', 'Guest TSURAMAVA DANIEL checked out from room 333', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 16:31:31'),
(235, 4, 'admin12', 'Booking Cancelled', 'Booking #96 cancelled for John Doe (Room 137). Reason: cccccccccccccccccc', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 16:49:38'),
(236, 4, 'admin12', 'Mark Invoice Paid', 'Invoice #INV1787325441680 marked as paid', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 16:51:06'),
(237, 4, 'admin12', 'Select Customer', 'Selected oluwafemi adekolure for booking', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 16:55:00'),
(238, 4, 'admin12', 'Create Booking', 'Booking #98 created for oluwafemi adekolure (Invoice #INV1787327703128)', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 16:55:03'),
(239, 4, 'admin12', 'Mark Invoice Paid', 'Invoice #INV1787327703128 marked as paid', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 16:56:11'),
(240, 4, 'admin12', 'Guest Check-in', 'Booking ID 98 (oluwafemi adekolure) checked into room 333 by admin ID 4', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 16:57:07'),
(241, 4, 'admin12', 'Guest Checkout', 'Guest oluwafemi adekolure checked out from room 333', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 16:58:15');
INSERT INTO `admin_logs` (`id`, `admin_id`, `admin_name`, `action`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES
(242, 4, 'admin12', 'Guest Checkout', 'Guest shettima johnson checked out from room 123', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 16:58:48'),
(243, 4, 'admin12', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 17:04:55'),
(244, 4, 'admin12', 'Login', 'Successful login', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 17:05:10'),
(245, 4, 'admin12', 'Login', 'Successful login', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 18:05:14'),
(246, 4, 'admin12', 'Refund Processed', 'Refund of ₦10,000.00 processed for TSURAMAVA DANIEL (Room 333) via CASH', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 19:51:58'),
(247, 4, 'admin12', 'Login', 'Successful login', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 09:11:47'),
(248, 4, 'admin12', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 09:13:02'),
(249, 1, 'admin', 'Login', 'Successful login', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 09:13:05'),
(250, 1, 'admin', 'Schema Migration', 'Ran setup.php schema migration (roles, staff tracking, staff login)', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 09:19:40'),
(251, 1, 'admin', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 09:20:35'),
(252, 4, 'admin12', 'Login', 'Successful login', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 09:20:37'),
(253, 4, 'admin12', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 09:25:22'),
(254, 4, 'admin12', 'Login', 'Successful login', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 09:25:23'),
(255, 1, 'admin', 'Login', 'Successful login', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64; rv:52.0) Gecko/20100101 Firefox/52.0 Cyberfox/52.9.1', '2026-08-22 09:25:47'),
(256, 4, 'admin12', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 09:28:25'),
(257, 1, 'admin', 'Login', 'Successful login', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 09:28:30'),
(258, 1, 'admin', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 09:29:59'),
(259, 2, 'shettimax', 'Login', 'Successful login', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 09:30:03'),
(260, 2, 'shettimax', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 09:44:56'),
(261, 1, 'admin', 'Login', 'Successful login', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 09:45:00'),
(262, 1, 'admin', 'Grant Permission', 'Granted \'Finance Operations\' permission to \'super_admin\' role', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 09:53:58'),
(263, 1, 'admin', 'Grant Permission', 'Granted \'Manage Disputes\' to \'super_admin\'', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 10:20:40'),
(264, 1, 'admin', 'Batch Grant', 'Granted 18 permissions granted to \'super_admin\' role', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 10:26:39'),
(265, 1, 'admin', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 11:15:14'),
(266, 4, 'admin1', 'Login', 'Successful login', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 11:15:17'),
(267, 4, 'admin1', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 11:15:48'),
(268, 1, 'admin', 'Login', 'Successful login', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 11:15:51'),
(269, 1, 'admin', 'Batch Grant', 'Granted 2 permissions granted to \'receptionist\' role', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 11:17:36'),
(270, 1, 'admin', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 11:17:40'),
(271, 4, 'admin1', 'Login', 'Successful login', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 11:17:42'),
(272, 1, 'admin', 'Login', 'Successful login', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 21:56:07'),
(273, 1, 'admin', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 21:56:18'),
(274, 4, 'admin1', 'Login', 'Successful login', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 21:56:22'),
(275, 4, 'admin1', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 21:59:47'),
(276, 1, 'admin', 'Login', 'Successful login', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 21:59:53'),
(277, 1, 'admin', 'Guest Check-in', 'Booking ID 96 (John Doe) checked into room 137 by admin ID 1', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 22:00:18'),
(278, 1, 'admin', 'Batch Grant', 'Granted 3 permissions granted to \'receptionist\' role', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 22:01:36'),
(279, 1, 'admin', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 22:01:41'),
(280, 4, 'admin1', 'Login', 'Successful login', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 22:01:47'),
(281, 4, 'admin1', 'Logout', 'User logged out', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 22:02:44'),
(282, 1, 'admin', 'Login', 'Successful login', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 22:02:52'),
(283, 1, 'admin', 'Batch Grant', 'Granted 8 permissions granted to \'manager\' role', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 22:06:23'),
(284, 1, 'admin', 'Batch Grant', 'Granted 6 permissions granted to \'accountant\' role', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 22:07:19');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `room_id` int(10) UNSIGNED NOT NULL,
  `checkin_date` date NOT NULL,
  `checkout_date` date NOT NULL,
  `nights` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `status` enum('pending','confirmed','paid','cancelled') NOT NULL DEFAULT 'pending',
  `checkout_status` varchar(20) DEFAULT 'not_checked_out',
  `invoice_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `checkin_time` time DEFAULT '14:00:00',
  `checkout_time` time DEFAULT '12:00:00',
  `actual_checkin_datetime` datetime DEFAULT NULL,
  `keycard_issued` tinyint(1) DEFAULT '0',
  `keycard_issued_by` int(11) DEFAULT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `staff_name` varchar(100) DEFAULT NULL,
  `keycard_issued_at` datetime DEFAULT NULL,
  `actual_checkin_time` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='reservations and bookings mapper';

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `customer_id`, `room_id`, `checkin_date`, `checkout_date`, `nights`, `amount`, `status`, `checkout_status`, `invoice_id`, `created_at`, `checkin_time`, `checkout_time`, `actual_checkin_datetime`, `keycard_issued`, `keycard_issued_by`, `staff_id`, `staff_name`, `keycard_issued_at`, `actual_checkin_time`) VALUES
(1, 9, 8, '2025-12-12', '2025-12-13', 1, '41000.00', 'paid', 'checked_out', 1, '2025-12-12 21:09:52', '22:09:52', '12:00:00', '2025-12-12 22:14:08', 1, 1, NULL, NULL, '2025-12-12 22:14:08', '22:14:08'),
(2, 8, 9, '2025-12-12', '2025-12-13', 1, '4000.00', 'paid', 'checked_out', 2, '2025-12-12 21:29:04', '22:29:04', '12:00:00', '2025-12-12 22:40:20', 1, 1, NULL, NULL, '2025-12-12 22:40:20', '22:40:20'),
(3, 6, 14, '2025-12-12', '2025-12-14', 2, '120000.00', 'paid', 'checked_out', 3, '2025-12-12 22:57:31', '23:57:31', '12:00:00', '2025-12-13 07:11:35', 1, 1, NULL, NULL, '2025-12-13 07:11:35', '07:11:35'),
(4, 10, 10, '2025-12-13', '2025-12-15', 2, '82000.00', 'paid', 'checked_out', 4, '2025-12-13 06:29:49', '07:29:49', '12:00:00', '2025-12-13 07:34:29', 1, 1, NULL, NULL, '2025-12-13 07:34:29', '07:34:29'),
(5, 5, 12, '2025-12-14', '2025-12-15', 1, '1200.00', 'cancelled', 'cancelled', 5, '2025-12-13 06:36:04', '14:00:00', '12:00:00', NULL, 0, NULL, NULL, NULL, NULL, NULL),
(6, 1, 13, '2025-12-13', '2025-12-16', 3, '3600.00', 'paid', 'checked_out', 6, '2025-12-13 06:56:25', '07:56:25', '12:00:00', '2025-12-13 08:00:29', 1, 1, NULL, NULL, '2025-12-13 08:00:29', '08:00:29'),
(7, 2, 2, '2025-12-13', '2025-12-16', 3, '42000.00', 'paid', 'checked_out', 7, '2025-12-13 06:58:54', '07:58:54', '12:00:00', '2025-12-13 08:06:53', 1, 1, NULL, NULL, '2025-12-13 08:06:53', '08:06:53'),
(8, 3, 1, '2025-12-13', '2025-12-15', 2, '28000.00', 'paid', 'checked_out', 8, '2025-12-13 07:18:32', '08:18:32', '12:00:00', '2025-12-13 08:21:12', 1, 1, NULL, NULL, '2025-12-13 08:21:12', '08:21:12'),
(9, 4, 11, '2025-12-13', '2025-12-15', 2, '2400.00', 'paid', 'checked_out', 9, '2025-12-13 07:35:38', '08:35:38', '12:00:00', '2025-12-13 09:06:32', 1, 1, NULL, NULL, '2025-12-13 09:06:32', '09:06:32'),
(10, 4, 4, '2025-12-13', '2025-12-15', 2, '28000.00', 'paid', 'checked_out', 10, '2025-12-13 08:18:28', '09:18:28', '12:00:00', '2025-12-13 09:19:43', 1, 1, NULL, NULL, '2025-12-13 09:19:43', '09:19:43'),
(11, 5, 5, '2025-12-13', '2025-12-15', 2, '5000.00', 'paid', 'checked_out', 11, '2025-12-13 08:22:10', '09:22:10', '12:00:00', '2025-12-13 09:37:44', 1, 1, NULL, NULL, '2025-12-13 09:37:44', '09:37:44'),
(12, 1, 3, '2025-12-14', '2025-12-15', 1, '14000.00', 'cancelled', 'cancelled', 12, '2025-12-13 08:41:07', '14:00:00', '12:00:00', NULL, 0, NULL, NULL, NULL, NULL, NULL),
(13, 5, 7, '2025-12-13', '2025-12-15', 2, '5000.00', 'paid', 'checked_out', 13, '2025-12-13 08:49:14', '09:49:14', '12:00:00', '2025-12-13 09:50:33', 1, 1, NULL, NULL, '2025-12-13 09:50:33', '09:50:33'),
(14, 8, 6, '2025-12-13', '2025-12-15', 2, '5000.00', 'paid', 'checked_out', 14, '2025-12-13 08:56:25', '09:56:25', '12:00:00', '2025-12-13 09:58:40', 1, 1, NULL, NULL, '2025-12-13 09:58:40', '09:58:40'),
(15, 6, 8, '2025-12-13', '2025-12-15', 2, '82000.00', 'paid', 'checked_out', 15, '2025-12-13 09:07:29', '10:07:29', '12:00:00', '2025-12-13 10:08:12', 1, 1, NULL, NULL, '2025-12-13 10:08:12', '10:08:12'),
(16, 6, 3, '2025-12-13', '2025-12-14', 1, '14000.00', 'paid', 'checked_out', 16, '2025-12-13 09:10:50', '10:10:50', '12:00:00', '2025-12-13 10:11:57', 1, 1, NULL, NULL, '2025-12-13 10:11:57', '10:11:57'),
(17, 6, 12, '2025-12-13', '2025-12-14', 1, '1200.00', 'paid', 'checked_out', 17, '2025-12-13 09:12:28', '10:12:28', '12:00:00', '2025-12-13 10:38:43', 1, 1, NULL, NULL, '2025-12-13 10:38:43', '10:38:43'),
(18, 6, 9, '2025-12-13', '2025-12-15', 2, '8000.00', 'paid', 'checked_out', 18, '2025-12-13 09:42:00', '10:42:00', '12:00:00', '2025-12-13 10:44:56', 1, 1, NULL, NULL, '2025-12-13 10:44:56', '10:44:56'),
(19, 6, 12, '2025-12-13', '2025-12-14', 1, '1200.00', 'paid', 'checked_out', 19, '2025-12-13 09:43:36', '10:43:36', '12:00:00', '2025-12-13 10:44:14', 1, 1, NULL, NULL, '2025-12-13 10:44:14', '10:44:14'),
(20, 5, 12, '2025-12-13', '2025-12-14', 1, '1200.00', 'paid', 'checked_out', 20, '2025-12-13 12:17:13', '13:17:13', '12:00:00', '2025-12-13 13:20:42', 1, 1, NULL, NULL, '2025-12-13 13:20:42', '13:20:42'),
(21, 5, 3, '2025-12-13', '2025-12-14', 1, '14000.00', 'paid', 'checked_out', 21, '2025-12-13 12:24:05', '13:24:05', '12:00:00', '2025-12-13 15:20:07', 1, 1, NULL, NULL, '2025-12-13 15:20:07', '15:20:07'),
(22, 10, 3, '2025-12-13', '2025-12-14', 1, '14000.00', 'paid', 'checked_out', 22, '2025-12-13 18:33:28', '19:33:28', '12:00:00', '2025-12-13 21:14:47', 1, 1, NULL, NULL, '2025-12-13 21:14:47', '21:14:47'),
(23, 9, 12, '2025-12-13', '2025-12-14', 1, '1200.00', 'cancelled', 'cancelled', 23, '2025-12-13 18:52:39', '19:52:39', '12:00:00', '2025-12-13 21:35:54', 1, 2, NULL, NULL, '2025-12-13 21:35:54', '21:35:54'),
(24, 10, 14, '2025-12-13', '2025-12-15', 2, '120000.00', 'paid', 'checked_out', 24, '2025-12-13 19:17:02', '20:17:02', '12:00:00', '2025-12-13 23:38:24', 1, 2, NULL, NULL, '2025-12-13 23:38:24', '23:38:24'),
(25, 1, 6, '2025-12-13', '2025-12-15', 2, '5000.00', 'paid', 'checked_out', 25, '2025-12-13 19:33:21', '20:33:21', '12:00:00', '2025-12-13 21:40:09', 1, 2, NULL, NULL, '2025-12-13 21:40:09', '21:40:09'),
(26, 9, 4, '2025-12-13', '2025-12-15', 2, '28000.00', 'paid', 'checked_out', 26, '2025-12-13 19:55:09', '20:55:09', '12:00:00', '2025-12-13 21:23:16', 1, 1, NULL, NULL, '2025-12-13 21:23:16', '21:23:16'),
(27, 5, 3, '2025-12-14', '2025-12-15', 1, '14000.00', 'cancelled', 'cancelled', 27, '2025-12-13 21:18:07', '14:00:00', '12:00:00', NULL, 0, NULL, NULL, NULL, NULL, NULL),
(28, 10, 17, '2025-12-14', '2025-12-16', 2, '120000.00', 'paid', 'checked_out', 28, '2025-12-13 23:25:49', '00:25:49', '12:00:00', NULL, 0, NULL, NULL, NULL, NULL, NULL),
(29, 8, 18, '2025-12-14', '2025-12-16', 2, '120000.00', 'paid', 'checked_out', 29, '2025-12-13 23:44:28', '00:44:28', '12:00:00', NULL, 0, NULL, NULL, NULL, NULL, NULL),
(30, 8, 16, '2025-12-14', '2025-12-16', 2, '120000.00', 'paid', 'checked_out', 30, '2025-12-13 23:57:47', '00:57:47', '12:00:00', NULL, 0, NULL, NULL, NULL, NULL, NULL),
(31, 4, 15, '2025-12-16', '2025-12-17', 1, '60000.00', 'cancelled', 'cancelled', 31, '2025-12-14 10:13:32', '14:00:00', '12:00:00', NULL, 0, NULL, NULL, NULL, NULL, NULL),
(32, 10, 15, '2025-12-14', '2025-12-15', 1, '60000.00', 'cancelled', 'cancelled', 32, '2025-12-14 14:02:11', '15:02:11', '12:00:00', NULL, 0, NULL, NULL, NULL, NULL, NULL),
(33, 8, 19, '2025-12-14', '2025-12-16', 2, '120000.00', 'cancelled', 'cancelled', 33, '2025-12-14 16:51:08', '17:51:08', '12:00:00', NULL, 0, NULL, NULL, NULL, NULL, NULL),
(34, 9, 12, '2025-12-20', '2025-12-16', 4, '4800.00', 'cancelled', 'cancelled', 34, '2025-12-14 16:57:10', '14:00:00', '12:00:00', NULL, 0, NULL, NULL, NULL, NULL, NULL),
(35, 6, 12, '2025-12-14', '2025-12-15', 1, '1200.00', 'cancelled', 'cancelled', 35, '2025-12-14 17:06:13', '18:06:13', '12:00:00', NULL, 0, NULL, NULL, NULL, NULL, NULL),
(36, 3, 19, '2025-12-16', '2025-12-17', 1, '60000.00', 'cancelled', 'cancelled', 36, '2025-12-15 10:46:35', '14:00:00', '12:00:00', NULL, 0, NULL, NULL, NULL, NULL, NULL),
(37, 1, 19, '2025-12-15', '2025-12-16', 1, '60000.00', 'paid', 'checked_out', 37, '2025-12-15 10:49:51', '11:49:51', '12:00:00', '2025-12-15 14:45:14', 1, 2, NULL, NULL, '2025-12-15 14:45:14', '14:45:14'),
(38, 9, 15, '2025-12-16', '2025-12-17', 1, '60000.00', 'cancelled', 'cancelled', 38, '2025-12-15 13:39:03', '14:00:00', '12:00:00', NULL, 0, NULL, NULL, NULL, NULL, NULL),
(39, 10, 15, '2025-12-16', '2025-12-17', 1, '60000.00', 'paid', 'checked_out', 39, '2025-12-15 14:05:01', '14:00:00', '12:00:00', '2025-12-16 14:03:48', 1, 2, NULL, NULL, '2025-12-16 14:03:48', '14:03:48'),
(40, 6, 15, '2025-12-15', '2025-12-16', 1, '60000.00', 'paid', 'checked_out', 40, '2025-12-15 15:12:40', '16:12:40', '12:00:00', '2025-12-15 16:23:23', 1, 2, NULL, NULL, '2025-12-15 16:23:23', '16:23:23'),
(41, 4, 18, '2025-12-15', '2025-12-17', 2, '120000.00', 'paid', 'checked_out', 41, '2025-12-15 15:26:31', '16:26:31', '12:00:00', '2025-12-15 16:29:49', 1, 2, NULL, NULL, '2025-12-15 16:29:49', '16:29:49'),
(42, 2, 17, '2025-12-15', '2025-12-17', 2, '120000.00', 'paid', 'checked_out', 42, '2025-12-15 15:30:51', '16:30:51', '12:00:00', '2025-12-15 18:00:08', 1, 2, NULL, NULL, '2025-12-15 18:00:08', '18:00:08'),
(43, 1, 19, '2025-12-15', '2025-12-17', 2, '120000.00', 'paid', 'checked_out', 43, '2025-12-15 17:00:37', '18:00:37', '12:00:00', '2025-12-15 18:10:11', 1, 2, NULL, NULL, '2025-12-15 18:10:11', '18:10:11'),
(44, 6, 14, '2025-12-16', '2025-12-17', 1, '60000.00', 'cancelled', 'cancelled', 44, '2025-12-15 17:11:55', '14:00:00', '12:00:00', NULL, 0, NULL, NULL, NULL, NULL, NULL),
(45, 3, 15, '2025-12-15', '2025-12-16', 1, '60000.00', 'paid', 'checked_out', 45, '2025-12-15 17:15:04', '18:15:04', '12:00:00', '2025-12-15 18:25:33', 1, 2, NULL, NULL, '2025-12-15 18:25:33', '18:25:33'),
(46, 8, 15, '2025-12-15', '2025-12-16', 1, '60000.00', 'paid', 'checked_out', 46, '2025-12-15 17:32:55', '18:32:55', '12:00:00', '2025-12-15 19:19:15', 1, 2, NULL, NULL, '2025-12-15 19:19:15', '19:19:15'),
(47, 2, 14, '2025-12-15', '2025-12-17', 2, '120000.00', 'cancelled', 'cancelled', 47, '2025-12-15 18:38:54', '19:38:54', '12:00:00', NULL, 0, NULL, NULL, NULL, NULL, NULL),
(48, 3, 16, '2025-12-15', '2025-12-17', 2, '120000.00', 'cancelled', 'cancelled', 48, '2025-12-15 19:57:41', '20:57:41', '12:00:00', NULL, 0, NULL, NULL, NULL, NULL, NULL),
(49, 2, 8, '2025-12-16', '2025-12-18', 2, '82000.00', 'cancelled', 'cancelled', 49, '2025-12-16 09:39:52', '10:39:52', '12:00:00', NULL, 0, NULL, NULL, NULL, NULL, NULL),
(50, 4, 18, '2025-12-16', '2025-12-18', 2, '120000.00', 'paid', 'checked_out', 50, '2025-12-16 12:23:43', '13:23:43', '12:00:00', '2025-12-16 13:53:47', 1, 2, NULL, NULL, '2025-12-16 13:53:47', '13:53:47'),
(51, 4, 17, '2025-12-16', '2025-12-18', 2, '120000.00', 'paid', 'checked_out', 51, '2025-12-16 12:56:50', '13:56:50', '12:00:00', '2025-12-16 13:59:44', 1, 2, NULL, NULL, '2025-12-16 13:59:44', '13:59:44'),
(52, 4, 19, '2025-12-16', '2025-12-18', 2, '120000.00', 'paid', 'checked_out', 52, '2025-12-16 12:58:05', '13:58:05', '12:00:00', '2025-12-16 14:01:41', 1, 2, NULL, NULL, '2025-12-16 14:01:41', '14:01:41'),
(53, 4, 18, '2025-12-16', '2025-12-18', 2, '120000.00', 'paid', 'checked_out', 53, '2025-12-16 13:04:18', '14:04:18', '12:00:00', '2025-12-16 14:05:01', 1, 2, NULL, NULL, '2025-12-16 14:05:01', '14:05:01'),
(54, 3, 8, '2025-12-16', '2025-12-18', 2, '82000.00', 'paid', 'checked_out', 54, '2025-12-16 13:34:43', '14:34:43', '12:00:00', '2025-12-16 14:41:29', 1, 2, NULL, NULL, '2025-12-16 14:41:29', '14:41:29'),
(55, 9, 9, '2025-12-16', '2025-12-19', 3, '12000.00', 'paid', 'checked_out', 55, '2025-12-16 13:55:23', '14:55:23', '12:00:00', '2025-12-16 14:55:59', 1, 2, NULL, NULL, '2025-12-16 14:55:59', '14:55:59'),
(56, 4, 10, '2025-12-17', '2025-12-18', 1, '41000.00', 'paid', 'checked_out', 56, '2025-12-16 14:13:22', '08:50:00', '12:00:00', '2025-12-17 08:57:51', 1, 1, NULL, NULL, '2025-12-17 08:57:51', '08:57:51'),
(57, 10, 10, '2025-12-16', '2025-12-17', 1, '41000.00', 'paid', 'checked_out', 57, '2025-12-16 14:25:23', '15:25:23', '12:00:00', '2025-12-16 15:25:52', 1, 2, NULL, NULL, '2025-12-16 15:25:52', '15:25:52'),
(58, 11, 15, '2025-12-16', '2025-12-18', 2, '120000.00', 'paid', 'checked_out', 58, '2025-12-16 16:44:44', '17:44:44', '12:00:00', '2025-12-16 17:46:32', 1, 1, NULL, NULL, '2025-12-16 17:46:32', '17:46:32'),
(59, 11, 11, '2025-12-16', '2025-12-18', 2, '2400.00', 'paid', 'checked_out', 59, '2025-12-16 17:02:30', '18:02:30', '12:00:00', '2025-12-16 18:03:00', 1, 1, NULL, NULL, '2025-12-16 18:03:00', '18:03:00'),
(60, 12, 12, '2025-12-16', '2025-12-18', 2, '2400.00', 'paid', 'checked_out', 60, '2025-12-16 19:20:54', '20:20:54', '12:00:00', '2025-12-16 20:23:41', 1, 2, NULL, NULL, '2025-12-16 20:23:41', '20:23:41'),
(61, 4, 13, '2025-12-16', '2025-12-18', 2, '2400.00', 'paid', 'checked_out', 61, '2025-12-16 19:42:45', '20:42:45', '12:00:00', '2025-12-16 21:23:44', 1, 2, NULL, NULL, '2025-12-16 21:23:44', '21:23:44'),
(62, 12, 1, '2025-12-16', '2025-12-18', 2, '28000.00', 'paid', 'checked_out', 62, '2025-12-16 19:48:53', '20:48:53', '12:00:00', '2025-12-16 22:22:50', 1, 2, NULL, NULL, '2025-12-16 22:22:50', '22:22:50'),
(63, 12, 2, '2025-12-16', '2025-12-18', 2, '28000.00', 'paid', 'checked_out', 63, '2025-12-16 19:54:00', '20:54:00', '12:00:00', '2025-12-16 22:55:11', 1, 2, NULL, NULL, '2025-12-16 22:55:11', '22:55:11'),
(64, 10, 3, '2025-12-16', '2025-12-18', 2, '28000.00', 'paid', 'checked_out', 64, '2025-12-16 19:56:38', '20:56:38', '12:00:00', '2025-12-16 22:51:25', 1, 2, NULL, NULL, '2025-12-16 22:51:25', '22:51:25'),
(65, 12, 5, '2025-12-16', '2025-12-18', 2, '5000.00', 'paid', 'checked_out', 65, '2025-12-16 20:03:55', '21:03:55', '12:00:00', '2025-12-16 22:29:19', 1, 2, NULL, NULL, '2025-12-16 22:29:19', '22:29:19'),
(66, 12, 4, '2025-12-16', '2025-12-18', 2, '28000.00', 'paid', 'checked_out', 66, '2025-12-16 20:18:47', '21:18:47', '12:00:00', '2025-12-16 22:31:44', 1, 2, NULL, NULL, '2025-12-16 22:31:44', '22:31:44'),
(67, 5, 6, '2025-12-16', '2025-12-18', 2, '5000.00', 'paid', 'checked_out', 67, '2025-12-16 21:21:44', '22:21:44', '12:00:00', '2025-12-16 22:33:13', 1, 2, NULL, NULL, '2025-12-16 22:33:13', '22:33:13'),
(68, 4, 7, '2025-12-16', '2025-12-18', 2, '5000.00', 'paid', 'checked_out', 68, '2025-12-16 21:42:57', '22:42:57', '12:00:00', '2025-12-16 22:44:31', 1, 2, NULL, NULL, '2025-12-16 22:44:31', '22:44:31'),
(69, 11, 10, '2025-12-17', '2025-12-19', 2, '82000.00', 'paid', 'checked_out', 69, '2025-12-17 08:02:50', '09:02:50', '12:00:00', '2025-12-17 13:00:35', 1, 2, NULL, NULL, '2025-12-17 13:00:35', '13:00:35'),
(70, 8, 14, '2025-12-17', '2025-12-19', 2, '120000.00', 'paid', 'checked_out', 70, '2025-12-17 12:11:21', '13:11:21', '12:00:00', '2025-12-17 13:12:56', 1, 2, NULL, NULL, '2025-12-17 13:12:56', '13:12:56'),
(71, 6, 16, '2025-12-17', '2025-12-19', 2, '120000.00', 'paid', 'checked_out', 71, '2025-12-17 12:18:20', '13:18:20', '12:00:00', '2025-12-17 13:21:35', 1, 2, NULL, NULL, '2025-12-17 13:21:35', '13:21:35'),
(72, 11, 20, '2025-12-17', '2025-12-18', 1, '60000.00', 'paid', 'checked_out', 72, '2025-12-17 12:24:26', '13:24:26', '12:00:00', '2025-12-17 13:33:42', 1, 2, NULL, NULL, '2025-12-17 13:33:42', '13:33:42'),
(73, 12, 17, '2025-12-17', '2025-12-18', 1, '60000.00', 'paid', 'checked_out', 73, '2025-12-17 15:17:03', '16:17:03', '12:00:00', '2025-12-17 16:18:54', 1, 2, NULL, NULL, '2025-12-17 16:18:54', '16:18:54'),
(74, 10, 17, '2025-12-18', '2025-12-19', 1, '60000.00', 'cancelled', 'cancelled', 74, '2025-12-17 15:24:07', '14:00:00', '12:00:00', NULL, 0, NULL, NULL, NULL, NULL, NULL),
(75, 8, 17, '2025-12-17', '2025-12-19', 2, '120000.00', 'cancelled', 'cancelled', 75, '2025-12-17 15:41:27', '16:41:27', '12:00:00', NULL, 0, NULL, NULL, NULL, NULL, NULL),
(76, 9, 17, '2025-12-18', '2025-12-19', 1, '60000.00', 'cancelled', 'cancelled', 76, '2025-12-17 15:45:29', '14:00:00', '12:00:00', NULL, 0, NULL, NULL, NULL, NULL, NULL),
(77, 5, 17, '2025-12-18', '2025-12-19', 1, '60000.00', 'cancelled', 'cancelled', 77, '2025-12-17 15:51:14', '14:00:00', '12:00:00', NULL, 0, NULL, NULL, NULL, NULL, NULL),
(78, 9, 19, '2025-12-18', '2025-12-19', 1, '60000.00', 'cancelled', 'cancelled', 78, '2025-12-17 15:55:43', '14:00:00', '12:00:00', NULL, 0, NULL, NULL, NULL, NULL, NULL),
(79, 8, 17, '2025-12-17', '2025-12-19', 2, '120000.00', 'paid', 'checked_out', 79, '2025-12-17 17:23:51', '18:23:51', '12:00:00', '2025-12-17 18:29:03', 1, 2, NULL, NULL, '2025-12-17 18:29:03', '18:29:03'),
(80, 12, 17, '2025-12-18', '2025-12-19', 1, '60000.00', 'paid', 'checked_out', 80, '2025-12-17 17:34:20', '14:00:00', '12:00:00', '2025-12-18 12:22:22', 1, 2, NULL, NULL, '2025-12-18 12:22:22', '12:22:22'),
(81, 1, 19, '2025-12-18', '2025-12-19', 1, '60000.00', 'cancelled', 'cancelled', 81, '2025-12-17 17:55:12', '14:00:00', '12:00:00', NULL, 0, NULL, NULL, NULL, NULL, NULL),
(82, 10, 18, '2025-12-19', '2025-12-20', 1, '60000.00', 'paid', 'checked_out', 82, '2025-12-18 09:31:04', '14:00:00', '12:00:00', '2025-12-19 15:23:56', 1, 2, NULL, NULL, '2025-12-19 15:23:56', '15:23:56'),
(83, 8, 20, '2025-12-18', '2025-12-20', 2, '120000.00', 'paid', 'checked_out', 83, '2025-12-18 09:54:41', '10:54:41', '12:00:00', '2025-12-18 12:04:43', 1, 2, NULL, NULL, '2025-12-18 12:04:43', '12:04:43'),
(84, 5, 15, '2025-12-18', '2025-12-20', 2, '120000.00', 'paid', 'checked_out', 84, '2025-12-18 11:19:42', '12:19:42', '12:00:00', '2025-12-18 12:52:12', 1, 2, NULL, NULL, '2025-12-18 12:52:12', '12:52:12'),
(85, 11, 11, '2025-12-19', '2025-12-20', 1, '1200.00', 'paid', 'checked_out', 85, '2025-12-18 11:21:08', '14:00:00', '12:00:00', '2025-12-19 12:57:32', 1, 2, NULL, NULL, '2025-12-19 12:57:32', '12:57:32'),
(86, 11, 8, '2025-12-18', '2025-12-20', 2, '82000.00', 'paid', 'checked_out', 86, '2025-12-18 11:32:17', '12:32:17', '12:00:00', '2025-12-18 13:33:52', 1, 2, NULL, NULL, '2025-12-18 13:33:52', '13:33:52'),
(87, 12, 12, '2025-12-18', '2025-12-20', 2, '2400.00', 'paid', 'checked_out', 87, '2025-12-18 11:34:12', '12:34:12', '12:00:00', '2025-12-18 13:34:03', 1, 2, NULL, NULL, '2025-12-18 13:34:03', '13:34:03'),
(88, 11, 13, '2025-12-18', '2025-12-19', 1, '1200.00', 'paid', 'checked_out', 88, '2025-12-18 11:52:55', '12:52:55', '12:00:00', '2025-12-18 13:34:12', 1, 2, NULL, NULL, '2025-12-18 13:34:12', '13:34:12'),
(89, 4, 1, '2025-12-18', '2025-12-19', 1, '14000.00', 'paid', 'checked_out', 89, '2025-12-18 11:53:43', '12:53:43', '12:00:00', '2025-12-18 12:54:06', 1, 2, NULL, NULL, '2025-12-18 12:54:06', '12:54:06'),
(90, 13, 11, '2025-12-21', '2025-12-22', 1, '1200.00', 'cancelled', 'cancelled', 90, '2025-12-20 13:41:27', '14:00:00', '12:00:00', NULL, 0, NULL, NULL, NULL, NULL, NULL),
(91, 11, 11, '2025-12-20', '2025-12-21', 1, '1200.00', 'paid', 'checked_out', 91, '2025-12-20 13:43:31', '14:43:31', '12:00:00', '2025-12-20 14:43:51', 1, 1, NULL, NULL, '2025-12-20 14:43:51', '14:43:51'),
(92, 14, 20, '2025-12-28', '2025-12-31', 3, '180000.00', 'paid', 'checked_out', 92, '2025-12-28 09:34:10', '10:34:10', '12:00:00', '2025-12-28 10:40:31', 1, 1, NULL, NULL, '2025-12-28 10:40:31', '10:40:31'),
(93, 13, 18, '2025-12-29', '2025-12-31', 2, '120000.00', 'cancelled', 'cancelled', 93, '2025-12-28 09:51:26', '14:00:00', '12:00:00', NULL, 0, NULL, NULL, NULL, NULL, NULL),
(94, 12, 18, '2026-08-21', '2026-08-23', 2, '120000.00', 'paid', 'checked_out', 94, '2026-08-21 10:35:41', '11:35:41', '12:00:00', '2026-08-21 11:57:09', 1, 4, NULL, NULL, '2026-08-21 11:57:09', '11:57:09'),
(95, 6, 18, '2026-08-21', '2026-08-23', 2, '120000.00', 'paid', 'checked_out', 95, '2026-08-21 14:14:27', '15:14:27', '12:00:00', '2026-08-21 15:36:22', 1, 4, NULL, NULL, '2026-08-21 15:36:22', '15:36:22'),
(96, 1, 17, '2026-08-22', '2026-08-23', 1, '60000.00', 'paid', 'cancelled', 96, '2026-08-21 15:17:21', '14:00:00', '12:00:00', '2026-08-22 22:00:18', 1, 1, NULL, NULL, '2026-08-22 22:00:18', '22:00:18'),
(97, 11, 19, '2026-08-21', '2026-08-22', 1, '60000.00', 'paid', 'checked_out', 97, '2026-08-21 15:29:30', '16:29:30', '12:00:00', '2026-08-21 16:30:37', 1, 4, NULL, NULL, '2026-08-21 16:30:37', '16:30:37'),
(98, 8, 19, '2026-08-21', '2026-08-23', 2, '120000.00', 'paid', 'checked_out', 98, '2026-08-21 15:55:03', '16:55:03', '12:00:00', '2026-08-21 16:57:07', 1, 4, NULL, NULL, '2026-08-21 16:57:07', '16:57:07');

-- --------------------------------------------------------

--
-- Table structure for table `buildings`
--

CREATE TABLE `buildings` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `building_type` enum('regular','vip','premium') DEFAULT 'regular',
  `description` text,
  `base_price` decimal(10,2) DEFAULT '1000.00',
  `status` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='hotel buildings information';

--
-- Dumping data for table `buildings`
--

INSERT INTO `buildings` (`id`, `name`, `building_type`, `description`, `base_price`, `status`, `created_at`) VALUES
(1, 'Main Building', 'regular', 'Main hotel block with standard rooms', '14000.00', 1, '2025-12-05 18:05:21'),
(2, 'VIP Tower', 'vip', 'Premium VIP accommodations with extra amenities', '2500.00', 1, '2025-12-05 18:05:21'),
(3, 'Executive Wing', 'regular', 'Executive suites with luxury features', '41000.00', 1, '2025-12-06 15:18:51'),
(4, 'Garden View Block', 'regular', 'Rooms with garden view', '1200.00', 1, '2025-12-05 18:05:21'),
(5, '1337EMPIRE', 'vip', 'elites only', '60000.00', 1, '2025-12-17 12:10:14'),
(6, 'AKIGA BLOCK', 'regular', 'chief felix akiga hall', '25000.00', 1, '2025-12-16 11:18:11'),
(7, 'ismail hall', 'vip', 'blah blah', '20000.00', 1, '2025-12-28 09:28:37');

-- --------------------------------------------------------

--
-- Table structure for table `checkouts`
--

CREATE TABLE `checkouts` (
  `id` int(11) NOT NULL,
  `booking_id` bigint(20) UNSIGNED NOT NULL,
  `invoice_id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `room_id` int(10) UNSIGNED NOT NULL,
  `checkout_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actual_checkout_date` date NOT NULL,
  `checkout_time` time DEFAULT '12:00:00',
  `checked_out_by` int(10) UNSIGNED NOT NULL,
  `room_condition` varchar(20) DEFAULT 'good',
  `additional_charges` decimal(10,2) DEFAULT '0.00',
  `charges_description` text,
  `refund_amount` decimal(10,2) DEFAULT '0.00',
  `refund_description` text,
  `notes` text,
  `status` varchar(20) DEFAULT 'completed',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='checkout and refund mapper';

--
-- Dumping data for table `checkouts`
--

INSERT INTO `checkouts` (`id`, `booking_id`, `invoice_id`, `customer_id`, `room_id`, `checkout_date`, `actual_checkout_date`, `checkout_time`, `checked_out_by`, `room_condition`, `additional_charges`, `charges_description`, `refund_amount`, `refund_description`, `notes`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 9, 8, '2025-12-13 09:07:03', '2025-12-13', '10:07:03', 1, 'good', '0.00', '', '0.00', '', '', 'completed', '2025-12-13 09:07:03', '2025-12-13 09:07:03'),
(2, 2, 2, 8, 9, '2025-12-13 09:41:16', '2025-12-13', '10:41:16', 1, 'good', '0.00', '', '0.00', '', '', 'completed', '2025-12-13 09:41:16', '2025-12-13 09:41:16'),
(3, 17, 17, 6, 12, '2025-12-13 09:43:07', '2025-12-13', '10:43:07', 1, 'good', '0.00', '', '600.00', 'Early checkout: 1 day(s) unused (50% refund)', '\n\n[REFUND PAID: 2025-12-13 17:31:42 | Method: CASH | Amount: ₦600.00]', 'completed', '2025-12-13 09:43:07', '2025-12-13 16:31:42'),
(4, 19, 19, 6, 12, '2025-12-13 12:16:51', '2025-12-13', '13:16:51', 1, 'good', '0.00', '', '0.00', '', '', 'completed', '2025-12-13 12:16:51', '2025-12-13 12:16:51'),
(5, 16, 16, 6, 3, '2025-12-13 12:23:50', '2025-12-13', '13:23:50', 1, 'good', '0.00', '', '0.00', '', '', 'completed', '2025-12-13 12:23:50', '2025-12-13 12:23:50'),
(6, 21, 21, 5, 3, '2025-12-13 17:51:41', '2025-12-13', '18:51:41', 1, 'good', '0.00', '', '0.00', '', '', 'completed', '2025-12-13 17:51:41', '2025-12-13 17:51:41'),
(7, 20, 20, 5, 12, '2025-12-13 18:51:03', '2025-12-13', '19:51:03', 1, 'good', '0.00', '', '0.00', '', '', 'completed', '2025-12-13 18:51:03', '2025-12-13 18:51:03'),
(8, 12, 12, 1, 3, '2025-12-13 19:06:45', '2025-12-13', '20:06:45', 1, 'good', '0.00', NULL, '0.00', 'nothing', NULL, 'cancelled', '2025-12-13 19:06:45', '2025-12-13 19:06:45'),
(9, 5, 5, 5, 12, '2025-12-13 19:09:56', '2025-12-13', '20:09:56', 1, 'good', '0.00', NULL, '300.00', 'oho oho oho', '\n\n[REFUND PAID: 2025-12-17 15:48:08 | Method: CASH | Amount: ₦300.00]', 'cancelled', '2025-12-13 19:09:56', '2025-12-17 14:48:08'),
(10, 3, 3, 6, 14, '2025-12-13 19:11:09', '2025-12-13', '20:11:09', 1, 'good', '0.00', '', '0.00', '', '', 'completed', '2025-12-13 19:11:09', '2025-12-13 19:11:09'),
(11, 14, 14, 8, 6, '2025-12-13 19:11:59', '2025-12-13', '20:11:59', 1, 'good', '0.00', '', '0.00', 'Early checkout: 1 day(s) unused (50% refund)', '', 'completed', '2025-12-13 19:11:59', '2025-12-13 19:11:59'),
(12, 10, 10, 4, 4, '2025-12-13 19:12:20', '2025-12-13', '20:12:20', 1, 'good', '0.00', '', '0.00', 'Early checkout: 1 day(s) unused (50% refund)', 'zarag0za', 'completed', '2025-12-13 19:12:20', '2025-12-13 19:12:20'),
(13, 22, 22, 10, 3, '2025-12-13 21:15:39', '2025-12-13', '22:15:39', 2, 'good', '0.00', '', '0.00', '', '', 'completed', '2025-12-13 21:15:39', '2025-12-13 21:15:39'),
(14, 31, 31, 4, 15, '2025-12-14 16:44:24', '2025-12-14', '17:44:24', 2, 'good', '0.00', NULL, '0.00', 'roses aint really red', NULL, 'cancelled', '2025-12-14 16:44:24', '2025-12-14 16:44:24'),
(15, 23, 23, 9, 12, '2025-12-14 16:47:40', '2025-12-14', '17:47:40', 2, 'good', '0.00', NULL, '600.00', 'bbbbbbbbbbbbbbbbbbbb', '\n\n[REFUND PAID: 2025-12-17 15:38:20 | Method: TRANSFER | Amount: ₦600.00]', 'cancelled', '2025-12-14 16:47:40', '2025-12-17 14:38:20'),
(16, 34, 34, 9, 12, '2025-12-15 08:15:43', '2025-12-15', '09:15:43', 2, 'good', '0.00', NULL, '0.00', 'xxxxxxxxxxxxxxx', NULL, 'cancelled', '2025-12-15 08:15:43', '2025-12-15 08:15:43'),
(17, 33, 33, 8, 19, '2025-12-15 10:44:10', '2025-12-15', '11:44:10', 1, 'good', '0.00', NULL, '0.00', 'tyytyytyy', NULL, 'cancelled', '2025-12-15 10:44:10', '2025-12-15 10:44:10'),
(18, 32, 32, 10, 15, '2025-12-15 10:45:00', '2025-12-15', '11:45:00', 1, 'good', '0.00', NULL, '0.00', 'tyytyy', NULL, 'cancelled', '2025-12-15 10:45:00', '2025-12-15 10:45:00'),
(19, 35, 35, 6, 12, '2025-12-15 10:45:23', '2025-12-15', '11:45:23', 1, 'good', '0.00', NULL, '300.00', 'kkkkkkkkkkk', '\n\n[REFUND PAID: 2025-12-17 15:32:27 | Method: CASH | Amount: ₦300.00]', 'cancelled', '2025-12-15 10:45:23', '2025-12-17 14:32:27'),
(20, 27, 27, 5, 3, '2025-12-15 10:45:41', '2025-12-15', '11:45:41', 1, 'good', '0.00', NULL, '0.00', 'kkkkkkkkkkk', NULL, 'cancelled', '2025-12-15 10:45:41', '2025-12-15 10:45:41'),
(21, 36, 36, 3, 19, '2025-12-15 13:35:11', '2025-12-15', '14:35:11', 2, 'good', '0.00', NULL, '0.00', 'sssssss', NULL, 'cancelled', '2025-12-15 13:35:11', '2025-12-15 13:35:11'),
(22, 15, 15, 6, 8, '2025-12-15 13:36:09', '2025-12-15', '14:36:09', 2, 'good', '0.00', 'Late checkout: 1 day(s) past scheduled date (12:00 PM)', '0.00', '', '', 'completed', '2025-12-15 13:36:09', '2025-12-15 13:36:09'),
(23, 9, 9, 4, 11, '2025-12-15 13:36:21', '2025-12-15', '14:36:21', 2, 'good', '0.00', 'Late checkout: 1 day(s) past scheduled date (12:00 PM)', '0.00', '', '', 'completed', '2025-12-15 13:36:21', '2025-12-15 13:36:21'),
(24, 6, 6, 1, 13, '2025-12-15 13:36:33', '2025-12-15', '14:36:33', 2, 'good', '0.00', '', '0.00', '', '', 'completed', '2025-12-15 13:36:33', '2025-12-15 13:36:33'),
(25, 38, 38, 9, 15, '2025-12-15 13:40:46', '2025-12-15', '14:40:46', 2, 'good', '0.00', NULL, '0.00', 'sssssssssssssss', NULL, 'cancelled', '2025-12-15 13:40:46', '2025-12-15 13:40:46'),
(26, 37, 37, 1, 19, '2025-12-15 15:03:36', '2025-12-15', '16:03:36', 2, 'good', '0.00', '', '0.00', '', '', 'completed', '2025-12-15 15:03:36', '2025-12-15 15:03:36'),
(27, 11, 11, 5, 5, '2025-12-15 15:04:20', '2025-12-15', '16:04:20', 2, 'good', '2500.00', 'Late checkout: 1 day(s) past scheduled date (12:00 PM)', '0.00', '', '', 'completed', '2025-12-15 15:04:20', '2025-12-15 15:04:20'),
(28, 26, 26, 9, 4, '2025-12-15 15:04:34', '2025-12-15', '16:04:34', 2, 'good', '0.00', 'Late checkout: 1 day(s) past scheduled date (12:00 PM)', '0.00', '', '', 'completed', '2025-12-15 15:04:34', '2025-12-15 15:04:34'),
(29, 4, 4, 10, 10, '2025-12-15 15:06:02', '2025-12-15', '16:06:02', 2, 'good', '0.00', 'Late checkout: 1 day(s) past scheduled date (12:00 PM)', '0.00', '', '', 'completed', '2025-12-15 15:06:02', '2025-12-15 15:06:02'),
(30, 7, 7, 2, 2, '2025-12-15 15:06:27', '2025-12-15', '16:06:27', 2, 'good', '0.00', '', '0.00', '', '', 'completed', '2025-12-15 15:06:27', '2025-12-15 15:06:27'),
(31, 13, 13, 5, 7, '2025-12-15 15:06:38', '2025-12-15', '16:06:38', 2, 'good', '0.00', 'Late checkout: 1 day(s) past scheduled date (12:00 PM)', '0.00', '', '', 'completed', '2025-12-15 15:06:38', '2025-12-15 15:06:38'),
(32, 8, 8, 3, 1, '2025-12-15 15:06:51', '2025-12-15', '16:06:51', 2, 'good', '0.00', 'Late checkout: 1 day(s) past scheduled date (12:00 PM)', '0.00', '', '', 'completed', '2025-12-15 15:06:51', '2025-12-15 15:06:51'),
(33, 25, 25, 1, 6, '2025-12-15 15:07:44', '2025-12-15', '16:07:44', 2, 'good', '0.00', 'Late checkout: 1 day(s) past scheduled date (12:00 PM)', '0.00', '', '', 'completed', '2025-12-15 15:07:44', '2025-12-15 15:07:44'),
(34, 18, 18, 6, 9, '2025-12-15 15:07:56', '2025-12-15', '16:07:56', 2, 'good', '0.00', 'Late checkout: 1 day(s) past scheduled date (12:00 PM)', '0.00', '', '', 'completed', '2025-12-15 15:07:56', '2025-12-15 15:07:56'),
(35, 24, 24, 10, 14, '2025-12-15 15:08:05', '2025-12-15', '16:08:05', 2, 'good', '0.00', 'Late checkout: 1 day(s) past scheduled date (12:00 PM)', '0.00', '', '', 'completed', '2025-12-15 15:08:05', '2025-12-15 15:08:05'),
(36, 30, 30, 8, 16, '2025-12-15 15:09:29', '2025-12-15', '16:09:29', 2, 'good', '0.00', '', '0.00', '', '', 'completed', '2025-12-15 15:09:29', '2025-12-15 15:09:29'),
(37, 28, 28, 10, 17, '2025-12-15 15:09:52', '2025-12-15', '16:09:52', 2, 'good', '0.00', '', '0.00', '', '', 'completed', '2025-12-15 15:09:52', '2025-12-15 15:09:52'),
(38, 29, 29, 8, 18, '2025-12-15 15:10:17', '2025-12-15', '16:10:17', 2, 'good', '0.00', '', '0.00', '', '', 'completed', '2025-12-15 15:10:17', '2025-12-15 15:10:17'),
(39, 40, 40, 6, 15, '2025-12-15 15:25:50', '2025-12-15', '16:25:50', 2, 'good', '0.00', '', '0.00', '', '', 'completed', '2025-12-15 15:25:50', '2025-12-15 15:25:50'),
(40, 44, 44, 6, 14, '2025-12-15 17:14:37', '2025-12-15', '18:14:37', 2, 'good', '0.00', NULL, '0.00', 'nothing really o.. tchww', NULL, 'cancelled', '2025-12-15 17:14:37', '2025-12-15 17:14:37'),
(41, 45, 45, 3, 15, '2025-12-15 17:27:40', '2025-12-15', '18:27:40', 2, 'good', '0.00', '', '0.00', '', '', 'completed', '2025-12-15 17:27:40', '2025-12-15 17:27:40'),
(42, 49, 49, 2, 8, '2025-12-16 11:01:32', '2025-12-16', '12:01:32', 2, 'good', '0.00', NULL, '0.00', 'xxxxxxxxxxxx', NULL, 'cancelled', '2025-12-16 11:01:32', '2025-12-16 11:01:32'),
(43, 46, 46, 8, 15, '2025-12-16 11:58:23', '2025-12-16', '12:58:23', 2, 'good', '0.00', '', '0.00', '', '', 'completed', '2025-12-16 11:58:23', '2025-12-16 11:58:23'),
(44, 43, 43, 1, 19, '2025-12-16 12:08:21', '2025-12-16', '13:08:21', 2, 'good', '0.00', '', '0.00', '', '', 'completed', '2025-12-16 12:08:21', '2025-12-16 12:08:21'),
(45, 41, 41, 4, 18, '2025-12-16 12:21:12', '2025-12-16', '13:21:12', 2, 'good', '0.00', '', '0.00', '', '', 'completed', '2025-12-16 12:21:12', '2025-12-16 12:21:12'),
(46, 42, 42, 2, 17, '2025-12-16 12:25:39', '2025-12-16', '13:25:39', 2, 'good', '0.00', '', '0.00', '', '', 'completed', '2025-12-16 12:25:39', '2025-12-16 12:25:39'),
(47, 50, 50, 4, 18, '2025-12-16 13:00:43', '2025-12-16', '14:00:43', 2, 'good', '0.00', '', '0.00', 'Early checkout: 1 day(s) unused (50% refund)', '', 'completed', '2025-12-16 13:00:43', '2025-12-16 13:00:43'),
(48, 39, 39, 10, 15, '2025-12-16 15:37:09', '2025-12-16', '16:37:09', 2, 'good', '0.00', '', '0.00', '', '', 'completed', '2025-12-16 15:37:09', '2025-12-16 15:37:09'),
(49, 57, 57, 10, 10, '2025-12-16 20:31:12', '2025-12-16', '21:31:12', 2, 'good', '0.00', '', '0.00', '', '', 'completed', '2025-12-16 20:31:12', '2025-12-16 20:31:12'),
(50, 56, 56, 4, 10, '2025-12-17 07:59:35', '2025-12-17', '08:59:35', 1, 'good', '0.00', '', '20000.00', 'Early checkout: 1 day(s) unused (50% refund)', '\n\n[REFUND PAID: 2025-12-17 15:16:41 | Method: POS | Amount: ₦20,000.00]', 'completed', '2025-12-17 07:59:35', '2025-12-17 14:16:41'),
(51, 48, 48, 3, 16, '2025-12-17 12:04:53', '2025-12-17', '13:04:53', 2, 'good', '0.00', NULL, '0.00', 'nnnnnnnnnnnnnnnnnnnnn', NULL, 'cancelled', '2025-12-17 12:04:53', '2025-12-17 12:04:53'),
(52, 47, 47, 2, 14, '2025-12-17 12:05:27', '2025-12-17', '13:05:27', 2, 'good', '0.00', NULL, '0.00', 'bbbbbbbbbbbb', NULL, 'cancelled', '2025-12-17 12:05:27', '2025-12-17 12:05:27'),
(53, 67, 67, 5, 6, '2025-12-17 12:46:05', '2025-12-17', '13:46:05', 2, 'good', '0.00', '', '0.00', '', '', 'completed', '2025-12-17 12:46:05', '2025-12-17 12:46:05'),
(54, 52, 52, 4, 19, '2025-12-17 12:52:20', '2025-12-17', '13:52:20', 2, 'good', '0.00', '', '0.00', '', '', 'completed', '2025-12-17 12:52:20', '2025-12-17 12:52:20'),
(55, 72, 72, 11, 20, '2025-12-17 12:58:25', '2025-12-17', '13:58:25', 2, 'good', '0.00', '', '0.00', '', '', 'completed', '2025-12-17 12:58:25', '2025-12-17 12:58:25'),
(56, 51, 51, 4, 17, '2025-12-17 15:13:17', '2025-12-17', '16:13:17', 2, 'good', '0.00', '', '0.00', '', '', 'completed', '2025-12-17 15:13:17', '2025-12-17 15:13:17'),
(57, 73, 73, 12, 17, '2025-12-17 15:23:47', '2025-12-17', '16:23:47', 2, 'good', '0.00', '', '0.00', '', '', 'completed', '2025-12-17 15:23:47', '2025-12-17 15:23:47'),
(58, 74, 74, 10, 17, '2025-12-17 15:24:49', '2025-12-17', '16:24:49', 2, 'good', '0.00', NULL, '15000.00', 'zzz nnn zzz', '\n\n[REFUND PAID: 2025-12-17 16:25:59 | Method: CASH | Amount: ₦15,000.00]', 'cancelled', '2025-12-17 15:24:49', '2025-12-17 15:25:59'),
(59, 59, 59, 11, 11, '2025-12-17 15:37:10', '2025-12-17', '16:37:10', 2, 'good', '0.00', '', '2000.00', '', '\n\n[REFUND PAID: 2025-12-17 16:37:26 | Method: CASH | Amount: ₦2,000.00]', 'completed', '2025-12-17 15:37:10', '2025-12-17 15:37:26'),
(60, 75, 75, 8, 17, '2025-12-17 15:42:03', '2025-12-17', '16:42:03', 2, 'good', '0.00', NULL, '0.00', 'yoyoyo', NULL, 'cancelled', '2025-12-17 15:42:03', '2025-12-17 15:42:03'),
(61, 76, 76, 9, 17, '2025-12-17 15:46:09', '2025-12-17', '16:46:09', 2, 'good', '0.00', NULL, '0.00', 'bye suckers', NULL, 'cancelled', '2025-12-17 15:46:09', '2025-12-17 15:46:09'),
(62, 77, 77, 5, 17, '2025-12-17 15:52:13', '2025-12-17', '16:52:13', 2, 'good', '0.00', NULL, '0.00', 'what tha fffffffffffff', NULL, 'cancelled', '2025-12-17 15:52:13', '2025-12-17 15:52:13'),
(63, 78, 78, 9, 19, '2025-12-17 15:56:04', '2025-12-17', '16:56:04', 2, 'good', '0.00', NULL, '0.00', 'kawai nagadama ne', NULL, 'cancelled', '2025-12-17 15:56:04', '2025-12-17 15:56:04'),
(64, 79, 79, 8, 17, '2025-12-17 17:29:29', '2025-12-17', '18:29:29', 2, 'good', '0.00', '', '30000.00', 'Early checkout: 1 day(s) unused (50% refund)', '\n\n[REFUND PAID: 2025-12-20 14:38:55 | Method: POS | Amount: ₦30,000.00]', 'completed', '2025-12-17 17:29:29', '2025-12-20 13:38:55'),
(65, 53, 53, 4, 18, '2025-12-18 09:10:33', '2025-12-18', '10:10:33', 2, 'good', '0.00', '', '0.00', '', 'nice customer', 'completed', '2025-12-18 09:10:33', '2025-12-18 09:10:33'),
(66, 54, 54, 3, 8, '2025-12-18 09:11:17', '2025-12-18', '10:11:17', 2, 'good', '0.00', '', '0.00', '', '', 'completed', '2025-12-18 09:11:17', '2025-12-18 09:11:17'),
(67, 61, 61, 4, 13, '2025-12-18 09:11:37', '2025-12-18', '10:11:37', 2, 'good', '0.00', '', '0.00', '', '', 'completed', '2025-12-18 09:11:37', '2025-12-18 09:11:37'),
(68, 58, 58, 11, 15, '2025-12-18 09:12:10', '2025-12-18', '10:12:10', 2, 'good', '0.00', '', '0.00', '', '', 'completed', '2025-12-18 09:12:10', '2025-12-18 09:12:10'),
(69, 60, 60, 12, 12, '2025-12-18 09:14:13', '2025-12-18', '10:14:13', 2, 'good', '0.00', '', '0.00', '', '', 'completed', '2025-12-18 09:14:13', '2025-12-18 09:14:13'),
(70, 68, 68, 4, 7, '2025-12-18 09:14:48', '2025-12-18', '10:14:48', 2, 'good', '0.00', '', '0.00', '', '', 'completed', '2025-12-18 09:14:48', '2025-12-18 09:14:48'),
(71, 62, 62, 12, 1, '2025-12-18 09:18:43', '2025-12-18', '10:18:43', 2, 'good', '0.00', '', '0.00', '', '', 'completed', '2025-12-18 09:18:43', '2025-12-18 09:18:43'),
(72, 63, 63, 12, 2, '2025-12-18 09:18:50', '2025-12-18', '10:18:50', 2, 'good', '0.00', '', '0.00', '', '', 'completed', '2025-12-18 09:18:50', '2025-12-18 09:18:50'),
(73, 64, 64, 10, 3, '2025-12-18 09:19:00', '2025-12-18', '10:19:00', 2, 'good', '0.00', '', '0.00', '', '', 'completed', '2025-12-18 09:19:00', '2025-12-18 09:19:00'),
(74, 65, 65, 12, 5, '2025-12-18 10:18:04', '2025-12-18', '11:18:04', 2, 'good', '0.00', '', '0.00', '', '', 'completed', '2025-12-18 10:18:04', '2025-12-18 10:18:04'),
(75, 66, 66, 12, 4, '2025-12-18 10:18:11', '2025-12-18', '11:18:11', 2, 'good', '0.00', '', '0.00', '', '', 'completed', '2025-12-18 10:18:11', '2025-12-18 10:18:11'),
(76, 55, 55, 9, 9, '2025-12-19 09:52:15', '2025-12-19', '10:52:15', 2, 'good', '0.00', '', '0.00', '', '', 'completed', '2025-12-19 09:52:15', '2025-12-19 09:52:15'),
(77, 69, 69, 11, 10, '2025-12-19 09:57:30', '2025-12-19', '10:57:30', 2, 'good', '0.00', '', '0.00', '', '', 'completed', '2025-12-19 09:57:30', '2025-12-19 09:57:30'),
(78, 70, 70, 8, 14, '2025-12-19 09:57:43', '2025-12-19', '10:57:43', 2, 'good', '0.00', '', '0.00', '', '', 'completed', '2025-12-19 09:57:43', '2025-12-19 09:57:43'),
(79, 89, 89, 4, 1, '2025-12-19 14:18:56', '2025-12-19', '15:18:56', 2, 'good', '14000.00', 'Late checkout: 1 day(s) past scheduled date (12:00 PM)', '0.00', '', '', 'completed', '2025-12-19 14:18:56', '2025-12-19 14:18:56'),
(80, 71, 71, 6, 16, '2025-12-19 14:19:20', '2025-12-19', '15:19:20', 2, 'good', '70000.00', 'Late checkout: 1 day(s) past scheduled date (12:00 PM)', '0.00', '', '', 'completed', '2025-12-19 14:19:20', '2025-12-19 14:19:20'),
(81, 80, 80, 12, 17, '2025-12-19 14:19:39', '2025-12-19', '15:19:39', 2, 'good', '0.00', 'Late checkout: 1 day(s) past scheduled date (12:00 PM)', '0.00', '', '', 'completed', '2025-12-19 14:19:39', '2025-12-19 14:19:39'),
(82, 88, 88, 11, 13, '2025-12-19 14:19:50', '2025-12-19', '15:19:50', 2, 'good', '0.00', 'Late checkout: 1 day(s) past scheduled date (12:00 PM)', '0.00', '', '', 'completed', '2025-12-19 14:19:50', '2025-12-19 14:19:50'),
(83, 81, 81, 1, 19, '2025-12-19 14:21:11', '2025-12-19', '15:21:11', 2, 'good', '0.00', NULL, '0.00', 'him no show', NULL, 'cancelled', '2025-12-19 14:21:11', '2025-12-19 14:21:11'),
(84, 82, 82, 10, 18, '2025-12-20 13:22:53', '2025-12-20', '14:22:53', 1, 'good', '0.00', 'Late checkout: 1 day(s) past scheduled date (12:00 PM)', '0.00', '', '', 'completed', '2025-12-20 13:22:53', '2025-12-20 13:22:53'),
(85, 83, 83, 8, 20, '2025-12-20 13:23:08', '2025-12-20', '14:23:08', 1, 'good', '0.00', 'Late checkout: 1 day(s) past scheduled date (12:00 PM)', '0.00', '', '', 'completed', '2025-12-20 13:23:08', '2025-12-20 13:23:08'),
(86, 87, 87, 12, 12, '2025-12-20 13:23:31', '2025-12-20', '14:23:31', 1, 'good', '0.00', 'Late checkout: 1 day(s) past scheduled date (12:00 PM)', '0.00', '', '', 'completed', '2025-12-20 13:23:31', '2025-12-20 13:23:31'),
(87, 86, 86, 11, 8, '2025-12-20 13:23:50', '2025-12-20', '14:23:50', 1, 'good', '0.00', 'Late checkout: 1 day(s) past scheduled date (12:00 PM)', '0.00', '', '', 'completed', '2025-12-20 13:23:50', '2025-12-20 13:23:50'),
(88, 85, 85, 11, 11, '2025-12-20 13:24:22', '2025-12-20', '14:24:22', 1, 'good', '0.00', 'Late checkout: 1 day(s) past scheduled date (12:00 PM)', '0.00', '', '', 'completed', '2025-12-20 13:24:22', '2025-12-20 13:24:22'),
(89, 84, 84, 5, 15, '2025-12-20 13:24:40', '2025-12-20', '14:24:40', 1, 'good', '0.00', 'Late checkout: 1 day(s) past scheduled date (12:00 PM)', '0.00', '', '', 'completed', '2025-12-20 13:24:40', '2025-12-20 13:24:40'),
(90, 93, 93, 13, 18, '2026-01-20 14:17:12', '2026-01-20', '15:17:12', 1, 'good', '0.00', NULL, '0.00', 'dddddddddddddddddddddddd', NULL, 'cancelled', '2026-01-20 14:17:12', '2026-01-20 14:17:12'),
(91, 90, 90, 13, 11, '2026-01-20 14:17:27', '2026-01-20', '15:17:27', 1, 'good', '0.00', NULL, '300.00', 'blah blah', '\n\n[REFUND PAID: 2026-08-21 11:53:52 | Method: CASH | Amount: ₦300.00]', 'cancelled', '2026-01-20 14:17:27', '2026-08-21 10:53:52'),
(92, 92, 92, 14, 20, '2026-01-20 14:17:40', '2026-01-20', '15:17:40', 1, 'good', '0.00', 'Late checkout: 20 day(s) past scheduled date (12:00 PM)', '0.00', '', '', 'completed', '2026-01-20 14:17:40', '2026-01-20 14:17:40'),
(93, 91, 91, 11, 11, '2026-01-20 14:18:18', '2026-01-20', '15:18:18', 1, 'good', '0.00', 'Late checkout: 30 day(s) past scheduled date (12:00 PM)', '0.00', '', '', 'completed', '2026-01-20 14:18:18', '2026-01-20 14:18:18'),
(94, 94, 94, 12, 18, '2026-08-21 10:59:55', '2026-08-21', '11:59:55', 4, 'good', '0.00', '', '50000.00', 'Early checkout: 2 day(s) unused (50% refund)', '', 'completed', '2026-08-21 10:59:55', '2026-08-21 10:59:55'),
(95, 97, 97, 11, 19, '2026-08-21 15:31:31', '2026-08-21', '16:31:31', 4, 'good', '0.00', '', '10000.00', '', '\n\n[REFUND PAID: 2026-08-21 19:51:58 | Method: CASH | Amount: ₦10,000.00]', 'completed', '2026-08-21 15:31:31', '2026-08-21 18:51:58'),
(96, 96, 96, 1, 17, '2026-08-21 15:49:38', '2026-08-21', '16:49:38', 4, 'good', '0.00', NULL, '0.00', 'cccccccccccccccccc', NULL, 'cancelled', '2026-08-21 15:49:38', '2026-08-21 15:49:38'),
(97, 98, 98, 8, 19, '2026-08-21 15:58:15', '2026-08-21', '16:58:15', 4, 'good', '30000.00', 'Additional charges applied', '0.00', 'Early checkout: 1 day(s) unused (50% refund)', '', 'completed', '2026-08-21 15:58:15', '2026-08-21 15:58:15'),
(98, 95, 95, 6, 18, '2026-08-21 15:58:48', '2026-08-21', '16:58:48', 4, 'good', '0.00', '', '0.00', 'Early checkout: 1 day(s) unused (50% refund)', '', 'completed', '2026-08-21 15:58:48', '2026-08-21 15:58:48');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `firstname` varchar(100) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `gender` enum('male','female','other') DEFAULT 'male',
  `email` varchar(150) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `address` text,
  `has_car` tinyint(1) DEFAULT '0',
  `id_type` varchar(50) DEFAULT NULL,
  `id_number` varchar(100) DEFAULT NULL,
  `notes` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `firstname`, `lastname`, `gender`, `email`, `phone`, `created_at`, `address`, `has_car`, `id_type`, `id_number`, `notes`) VALUES
(1, 'John', 'Doe', 'male', 'john@example.com', '08012345674', '2025-12-05 18:05:21', '123 Main St, Lagos', 1, 'national_id', '12345678901', ''),
(2, 'Jane', 'Smith', 'female', 'jane@example.com', '08023456789', '2025-12-05 18:05:21', '456 Oak Ave, Abuja', 0, 'national_id', 'DL123456', ''),
(3, 'Mike', 'Johnson', 'male', 'mike@example.com', '08034567890', '2025-12-05 18:05:21', '789 Pine Rd, Port Harcourt', 1, 'national_id', 'A12345678', ''),
(4, 'Sarah', 'Williams', 'female', 'sarah@example.com', '08045678901', '2025-12-05 18:05:21', '321 Elm St, Ibadan', 1, 'national_id', 'VC987654', ''),
(5, 'queen', 'alhassan', 'female', 'qira@1337.com', '08176476423', '2025-12-05 18:17:51', '43 Beauchamp place, Oxford, OX4 3NE, United Kingdom', 1, 'national_id', '1337x1337', ''),
(6, 'shettima', 'johnson', 'male', 'shettimax@gmail.com', '08167654579', '2025-12-05 19:54:11', 'xxx', 1, 'national_id', '133c1337', ''),
(8, 'oluwafemi', 'adekolure', 'male', 'shettima2@gmail.com', '08167654573', '2025-12-06 09:55:34', '43 Beauchamp place, Oxford, OX4 3NE, United Kingdom', 1, 'national_id', '133cf1337', ''),
(9, 'ROSE', 'ADENIYI', 'female', 'rose@dick.com', '08123485543', '2025-12-08 20:53:28', 'SAMBISA FOREST, PHASE 2', 1, 'national_id', 'RS12345', ''),
(10, 'BLESSING', 'JOHNSON', 'female', 'blessing@1337.co', '08234579948', '2025-12-13 06:18:52', 'DAJIN FALGORE', 1, 'nin', '113344553', 'aunty ble'),
(11, 'TSURAMAVA', 'DANIEL', 'female', 'tsutsu@1337.co', '09126744399', '2025-12-16 14:56:32', 'zuruuuuuuuu gida', 1, 'national_id', '123456781', ''),
(12, 'LUCY', 'SHETTIMA', 'female', 'lsk@1337.co', '08122233345', '2025-12-16 19:20:30', 'BORNO, MAIDUGURI', 1, 'national_id', '13X13', 'satisfied'),
(13, 'JOHNSON', 'SEBASTIAN', 'male', 'johnsonseb@1337.co', '09182847567', '2025-12-17 18:04:32', '43 Beauchamp place, Oxford, OX4 3NE, United Kingdom', 1, 'nin', '123X133', 'my love'),
(14, 'ismail', 'adamu', 'male', 'admu@1337.co', '09865432456', '2025-12-28 09:33:47', 'hayin dogo', 1, 'national_id', '56789', '');

-- --------------------------------------------------------

--
-- Table structure for table `disputes`
--

CREATE TABLE `disputes` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('open','resolved') COLLATE utf8mb4_unicode_ci DEFAULT 'open',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `resolved_at` datetime DEFAULT NULL,
  `resolved_by` int(11) DEFAULT NULL,
  `resolution_notes` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `disputes`
--

INSERT INTO `disputes` (`id`, `booking_id`, `subject`, `description`, `status`, `created_at`, `resolved_at`, `resolved_by`, `resolution_notes`) VALUES
(1, 57, 'shey ya weyrey ni', 'bal bal nnnn anananha ahahah', 'resolved', '2025-12-16 16:51:00', '2025-12-16 16:52:24', 2, 'Allah baka hakuri'),
(2, 92, 'wysiwyg', 'lorem', 'resolved', '2026-08-21 10:45:25', '2026-08-21 15:41:57', 4, 'settled');

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `invoice_number` varchar(60) NOT NULL,
  `booking_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `issued_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `paid` tinyint(1) NOT NULL DEFAULT '0',
  `paid_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `invoice_number`, `booking_id`, `amount`, `issued_at`, `paid`, `paid_at`) VALUES
(1, 'INV1765573792536', 1, '41000.00', '2025-12-12 21:09:52', 1, '2025-12-12 21:10:55'),
(2, 'INV1765574944338', 2, '4000.00', '2025-12-12 21:29:04', 1, '2025-12-12 21:31:00'),
(3, 'INV1765580251754', 3, '120000.00', '2025-12-12 22:57:31', 1, '2025-12-13 06:09:34'),
(4, 'INV1765607389252', 4, '82000.00', '2025-12-13 06:29:49', 1, '2025-12-13 06:32:35'),
(5, 'INV1765607764186', 5, '1200.00', '2025-12-13 06:36:04', 1, '2025-12-13 06:52:54'),
(6, 'INV1765608985832', 6, '3600.00', '2025-12-13 06:56:25', 1, '2025-12-13 06:57:14'),
(7, 'INV1765609134131', 7, '42000.00', '2025-12-13 06:58:54', 1, '2025-12-13 06:59:01'),
(8, 'INV1765610312616', 8, '28000.00', '2025-12-13 07:18:32', 1, '2025-12-13 07:19:38'),
(9, 'INV1765611338782', 9, '2400.00', '2025-12-13 07:35:38', 1, '2025-12-13 07:35:47'),
(10, 'INV1765613908888', 10, '28000.00', '2025-12-13 08:18:28', 1, '2025-12-13 08:19:00'),
(11, 'INV1765614130794', 11, '7500.00', '2025-12-13 08:22:10', 1, '2025-12-13 08:23:37'),
(12, 'INV1765615267906', 12, '14000.00', '2025-12-13 08:41:07', 1, '2025-12-13 08:41:27'),
(13, 'INV1765615754102', 13, '5000.00', '2025-12-13 08:49:14', 1, '2025-12-13 08:49:23'),
(14, 'INV1765616185884', 14, '5000.00', '2025-12-13 08:56:25', 1, '2025-12-13 08:56:32'),
(15, 'INV1765616849422', 15, '82000.00', '2025-12-13 09:07:29', 1, '2025-12-13 09:07:36'),
(16, 'INV1765617050947', 16, '14000.00', '2025-12-13 09:10:50', 1, '2025-12-13 09:11:10'),
(17, 'INV1765617148957', 17, '1200.00', '2025-12-13 09:12:28', 1, '2025-12-13 09:12:33'),
(18, 'INV1765618920476', 18, '8000.00', '2025-12-13 09:42:00', 1, '2025-12-13 09:42:05'),
(19, 'INV1765619016166', 19, '1200.00', '2025-12-13 09:43:36', 1, '2025-12-13 09:43:42'),
(20, 'INV1765628233288', 20, '1200.00', '2025-12-13 12:17:13', 1, '2025-12-13 12:17:21'),
(21, 'INV1765628645774', 21, '14000.00', '2025-12-13 12:24:05', 1, '2025-12-13 12:24:13'),
(22, 'INV1765650808827', 22, '14000.00', '2025-12-13 18:33:28', 1, '2025-12-13 18:40:36'),
(23, 'INV1765651959230', 23, '1200.00', '2025-12-13 18:52:39', 1, '2025-12-13 18:53:26'),
(24, 'INV1765653422852', 24, '120000.00', '2025-12-13 19:17:02', 1, '2025-12-13 20:53:50'),
(25, 'INV1765654401824', 25, '5000.00', '2025-12-13 19:33:21', 1, '2025-12-13 19:35:04'),
(26, 'INV1765655709793', 26, '28000.00', '2025-12-13 19:55:09', 1, '2025-12-13 19:55:51'),
(27, 'INV1765660687414', 27, '14000.00', '2025-12-13 21:18:07', 1, '2025-12-13 21:18:48'),
(28, 'INV1765668349571', 28, '120000.00', '2025-12-13 23:25:49', 1, '2025-12-13 23:33:17'),
(29, 'INV1765669468188', 29, '120000.00', '2025-12-13 23:44:28', 1, '2025-12-13 23:55:01'),
(30, 'INV1765670267552', 30, '120000.00', '2025-12-13 23:57:47', 1, '2025-12-14 18:57:48'),
(31, 'INV1765707212138', 31, '60000.00', '2025-12-14 10:13:32', 1, '2025-12-14 10:13:59'),
(32, 'INV1765720931829', 32, '60000.00', '2025-12-14 14:02:11', 0, NULL),
(33, 'INV1765731068541', 33, '120000.00', '2025-12-14 16:51:08', 0, NULL),
(34, 'INV1765731430600', 34, '4800.00', '2025-12-14 16:57:10', 1, '2025-12-14 16:59:45'),
(35, 'INV1765731973164', 35, '1200.00', '2025-12-14 17:06:13', 1, '2025-12-14 17:28:23'),
(36, 'INV1765795595721', 36, '60000.00', '2025-12-15 10:46:35', 1, '2025-12-15 10:49:24'),
(37, 'INV1765795791981', 37, '60000.00', '2025-12-15 10:49:51', 1, '2025-12-15 13:43:40'),
(38, 'INV1765805943595', 38, '60000.00', '2025-12-15 13:39:03', 0, NULL),
(39, 'INV1765807501280', 39, '60000.00', '2025-12-15 14:05:01', 1, '2025-12-15 14:06:01'),
(40, 'INV1765811560108', 40, '60000.00', '2025-12-15 15:12:40', 1, '2025-12-15 15:21:56'),
(41, 'INV1765812391522', 41, '120000.00', '2025-12-15 15:26:31', 1, '2025-12-15 15:29:00'),
(42, 'INV1765812651335', 42, '120000.00', '2025-12-15 15:30:51', 1, '2025-12-15 15:45:51'),
(43, 'INV1765818037449', 43, '120000.00', '2025-12-15 17:00:37', 1, '2025-12-15 17:01:32'),
(44, 'INV1765818715332', 44, '60000.00', '2025-12-15 17:11:55', 1, '2025-12-15 17:13:16'),
(45, 'INV1765818904349', 45, '60000.00', '2025-12-15 17:15:04', 1, '2025-12-15 17:15:29'),
(46, 'INV1765819975394', 46, '60000.00', '2025-12-15 17:32:55', 1, '2025-12-15 17:33:04'),
(47, 'INV1765823934502', 47, '120000.00', '2025-12-15 18:38:54', 1, '2025-12-15 18:40:04'),
(48, 'INV1765828661174', 48, '120000.00', '2025-12-15 19:57:41', 1, '2025-12-16 16:49:29'),
(49, 'INV1765877992573', 49, '82000.00', '2025-12-16 09:39:52', 0, NULL),
(50, 'INV1765887823191', 50, '120000.00', '2025-12-16 12:23:43', 1, '2025-12-16 12:28:46'),
(51, 'INV1765889810350', 51, '120000.00', '2025-12-16 12:56:50', 1, '2025-12-16 12:57:38'),
(52, 'INV1765889885327', 52, '120000.00', '2025-12-16 12:58:05', 1, '2025-12-16 12:58:18'),
(53, 'INV1765890258713', 53, '120000.00', '2025-12-16 13:04:18', 1, '2025-12-16 13:04:40'),
(54, 'INV1765892083267', 54, '82000.00', '2025-12-16 13:34:43', 1, '2025-12-16 13:34:59'),
(55, 'INV1765893323545', 55, '12000.00', '2025-12-16 13:55:23', 1, '2025-12-16 13:55:47'),
(56, 'INV1765894402403', 56, '41000.00', '2025-12-16 14:13:22', 1, '2025-12-16 14:13:31'),
(57, 'INV1765895123927', 57, '41000.00', '2025-12-16 14:25:23', 1, '2025-12-16 14:25:39'),
(58, 'INV1765903484516', 58, '120000.00', '2025-12-16 16:44:44', 1, '2025-12-16 16:45:38'),
(59, 'INV1765904550439', 59, '2400.00', '2025-12-16 17:02:30', 1, '2025-12-16 17:02:37'),
(60, 'INV1765912854519', 60, '2400.00', '2025-12-16 19:20:54', 1, '2025-12-16 19:22:58'),
(61, 'INV1765914165431', 61, '2400.00', '2025-12-16 19:42:45', 1, '2025-12-16 19:43:40'),
(62, 'INV1765914533131', 62, '28000.00', '2025-12-16 19:48:53', 1, '2025-12-16 19:49:01'),
(63, 'INV1765914840805', 63, '28000.00', '2025-12-16 19:54:00', 1, '2025-12-16 21:55:00'),
(64, 'INV1765914998317', 64, '28000.00', '2025-12-16 19:56:38', 1, '2025-12-16 21:51:12'),
(65, 'INV1765915435935', 65, '5000.00', '2025-12-16 20:03:55', 1, '2025-12-16 20:14:39'),
(66, 'INV1765916327897', 66, '28000.00', '2025-12-16 20:18:47', 1, '2025-12-16 20:18:54'),
(67, 'INV1765920104272', 67, '5000.00', '2025-12-16 21:21:44', 1, '2025-12-16 21:21:49'),
(68, 'INV1765921377795', 68, '5000.00', '2025-12-16 21:42:57', 1, '2025-12-16 21:44:08'),
(69, 'INV1765958570570', 69, '82000.00', '2025-12-17 08:02:50', 1, '2025-12-17 12:00:22'),
(70, 'INV1765973481158', 70, '120000.00', '2025-12-17 12:11:21', 1, '2025-12-17 12:12:16'),
(71, 'INV1765973900774', 71, '190000.00', '2025-12-17 12:18:20', 1, '2025-12-17 12:19:00'),
(72, 'INV1765974266337', 72, '60000.00', '2025-12-17 12:24:26', 1, '2025-12-17 12:33:24'),
(73, 'INV1765984623283', 73, '60000.00', '2025-12-17 15:17:03', 1, '2025-12-17 15:17:16'),
(74, 'INV1765985047799', 74, '60000.00', '2025-12-17 15:24:07', 1, '2025-12-17 15:24:12'),
(75, 'INV1765986087676', 75, '120000.00', '2025-12-17 15:41:27', 0, NULL),
(76, 'INV1765986329851', 76, '60000.00', '2025-12-17 15:45:29', 0, NULL),
(77, 'INV1765986674775', 77, '60000.00', '2025-12-17 15:51:14', 0, NULL),
(78, 'INV1765986943811', 78, '60000.00', '2025-12-17 15:55:43', 0, NULL),
(79, 'INV1765992231388', 79, '120000.00', '2025-12-17 17:23:51', 1, '2025-12-17 17:24:56'),
(80, 'INV1765992860242', 80, '60000.00', '2025-12-17 17:34:20', 1, '2025-12-17 17:34:41'),
(81, 'INV1765994112952', 81, '60000.00', '2025-12-17 17:55:12', 1, '2025-12-17 17:56:41'),
(82, 'INV1766050264457', 82, '60000.00', '2025-12-18 09:31:04', 1, '2025-12-18 09:31:08'),
(83, 'INV1766051681576', 83, '120000.00', '2025-12-18 09:54:41', 1, '2025-12-18 09:54:47'),
(84, 'INV1766056782968', 84, '120000.00', '2025-12-18 11:19:42', 1, '2025-12-18 11:19:47'),
(85, 'INV1766056868684', 85, '1200.00', '2025-12-18 11:21:08', 1, '2025-12-18 11:21:16'),
(86, 'INV1766057537784', 86, '82000.00', '2025-12-18 11:32:18', 1, '2025-12-18 11:32:55'),
(87, 'INV1766057652715', 87, '2400.00', '2025-12-18 11:34:12', 1, '2025-12-18 11:34:21'),
(88, 'INV1766058775397', 88, '1200.00', '2025-12-18 11:52:55', 1, '2025-12-18 11:53:01'),
(89, 'INV1766058823983', 89, '28000.00', '2025-12-18 11:53:43', 1, '2025-12-18 11:53:50'),
(90, 'INV1766238087823', 90, '1200.00', '2025-12-20 13:41:27', 1, '2025-12-20 13:41:50'),
(91, 'INV1766238211195', 91, '1200.00', '2025-12-20 13:43:31', 1, '2025-12-20 13:43:39'),
(92, 'INV1766914450112', 92, '180000.00', '2025-12-28 09:34:10', 1, '2025-12-28 09:37:29'),
(93, 'INV1766915486909', 93, '120000.00', '2025-12-28 09:51:26', 1, '2025-12-28 09:51:33'),
(94, 'INV1787308541641', 94, '120000.00', '2026-08-21 10:35:41', 1, '2026-08-21 10:37:01'),
(95, 'INV1787321667489', 95, '120000.00', '2026-08-21 14:14:27', 1, '2026-08-21 14:18:55'),
(96, 'INV1787325441680', 96, '60000.00', '2026-08-21 15:17:21', 1, '2026-08-21 15:51:06'),
(97, 'INV1787326170135', 97, '60000.00', '2026-08-21 15:29:30', 1, '2026-08-21 15:29:37'),
(98, 'INV1787327703128', 98, '150000.00', '2026-08-21 15:55:03', 1, '2026-08-21 15:56:11');

-- --------------------------------------------------------

--
-- Table structure for table `page_permissions`
--

CREATE TABLE `page_permissions` (
  `id` int(11) NOT NULL,
  `page` varchar(100) NOT NULL COMMENT 'e.g., admin_users.php',
  `permission_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `page_permissions`
--

INSERT INTO `page_permissions` (`id`, `page`, `permission_id`, `created_at`) VALUES
(1, 'admin_users.php', 1, '2026-08-22 08:42:12'),
(2, 'admin_logs.php', 3, '2026-08-22 08:42:12'),
(3, 'admin_reports.php', 4, '2026-08-22 08:42:12'),
(4, 'admin_settings.php', 2, '2026-08-22 08:42:12'),
(5, 'admin_manage_rooms.php', 14, '2026-08-22 08:42:12'),
(6, 'admin_manage_blocks.php', 15, '2026-08-22 08:42:12'),
(7, 'admin_staff.php', 16, '2026-08-22 08:42:12'),
(8, 'admin_checkin.php', 7, '2026-08-22 08:42:12'),
(9, 'admin_checkout.php', 8, '2026-08-22 08:42:12'),
(10, 'admin_cancellation.php', 6, '2026-08-22 08:42:12'),
(11, 'admin_extend_stay.php', 9, '2026-08-22 08:42:12'),
(12, 'admin_mark_paid.php', 11, '2026-08-22 08:42:12'),
(13, 'admin_refunds.php', 12, '2026-08-22 08:42:12'),
(14, 'admin_dispute.php', 13, '2026-08-22 08:42:12'),
(15, 'admin_resolve_dispute.php', 13, '2026-08-22 08:42:12');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `invoice_id` bigint(20) UNSIGNED NOT NULL,
  `txn_ref` varchar(120) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `method` varchar(50) NOT NULL DEFAULT 'manual',
  `notes` varchar(4000) DEFAULT NULL,
  `status` enum('initiated','completed','failed') NOT NULL DEFAULT 'initiated',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `invoice_id`, `txn_ref`, `amount`, `method`, `notes`, `status`, `created_at`) VALUES
(1, 1, 'TXN1765573792461', '41000.00', 'manual', NULL, 'completed', '2025-12-12 21:09:52'),
(2, 2, 'TXN1765574944546', '4000.00', 'manual', NULL, 'completed', '2025-12-12 21:29:04'),
(3, 3, 'TXN1765580251588', '120000.00', 'manual', NULL, 'completed', '2025-12-12 22:57:31'),
(4, 4, 'TXN1765607389927', '82000.00', 'manual', NULL, 'completed', '2025-12-13 06:29:49'),
(5, 5, 'TXN1765607764337', '1200.00', 'manual', NULL, 'completed', '2025-12-13 06:36:04'),
(6, 6, 'TXN1765608985695', '3600.00', 'manual', NULL, 'completed', '2025-12-13 06:56:25'),
(7, 7, 'TXN1765609134175', '42000.00', 'manual', NULL, 'completed', '2025-12-13 06:58:54'),
(8, 8, 'TXN1765610312909', '28000.00', 'manual', NULL, 'completed', '2025-12-13 07:18:32'),
(9, 9, 'TXN1765611338530', '2400.00', 'manual', NULL, 'completed', '2025-12-13 07:35:38'),
(10, 10, 'TXN1765613908979', '28000.00', 'manual', NULL, 'completed', '2025-12-13 08:18:28'),
(11, 11, 'TXN1765614130101', '5000.00', 'manual', NULL, 'completed', '2025-12-13 08:22:10'),
(12, 12, 'TXN1765615267228', '14000.00', 'manual', NULL, 'completed', '2025-12-13 08:41:07'),
(13, 13, 'TXN1765615754154', '5000.00', 'manual', NULL, 'completed', '2025-12-13 08:49:14'),
(14, 14, 'TXN1765616185744', '5000.00', 'manual', NULL, 'completed', '2025-12-13 08:56:25'),
(15, 15, 'TXN1765616849341', '82000.00', 'manual', NULL, 'completed', '2025-12-13 09:07:29'),
(16, 16, 'TXN1765617050248', '14000.00', 'manual', NULL, 'completed', '2025-12-13 09:10:50'),
(17, 17, 'TXN1765617148329', '1200.00', 'manual', NULL, 'completed', '2025-12-13 09:12:28'),
(18, 18, 'TXN1765618920227', '8000.00', 'manual', NULL, 'completed', '2025-12-13 09:42:00'),
(19, 19, 'TXN1765619016693', '1200.00', 'manual', NULL, 'completed', '2025-12-13 09:43:36'),
(20, 20, 'TXN1765628233988', '1200.00', 'manual', NULL, 'completed', '2025-12-13 12:17:13'),
(21, 21, 'TXN1765628645484', '14000.00', 'manual', NULL, 'completed', '2025-12-13 12:24:05'),
(22, 17, 'REFUND1765643502652', '-600.00', 'refund_cash', NULL, 'completed', '2025-12-13 16:31:42'),
(23, 22, 'TXN1765650808585', '14000.00', 'manual', NULL, 'initiated', '2025-12-13 18:33:28'),
(24, 22, NULL, '14000.00', 'card', '', 'completed', '2025-12-13 18:40:36'),
(25, 23, 'TXN1765651959574', '1200.00', 'manual', NULL, 'completed', '2025-12-13 18:52:39'),
(26, 24, 'TXN1765653422387', '120000.00', 'manual', NULL, 'completed', '2025-12-13 19:17:02'),
(27, 25, 'TXN1765654401477', '5000.00', 'manual', NULL, 'completed', '2025-12-13 19:33:21'),
(28, 26, 'TXN1765655709403', '28000.00', 'manual', NULL, 'completed', '2025-12-13 19:55:09'),
(29, 27, 'TXN1765660687409', '14000.00', 'manual', NULL, 'completed', '2025-12-13 21:18:07'),
(30, 28, 'TXN1765668349592', '120000.00', 'manual', NULL, 'completed', '2025-12-13 23:25:49'),
(31, 29, 'TXN1765669468718', '120000.00', 'manual', NULL, 'completed', '2025-12-13 23:44:28'),
(32, 30, 'TXN1765670267927', '120000.00', 'manual', NULL, 'completed', '2025-12-13 23:57:47'),
(33, 31, 'TXN1765707212960', '60000.00', 'manual', NULL, 'completed', '2025-12-14 10:13:32'),
(34, 32, 'TXN1765720931789', '60000.00', 'manual', NULL, 'initiated', '2025-12-14 14:02:11'),
(35, 33, 'TXN1765731068883', '120000.00', 'manual', NULL, 'initiated', '2025-12-14 16:51:08'),
(36, 34, 'TXN1765731430740', '4800.00', 'manual', NULL, 'completed', '2025-12-14 16:57:10'),
(37, 35, 'TXN1765731973492', '1200.00', 'manual', NULL, 'completed', '2025-12-14 17:06:13'),
(38, 36, 'TXN1765795595982', '60000.00', 'manual', NULL, 'completed', '2025-12-15 10:46:35'),
(39, 37, 'TXN1765795791102', '60000.00', 'manual', NULL, 'completed', '2025-12-15 10:49:51'),
(40, 38, 'TXN1765805943140', '60000.00', 'manual', NULL, 'initiated', '2025-12-15 13:39:03'),
(41, 39, 'TXN1765807501483', '60000.00', 'manual', NULL, 'completed', '2025-12-15 14:05:01'),
(42, 11, 'LATE1765811060', '2500.00', 'late_checkout', NULL, 'completed', '2025-12-15 15:04:20'),
(43, 40, 'TXN1765811560556', '60000.00', 'manual', NULL, 'completed', '2025-12-15 15:12:40'),
(44, 41, 'TXN1765812391436', '120000.00', 'manual', NULL, 'completed', '2025-12-15 15:26:31'),
(45, 42, 'TXN1765812651159', '120000.00', 'manual', NULL, 'completed', '2025-12-15 15:30:51'),
(46, 43, 'TXN1765818037687', '120000.00', 'manual', NULL, 'completed', '2025-12-15 17:00:37'),
(47, 44, 'TXN1765818715607', '60000.00', 'manual', NULL, 'completed', '2025-12-15 17:11:55'),
(48, 45, 'TXN1765818904714', '60000.00', 'manual', NULL, 'completed', '2025-12-15 17:15:04'),
(49, 46, 'TXN1765819975725', '60000.00', 'manual', NULL, 'completed', '2025-12-15 17:32:55'),
(50, 47, 'TXN1765823934181', '120000.00', 'manual', NULL, 'completed', '2025-12-15 18:38:54'),
(51, 48, 'TXN1765828661640', '120000.00', 'manual', NULL, 'completed', '2025-12-15 19:57:41'),
(52, 49, 'TXN1765877992387', '82000.00', 'manual', NULL, 'initiated', '2025-12-16 09:39:52'),
(53, 50, 'TXN1765887823921', '120000.00', 'manual', NULL, 'completed', '2025-12-16 12:23:43'),
(54, 51, 'TXN1765889810872', '120000.00', 'manual', NULL, 'completed', '2025-12-16 12:56:50'),
(55, 52, 'TXN1765889885265', '120000.00', 'manual', NULL, 'completed', '2025-12-16 12:58:05'),
(56, 53, 'TXN1765890258157', '60000.00', 'manual', NULL, 'completed', '2025-12-16 13:04:18'),
(57, 54, 'TXN1765892083754', '82000.00', 'manual', NULL, 'completed', '2025-12-16 13:34:43'),
(58, 55, 'TXN1765893323786', '4000.00', 'manual', NULL, 'completed', '2025-12-16 13:55:23'),
(59, 56, 'TXN1765894402503', '41000.00', 'manual', NULL, 'completed', '2025-12-16 14:13:22'),
(60, 53, 'EXT1765894459595', '60000.00', 'cash', NULL, 'completed', '2025-12-16 14:14:19'),
(61, 55, 'EXT1765894535702', '4000.00', 'cash', NULL, 'completed', '2025-12-16 14:15:35'),
(62, 55, 'EXT1765894539456', '4000.00', 'cash', NULL, 'completed', '2025-12-16 14:15:39'),
(63, 57, 'TXN1765895123291', '41000.00', 'manual', NULL, 'completed', '2025-12-16 14:25:23'),
(64, 58, 'TXN1765903484942', '60000.00', 'manual', NULL, 'completed', '2025-12-16 16:44:44'),
(65, 59, 'TXN1765904550512', '1200.00', 'manual', NULL, 'completed', '2025-12-16 17:02:30'),
(66, 60, 'TXN1765912854715', '1200.00', 'manual', NULL, 'completed', '2025-12-16 19:20:54'),
(67, 61, 'TXN1765914165696', '1200.00', 'manual', NULL, 'completed', '2025-12-16 19:42:45'),
(68, 62, 'TXN1765914533466', '14000.00', 'manual', NULL, 'completed', '2025-12-16 19:48:53'),
(69, 63, 'TXN1765914840682', '28000.00', 'manual', NULL, 'completed', '2025-12-16 19:54:00'),
(70, 64, 'TXN1765914998388', '14000.00', 'manual', NULL, 'completed', '2025-12-16 19:56:38'),
(71, 65, 'TXN1765915435119', '2500.00', 'manual', NULL, 'completed', '2025-12-16 20:03:55'),
(72, 66, 'TXN1765916327226', '14000.00', 'manual', NULL, 'completed', '2025-12-16 20:18:47'),
(73, 58, 'EXT1765918191879', '60000.00', 'cash', NULL, 'completed', '2025-12-16 20:49:51'),
(74, 59, 'EXT1765918310493', '1200.00', 'cash', NULL, 'completed', '2025-12-16 20:51:50'),
(75, 60, 'EXT1765919339772', '1200.00', 'cash', NULL, 'completed', '2025-12-16 21:08:59'),
(76, 61, 'EXT1765919717821', '1200.00', 'cash', NULL, 'completed', '2025-12-16 21:15:17'),
(77, 67, 'TXN1765920104722', '2500.00', 'manual', NULL, 'completed', '2025-12-16 21:21:44'),
(78, 62, 'EXT1765920180529', '14000.00', 'cash', NULL, 'completed', '2025-12-16 21:23:00'),
(79, 65, 'EXT1765920567535', '2500.00', 'cash', NULL, 'completed', '2025-12-16 21:29:27'),
(80, 66, 'EXT1765920811845', '14000.00', 'cash', NULL, 'completed', '2025-12-16 21:33:31'),
(81, 67, 'EXT1765920881423', '2500.00', 'cash', NULL, 'completed', '2025-12-16 21:34:41'),
(82, 68, 'TXN1765921377868', '2500.00', 'manual', NULL, 'completed', '2025-12-16 21:42:57'),
(83, 68, 'EXT1765921491383', '2500.00', 'cash', NULL, 'completed', '2025-12-16 21:44:51'),
(84, 64, 'EXT1765921917957', '14000.00', 'cash', NULL, 'completed', '2025-12-16 21:51:57'),
(85, 69, 'TXN1765958570697', '41000.00', 'manual', NULL, 'completed', '2025-12-17 08:02:50'),
(86, 69, 'EXT1765973301214', '41000.00', 'cash', NULL, 'completed', '2025-12-17 12:08:21'),
(87, 70, 'TXN1765973481286', '60000.00', 'manual', NULL, 'completed', '2025-12-17 12:11:21'),
(88, 71, 'TXN1765973900673', '120000.00', 'manual', NULL, 'completed', '2025-12-17 12:18:20'),
(89, 72, 'TXN1765974266372', '60000.00', 'manual', NULL, 'completed', '2025-12-17 12:24:26'),
(90, 56, 'REFUND1765981001620', '-20000.00', 'refund_pos', NULL, 'completed', '2025-12-17 14:16:41'),
(91, 35, 'REFUND1765981947236', '-300.00', 'refund_cash', NULL, 'completed', '2025-12-17 14:32:27'),
(92, 23, 'REFUND1765982300481', '-600.00', 'refund_transfer', NULL, 'completed', '2025-12-17 14:38:20'),
(93, 5, 'REFUND1765982888645', '-300.00', 'refund_cash', NULL, 'completed', '2025-12-17 14:48:08'),
(94, 73, 'TXN1765984623341', '60000.00', 'manual', NULL, 'completed', '2025-12-17 15:17:03'),
(95, 70, 'EXT1765985005245', '60000.00', 'cash', NULL, 'completed', '2025-12-17 15:23:25'),
(96, 74, 'TXN1765985047836', '60000.00', 'manual', NULL, 'completed', '2025-12-17 15:24:07'),
(97, 74, 'REFUND1765985159169', '-15000.00', 'refund_cash', NULL, 'completed', '2025-12-17 15:25:59'),
(98, 59, 'REFUND1765985846348', '-2000.00', 'refund_cash', NULL, 'completed', '2025-12-17 15:37:26'),
(99, 75, 'TXN1765986087935', '120000.00', 'manual', NULL, 'initiated', '2025-12-17 15:41:27'),
(100, 76, 'TXN1765986329951', '60000.00', 'manual', NULL, 'initiated', '2025-12-17 15:45:29'),
(101, 77, 'TXN1765986674970', '60000.00', 'manual', NULL, 'initiated', '2025-12-17 15:51:14'),
(102, 78, 'TXN1765986943429', '60000.00', 'manual', NULL, 'initiated', '2025-12-17 15:55:43'),
(103, 79, 'TXN1765992231121', '120000.00', 'manual', NULL, 'completed', '2025-12-17 17:23:51'),
(104, 80, 'TXN1765992860242', '60000.00', 'manual', NULL, 'completed', '2025-12-17 17:34:20'),
(105, 81, 'TXN1765994112794', '60000.00', 'manual', NULL, 'completed', '2025-12-17 17:55:12'),
(106, 82, 'TXN1766050264233', '60000.00', 'manual', NULL, 'completed', '2025-12-18 09:31:04'),
(107, 83, 'TXN1766051681155', '120000.00', 'manual', NULL, 'completed', '2025-12-18 09:54:41'),
(108, 84, 'TXN1766056782175', '120000.00', 'manual', NULL, 'completed', '2025-12-18 11:19:42'),
(109, 85, 'TXN1766056868348', '1200.00', 'manual', NULL, 'completed', '2025-12-18 11:21:08'),
(110, 86, 'TXN1766057538750', '82000.00', 'manual', NULL, 'completed', '2025-12-18 11:32:18'),
(111, 87, 'TXN1766057652496', '2400.00', 'manual', NULL, 'completed', '2025-12-18 11:34:12'),
(112, 88, 'TXN1766058775214', '1200.00', 'manual', NULL, 'completed', '2025-12-18 11:52:55'),
(113, 89, 'TXN1766058823217', '14000.00', 'manual', NULL, 'completed', '2025-12-18 11:53:43'),
(114, 89, 'LATE1766153936', '14000.00', 'late_checkout', NULL, 'completed', '2025-12-19 14:18:56'),
(115, 71, 'LATE1766153960', '70000.00', 'late_checkout', NULL, 'completed', '2025-12-19 14:19:20'),
(116, 79, 'REFUND1766237935331', '-30000.00', 'refund_pos', NULL, 'completed', '2025-12-20 13:38:55'),
(117, 90, 'TXN1766238087492', '1200.00', 'manual', NULL, 'completed', '2025-12-20 13:41:27'),
(118, 91, 'TXN1766238211794', '1200.00', 'manual', NULL, 'completed', '2025-12-20 13:43:31'),
(119, 92, 'TXN1766914450192', '60000.00', 'manual', NULL, 'completed', '2025-12-28 09:34:10'),
(120, 92, 'EXT1766915069191', '120000.00', 'cash', NULL, 'completed', '2025-12-28 09:44:29'),
(121, 93, 'TXN1766915486353', '120000.00', 'manual', NULL, 'completed', '2025-12-28 09:51:26'),
(122, 94, 'TXN1787308541705', '120000.00', 'manual', NULL, 'completed', '2026-08-21 10:35:41'),
(123, 90, 'REFUND1787309632273', '-300.00', 'refund_cash', NULL, 'completed', '2026-08-21 10:53:52'),
(124, 95, 'TXN1787321667673', '60000.00', 'manual', NULL, 'completed', '2026-08-21 14:14:27'),
(125, 95, 'EXT1787323170933', '60000.00', 'cash', NULL, 'completed', '2026-08-21 14:39:30'),
(126, 96, 'TXN1787325441985', '60000.00', 'manual', NULL, 'completed', '2026-08-21 15:17:21'),
(127, 97, 'TXN1787326170293', '60000.00', 'manual', NULL, 'completed', '2026-08-21 15:29:30'),
(128, 98, 'TXN1787327703865', '120000.00', 'manual', NULL, 'completed', '2026-08-21 15:55:03'),
(129, 98, 'LATE1787327895', '30000.00', 'late_checkout', NULL, 'completed', '2026-08-21 15:58:15'),
(130, 97, 'REFUND1787338318325', '-10000.00', 'refund_cash', NULL, 'completed', '2026-08-21 18:51:58');

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL COMMENT 'e.g., manage_users, view_reports',
  `display_name` varchar(100) NOT NULL COMMENT 'e.g., Manage Users',
  `category` varchar(50) DEFAULT NULL COMMENT 'e.g., System, Finance, Front Desk',
  `description` text,
  `is_system` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `display_name`, `category`, `description`, `is_system`, `created_at`) VALUES
(1, 'manage_users', 'Manage Users', 'System', 'Create, edit, delete admin users', 1, '2026-08-22 08:42:12'),
(2, 'manage_settings', 'Manage Settings', 'System', 'Change hotel settings and configuration', 1, '2026-08-22 08:42:12'),
(3, 'view_logs', 'View Activity Logs', 'System', 'View admin activity logs', 1, '2026-08-22 08:42:12'),
(4, 'view_reports', 'View Reports', 'System', 'Access all reports', 1, '2026-08-22 08:42:12'),
(5, 'front_desk', 'Front Desk Operations', 'Front Desk', 'Checkin, checkout, reservations', 1, '2026-08-22 08:42:12'),
(6, 'manage_bookings', 'Manage Bookings', 'Front Desk', 'Create, edit, cancel bookings', 1, '2026-08-22 08:42:12'),
(7, 'checkin_guest', 'Check-in Guests', 'Front Desk', 'Process guest check-in', 1, '2026-08-22 08:42:12'),
(8, 'checkout_guest', 'Check-out Guests', 'Front Desk', 'Process guest check-out', 1, '2026-08-22 08:42:12'),
(9, 'extend_stay', 'Extend Stay', 'Front Desk', 'Extend guest reservations', 1, '2026-08-22 08:42:12'),
(10, 'finance', 'Finance Operations', 'Finance', 'Invoices, payments, refunds', 1, '2026-08-22 08:42:12'),
(11, 'mark_paid', 'Mark Invoice Paid', 'Finance', 'Mark invoices as paid', 1, '2026-08-22 08:42:12'),
(12, 'process_refunds', 'Process Refunds', 'Finance', 'Issue refunds', 1, '2026-08-22 08:42:12'),
(13, 'manage_disputes', 'Manage Disputes', 'Finance', 'Resolve customer disputes', 1, '2026-08-22 08:42:12'),
(14, 'manage_rooms', 'Manage Rooms', 'Property', 'Add, edit, delete rooms', 1, '2026-08-22 08:42:12'),
(15, 'manage_buildings', 'Manage Buildings', 'Property', 'Add, edit, delete buildings', 1, '2026-08-22 08:42:12'),
(16, 'manage_staff', 'Manage Staff', 'Staff', 'Manage staff members', 1, '2026-08-22 08:42:12'),
(17, 'view_attendance', 'View Attendance', 'Staff', 'View staff attendance', 1, '2026-08-22 08:42:12'),
(18, 'manage_customers', 'Manage Customers', 'Customers', 'Manage customer profiles', 1, '2026-08-22 08:42:12');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL COMMENT 'e.g., super_admin, manager, receptionist',
  `display_name` varchar(100) NOT NULL COMMENT 'e.g., Super Administrator',
  `description` text,
  `is_system` tinyint(1) DEFAULT '0' COMMENT '1 = cannot be deleted',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `display_name`, `description`, `is_system`, `created_at`) VALUES
(1, 'super_admin', 'Super Administrator', 'Full system access - manages everything', 1, '2026-08-22 08:42:12'),
(2, 'manager', 'Hotel Manager', 'Manages operations, staff, and reports', 1, '2026-08-22 08:42:12'),
(3, 'receptionist', 'Receptionist', 'Front desk operations - checkin/checkout', 1, '2026-08-22 08:42:12'),
(4, 'security', 'Security Staff', 'Limited access - viewing only', 1, '2026-08-22 08:42:12'),
(5, 'housekeeping', 'Housekeeping', 'Room status management', 0, '2026-08-22 08:42:12'),
(6, 'accountant', 'Accountant', 'Financial access only', 0, '2026-08-22 08:42:12');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`id`, `role_id`, `permission_id`, `created_at`) VALUES
(1, 1, 18, '2026-08-22 08:45:41'),
(2, 1, 10, '2026-08-22 08:53:58'),
(3, 1, 13, '2026-08-22 09:20:40'),
(7, 1, 11, '2026-08-22 09:26:38'),
(8, 1, 12, '2026-08-22 09:26:38'),
(9, 1, 7, '2026-08-22 09:26:38'),
(10, 1, 8, '2026-08-22 09:26:38'),
(11, 1, 9, '2026-08-22 09:26:38'),
(12, 1, 5, '2026-08-22 09:26:38'),
(13, 1, 6, '2026-08-22 09:26:39'),
(14, 1, 15, '2026-08-22 09:26:39'),
(15, 1, 14, '2026-08-22 09:26:39'),
(16, 1, 16, '2026-08-22 09:26:39'),
(17, 1, 17, '2026-08-22 09:26:39'),
(18, 1, 2, '2026-08-22 09:26:39'),
(19, 1, 1, '2026-08-22 09:26:39'),
(20, 1, 3, '2026-08-22 09:26:39'),
(21, 1, 4, '2026-08-22 09:26:39'),
(22, 3, 11, '2026-08-22 10:17:36'),
(23, 3, 5, '2026-08-22 10:17:36'),
(24, 3, 7, '2026-08-22 21:01:36'),
(25, 3, 8, '2026-08-22 21:01:36'),
(26, 3, 9, '2026-08-22 21:01:36'),
(27, 2, 15, '2026-08-22 21:06:23'),
(28, 2, 14, '2026-08-22 21:06:23'),
(29, 2, 16, '2026-08-22 21:06:23'),
(30, 2, 17, '2026-08-22 21:06:23'),
(31, 2, 2, '2026-08-22 21:06:23'),
(32, 2, 1, '2026-08-22 21:06:23'),
(33, 2, 3, '2026-08-22 21:06:23'),
(34, 2, 4, '2026-08-22 21:06:23'),
(35, 6, 10, '2026-08-22 21:07:19'),
(36, 6, 13, '2026-08-22 21:07:19'),
(37, 6, 11, '2026-08-22 21:07:19'),
(38, 6, 12, '2026-08-22 21:07:19'),
(39, 6, 3, '2026-08-22 21:07:19'),
(40, 6, 4, '2026-08-22 21:07:19');

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(10) UNSIGNED NOT NULL,
  `building_id` int(10) UNSIGNED NOT NULL,
  `room_number` varchar(50) NOT NULL,
  `capacity` int(11) NOT NULL DEFAULT '1',
  `status` enum('available','booked','maintenance') DEFAULT 'available',
  `price_per_night` decimal(10,2) NOT NULL DEFAULT '0.00',
  `use_system_price` tinyint(1) DEFAULT '1',
  `manual_price` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='rooms information';

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `building_id`, `room_number`, `capacity`, `status`, `price_per_night`, `use_system_price`, `manual_price`, `created_at`) VALUES
(1, 1, '101', 2, 'available', '14000.00', 1, '1000.00', '2025-12-10 17:41:38'),
(2, 1, '102', 2, 'available', '14000.00', 1, NULL, '2025-12-05 18:05:21'),
(3, 1, '103', 3, 'available', '14000.00', 1, NULL, '2025-12-05 18:05:21'),
(4, 1, '104', 2, 'available', '14000.00', 1, NULL, '2025-12-05 18:05:21'),
(5, 2, '201', 2, 'available', '2500.00', 1, NULL, '2025-12-05 18:05:21'),
(6, 2, '202', 2, 'available', '2500.00', 1, NULL, '2025-12-05 18:05:21'),
(7, 2, '203', 3, 'available', '2500.00', 1, NULL, '2025-12-05 18:05:21'),
(8, 3, '301', 2, 'available', '41000.00', 0, '41000.00', '2025-12-09 19:23:13'),
(9, 3, '302', 3, 'available', '4000.00', 0, '4000.00', '2025-12-08 03:23:07'),
(10, 3, '303', 2, 'available', '41000.00', 1, NULL, '2025-12-06 15:18:51'),
(11, 4, '401', 2, 'available', '1200.00', 1, NULL, '2025-12-05 18:05:21'),
(12, 4, '402', 2, 'available', '1200.00', 1, NULL, '2025-12-05 18:05:21'),
(13, 4, '403', 3, 'available', '1200.00', 1, NULL, '2025-12-05 18:05:21'),
(14, 5, '101Ac', 2, 'available', '60000.00', 1, '0.00', '2026-08-21 13:31:39'),
(15, 5, '1337', 3, 'available', '60000.00', 1, NULL, '2025-12-13 21:53:01'),
(16, 5, '1337x', 3, 'available', '60000.00', 1, '0.00', '2025-12-17 12:01:24'),
(17, 5, '137', 1, 'available', '60000.00', 1, '0.00', '2025-12-13 22:23:15'),
(18, 5, '123', 1, 'available', '60000.00', 1, '0.00', '2025-12-13 22:35:27'),
(19, 5, '333', 1, 'available', '60000.00', 1, '0.00', '2025-12-14 14:04:55'),
(20, 5, '666', 1, 'available', '60000.00', 1, '0.00', '2025-12-17 12:22:42'),
(21, 7, '600', 1, 'available', '20000.00', 0, '20000.00', '2025-12-28 09:30:27');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `id` int(11) NOT NULL,
  `pnumber` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `department` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'General',
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`id`, `pnumber`, `name`, `department`, `phone`, `password_hash`, `photo`, `status`, `created_at`, `created_by`) VALUES
(1, 'GE001', 'JOHNSON SHETTIMA', 'General', '08176976923', NULL, 'uploads/staff/.png', 'active', '2025-12-19 15:22:01', 2),
(2, 'SE001', 'ADMIN ADMINS', 'Security', '09013371337', NULL, 'img/staff/.png', 'active', '2025-12-19 15:25:40', 2),
(3, 'HO001', 'EMMANUEL JOHNSON', 'Housekeeping', '09333727234', NULL, 'img/staff/.jpg', 'active', '2025-12-19 16:10:08', 2),
(4, 'GE002', 'AARON JOHNSON', 'General', '09188877763', NULL, 'img/staff/GE002.jpeg', 'active', '2025-12-20 13:28:26', 1);

-- --------------------------------------------------------

--
-- Table structure for table `staff_attendance`
--

CREATE TABLE `staff_attendance` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `clock_in` datetime DEFAULT NULL,
  `clock_out` datetime DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `recorded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `staff_attendance`
--

INSERT INTO `staff_attendance` (`id`, `staff_id`, `date`, `clock_in`, `clock_out`, `notes`, `recorded_by`, `created_at`) VALUES
(1, 1, '2025-12-19', '2025-12-19 17:11:44', NULL, NULL, 2, '2025-12-19 15:22:26'),
(2, 2, '2025-12-19', '2025-12-19 16:49:03', '2025-12-19 17:02:48', 'Manual clock-in', 2, '2025-12-19 15:49:03'),
(3, 2, '2025-12-20', '2025-12-20 10:37:27', '2025-12-20 14:33:42', NULL, 1, '2025-12-20 09:37:27'),
(4, 4, '2025-12-20', '2025-12-20 14:30:46', '2025-12-20 14:32:00', NULL, 1, '2025-12-20 13:30:46'),
(5, 3, '2025-12-28', '2025-12-28 10:48:48', '2025-12-28 10:49:27', NULL, 1, '2025-12-28 09:48:48'),
(6, 4, '2026-08-21', '2026-08-21 09:45:44', '2026-08-21 09:46:33', NULL, 4, '2026-08-21 08:45:44'),
(7, 1, '2026-08-21', '2026-08-21 11:13:48', '2026-08-21 11:13:59', NULL, 4, '2026-08-21 10:13:48');

-- --------------------------------------------------------

--
-- Table structure for table `stay_extensions`
--

CREATE TABLE `stay_extensions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `booking_id` bigint(20) UNSIGNED NOT NULL,
  `original_checkout` date NOT NULL,
  `new_checkout` date NOT NULL,
  `additional_nights` int(11) NOT NULL,
  `additional_amount` decimal(12,2) NOT NULL,
  `extended_by` int(10) UNSIGNED DEFAULT NULL,
  `extended_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='room retainment';

--
-- Dumping data for table `stay_extensions`
--

INSERT INTO `stay_extensions` (`id`, `booking_id`, `original_checkout`, `new_checkout`, `additional_nights`, `additional_amount`, `extended_by`, `extended_at`) VALUES
(1, 53, '2025-12-17', '2025-12-18', 1, '60000.00', 2, '2025-12-16 14:14:19'),
(2, 55, '2025-12-17', '2025-12-18', 1, '4000.00', 2, '2025-12-16 14:15:35'),
(3, 55, '2025-12-18', '2025-12-19', 1, '4000.00', 2, '2025-12-16 14:15:39'),
(4, 58, '2025-12-17', '2025-12-18', 1, '60000.00', 2, '2025-12-16 20:49:51'),
(5, 59, '2025-12-17', '2025-12-18', 1, '1200.00', 2, '2025-12-16 20:51:50'),
(6, 60, '2025-12-17', '2025-12-18', 1, '1200.00', 2, '2025-12-16 21:08:59'),
(7, 61, '2025-12-17', '2025-12-18', 1, '1200.00', 2, '2025-12-16 21:15:17'),
(8, 62, '2025-12-17', '2025-12-18', 1, '14000.00', 2, '2025-12-16 21:23:00'),
(9, 65, '2025-12-17', '2025-12-18', 1, '2500.00', 2, '2025-12-16 21:29:27'),
(10, 66, '2025-12-17', '2025-12-18', 1, '14000.00', 2, '2025-12-16 21:33:31'),
(11, 67, '2025-12-17', '2025-12-18', 1, '2500.00', 2, '2025-12-16 21:34:41'),
(12, 68, '2025-12-17', '2025-12-18', 1, '2500.00', 2, '2025-12-16 21:44:51'),
(13, 64, '2025-12-17', '2025-12-18', 1, '14000.00', 2, '2025-12-16 21:51:57'),
(14, 69, '2025-12-18', '2025-12-19', 1, '41000.00', 2, '2025-12-17 12:08:21'),
(15, 70, '2025-12-18', '2025-12-19', 1, '60000.00', 2, '2025-12-17 15:23:25'),
(16, 92, '2025-12-29', '2025-12-31', 2, '120000.00', 1, '2025-12-28 09:44:29'),
(17, 95, '2026-08-22', '2026-08-23', 1, '60000.00', 4, '2026-08-21 14:39:30');

-- --------------------------------------------------------

--
-- Table structure for table `users_admin`
--

CREATE TABLE `users_admin` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fullname` varchar(150) DEFAULT NULL,
  `role` varchar(50) DEFAULT 'receptionist',
  `role_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users_admin`
--

INSERT INTO `users_admin` (`id`, `username`, `password`, `fullname`, `role`, `role_id`, `created_at`) VALUES
(1, 'admin', '$2y$10$8OIMM4Q3DBmRlqhcDn0V0OlNmC3vqVjGTS6C5F5jNpCSK1hpB7jpq', 'SHETTIMA JOHNSON', 'super_admin', 1, '2025-12-05 18:05:21'),
(2, 'shettimax', '$2y$10$8OIMM4Q3DBmRlqhcDn0V0OlNmC3vqVjGTS6C5F5jNpCSK1hpB7jpq', 'JOHNSON AARON', 'manager', 2, '2025-12-07 11:57:31'),
(4, 'admin1', '$2y$10$8OIMM4Q3DBmRlqhcDn0V0OlNmC3vqVjGTS6C5F5jNpCSK1hpB7jpq', 'MARY JOHNSON', 'receptionist', 3, '2025-12-08 03:35:43');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_admin` (`admin_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_date` (`created_at`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_booking_customer` (`customer_id`),
  ADD KEY `fk_booking_room` (`room_id`),
  ADD KEY `idx_bookings_dates` (`checkin_date`,`checkout_date`),
  ADD KEY `idx_bookings_status` (`status`,`checkout_status`),
  ADD KEY `idx_checkin_datetime` (`checkin_date`,`checkin_time`),
  ADD KEY `idx_checkout_datetime` (`checkout_date`,`checkout_time`),
  ADD KEY `idx_staff_id` (`staff_id`);

--
-- Indexes for table `buildings`
--
ALTER TABLE `buildings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `checkouts`
--
ALTER TABLE `checkouts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_booking` (`booking_id`),
  ADD KEY `co_fk_invoice` (`invoice_id`),
  ADD KEY `co_fk_customer` (`customer_id`),
  ADD KEY `co_fk_room` (`room_id`),
  ADD KEY `co_fk_admin` (`checked_out_by`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_customers_name` (`lastname`,`firstname`),
  ADD KEY `idx_customers_phone` (`phone`);

--
-- Indexes for table `disputes`
--
ALTER TABLE `disputes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_booking_id` (`booking_id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_invoice_booking` (`booking_id`);

--
-- Indexes for table `page_permissions`
--
ALTER TABLE `page_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_page` (`page`),
  ADD KEY `fk_page_permissions_permission` (`permission_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_payment_invoice` (`invoice_id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_role_permission` (`role_id`,`permission_id`),
  ADD KEY `fk_role_permissions_permission` (`permission_id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_room_building` (`building_id`),
  ADD KEY `idx_rooms_status` (`status`,`building_id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pnumber` (`pnumber`);

--
-- Indexes for table `staff_attendance`
--
ALTER TABLE `staff_attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_staff_date` (`staff_id`,`date`);

--
-- Indexes for table `stay_extensions`
--
ALTER TABLE `stay_extensions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `se_fk_booking` (`booking_id`),
  ADD KEY `se_fk_admin` (`extended_by`);

--
-- Indexes for table `users_admin`
--
ALTER TABLE `users_admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `fk_users_admin_role` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=285;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=99;

--
-- AUTO_INCREMENT for table `buildings`
--
ALTER TABLE `buildings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `checkouts`
--
ALTER TABLE `checkouts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=99;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `disputes`
--
ALTER TABLE `disputes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=99;

--
-- AUTO_INCREMENT for table `page_permissions`
--
ALTER TABLE `page_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=131;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `staff_attendance`
--
ALTER TABLE `staff_attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `stay_extensions`
--
ALTER TABLE `stay_extensions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `users_admin`
--
ALTER TABLE `users_admin`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `fk_booking_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_booking_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`);

--
-- Constraints for table `checkouts`
--
ALTER TABLE `checkouts`
  ADD CONSTRAINT `co_fk_admin` FOREIGN KEY (`checked_out_by`) REFERENCES `users_admin` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `co_fk_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `co_fk_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `co_fk_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `co_fk_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `fk_invoice_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `page_permissions`
--
ALTER TABLE `page_permissions`
  ADD CONSTRAINT `fk_page_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payment_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `fk_role_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_role_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `fk_room_building` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `staff_attendance`
--
ALTER TABLE `staff_attendance`
  ADD CONSTRAINT `staff_attendance_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stay_extensions`
--
ALTER TABLE `stay_extensions`
  ADD CONSTRAINT `se_fk_admin` FOREIGN KEY (`extended_by`) REFERENCES `users_admin` (`id`),
  ADD CONSTRAINT `se_fk_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users_admin`
--
ALTER TABLE `users_admin`
  ADD CONSTRAINT `fk_users_admin_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
