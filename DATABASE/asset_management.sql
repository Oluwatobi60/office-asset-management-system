-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 15, 2025 at 12:18 AM
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
-- Database: `asset_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `asset_table`
--

CREATE TABLE `asset_table` (
  `id` int(11) NOT NULL,
  `reg_no` varchar(30) NOT NULL,
  `asset_name` varchar(200) NOT NULL,
  `description` varchar(250) NOT NULL,
  `quantity` varchar(20) NOT NULL,
  `category` varchar(30) NOT NULL,
  `dateofpurchase` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `asset_table`
--

INSERT INTO `asset_table` (`id`, `reg_no`, `asset_name`, `description`, `quantity`, `category`, `dateofpurchase`) VALUES
(9, 'NO5567', 'HP G300', 'dhtkymdcxvfdthyy', '10', 'Laptops', '2025-03-30'),
(11, 'BC4986', 'Mouse', 'tjyluiwqxsacdstryiulkj,j,mj', '12', 'Accessories', '2025-03-30'),
(12, 'QD7414', 'HP Printer', 'ukllereweasscadsaiuiulhjk', '1', 'Printers', '2025-06-07'),
(13, 'Z61101', 'Hisense Air Condition', 'ferehtkyukhh,jh,jh,h', '5', 'AC', '2025-03-30'),
(14, '5I9094', 'Office Chair', 'ko;ermwfereoeojeiwe', '2', 'Furniture', '2025-03-30'),
(16, 'J93920', 'UPS', 'dhgjhkjfdgf vbojlkl;', '25', 'Accessories', '2025-04-29'),
(17, 'I22556', 'Office Table', 'In good condition', '7', 'Furniture', '2025-05-05'),
(18, 'QD6963', 'HP Laser MP', 'nwe printer', '3', 'Printers', '2025-05-17'),
(19, 'JI3600', 'Fridge Hisense', 'New Fridge for the HOD offices', '3', 'Fridge', '2025-05-17'),
(20, '2V5245', 'Laptop Battery', 'For all HP product', '6', 'Accessories', '2025-05-21'),
(21, 'O48992', 'Dell E5440', 'ueoitojmeljtopjljvmeeelksmleejgerree rejer;oejo;erig', '6', 'Laptops', '2025-05-24'),
(22, 'BR5140', 'Biro', 'hjgkj', '9', 'Accessories', '2025-06-26');

-- --------------------------------------------------------

--
-- Table structure for table `borrow_table`
--

CREATE TABLE `borrow_table` (
  `id` int(11) NOT NULL,
  `reg_no` varchar(100) NOT NULL,
  `asset_name` varchar(100) NOT NULL,
  `purpose` varchar(100) NOT NULL,
  `quantity` varchar(100) NOT NULL,
  `category` varchar(100) NOT NULL,
  `employee_name` varchar(100) NOT NULL,
  `department` varchar(100) NOT NULL,
  `borrow_date` varchar(100) NOT NULL,
  `borrow_by` varchar(100) NOT NULL,
  `admin_borrow_for` varchar(30) NOT NULL,
  `hod_status` int(11) NOT NULL,
  `pro_status` int(11) NOT NULL,
  `returned` int(11) NOT NULL,
  `returned_date` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `borrow_table`
--

INSERT INTO `borrow_table` (`id`, `reg_no`, `asset_name`, `purpose`, `quantity`, `category`, `employee_name`, `department`, `borrow_date`, `borrow_by`, `admin_borrow_for`, `hod_status`, `pro_status`, `returned`, `returned_date`) VALUES
(29, 'J93920', 'UPS', 'urgent use', '1', 'Accessories', 'Adebayo Toheeb', 'Computer Science', '2025-06-14', 'Teeb', '', 1, 1, 1, '2025-07-14'),
(34, 'QD7414', 'HP Printer', 'Urgent', '1', 'Printers', 'Adebayo Toheeb', 'Computer Science', '2025-07-14', 'Tobestic', 'Adebayo Toheeb', 0, 0, 0, ''),
(35, 'BC4986', 'Mouse', 'kj', '1', 'Accessories', 'Adebayo Toheeb', 'Computer Science', '2025-07-14', 'Teeb', '', 1, 1, 1, '2025-07-15');

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

CREATE TABLE `category` (
  `id` int(11) NOT NULL,
  `category` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`id`, `category`) VALUES
(5, 'Laptops'),
(6, 'Accessories'),
(7, 'AC'),
(8, 'Furniture'),
(13, 'Printers'),
(14, 'Fridge'),
(16, 'Desktop System');

-- --------------------------------------------------------

--
-- Table structure for table `department_borrow_table`
--

CREATE TABLE `department_borrow_table` (
  `id` int(11) NOT NULL,
  `reg_no` varchar(50) DEFAULT NULL,
  `asset_name` varchar(100) DEFAULT NULL,
  `purpose` text DEFAULT NULL,
  `quantity` varchar(10) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `employee_name` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `borrow_by_dept` varchar(100) NOT NULL,
  `borrow_date` varchar(100) DEFAULT NULL,
  `hod_name` varchar(100) NOT NULL,
  `hod_status` int(11) NOT NULL,
  `returned` int(11) NOT NULL,
  `return_date` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `department_borrow_table`
--

INSERT INTO `department_borrow_table` (`id`, `reg_no`, `asset_name`, `purpose`, `quantity`, `category`, `employee_name`, `department`, `borrow_by_dept`, `borrow_date`, `hod_name`, `hod_status`, `returned`, `return_date`) VALUES
(7, 'J93920', 'UPS', 'For urgent use', '1', 'Accessories', 'Dr Sam Ayanfe', 'Computer Science', 'Micro-Biology', '2025-07-14', 'Odeyemi Timothy', 1, 1, '2025-07-14 17:33:15'),
(8, 'QD6963', 'HP Laser MP', 'Urgent Use', '1', 'Printers', 'Odeyemi Timothy', 'Accounting', 'Computer Science', '2025-07-14', 'Dr. Ademola Abraham', 1, 1, '2025-07-14 22:05:40');

-- --------------------------------------------------------

--
-- Table structure for table `department_table`
--

CREATE TABLE `department_table` (
  `id` int(11) NOT NULL,
  `department` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `department_table`
--

INSERT INTO `department_table` (`id`, `department`) VALUES
(1, 'Computer Science'),
(2, 'Accounting'),
(3, 'Micro-Biology'),
(4, 'Law '),
(6, 'Business Administration'),
(7, 'Procurement/Maintenance '),
(8, 'Admin'),
(9, 'Nursing');

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_table`
--

CREATE TABLE `maintenance_table` (
  `id` int(11) NOT NULL,
  `reg_no` varchar(30) NOT NULL,
  `asset_name` varchar(100) NOT NULL,
  `description` varchar(250) NOT NULL,
  `category` varchar(20) NOT NULL,
  `department` varchar(30) NOT NULL,
  `last_service` varchar(20) NOT NULL,
  `next_service` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `repair_asset`
--

CREATE TABLE `repair_asset` (
  `id` int(11) NOT NULL,
  `asset_id` int(11) NOT NULL,
  `reg_no` varchar(100) NOT NULL,
  `asset_name` varchar(255) NOT NULL,
  `department` varchar(100) NOT NULL,
  `reported_by` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(50) DEFAULT 'General',
  `quantity` int(11) DEFAULT 1,
  `report_date` datetime DEFAULT current_timestamp(),
  `status` varchar(50) DEFAULT 'Under Repair'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `repair_asset`
--

INSERT INTO `repair_asset` (`id`, `asset_id`, `reg_no`, `asset_name`, `department`, `reported_by`, `description`, `category`, `quantity`, `report_date`, `status`) VALUES
(10, 16, 'QD7414', 'HP Printer', 'Computer Science', 'Adebayo Toheeb', 'Marked for repair', 'General', 1, '2025-06-14 14:29:07', 'Under Repair');

-- --------------------------------------------------------

--
-- Table structure for table `request_table`
--

CREATE TABLE `request_table` (
  `id` int(11) NOT NULL,
  `reg_no` varchar(30) NOT NULL,
  `asset_name` varchar(100) NOT NULL,
  `description` varchar(250) NOT NULL,
  `quantity` varchar(10) NOT NULL,
  `category` varchar(30) NOT NULL,
  `department` varchar(30) NOT NULL,
  `assigned_employee` varchar(30) NOT NULL,
  `requested_by` varchar(30) NOT NULL,
  `request_date` varchar(30) NOT NULL,
  `hod_approved` int(2) NOT NULL,
  `pro_approved` int(2) NOT NULL,
  `approval_date` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `request_table`
--

INSERT INTO `request_table` (`id`, `reg_no`, `asset_name`, `description`, `quantity`, `category`, `department`, `assigned_employee`, `requested_by`, `request_date`, `hod_approved`, `pro_approved`, `approval_date`) VALUES
(66, 'BC4986', 'Mouse', 'tjyluiwqxsacdstryiulkj,j,mj', '1', 'Accessories', 'Computer Science', 'Adebayo Toheeb', 'Teeb', '2025-06-14', 1, 1, ''),
(67, 'BC4986', 'Mouse', 'tjyluiwqxsacdstryiulkj,j,mj', '1', 'Accessories', 'Computer Science', 'Adebayo Toheeb', 'Tobestic', '2025-07-14', 1, 1, '2025-07-14 09:08:05'),
(68, 'J93920', 'UPS', 'dhgjhkjfdgf vbojlkl;', '1', 'Accessories', 'Computer Science', 'Adebayo Toheeb', 'Tobestic', '2025-07-14', 1, 1, ''),
(69, 'QD6963', 'HP Laser MP', 'nwe printer', '1', 'Printers', 'Accounting', 'Oye Olasunkanmi', 'Ola', '2025-07-14', 1, 1, '');

-- --------------------------------------------------------

--
-- Table structure for table `role`
--

CREATE TABLE `role` (
  `id` int(11) NOT NULL,
  `user_role` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role`
--

INSERT INTO `role` (`id`, `user_role`) VALUES
(1, 'admin'),
(2, 'user'),
(3, 'hod'),
(7, 'procurement');

-- --------------------------------------------------------

--
-- Table structure for table `staff_table`
--

CREATE TABLE `staff_table` (
  `id` int(11) NOT NULL,
  `reg_no` varchar(100) NOT NULL,
  `asset_name` varchar(100) NOT NULL,
  `description` varchar(200) NOT NULL,
  `quantity` varchar(50) NOT NULL,
  `category` varchar(100) NOT NULL,
  `department` varchar(100) NOT NULL,
  `assigned_employee` varchar(100) NOT NULL,
  `requested_by` varchar(100) NOT NULL,
  `request_date` varchar(100) NOT NULL,
  `hod_approved` int(11) NOT NULL,
  `pro_approved` int(11) NOT NULL,
  `approval_date` varchar(100) NOT NULL,
  `status` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff_table`
--

INSERT INTO `staff_table` (`id`, `reg_no`, `asset_name`, `description`, `quantity`, `category`, `department`, `assigned_employee`, `requested_by`, `request_date`, `hod_approved`, `pro_approved`, `approval_date`, `status`) VALUES
(15, 'NO5567', 'HP G300', 'dhtkymdcxvfdthyy', '5', 'Laptops', 'Computer Science', 'Adebayo Toheeb', 'Tobestic', '2025-06-14 14:25:00', 0, 0, '', NULL),
(16, 'QD7414', 'HP Printer', 'ukllereweasscadsaiuiulhjk', '1', 'Printers', 'Computer Science', 'Adebayo Toheeb', 'Tobestic', '2025-06-14 14:25:00', 0, 0, '', 'Under Repair'),
(17, 'BC4986', 'Mouse', 'tjyluiwqxsacdstryiulkj,j,mj', '1', 'Accessories', 'Computer Science', 'Adebayo Toheeb', 'Tobestic', '2025-07-14 10:31:00', 0, 0, '', NULL),
(18, 'QD7414', 'HP Printer', 'ukllereweasscadsaiuiulhjk', '1', 'Printers', 'Computer Science', 'Adebayo Toheeb', 'Tobestic', '2025-07-14 10:31:00', 0, 0, '', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_table`
--

CREATE TABLE `user_table` (
  `id` int(11) NOT NULL,
  `firstname` varchar(100) NOT NULL,
  `lastname` varchar(250) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(30) NOT NULL,
  `role` varchar(30) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `department` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_table`
--

INSERT INTO `user_table` (`id`, `firstname`, `lastname`, `username`, `email`, `password`, `role`, `phone`, `department`) VALUES
(3, 'Odeyemi', 'Timothy', 'Tobestics', 'odeyemioluwatobi60@gmail.com', '12345', 'hod', '08154883262', 'Computer Science'),
(4, 'Oye', 'Olasunkanmi', 'Ola', 'olasunkanmioye17@gmail.com', '12345', 'user', '08143405242', 'Accounting'),
(6, 'Daramola', 'Damola', 'Daraminds', 'daramolaadewunmi@gmail.com', '12345', 'procurement', '08143405244', 'Procurement/Maintenance'),
(7, 'Odeyemi', 'Oluwatobi', 'Tobestic', 'tobestic53@gmail.com', '12345', 'admin', '08143405243', 'Admin'),
(8, 'Adebayo', 'Toheeb', 'Teeb', 'adebayotoheeb199@gmail.com', '12345', 'user', '08143405246', 'Computer Science'),
(9, 'Dr Sam', 'Ayanfe', 'Ayanfe1', 'ayanfe@gmail.com', '12345', 'hod', '08143405248', 'Micro-Biology'),
(10, 'Adeniran', 'Bukunmi', 'Havilla', 'adebiyi@gmail.com', '12345', 'user', '09056134481', 'Law'),
(11, 'Odeyemi', 'Sunkanmi', 'Sunky', 'odeyemi@gmail.com', '12345', 'user', '09035222098', 'Computer Science'),
(12, 'Dr. Ademola', 'Abraham', 'Ademola', 'ade@gmail.com', '12345', 'hod', '08143405249', 'Accounting');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `asset_table`
--
ALTER TABLE `asset_table`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `borrow_table`
--
ALTER TABLE `borrow_table`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `department_borrow_table`
--
ALTER TABLE `department_borrow_table`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `department_table`
--
ALTER TABLE `department_table`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `maintenance_table`
--
ALTER TABLE `maintenance_table`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `repair_asset`
--
ALTER TABLE `repair_asset`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_unique_repair` (`asset_id`,`status`),
  ADD KEY `idx_asset_id` (`asset_id`),
  ADD KEY `idx_reg_no` (`reg_no`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `request_table`
--
ALTER TABLE `request_table`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `role`
--
ALTER TABLE `role`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `staff_table`
--
ALTER TABLE `staff_table`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_table`
--
ALTER TABLE `user_table`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `asset_table`
--
ALTER TABLE `asset_table`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `borrow_table`
--
ALTER TABLE `borrow_table`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `department_borrow_table`
--
ALTER TABLE `department_borrow_table`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `department_table`
--
ALTER TABLE `department_table`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `maintenance_table`
--
ALTER TABLE `maintenance_table`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `repair_asset`
--
ALTER TABLE `repair_asset`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `request_table`
--
ALTER TABLE `request_table`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT for table `role`
--
ALTER TABLE `role`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `staff_table`
--
ALTER TABLE `staff_table`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `user_table`
--
ALTER TABLE `user_table`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `repair_asset`
--
ALTER TABLE `repair_asset`
  ADD CONSTRAINT `repair_asset_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `staff_table` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
