-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 16, 2026 at 12:16 AM
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
-- Database: `okoawatoto_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `case_updates`
--

CREATE TABLE `case_updates` (
  `id` int(11) NOT NULL,
  `case_id` int(11) NOT NULL,
  `case_type` enum('missing','found') DEFAULT 'missing',
  `previous_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `case_updates`
--

INSERT INTO `case_updates` (`id`, `case_id`, `case_type`, `previous_status`, `new_status`, `notes`, `updated_by`, `created_at`) VALUES
(1, 4, 'missing', 'Missing', 'Found', NULL, NULL, '2026-05-15 21:27:28'),
(2, 4, 'missing', 'Found', 'Missing', NULL, NULL, '2026-05-15 21:27:39'),
(3, 5, 'missing', 'Missing', 'Reunited', NULL, NULL, '2026-05-15 21:28:09');

-- --------------------------------------------------------

--
-- Table structure for table `children_reports`
--

CREATE TABLE `children_reports` (
  `id` int(11) NOT NULL,
  `case_number` varchar(50) NOT NULL,
  `child_name` varchar(100) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `description` text DEFAULT NULL,
  `clothing` text DEFAULT NULL,
  `health_status` enum('Safe','Injured','In Danger','Unknown') DEFAULT 'Unknown',
  `status` enum('Missing','Found','Reunited') DEFAULT 'Missing',
  `last_seen_location` varchar(255) DEFAULT NULL,
  `region_id` int(11) DEFAULT NULL,
  `district_id` int(11) DEFAULT NULL,
  `ward_id` int(11) DEFAULT NULL,
  `last_seen_date` datetime DEFAULT NULL,
  `specific_address` text DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `additional_photos` text DEFAULT NULL,
  `reporter_name` varchar(100) DEFAULT NULL,
  `reporter_phone` varchar(20) DEFAULT NULL,
  `reporter_email` varchar(100) DEFAULT NULL,
  `reporter_type` enum('Parent','Police','Witness','Other','Relative','Teacher') DEFAULT NULL,
  `reporter_id` int(11) DEFAULT NULL,
  `police_station_id` int(11) DEFAULT NULL,
  `investigating_officer` varchar(100) DEFAULT NULL,
  `officer_badge` varchar(50) DEFAULT NULL,
  `case_priority` enum('High','Medium','Low') DEFAULT 'Medium',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `children_reports`
--

INSERT INTO `children_reports` (`id`, `case_number`, `child_name`, `age`, `gender`, `description`, `clothing`, `health_status`, `status`, `last_seen_location`, `region_id`, `district_id`, `ward_id`, `last_seen_date`, `specific_address`, `latitude`, `longitude`, `photo`, `additional_photos`, `reporter_name`, `reporter_phone`, `reporter_email`, `reporter_type`, `reporter_id`, `police_station_id`, `investigating_officer`, `officer_badge`, `case_priority`, `created_at`, `updated_at`) VALUES
(1, 'CASE-2024-001', 'Amani Juma', 8, 'Male', 'Short height, dark skin, short black hair, small scar on left cheek', 'Blue t-shirt, black shorts, white shoes', 'Unknown', 'Missing', 'Kinondoni, Dar es Salaam', 2, 9, NULL, '2024-10-12 14:30:00', NULL, NULL, NULL, NULL, NULL, 'Juma Hassan', '0712345678', NULL, NULL, NULL, NULL, NULL, NULL, 'High', '2026-05-15 19:33:10', '2026-05-15 19:33:10'),
(3, 'CASE-2024-003', 'Zahara Hamisi', 6, 'Female', 'Dark skin, braided hair with beads, missing front tooth', 'Pink dress with flowers, white socks', 'Unknown', 'Missing', 'Mwanza City Center', 16, 13, NULL, '2024-10-15 16:20:00', NULL, NULL, NULL, NULL, NULL, 'Fatma Hamisi', '0745678901', NULL, NULL, NULL, NULL, NULL, NULL, 'High', '2026-05-15 19:33:10', '2026-05-15 19:33:10'),
(4, 'CASE-2026-1137', 'Hussein Abdulrahman', 4, 'Male', 'mweupe kidogo', 'kavaa suruali na shati', 'Safe', 'Missing', 'Kilombero', NULL, NULL, NULL, '2026-05-15 23:24:00', NULL, NULL, NULL, '1778876984_me1.jpg', NULL, 'ABDALLA ABRAHMANI ABDALLA', '0775892103', 'husseinali2334@gmail.com', 'Parent', NULL, NULL, NULL, NULL, 'High', '2026-05-15 20:29:44', '2026-05-15 21:27:39'),
(5, 'CASE-2026-4404', 'Sultan Abrahman Abdulla', 10, 'Male', 'Heing', 'shirt', 'Safe', 'Reunited', 'Kilombero', NULL, NULL, NULL, '2026-05-16 00:18:00', NULL, NULL, NULL, '1778880059_wakas1.jpg', NULL, 'EUTICK JOSEPH TESHA', '0756464274', 'mfano@gmail.com', 'Parent', NULL, 1, NULL, NULL, 'High', '2026-05-15 21:20:59', '2026-05-15 21:28:09');

--
-- Triggers `children_reports`
--
DELIMITER $$
CREATE TRIGGER `after_child_report_insert` AFTER INSERT ON `children_reports` FOR EACH ROW BEGIN
    UPDATE system_stats 
    SET stat_value = stat_value + 1 
    WHERE stat_name = 'total_children_reported';
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `before_child_report_update` BEFORE UPDATE ON `children_reports` FOR EACH ROW BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO case_updates (case_id, case_type, previous_status, new_status, updated_by)
        VALUES (NEW.id, 'missing', OLD.status, NEW.status, @current_user_id);
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `districts`
--

CREATE TABLE `districts` (
  `id` int(11) NOT NULL,
  `region_id` int(11) NOT NULL,
  `district_name` varchar(100) NOT NULL,
  `district_code` varchar(10) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `districts`
--

INSERT INTO `districts` (`id`, `region_id`, `district_name`, `district_code`, `created_at`) VALUES
(1, 1, 'Arusha City Council', 'AR-01', '2026-05-15 19:33:10'),
(2, 1, 'Arusha District Council', 'AR-02', '2026-05-15 19:33:10'),
(3, 1, 'Meru District', 'AR-03', '2026-05-15 19:33:10'),
(4, 1, 'Karatu District', 'AR-04', '2026-05-15 19:33:10'),
(5, 1, 'Longido District', 'AR-05', '2026-05-15 19:33:10'),
(6, 1, 'Monduli District', 'AR-06', '2026-05-15 19:33:10'),
(7, 1, 'Ngorongoro District', 'AR-07', '2026-05-15 19:33:10'),
(8, 2, 'Ilala Municipal Council', 'DS-01', '2026-05-15 19:33:10'),
(9, 2, 'Kinondoni Municipal Council', 'DS-02', '2026-05-15 19:33:10'),
(10, 2, 'Temeke Municipal Council', 'DS-03', '2026-05-15 19:33:10'),
(11, 2, 'Kigamboni Municipal Council', 'DS-04', '2026-05-15 19:33:10'),
(12, 2, 'Ubungo Municipal Council', 'DS-05', '2026-05-15 19:33:10'),
(13, 16, 'Mwanza City Council', 'MW-01', '2026-05-15 19:33:10'),
(14, 16, 'Nyamagana District', 'MW-02', '2026-05-15 19:33:10'),
(15, 16, 'Ilemela District', 'MW-03', '2026-05-15 19:33:10'),
(16, 16, 'Sengerema District', 'MW-04', '2026-05-15 19:33:10'),
(17, 16, 'Bunda District', 'MW-05', '2026-05-15 19:33:10'),
(18, 16, 'Ukerewe District', 'MW-06', '2026-05-15 19:33:10'),
(19, 3, 'Dodoma City Council', 'DD-01', '2026-05-15 19:33:10'),
(20, 3, 'Dodoma District', 'DD-02', '2026-05-15 19:33:10'),
(21, 3, 'Bahi District', 'DD-03', '2026-05-15 19:33:10'),
(22, 3, 'Chamwino District', 'DD-04', '2026-05-15 19:33:10'),
(23, 3, 'Chemba District', 'DD-05', '2026-05-15 19:33:10'),
(24, 3, 'Kondoa District', 'DD-06', '2026-05-15 19:33:10'),
(25, 3, 'Mpwapwa District', 'DD-07', '2026-05-15 19:33:10'),
(26, 9, 'Moshi City Council', 'KJ-01', '2026-05-15 19:33:10'),
(27, 9, 'Moshi District', 'KJ-02', '2026-05-15 19:33:10'),
(28, 9, 'Rombo District', 'KJ-03', '2026-05-15 19:33:10'),
(29, 9, 'Same District', 'KJ-04', '2026-05-15 19:33:10'),
(30, 9, 'Hai District', 'KJ-05', '2026-05-15 19:33:10'),
(31, 9, 'Siha District', 'KJ-06', '2026-05-15 19:33:10'),
(32, 13, 'Mbeya City Council', 'MB-01', '2026-05-15 19:33:10'),
(33, 13, 'Mbeya District', 'MB-02', '2026-05-15 19:33:10'),
(34, 13, 'Chunya District', 'MB-03', '2026-05-15 19:33:10'),
(35, 13, 'Kyela District', 'MB-04', '2026-05-15 19:33:10'),
(36, 13, 'Mbarali District', 'MB-05', '2026-05-15 19:33:10'),
(37, 13, 'Rungwe District', 'MB-06', '2026-05-15 19:33:10'),
(38, 28, 'Tanga City Council', 'TG-01', '2026-05-15 19:33:10'),
(39, 28, 'Muheza District', 'TG-02', '2026-05-15 19:33:10'),
(40, 28, 'Korogwe District', 'TG-03', '2026-05-15 19:33:10'),
(41, 28, 'Handeni District', 'TG-04', '2026-05-15 19:33:10'),
(42, 28, 'Lushoto District', 'TG-05', '2026-05-15 19:33:10'),
(43, 28, 'Pangani District', 'TG-06', '2026-05-15 19:33:10'),
(44, 14, 'Morogoro City Council', 'MR-01', '2026-05-15 19:33:10'),
(45, 14, 'Morogoro District', 'MR-02', '2026-05-15 19:33:10'),
(46, 14, 'Gairo District', 'MR-03', '2026-05-15 19:33:10'),
(47, 14, 'Kilosa District', 'MR-04', '2026-05-15 19:33:10'),
(48, 14, 'Mvomero District', 'MR-05', '2026-05-15 19:33:10'),
(49, 14, 'Ulanga District', 'MR-06', '2026-05-15 19:33:10');

-- --------------------------------------------------------

--
-- Table structure for table `donations`
--

CREATE TABLE `donations` (
  `id` int(11) NOT NULL,
  `donation_number` varchar(50) DEFAULT NULL,
  `donor_name` varchar(100) DEFAULT NULL,
  `donor_email` varchar(100) DEFAULT NULL,
  `donor_phone` varchar(20) DEFAULT NULL,
  `donor_type` enum('Individual','Corporate','NGO','Government') DEFAULT 'Individual',
  `amount` decimal(10,2) DEFAULT NULL,
  `currency` enum('TZS','USD','EUR') DEFAULT 'TZS',
  `payment_method` enum('M-Pesa','Bank Transfer','Cash','Card','Airtel Money','Tigo Pesa') DEFAULT 'M-Pesa',
  `transaction_id` varchar(100) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `is_anonymous` tinyint(1) DEFAULT 0,
  `status` enum('Pending','Completed','Failed','Refunded') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `emergency_contacts`
--

CREATE TABLE `emergency_contacts` (
  `id` int(11) NOT NULL,
  `region_id` int(11) DEFAULT NULL,
  `district_id` int(11) DEFAULT NULL,
  `contact_type` enum('Police','Hospital','Fire','Social Welfare','NGO') NOT NULL,
  `contact_name` varchar(150) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `emergency_contacts`
--

INSERT INTO `emergency_contacts` (`id`, `region_id`, `district_id`, `contact_type`, `contact_name`, `phone`, `email`, `address`, `is_active`, `created_at`) VALUES
(1, 1, NULL, 'Police', 'Arusha Police HQ', '0272541234', NULL, NULL, 1, '2026-05-15 19:33:10'),
(2, 1, 1, 'Hospital', 'Arusha Lutheran Medical Centre', '0272545678', NULL, NULL, 1, '2026-05-15 19:33:10'),
(3, 2, NULL, 'Police', 'Dar es Salaam Police HQ', '0222123456', NULL, NULL, 1, '2026-05-15 19:33:10'),
(4, 2, 8, 'Hospital', 'Muhimbili National Hospital', '0222150000', NULL, NULL, 1, '2026-05-15 19:33:10'),
(5, 16, NULL, 'Police', 'Mwanza Police HQ', '0282541234', NULL, NULL, 1, '2026-05-15 19:33:10'),
(6, 16, 13, 'Hospital', 'Bugando Medical Centre', '0282545678', NULL, NULL, 1, '2026-05-15 19:33:10');

-- --------------------------------------------------------

--
-- Table structure for table `found_reports`
--

CREATE TABLE `found_reports` (
  `id` int(11) NOT NULL,
  `case_number` varchar(50) NOT NULL,
  `found_child_name` varchar(100) DEFAULT NULL,
  `approximate_age` int(11) DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `description` text DEFAULT NULL,
  `health_status` enum('Safe','Injured','In Danger','Sick') DEFAULT 'Safe',
  `found_location` varchar(255) DEFAULT NULL,
  `region_id` int(11) DEFAULT NULL,
  `district_id` int(11) DEFAULT NULL,
  `ward_id` int(11) DEFAULT NULL,
  `found_date` datetime DEFAULT NULL,
  `specific_address` text DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `additional_photos` text DEFAULT NULL,
  `finder_name` varchar(100) DEFAULT NULL,
  `finder_phone` varchar(20) DEFAULT NULL,
  `finder_email` varchar(100) DEFAULT NULL,
  `finder_type` enum('Police','Civilian','Social Worker','Medical Staff','Other') DEFAULT NULL,
  `finder_id` int(11) DEFAULT NULL,
  `current_location` varchar(255) DEFAULT NULL,
  `current_care_location_id` int(11) DEFAULT NULL,
  `matched_to_case_id` int(11) DEFAULT NULL,
  `status` enum('Awaiting ID','Reunited','In Care','Transferred') DEFAULT 'Awaiting ID',
  `reunification_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `found_reports`
--

INSERT INTO `found_reports` (`id`, `case_number`, `found_child_name`, `approximate_age`, `gender`, `description`, `health_status`, `found_location`, `region_id`, `district_id`, `ward_id`, `found_date`, `specific_address`, `latitude`, `longitude`, `photo`, `additional_photos`, `finder_name`, `finder_phone`, `finder_email`, `finder_type`, `finder_id`, `current_location`, `current_care_location_id`, `matched_to_case_id`, `status`, `reunification_date`, `created_at`, `updated_at`) VALUES
(1, 'FOUND-2024-001', 'Unknown Female', 5, 'Female', 'Light skin, braided hair, wearing yellow dress with red stripes', 'Safe', 'Mwanza Central Market', 16, 13, NULL, '2024-10-14 09:00:00', NULL, NULL, NULL, NULL, NULL, 'Officer John', '0756789123', NULL, NULL, NULL, 'Mwanza Children Home', NULL, NULL, 'Awaiting ID', NULL, '2026-05-15 19:33:10', '2026-05-15 19:33:10'),
(2, 'FOUND-2026-3919', 'ali', 4, 'Male', 'kavaa kanzu na kofia ', 'Safe', 'njiro', NULL, NULL, NULL, '2026-05-15 23:17:00', NULL, NULL, NULL, '1778876276_1.png', NULL, 'ABDALLA ABRAHMANI ABDALLA', '0775892103', 'husseinali2334@gmail.com', NULL, NULL, 'polisi double b', NULL, NULL, 'Awaiting ID', NULL, '2026-05-15 20:17:56', '2026-05-15 20:17:56'),
(3, 'FOUND-2026-7730', 'ali', 3, 'Male', 'kavaa kanzu na kofia ', 'Safe', 'njiro', NULL, NULL, NULL, '2026-05-15 23:22:00', NULL, NULL, NULL, '1778876570_1.png', NULL, 'Hussein Abdulrahman', '0658216348', 'husseinali2334@gmail.com', NULL, NULL, 'polisi double b', NULL, NULL, 'Awaiting ID', NULL, '2026-05-15 20:22:50', '2026-05-15 20:22:50'),
(4, 'FOUND-2026-8312', 'wakas', 10, 'Male', 'hana nguo', 'In Danger', 'njiro', NULL, NULL, NULL, '2026-05-16 00:12:00', NULL, NULL, NULL, '1778879588_wakas.jpg', NULL, 'KHAMIS ABDALLA ABRAHMANI', '0658216348', 'husseinali2334@gmail.com', NULL, NULL, 'town', NULL, NULL, 'Awaiting ID', NULL, '2026-05-15 21:13:08', '2026-05-15 21:13:08'),
(5, 'FOUND-2026-9216', 'wakas', 10, 'Male', 'kavaa ngendi', 'Safe', 'njiro', NULL, NULL, NULL, '2026-05-16 00:17:00', NULL, NULL, NULL, '1778879878_wakas.jpg', NULL, 'Sultan Abrahman Abdulla', '0773154031', 'mfano@gmail.com', NULL, NULL, 'polisi double b', NULL, NULL, 'Awaiting ID', NULL, '2026-05-15 21:17:58', '2026-05-15 21:17:58');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(200) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `type` enum('info','warning','success','error','alert') DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `link` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `police_stations`
--

CREATE TABLE `police_stations` (
  `id` int(11) NOT NULL,
  `station_name` varchar(150) NOT NULL,
  `region_id` int(11) DEFAULT NULL,
  `district_id` int(11) DEFAULT NULL,
  `ward_id` int(11) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `police_stations`
--

INSERT INTO `police_stations` (`id`, `station_name`, `region_id`, `district_id`, `ward_id`, `address`, `phone`, `email`, `latitude`, `longitude`, `created_at`) VALUES
(1, 'Arusha Central Police Station', NULL, 1, NULL, NULL, '0272541234', NULL, NULL, NULL, '2026-05-15 19:33:10'),
(2, 'Arusha Traffic Police', NULL, 1, NULL, NULL, '0272545678', NULL, NULL, NULL, '2026-05-15 19:33:10'),
(3, 'Ngaramtoni Police Post', NULL, 1, NULL, NULL, '0272551234', NULL, NULL, NULL, '2026-05-15 19:33:10'),
(4, 'Sekei Police Post', NULL, 1, NULL, NULL, '0272561234', NULL, NULL, NULL, '2026-05-15 19:33:10'),
(5, 'Ilala Police Station', NULL, 8, NULL, NULL, '0222123456', NULL, NULL, NULL, '2026-05-15 19:33:10'),
(6, 'Kariakoo Police Post', NULL, 8, NULL, NULL, '0222134567', NULL, NULL, NULL, '2026-05-15 19:33:10'),
(7, 'Kinondoni Police Station', NULL, 9, NULL, NULL, '0222678901', NULL, NULL, NULL, '2026-05-15 19:33:10'),
(8, 'Mwananyamala Police Post', NULL, 9, NULL, NULL, '0222678902', NULL, NULL, NULL, '2026-05-15 19:33:10'),
(9, 'Temeke Police Station', NULL, 10, NULL, NULL, '0222851234', NULL, NULL, NULL, '2026-05-15 19:33:10');

-- --------------------------------------------------------

--
-- Table structure for table `regions`
--

CREATE TABLE `regions` (
  `id` int(11) NOT NULL,
  `region_name` varchar(100) NOT NULL,
  `region_code` varchar(10) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `regions`
--

INSERT INTO `regions` (`id`, `region_name`, `region_code`, `created_at`, `latitude`, `longitude`) VALUES
(1, 'Arusha', '01', '2026-05-15 19:33:10', -3.38690000, 36.68200000),
(2, 'Dar es Salaam', '02', '2026-05-15 19:33:10', -6.79240000, 39.20830000),
(3, 'Dodoma', '03', '2026-05-15 19:33:10', -6.16290000, 35.75160000),
(4, 'Geita', '04', '2026-05-15 19:33:10', NULL, NULL),
(5, 'Iringa', '05', '2026-05-15 19:33:10', NULL, NULL),
(6, 'Kagera', '06', '2026-05-15 19:33:10', NULL, NULL),
(7, 'Katavi', '07', '2026-05-15 19:33:10', NULL, NULL),
(8, 'Kigoma', '08', '2026-05-15 19:33:10', -5.03000000, 32.80000000),
(9, 'Kilimanjaro', '09', '2026-05-15 19:33:10', -3.06740000, 37.35560000),
(10, 'Lindi', '10', '2026-05-15 19:33:10', NULL, NULL),
(11, 'Manyara', '11', '2026-05-15 19:33:10', NULL, NULL),
(12, 'Mara', '12', '2026-05-15 19:33:10', NULL, NULL),
(13, 'Mbeya', '13', '2026-05-15 19:33:10', -8.90510000, 33.48260000),
(14, 'Morogoro', '14', '2026-05-15 19:33:10', -6.83000000, 37.67000000),
(15, 'Mtwara', '15', '2026-05-15 19:33:10', NULL, NULL),
(16, 'Mwanza', '16', '2026-05-15 19:33:10', -2.51640000, 32.91750000),
(17, 'Njombe', '17', '2026-05-15 19:33:10', NULL, NULL),
(18, 'Pemba Kaskazini', '18', '2026-05-15 19:33:10', NULL, NULL),
(19, 'Pemba Kusini', '19', '2026-05-15 19:33:10', NULL, NULL),
(20, 'Pwani', '20', '2026-05-15 19:33:10', NULL, NULL),
(21, 'Rukwa', '21', '2026-05-15 19:33:10', -4.56670000, 33.78330000),
(22, 'Ruvuma', '22', '2026-05-15 19:33:10', -10.50000000, 35.50000000),
(23, 'Shinyanga', '23', '2026-05-15 19:33:10', -3.65000000, 33.41670000),
(24, 'Simiyu', '24', '2026-05-15 19:33:10', NULL, NULL),
(25, 'Singida', '25', '2026-05-15 19:33:10', NULL, NULL),
(26, 'Songwe', '26', '2026-05-15 19:33:10', NULL, NULL),
(27, 'Tabora', '27', '2026-05-15 19:33:10', -4.89510000, 33.59500000),
(28, 'Tanga', '28', '2026-05-15 19:33:10', -5.06890000, 39.09870000),
(29, 'Unguja Kaskazini', '29', '2026-05-15 19:33:10', NULL, NULL),
(30, 'Unguja Kusini', '30', '2026-05-15 19:33:10', NULL, NULL),
(31, 'Unguja Mjini Magharibi', '31', '2026-05-15 19:33:10', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `system_logs`
--

CREATE TABLE `system_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_name` varchar(100) DEFAULT NULL,
  `user_role` varchar(50) DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `action_type` enum('login','logout','create','update','delete','view','export','error','search','report') DEFAULT 'view',
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `page_url` varchar(255) DEFAULT NULL,
  `old_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_data`)),
  `new_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_data`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_logs`
--

INSERT INTO `system_logs` (`id`, `user_id`, `user_name`, `user_role`, `action`, `action_type`, `description`, `ip_address`, `user_agent`, `page_url`, `old_data`, `new_data`, `created_at`) VALUES
(1, 1, 'Administrator', 'admin', 'System Setup', 'create', 'Initial database setup completed', '127.0.0.1', NULL, '/install.php', NULL, NULL, '2026-05-15 19:33:10'),
(2, 1, 'Administrator', 'admin', 'User Registration', 'create', 'Admin user created', '127.0.0.1', NULL, '/register.php', NULL, NULL, '2026-05-15 19:33:10'),
(3, NULL, 'Guest', 'guest', 'Failed Login Attempt', 'error', 'Failed login attempt for email/phone: admin@okoawatoto.com from IP: 127.0.0.1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '/okoaWatoto/login.php', '{\"attempted_email\":\"admin@okoawatoto.com\"}', NULL, '2026-05-15 20:12:57'),
(4, NULL, 'Guest', 'guest', 'Failed Login Attempt', 'error', 'Failed login attempt for email/phone: admin@okoawatoto.com from IP: 127.0.0.1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '/okoaWatoto/login.php', '{\"attempted_email\":\"admin@okoawatoto.com\"}', NULL, '2026-05-15 20:13:08'),
(5, NULL, 'Guest', 'guest', 'Failed Login Attempt', 'error', 'Failed login attempt for email/phone: admin@okoawatoto.com from IP: 127.0.0.1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '/okoaWatoto/login.php', '{\"attempted_email\":\"admin@okoawatoto.com\"}', NULL, '2026-05-15 20:15:35'),
(6, NULL, 'Guest', 'guest', 'Missing Child Report Submitted', 'create', 'New missing child report submitted for Hussein Abdulrahman. Case Number: CASE-2026-1137', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '/okoaWatoto/report-missing.php', NULL, '{\"child_name\":\"Hussein Abdulrahman\",\"age\":4,\"gender\":\"Male\",\"location\":\"Kilombero\",\"case_number\":\"CASE-2026-1137\",\"reporter_name\":\"ABDALLA ABRAHMANI ABDALLA\"}', '2026-05-15 20:29:44'),
(7, NULL, 'Guest', 'guest', 'User Registration', 'create', 'New user registered: Hussein Abdulrahman (husseinali2334@gmail.com)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '/okoaWatoto/register.php', NULL, '{\"fullname\":\"Hussein Abdulrahman\",\"email\":\"husseinali2334@gmail.com\",\"role\":\"user\"}', '2026-05-15 20:40:18'),
(8, NULL, 'Guest', 'guest', 'User Registration', 'create', 'New user registered: Hussein Abdulrahman (husseinali2334@gmail.com)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '/okoaWatoto/register.php', NULL, '{\"fullname\":\"Hussein Abdulrahman\",\"email\":\"husseinali2334@gmail.com\",\"role\":\"user\"}', '2026-05-15 20:44:09'),
(9, 1, 'Administrator', 'admin', 'User Login', 'login', 'User Administrator logged in successfully from IP: 127.0.0.1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '/okoaWatoto/login.php', NULL, '{\"user_id\":1,\"email\":\"admin@okoawatoto.com\",\"role\":\"admin\"}', '2026-05-15 20:49:10'),
(10, 1, 'Administrator', 'admin', 'User Logout', 'logout', 'User Administrator logged out successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '/okoaWatoto/logout.php', NULL, '{\"user_id\":1,\"email\":\"admin@okoawatoto.com\"}', '2026-05-15 21:04:25'),
(11, 1, 'Administrator', 'admin', 'User Login', 'login', 'User Administrator logged in successfully from IP: 127.0.0.1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '/okoaWatoto/login.php', NULL, '{\"user_id\":1,\"email\":\"admin@okoawatoto.com\",\"role\":\"admin\"}', '2026-05-15 21:04:32'),
(12, 1, 'Administrator', 'admin', 'User Logout', 'logout', 'User Administrator logged out successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '/okoaWatoto/logout.php', NULL, '{\"user_id\":1,\"email\":\"admin@okoawatoto.com\"}', '2026-05-15 21:04:48'),
(13, NULL, 'Guest', 'guest', 'Found Child Report Submitted', 'create', 'New found child report submitted. Case Number: FOUND-2026-8312', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '/okoaWatoto/report-found.php', NULL, '{\"found_location\":\"njiro\",\"case_number\":\"FOUND-2026-8312\",\"finder_name\":\"KHAMIS ABDALLA ABRAHMANI\"}', '2026-05-15 21:13:08'),
(14, NULL, 'Guest', 'guest', 'Found Child Report Submitted', 'create', 'New found child report submitted. Case Number: FOUND-2026-9216', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '/okoaWatoto/report-found.php', NULL, '{\"found_location\":\"njiro\",\"case_number\":\"FOUND-2026-9216\",\"finder_name\":\"Sultan Abrahman Abdulla\"}', '2026-05-15 21:17:58'),
(15, NULL, 'Guest', 'guest', 'Missing Child Report Submitted', 'create', 'New missing child report submitted for Sultan Abrahman Abdulla. Case Number: CASE-2026-4404', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '/okoaWatoto/report-missing.php', NULL, '{\"child_name\":\"Sultan Abrahman Abdulla\",\"age\":10,\"gender\":\"Male\",\"location\":\"Kilombero\",\"case_number\":\"CASE-2026-4404\",\"reporter_name\":\"EUTICK JOSEPH TESHA\"}', '2026-05-15 21:20:59'),
(16, 1, 'Administrator', 'admin', 'User Login', 'login', 'User Administrator logged in successfully from IP: 127.0.0.1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '/okoaWatoto/login.php', NULL, '{\"user_id\":1,\"email\":\"admin@okoawatoto.com\",\"role\":\"admin\"}', '2026-05-15 21:23:48'),
(17, 1, 'Administrator', 'admin', 'Case Status Updated', 'update', 'User Administrator changed case CASE-2026-1137 status from Missing to Found', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '/okoaWatoto/admin/cases.php', '{\"old_status\":\"Missing\",\"case_number\":\"CASE-2026-1137\"}', '{\"new_status\":\"Found\",\"updated_by\":\"Administrator\"}', '2026-05-15 21:27:28'),
(18, 1, 'Administrator', 'admin', 'Case Status Updated', 'update', 'User Administrator changed case CASE-2026-1137 status from Found to Missing', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '/okoaWatoto/admin/cases.php', '{\"old_status\":\"Found\",\"case_number\":\"CASE-2026-1137\"}', '{\"new_status\":\"Missing\",\"updated_by\":\"Administrator\"}', '2026-05-15 21:27:39'),
(19, 1, 'Administrator', 'admin', 'Case Status Updated', 'update', 'User Administrator changed case CASE-2026-4404 status from Missing to Reunited', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '/okoaWatoto/admin/cases.php?type=missing&status=all', '{\"old_status\":\"Missing\",\"case_number\":\"CASE-2026-4404\"}', '{\"new_status\":\"Reunited\",\"updated_by\":\"Administrator\"}', '2026-05-15 21:28:09'),
(20, 1, 'Administrator', 'admin', 'Logs Cleaned', 'delete', 'Admin Administrator cleaned logs older than 30 days. Deleted 0 records.', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '/okoaWatoto/admin/logs.php', NULL, '{\"days\":30,\"deleted_count\":0}', '2026-05-15 21:46:57');

-- --------------------------------------------------------

--
-- Table structure for table `system_stats`
--

CREATE TABLE `system_stats` (
  `id` int(11) NOT NULL,
  `stat_name` varchar(100) NOT NULL,
  `stat_value` int(11) DEFAULT 0,
  `stat_description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_stats`
--

INSERT INTO `system_stats` (`id`, `stat_name`, `stat_value`, `stat_description`, `updated_at`) VALUES
(1, 'total_children_reported', 1522, 'Jumla ya watoto walioripotiwa', '2026-05-15 21:20:59'),
(2, 'children_reunited', 1204, 'Watoto waliounganishwa na familia', '2026-05-15 19:33:10'),
(3, 'active_missing_cases', 86, 'Kesi amilifu za watoto wanaotafutwa', '2026-05-15 19:33:10'),
(4, 'active_found_cases', 47, 'Kesi za watoto waliopatikana wakisubiri kutambuliwa', '2026-05-15 19:33:10'),
(5, 'total_users', 25, 'Jumla ya watumiaji waliosajiliwa', '2026-05-15 19:33:10'),
(6, 'total_donations', 128, 'Jumla ya michango iliyopokelewa', '2026-05-15 19:33:10'),
(7, 'avg_response_hours', 24, 'Wastani wa muda wa kujibu (masaa)', '2026-05-15 19:33:10'),
(8, 'police_stations', 150, 'Jumla ya vituo vya polisi vilivyosajiliwa', '2026-05-15 19:33:10');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','staff','user') DEFAULT 'user',
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `remember_token` varchar(255) DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expiry` datetime DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `last_ip` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fullname`, `email`, `phone`, `password`, `role`, `status`, `remember_token`, `token_expiry`, `reset_token`, `reset_expiry`, `last_login`, `last_ip`, `created_at`, `updated_at`) VALUES
(1, 'Administrator', 'admin@okoawatoto.com', '0712345678', '$2a$12$cpsMcCuSr6iQKiRyKC4Qv.IDsWC9KTy2u2ip6GtS3DijpmKNGljhK', 'admin', 'active', NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-15 19:33:10', '2026-05-15 20:37:43'),
(2, 'John Polisi', 'john.polisi@police.go.tz', '0756789123', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', 'active', NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-15 19:33:10', '2026-05-15 19:33:10'),
(4, 'Hussein Abdulrahman', 'husseinali2334@gmail.com', '0775892103', '$2y$10$wYm2i9jrvf21mGX76erSXOVUHBni2g3cQDROUj2ZS7Y0iNNL2O3lS', 'user', 'active', NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-15 20:44:09', '2026-05-15 22:14:16');

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_active_missing_cases`
-- (See below for the actual view)
--
CREATE TABLE `v_active_missing_cases` (
`id` int(11)
,`case_number` varchar(50)
,`child_name` varchar(100)
,`age` int(11)
,`gender` enum('Male','Female','Other')
,`description` text
,`clothing` text
,`health_status` enum('Safe','Injured','In Danger','Unknown')
,`status` enum('Missing','Found','Reunited')
,`last_seen_location` varchar(255)
,`region_id` int(11)
,`district_id` int(11)
,`ward_id` int(11)
,`last_seen_date` datetime
,`specific_address` text
,`latitude` decimal(10,8)
,`longitude` decimal(11,8)
,`photo` varchar(255)
,`additional_photos` text
,`reporter_name` varchar(100)
,`reporter_phone` varchar(20)
,`reporter_email` varchar(100)
,`reporter_type` enum('Parent','Police','Witness','Other','Relative','Teacher')
,`reporter_id` int(11)
,`police_station_id` int(11)
,`investigating_officer` varchar(100)
,`officer_badge` varchar(50)
,`case_priority` enum('High','Medium','Low')
,`created_at` timestamp
,`updated_at` timestamp
,`region_name` varchar(100)
,`district_name` varchar(100)
,`ward_name` varchar(100)
,`police_station` varchar(150)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_monthly_stats`
-- (See below for the actual view)
--
CREATE TABLE `v_monthly_stats` (
`month` varchar(7)
,`total_reports` bigint(21)
,`missing_count` decimal(22,0)
,`found_count` decimal(22,0)
,`reunited_count` decimal(22,0)
);

-- --------------------------------------------------------

--
-- Table structure for table `wards`
--

CREATE TABLE `wards` (
  `id` int(11) NOT NULL,
  `district_id` int(11) NOT NULL,
  `ward_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure for view `v_active_missing_cases`
--
DROP TABLE IF EXISTS `v_active_missing_cases`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_active_missing_cases`  AS SELECT `cr`.`id` AS `id`, `cr`.`case_number` AS `case_number`, `cr`.`child_name` AS `child_name`, `cr`.`age` AS `age`, `cr`.`gender` AS `gender`, `cr`.`description` AS `description`, `cr`.`clothing` AS `clothing`, `cr`.`health_status` AS `health_status`, `cr`.`status` AS `status`, `cr`.`last_seen_location` AS `last_seen_location`, `cr`.`region_id` AS `region_id`, `cr`.`district_id` AS `district_id`, `cr`.`ward_id` AS `ward_id`, `cr`.`last_seen_date` AS `last_seen_date`, `cr`.`specific_address` AS `specific_address`, `cr`.`latitude` AS `latitude`, `cr`.`longitude` AS `longitude`, `cr`.`photo` AS `photo`, `cr`.`additional_photos` AS `additional_photos`, `cr`.`reporter_name` AS `reporter_name`, `cr`.`reporter_phone` AS `reporter_phone`, `cr`.`reporter_email` AS `reporter_email`, `cr`.`reporter_type` AS `reporter_type`, `cr`.`reporter_id` AS `reporter_id`, `cr`.`police_station_id` AS `police_station_id`, `cr`.`investigating_officer` AS `investigating_officer`, `cr`.`officer_badge` AS `officer_badge`, `cr`.`case_priority` AS `case_priority`, `cr`.`created_at` AS `created_at`, `cr`.`updated_at` AS `updated_at`, `r`.`region_name` AS `region_name`, `d`.`district_name` AS `district_name`, `w`.`ward_name` AS `ward_name`, `ps`.`station_name` AS `police_station` FROM ((((`children_reports` `cr` left join `regions` `r` on(`cr`.`region_id` = `r`.`id`)) left join `districts` `d` on(`cr`.`district_id` = `d`.`id`)) left join `wards` `w` on(`cr`.`ward_id` = `w`.`id`)) left join `police_stations` `ps` on(`cr`.`police_station_id` = `ps`.`id`)) WHERE `cr`.`status` = 'Missing' ORDER BY `cr`.`created_at` DESC ;

-- --------------------------------------------------------

--
-- Structure for view `v_monthly_stats`
--
DROP TABLE IF EXISTS `v_monthly_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_monthly_stats`  AS SELECT date_format(`children_reports`.`created_at`,'%Y-%m') AS `month`, count(0) AS `total_reports`, sum(case when `children_reports`.`status` = 'Missing' then 1 else 0 end) AS `missing_count`, sum(case when `children_reports`.`status` = 'Found' then 1 else 0 end) AS `found_count`, sum(case when `children_reports`.`status` = 'Reunited' then 1 else 0 end) AS `reunited_count` FROM `children_reports` GROUP BY date_format(`children_reports`.`created_at`,'%Y-%m') ORDER BY date_format(`children_reports`.`created_at`,'%Y-%m') DESC ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `case_updates`
--
ALTER TABLE `case_updates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_case_id` (`case_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `children_reports`
--
ALTER TABLE `children_reports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `case_number` (`case_number`),
  ADD KEY `ward_id` (`ward_id`),
  ADD KEY `police_station_id` (`police_station_id`),
  ADD KEY `reporter_id` (`reporter_id`),
  ADD KEY `idx_case_number` (`case_number`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_child_name` (`child_name`),
  ADD KEY `idx_region` (`region_id`),
  ADD KEY `idx_district` (`district_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_children_reports_status_created` (`status`,`created_at`),
  ADD KEY `idx_children_reports_region_status` (`region_id`,`status`);

--
-- Indexes for table `districts`
--
ALTER TABLE `districts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_district` (`region_id`,`district_name`),
  ADD KEY `idx_district_name` (`district_name`);

--
-- Indexes for table `donations`
--
ALTER TABLE `donations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `donation_number` (`donation_number`),
  ADD KEY `idx_donor_email` (`donor_email`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `emergency_contacts`
--
ALTER TABLE `emergency_contacts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `district_id` (`district_id`),
  ADD KEY `idx_region` (`region_id`),
  ADD KEY `idx_contact_type` (`contact_type`);

--
-- Indexes for table `found_reports`
--
ALTER TABLE `found_reports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `case_number` (`case_number`),
  ADD KEY `district_id` (`district_id`),
  ADD KEY `ward_id` (`ward_id`),
  ADD KEY `finder_id` (`finder_id`),
  ADD KEY `matched_to_case_id` (`matched_to_case_id`),
  ADD KEY `idx_case_number` (`case_number`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_found_location` (`region_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_found_reports_region_status` (`region_id`,`status`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_is_read` (`is_read`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `police_stations`
--
ALTER TABLE `police_stations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `region_id` (`region_id`),
  ADD KEY `district_id` (`district_id`),
  ADD KEY `ward_id` (`ward_id`),
  ADD KEY `idx_station_name` (`station_name`);

--
-- Indexes for table `regions`
--
ALTER TABLE `regions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `region_name` (`region_name`),
  ADD UNIQUE KEY `region_code` (`region_code`),
  ADD KEY `idx_region_name` (`region_name`);

--
-- Indexes for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action_type` (`action_type`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_ip_address` (`ip_address`),
  ADD KEY `idx_system_logs_created_type` (`created_at`,`action_type`);

--
-- Indexes for table `system_stats`
--
ALTER TABLE `system_stats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `stat_name` (`stat_name`),
  ADD KEY `idx_stat_name` (`stat_name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `wards`
--
ALTER TABLE `wards`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_ward` (`district_id`,`ward_name`),
  ADD KEY `idx_ward_name` (`ward_name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `case_updates`
--
ALTER TABLE `case_updates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `children_reports`
--
ALTER TABLE `children_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `districts`
--
ALTER TABLE `districts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `donations`
--
ALTER TABLE `donations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `emergency_contacts`
--
ALTER TABLE `emergency_contacts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `found_reports`
--
ALTER TABLE `found_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `police_stations`
--
ALTER TABLE `police_stations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `regions`
--
ALTER TABLE `regions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `system_stats`
--
ALTER TABLE `system_stats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `wards`
--
ALTER TABLE `wards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `case_updates`
--
ALTER TABLE `case_updates`
  ADD CONSTRAINT `case_updates_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `children_reports`
--
ALTER TABLE `children_reports`
  ADD CONSTRAINT `children_reports_ibfk_1` FOREIGN KEY (`region_id`) REFERENCES `regions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `children_reports_ibfk_2` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `children_reports_ibfk_3` FOREIGN KEY (`ward_id`) REFERENCES `wards` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `children_reports_ibfk_4` FOREIGN KEY (`police_station_id`) REFERENCES `police_stations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `children_reports_ibfk_5` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `districts`
--
ALTER TABLE `districts`
  ADD CONSTRAINT `districts_ibfk_1` FOREIGN KEY (`region_id`) REFERENCES `regions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `emergency_contacts`
--
ALTER TABLE `emergency_contacts`
  ADD CONSTRAINT `emergency_contacts_ibfk_1` FOREIGN KEY (`region_id`) REFERENCES `regions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `emergency_contacts_ibfk_2` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `found_reports`
--
ALTER TABLE `found_reports`
  ADD CONSTRAINT `found_reports_ibfk_1` FOREIGN KEY (`region_id`) REFERENCES `regions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `found_reports_ibfk_2` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `found_reports_ibfk_3` FOREIGN KEY (`ward_id`) REFERENCES `wards` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `found_reports_ibfk_4` FOREIGN KEY (`finder_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `found_reports_ibfk_5` FOREIGN KEY (`matched_to_case_id`) REFERENCES `children_reports` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `police_stations`
--
ALTER TABLE `police_stations`
  ADD CONSTRAINT `police_stations_ibfk_1` FOREIGN KEY (`region_id`) REFERENCES `regions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `police_stations_ibfk_2` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `police_stations_ibfk_3` FOREIGN KEY (`ward_id`) REFERENCES `wards` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD CONSTRAINT `system_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `wards`
--
ALTER TABLE `wards`
  ADD CONSTRAINT `wards_ibfk_1` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
