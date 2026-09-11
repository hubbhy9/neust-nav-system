-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 29, 2026 at 09:21 AM
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
-- Database: `capstone`
--

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `course_id` int(11) NOT NULL,
  `course_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `offices`
--

CREATE TABLE `offices` (
  `office_id` int(11) NOT NULL,
  `office_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `offices`
--

INSERT INTO `offices` (`office_id`, `office_name`) VALUES
(1, 'Coed Faculty'),
(2, 'CICT Faculty'),
(3, 'CMBT Faculty'),
(4, 'Accreditation'),
(5, 'Administration'),
(6, 'Registrar'),
(7, 'RET'),
(8, 'Dean\'s Office'),
(9, 'Information Desk'),
(10, 'Guidance');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`role_id`, `role_name`) VALUES
(2, 'Admin'),
(1, 'Staff'),
(3, 'Super Admin'),
(0, 'User');

-- --------------------------------------------------------

--
-- Table structure for table `staff_profiles`
--

CREATE TABLE `staff_profiles` (
  `user_id` int(11) NOT NULL,
  `staff_id` varchar(255) NOT NULL,
  `position` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff_profiles`
--

INSERT INTO `staff_profiles` (`user_id`, `staff_id`, `position`) VALUES
(7, 'STF-7', 'Admin'),
(15, 'STF-15', 'Admin'),
(20, 'STF-20', 'Admin'),
(37, 'STF-37', 'Admin');

-- --------------------------------------------------------

--
-- Table structure for table `student_profiles`
--

CREATE TABLE `student_profiles` (
  `user_id` int(11) NOT NULL,
  `student_id` varchar(255) NOT NULL,
  `course_id` int(11) NOT NULL,
  `year_level` int(11) NOT NULL,
  `section` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `middle_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) NOT NULL,
  `suffix` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `pass_word` varchar(255) NOT NULL COMMENT 'to be hashed',
  `role_id` int(11) NOT NULL COMMENT 'Referenced from roles table',
  `office_id` int(11) DEFAULT NULL COMMENT 'referenced from offices table',
  `supervisor_id` varchar(255) DEFAULT NULL COMMENT 'referenced from table staff_profiles',
  `email_verified` tinyint(1) DEFAULT 0 COMMENT 'boolean value true or false',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `first_name`, `middle_name`, `last_name`, `suffix`, `email`, `pass_word`, `role_id`, `office_id`, `supervisor_id`, `email_verified`, `created_at`, `updated_at`) VALUES
(8, 'Cict', '', 'Kevin', '', 'CictKevin@gmail.com', '$2y$10$CsLIzsByAH35Z6NBr9q3bukH7cZRzrKdhUFlt/uhCsgwwjMSIbMT6', 0, 0, NULL, 0, '2026-04-30 02:44:19', '2026-05-04 08:31:27'),
(9, 'Cict', '', 'Mia', '', 'CictMia@gmail.com', '$2y$10$F/PIJsrtlmqNpW6bMiAnjud2bUiYsbzs/Wj/f4M4C9eP6LCZjawcS', 1, 2, NULL, 0, '2026-04-30 02:44:36', '2026-05-02 04:40:53'),
(10, 'Cict', '', 'Leo', '', 'CictLeo@gmail.com', '$2y$10$2tmkoptUxKWEVi6JGJCk1u.4jdhXjkJWQfS7uSBCYcCX2UipxzPli', 3, 2, NULL, 0, '2026-04-30 02:45:03', '2026-05-04 06:39:50'),
(15, 'Admin', '', 'Mary Grace', '', 'AdministrationGrace@gmail.com', '$2y$10$k3yP0K/xQX1YXyYbWBmMpOyCXO57pMFU8vV2qKAo6wo8sHdh/vpJ.', 2, 10, NULL, 0, '2026-04-30 02:51:02', '2026-05-05 04:14:20'),
(16, 'Admin', '', 'Ethan', '', 'AdministrationEthan@gmail.com', '$2y$10$/YbSqkREgOYKh.oGwO/.Qe6M6lOB2PmJas4LOsDa1YFDGxjEMVBs6', 1, 5, NULL, 0, '2026-04-30 02:51:21', '2026-05-02 04:40:53'),
(18, 'Registrar', '', 'Ella', '', 'RegistrarElla@gmail.com', '$2y$10$8bja4mmDNRw4IW8D1G/9E.FYgyg/5qZuPWwyYc3hizZgNXpoO7FV6', 1, 6, NULL, 0, '2026-04-30 02:52:04', '2026-05-02 04:40:53'),
(19, 'Registrar', '', 'Noah', '', 'RegistrarNoah@gmail.com', '$2y$10$JtGVTC2UecS8AkdK5NQKU.aV2VgzZrkeHvLoku2SF/ZUKLdvWawfi', 1, 6, NULL, 0, '2026-04-30 02:52:26', '2026-05-02 04:40:53'),
(23, 'Dean', '', 'Victor', '', 'DeanVictor@gmail.com', '$2y$10$M6bUn.dkptwxT7OywbqkrO31i5kjoLMORqtjA8JEU0LXor8lEgM8.', 1, 8, NULL, 0, '2026-04-30 02:53:39', '2026-05-02 04:40:53'),
(24, 'Dean', '', 'Paula', '', 'DeanPaula@gmail.com', '$2y$10$0ER9vIa/ttylgt.i9YNLqulOc9esr6bTRTTaXc.LpIUSbNJSbZjJK', 1, 8, NULL, 0, '2026-04-30 02:53:56', '2026-05-02 04:40:53'),
(25, 'Dean', '', 'Henry', '', 'DeanHenry@gmail.com', '$2y$10$Rb93miAmldfuiCx06ml9MuJKZsZrsf2Wfv4mJjySZmdMqvv6xSSAu', 1, 8, NULL, 0, '2026-04-30 02:54:13', '2026-05-02 04:40:53'),
(29, 'Guidance', '', 'Dylan', '', 'GuidanceDylan@gmail.com', '$2y$10$DGtYG1bcvdNstq9nvZDsxOACJguq6wvRG74gyit7tCkdsrUra/.IW', 1, 10, NULL, 0, '2026-04-30 02:55:19', '2026-05-02 04:40:53'),
(30, 'Guidance', '', 'Kate', '', 'GuidanceKate@gmail.com', '$2y$10$JV8L.PdgckJxgwqY9Vk24evlPKf113uNmydkJJq7.ebMDGUYmZlBC', 1, 10, NULL, 0, '2026-04-30 02:55:30', '2026-05-02 04:40:53'),
(31, 'Guidance', '', 'Aaron', '', 'GuidanceAaron@gmail.com', '$2y$10$Q1URt2T2cGQMIWk7X8lXQuXt8JYIJm.CAWe1b77M26nR0N3xlCtq.', 1, 10, NULL, 0, '2026-04-30 02:55:59', '2026-05-02 04:40:53'),
(34, 'adsad', '', 'asda', 'd', 'asdasdaa@gmail.vom', '$2y$10$iTWQ7XaV2Yu.P.NvSCNGL.EAQZunC4QvhrVtF2nnN9o8gPUNkxvDq', 0, NULL, NULL, 0, '2026-05-02 04:47:28', '2026-05-02 04:47:28'),
(35, 'asd', '', 'asdasd', '', 'asd@gmail.com', '$2y$10$Mysj7fpdmymmYhZOJo.40.0H3cCFT0EOsBewom3Lh9MgHdtU6BL12', 0, 0, NULL, 0, '2026-05-02 04:48:46', '2026-05-02 04:48:46'),
(36, 'Bryan', '', 'Palero', '', 'Dela@gmail.com', '$2y$10$hkB/6Yyv4YqoRl7.6KPNle1BPZLxRK2F3pQpAHiwza0R2YXDtaSz.', 0, 0, NULL, 0, '2026-05-04 06:49:54', '2026-08-27 04:05:41'),
(37, 'Juan', '', 'Cruz', '', 'cruz@gmail.com', '$2y$10$7iUvQXIpPBPOrm.o3BtWv.s4stw5m/qIkabAfa.DS6tLvqDBsEn5q', 2, 4, NULL, 0, '2026-05-04 06:50:33', '2026-05-04 06:50:48'),
(38, '1', '', 'dasda', '', 'asdasd@lol.com', '$2y$10$2anxKzR4RmrzEh4GrAyj9erOIO/VutVxFOulc8J2LqXfDYtq3Naye', 0, 0, NULL, 0, '2026-05-05 07:49:12', '2026-05-05 07:49:13'),
(40, 'Nathaniel', '', 'Gabrieles', 'jr', 'akoctanil@gmail.com', '$2y$10$ZckTGhMzqKU8jROgUdtpeuuTw0XJ/BaCYvPBZ6S/HNMYmFxpNl0mC', 0, 0, NULL, 0, '2026-05-13 05:27:50', '2026-05-13 05:28:21'),
(41, 'nathaniel', '', 'Gabrieles', '', 'nath@gmail.com', '$2y$10$Hd9r5k6e9FmZtrZ33cSRnegSgwFSnjU7AmBu5gDW7t02WugTtOFIK', 3, 9, NULL, 0, '2026-05-13 07:10:12', '2026-05-13 07:34:24'),
(42, 'na', '', 'ganag', '', 'gmail@gmail.com', '$2y$10$Jgjinbc/tXYRx1h7TaxbGeebWF7ISj0ebMYcC69QRw0Qh2PEA49cy', 0, NULL, NULL, 0, '2026-05-13 07:42:45', '2026-05-13 07:42:45'),
(43, 'sa', '', 'na', '', 'sana@gmail.com', '$2y$10$aQD/IAPUrw8x7lrfD7GHaO6ZIwYH47BGULy.L1o8gJaggReyO6c7y', 0, NULL, NULL, 0, '2026-05-13 07:43:25', '2026-05-13 07:43:25'),
(44, 'lorraine', 'torres', 'lumibao', '', 'lumibaolorraine09@gmail.com', '$2y$10$u2rzEvfJjoTN0YT.pUx.xuyF/a5I4i41JmgShQt.G7Z63QVD/nA0K', 0, NULL, NULL, 0, '2026-06-21 12:26:40', '2026-06-21 12:26:40'),
(45, 'Nathaniel', '', 'Gabrieles', '', 'gab@gmail.com', '$2y$10$qGqFyDu9fxoIg6XKuqkEnunlq2ymSkCiKyILwla9hPkAVSzANOaxO', 0, NULL, NULL, 0, '2026-07-23 05:29:07', '2026-07-23 05:29:07');

--
-- Triggers `users`
--
DELIMITER $$
CREATE TRIGGER `after_user_role_update` AFTER UPDATE ON `users` FOR EACH ROW BEGIN

    IF NEW.role_id <> OLD.role_id AND (NEW.role_id = 1 OR NEW.role_id = 2) THEN
        

        SET @pos = IF(NEW.role_id = 1, 'Staff', 'Admin');
        
       
        INSERT INTO staff_profiles (user_id, staff_id, position)
        VALUES (NEW.user_id, CONCAT('STF-', NEW.user_id), @pos)
        ON DUPLICATE KEY UPDATE position = @pos;
        
    END IF;
END
$$
DELIMITER ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`course_id`);

--
-- Indexes for table `offices`
--
ALTER TABLE `offices`
  ADD PRIMARY KEY (`office_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `staff_profiles`
--
ALTER TABLE `staff_profiles`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `staff_id` (`staff_id`);

--
-- Indexes for table `student_profiles`
--
ALTER TABLE `student_profiles`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `student_id` (`student_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `office_id` (`office_id`),
  ADD KEY `fk_supervisor` (`supervisor_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `course_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `offices`
--
ALTER TABLE `offices`
  MODIFY `office_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
