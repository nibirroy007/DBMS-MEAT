-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:4306
-- Generation Time: Aug 29, 2025 at 10:42 PM
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
-- Database: `meat_market`
--

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `contact_number` varchar(40) DEFAULT NULL,
  `address_city` varchar(120) DEFAULT NULL,
  `address_street` varchar(160) DEFAULT NULL,
  `address_zipcode` varchar(20) DEFAULT NULL,
  `preferences` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer`
--

INSERT INTO `customer` (`customer_id`, `name`, `contact_number`, `address_city`, `address_street`, `address_zipcode`, `preferences`) VALUES
(1, 'Md. Shakil Ahmed', '01711000001', 'Dhaka', 'House 12, Road 3, Dhanmondi', '1209', 'prefers beef'),
(2, 'Nusrat Jahan', '01819000002', 'Chattogram', 'Lane 5, GEC Circle', '4000', 'prefers chicken'),
(3, 'Tanvir Islam', '01921000003', 'Sylhet', 'Shahi Eidgah Road', '3100', 'prefers mutton'),
(4, 'Farhana Hoque', '01615000004', 'Rajshahi', 'Kazla Main Rd', '6000', 'prefers chicken'),
(5, 'Samin Rahman', '01312000005', 'Khulna', 'Sonadanga Ave', '9000', 'prefers beef'),
(6, 'Afsana Akter', '01712000006', 'Barishal', 'Sadar Rd, Banglabazar', '8200', 'prefers chicken'),
(7, 'Rafiul Hasan', '01816000007', 'Rangpur', 'Paira Chattar Lane', '5400', 'prefers beef'),
(8, 'Mehjabin Chowdhury', '01922000008', 'Mymensingh', 'Ganginar Par', '2200', 'prefers chicken'),
(9, 'Faisal Karim', '01617000009', 'Cumilla', 'Kandirpar Rd', '3500', 'prefers beef'),
(10, 'Sadia Sultana', '01713000010', 'Gazipur', 'Chowrasta Rd', '1700', 'prefers chicken'),
(11, 'Imran Hossain', '01817000011', 'Narayanganj', 'Bangabandhu Rd', '1400', 'prefers mutton'),
(12, 'Jannatul Ferdaus', '01923000012', 'Bogura', 'Shatmatha Circle', '5800', 'prefers chicken'),
(13, 'Arif Mahmud', '01618000013', 'Jashore', 'Ghoramara Rd', '7400', 'prefers beef'),
(14, 'Tasnim Tuli', '01714000014', 'Cox\'s Bazar', 'Kolatoli Beach Rd', '4700', 'prefers chicken'),
(15, 'Sajib Khan', '01818000015', 'Savar', 'Nabinagar-Chandra Hwy', '1340', 'prefers beef'),
(16, 'Maliha Rahim', '01924000016', 'Tangail', 'Court Station Rd', '1900', 'prefers chicken'),
(17, 'Rashidul Islam', '01619000017', 'Narsingdi', 'Bazar Rd', '1600', 'prefers beef'),
(18, 'Zarin Tasnim', '01715000018', 'Feni', 'Trunk Rd', '3900', 'prefers chicken'),
(19, 'Aminul Haque', '01819000019', 'Madaripur', 'Main Rd', '7900', 'prefers beef'),
(20, 'Shaila Parvin', '01925000020', 'Manikganj', 'Sadar Rd', '1800', 'prefers chicken');

-- --------------------------------------------------------

--
-- Table structure for table `delivery`
--

CREATE TABLE `delivery` (
  `delivery_id` bigint(20) UNSIGNED NOT NULL,
  `order_quantity` decimal(12,3) NOT NULL CHECK (`order_quantity` >= 0),
  `price_per_unit` decimal(10,2) NOT NULL CHECK (`price_per_unit` >= 0),
  `order_date` date NOT NULL,
  `delivery_date` date DEFAULT NULL,
  `order_status` enum('pending','confirmed','in_transit','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `slaughter_house_id` bigint(20) UNSIGNED NOT NULL,
  `supplier_id` bigint(20) UNSIGNED NOT NULL
) ;

--
-- Dumping data for table `delivery`
--

INSERT INTO `delivery` (`delivery_id`, `order_quantity`, `price_per_unit`, `order_date`, `delivery_date`, `order_status`, `slaughter_house_id`, `supplier_id`) VALUES
(1, 500.000, 520.00, '2025-04-01', '2025-04-02', 'delivered', 1, 1),
(2, 420.000, 515.00, '2025-04-02', '2025-04-03', 'delivered', 2, 2),
(3, 380.000, 510.00, '2025-04-03', '2025-04-04', 'delivered', 3, 3),
(4, 450.000, 508.00, '2025-04-04', '2025-04-05', 'delivered', 4, 4),
(5, 470.000, 512.00, '2025-04-05', '2025-04-06', 'delivered', 5, 5),
(6, 360.000, 505.00, '2025-04-06', '2025-04-07', 'delivered', 6, 6),
(7, 340.000, 500.00, '2025-04-07', '2025-04-08', 'delivered', 7, 7),
(8, 390.000, 507.00, '2025-04-08', '2025-04-09', 'delivered', 8, 8),
(9, 410.000, 509.00, '2025-04-09', '2025-04-10', 'delivered', 9, 9),
(10, 430.000, 511.00, '2025-04-10', '2025-04-11', 'delivered', 10, 10),
(11, 350.000, 506.00, '2025-04-11', '2025-04-12', 'delivered', 11, 11),
(12, 365.000, 504.00, '2025-04-12', '2025-04-13', 'delivered', 12, 12),
(13, 375.000, 503.00, '2025-04-13', '2025-04-14', 'delivered', 13, 13),
(14, 300.000, 498.00, '2025-04-14', '2025-04-15', 'delivered', 14, 14),
(15, 320.000, 500.00, '2025-04-15', '2025-04-16', 'delivered', 15, 15),
(16, 315.000, 501.00, '2025-04-16', '2025-04-17', 'delivered', 16, 16),
(17, 295.000, 497.00, '2025-04-17', '2025-04-18', 'delivered', 17, 17),
(18, 280.000, 495.00, '2025-04-18', '2025-04-19', 'delivered', 18, 18),
(19, 305.000, 496.00, '2025-04-19', '2025-04-20', 'delivered', 19, 19),
(20, 290.000, 494.00, '2025-04-20', '2025-04-21', 'delivered', 20, 20);

-- --------------------------------------------------------

--
-- Table structure for table `farm`
--

CREATE TABLE `farm` (
  `farm_id` bigint(20) UNSIGNED NOT NULL,
  `farm_name` varchar(120) NOT NULL,
  `address_city` varchar(120) DEFAULT NULL,
  `address_area` varchar(120) DEFAULT NULL,
  `address_street` varchar(160) DEFAULT NULL,
  `farm_size` decimal(12,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `farm`
--

INSERT INTO `farm` (`farm_id`, `farm_name`, `address_city`, `address_area`, `address_street`, `farm_size`) VALUES
(1, 'Dhaka Agro Farm', 'Dhaka', 'Savar', 'Hemayetpur Road', 65.50),
(2, 'Chattogram Poultry Valley', 'Chattogram', 'Patenga', 'EPZ Access Rd', 72.00),
(3, 'Sylhet Green Meadows', 'Sylhet', 'Airport', 'Tilagor Lane', 58.25),
(4, 'Rajshahi Heritage Ranch', 'Rajshahi', 'Motihar', 'Kazla Bypass', 80.00),
(5, 'Khulna Delta Agro', 'Khulna', 'Sonadanga', 'Mujgunni Rd', 90.75),
(6, 'Barishal Riverbank Farm', 'Barishal', 'Sadar', 'Sadar Station Rd', 55.00),
(7, 'Rangpur North Fields', 'Rangpur', 'Sadar', 'Paira Chattar Ave', 68.10),
(8, 'Mymensingh Poultry Park', 'Mymensingh', 'Trishal', 'Agricultural Univ Rd', 77.70),
(9, 'Cumilla Cattle Yard', 'Cumilla', 'Adarsha Sadar', 'Kandirpar Link', 64.40),
(10, 'Gazipur Livestock Hub', 'Gazipur', 'Tongi', 'SATA Rd', 73.30),
(11, 'Narayanganj Agro Estate', 'Narayanganj', 'Fatullah', 'KB Rd', 59.90),
(12, 'Bogura Beef Complex', 'Bogura', 'Sadar', 'Shatmatha Link', 85.60),
(13, 'Jashore Pastures', 'Jashore', 'Abhaynagar', 'Noapara Hwy', 66.00),
(14, 'CoxBazar Coastal Farm', 'Cox\'s Bazar', 'Ramu', 'Marine Drive Spur', 51.25),
(15, 'Tangail Shyamnagar Agro', 'Tangail', 'Shyamnagar', 'Court Rd', 60.50),
(16, 'Narsingdi Growers', 'Narsingdi', 'Palash', 'Ghorashal Rd', 62.75),
(17, 'Feni Fresh Fields', 'Feni', 'Sadar', 'Railway Rd', 57.00),
(18, 'Madaripur Grassland', 'Madaripur', 'Rajoir', 'Barkhada Rd', 63.80),
(19, 'Manikganj Prairie', 'Manikganj', 'Sadar', 'Jamuna Rd', 69.45),
(20, 'Gazaria Riverside Farm', 'Munshiganj', 'Gazaria', 'Meghna Ghat Rd', 54.35);

-- --------------------------------------------------------

--
-- Table structure for table `farmer`
--

CREATE TABLE `farmer` (
  `farmer_id` bigint(20) UNSIGNED NOT NULL,
  `farm_id` bigint(20) UNSIGNED NOT NULL,
  `farmer_name` varchar(120) NOT NULL,
  `contact_number` varchar(40) DEFAULT NULL,
  `email` varchar(160) DEFAULT NULL,
  `password` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `farmer`
--

INSERT INTO `farmer` (`farmer_id`, `farm_id`, `farmer_name`, `contact_number`, `email`, `password`) VALUES
(1, 1, 'Md. Rashedul Hasan', '01720000101', 'rashedul1@example.com', '$2y$10$hash1'),
(2, 2, 'Nazmul Huda', '01820000102', 'nazmul2@example.com', '$2y$10$hash2'),
(3, 3, 'Shahidul Islam', '01920000103', 'shahidul3@example.com', '$2y$10$hash3'),
(4, 4, 'Aminur Rahman', '01620000104', 'aminur4@example.com', '$2y$10$hash4'),
(5, 5, 'Jahanara Begum', '01320000105', 'jahanara5@example.com', '$2y$10$hash5'),
(6, 6, 'Sultana Parvin', '01720000106', 'sultana6@example.com', '$2y$10$hash6'),
(7, 7, 'Abdul Karim', '01820000107', 'karim7@example.com', '$2y$10$hash7'),
(8, 8, 'Hasina Akter', '01920000108', 'hasina8@example.com', '$2y$10$hash8'),
(9, 9, 'Mehedi Hasan', '01620000109', 'mehedi9@example.com', '$2y$10$hash9'),
(10, 10, 'Lubna Yasmin', '01720000110', 'lubna10@example.com', '$2y$10$hash10'),
(11, 11, 'Mizanur Rahman', '01820000111', 'mizan11@example.com', '$2y$10$hash11'),
(12, 12, 'Faria Nawar', '01920000112', 'faria12@example.com', '$2y$10$hash12'),
(13, 13, 'Raihan Kabir', '01620000113', 'raihan13@example.com', '$2y$10$hash13'),
(14, 14, 'Tasnia Ahmed', '01720000114', 'tasnia14@example.com', '$2y$10$hash14'),
(15, 15, 'Shahadat Hossain', '01820000115', 'shahadat15@example.com', '$2y$10$hash15'),
(16, 16, 'Taslima Khatun', '01920000116', 'taslima16@example.com', '$2y$10$hash16'),
(17, 17, 'Mahfuzur Rahman', '01620000117', 'mahfuz17@example.com', '$2y$10$hash17'),
(18, 18, 'Samira Islam', '01720000118', 'samira18@example.com', '$2y$10$hash18'),
(19, 19, 'Asif Hossain', '01820000119', 'asif19@example.com', '$2y$10$hash19'),
(20, 20, 'Jubayer Alam', '01920000120', 'jubayer20@example.com', '$2y$10$hash20');

-- --------------------------------------------------------

--
-- Table structure for table `insights`
--

CREATE TABLE `insights` (
  `insight_id` bigint(20) UNSIGNED NOT NULL,
  `per_capita_meat_consumption` decimal(8,3) DEFAULT NULL,
  `region` varchar(120) DEFAULT NULL,
  `demographics` varchar(200) DEFAULT NULL,
  `nutritional_intake` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `insights`
--

INSERT INTO `insights` (`insight_id`, `per_capita_meat_consumption`, `region`, `demographics`, `nutritional_intake`) VALUES
(1, 6.500, 'Dhaka Division', 'Urban 18-35', 'Protein adequate; iron borderline'),
(2, 5.200, 'Chattogram Division', 'Urban 25-45', 'Protein adequate; B12 good'),
(3, 4.300, 'Sylhet Division', 'Semi-urban 18-40', 'Protein moderate; zinc low'),
(4, 4.900, 'Rajshahi Division', 'Rural 20-50', 'Protein moderate; iron low'),
(5, 5.100, 'Khulna Division', 'Urban 20-40', 'Protein adequate'),
(6, 3.900, 'Barishal Division', 'Rural 20-45', 'Protein low; iron low'),
(7, 4.700, 'Rangpur Division', 'Rural 18-45', 'Protein moderate'),
(8, 5.000, 'Mymensingh Division', 'Urban 18-35', 'Protein adequate'),
(9, 4.800, 'Cumilla Region', 'Urban 20-40', 'Protein adequate; fat high'),
(10, 4.600, 'Gazipur Region', 'Industrial workers', 'Protein adequate; calories high'),
(11, 4.400, 'Narayanganj Region', 'Mixed urban', 'Protein moderate'),
(12, 4.300, 'Bogura Region', 'Rural 18-50', 'Protein moderate; calcium low'),
(13, 4.500, 'Jashore Region', 'Semi-urban', 'Protein adequate'),
(14, 4.000, 'Cox\'s Bazar Region', 'Tourism workers', 'Protein moderate; iodine good'),
(15, 4.200, 'Tangail Region', 'Rural 18-45', 'Protein moderate'),
(16, 4.100, 'Narsingdi Region', 'Industrial area', 'Protein moderate; fat high'),
(17, 4.000, 'Feni Region', 'Semi-urban', 'Protein low-moderate'),
(18, 3.800, 'Madaripur Region', 'Rural', 'Protein low'),
(19, 4.050, 'Manikganj Region', 'Rural', 'Protein moderate'),
(20, 4.250, 'Munshiganj Region', 'Semi-urban', 'Protein moderate');

-- --------------------------------------------------------

--
-- Table structure for table `livestock`
--

CREATE TABLE `livestock` (
  `livestock_id` bigint(20) UNSIGNED NOT NULL,
  `farm_id` bigint(20) UNSIGNED NOT NULL,
  `livestock_count` int(11) NOT NULL CHECK (`livestock_count` >= 0),
  `slaughter_rate` decimal(6,3) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `livestock`
--

INSERT INTO `livestock` (`livestock_id`, `farm_id`, `livestock_count`, `slaughter_rate`) VALUES
(1, 1, 240, 0.320),
(2, 2, 900, 0.410),
(3, 3, 300, 0.350),
(4, 4, 420, 0.300),
(5, 5, 350, 0.280),
(6, 6, 280, 0.330),
(7, 7, 260, 0.310),
(8, 8, 750, 0.420),
(9, 9, 410, 0.300),
(10, 10, 600, 0.390),
(11, 11, 270, 0.300),
(12, 12, 500, 0.360),
(13, 13, 330, 0.310),
(14, 14, 210, 0.270),
(15, 15, 295, 0.300),
(16, 16, 240, 0.320),
(17, 17, 255, 0.300),
(18, 18, 225, 0.290),
(19, 19, 275, 0.310),
(20, 20, 245, 0.300);

-- --------------------------------------------------------

--
-- Table structure for table `meat_product`
--

CREATE TABLE `meat_product` (
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `type` enum('beef','chicken','mutton','pork','other') NOT NULL,
  `breed` varchar(80) DEFAULT NULL,
  `average_weight_at_slaughter` decimal(8,3) DEFAULT NULL,
  `feed_conversion_ratio` decimal(6,3) DEFAULT NULL,
  `rearing_period` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `meat_product`
--

INSERT INTO `meat_product` (`product_id`, `type`, `breed`, `average_weight_at_slaughter`, `feed_conversion_ratio`, `rearing_period`) VALUES
(1, 'beef', 'Local Red Chittagong', 230.000, 6.500, 730),
(2, 'beef', 'Sahiwal Cross', 260.000, 6.100, 700),
(3, 'mutton', 'Black Bengal', 22.000, 5.000, 360),
(4, 'chicken', 'Cobb 500 Broiler', 2.200, 1.650, 42),
(5, 'chicken', 'Ross 308 Broiler', 2.100, 1.700, 40),
(6, 'mutton', 'Jamuna Sheep', 24.500, 4.900, 380),
(7, 'beef', 'Friesian Cross', 280.000, 6.300, 720),
(8, 'other', 'Deshi Duck', 2.700, 2.400, 90),
(9, 'beef', 'Brahman Cross', 300.000, 6.000, 750),
(10, 'chicken', 'Sonali (Cross)', 1.900, 2.200, 90),
(11, 'mutton', 'Garole', 20.500, 5.200, 340),
(12, 'beef', 'Pabna Cattle', 240.000, 6.400, 710),
(13, 'chicken', 'Kuroiler', 2.300, 2.000, 85),
(14, 'other', 'Quail', 0.220, 2.800, 45),
(15, 'beef', 'Sindhi Cross', 255.000, 6.200, 700),
(16, 'mutton', 'Mongol Sheep', 23.300, 5.100, 370),
(17, 'chicken', 'Deshi Backyard', 1.300, 2.600, 120),
(18, 'beef', 'Nellore Cross', 290.000, 5.900, 760),
(19, 'other', 'Turkey', 6.500, 2.900, 150),
(20, 'chicken', 'ISA Brown (meat)', 1.800, 2.300, 100),
(21, 'chicken', 'Pabna', 20.000, 2.000, 30);

-- --------------------------------------------------------

--
-- Table structure for table `meat_production_batch`
--

CREATE TABLE `meat_production_batch` (
  `batch_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `processing_id` bigint(20) UNSIGNED DEFAULT NULL,
  `district_volume` decimal(14,3) DEFAULT NULL,
  `livestock_count` int(11) DEFAULT NULL CHECK (`livestock_count` >= 0),
  `slaughter_rate` decimal(6,3) DEFAULT NULL,
  `meat_yield_over_time` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meat_yield_over_time`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `meat_production_batch`
--

INSERT INTO `meat_production_batch` (`batch_id`, `product_id`, `processing_id`, `district_volume`, `livestock_count`, `slaughter_rate`, `meat_yield_over_time`) VALUES
(1, 1, 1, 12500.500, 120, 0.320, '{\"2025-01\": 4200.5, \"2025-02\": 4100.0, \"2025-03\": 4200.0}'),
(2, 2, 2, 9800.000, 140, 0.410, '{\"2025-01\": 3300.0, \"2025-02\": 3200.0, \"2025-03\": 3300.0}'),
(3, 3, 3, 2100.250, 90, 0.350, '{\"2025-01\": 700.0,  \"2025-02\": 700.0,  \"2025-03\": 700.3}'),
(4, 4, 4, 1600.000, 500, 0.300, '{\"2025-01\": 520.0,  \"2025-02\": 540.0,  \"2025-03\": 540.0}'),
(5, 5, 5, 3500.000, 320, 0.280, '{\"2025-01\": 1160.0, \"2025-02\": 1170.0, \"2025-03\": 1170.0}'),
(6, 6, 6, 2300.000, 260, 0.330, '{\"2025-02\": 760.0,  \"2025-03\": 770.0,  \"2025-04\": 770.0}'),
(7, 7, 7, 13000.000, 250, 0.310, '{\"2025-02\": 4300.0, \"2025-03\": 4300.0, \"2025-04\": 4400.0}'),
(8, 8, 8, 1800.000, 700, 0.420, '{\"2025-02\": 590.0,  \"2025-03\": 600.0,  \"2025-04\": 610.0}'),
(9, 9, 9, 14000.000, 380, 0.300, '{\"2025-02\": 4600.0, \"2025-03\": 4700.0, \"2025-04\": 4700.0}'),
(10, 10, 10, 1900.000, 550, 0.390, '{\"2025-02\": 620.0,  \"2025-03\": 640.0,  \"2025-04\": 640.0}'),
(11, 11, NULL, 2000.000, 240, 0.300, '{\"2025-02\": 660.0,  \"2025-03\": 670.0,  \"2025-04\": 670.0}'),
(12, 12, 12, 11000.000, 480, 0.360, '{\"2025-03\": 3600.0, \"2025-04\": 3700.0, \"2025-05\": 3700.0}'),
(13, 13, 13, 2100.000, 310, 0.310, '{\"2025-03\": 700.0,  \"2025-04\": 700.0,  \"2025-05\": 700.0}'),
(14, 14, 14, 1200.000, 200, 0.270, '{\"2025-03\": 380.0,  \"2025-04\": 400.0,  \"2025-05\": 420.0}'),
(15, 15, 15, 2400.000, 280, 0.300, '{\"2025-03\": 800.0,  \"2025-04\": 800.0,  \"2025-05\": 800.0}'),
(16, 16, 16, 2200.000, 230, 0.320, '{\"2025-03\": 720.0,  \"2025-04\": 740.0,  \"2025-05\": 740.0}'),
(17, 17, 17, 2050.000, 240, 0.300, '{\"2025-03\": 680.0,  \"2025-04\": 690.0,  \"2025-05\": 680.0}'),
(18, 18, 18, 1950.000, 210, 0.290, '{\"2025-03\": 640.0,  \"2025-04\": 650.0,  \"2025-05\": 660.0}'),
(19, 19, 19, 2150.000, 260, 0.310, '{\"2025-03\": 710.0,  \"2025-04\": 720.0,  \"2025-05\": 720.0}'),
(20, 20, 20, 2250.000, 240, 0.300, '{\"2025-03\": 740.0,  \"2025-04\": 750.0,  \"2025-05\": 760.0}');

-- --------------------------------------------------------

--
-- Table structure for table `nutrition_analyst`
--

CREATE TABLE `nutrition_analyst` (
  `nutritionist_id` bigint(20) UNSIGNED NOT NULL,
  `insight_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(120) NOT NULL,
  `role` varchar(80) DEFAULT NULL,
  `working_area` varchar(160) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `nutrition_analyst`
--

INSERT INTO `nutrition_analyst` (`nutritionist_id`, `insight_id`, `name`, `role`, `working_area`) VALUES
(1, 1, 'Dr. Sadia Anwar', 'Dietician', 'Dhaka city'),
(2, 2, 'Dr. S.M. Reza', 'Nutritionist', 'Chattogram metro'),
(3, 3, 'Shorna Akter', 'Field Analyst', 'Sylhet semi-urban'),
(4, 4, 'Dr. Mahfuz Kabir', 'Senior Analyst', 'Rajshahi rural'),
(5, 5, 'Tania Sultana', 'Dietician', 'Khulna city'),
(6, 6, 'Rafsan Jani', 'Field Analyst', 'Barishal rural'),
(7, 7, 'Dr. Faiyaz Rahman', 'Nutritionist', 'Rangpur rural'),
(8, 8, 'Rafiath Rashid', 'Analyst', 'Mymensingh'),
(9, 9, 'Dr. Sabrina Haque', 'Dietician', 'Cumilla'),
(10, 10, 'Md. Rony Islam', 'Field Analyst', 'Gazipur'),
(11, 11, 'Dr. Naimur Rahman', 'Nutritionist', 'Narayanganj'),
(12, 12, 'Afia Tabassum', 'Analyst', 'Bogura'),
(13, 13, 'Dr. Zahid Hasan', 'Senior Analyst', 'Jashore'),
(14, 14, 'Ishrat Jahan', 'Dietician', 'Cox\'s Bazar'),
(15, 15, 'Fahim Faisal', 'Analyst', 'Tangail'),
(16, 16, 'Dr. Rumi Chowdhury', 'Nutritionist', 'Narsingdi'),
(17, 17, 'Farzana Yasmin', 'Field Analyst', 'Feni'),
(18, 18, 'Dr. Nayeem Uddin', 'Analyst', 'Madaripur'),
(19, 19, 'Shafayat Hossain', 'Dietician', 'Manikganj'),
(20, 20, 'Dr. Iqbal Karim', 'Senior Analyst', 'Munshiganj');

-- --------------------------------------------------------

--
-- Table structure for table `order`
--

CREATE TABLE `order` (
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `order_quantity` decimal(12,3) NOT NULL CHECK (`order_quantity` >= 0),
  `order_date` date NOT NULL,
  `delivery_date` date DEFAULT NULL,
  `order_status` enum('pending','confirmed','in_transit','delivered','cancelled') NOT NULL DEFAULT 'pending'
) ;

--
-- Dumping data for table `order`
--

INSERT INTO `order` (`order_id`, `customer_id`, `order_quantity`, `order_date`, `delivery_date`, `order_status`) VALUES
(1, 1, 25.000, '2025-04-01', '2025-04-03', 'delivered'),
(2, 2, 15.500, '2025-04-02', '2025-04-04', 'delivered'),
(3, 3, 10.000, '2025-04-03', '2025-04-05', 'delivered'),
(4, 4, 18.750, '2025-04-04', '2025-04-06', 'delivered'),
(5, 5, 30.000, '2025-04-05', '2025-04-07', 'delivered'),
(6, 6, 12.000, '2025-04-06', '2025-04-08', 'delivered'),
(7, 7, 22.000, '2025-04-07', '2025-04-09', 'delivered'),
(8, 8, 16.500, '2025-04-08', '2025-04-10', 'delivered'),
(9, 9, 28.000, '2025-04-09', '2025-04-11', 'delivered'),
(10, 10, 20.000, '2025-04-10', '2025-04-12', 'delivered'),
(11, 11, 14.000, '2025-04-11', '2025-04-13', 'delivered'),
(12, 12, 19.000, '2025-04-12', '2025-04-14', 'delivered'),
(13, 13, 26.000, '2025-04-13', '2025-04-15', 'delivered'),
(14, 14, 11.000, '2025-04-14', '2025-04-16', 'delivered'),
(15, 15, 24.000, '2025-04-15', '2025-04-17', 'delivered'),
(16, 16, 17.000, '2025-04-16', '2025-04-18', 'delivered'),
(17, 17, 13.500, '2025-04-17', '2025-04-19', 'delivered'),
(18, 18, 21.000, '2025-04-18', '2025-04-20', 'delivered'),
(19, 19, 27.000, '2025-04-19', '2025-04-21', 'delivered'),
(20, 20, 18.000, '2025-04-20', '2025-04-22', 'delivered');

-- --------------------------------------------------------

--
-- Table structure for table `processing`
--

CREATE TABLE `processing` (
  `processing_id` bigint(20) UNSIGNED NOT NULL,
  `livestock_id` bigint(20) UNSIGNED NOT NULL,
  `slaughter_house_id` bigint(20) UNSIGNED DEFAULT NULL,
  `slaughter_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `processing`
--

INSERT INTO `processing` (`processing_id`, `livestock_id`, `slaughter_house_id`, `slaughter_date`) VALUES
(1, 1, 1, '2025-01-10'),
(2, 2, 2, '2025-01-12'),
(3, 3, 3, '2025-01-15'),
(4, 4, 4, '2025-01-20'),
(5, 5, 5, '2025-01-22'),
(6, 6, 6, '2025-02-01'),
(7, 7, 7, '2025-02-05'),
(8, 8, 8, '2025-02-08'),
(9, 9, 9, '2025-02-12'),
(10, 10, 10, '2025-02-18'),
(12, 12, 12, '2025-03-01'),
(13, 13, 13, '2025-03-05'),
(14, 14, 14, '2025-03-08'),
(15, 15, 15, '2025-03-12'),
(16, 16, 16, '2025-03-15'),
(17, 17, 17, '2025-03-20'),
(18, 18, 18, '2025-03-25'),
(19, 19, 19, '2025-03-28'),
(20, 20, 20, '2025-03-30');

-- --------------------------------------------------------

--
-- Table structure for table `retailer`
--

CREATE TABLE `retailer` (
  `retailer_id` bigint(20) UNSIGNED NOT NULL,
  `supplier_id` bigint(20) UNSIGNED NOT NULL,
  `retail_price_of_meat_products` decimal(10,2) DEFAULT NULL,
  `trend_seasonal_fluctuations` varchar(200) DEFAULT NULL,
  `trend_regional_fluctuations` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `retailer`
--

INSERT INTO `retailer` (`retailer_id`, `supplier_id`, `retail_price_of_meat_products`, `trend_seasonal_fluctuations`, `trend_regional_fluctuations`) VALUES
(1, 1, 650.00, 'Eid-ul-Adha surge', 'Dhaka premium'),
(2, 2, 630.00, 'Haor fishing season', 'Sylhet moderate'),
(3, 3, 620.00, 'Port worker demand', 'City higher'),
(4, 4, 615.00, 'Post-harvest dip', 'Rajshahi steady'),
(5, 5, 625.00, 'Cyclone storage costs', 'Khulna higher'),
(6, 6, 600.00, 'Monsoon transport', 'Barishal lower'),
(7, 7, 595.00, 'Winter weddings', 'Rangpur lower'),
(8, 8, 605.00, 'Campus sessions', 'Mymensingh steady'),
(9, 9, 610.00, 'Festival demand', 'Cumilla steady'),
(10, 10, 615.00, 'Factory canteens', 'Gazipur higher'),
(11, 11, 610.00, 'Dockside demand', 'Narayanganj higher'),
(12, 12, 600.00, 'Agri fairs', 'Bogura moderate'),
(13, 13, 605.00, 'Regional fairs', 'Jashore steady'),
(14, 14, 595.00, 'Tourist season', 'Cox\'s Bazar higher'),
(15, 15, 600.00, 'Monsoon effect', 'Tangail moderate'),
(16, 16, 600.00, 'Gas outage spikes', 'Narsingdi higher'),
(17, 17, 590.00, 'Monsoon dip', 'Feni lower'),
(18, 18, 585.00, 'Ferry delays', 'Madaripur moderate'),
(19, 19, 595.00, 'Bridge tolls', 'Manikganj moderate'),
(20, 20, 585.00, 'River crossing', 'Munshiganj moderate');

-- --------------------------------------------------------

--
-- Table structure for table `slaughter_house`
--

CREATE TABLE `slaughter_house` (
  `slaughter_house_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `location_city` varchar(120) DEFAULT NULL,
  `location_area` varchar(120) DEFAULT NULL,
  `location_street` varchar(160) DEFAULT NULL,
  `supply_region` varchar(120) DEFAULT NULL,
  `supply_timeframe` varchar(120) DEFAULT NULL,
  `policy_decision` varchar(240) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `slaughter_house`
--

INSERT INTO `slaughter_house` (`slaughter_house_id`, `name`, `location_city`, `location_area`, `location_street`, `supply_region`, `supply_timeframe`, `policy_decision`) VALUES
(1, 'Tejgaon Central SH', 'Dhaka', 'Tejgaon', 'Old Airport Rd', 'Dhaka Division', 'Weekly', 'HACCP compliant'),
(2, 'Karnafuli SH', 'Chattogram', 'EPZ', 'Karnafuli Bridge Rd', 'Chattogram Division', 'Weekly', 'Cold-chain expansion'),
(3, 'Sylhet City SH', 'Sylhet', 'Zindabazar', 'Amberkhana Rd', 'Sylhet Division', 'Biweekly', 'Seasonal surge plan'),
(4, 'Rajshahi Metropolitan', 'Rajshahi', 'Boalia', 'Shaheb Bazar Rd', 'Rajshahi Division', 'Monthly', 'Water recycling'),
(5, 'Khulna Industrial SH', 'Khulna', 'BIDC', 'BIDC Rd', 'Khulna Division', 'Weekly', 'ISO 22000'),
(6, 'Barishal Sadar SH', 'Barishal', 'Sadar', 'Launch Ghat Rd', 'Barishal Division', 'Biweekly', 'Vendor vetting'),
(7, 'Rangpur Regional SH', 'Rangpur', 'Sadar', 'Station Rd', 'Rangpur Division', 'Weekly', 'Vaccination drive'),
(8, 'Mymensingh SH', 'Mymensingh', 'Sadar', 'Agriculture Univ Rd', 'Mymensingh Division', 'Weekly', 'Effluent control'),
(9, 'Cumilla District SH', 'Cumilla', 'Kotwali', 'Kandirpar Rd', 'Cumilla Region', 'Weekly', 'Hazard audit'),
(10, 'Gazipur City SH', 'Gazipur', 'Tongi', 'Mill Gate Rd', 'Gazipur Region', 'Weekly', 'Energy saving'),
(11, 'Narayanganj River SH', 'Narayanganj', 'Sadar', 'B.B. Rd', 'Narayanganj Region', 'Weekly', 'Traceability'),
(12, 'Bogura SH', 'Bogura', 'Sadar', 'Shatmatha Rd', 'Bogura Region', 'Weekly', 'Staff training'),
(13, 'Jashore SH', 'Jashore', 'Sadar', 'Railgate Rd', 'Jashore Region', 'Weekly', 'New chillers'),
(14, 'Cox\'s Bazar Coastal SH', 'Cox\'s Bazar', 'Ramu', 'Marine Drive', 'Cox\'s Bazar Region', 'Biweekly', 'Tourist season plan'),
(15, 'Tangail SH', 'Tangail', 'Sadar', 'Court Station Rd', 'Tangail Region', 'Monthly', 'Supplier onboarding'),
(16, 'Narsingdi SH', 'Narsingdi', 'Palash', 'Ghorashal Rd', 'Narsingdi Region', 'Weekly', 'By-product sales'),
(17, 'Feni SH', 'Feni', 'Sadar', 'Trunk Rd', 'Feni Region', 'Biweekly', 'Freezer upgrade'),
(18, 'Madaripur SH', 'Madaripur', 'Sadar', 'Main Rd', 'Madaripur Region', 'Monthly', 'Safety drills'),
(19, 'Manikganj SH', 'Manikganj', 'Sadar', 'Jamuna Rd', 'Manikganj Region', 'Monthly', 'Community program'),
(20, 'Gazaria SH', 'Munshiganj', 'Gazaria', 'Meghna Ghat Rd', 'Munshiganj Region', 'Monthly', 'Logistics MoU');

-- --------------------------------------------------------

--
-- Table structure for table `supplier`
--

CREATE TABLE `supplier` (
  `supplier_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `contact_number` varchar(40) DEFAULT NULL,
  `location_city` varchar(120) DEFAULT NULL,
  `location_area` varchar(120) DEFAULT NULL,
  `location_street` varchar(160) DEFAULT NULL,
  `consumer_demand` decimal(12,3) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supplier`
--

INSERT INTO `supplier` (`supplier_id`, `name`, `contact_number`, `location_city`, `location_area`, `location_street`, `consumer_demand`) VALUES
(1, 'Amin Traders', '01730001001', 'Dhaka', 'Mohakhali', 'TB Gate Rd', 12500.000),
(2, 'Shahjalal Supplies', '01830001002', 'Sylhet', 'Zindabazar', 'Amberkhana Rd', 4200.000),
(3, 'Karnafuli Foods', '01930001003', 'Chattogram', 'Agrabad', 'OC Rd', 9800.000),
(4, 'Rajshahi AgroMart', '01630001004', 'Rajshahi', 'Boalia', 'Naodapara Rd', 5200.000),
(5, 'Delta Fresh', '01330001005', 'Khulna', 'Sonadanga', 'BIDC Rd', 6100.000),
(6, 'Barishal Meat Co.', '01730001006', 'Barishal', 'Sadar', 'Launch Ghat Rd', 3300.000),
(7, 'Rangpur Meat House', '01830001007', 'Rangpur', 'Sadar', 'Station Rd', 3600.000),
(8, 'Mymensingh Foods', '01930001008', 'Mymensingh', 'Sadar', 'Univ Rd', 4100.000),
(9, 'Cumilla Protein Ltd', '01630001009', 'Cumilla', 'Kotwali', 'Kandirpar Rd', 3900.000),
(10, 'Gazipur Distributors', '01730001010', 'Gazipur', 'Tongi', 'Mill Gate Rd', 4500.000),
(11, 'Narayanganj Supply', '01830001011', 'Narayanganj', 'Sadar', 'B.B. Rd', 4300.000),
(12, 'Bogura Provisioners', '01930001012', 'Bogura', 'Sadar', 'Shatmatha Rd', 3400.000),
(13, 'Jashore Wholesale', '01630001013', 'Jashore', 'Sadar', 'Railgate Rd', 3700.000),
(14, 'Seaview Foods', '01730001014', 'Cox\'s Bazar', 'Ramu', 'Marine Dr', 3000.000),
(15, 'Tangail Agro Supply', '01830001015', 'Tangail', 'Sadar', 'Court Station Rd', 3200.000),
(16, 'Narsingdi Partners', '01930001016', 'Narsingdi', 'Palash', 'Ghorashal Rd', 3100.000),
(17, 'Feni Protein House', '01630001017', 'Feni', 'Sadar', 'Trunk Rd', 2800.000),
(18, 'Madaripur Foods', '01730001018', 'Madaripur', 'Sadar', 'Main Rd', 2500.000),
(19, 'Manikganj Sellers', '01830001019', 'Manikganj', 'Sadar', 'Jamuna Rd', 2600.000),
(20, 'Gazaria Distribution', '01930001020', 'Munshiganj', 'Gazaria', 'Meghna Ghat Rd', 2400.000);

-- --------------------------------------------------------

--
-- Table structure for table `wholesaler`
--

CREATE TABLE `wholesaler` (
  `wholesaler_id` bigint(20) UNSIGNED NOT NULL,
  `supplier_id` bigint(20) UNSIGNED NOT NULL,
  `wholesale_price_of_meat_products` decimal(10,2) DEFAULT NULL,
  `trend_seasonal_fluctuations` varchar(200) DEFAULT NULL,
  `trend_regional_fluctuations` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wholesaler`
--

INSERT INTO `wholesaler` (`wholesaler_id`, `supplier_id`, `wholesale_price_of_meat_products`, `trend_seasonal_fluctuations`, `trend_regional_fluctuations`) VALUES
(1, 1, 560.00, 'Peaks in Eid-ul-Adha', 'Dhaka higher'),
(2, 2, 540.00, 'Tourist season boost', 'Sylhet moderate'),
(3, 3, 520.00, 'Winter demand rise', 'Port fees add'),
(4, 4, 515.00, 'Harvest-time dip', 'Rajshahi steady'),
(5, 5, 525.00, 'Cyclone risk spikes', 'Khulna higher'),
(6, 6, 505.00, 'River transport limits', 'Barishal lower'),
(7, 7, 500.00, 'Winter rise', 'Rangpur lower'),
(8, 8, 510.00, 'Academic season steady', 'Mymensingh steady'),
(9, 9, 515.00, 'Festival peaks', 'Cumilla steady'),
(10, 10, 520.00, 'Industrial canteens', 'Gazipur higher'),
(11, 11, 515.00, 'Dock delays', 'Narayanganj higher'),
(12, 12, 505.00, 'Agri fair season', 'Bogura moderate'),
(13, 13, 510.00, 'Mango season tourism', 'Jashore steady'),
(14, 14, 500.00, 'Tourist influx', 'Cox\'s Bazar higher'),
(15, 15, 505.00, 'Monsoon logistics', 'Tangail moderate'),
(16, 16, 505.00, 'Gas supply issues', 'Narsingdi higher'),
(17, 17, 495.00, 'Monsoon dips', 'Feni lower'),
(18, 18, 490.00, 'Ferry delays', 'Madaripur moderate'),
(19, 19, 500.00, 'Bridge toll impact', 'Manikganj moderate'),
(20, 20, 490.00, 'River crossing costs', 'Munshiganj moderate');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`customer_id`);

--
-- Indexes for table `delivery`
--
ALTER TABLE `delivery`
  ADD PRIMARY KEY (`delivery_id`),
  ADD KEY `fk_delivery_house` (`slaughter_house_id`),
  ADD KEY `idx_delivery_supplier_date` (`supplier_id`,`delivery_date`);

--
-- Indexes for table `farm`
--
ALTER TABLE `farm`
  ADD PRIMARY KEY (`farm_id`);

--
-- Indexes for table `farmer`
--
ALTER TABLE `farmer`
  ADD PRIMARY KEY (`farmer_id`),
  ADD UNIQUE KEY `uq_farmer_email` (`email`),
  ADD KEY `idx_farmer_farm` (`farm_id`);

--
-- Indexes for table `insights`
--
ALTER TABLE `insights`
  ADD PRIMARY KEY (`insight_id`);

--
-- Indexes for table `livestock`
--
ALTER TABLE `livestock`
  ADD PRIMARY KEY (`livestock_id`),
  ADD KEY `idx_livestock_farm` (`farm_id`);

--
-- Indexes for table `meat_product`
--
ALTER TABLE `meat_product`
  ADD PRIMARY KEY (`product_id`);

--
-- Indexes for table `meat_production_batch`
--
ALTER TABLE `meat_production_batch`
  ADD PRIMARY KEY (`batch_id`),
  ADD KEY `idx_batch_product` (`product_id`),
  ADD KEY `idx_batch_processing` (`processing_id`);

--
-- Indexes for table `nutrition_analyst`
--
ALTER TABLE `nutrition_analyst`
  ADD PRIMARY KEY (`nutritionist_id`),
  ADD KEY `fk_analyst_insight` (`insight_id`);

--
-- Indexes for table `order`
--
ALTER TABLE `order`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `idx_order_customer_date` (`customer_id`,`order_date`);

--
-- Indexes for table `processing`
--
ALTER TABLE `processing`
  ADD PRIMARY KEY (`processing_id`),
  ADD KEY `fk_processing_livestock` (`livestock_id`),
  ADD KEY `idx_processing_date` (`slaughter_date`),
  ADD KEY `idx_processing_house` (`slaughter_house_id`);

--
-- Indexes for table `retailer`
--
ALTER TABLE `retailer`
  ADD PRIMARY KEY (`retailer_id`),
  ADD KEY `idx_retailer_supplier` (`supplier_id`);

--
-- Indexes for table `slaughter_house`
--
ALTER TABLE `slaughter_house`
  ADD PRIMARY KEY (`slaughter_house_id`);

--
-- Indexes for table `supplier`
--
ALTER TABLE `supplier`
  ADD PRIMARY KEY (`supplier_id`);

--
-- Indexes for table `wholesaler`
--
ALTER TABLE `wholesaler`
  ADD PRIMARY KEY (`wholesaler_id`),
  ADD KEY `idx_wholesaler_supplier` (`supplier_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `customer`
--
ALTER TABLE `customer`
  MODIFY `customer_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `delivery`
--
ALTER TABLE `delivery`
  MODIFY `delivery_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `farm`
--
ALTER TABLE `farm`
  MODIFY `farm_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `farmer`
--
ALTER TABLE `farmer`
  MODIFY `farmer_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `insights`
--
ALTER TABLE `insights`
  MODIFY `insight_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `livestock`
--
ALTER TABLE `livestock`
  MODIFY `livestock_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `meat_product`
--
ALTER TABLE `meat_product`
  MODIFY `product_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `meat_production_batch`
--
ALTER TABLE `meat_production_batch`
  MODIFY `batch_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `nutrition_analyst`
--
ALTER TABLE `nutrition_analyst`
  MODIFY `nutritionist_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `order`
--
ALTER TABLE `order`
  MODIFY `order_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `processing`
--
ALTER TABLE `processing`
  MODIFY `processing_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `retailer`
--
ALTER TABLE `retailer`
  MODIFY `retailer_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `slaughter_house`
--
ALTER TABLE `slaughter_house`
  MODIFY `slaughter_house_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `supplier`
--
ALTER TABLE `supplier`
  MODIFY `supplier_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `wholesaler`
--
ALTER TABLE `wholesaler`
  MODIFY `wholesaler_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `delivery`
--
ALTER TABLE `delivery`
  ADD CONSTRAINT `fk_delivery_house` FOREIGN KEY (`slaughter_house_id`) REFERENCES `slaughter_house` (`slaughter_house_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_delivery_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `supplier` (`supplier_id`) ON UPDATE CASCADE;

--
-- Constraints for table `farmer`
--
ALTER TABLE `farmer`
  ADD CONSTRAINT `fk_farmer_farm` FOREIGN KEY (`farm_id`) REFERENCES `farm` (`farm_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `livestock`
--
ALTER TABLE `livestock`
  ADD CONSTRAINT `fk_livestock_farm` FOREIGN KEY (`farm_id`) REFERENCES `farm` (`farm_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `meat_production_batch`
--
ALTER TABLE `meat_production_batch`
  ADD CONSTRAINT `fk_batch_processing` FOREIGN KEY (`processing_id`) REFERENCES `processing` (`processing_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_batch_product` FOREIGN KEY (`product_id`) REFERENCES `meat_product` (`product_id`) ON UPDATE CASCADE;

--
-- Constraints for table `nutrition_analyst`
--
ALTER TABLE `nutrition_analyst`
  ADD CONSTRAINT `fk_analyst_insight` FOREIGN KEY (`insight_id`) REFERENCES `insights` (`insight_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `order`
--
ALTER TABLE `order`
  ADD CONSTRAINT `fk_order_customer` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `processing`
--
ALTER TABLE `processing`
  ADD CONSTRAINT `fk_processing_house` FOREIGN KEY (`slaughter_house_id`) REFERENCES `slaughter_house` (`slaughter_house_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_processing_livestock` FOREIGN KEY (`livestock_id`) REFERENCES `livestock` (`livestock_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `retailer`
--
ALTER TABLE `retailer`
  ADD CONSTRAINT `fk_retailer_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `supplier` (`supplier_id`) ON UPDATE CASCADE;

--
-- Constraints for table `wholesaler`
--
ALTER TABLE `wholesaler`
  ADD CONSTRAINT `fk_wholesaler_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `supplier` (`supplier_id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
