-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 24, 2026 at 06:00 PM
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
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `actor_name` varchar(190) NOT NULL,
  `actor_role` varchar(40) NOT NULL,
  `action` varchar(32) NOT NULL,
  `module` varchar(80) NOT NULL,
  `target_type` varchar(80) DEFAULT NULL,
  `target_id` varchar(100) DEFAULT NULL,
  `target_name` varchar(255) DEFAULT NULL,
  `description` varchar(1000) NOT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `changed_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`changed_fields`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(512) DEFAULT NULL,
  `client_hints` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`client_hints`)),
  `request_method` varchar(10) NOT NULL,
  `request_path` varchar(512) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'success',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `actor_name`, `actor_role`, `action`, `module`, `target_type`, `target_id`, `target_name`, `description`, `old_values`, `new_values`, `changed_fields`, `ip_address`, `user_agent`, `client_hints`, `request_method`, `request_path`, `status`, `created_at`) VALUES
(1, NULL, 'Guest', 'guest', 'LOGIN', 'Authentication', NULL, NULL, NULL, 'Guest session started through the Ionic API.', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.138.0 Chrome/148.0.7778.280 Electron/42.10.0 Safari/537.36', NULL, 'POST', '/checking/api/index.php', 'success', '2026-10-20 20:19:21'),
(3, NULL, 'Guest', 'guest', 'ACCESS_DENIED', 'Document Requests', NULL, NULL, NULL, 'Denied access to administrator document request management.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', NULL, 'GET', '/checking/document-requests.php', 'denied', '2026-10-20 20:33:42'),
(4, NULL, 'Unknown', 'anonymous', 'ACCESS_DENIED', 'Audit Logs', NULL, NULL, NULL, 'Denied access to audit log viewer.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.138.0 Chrome/148.0.7778.280 Electron/42.10.0 Safari/537.36', NULL, 'GET', '/checking/audit-logs.php', 'denied', '2026-10-20 20:33:42'),
(5, 26, 'j r', 'admin', 'LOGIN', 'Authentication', 'user', '26', 'dela@gmail.com', 'User signed in successfully.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', NULL, 'POST', '/checking/functions/do_login.php', 'success', '2026-10-20 20:33:47'),
(7, NULL, 'Unknown', 'anonymous', 'ACCESS_DENIED', 'Audit Logs', NULL, NULL, NULL, 'Denied access to audit log viewer.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444', NULL, 'GET', '/checking/audit-logs.php', 'denied', '2026-10-20 20:35:53'),
(9, NULL, 'Unknown', 'anonymous', 'ACCESS_DENIED', 'Audit Logs', NULL, NULL, NULL, 'Denied access to audit logs because the session is not the configured audit super administrator.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444', NULL, 'GET', '/checking/audit-logs.php', 'denied', '2026-10-20 20:55:15'),
(10, NULL, 'Unknown', 'anonymous', 'ACCESS_DENIED', 'Audit Logs', NULL, NULL, NULL, 'Denied access to audit logs because the session is not the configured audit super administrator.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444', NULL, 'GET', '/checking/audit-logs.php', 'denied', '2026-10-20 21:00:57'),
(11, NULL, 'Guest', 'guest', 'LOGOUT', 'Authentication', NULL, NULL, NULL, 'User ended the Ionic session.', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', NULL, 'POST', '/checking/api/index.php', 'success', '2026-10-20 21:04:26'),
(12, 26, 'j r', 'admin', 'LOGIN', 'Authentication', 'user', '26', 'dela@gmail.com', 'User signed in through the Ionic API.', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', NULL, 'POST', '/checking/api/index.php', 'success', '2026-10-20 21:04:47'),
(13, NULL, 'Unknown', 'anonymous', 'ACCESS_DENIED', 'Audit Logs', NULL, NULL, NULL, 'Denied access to audit logs because the session is not an administrator.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.138.0 Chrome/148.0.7778.280 Electron/42.10.0 Safari/537.36', NULL, 'GET', '/checking/audit-logs.php', 'denied', '2026-10-20 21:17:28'),
(14, NULL, 'Unknown', 'anonymous', 'ACCESS_DENIED', 'Audit Logs', NULL, NULL, NULL, 'Denied access to audit logs because the session is not an administrator.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.138.0 Chrome/148.0.7778.280 Electron/42.10.0 Safari/537.36', NULL, 'GET', '/checking/audit-logs.php', 'denied', '2026-10-20 21:18:56'),
(15, NULL, 'Unknown', 'anonymous', 'ACCESS_DENIED', 'Audit Logs', NULL, NULL, NULL, 'Denied access to audit logs because the session is not an administrator.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444', NULL, 'GET', '/checking/audit-logs.php', 'denied', '2026-10-20 21:19:48'),
(16, 26, 'j r', 'admin', 'UPDATE', 'Map Configuration', 'map_layout', 'map.json', 'Campus map layout', 'Saved campus map layout.', NULL, '{\"node_count\":245,\"edge_count\":311,\"building_count\":9}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', NULL, 'POST', '/checking/map-editor/save_map.php', 'success', '2026-10-20 21:24:01'),
(17, NULL, 'Guest', 'guest', 'LOGIN', 'Authentication', NULL, NULL, NULL, 'Guest session started through the Ionic API.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444', NULL, 'POST', '/checking/api/index.php', 'success', '2026-10-20 21:46:42'),
(18, NULL, 'Guest', 'guest', 'LOGOUT', 'Authentication', NULL, NULL, NULL, 'User ended the Ionic session.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444', NULL, 'POST', '/checking/api/index.php', 'success', '2026-10-20 21:46:42'),
(19, NULL, 'Guest', 'guest', 'LOGIN', 'Authentication', NULL, NULL, NULL, 'Guest session started through the Ionic API.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444', NULL, 'POST', '/checking/api/index.php', 'success', '2026-10-20 21:49:33'),
(20, NULL, 'Guest', 'guest', 'LOGOUT', 'Authentication', NULL, NULL, NULL, 'User ended the Ionic session.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.9444', NULL, 'POST', '/checking/api/index.php', 'success', '2026-10-20 21:49:33'),
(21, 26, 'j r', 'admin', 'LOGOUT', 'Authentication', NULL, NULL, NULL, 'User ended the Ionic session.', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/checking/api/index.php', 'success', '2026-10-20 21:59:01'),
(22, NULL, 'Guest', 'guest', 'LOGIN', 'Authentication', NULL, NULL, NULL, 'Guest session started through the Ionic API.', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/checking/api/index.php', 'success', '2026-10-20 21:59:03'),
(23, 26, 'j r', 'admin', 'LOGOUT', 'Authentication', NULL, NULL, NULL, 'User ended the Ionic session.', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?1\",\"platform\":\"\\\"Android\\\"\"}', 'POST', '/checking/api/index.php', 'success', '2026-10-20 22:08:08'),
(24, NULL, 'Guest', 'guest', 'LOGIN', 'Authentication', NULL, NULL, NULL, 'Guest session started through the Ionic API.', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?1\",\"platform\":\"\\\"Android\\\"\"}', 'POST', '/checking/api/index.php', 'success', '2026-10-20 22:08:11'),
(25, NULL, 'Guest', 'guest', 'LOGOUT', 'Authentication', NULL, NULL, NULL, 'User ended the Ionic session.', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?1\",\"platform\":\"\\\"Android\\\"\"}', 'POST', '/checking/api/index.php', 'success', '2026-10-20 22:18:03'),
(26, 26, 'j r', 'admin', 'LOGIN', 'Authentication', 'user', '26', 'dela@gmail.com', 'User signed in through the Ionic API.', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?1\",\"platform\":\"\\\"Android\\\"\"}', 'POST', '/checking/api/index.php', 'success', '2026-10-20 22:18:06'),
(27, NULL, 'Unknown', 'anonymous', 'ACCESS_DENIED', 'Dashboard', NULL, NULL, NULL, 'Unauthenticated request was redirected from the dashboard.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.138.0 Chrome/148.0.7778.280 Electron/42.10.0 Safari/537.36', NULL, 'GET', '/checking/dashboard.php', 'denied', '2026-10-20 22:35:51'),
(28, NULL, 'Guest', 'guest', 'LOGIN', 'Authentication', NULL, NULL, NULL, 'Guest session started through the Ionic API.', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?1\",\"platform\":\"\\\"Android\\\"\"}', 'POST', '/checking/api/index.php', 'success', '2026-10-20 22:41:07'),
(29, NULL, 'Guest', 'guest', 'LOGOUT', 'Authentication', NULL, NULL, NULL, 'User ended the Ionic session.', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?1\",\"platform\":\"\\\"Android\\\"\"}', 'POST', '/checking/api/index.php', 'success', '2026-10-20 22:41:15'),
(30, 26, 'j r', 'admin', 'LOGIN', 'Authentication', 'user', '26', 'dela@gmail.com', 'User signed in through the Ionic API.', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?1\",\"platform\":\"\\\"Android\\\"\"}', 'POST', '/checking/api/index.php', 'success', '2026-10-20 22:41:17'),
(31, NULL, 'Guest', 'guest', 'LOGOUT', 'Authentication', NULL, NULL, NULL, 'User ended the Ionic session.', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?1\",\"platform\":\"\\\"iOS\\\"\"}', 'POST', '/checking/api/index.php', 'success', '2026-10-20 22:51:01'),
(32, 26, 'j r', 'admin', 'LOGIN', 'Authentication', 'user', '26', 'dela@gmail.com', 'User signed in through the Ionic API.', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?1\",\"platform\":\"\\\"iOS\\\"\"}', 'POST', '/checking/api/index.php', 'success', '2026-10-20 22:51:05'),
(33, 26, 'j r', 'admin', 'LOGIN', 'Authentication', 'user', '26', 'dela@gmail.com', 'User signed in through the Ionic API.', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?1\",\"platform\":\"\\\"Android\\\"\"}', 'POST', '/checking/api/index.php', 'success', '2026-10-20 22:51:28'),
(34, 26, 'j r', 'admin', 'LOGOUT', 'Authentication', NULL, NULL, NULL, 'User ended the Ionic session.', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?1\",\"platform\":\"\\\"iOS\\\"\"}', 'POST', '/checking/api/index.php', 'success', '2026-10-20 23:12:47'),
(35, NULL, 'Guest', 'guest', 'LOGIN', 'Authentication', NULL, NULL, NULL, 'Guest session started through the Ionic API.', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?1\",\"platform\":\"\\\"iOS\\\"\"}', 'POST', '/checking/api/index.php', 'success', '2026-10-20 23:12:49'),
(36, NULL, 'Guest', 'guest', 'CREATE', 'Document Requests', 'document_request', '27', 'Nathaniel tae reyes', 'Submitted a document request containing 1 document(s).', NULL, '{\"document_count\":1,\"document_titles\":[\"ACCEPTANCE OF APPLICANT TO AWARDING OF CONTRACT TO TENANTS IN UNIVERSITY STALLS\"],\"source\":\"guest\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?1\",\"platform\":\"\\\"iOS\\\"\"}', 'POST', '/checking/api/index.php', 'success', '2026-10-20 23:13:24'),
(37, 26, 'j r', 'admin', 'UPDATE', 'Document Requests', 'document_request', '27', 'Nathaniel tae reyes', 'Changed request status from pending to accepted.', '{\"status\":\"pending\"}', '{\"status\":\"accepted\"}', '[\"status\"]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/checking/document-requests.php', 'success', '2026-10-20 23:13:36'),
(38, NULL, 'Unknown', 'anonymous', 'REQUEST_LINK', 'Document Requests', 'document_request', '19', 'Nathaniel tae Gabrieles', 'Requested a replacement link for an accepted document request.', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?1\",\"platform\":\"\\\"Android\\\"\"}', 'POST', '/checking/api/index.php', 'success', '2026-10-20 23:14:17'),
(39, NULL, 'System correction', 'system', 'UPDATE', 'Document Requests', 'document_request', '19', 'Request #19', 'Corrected request status from accepted to pending after requester reported they had not accepted it; revoked accepted access tokens.', '{\"status\":\"accepted\"}', '{\"status\":\"pending\"}', '[\"status\"]', NULL, NULL, NULL, 'CLI', '/', 'success', '2026-10-20 23:38:01'),
(40, 26, 'j r', 'admin', 'UPDATE', 'Document Requests', 'document_request', '19', 'Nathaniel tae Gabrieles', 'Changed request status from pending to accepted.', '{\"status\":\"pending\",\"admin_note\":null}', '{\"status\":\"accepted\",\"admin_note\":\"Status corrected to pending after requester reported the request was not accepted.\"}', '[\"status\",\"admin_note\"]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/checking/document-requests.php', 'success', '2026-10-20 23:39:57'),
(41, NULL, 'System correction', 'system', 'UPDATE', 'Document Requests', 'document_request', '19', 'Request #19', 'Restored pending status after stale Save note form reapplied Accepted; retained the current access link.', '{\"status\":\"accepted\"}', '{\"status\":\"pending\"}', '[\"status\"]', NULL, NULL, NULL, 'CLI', '/', 'success', '2026-10-20 23:52:47'),
(42, 26, 'j r', 'admin', 'UPDATE', 'Document Requests', 'document_request', '19', 'Nathaniel tae Gabrieles', 'Changed request status from pending to accepted.', '{\"status\":\"pending\",\"admin_note\":null}', '{\"status\":\"accepted\",\"admin_note\":\"Status corrected to pending after requester reported the request was not accepted.\"}', '[\"status\",\"admin_note\"]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/checking/document-requests.php', 'success', '2026-10-20 23:54:04'),
(43, NULL, 'Unknown', 'anonymous', 'REQUEST_LINK', 'Document Requests', 'document_request', '19', 'Nathaniel tae Gabrieles', 'Requested a replacement access link for a accepted document request.', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?1\",\"platform\":\"\\\"Android\\\"\"}', 'POST', '/checking/api/index.php', 'success', '2026-10-21 00:03:38'),
(44, 26, 'j r', 'admin', 'LOGOUT', 'Authentication', NULL, NULL, NULL, 'User ended the session.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0', '{\"brands\":\"\\\"Microsoft Edge\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'GET', '/checking/functions/logout.php', 'success', '2026-10-21 00:04:34'),
(45, NULL, 'Guest', 'guest', 'LOGIN', 'Authentication', NULL, NULL, NULL, 'Guest session started.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0', '{\"brands\":\"\\\"Microsoft Edge\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/checking/functions/do_login.php', 'success', '2026-10-21 00:04:37'),
(46, NULL, 'Guest', 'guest', 'ACCESS_DENIED', 'Document Requests', NULL, NULL, NULL, 'Denied access to administrator document request management.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0', '{\"brands\":\"\\\"Microsoft Edge\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'GET', '/checking/document-requests.php', 'denied', '2026-10-21 00:04:37'),
(47, NULL, 'Guest', 'guest', 'CREATE', 'Document Requests', 'document_request', '28', 'john rel reyes', 'Submitted a document request containing 1 document(s).', NULL, '{\"document_count\":1,\"document_titles\":[\"ACCEPTANCE OF APPLICANT TO AWARDING OF CONTRACT TO TENANTS IN UNIVERSITY STALLS\"],\"source\":\"guest\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0', '{\"brands\":\"\\\"Microsoft Edge\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/checking/functions/submit_document_request.php', 'success', '2026-10-21 00:05:14'),
(48, 26, 'j r', 'admin', 'UPDATE', 'Document Requests', 'document_request', '28', 'john rel reyes', 'Changed request status from pending to accepted.', '{\"status\":\"pending\"}', '{\"status\":\"accepted\"}', '[\"status\"]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/checking/document-requests.php', 'success', '2026-10-21 00:05:24'),
(49, 26, 'j r', 'admin', 'UPDATE', 'Document Requests', 'document_request', '28', 'john rel reyes', 'Changed request status from accepted to rejected.', '{\"status\":\"accepted\"}', '{\"status\":\"rejected\"}', '[\"status\"]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/checking/document-requests.php', 'success', '2026-10-21 00:22:16'),
(50, NULL, 'Unknown', 'anonymous', 'REQUEST_LINK', 'Document Requests', 'document_request', '28', 'john rel reyes', 'Requested a replacement access link for a rejected document request.', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?1\",\"platform\":\"\\\"Android\\\"\"}', 'POST', '/checking/api/index.php', 'success', '2026-10-21 00:22:47'),
(51, 26, 'j r', 'admin', 'UPDATE', 'Document Requests', 'document_request', '28', 'john rel reyes', 'Changed request status from rejected to accepted.', '{\"status\":\"rejected\"}', '{\"status\":\"accepted\"}', '[\"status\"]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/checking/document-requests.php', 'success', '2026-10-21 00:22:55'),
(52, NULL, 'Unknown', 'anonymous', 'REQUEST_LINK', 'Document Requests', 'document_request', '28', 'john rel reyes', 'Requested a replacement access link for a accepted document request.', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?1\",\"platform\":\"\\\"Android\\\"\"}', 'POST', '/checking/api/index.php', 'success', '2026-10-21 00:24:43'),
(53, NULL, 'Unknown', 'anonymous', 'REQUEST_LINK', 'Document Requests', 'document_request', '28', 'john rel reyes', 'Requested a replacement access link for a accepted document request.', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?1\",\"platform\":\"\\\"Android\\\"\"}', 'POST', '/checking/api/index.php', 'success', '2026-10-21 00:27:28'),
(54, NULL, 'Unknown', 'anonymous', 'REQUEST_LINK', 'Document Requests', 'document_request', '28', 'john rel reyes', 'Requested a replacement access link for a accepted document request.', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?1\",\"platform\":\"\\\"Android\\\"\"}', 'POST', '/checking/api/index.php', 'success', '2026-10-21 00:31:12'),
(55, NULL, 'Guest', 'guest', 'LOGIN', 'Authentication', NULL, NULL, NULL, 'Guest session started through the Ionic API.', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?1\",\"platform\":\"\\\"Android\\\"\"}', 'POST', '/checking/api/index.php', 'success', '2026-10-21 00:32:42'),
(56, NULL, 'Guest', 'guest', 'LOGOUT', 'Authentication', NULL, NULL, NULL, 'User ended the Ionic session.', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?1\",\"platform\":\"\\\"Android\\\"\"}', 'POST', '/checking/api/index.php', 'success', '2026-10-21 00:33:22'),
(57, 26, 'j r', 'admin', 'LOGIN', 'Authentication', 'user', '26', 'dela@gmail.com', 'User signed in through the Ionic API.', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?1\",\"platform\":\"\\\"Android\\\"\"}', 'POST', '/checking/api/index.php', 'success', '2026-10-21 00:33:43'),
(58, 26, 'j r', 'admin', 'LOGOUT', 'Authentication', NULL, NULL, NULL, 'User ended the Ionic session.', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?1\",\"platform\":\"\\\"Android\\\"\"}', 'POST', '/checking/api/index.php', 'success', '2026-10-21 00:34:14'),
(59, NULL, 'Guest', 'guest', 'LOGIN', 'Authentication', NULL, NULL, NULL, 'Guest session started through the Ionic API.', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?1\",\"platform\":\"\\\"Android\\\"\"}', 'POST', '/checking/api/index.php', 'success', '2026-10-21 00:34:15'),
(60, NULL, 'Guest', 'guest', 'REQUEST_LINK', 'Document Requests', 'document_request', '28', 'john rel reyes', 'Requested a replacement access link for a accepted document request.', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?1\",\"platform\":\"\\\"Android\\\"\"}', 'POST', '/checking/api/index.php', 'success', '2026-10-21 00:34:28'),
(61, NULL, 'Guest', 'guest', 'REQUEST_LINK', 'Document Requests', 'document_request', '28', 'john rel reyes', 'Requested a replacement access link for a accepted document request.', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?1\",\"platform\":\"\\\"Android\\\"\"}', 'POST', '/checking/api/index.php', 'success', '2026-10-21 00:37:51'),
(62, 26, 'j r', 'admin', 'UPDATE', 'Document Requests', 'document_request', '28', 'john rel reyes', 'Changed request status from accepted to rejected.', '{\"status\":\"accepted\"}', '{\"status\":\"rejected\"}', '[\"status\"]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/checking/document-requests.php', 'success', '2026-10-21 00:40:30'),
(63, NULL, 'Guest', 'guest', 'REQUEST_LINK', 'Document Requests', 'document_request', '28', 'john rel reyes', 'Requested a replacement access link for a rejected document request.', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?1\",\"platform\":\"\\\"Android\\\"\"}', 'POST', '/checking/api/index.php', 'success', '2026-10-21 00:40:55'),
(64, NULL, 'Unknown', 'anonymous', 'CREATE', 'Document Requests', 'document_request', '30', 'john rel reyes', 'Created a pending follow-up request from a replacement-link request.', NULL, '{\"follow_up_of\":28,\"document_count\":1,\"source\":\"web\"}', NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?1\",\"platform\":\"\\\"Android\\\"\"}', 'POST', '/checking/api/index.php', 'success', '2026-10-21 00:47:37'),
(65, NULL, 'Unknown', 'anonymous', 'REQUEST_LINK', 'Document Requests', 'document_request', '28', 'john rel reyes', 'Created pending follow-up request #30 from a replacement-link request.', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?1\",\"platform\":\"\\\"Android\\\"\"}', 'POST', '/checking/api/index.php', 'success', '2026-10-21 00:47:37'),
(66, 26, 'j r', 'admin', 'UPDATE', 'Document Requests', 'document_request', '30', 'john rel reyes', 'Changed request status from pending to accepted.', '{\"status\":\"pending\"}', '{\"status\":\"accepted\"}', '[\"status\"]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/checking/document-requests.php', 'success', '2026-10-21 00:47:51'),
(67, 26, 'j r', 'admin', 'LOGIN', 'Authentication', 'user', '26', 'dela@gmail.com', 'User signed in through the Ionic API.', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?1\",\"platform\":\"\\\"Android\\\"\"}', 'POST', '/checking/api/index.php', 'success', '2026-10-21 00:51:41'),
(68, NULL, 'Unknown', 'anonymous', 'ACCESS_DENIED', 'Document Requests', NULL, NULL, NULL, 'Denied access to administrator document request management.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.138.0 Chrome/148.0.7778.280 Electron/42.10.0 Safari/537.36', NULL, 'GET', '/checking/document-requests.php', 'denied', '2026-10-21 00:55:22'),
(69, NULL, 'Guest', 'guest', 'LOGIN', 'Authentication', NULL, NULL, NULL, 'Guest session started through the Ionic API.', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 14; SM-F956U) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?1\",\"platform\":\"\\\"Android\\\"\"}', 'POST', '/checking/api/index.php', 'success', '2026-10-21 00:59:32'),
(70, NULL, 'Guest', 'guest', 'LOGOUT', 'Authentication', NULL, NULL, NULL, 'User ended the Ionic session.', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (iPad; CPU OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?1\",\"platform\":\"\\\"iOS\\\"\"}', 'POST', '/checking/api/index.php', 'success', '2026-10-21 00:59:53'),
(71, 26, 'j r', 'admin', 'LOGIN', 'Authentication', 'user', '26', 'dela@gmail.com', 'User signed in through the Ionic API.', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (iPad; CPU OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?1\",\"platform\":\"\\\"iOS\\\"\"}', 'POST', '/checking/api/index.php', 'success', '2026-10-21 01:00:11'),
(72, NULL, 'Guest', 'guest', 'ACCESS_DENIED', 'Document Requests', NULL, NULL, NULL, 'Denied access to administrator document request management.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0', '{\"brands\":\"\\\"Microsoft Edge\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'GET', '/checking/document-requests.php', 'denied', '2026-10-21 01:14:14'),
(73, 24, 'Nath Nath', 'admin', 'CREATE', 'Documents', 'document', '52', 'asdasd', 'Created document and its requirements, steps, and totals.', NULL, '{\"title\":\"asdasd\",\"office\":\"Office of Student Organizations Activities and Development\",\"classification\":\"Complex Transactions\",\"who_may_avail\":\"asdasda\",\"transaction_type_ids\":[7],\"requirements\":[\"asdas\"]}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/final/docs/docxadd.php', 'success', '2026-10-21 10:37:53'),
(74, 24, 'Nath Nath', 'admin', 'DELETE', 'Documents', 'document', '52', 'asdasd', 'Deleted document and its related requirements, steps, and totals.', '{\"title\":\"asdasd\",\"who_may_avail\":\"asdasda\"}', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/final/docs/docxdel.php', 'success', '2026-10-21 10:38:10'),
(75, 24, 'Nath Nath', 'admin', 'UPDATE', 'Users', 'user', '24', 'Nath Nath', 'Changed user role.', '{\"role_id\":0}', '{\"role_id\":1}', '[\"role_id\"]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/final/dashboard.php', 'success', '2026-10-21 10:44:57'),
(76, 24, 'Nath Nath', 'admin', 'DELETE', 'Users', 'user', '1', 'Juan Reyes', 'Deleted a user account.', '{\"email\":\"juan.reyes@example.com\",\"role_id\":0}', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/final/functions/delete.php', 'success', '2026-10-21 10:45:34'),
(77, 24, 'Nath Nath', 'admin', 'UPDATE', 'Settings', 'home_card', 'destinations', 'destinations', 'Updated dashboard home card content.', '{\"content\":{\"title\":\"Quick destinations\",\"subtitle\":\"Open a highlighted location on the campus map\",\"items\":[{\"label\":\"Clinic\",\"target\":\"wp-clinic\"},{\"label\":\"registrar\",\"target\":\"wp-registrar\"},{\"label\":\"Cashier\",\"target\":\"wp-cashier\"}]}}', '{\"content\":{\"title\":\"Quick destinations\",\"subtitle\":\"Open a highlighted location on the campus map\",\"items\":[{\"label\":\"Clinic\",\"target\":\"wp-mv7134ts-sauy6c\"},{\"label\":\"Registrar\",\"target\":\"wp-mv7lb748-du2gm6\"},{\"label\":\"Cashier\",\"target\":\"wp-mv7lbx2e-fvv4vw\"}]}}', '[\"content\"]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/final/dashboard.php', 'success', '2026-10-21 10:49:05'),
(78, 24, 'Nath Nath', 'admin', 'LOGOUT', 'Authentication', NULL, NULL, NULL, 'User ended the session.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'GET', '/final/functions/logout.php', 'success', '2026-10-21 11:33:08'),
(79, NULL, 'Guest', 'guest', 'LOGIN', 'Authentication', NULL, NULL, NULL, 'Guest session started.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/final/functions/do_login.php', 'success', '2026-10-21 11:33:11'),
(80, NULL, 'Guest', 'guest', 'ACCESS_DENIED', 'Dashboard', NULL, NULL, NULL, 'Non-admin attempted to open an administrator dashboard tab.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'GET', '/final/dashboard.php', 'denied', '2026-10-21 11:33:12'),
(81, NULL, 'Guest', 'guest', 'ACCESS_DENIED', 'Document Requests', NULL, NULL, NULL, 'Denied access to administrator document request management.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'GET', '/final/document-requests.php', 'denied', '2026-10-21 11:33:13'),
(82, 24, 'Nath Nath', 'admin', 'LOGIN', 'Authentication', 'user', '24', 'nathaniel.gabrieles69420@gmail.com', 'User signed in successfully.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/final/functions/do_login.php', 'success', '2026-10-21 11:33:33'),
(83, 24, 'Nath Nath', 'admin', 'LOGOUT', 'Authentication', NULL, NULL, NULL, 'User ended the session.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'GET', '/final/functions/logout.php', 'success', '2026-10-21 13:38:57'),
(84, NULL, 'Guest', 'guest', 'LOGIN', 'Authentication', NULL, NULL, NULL, 'Guest session started.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/final/functions/do_login.php', 'success', '2026-10-21 13:39:02'),
(85, NULL, 'Guest', 'guest', 'ACCESS_DENIED', 'Document Requests', NULL, NULL, NULL, 'Denied access to administrator document request management.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'GET', '/final/document-requests.php', 'denied', '2026-10-21 13:39:03'),
(86, 24, 'Nath Nath', 'admin', 'LOGIN', 'Authentication', 'user', '24', 'nathaniel.gabrieles69420@gmail.com', 'User signed in successfully.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/final/functions/do_login.php', 'success', '2026-10-21 13:42:38'),
(87, 24, 'Nath Nath', 'admin', 'LOGOUT', 'Authentication', NULL, NULL, NULL, 'User ended the session.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'GET', '/final/functions/logout.php', 'success', '2026-10-21 13:52:02'),
(88, NULL, 'Guest', 'guest', 'LOGIN', 'Authentication', NULL, NULL, NULL, 'Guest session started.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/final/functions/do_login.php', 'success', '2026-10-21 13:52:05'),
(89, NULL, 'Guest', 'guest', 'ACCESS_DENIED', 'Document Requests', NULL, NULL, NULL, 'Denied access to administrator document request management.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'GET', '/final/document-requests.php', 'denied', '2026-10-21 13:52:06'),
(90, NULL, 'Guest', 'guest', 'ACCESS_DENIED', 'Document Requests', NULL, NULL, NULL, 'Denied access to administrator document request management.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'GET', '/final/document-requests.php', 'denied', '2026-10-21 13:55:23'),
(91, 24, 'Nath Nath', 'admin', 'LOGIN', 'Authentication', 'user', '24', 'nathaniel.gabrieles69420@gmail.com', 'User signed in successfully.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/final/functions/do_login.php', 'success', '2026-10-21 13:55:34'),
(92, 24, 'Nath Nath', 'admin', 'LOGOUT', 'Authentication', NULL, NULL, NULL, 'User ended the session.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'GET', '/final/functions/logout.php', 'success', '2026-10-21 14:00:13'),
(93, 24, 'Nath Nath', 'admin', 'LOGIN', 'Authentication', 'user', '24', 'nathaniel.gabrieles69420@gmail.com', 'User signed in successfully.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/final/functions/do_login.php', 'success', '2026-10-21 14:00:18'),
(94, 24, 'Nath Nath', 'admin', 'LOGOUT', 'Authentication', NULL, NULL, NULL, 'User ended the session.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'GET', '/final/functions/logout.php', 'success', '2026-10-21 14:04:01'),
(95, 24, 'Nath Nath', 'admin', 'LOGIN', 'Authentication', 'user', '24', 'nathaniel.gabrieles69420@gmail.com', 'User signed in successfully.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/final/functions/do_login.php', 'success', '2026-10-21 14:04:08'),
(96, 24, 'Nath Nath', 'admin', 'LOGOUT', 'Authentication', NULL, NULL, NULL, 'User ended the session.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'GET', '/final/functions/logout.php', 'success', '2026-10-21 14:05:35'),
(97, 24, 'Nath Nath', 'admin', 'LOGIN', 'Authentication', 'user', '24', 'nathaniel.gabrieles69420@gmail.com', 'User signed in successfully.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/final/functions/do_login.php', 'success', '2026-10-21 14:05:41'),
(98, 24, 'Nath Nath', 'admin', 'LOGOUT', 'Authentication', NULL, NULL, NULL, 'User ended the session.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'GET', '/final/functions/logout.php', 'success', '2026-10-21 14:05:44'),
(99, NULL, 'Guest', 'guest', 'LOGIN', 'Authentication', NULL, NULL, NULL, 'Guest session started.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/final/functions/do_login.php', 'success', '2026-10-21 14:05:46'),
(100, NULL, 'Guest', 'guest', 'ACCESS_DENIED', 'Document Requests', NULL, NULL, NULL, 'Denied access to administrator document request management.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'GET', '/final/document-requests.php', 'denied', '2026-10-21 14:05:47'),
(101, 24, 'Nath Nath', 'admin', 'LOGIN', 'Authentication', 'user', '24', 'nathaniel.gabrieles69420@gmail.com', 'User signed in successfully.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/final/functions/do_login.php', 'success', '2026-10-21 14:05:57');
INSERT INTO `audit_logs` (`id`, `user_id`, `actor_name`, `actor_role`, `action`, `module`, `target_type`, `target_id`, `target_name`, `description`, `old_values`, `new_values`, `changed_fields`, `ip_address`, `user_agent`, `client_hints`, `request_method`, `request_path`, `status`, `created_at`) VALUES
(102, 24, 'Nath Nath', 'admin', 'LOGOUT', 'Authentication', NULL, NULL, NULL, 'User ended the session.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'GET', '/navi/functions/logout.php', 'success', '2026-10-22 09:39:35'),
(103, NULL, 'Guest', 'guest', 'LOGIN', 'Authentication', NULL, NULL, NULL, 'Guest session started.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navi/functions/do_login.php', 'success', '2026-10-22 09:40:16'),
(104, NULL, 'Guest', 'guest', 'ACCESS_DENIED', 'Document Requests', NULL, NULL, NULL, 'Denied access to administrator document request management.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'GET', '/navi/document-requests.php', 'denied', '2026-10-22 09:40:23'),
(105, 26, 'j r', 'admin', 'LOGIN', 'Authentication', 'user', '26', 'dela@gmail.com', 'User signed in successfully.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navi/functions/do_login.php', 'success', '2026-10-22 09:46:06'),
(106, 26, 'j r', 'admin', 'LOGOUT', 'Authentication', NULL, NULL, NULL, 'User ended the session.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'GET', '/navi/functions/logout.php', 'success', '2026-10-22 09:46:16'),
(107, NULL, 'Guest', 'guest', 'LOGIN', 'Authentication', NULL, NULL, NULL, 'Guest session started.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navi/functions/do_login.php', 'success', '2026-10-22 09:57:28'),
(108, NULL, 'Guest', 'guest', 'ACCESS_DENIED', 'Document Requests', NULL, NULL, NULL, 'Denied access to administrator document request management.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'GET', '/navi/document-requests.php', 'denied', '2026-10-22 09:57:31'),
(109, NULL, 'Guest', 'guest', 'LOGIN', 'Authentication', NULL, NULL, NULL, 'Guest session started.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navi/functions/do_login.php', 'success', '2026-10-22 09:57:33'),
(110, NULL, 'Guest', 'guest', 'ACCESS_DENIED', 'Document Requests', NULL, NULL, NULL, 'Denied access to administrator document request management.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'GET', '/navi/document-requests.php', 'denied', '2026-10-22 09:57:35'),
(111, NULL, 'Guest', 'guest', 'ACCESS_DENIED', 'Document Requests', NULL, NULL, NULL, 'Denied access to administrator document request management.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'GET', '/navi/document-requests.php', 'denied', '2026-10-22 09:58:00'),
(112, 24, 'Nath Nath', 'admin', 'LOGIN', 'Authentication', 'user', '24', 'nathaniel.gabrieles69420@gmail.com', 'User signed in successfully.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navi/functions/do_login.php', 'success', '2026-10-22 10:03:37'),
(113, 24, 'Nath Nath', 'admin', 'LOGIN', 'Authentication', 'user', '24', 'nathaniel.gabrieles69420@gmail.com', 'User signed in successfully.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navi/functions/do_login.php', 'success', '2026-10-22 10:03:43'),
(114, 24, 'Nath Nath', 'admin', 'LOGOUT', 'Authentication', NULL, NULL, NULL, 'User ended the session.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'GET', '/navi/functions/logout.php', 'success', '2026-10-22 10:31:06'),
(115, 24, 'Nath Nath', 'admin', 'LOGIN', 'Authentication', 'user', '24', 'nathaniel.gabrieles69420@gmail.com', 'User signed in successfully.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navi/functions/do_login.php', 'success', '2026-10-22 10:32:13'),
(116, 24, 'Nath Nath', 'admin', 'LOGOUT', 'Authentication', NULL, NULL, NULL, 'User ended the session.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'GET', '/navi/functions/logout.php', 'success', '2026-10-22 10:32:18'),
(117, 24, 'Nath Nath', 'admin', 'LOGIN', 'Authentication', 'user', '24', 'nathaniel.gabrieles69420@gmail.com', 'User signed in successfully.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navi/functions/do_login.php', 'success', '2026-10-22 10:37:02'),
(118, 24, 'Nath Nath', 'admin', 'UPDATE', 'Map Configuration', 'map_layout', 'map.json', 'Campus map layout', 'Saved campus map layout.', NULL, '{\"node_count\":245,\"edge_count\":311,\"building_count\":9}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navi/map-editor/save_map.php', 'success', '2026-10-22 10:55:34'),
(119, 24, 'Nath Nath', 'admin', 'UPDATE', 'Map Configuration', 'map_layout', 'map.json', 'Campus map layout', 'Saved campus map layout.', NULL, '{\"node_count\":245,\"edge_count\":311,\"building_count\":9}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navi/map-editor/save_map.php', 'success', '2026-10-22 11:00:55'),
(120, 24, 'Nath Nath', 'admin', 'LOGOUT', 'Authentication', NULL, NULL, NULL, 'User ended the session.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'GET', '/navi/functions/logout.php', 'success', '2026-10-22 11:24:28'),
(121, 24, 'Nath Nath', 'admin', 'LOGIN', 'Authentication', 'user', '24', 'nathaniel.gabrieles69420@gmail.com', 'User signed in successfully.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navi/functions/do_login.php', 'success', '2026-10-22 11:24:32'),
(122, 24, 'Nath Nath', 'admin', 'UPDATE', 'Map Configuration', 'map_layout', 'map.json', 'Campus map layout', 'Saved campus map layout.', NULL, '{\"node_count\":245,\"edge_count\":311,\"building_count\":9}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navi/map-editor/save_map.php', 'success', '2026-10-22 19:06:22'),
(123, 24, 'Nath Nath', 'admin', 'UPDATE', 'Map Configuration', 'map_layout', 'map.json', 'Campus map layout', 'Saved campus map layout.', NULL, '{\"node_count\":245,\"edge_count\":311,\"building_count\":9}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navi/map-editor/save_map.php', 'success', '2026-10-22 19:07:21'),
(124, 24, 'Nath Nath', 'admin', 'UPDATE', 'Map Configuration', 'map_layout', 'map.json', 'Campus map layout', 'Saved campus map layout.', NULL, '{\"node_count\":245,\"edge_count\":313,\"building_count\":9}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navi/map-editor/save_map.php', 'success', '2026-10-22 19:16:13'),
(125, 24, 'Nath Nath', 'admin', 'UPDATE', 'Map Configuration', 'map_layout', 'map.json', 'Campus map layout', 'Saved campus map layout.', NULL, '{\"node_count\":245,\"edge_count\":313,\"building_count\":9}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navi/map-editor/save_map.php', 'success', '2026-10-22 19:17:21'),
(126, 24, 'Nath Nath', 'admin', 'UPDATE', 'Map Configuration', 'map_layout', 'map.json', 'Campus map layout', 'Saved campus map layout.', NULL, '{\"node_count\":245,\"edge_count\":313,\"building_count\":9}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navi/map-editor/save_map.php', 'success', '2026-10-22 19:20:25'),
(127, 24, 'Nath Nath', 'admin', 'LOGOUT', 'Authentication', NULL, NULL, NULL, 'User ended the session.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'GET', '/navi/functions/logout.php', 'success', '2026-10-22 19:49:18'),
(128, 24, 'Nath Nath', 'admin', 'LOGIN', 'Authentication', 'user', '24', 'nathaniel.gabrieles69420@gmail.com', 'User signed in successfully.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navi/functions/do_login.php', 'success', '2026-10-22 19:52:10'),
(129, 24, 'Nath Nath', 'admin', 'LOGOUT', 'Authentication', NULL, NULL, NULL, 'User ended the session.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'GET', '/navi/functions/logout.php', 'success', '2026-10-22 19:53:11'),
(130, 24, 'Nath Nath', 'admin', 'LOGIN', 'Authentication', 'user', '24', 'nathaniel.gabrieles69420@gmail.com', 'User signed in successfully.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navigation/functions/do_login.php', 'success', '2026-10-24 18:46:51'),
(131, 24, 'Nath Nath', 'admin', 'LOGOUT', 'Authentication', NULL, NULL, NULL, 'User ended the session.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'GET', '/navigation/functions/logout.php', 'success', '2026-10-24 18:48:06'),
(132, NULL, 'Guest', 'guest', 'LOGIN', 'Authentication', NULL, NULL, NULL, 'Guest session started.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navigation/functions/do_login.php', 'success', '2026-10-24 18:48:10'),
(133, NULL, 'Guest', 'guest', 'ACCESS_DENIED', 'Document Requests', NULL, NULL, NULL, 'Denied access to administrator document request management.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'GET', '/navigation/document-requests.php', 'denied', '2026-10-24 18:48:12'),
(134, 24, 'Nath Nath', 'admin', 'LOGIN', 'Authentication', 'user', '24', 'nathaniel.gabrieles69420@gmail.com', 'User signed in successfully.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navigation/functions/do_login.php', 'success', '2026-10-24 18:48:51'),
(135, 24, 'Nath Nath', 'admin', 'DELETE', 'Documents', 'document', '55', 'Application for Certificate of Indigency', 'Deleted document and its related requirements, steps, and totals.', '{\"title\":\"Application for Certificate of Indigency\",\"who_may_avail\":\"Low-Income \\/ Indigent Residents\"}', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navigation/docs/docxdel.php', 'success', '2026-10-24 19:06:09'),
(136, 24, 'Nath Nath', 'admin', 'DELETE', 'Documents', 'document', '54', 'Issuance of Barangay Clearance', 'Deleted document and its related requirements, steps, and totals.', '{\"title\":\"Issuance of Barangay Clearance\",\"who_may_avail\":\"Barangay Residents\"}', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navigation/docs/docxdel.php', 'success', '2026-10-24 19:06:23'),
(137, 24, 'Nath Nath', 'admin', 'DELETE', 'Documents', 'document', '53', 'Application for Business Permit (New)', 'Deleted document and its related requirements, steps, and totals.', '{\"title\":\"Application for Business Permit (New)\",\"who_may_avail\":\"Business Owners \\/ Sole Proprietors \\/ Corporations\"}', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navigation/docs/docxdel.php', 'success', '2026-10-24 19:06:23'),
(138, 24, 'Nath Nath', 'admin', 'DELETE', 'Users', 'user', '22', 'Christian Dela Cruz', 'Moved a user account to the deleted users archive.', '{\"email\":\"crzyme33@gmail.com\",\"role_id\":0}', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navigation/functions/delete.php', 'success', '2026-10-24 19:58:03'),
(139, 24, 'Nath Nath', 'admin', 'DELETE', 'Documents', 'document', '58', 'Application for Certificate of Indigency', 'Deleted document and its related requirements, steps, and totals.', '{\"title\":\"Application for Certificate of Indigency\",\"who_may_avail\":\"Low-Income \\/ Indigent Residents\"}', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navigation/docs/docxdel.php', 'success', '2026-10-24 21:14:48'),
(140, 24, 'Nath Nath', 'admin', 'DELETE', 'Documents', 'document', '57', 'Issuance of Barangay Clearance', 'Deleted document and its related requirements, steps, and totals.', '{\"title\":\"Issuance of Barangay Clearance\",\"who_may_avail\":\"Barangay Residents\"}', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navigation/docs/docxdel.php', 'success', '2026-10-24 21:14:48'),
(141, 24, 'Nath Nath', 'admin', 'DELETE', 'Documents', 'document', '56', 'Application for Business Permit (New)', 'Deleted document and its related requirements, steps, and totals.', '{\"title\":\"Application for Business Permit (New)\",\"who_may_avail\":\"Business Owners \\/ Sole Proprietors \\/ Corporations\"}', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navigation/docs/docxdel.php', 'success', '2026-10-24 21:14:48'),
(142, 24, 'Nath Nath', 'admin', 'DELETE', 'Users', 'user', '25', 'j h', 'Moved a user account to the deleted users archive.', '{\"email\":\"dummy@gmail.com\",\"role_id\":1}', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navigation/functions/delete.php', 'success', '2026-10-24 21:23:03'),
(143, 24, 'Nath Nath', 'admin', 'DELETE', 'Documents', 'document', '61', 'Application for Certificate of Indigency', 'Deleted document and its related requirements, steps, and totals.', '{\"title\":\"Application for Certificate of Indigency\",\"who_may_avail\":\"Low-Income \\/ Indigent Residents\"}', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navigation/docs/docxdel.php', 'success', '2026-10-24 21:36:45'),
(144, 24, 'Nath Nath', 'admin', 'DELETE', 'Documents', 'document', '60', 'Issuance of Barangay Clearance', 'Deleted document and its related requirements, steps, and totals.', '{\"title\":\"Issuance of Barangay Clearance\",\"who_may_avail\":\"Barangay Residents\"}', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navigation/docs/docxdel.php', 'success', '2026-10-24 21:36:46'),
(145, 24, 'Nath Nath', 'admin', 'DELETE', 'Documents', 'document', '59', 'Application for Business Permit (New)', 'Deleted document and its related requirements, steps, and totals.', '{\"title\":\"Application for Business Permit (New)\",\"who_may_avail\":\"Business Owners \\/ Sole Proprietors \\/ Corporations\"}', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navigation/docs/docxdel.php', 'success', '2026-10-24 21:36:46'),
(146, 24, 'Nath Nath', 'admin', 'DELETE', 'Users', 'user', '2', 'Maria Santos', 'Moved a user account to the deleted users archive.', '{\"email\":\"maria.santos@example.com\",\"role_id\":0}', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navigation/functions/delete.php', 'success', '2026-10-24 22:01:26'),
(147, 24, 'Nath Nath', 'admin', 'DELETE', 'Users', 'user', '3', 'Juan Dela Cruz', 'Moved a user account to the deleted users archive.', '{\"email\":\"juan.delacruz@example.com\",\"role_id\":0}', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navigation/functions/delete.php', 'success', '2026-10-24 22:01:27'),
(148, 24, 'Nath Nath', 'admin', 'DELETE', 'Users', 'user', '4', 'Mark Reyes', 'Moved a user account to the deleted users archive.', '{\"email\":\"mark.reyes@example.com\",\"role_id\":0}', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navigation/functions/delete.php', 'success', '2026-10-24 22:01:27'),
(149, 24, 'Nath Nath', 'admin', 'UPDATE', 'Documents', 'document', '64', 'Certificate of Indigency', 'Updated document details, requirements, process steps, or totals.', '{\"title\":\"Application for Certificate of Indigency\"}', '{\"title\":\"Certificate of Indigency\"}', '[\"title\"]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navigation/docs/docxu.php', 'success', '2026-10-24 22:04:07'),
(150, 24, 'Nath Nath', 'admin', 'RESTORE', 'Users', 'user', '4', 'Mark Reyes', 'Restored a user account from the deleted users archive.', '{\"email\":\"mark.reyes@example.com\",\"role_id\":0}', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navigation/functions/delete.php', 'success', '2026-10-24 22:08:19'),
(151, 24, 'Nath Nath', 'admin', 'DELETE', 'Users', 'user', '4', 'Mark Reyes', 'Moved a user account to the deleted users archive.', '{\"email\":\"mark.reyes@example.com\",\"role_id\":0}', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navigation/functions/delete.php', 'success', '2026-10-24 22:08:37'),
(152, 24, 'Nath Nath', 'admin', 'RESTORE', 'Users', 'user', '4', 'Mark Reyes', 'Restored a user account from the deleted users archive.', '{\"email\":\"mark.reyes@example.com\",\"role_id\":0}', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navigation/functions/delete.php', 'success', '2026-10-24 22:08:53'),
(153, 24, 'Nath Nath', 'admin', 'DELETE', 'Users', 'user', '4', 'Mark Reyes', 'Moved a user account to the deleted users archive.', '{\"email\":\"mark.reyes@example.com\",\"role_id\":0}', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navigation/functions/delete.php', 'success', '2026-10-24 22:12:49'),
(154, 24, 'Nath Nath', 'admin', 'RESTORE', 'Users', 'user', '4', 'Mark Reyes', 'Restored a user account from the deleted users archive.', '{\"email\":\"mark.reyes@example.com\",\"role_id\":0}', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navigation/functions/delete.php', 'success', '2026-10-24 22:13:00'),
(155, 24, 'Nath Nath', 'admin', 'DELETE', 'Users', 'user', '4', 'Mark Reyes', 'Moved a user account to the deleted users archive.', '{\"email\":\"mark.reyes@example.com\",\"role_id\":0}', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navigation/functions/delete.php', 'success', '2026-10-24 22:13:08'),
(156, 24, 'Nath Nath', 'admin', 'DELETE', 'Documents', 'document', '64', 'Certificate of Indigency', 'Archived document and moved it to the deleted documents archive.', '{\"title\":\"Certificate of Indigency\",\"who_may_avail\":\"Low-Income \\/ Indigent Residents\"}', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navigation/functions/delete.php', 'success', '2026-10-24 22:13:49'),
(157, 24, 'Nath Nath', 'admin', 'DELETE', 'Documents', 'document', '63', 'Issuance of Barangay Clearance', 'Archived document and moved it to the deleted documents archive.', '{\"title\":\"Issuance of Barangay Clearance\",\"who_may_avail\":\"Barangay Residents\"}', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navigation/functions/delete.php', 'success', '2026-10-24 22:15:55'),
(158, 24, 'Nath Nath', 'admin', 'DELETE', 'Documents', 'document', '62', 'Application for Business Permit (New)', 'Archived document and moved it to the deleted documents archive.', '{\"title\":\"Application for Business Permit (New)\",\"who_may_avail\":\"Business Owners \\/ Sole Proprietors \\/ Corporations\"}', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navigation/functions/delete.php', 'success', '2026-10-24 22:16:33'),
(159, 24, 'Nath Nath', 'admin', 'RESTORE', 'Documents', 'document', '62', 'Application for Business Permit (New)', 'Restored a document from the deleted documents archive.', '{\"title\":\"Application for Business Permit (New)\",\"who_may_avail\":\"Business Owners \\/ Sole Proprietors \\/ Corporations\"}', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navigation/functions/delete.php', 'success', '2026-10-24 22:17:35'),
(160, 24, 'Nath Nath', 'admin', 'DELETE', 'Documents', 'document', '62', 'Application for Business Permit (New)', 'Archived document and moved it to the deleted documents archive.', '{\"title\":\"Application for Business Permit (New)\",\"who_may_avail\":\"Business Owners \\/ Sole Proprietors \\/ Corporations\"}', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navigation/functions/delete.php', 'success', '2026-10-24 22:26:51'),
(161, 24, 'Nath Nath', 'admin', 'CREATE', 'Users', 'user', '28', 'Juan Santos', 'Administrator imported a user account from CSV.', NULL, '{\"email\":\"juan.santos@example.com\",\"role_id\":0,\"source\":\"csv\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navigation/dashboard.php', 'success', '2026-10-24 22:27:49'),
(162, 24, 'Nath Nath', 'admin', 'CREATE', 'Users', 'user', '29', 'Maria Reyes', 'Administrator imported a user account from CSV.', NULL, '{\"email\":\"maria.reyes@example.com\",\"role_id\":0,\"source\":\"csv\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navigation/dashboard.php', 'success', '2026-10-24 22:28:00'),
(163, 24, 'Nath Nath', 'admin', 'CREATE', 'Users', 'user', '30', 'Christian Mendoza', 'Administrator imported a user account from CSV.', NULL, '{\"email\":\"christian.mendoza@example.com\",\"role_id\":0,\"source\":\"csv\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navigation/dashboard.php', 'success', '2026-10-24 22:28:05'),
(164, 24, 'Nath Nath', 'admin', 'CREATE', 'Users', 'user', '31', 'Alyssa Garcia', 'Administrator imported a user account from CSV.', NULL, '{\"email\":\"alyssa.garcia@example.com\",\"role_id\":0,\"source\":\"csv\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navigation/dashboard.php', 'success', '2026-10-24 22:28:10'),
(165, 24, 'Nath Nath', 'admin', 'CREATE', 'Users', 'user', '32', 'Mark Anthony Cruz', 'Administrator imported a user account from CSV.', NULL, '{\"email\":\"mark.cruz@example.com\",\"role_id\":0,\"source\":\"csv\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navigation/dashboard.php', 'success', '2026-10-24 22:28:14'),
(166, 24, 'Nath Nath', 'admin', 'CREATE', 'Users', 'user', '33', 'Bea Patricia Aquino', 'Administrator imported a user account from CSV.', NULL, '{\"email\":\"bea.aquino@example.com\",\"role_id\":0,\"source\":\"csv\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navigation/dashboard.php', 'success', '2026-10-24 22:28:19'),
(167, 24, 'Nath Nath', 'admin', 'DELETE', 'Users', 'user', '28', 'Juan Santos', 'Moved a user account to the deleted users archive.', '{\"email\":\"juan.santos@example.com\",\"role_id\":0}', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navigation/functions/delete.php', 'success', '2026-10-24 22:29:01'),
(168, 24, 'Nath Nath', 'admin', 'DELETE', 'Users', 'user', '29', 'Maria Reyes', 'Moved a user account to the deleted users archive.', '{\"email\":\"maria.reyes@example.com\",\"role_id\":0}', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navigation/functions/delete.php', 'success', '2026-10-24 22:29:01'),
(169, 24, 'Nath Nath', 'admin', 'DELETE', 'Users', 'user', '30', 'Christian Mendoza', 'Moved a user account to the deleted users archive.', '{\"email\":\"christian.mendoza@example.com\",\"role_id\":0}', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navigation/functions/delete.php', 'success', '2026-10-24 22:29:01'),
(170, 24, 'Nath Nath', 'admin', 'DELETE', 'Users', 'user', '31', 'Alyssa Garcia', 'Moved a user account to the deleted users archive.', '{\"email\":\"alyssa.garcia@example.com\",\"role_id\":0}', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navigation/functions/delete.php', 'success', '2026-10-24 22:29:02'),
(171, 24, 'Nath Nath', 'admin', 'DELETE', 'Users', 'user', '32', 'Mark Anthony Cruz', 'Moved a user account to the deleted users archive.', '{\"email\":\"mark.cruz@example.com\",\"role_id\":0}', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navigation/functions/delete.php', 'success', '2026-10-24 22:29:02'),
(172, 24, 'Nath Nath', 'admin', 'DELETE', 'Users', 'user', '33', 'Bea Patricia Aquino', 'Moved a user account to the deleted users archive.', '{\"email\":\"bea.aquino@example.com\",\"role_id\":0}', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navigation/functions/delete.php', 'success', '2026-10-24 22:41:57'),
(173, 24, 'Nath Nath', 'admin', 'DELETE', 'Documents', 'document', '67', 'Application for Certificate of Indigency', 'Archived document and moved it to the deleted documents archive.', '{\"title\":\"Application for Certificate of Indigency\",\"who_may_avail\":\"Low-Income \\/ Indigent Residents\"}', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navigation/functions/delete.php', 'success', '2026-10-24 22:42:35'),
(174, 24, 'Nath Nath', 'admin', 'DELETE', 'Documents', 'document', '66', 'Issuance of Barangay Clearance', 'Archived document and moved it to the deleted documents archive.', '{\"title\":\"Issuance of Barangay Clearance\",\"who_may_avail\":\"Barangay Residents\"}', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navigation/functions/delete.php', 'success', '2026-10-24 22:42:36'),
(175, 24, 'Nath Nath', 'admin', 'DELETE', 'Documents', 'document', '65', 'Application for Business Permit (New)', 'Archived document and moved it to the deleted documents archive.', '{\"title\":\"Application for Business Permit (New)\",\"who_may_avail\":\"Business Owners \\/ Sole Proprietors \\/ Corporations\"}', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navigation/functions/delete.php', 'success', '2026-10-24 22:42:36'),
(176, 24, 'Nath Nath', 'admin', 'CREATE', 'Users', 'user', '34', 'Juan Santos', 'Administrator imported a user account from CSV.', NULL, '{\"email\":\"juan.santos@example.com\",\"role_id\":0,\"source\":\"csv\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navigation/dashboard.php', 'success', '2026-10-24 23:09:15'),
(177, 24, 'Nath Nath', 'admin', 'CREATE', 'Users', 'user', '35', 'Maria Reyes', 'Administrator imported a user account from CSV.', NULL, '{\"email\":\"maria.reyes@example.com\",\"role_id\":0,\"source\":\"csv\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navigation/dashboard.php', 'success', '2026-10-24 23:09:20'),
(178, 24, 'Nath Nath', 'admin', 'CREATE', 'Users', 'user', '36', 'Christian Mendoza', 'Administrator imported a user account from CSV.', NULL, '{\"email\":\"christian.mendoza@example.com\",\"role_id\":0,\"source\":\"csv\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navigation/dashboard.php', 'success', '2026-10-24 23:09:25'),
(179, 24, 'Nath Nath', 'admin', 'CREATE', 'Users', 'user', '37', 'Alyssa Garcia', 'Administrator imported a user account from CSV.', NULL, '{\"email\":\"alyssa.garcia@example.com\",\"role_id\":0,\"source\":\"csv\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navigation/dashboard.php', 'success', '2026-10-24 23:09:29'),
(180, 24, 'Nath Nath', 'admin', 'CREATE', 'Users', 'user', '38', 'Mark Anthony Cruz', 'Administrator imported a user account from CSV.', NULL, '{\"email\":\"mark.cruz@example.com\",\"role_id\":0,\"source\":\"csv\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navigation/dashboard.php', 'success', '2026-10-24 23:09:34'),
(181, 24, 'Nath Nath', 'admin', 'CREATE', 'Users', 'user', '39', 'Bea Patricia Aquino', 'Administrator imported a user account from CSV.', NULL, '{\"email\":\"bea.aquino@example.com\",\"role_id\":0,\"source\":\"csv\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navigation/dashboard.php', 'success', '2026-10-24 23:09:38'),
(182, 24, 'Nath Nath', 'admin', 'DELETE', 'Users', 'user', '39', 'Bea Patricia Aquino', 'Moved a user account to the deleted users archive.', '{\"email\":\"bea.aquino@example.com\",\"role_id\":0}', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navigation/functions/delete.php', 'success', '2026-10-24 23:10:10'),
(183, 24, 'Nath Nath', 'admin', 'LOGOUT', 'Authentication', NULL, NULL, NULL, 'User ended the session.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'GET', '/navigation/functions/logout.php', 'success', '2026-10-24 23:24:58'),
(184, 24, 'Nath Nath', 'admin', 'LOGIN', 'Authentication', 'user', '24', 'nathaniel.gabrieles69420@gmail.com', 'User signed in successfully.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navigation/functions/do_login.php', 'success', '2026-10-24 23:25:05'),
(185, 24, 'Nath Nath', 'admin', 'DELETE', 'Documents', 'document', '70', 'Application for Certificate of Indigency', 'Archived document and moved it to the deleted documents archive.', '{\"title\":\"Application for Certificate of Indigency\",\"who_may_avail\":\"Low-Income \\/ Indigent Residents\"}', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navigation/functions/delete.php', 'success', '2026-10-24 23:34:27'),
(186, 24, 'Nath Nath', 'admin', 'DELETE', 'Documents', 'document', '69', 'Issuance of Barangay Clearance', 'Archived document and moved it to the deleted documents archive.', '{\"title\":\"Issuance of Barangay Clearance\",\"who_may_avail\":\"Barangay Residents\"}', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navigation/functions/delete.php', 'success', '2026-10-24 23:34:30'),
(187, 24, 'Nath Nath', 'admin', 'DELETE', 'Documents', 'document', '68', 'Application for Business Permit (New)', 'Archived document and moved it to the deleted documents archive.', '{\"title\":\"Application for Business Permit (New)\",\"who_may_avail\":\"Business Owners \\/ Sole Proprietors \\/ Corporations\"}', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navigation/functions/delete.php', 'success', '2026-10-24 23:34:32');
INSERT INTO `audit_logs` (`id`, `user_id`, `actor_name`, `actor_role`, `action`, `module`, `target_type`, `target_id`, `target_name`, `description`, `old_values`, `new_values`, `changed_fields`, `ip_address`, `user_agent`, `client_hints`, `request_method`, `request_path`, `status`, `created_at`) VALUES
(188, 24, 'Nath Nath', 'admin', 'RESTORE', 'Documents', 'document', '70', 'Application for Certificate of Indigency', 'Restored a document from the deleted documents archive.', '{\"title\":\"Application for Certificate of Indigency\",\"who_may_avail\":\"Low-Income \\/ Indigent Residents\"}', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navigation/functions/delete.php', 'success', '2026-10-24 23:35:18'),
(189, 24, 'Nath Nath', 'admin', 'DELETE', 'Documents', 'document', '70', 'Application for Certificate of Indigency', 'Archived document and moved it to the deleted documents archive.', '{\"title\":\"Application for Certificate of Indigency\",\"who_may_avail\":\"Low-Income \\/ Indigent Residents\"}', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'POST', '/navigation/functions/delete.php', 'success', '2026-10-24 23:35:32'),
(190, 24, 'Nath Nath', 'admin', 'LOGOUT', 'Authentication', NULL, NULL, NULL, 'User ended the session.', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '{\"brands\":\"\\\"Brave\\\";v=\\\"155\\\", \\\"Chromium\\\";v=\\\"155\\\", \\\"Not(A:Brand\\\";v=\\\"24\\\"\",\"mobile\":\"?0\",\"platform\":\"\\\"Windows\\\"\"}', 'GET', '/navigation/functions/logout.php', 'success', '2026-10-24 23:59:16');

--
-- Triggers `audit_logs`
--
DELIMITER $$
CREATE TRIGGER `audit_logs_block_delete` BEFORE DELETE ON `audit_logs` FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Audit logs are immutable'
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `audit_logs_block_update` BEFORE UPDATE ON `audit_logs` FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Audit logs are immutable'
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `classifications`
--

CREATE TABLE `classifications` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `classifications`
--

INSERT INTO `classifications` (`id`, `name`) VALUES
(4, 'Complex'),
(1, 'Complex Transactions'),
(3, 'Highly Technical Transaction'),
(5, 'Simple'),
(2, 'Simple Transactions');

-- --------------------------------------------------------

--
-- Table structure for table `deleted_documents`
--

CREATE TABLE `deleted_documents` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `office_id` int(11) NOT NULL,
  `classification_id` int(11) NOT NULL,
  `who_may_avail` text DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deleted_document_steps`
--

CREATE TABLE `deleted_document_steps` (
  `id` int(11) NOT NULL,
  `document_id` int(11) NOT NULL,
  `step_number` int(11) NOT NULL,
  `client_step` text DEFAULT NULL,
  `agency_action` text DEFAULT NULL,
  `fees` varchar(1000) DEFAULT 'None',
  `processing_time` varchar(100) DEFAULT NULL,
  `person_responsible` text DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deleted_document_transaction_types`
--

CREATE TABLE `deleted_document_transaction_types` (
  `document_id` int(11) NOT NULL,
  `transaction_type_id` int(11) NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deleted_requirements`
--

CREATE TABLE `deleted_requirements` (
  `id` int(11) NOT NULL,
  `document_id` int(11) NOT NULL,
  `requirement_name` text NOT NULL,
  `where_to_secure` text DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deleted_total`
--

CREATE TABLE `deleted_total` (
  `id` int(11) NOT NULL,
  `document_id` int(11) DEFAULT NULL,
  `fees` varchar(255) DEFAULT NULL,
  `time` varchar(255) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deleted_users`
--

CREATE TABLE `deleted_users` (
  `id` int(11) NOT NULL,
  `registration_number` varchar(50) DEFAULT NULL,
  `student_number` varchar(50) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `barangay` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `sex` varchar(20) DEFAULT NULL,
  `mobile_number` varchar(20) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `password_reset_otp` varchar(255) DEFAULT NULL,
  `password_reset_expires_at` datetime DEFAULT NULL,
  `password_reset_attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `password_reset_locked_until` datetime DEFAULT NULL,
  `password_reset_requested_at` datetime DEFAULT NULL,
  `role_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `office_id` int(11) NOT NULL,
  `classification_id` int(11) NOT NULL,
  `who_may_avail` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `documents`
--

INSERT INTO `documents` (`id`, `title`, `office_id`, `classification_id`, `who_may_avail`) VALUES
(1, 'ADMISSION SERVICES FOR PROSPECTIVE ENROLLEES IN COLLEGE LEVEL', 1, 1, 'Graduates of Senior High School'),
(2, 'ISSUANCE OF CERTIFICATION OF INCLUSION IN THE FREE HIGHER EDUCATION', 2, 2, 'NEUST students'),
(3, 'NEW REGISTRATION OF STUDENT ORGANIZATIONS', 3, 2, 'NEUST students/college or campus student and advisers'),
(4, 'ADMISSION SERVICES FOR PROSPECTIVE ENROLLEES IN JUNIOR HIGH SCHOOL LEVEL', 1, 1, 'Graduates of Elementary Education'),
(5, 'ISSUANCE OF CERTIFICATION OF NO NATIONALLY FUNDED SCHOLARSHIP OR FINANCIAL ASSISTANCE RECEIVED EXCEPT FHE', 2, 2, 'NEUST students'),
(6, 'RENEWAL OF REGISTRATION OF STUDENT ORGANIZATIONS', 3, 2, 'NEUST students/college or campus student and advisers'),
(7, 'ENROLLMENT/REGISTRATION OF GRADE SEVEN (JUNIOR HIGH SCHOOL) STUDENTS', 1, 1, 'Qualifiers for Grade 7 / Junior High School Curricular Program'),
(8, 'APPLICATION FOR UNIFAST TERTIARY EDUCATION SUBSIDY', 2, 2, 'NEUST students'),
(9, 'PROCESSING OF DISBURSEMENT VOUCHERS', 4, 1, 'University Personnel'),
(10, 'ENROLLMENT/REGISTRATION OF NEW COLLEGE STUDENTS', 1, 1, 'Qualified and admitted applicants for college studies'),
(11, 'ONLINE ISSUANCE OF CERTIFICATION OF INCLUSION IN THE FREE HIGHER EDUCATION', 2, 2, 'NEUST students'),
(12, 'CASH ADVANCE PREPARATION', 5, 1, 'University Personnel (Internal Audit Office)'),
(13, 'ENROLLMENT/REGISTRATION OF OLD COLLEGE STUDENTS', 1, 1, 'Old college students (enrolled last semester/year)'),
(14, 'ISSUANCE OF ONLINE CERTIFICATION OF NO NATIONALLY FUNDED SCHOLARSHIP OR FINANCIAL ASSISTANCE RECEIVED EXCEPT FHE', 2, 2, 'NEUST students'),
(15, 'CHECK PREPARATION', 5, 1, 'University Personnel'),
(16, 'ENROLLMENT/REGISTRATION OF NEW GRADUATE STUDENTS', 1, 1, 'New Students in Advance Education (Masteral and Doctoral Programs)'),
(17, 'RELEASING OF CHECKS', 5, 1, 'University Personnel'),
(18, 'ENROLLMENT/REGISTRATION OF OLD STUDENTS IN THE GRADUATE SCHOOL', 1, 1, 'New Students in Advance Education (Masteral and Doctoral Programs)'),
(19, 'ISSUANCE OF LIBRARY CARD', 6, 2, 'NEUST students'),
(20, 'ACCEPTANCE OF APPLICANT TO AWARDING OF CONTRACT TO TENANTS IN UNIVERSITY STALLS', 7, 1, 'Individuals or business entities interested in renting commercial space'),
(21, 'ENROLLMENT/REGISTRATION OF JUNIOR (GRADE 8-10) HIGH SCHOOL STUDENTS', 1, 1, 'Current Junior High School Students'),
(22, 'SIGNING OF LIBRARY CLEARANCE', 6, 2, 'NEUST students'),
(23, 'REQUISITION, DELIVERY, SELLING OF ITEMS SOLD AT THE (EFWRS)', 8, 2, 'Students, University Units/ Colleges, Guests'),
(24, 'ENROLLMENT/REGISTRATION OF SENIOR (GRADE 11-12) HIGH SCHOOL STUDENTS', 1, 1, 'Current Junior High School Students (Completers of Grade 10)'),
(25, 'BORROWING/CHARGING OUT OF LIBRARY MATERIALS', 6, 2, 'NEUST faculty/students'),
(26, 'ACCEPTING OF LETTER OF INTENT TO APPROVAL OF UTILIZATION OF UNIVERSITY FACILITIES', 9, 2, 'External individuals or organizations (citizens, private institutions, government agencies) interested in renting university facilities'),
(27, 'ENROLLMENT/REGISTRATION OF TRANSFEREES', 1, 1, 'New/transferring students from other schools'),
(28, 'RETURNING/CHECKING IN LIBRARY MATERIALS', 6, 2, 'NEUST faculty/students'),
(29, 'UNIVERSITY HOSTEL/DORMITORY ACCOMMODATION', 10, 2, 'Students, Faculty, Staff, and Authorized Guests of the University'),
(30, 'ISSUANCE/RELEASE OF CERTIFICATES', 1, 2, 'Students, Graduates/Alumni'),
(31, 'HEALTH SERVICES', 11, 3, 'Bona fide Students and Personnel of NEUST'),
(32, 'FIRST ISSUANCE OF TRANSCRIPT OF RECORDS (TOR)', 1, 2, 'Students, Graduates/Alumni'),
(33, 'CLAIMING OF LOST AND FOUND ITEMS', 12, 2, 'University personnel, students, Outside Visitors/Guests'),
(34, 'REISSUANCE OF TRANSCRIPT OF RECORDS (TOR) (Special Case - For Retaking the Licensure Examination)', 1, 2, 'Students, Graduates/Alumni'),
(35, 'REQUEST FOR LIBRARY ORIENTATION AND LIBRARY INSTRUCTION SESSION (IN-PERSON)', 6, 2, 'NEUST faculty/students'),
(36, 'REQUEST OF GATE PASS/RFID STICKER', 12, 2, 'University personnel, students and concessionaires'),
(37, 'ISSUANCE OF CERTIFICATION, AUTHENTICATION AND VERIFICATION (CAV)', 1, 1, 'Students, Graduates/Alumni'),
(38, 'REFERENCE/INFORMATION AND BIBLIOGRAPHIC SERVICES', 6, 2, 'NEUST faculty/students'),
(39, 'REVIEW OF CCTV', 12, 2, 'University personnel, students, Outside Visitors/Guests'),
(40, 'REISSUANCE OF ARCHIVED TRANSCRIPT OF RECORDS (TOR)', 1, 1, 'Graduates and Alumni with archived academic records'),
(41, 'USE OF PCS TO ACCESS E-LIBRARY', 6, 2, 'NEUST faculty/students'),
(42, 'ISSUANCE OF IDENTIFICATION CARD', 13, 2, 'University students'),
(43, 'CERTIFICATION OF GOOD MORAL CHARACTER', 14, 2, 'NEUST Students and Graduates'),
(44, 'USE OF NEUST LIBRARY SPACES', 6, 2, 'NEUST faculty/students'),
(45, 'CONDUCT OR SERVE AS RESOURCE PERSON FOR TECHNICAL TRAININGS/WORKSHOPS/SEMINARS', 13, 1, 'University students, Employees, Requesting Agencies'),
(46, 'COUNSELING SERVICE', 14, 2, 'NEUST Students and Graduates'),
(47, 'REGISTRATION OF NON-NEUST USERS TO ACCESS AND USE LIBRARY RESOURCES', 6, 2, 'NEUST students / Graduate students from other universities/schools, private and government researchers who need to have access'),
(48, 'CONSULTANCY SERVICES', 13, 1, 'University students, Employees, Requesting Agencies'),
(49, 'RENEWING BOOKS BORROWED/CHECKED OUT', 6, 2, 'NEUST faculty/students'),
(50, 'REQUESTING/PAGING PERIODICALS/JOURNALS', 6, 2, 'NEUST faculty/students'),
(51, 'ONLINE APPLICATION FOR UNIFAST TERTIARY EDUCATION SUBSIDY', 2, 2, 'NEUST students');

-- --------------------------------------------------------

--
-- Table structure for table `document_requests`
--

CREATE TABLE `document_requests` (
  `request_id` bigint(20) UNSIGNED NOT NULL,
  `request_batch_id` char(36) NOT NULL,
  `requester_user_id` int(11) DEFAULT NULL,
  `fname` varchar(80) DEFAULT NULL,
  `mname` varchar(80) DEFAULT NULL,
  `lname` varchar(80) DEFAULT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `requester_name` varchar(255) NOT NULL,
  `requester_email` varchar(190) NOT NULL,
  `requester_phone` varchar(40) DEFAULT NULL,
  `request_access_token_hash` char(64) DEFAULT NULL,
  `request_access_expires_at` datetime DEFAULT NULL,
  `request_access_origin` varchar(255) DEFAULT NULL,
  `last_access_link_requested_at` datetime DEFAULT NULL,
  `source` enum('web','ionic') NOT NULL,
  `status` enum('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
  `admin_note` text DEFAULT NULL,
  `handled_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `document_requests`
--

INSERT INTO `document_requests` (`request_id`, `request_batch_id`, `requester_user_id`, `fname`, `mname`, `lname`, `suffix`, `requester_name`, `requester_email`, `requester_phone`, `request_access_token_hash`, `request_access_expires_at`, `request_access_origin`, `last_access_link_requested_at`, `source`, `status`, `admin_note`, `handled_by`, `created_at`, `updated_at`) VALUES
(4, '98ddcaa9-936e-44c7-a0c9-204936dfb28a', NULL, 'johnrel', NULL, 'reyes', NULL, 'johnrel reyes', 'johnrelreyes545@gmail.com', NULL, NULL, NULL, NULL, NULL, 'ionic', 'accepted', NULL, 26, '2026-10-17 07:28:47', '2026-10-17 11:58:32'),
(5, '9a839d6a-f33c-4773-a4a9-1ab95ba56ab8', NULL, 'kkenneth', NULL, 'soledadd', NULL, 'kkenneth soledadd', 'idk@gmail.com', NULL, NULL, NULL, NULL, NULL, 'web', 'rejected', NULL, 26, '2026-10-17 07:30:02', '2026-10-20 11:18:16'),
(6, 'e1545b3d-bc3d-4a26-a404-f13f55aa37a8', NULL, 'johnrel', NULL, 'reyes', NULL, 'johnrel reyes', 'johnrel@gmail.com', NULL, NULL, NULL, NULL, NULL, 'ionic', 'rejected', NULL, 26, '2026-10-17 07:47:25', '2026-10-17 12:09:39'),
(7, '888ceef0-5828-4c91-a1a3-7a4c0d55f57a', NULL, 'kkenneth', NULL, 'soledadd', NULL, 'kkenneth soledadd', 'idk@gmail.com', NULL, NULL, NULL, NULL, NULL, 'web', 'accepted', NULL, 26, '2026-10-17 07:54:53', '2026-10-17 09:55:50'),
(15, 'e2cba660-565b-4f54-a1be-d001667a81e1', NULL, 'Nathaniel', 'tae', 'Gabrieles', NULL, 'Nathaniel tae Gabrieles', 'nathenatix@gmail.com', NULL, '3fe0a4195f267830ed245b79b5435f2cfd4d86240eaac7a944001e1bdeec5103', '2026-11-16 18:23:26', 'https://stamp-quantity-opinions-touring.trycloudflare.com', NULL, 'ionic', 'accepted', NULL, 26, '2026-10-17 10:23:26', '2026-10-17 11:31:59'),
(17, '350710fe-4a2b-4f86-ad72-a74529d2de91', NULL, 'meowskie', 'Irinco', 'Soledad', NULL, 'meowskie Irinco Soledad', 'meowskie0004@gmail.com', NULL, '8bdcc001b67d43a7ad1024e718223d7c608e2d4944c46d7448776177e12306b9', '2026-11-16 18:32:40', 'https://stamp-quantity-opinions-touring.trycloudflare.com', NULL, 'ionic', 'accepted', NULL, 26, '2026-10-17 10:32:40', '2026-10-17 10:34:04'),
(18, 'b2a52284-9669-4c4a-a3d6-eadada88021b', NULL, 'Nathaniel', 'tae', 'Gabrieles', NULL, 'Nathaniel tae Gabrieles', 'johnrelreyes545@gmail.com', NULL, 'ba162c62eba86c93aafb4bd31ae20b1a6376b3c74f4d9043b6695a2fa0c1128e', '2026-11-16 19:24:12', 'https://stamp-quantity-opinions-touring.trycloudflare.com', NULL, 'ionic', 'rejected', NULL, 26, '2026-10-17 11:24:12', '2026-10-17 12:03:09'),
(19, 'fb22946b-4a25-46b0-ac94-25ce55f440ed', NULL, 'Nathaniel', 'tae', 'Gabrieles', NULL, 'Nathaniel tae Gabrieles', 'johnrelreyes545@gmail.com', NULL, NULL, NULL, 'https://stamp-quantity-opinions-touring.trycloudflare.com', '2026-10-21 00:03:38', 'ionic', 'accepted', 'Status corrected to pending after requester reported the request was not accepted.', 26, '2026-10-17 11:26:48', '2026-10-20 16:03:38'),
(20, '9d6e1426-9c6d-4ed5-a059-44b79162ff7f', NULL, 'Nathaniel', 'tae', 'Gabrieles', NULL, 'Nathaniel tae Gabrieles', 'johnrelreyes545@gmail.com', NULL, 'c624637a1f6d4f22ff87d22d8fa9fef02e8fbf1feb2d69ecc804e6f6249532c4', '2026-11-16 19:27:09', 'https://stamp-quantity-opinions-touring.trycloudflare.com', NULL, 'ionic', 'accepted', NULL, 26, '2026-10-17 11:27:09', '2026-10-17 11:37:42'),
(21, '39a4fcea-26c1-48c4-ac23-e93c06a71e1f', NULL, 'Nathaniel', 'tae', 'Gabrieles', NULL, 'Nathaniel tae Gabrieles', 'johnrelreyes545@gmail.com', NULL, '42382c9cf097ed9971f47cd460d752926915e893369566f642bc4f37f921d1c1', '2026-11-16 19:28:20', 'https://stamp-quantity-opinions-touring.trycloudflare.com', NULL, 'ionic', 'accepted', NULL, 26, '2026-10-17 11:28:20', '2026-10-17 11:28:41'),
(22, '49b4b643-51df-4902-a67d-961884b06fbb', NULL, 'Nathaniel', 'tae', 'Gabrieles', NULL, 'Nathaniel tae Gabrieles', 'johnrelreyes545@gmail.com', NULL, 'c4eeddf01a8d171f1fded1635d2944430b272cb84b2aa3e1cedde361393db6dc', '2026-10-17 23:04:09', 'https://stamp-quantity-opinions-touring.trycloudflare.com', NULL, 'ionic', 'accepted', NULL, 26, '2026-10-17 12:04:09', '2026-10-17 12:09:32'),
(23, '81d54b04-95fb-4e64-ab2c-ae00123aa2f3', NULL, 'Nathaniel', 'tae', 'Gabrieles', NULL, 'Nathaniel tae Gabrieles', 'nathenatix@gmail.com', NULL, '65e2eaef1ebafc50b329dbddcaee2cd4eef71ff9d7c26591acd2e5fb9be38a26', '2026-10-17 23:09:54', 'https://stamp-quantity-opinions-touring.trycloudflare.com', NULL, 'ionic', 'accepted', NULL, 26, '2026-10-17 12:09:54', '2026-10-20 10:54:50'),
(24, '6225bacb-9069-455f-ac5b-08160b29332c', NULL, 'Nathaniel', 'tae', 'G', NULL, 'Nathaniel tae G', 'johnrelreyes545@gmail.com', NULL, '161dc68c1295c6f9f519292414041f9cd1d5b2f21e2d5245b608511e06085651', '2026-10-17 23:24:12', 'https://stamp-quantity-opinions-touring.trycloudflare.com', NULL, 'ionic', 'accepted', NULL, 26, '2026-10-17 12:24:12', '2026-10-17 12:26:49'),
(27, '0b5329b2-d490-4be8-a735-6680f549ac6c', NULL, 'Nathaniel', 'tae', 'reyes', NULL, 'Nathaniel tae reyes', 'johnrelreyes545@gmail.com', NULL, '6d6dd97f14844f25dabecf7a1752bbe3926942305904acc63ec28c8576ea7ca8', '2026-10-21 02:13:24', 'https://suspended-indicator-automobiles-gospel.trycloudflare.com', NULL, 'ionic', 'accepted', NULL, 26, '2026-10-20 15:13:24', '2026-10-20 15:13:36'),
(28, 'd5385091-775e-47ed-a342-02814698f237', NULL, 'john', 'rel', 'reyes', NULL, 'john rel reyes', 'johnrelreyes545@gmail.com', NULL, '58df979240f086b21ddd7fe9b621de8b422025b2bd8eb446ab47bee536cf4437', '2026-10-21 03:05:14', 'https://suspended-indicator-automobiles-gospel.trycloudflare.com', '2026-10-21 00:47:37', 'web', 'rejected', NULL, 26, '2026-10-20 16:05:14', '2026-10-20 16:47:37'),
(30, '057c3c4a-5140-4ebf-a97e-74a70533a1ee', NULL, 'john', 'rel', 'reyes', NULL, 'john rel reyes', 'johnrelreyes545@gmail.com', NULL, '66077e8e687e6d3facf908a4de740ba3e3d674907dbbe01b1a1db675e26a5589', '2026-10-21 03:47:37', 'https://differ-offline-cad-lopez.trycloudflare.com', NULL, 'web', 'accepted', NULL, 26, '2026-10-20 16:47:37', '2026-10-20 16:47:51');

-- --------------------------------------------------------

--
-- Table structure for table `document_request_access_tokens`
--

CREATE TABLE `document_request_access_tokens` (
  `access_token_id` bigint(20) UNSIGNED NOT NULL,
  `request_id` bigint(20) UNSIGNED NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `access_origin` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `document_request_access_tokens`
--

INSERT INTO `document_request_access_tokens` (`access_token_id`, `request_id`, `token_hash`, `expires_at`, `access_origin`, `created_at`) VALUES
(2, 4, '11ea4205cd3d466703c2f0532b0409f4e269e8a16072985d69317ee920361a7d', '2026-11-16 16:43:13', 'http://localhost', '2026-10-17 08:43:13'),
(3, 15, '465599cb8fcc2d0d1534889cdc31ab3fa2487f09dd243570f12248fee1e7c62f', '2026-11-16 18:23:45', 'https://stamp-quantity-opinions-touring.trycloudflare.com', '2026-10-17 10:23:45'),
(4, 4, '838cf6b33487cf286f1ff8e01b9be8967e41992ea092dfd3d3269b82a897ef14', '2026-11-16 18:24:56', 'https://stamp-quantity-opinions-touring.trycloudflare.com', '2026-10-17 10:24:56'),
(5, 17, '016bdd1ed330761254cd7285c6518224381288ae21fb87327b50b2407b9a58cc', '2026-11-16 18:34:04', 'https://stamp-quantity-opinions-touring.trycloudflare.com', '2026-10-17 10:34:04'),
(6, 4, '50624e9ae4815c6a80b246587b2b28b9f5ce5a82d627f6bec2e2ed1c5bd185a7', '2026-11-16 18:36:53', 'https://stamp-quantity-opinions-touring.trycloudflare.com', '2026-10-17 10:36:53'),
(7, 21, '1aab1f4e6d2d68e3bf69edb6653b6a26bc76d64603d30bd526f9f0248ed26c28', '2026-11-16 19:28:41', 'https://stamp-quantity-opinions-touring.trycloudflare.com', '2026-10-17 11:28:41'),
(8, 15, '8cd9b1d34be75dd1bc0a51347484a4be4dddd0485a834639cb21056f3db5dae4', '2026-11-16 19:31:59', 'https://stamp-quantity-opinions-touring.trycloudflare.com', '2026-10-17 11:31:59'),
(9, 20, '97f3217096ec3eb590f5626b0a561d8d8f81a0d212c71fdb4d36d211c1b1ad68', '2026-11-16 19:32:04', 'https://stamp-quantity-opinions-touring.trycloudflare.com', '2026-10-17 11:32:04'),
(10, 20, '3026255ec69080b29dbb51bebf644be7c4cc54f2f84c88db4b3963980e650699', '2026-11-16 19:37:42', 'https://stamp-quantity-opinions-touring.trycloudflare.com', '2026-10-17 11:37:42'),
(11, 4, '5ac5cca4720b56ed6be57024a982d3d38c0621210afbfb3c8ad1e75893797bd5', '2026-10-17 22:54:04', 'https://stamp-quantity-opinions-touring.trycloudflare.com', '2026-10-17 11:54:04'),
(12, 4, '8da65950a90b9e960cfcf61a4d13c0b750256bd107d37d36aa509eaedc477cef', '2026-10-17 22:58:32', 'https://stamp-quantity-opinions-touring.trycloudflare.com', '2026-10-17 11:58:32'),
(13, 18, 'bb44efb3295a9c79c78e045b9bd1d87168793a97eb81635e5d31b1ef72248078', '2026-10-17 23:03:09', 'https://stamp-quantity-opinions-touring.trycloudflare.com', '2026-10-17 12:03:09'),
(14, 22, '5b560aefe39186933690b47b4ed4a5c5c5f7b610729a0c00ef305627228fc8eb', '2026-10-17 23:09:32', 'https://stamp-quantity-opinions-touring.trycloudflare.com', '2026-10-17 12:09:32'),
(15, 6, '518b6861a039f910245d1d90179cbc7f3f1e7d8108ec1f7b2adfbb30c0e1c59e', '2026-10-17 23:09:39', 'https://stamp-quantity-opinions-touring.trycloudflare.com', '2026-10-17 12:09:39'),
(16, 24, 'd0c4479fe46cc563ad204c18261b457035cda3c4baaa7577c162b634ded09ee0', '2026-10-17 23:26:49', 'https://stamp-quantity-opinions-touring.trycloudflare.com', '2026-10-17 12:26:49'),
(17, 23, '104b6948dbae73016e9c23766137af0c63ece21abc60393a3e4372b6514d8571', '2026-10-20 21:54:50', 'https://suspended-indicator-automobiles-gospel.trycloudflare.com', '2026-10-20 10:54:50'),
(19, 5, '1ad498bed3f2f0c68e393e79695465d8f533c5b104c64d6f943da7f295299cb1', '2026-10-20 22:18:16', 'https://suspended-indicator-automobiles-gospel.trycloudflare.com', '2026-10-20 11:18:16'),
(20, 27, 'd584a2446b4c9a97a9564df854ab2561621e2ee536bb86c2bc539e761ad22437', '2026-10-21 02:13:36', 'https://suspended-indicator-automobiles-gospel.trycloudflare.com', '2026-10-20 15:13:36'),
(23, 19, '7c0f40cb0a5a3d5b70a731d1319efd591c57e4a7102dafa8a13966af9d0408fd', '2026-10-21 02:54:04', 'https://suspended-indicator-automobiles-gospel.trycloudflare.com', '2026-10-20 15:54:04'),
(24, 19, '8f0cb1e1805ec61ad98fd55af1cf8b437f2f4bd7123a607cc65d0c4003b856fa', '2026-10-21 03:03:35', 'https://suspended-indicator-automobiles-gospel.trycloudflare.com', '2026-10-20 16:03:35'),
(34, 28, 'd5df87cdd6669a058c205d45a7b1d5d48763b4415267c700f2997e99e92d7f82', '2026-10-21 03:40:30', 'https://differ-offline-cad-lopez.trycloudflare.com', '2026-10-20 16:40:30'),
(35, 28, '9c6094ab6216f8a9ca5189d86844e5b939a7e3e5eefe857c0a60aeb1b8138f90', '2026-10-21 03:40:51', 'https://differ-offline-cad-lopez.trycloudflare.com', '2026-10-20 16:40:51'),
(36, 30, '5296ffd1cbe50182981ddbef5635de1928846ac9ce9ae91a61a723560c3f888a', '2026-10-21 03:47:51', 'https://differ-offline-cad-lopez.trycloudflare.com', '2026-10-20 16:47:51');

-- --------------------------------------------------------

--
-- Table structure for table `document_request_items`
--

CREATE TABLE `document_request_items` (
  `item_id` bigint(20) UNSIGNED NOT NULL,
  `request_id` bigint(20) UNSIGNED NOT NULL,
  `source_document_id` int(10) UNSIGNED NOT NULL,
  `document_title` varchar(255) NOT NULL,
  `office_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `document_request_items`
--

INSERT INTO `document_request_items` (`item_id`, `request_id`, `source_document_id`, `document_title`, `office_name`) VALUES
(4, 4, 20, 'ACCEPTANCE OF APPLICANT TO AWARDING OF CONTRACT TO TENANTS IN UNIVERSITY STALLS', 'Corporate and Business Affairs Office (CBAO) / Office of the President'),
(5, 5, 4, 'ADMISSION SERVICES FOR PROSPECTIVE ENROLLEES IN JUNIOR HIGH SCHOOL LEVEL', 'Office of Admission and Registration'),
(6, 6, 20, 'ACCEPTANCE OF APPLICANT TO AWARDING OF CONTRACT TO TENANTS IN UNIVERSITY STALLS', 'Corporate and Business Affairs Office (CBAO) / Office of the President'),
(7, 6, 26, 'ACCEPTING OF LETTER OF INTENT TO APPROVAL OF UTILIZATION OF UNIVERSITY FACILITIES', 'Corporate and Business Affairs Office - RENTAL OF UNIVERSITY FACILITIES'),
(8, 6, 4, 'ADMISSION SERVICES FOR PROSPECTIVE ENROLLEES IN JUNIOR HIGH SCHOOL LEVEL', 'Office of Admission and Registration'),
(9, 6, 1, 'ADMISSION SERVICES FOR PROSPECTIVE ENROLLEES IN COLLEGE LEVEL', 'Office of Admission and Registration'),
(10, 6, 25, 'BORROWING/CHARGING OUT OF LIBRARY MATERIALS', 'Office of the University Library'),
(11, 6, 8, 'APPLICATION FOR UNIFAST TERTIARY EDUCATION SUBSIDY', 'Office of the Student Affairs/ Scholarship and Financial Assistance'),
(12, 6, 12, 'CASH ADVANCE PREPARATION', 'Cashier\'s Office'),
(13, 6, 33, 'CLAIMING OF LOST AND FOUND ITEMS', 'Civil Security Unit'),
(14, 7, 20, 'ACCEPTANCE OF APPLICANT TO AWARDING OF CONTRACT TO TENANTS IN UNIVERSITY STALLS', 'Corporate and Business Affairs Office (CBAO) / Office of the President'),
(22, 15, 20, 'ACCEPTANCE OF APPLICANT TO AWARDING OF CONTRACT TO TENANTS IN UNIVERSITY STALLS', 'Corporate and Business Affairs Office (CBAO) / Office of the President'),
(23, 15, 26, 'ACCEPTING OF LETTER OF INTENT TO APPROVAL OF UTILIZATION OF UNIVERSITY FACILITIES', 'Corporate and Business Affairs Office - RENTAL OF UNIVERSITY FACILITIES'),
(24, 15, 4, 'ADMISSION SERVICES FOR PROSPECTIVE ENROLLEES IN JUNIOR HIGH SCHOOL LEVEL', 'Office of Admission and Registration'),
(25, 15, 8, 'APPLICATION FOR UNIFAST TERTIARY EDUCATION SUBSIDY', 'Office of the Student Affairs/ Scholarship and Financial Assistance'),
(27, 17, 20, 'ACCEPTANCE OF APPLICANT TO AWARDING OF CONTRACT TO TENANTS IN UNIVERSITY STALLS', 'Corporate and Business Affairs Office (CBAO) / Office of the President'),
(28, 18, 20, 'ACCEPTANCE OF APPLICANT TO AWARDING OF CONTRACT TO TENANTS IN UNIVERSITY STALLS', 'Corporate and Business Affairs Office (CBAO) / Office of the President'),
(29, 19, 20, 'ACCEPTANCE OF APPLICANT TO AWARDING OF CONTRACT TO TENANTS IN UNIVERSITY STALLS', 'Corporate and Business Affairs Office (CBAO) / Office of the President'),
(30, 20, 20, 'ACCEPTANCE OF APPLICANT TO AWARDING OF CONTRACT TO TENANTS IN UNIVERSITY STALLS', 'Corporate and Business Affairs Office (CBAO) / Office of the President'),
(31, 21, 20, 'ACCEPTANCE OF APPLICANT TO AWARDING OF CONTRACT TO TENANTS IN UNIVERSITY STALLS', 'Corporate and Business Affairs Office (CBAO) / Office of the President'),
(32, 22, 20, 'ACCEPTANCE OF APPLICANT TO AWARDING OF CONTRACT TO TENANTS IN UNIVERSITY STALLS', 'Corporate and Business Affairs Office (CBAO) / Office of the President'),
(33, 23, 20, 'ACCEPTANCE OF APPLICANT TO AWARDING OF CONTRACT TO TENANTS IN UNIVERSITY STALLS', 'Corporate and Business Affairs Office (CBAO) / Office of the President'),
(34, 24, 20, 'ACCEPTANCE OF APPLICANT TO AWARDING OF CONTRACT TO TENANTS IN UNIVERSITY STALLS', 'Corporate and Business Affairs Office (CBAO) / Office of the President'),
(35, 24, 26, 'ACCEPTING OF LETTER OF INTENT TO APPROVAL OF UTILIZATION OF UNIVERSITY FACILITIES', 'Corporate and Business Affairs Office - RENTAL OF UNIVERSITY FACILITIES'),
(36, 24, 1, 'ADMISSION SERVICES FOR PROSPECTIVE ENROLLEES IN COLLEGE LEVEL', 'Office of Admission and Registration'),
(37, 24, 4, 'ADMISSION SERVICES FOR PROSPECTIVE ENROLLEES IN JUNIOR HIGH SCHOOL LEVEL', 'Office of Admission and Registration'),
(38, 24, 8, 'APPLICATION FOR UNIFAST TERTIARY EDUCATION SUBSIDY', 'Office of the Student Affairs/ Scholarship and Financial Assistance'),
(39, 24, 25, 'BORROWING/CHARGING OUT OF LIBRARY MATERIALS', 'Office of the University Library'),
(40, 24, 12, 'CASH ADVANCE PREPARATION', 'Cashier\'s Office'),
(41, 24, 43, 'CERTIFICATION OF GOOD MORAL CHARACTER', 'Guidance and Counseling Office'),
(42, 24, 15, 'CHECK PREPARATION', 'Cashier\'s Office'),
(43, 24, 33, 'CLAIMING OF LOST AND FOUND ITEMS', 'Civil Security Unit'),
(44, 24, 45, 'CONDUCT OR SERVE AS RESOURCE PERSON FOR TECHNICAL TRAININGS/WORKSHOPS/SEMINARS', 'Management Information Systems Office'),
(45, 24, 48, 'CONSULTANCY SERVICES', 'Management Information Systems Office'),
(46, 24, 46, 'COUNSELING SERVICE', 'Guidance and Counseling Office'),
(47, 24, 7, 'ENROLLMENT/REGISTRATION OF GRADE SEVEN (JUNIOR HIGH SCHOOL) STUDENTS', 'Office of Admission and Registration'),
(48, 24, 21, 'ENROLLMENT/REGISTRATION OF JUNIOR (GRADE 8-10) HIGH SCHOOL STUDENTS', 'Office of Admission and Registration'),
(49, 24, 10, 'ENROLLMENT/REGISTRATION OF NEW COLLEGE STUDENTS', 'Office of Admission and Registration'),
(50, 24, 16, 'ENROLLMENT/REGISTRATION OF NEW GRADUATE STUDENTS', 'Office of Admission and Registration'),
(51, 24, 13, 'ENROLLMENT/REGISTRATION OF OLD COLLEGE STUDENTS', 'Office of Admission and Registration'),
(52, 24, 18, 'ENROLLMENT/REGISTRATION OF OLD STUDENTS IN THE GRADUATE SCHOOL', 'Office of Admission and Registration'),
(53, 24, 24, 'ENROLLMENT/REGISTRATION OF SENIOR (GRADE 11-12) HIGH SCHOOL STUDENTS', 'Office of Admission and Registration'),
(54, 24, 27, 'ENROLLMENT/REGISTRATION OF TRANSFEREES', 'Office of Admission and Registration'),
(55, 24, 32, 'FIRST ISSUANCE OF TRANSCRIPT OF RECORDS (TOR)', 'Office of Admission and Registration'),
(56, 24, 31, 'HEALTH SERVICES', 'Health Services Unit (HSU)'),
(57, 24, 2, 'ISSUANCE OF CERTIFICATION OF INCLUSION IN THE FREE HIGHER EDUCATION', 'Office of the Student Affairs/ Scholarship and Financial Assistance'),
(58, 24, 5, 'ISSUANCE OF CERTIFICATION OF NO NATIONALLY FUNDED SCHOLARSHIP OR FINANCIAL ASSISTANCE RECEIVED EXCEPT FHE', 'Office of the Student Affairs/ Scholarship and Financial Assistance'),
(59, 24, 37, 'ISSUANCE OF CERTIFICATION, AUTHENTICATION AND VERIFICATION (CAV)', 'Office of Admission and Registration'),
(60, 24, 42, 'ISSUANCE OF IDENTIFICATION CARD', 'Management Information Systems Office'),
(61, 24, 19, 'ISSUANCE OF LIBRARY CARD', 'Office of the University Library'),
(62, 24, 14, 'ISSUANCE OF ONLINE CERTIFICATION OF NO NATIONALLY FUNDED SCHOLARSHIP OR FINANCIAL ASSISTANCE RECEIVED EXCEPT FHE', 'Office of the Student Affairs/ Scholarship and Financial Assistance'),
(63, 24, 30, 'ISSUANCE/RELEASE OF CERTIFICATES', 'Office of Admission and Registration'),
(64, 24, 3, 'NEW REGISTRATION OF STUDENT ORGANIZATIONS', 'Office of Student Organizations, Activities and Development'),
(65, 24, 51, 'ONLINE APPLICATION FOR UNIFAST TERTIARY EDUCATION SUBSIDY', 'Office of the Student Affairs/ Scholarship and Financial Assistance'),
(66, 24, 11, 'ONLINE ISSUANCE OF CERTIFICATION OF INCLUSION IN THE FREE HIGHER EDUCATION', 'Office of the Student Affairs/ Scholarship and Financial Assistance'),
(67, 24, 9, 'PROCESSING OF DISBURSEMENT VOUCHERS', 'Accounting Office'),
(68, 24, 38, 'REFERENCE/INFORMATION AND BIBLIOGRAPHIC SERVICES', 'Office of the University Library'),
(69, 24, 47, 'REGISTRATION OF NON-NEUST USERS TO ACCESS AND USE LIBRARY RESOURCES', 'Office of the University Library'),
(70, 24, 40, 'REISSUANCE OF ARCHIVED TRANSCRIPT OF RECORDS (TOR)', 'Office of Admission and Registration'),
(71, 24, 34, 'REISSUANCE OF TRANSCRIPT OF RECORDS (TOR) (Special Case - For Retaking the Licensure Examination)', 'Office of Admission and Registration'),
(72, 24, 17, 'RELEASING OF CHECKS', 'Cashier\'s Office'),
(73, 24, 6, 'RENEWAL OF REGISTRATION OF STUDENT ORGANIZATIONS', 'Office of Student Organizations, Activities and Development'),
(74, 24, 49, 'RENEWING BOOKS BORROWED/CHECKED OUT', 'Office of the University Library'),
(75, 24, 35, 'REQUEST FOR LIBRARY ORIENTATION AND LIBRARY INSTRUCTION SESSION (IN-PERSON)', 'Office of the University Library'),
(76, 24, 36, 'REQUEST OF GATE PASS/RFID STICKER', 'Civil Security Unit'),
(77, 24, 50, 'REQUESTING/PAGING PERIODICALS/JOURNALS', 'Office of the University Library'),
(78, 24, 23, 'REQUISITION, DELIVERY, SELLING OF ITEMS SOLD AT THE (EFWRS)', 'Corporate and Business Affairs Office - EFWRS'),
(79, 24, 28, 'RETURNING/CHECKING IN LIBRARY MATERIALS', 'Office of the University Library'),
(80, 24, 39, 'REVIEW OF CCTV', 'Civil Security Unit'),
(81, 24, 22, 'SIGNING OF LIBRARY CLEARANCE', 'Office of the University Library'),
(82, 24, 29, 'UNIVERSITY HOSTEL/DORMITORY ACCOMMODATION', 'Hostel and Dormitory Services Unit (under CBAO & Auxiliary Services)'),
(83, 24, 44, 'USE OF NEUST LIBRARY SPACES', 'Office of the University Library'),
(84, 24, 41, 'USE OF PCS TO ACCESS E-LIBRARY', 'Office of the University Library'),
(87, 27, 20, 'ACCEPTANCE OF APPLICANT TO AWARDING OF CONTRACT TO TENANTS IN UNIVERSITY STALLS', 'Corporate and Business Affairs Office (CBAO) / Office of the President'),
(88, 28, 20, 'ACCEPTANCE OF APPLICANT TO AWARDING OF CONTRACT TO TENANTS IN UNIVERSITY STALLS', 'Corporate and Business Affairs Office (CBAO) / Office of the President'),
(90, 30, 20, 'ACCEPTANCE OF APPLICANT TO AWARDING OF CONTRACT TO TENANTS IN UNIVERSITY STALLS', 'Corporate and Business Affairs Office (CBAO) / Office of the President');

-- --------------------------------------------------------

--
-- Table structure for table `document_steps`
--

CREATE TABLE `document_steps` (
  `id` int(11) NOT NULL,
  `document_id` int(11) NOT NULL,
  `step_number` int(11) NOT NULL,
  `client_step` text DEFAULT NULL,
  `agency_action` text DEFAULT NULL,
  `fees` varchar(1000) DEFAULT 'None',
  `processing_time` varchar(100) DEFAULT NULL,
  `person_responsible` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `document_steps`
--

INSERT INTO `document_steps` (`id`, `document_id`, `step_number`, `client_step`, `agency_action`, `fees`, `processing_time`, `person_responsible`) VALUES
(1, 1, 1, '1. Applicant to fill up online Application Form at NEUST Online admission portal.', '1.1 Approval /disapproval. If approved, applicant will receive an email from the OAR. Afterwards, a test permit will also be sent to them where the date, time and venue of examination is indicated.', 'None', '2 Minutes', 'Office of Admission and Registration'),
(2, 1, 2, '2. On the date and time of examination applicants examinees will proceed to the assigned room for F2F taking of the admission Test.', '2.1 Administration of the admission test.', 'None', '1 hour, 45 minutes', 'Student'),
(3, 1, 3, '3. Wait for the result of the Admission Test.', '3.1 List of qualified applicants will be posted in the OAR page.', 'None', '4 Days', 'Office of Admission and Registration'),
(4, 2, 1, '1. The University student shall proceed to the Office of Student Affairs and provide a photocopy of the Certificate of Registration', '1.1 The SFAO personnel shall collect from the student the requirement for a photocopy of the certificate of registration', 'None', '1 minute', 'SFAO personnel, Office of Student Affairs'),
(5, 2, 1, NULL, '1.2 The SFAO personnel shall issue the Certification of Free Higher Education', 'None', '1 minute', 'SFAO personnel, Office of Student Affairs'),
(6, 3, 1, '1. The applicant student/s organization shall submit their Constitution and By Laws to the Committee on Student Organizations, and Activities (CSOA)', '1.1 The Committee on Student Organizations, and Activities (CSOA) will review and approve the applicant USO\'s constitution and by-laws.', 'None', '10 minutes', 'Committee on Student Organizations and Activities (CSOA)'),
(7, 3, 2, '2. The applicant student/s organization shall submit all the accomplished application forms together with their Constitution and By Laws to the Head of the Office of Student Organization, Activities and Development (OSOAD).', '2.1 The Head of OSOAD shall review and evaluate the submitted documents from the applicant USO, after which the Dean of OSAS shall provide confirmation.', 'None', '10 minutes', 'Head, Office of Student Organizations, Activities and Development (OSOAD).'),
(8, 3, 2, NULL, '2.2 After the evaluation process, OSOAD shall endorse the qualified USO applicants to the University President for approval. Upon approval, the OSAS, in coordination with OSOAD, shall issue the USO Certificate to Operate, which will be formally awarded during the Recognition of USO Ceremony.', 'None', NULL, 'Dean, Office of Students Affairs and Services (OSAS).'),
(9, 4, 1, '1. Submit certified true Copy of Grade 5 Form 137 and 1st quarter grade (Grade 6) to the Office of the LHS Principal.', '1.1 Evaluate grades of applicant. If qualified, applicants will receive an interview schedule.', 'None', '1 Minute', 'Laboratory High School Faculty'),
(10, 4, 2, '2. Applicant will come for an interview on the date and time given to him/her.', '2.1 Interview Applicants', 'None', '4 Minutes', 'Laboratory High School Faculty'),
(11, 4, 3, '3. After the interview applicant will receive an application form and will pay the admission Test fee at the NEUST Cashier\'s office.', '3.1 Receive payment.', '₱180.00', '2 Minutes', 'NEUST Cashier'),
(12, 4, 4, '4. Submit filled up application form and show receipt of payment to the Office of Admission and Registration.', '4.1 Check application form, verify payment and issue test permit to the applicant.', 'None', '1 Minute', 'Student/Office of Admission and Registration'),
(13, 4, 5, '5. Take F2F admission Test on the date, time and room specified on the test permit.', '5.1 Publish the names of qualified applicants', 'None', '5 Days', 'Office of Admission and Registration'),
(14, 5, 1, '1. The University student shall proceed to the Office of Student Affairs and provide a photocopy of the Certificate of Registration.', '1.1 The SFAO personnel shall collect the requirements for the photocopy certificate of registration from the student.', 'None', '1 minute', 'SFAO personnel, Office of Student Affairs'),
(15, 5, 1, NULL, '1.2 The SFAO personnel shall verify and issue the Certification of No National Scholarship or Financial Assistance Received', 'None', '3 minutes', 'SFAO personnel, Office of Student Affairs'),
(16, 6, 1, '1. The applicant student/s organization shall submit their Constitution and By Laws to the Committee on Student Organizations, and Activities (CSOA)', '1.1 The Committee on Student Organization, Activities and Development (CSOA) will review and approve the applicant USO\'s constitution and by-laws.', 'None', '10 minutes', 'Committee on Student Organizations, and Activities (CSOA)'),
(17, 6, 2, '2. The applicant student/s organization shall submit all the accomplished application forms together with their Constitution and By Laws to the Head of the Office of Student Organization, Activities and Development (OSOAD).', '2.1 The Head of OSOAD shall review and evaluate the submitted documents from the applicant USO, after which the Dean of OSAS shall provide confirmation.', 'None', '10 minutes', 'Head, Office of Student Organizations, Activities and Development (OSOAD). / Dean, Office of Students Affairs and Services (OSAS).'),
(18, 6, 2, NULL, '2.2 After the evaluation process, OSOAD shall endorse the qualified USO applicants to the University President for approval. Upon approval, the OSAS, in coordination with OSOAD, shall issue the USO Certificate to Operate, which will be formally awarded during the Recognition of USO Ceremony.', 'None', NULL, 'Head, Office of Student Organizations, Activities and Development (OSOAD). / Dean, Office of Students Affairs and Services (OSAS).'),
(19, 7, 1, '1. Qualified applicants are requested to submit their original Form 137 and 138 in the Office of the Principal. Afterwards, qualified applicants will receive a username, password and enrolment link in their email accounts.', '1.1 Check and Verify', 'None', '2 Minutes', 'Office of Admission and Registration'),
(20, 7, 2, '2. Enter enrolment link and answer/ supply all needed information and follow instructions to be able to enroll.', '2.1 Assist student applicant', 'None', '2 Minutes', 'Qualified applicant/student'),
(21, 7, 3, '3. Payment of fees at the NEUST Cashier', '3.1 Receive payment', 'Sports Development Fee:150.00;\r\nSocio Cultural: 100.00;\r\nGuidance Fee: 50.00;\r\nID Fee: 70.00;\r\nPublication: 200.00;\r\nSecurity: 50.00;\r\nStudent Handbook: 40.00;\r\nAnti-TB/Red Cross: 15.00;\r\nCharity: 10.00;\r\nFTPCC: 15.00;\r\nUtility Fee: 100.00;\r\nLaboratory Deposit: 600.00;\r\nUSG/Insurance: 250.00;\r\nScience and Technology: 50.00;\r\nRegistration Fee: 100.00;\r\nAthletic Fee: 90.00;\r\nEntrance Fee: 120.00;\r\nLibrary Fee: 160.00;\r\nMedical & Dental Fee: 80.00;\r\nLab Fee: 160.00;\r\nMaintenance Development: 1,000.00;\r\nPhysical Development: 3,500.00', '4 Minutes', 'Cashier\'s Office Personnel/Student'),
(22, 7, 4, '4. If verified, student applicant/ examinee will receive Certificate of Registration (COR)', '4.1 Issuance of COR', 'None', '2 Minutes', 'Office of Admission and Registration'),
(23, 8, 1, '1. The University student shall proceed to the Office of Student Affairs and provide a photocopy of the Certificate of Registration, a photocopy of the NEUST student ID, and a photocopy of the DSWD Household ID or Listahanan ID.', '1.1 The SFAO personnel shall collect from the student the required photocopy of the Certificate of Registration, photocopy of NEUST student ID, and photocopy of DSWD Household ID or Listahanan ID.', 'None', '5 minutes', 'SFAO personnel, Office of Student Affairs'),
(24, 8, 1, NULL, '1.2 The SFAO personnel shall issue a financial assistance application form', 'None', '1 minutes', 'SFAO personnel, Office of Student Affairs'),
(25, 8, 2, '2. The University student shall fill up the issued financial assistance application form at the Office of Student Affairs', '2.1 The SFAO personnel shall collect the accomplished financial assistance application form', 'None', '1 minute', 'SFAO personnel, Office of Student Affairs'),
(26, 8, 2, NULL, '2.2 Verify the adequacy of the information provided', 'None', '5 minute', 'SFAO personnel, Office of Student Affairs'),
(27, 9, 1, '1. The requesting party will be required to submit a document approving the disbursement including all necessary supporting documents including all necessary supporting documents', '1.1 Verifies completeness of submitted supporting documents and provides computation of DTE, Disbursement Vouchers and certification form from the accountant of no outstanding balance for cash advance requests.', 'None', '10 Minutes', 'Accounting Office Personnel, Accounting Office'),
(28, 9, 1, NULL, '1.2 Forwards supporting documents to Budget Office for assignment of Obligation (ORS/BURS)', 'None', '2 Minutes', 'Accounting Office Personnel, Accounting Office'),
(29, 9, 1, NULL, '1.3 Forwards obligated supporting documents and disbursement voucher to the Office of the Vice President for Administration, Business and Finance', 'None', '5 Minutes', 'Accounting Office Personnel, Accounting Office'),
(30, 10, 1, '1. Qualified applicants are requested to submit their original F137 and 138 to the Office of Admission & Registration. Afterwards, qualified applicants will receive a username, password and enrolment link in their email accounts.', '1.1 Check authenticity of submitted document and GWA obtained in Grade 12.', 'None', '2 Minutes', 'Office of Admission and Registration'),
(31, 10, 2, '2. Enter enrolment link and answer/ supply all needed information and follow instructions to be able to enroll.', '2.1 Assist qualified examinee', 'None', '4 Minutes', 'Student'),
(32, 10, 3, '3. Print Certificate of Registration (COR).', NULL, 'None', '1 Minute', 'Student'),
(33, 11, 1, '1. The University student shall visit the Facebook page of the Scholarship and Financial Assistance and scan the QR code or fill out the Certificate of Free Higher Education E-Logbook. https://bit.ly/CertOFFHE', '1.1 The SFAO personnel shall gather the information provided by the student from the FHE E-Logbook.', 'None', '1 minute', ''),
(34, 11, 1, '', '1.2 The SFAO personnel shall issue the Certificate of Inclusion in Free Higher Education.', 'None', '1 minute', 'SFAO personnel, Office of Student Affairs'),
(35, 12, 1, '1. The Office of the President / Accounting / VP-ABF will transmit the approved Disbursement Voucher / Payroll to the Cashier\'s Office', '1.1 Receive from Office of the President / Accounting / VP-ABF Office the DV/Payroll and necessary attachments', 'None', '3 Minutes', 'Cashier\'s Office Personnel'),
(36, 12, 1, NULL, '1.2 Sort the payroll / DV as per source of fund then prepare the cash advance based on the payroll / DV and supporting documents.', 'None', '1 Day', 'Cashier\'s Office Personnel'),
(37, 12, 1, NULL, '1.3 Forward the document package to the Head Cashier for signature attach prepared check to the respective DV/ Payroll', 'None', NULL, NULL),
(38, 12, 1, NULL, '1.4 Review the prepared cash advance and the attachments', 'None', '3 Minutes', 'Cashier Head'),
(39, 12, 1, NULL, '1.5 Sign the cash advance if all is in order', 'None', '5 Minutes', 'Cashier Head'),
(40, 12, 1, NULL, '1.6 Forward to Budget Office for ALLOBS', 'None', '2 Minutes', 'Cashier\'s Office Personnel'),
(41, 13, 1, '1. Login to enroll.neust.edu.ph using the password and username given before. Update information.', '1.1 Check and verify', 'None', '8 Minutes', 'Enrolling teacher of each department / Accounting Office'),
(42, 13, 2, '2. If verified, then print Certificate of Registration (COR)', '2.1 Check and verify', 'None', '2 Minutes', 'Department/ Official of Admission and Registration/ Student'),
(43, 14, 1, '1. The University student shall visit the Facebook page of the Scholarship and Financial Assistance, scan the QR code, or fill out the Certificate of No Nationally Funded Scholarship or Financial Assistance Received E-Logbook._ https://bit.ly/CertOfFHE', '1.1 The SFAO personnel shall gather the information provided by the student from the E-Logbook, including any nationally funded scholarship or financial assistance received.', 'None', '1 minute', 'SFAO personnel, Office of Student Affairs'),
(44, 14, 2, '2. The University student can print the signed PDF or proceed to the Office.', '1.2 The SFAO personnel shall verify and issue the Certification of No National Scholarship or Financial Assistance Received', 'None', '3 minutes', 'SFAO personnel, Office of Student Affairs'),
(45, 15, 1, '1. The Office of the President / Accounting / VP-ABF will transmit the Disbursement Voucher / Payroll to the Cashier\'s Office', '1.1 Receive from the Office of the President / Accounting / VP-ABF Office the DV/Payroll and necessary attachments', 'None', '3 Minutes', 'Cashier\'s Office Personnel'),
(46, 15, 1, NULL, '1.2 Sort the payroll / DV as per source of fund then prepare the check accordingly', 'None', '', ''),
(47, 15, 1, NULL, '1.3 Prepare 3 (triplicate) copies of check. (Duplicate for MDS-advice only)', 'None', '1 day', 'Cashier\'s Office Personnel'),
(48, 15, 1, NULL, '1.4 Attached prepared check to the respective DV/Payroll', 'None', NULL, NULL),
(49, 15, 1, NULL, '1.5 Record the check details to the record book / routing slip of checks prepared then forward to the Head Cashier for signature', 'None', '5 Minutes', 'Cashier Head'),
(50, 15, 1, NULL, '1.6 Review the prepared checks against the DV and its attachments', 'None', '4 Minutes', 'Cashier Head'),
(51, 15, 1, NULL, '1.7 Sign the check/s if all is in order', 'None', '', ''),
(52, 15, 1, NULL, '1.8 Forward to Office of the President for signing of checks.', 'None', '5 Minutes', 'Cashier\'s Office Personnel'),
(53, 16, 1, '1. New enrollees will log in to the link given by the Graduate school and fill up all information needed. Upload TOR and 2 letters of recommendation.', '1.1 Approval /Disapproval', 'None', '3 Minutes', 'Graduate School Faculty, Graduate School Office'),
(54, 16, 2, '2. If approved, password, username and enrolment link will be sent to the student applicant', '2.1 Check and verify', 'None', '2 Minutes', 'Graduate School Faculty, Graduate School Office'),
(55, 16, 3, '3. Payment of fees either at the NEUST Cashier\'s office or through online (Land Bank of the Philippines)', '3.1 Check and verify payment', 'Sports Development Fee:150.00;\nSocio Cultural: 100.00;\nGuidance Fee: 50.00;\nID Fee: 70.00;\nPublication: 100.00;\nSecurity: 50.00;\nResearch Journal: 250.00;\nAnti-TB/Red Cross: 15.00;\nCharity: 10.00;\nFTPCC: 15.00;\nUtility Fee: 100.00;\nID Fee: 70.00/Sem;\nLaboratory Deposit: 600.00(first year only);\nStudent Handbook: 40.00(first year only);\nRegistration Fee: 100.00;\nLibrary Fee: 100.00;\nMedical & Dental Fee: 80.00;\nMaintenance Development: 90.00;\nPhysical Development: 700.00;\nEquipment Development: 250.00;\nEntrance Fee: 120.00(one-time fee);\nInternet Fee: 350.00;\nTuition Fee:   12 Units; 400.00/unit for Masteral; 500.00/unit for Doctoral', '3 Minutes', 'Cashier\'s Office Personnel, Student'),
(56, 16, 4, '4. Payment in the NEUST cashier / Online banking process (LBP)', '4.1 Check and verification', 'None', '1 Minute / 5 Minutes', 'Accounting/ Cashier/Student'),
(57, 16, 5, '5. If verified, print Certificate of Registration (COR).', NULL, 'None', '1 Minute', 'Student'),
(58, 17, 1, '1. The claimant/payee will sign on the check receiving/releasing logbook/registry and on the DV and/or payroll from the Cashier\'s Office', '1. Receive from Office of the President the approved DV/ Payroll and other supporting documents', 'None', '1 Minute', 'Cashier\'s Office Personnel'),
(59, 17, 2, NULL, '2. Indicate the check details and assigned DV number on supporting papers', 'None', '2 Minutes', 'Cashier\'s Office Personnel'),
(60, 17, 2, NULL, '3.1 Ask the claimant / payee to sign on the check receiving / releasing logbook / registry', 'None', '2 Minutes', 'Cashier\'s Office Personnel'),
(61, 17, 2, NULL, '3.2 Request the payee to sign DV and/or payroll', 'None', NULL, 'Cashier\'s Office Personnel'),
(62, 17, 2, NULL, '3.3 Release/ Disburse the check to the payee. Require authorization if the claimant is different person from the payee indicated on the check.', 'None', NULL, 'Cashier\'s Office Personnel'),
(63, 17, 2, '2. The claimant/payee will receive the check from the Cashier\'s Office', '4.1 Issue the check to the payee/ claimant', 'None', '5 Minutes', 'Cashier\'s Office Personnel'),
(64, 18, 1, '1. Login to enroll.neust.edu.ph using the password and username given before. Update information.', '1.1 Approval / Disapproval', 'None', '3 Minutes', 'Graduate School Faculty, Graduate School Office'),
(65, 18, 2, '2. If approved, payment of fees. Either thru NEUST Cashier or thru online (LBP)', '2.1 Receive Payment', 'Sports Development Fee: 150.00; Socio Cultural: 100.00; Guidance Fee: 50.00; Publication: 100.00; Security: 50.00; Research Journal: 250.00; Anti-TB/Red Cross: 15.00; Charity: 10.00; FTPCC: 15.00; Utility Fee: 100.00; U O Fee: 70.00/Sem; Laboratory Deposit: 600.00 (first year only); Student Handbook: 40.00(first year only); Lab Fee: 300.00 (Masteral); 500.00 (Doctoral); \r\nRegistration Fee: 100.00;\r\nLibrary Fee: 100.00;\r\nMedical & Dental Fee: 80.00;\r\nMaintenance Development: 90.00;\r\nPhysical Development: 700.00;\r\nEquipment Development: 250.00;\r\nEntrance Fee: 120.00(one-time fee);\r\nInternet Fee: 350.00;\r\nTuition Fee:   12 Units; 400.00/unit for Masteral; 500.00/unit for Doctoral', '3 Minutes thru NEUST Cashier / 5 Minutes thru online (LBP)', 'Graduate School Faculty, Graduate School Office / Cashier\'s Office Personnel, Student'),
(66, 18, 3, '3. If payment is done in the Cashiers\' Office, student can immediately receive notification to print the certificate of registration. If payment is done through online, student should upload proof of payment and wait for verification. If verified, student can print Certificate of Registration (COR).', '3.1 Verification', 'None', '1 Minute / 3 Minutes', 'Accounting/ Cashier/Student / Student'),
(67, 19, 1, '1. Present COR, copy of appointment paper (for members of the faculty and administrative staff), 1x1 ID picture', '1.1 Check and verify the accuracy of the information indicated in the documents presented', 'None', '2 Minutes', 'Librarian/Library Staff, Office of the University Library'),
(68, 19, 2, '2. Fill out the Library User Registration Form (Google Form)', '2.1 Verify the identity of the client by comparing the information provided in the COR and appointment papers (for the members of the faculty and administrative staff)', 'None', '3 Minutes', 'Librarian/Library Staff, Office of the University Library'),
(69, 19, 3, '3. Wait for the issuance of the Library Card', '3.1 Process Library Card', 'None', '1 minute', 'Librarian/Library Staff, Office of the University Library'),
(70, 19, 4, '4. Receive validated/ countersigned Library Card', '4.1 Issue countersigned/ validated Library Card', 'None', '1 minute', 'Librarian/Library Staff, Office of the University Library'),
(71, 20, 1, '1. Submit Letter of Intent, Resume, and ID', 'Receives and forwards to CBAO', 'None', '1 working day', 'Office of the President'),
(72, 20, 2, NULL, 'Collates and reviews letters. If multiple applicants, sends back to President\'s Office for evaluation', 'None', '2-3 working days', 'CBAO'),
(73, 20, 3, NULL, 'Evaluates and approves the applicant(s)', 'None', '3-5 working days', 'Office of the President'),
(74, 20, 4, NULL, 'Prepares Contract of Lease', 'None', '2 working days', 'CBAO'),
(75, 20, 5, '5. Attend contract signing', 'Notifies client to sign and submit post-dated checks', 'None', '1 working day', 'CBAO'),
(76, 20, 6, '6. Submit business documents', 'Verifies compliance', 'None', '2-3 working days', 'CBAO'),
(77, 20, 7, '7. Pay rental dues', 'Issues Official Receipts', 'None', 'Every 5th of the month', 'CBAO'),
(78, 20, 8, NULL, 'Updates rental ledger', 'None', 'Ongoing monthly', 'CBAO'),
(79, 20, 9, NULL, 'Reads utility meters (3rd of month)', 'None', 'Monthly', 'CBAO'),
(80, 20, 10, NULL, 'Delivers utility bills (4th of month)', 'None', 'Monthly', 'CBAO'),
(81, 20, 11, '11. Pay utility bills', 'Issues Official Receipts', 'None', 'Monthly', 'CBAO'),
(82, 20, 12, NULL, 'Updates utility ledgers', 'None', 'Monthly', 'CBAO'),
(83, 20, 13, NULL, 'Prepares monthly accounts receivable reports', 'None', 'By 12th and end of month', 'CBAO'),
(84, 20, 14, NULL, 'Issues notice of disconnection (for unpaid bills)', 'None', 'By 10th of the month', 'CBAO'),
(85, 20, 15, NULL, 'Disconnects utility for delinquent accounts', 'None', 'By 13th of the month', 'BMAGS'),
(86, 20, 16, NULL, 'Prepares demand letters', 'None', 'By 15th of the month', 'CBAO'),
(87, 20, 17, NULL, 'Issues notice to vacate (for repeated non-payment)', 'None', 'Within 3 days after demand', 'CBAO'),
(88, 21, 1, '1. Login to enroll.neust.edu.ph using the password and username given before. Update information.', '1.1 Approval / disapproval', 'None', '3 Minutes', 'Enrolling Teacher of the Laboratory High School'),
(89, 21, 2, '2. Payment of fees in the NEUST Cashier\'s Office.', '2.1 Receive payment', 'Sports Development Fee: 150.00;\r\nSocio Cultural: 100.00;\r\nGuidance Fee: 50.00;\r\nID Fee: 70.00;\r\nPublication: 200.00;\r\nSecurity: 50.00;\r\nStudent Handbook: 40.00;\r\nAnti-TB/Red Cross: 15.00;\r\nCharity: 10.00;\r\nFTPCC: 15.00;\r\nUtility Fee: 100.00;\r\nLaboratory Deposit: 600.00;\r\nUSG/Insurance: 250.00;\r\nScience and Technology: 50.00;\r\nRegistration Fee: 100.00;\r\nAthletic Fee: 90.00;\r\nEntrance Fee: 120.00;\r\nLibrary Fee: 160.00;\r\nMedical & Dental Fee: 80.00;\r\nLab Fee: 160.00;\r\nMaintenance Development: 1,000.00;\r\nPhysical Development: 3,500.00;', '2 Minutes', 'Cashier\'s Office Personnel/ Bank Personnel'),
(90, 21, 3, '3. If payment is done, student will receive a notification that he/she can already print her/his Certificate of Registration', '3.1 Issuance of COR', 'None', '2 Minutes', 'Office of Admission and Registration/ Student'),
(91, 22, 1, '1. Present the Clearance Form duly signed by the campus/college library', '1.1 Verify information indicated in the Clearance Form', 'None', '2 Minutes', 'Librarian/Library Staff, Office of the University Library'),
(92, 22, 1, NULL, '1.2 Check and verify from the list with library accountability', 'None', '2 Minutes', 'Librarian/Library Staff, Office of the University Library'),
(93, 22, 2, '2. Log in the logbook', '2.1 Check for the accuracy of the information indicated', 'None', '1 minute', 'Librarian/Library Staff, Office of the University Library'),
(94, 22, 2, NULL, '2.2 If a student signs on behalf of the University Librarian (box for the Librarian)', 'None', '1 minute', 'Librarian/Library Staff, Office of the University Library'),
(95, 22, 3, '3. Receive duly signed Clearance Form', '3.1 Release duly signed Library Clearance', 'None', '1 minute', 'Librarian/Library Staff, Office of the University Library'),
(96, 22, 3, NULL, '3.2 Faculty and staff refer to the University Librarian for signature', 'None', NULL, 'Librarian/Library Staff, Office of the University Library'),
(97, 23, 1, 'Place an order (walk-in/phone/online)', 'Receive and confirm order details', 'None', '3 minutes', 'Business Center Staff'),
(98, 23, 2, 'Wait for confirmation', 'Fill out order slip, confirm details', 'None', '2 minutes', 'Business Center Staff'),
(99, 23, 3, 'Pay for the order', 'Accept payment, issue Official Receipt', 'As per order (e.g., PHP 30/gallon)', '5 minutes', 'Business Center Cashier'),
(100, 23, 4, 'Wait for delivery', 'Forward paid order details to EFWRS', 'None', '2 minutes', 'Business Center Staff'),
(101, 23, 5, 'Receive delivery receipt', 'Prepare and deliver water as ordered', 'None (delivery may vary per policy)', '30-60 minutes (depends on location)', 'EFWRS Delivery Staff'),
(102, 23, 6, 'Receive and inspect order, sign receipt', 'Issue numbered delivery receipt', 'None', '2 Minutes', 'Delivery Staff'),
(103, 23, 6, NULL, 'Confirm delivery and receive signed copy', 'None', '3 Minutes', 'Client/Delivery Staff'),
(104, 23, 6, NULL, 'Return signed duplicate to Business Center for filing', 'None', '5 Minutes', 'EFWRS Delivery Staff / Business Center Clerk'),
(105, 24, 1, '1. Login to enroll.neust.edu.ph using the password and username given before. Update information.', '1.1 Approval / Disapproval', 'None', '3 Minutes', 'Enrolling Teacher of the Laboratory High School'),
(106, 24, 2, '2. Payment of Fees at NEUST Cashier\'s office.', '2.1 Accept payment', 'Sports Development Fee: 75.00;\nSocio Cultural: 50.00;\nGuidance Fee: 50.00;\nPublication: 100.00;\nSecurity: 50.00;\nAnti-TB/Red Cross: 25.00;\nCharity: 25.00;\nUtility Fee: 100.00;\nUSG/Insurance: 150.00;\nScience and Technology: 62.50;\nInternet fee: 175.00;\nPMT: 60.00;\nRegistration Fee: 75.00;\nAthletic Fee: 60.00;\nLibrary Fee: 100.00;\nMedical, Dental Fee: 50.00;\nLab Fee: 130.00;\nMaintenance Development: 400.00;\nPhysical Development: 1,600.00;', '2 Minutes', 'Cashier\'s Office'),
(107, 24, 3, '3. If payment is done, student will receive a notification that he/she can already print her/his Certificate of Registration (COR)', NULL, 'None', '2 Minutes', 'Student'),
(108, 25, 1, '1. Search for the author/topic needed in the Online Public Access Catalog (OPAC).', NULL, 'None', '2 minutes', 'Librarian/Library Staff, Office of the University Library'),
(109, 25, 2, '2. Present your library card and school ID to the staff in charge at the desk. Get the book from the open shelves. Fill up the book card', '2.1 The Librarian/library staff shall scan the barcode after the release of the received material/s for borrowing/ checking out', 'None', '2 minutes', 'Librarian/Library Staff, Office of the University Library'),
(110, 25, 3, '3. Present books to be borrowed together with the library card', '3.1 Receive materials to be borrowed/check out', 'None', '1 minute', 'Librarian/Library Staff, Office of the University Library'),
(111, 25, 3, NULL, '3.2 Check and verify ID/Library card (should be valid and countersigned)', 'None', '1 minute', 'Librarian/Library Staff, Office of the University Library'),
(112, 25, 4, '4. Wait for the books to be processed for check-out', '4.1 Print the receipt for the books borrowed', 'None', '1 minute', 'Librarian/Library Staff, Office of the University Library'),
(113, 25, 5, '5. Receive books.', '5.1 Issue book/s borrowed plus the receipt.', 'None', '1 minute', 'Librarian/Library Staff, Office of the University Library'),
(114, 26, 1, 'Submit Letter of Intent to University President\'s Office', 'Receive and review Letter of Intent; endorse to CBAO', 'None', '1 working day', 'University President / Secretary'),
(115, 26, 2, 'Wait for endorsement', 'Review letter and check venue availability', 'None', '1 working day', 'CBAO Head'),
(116, 26, 3, 'Wait for communication', 'Prepare documents (Reservation Slip, Event Details)', 'None', '1 working day', 'CBAO Staff In-charge'),
(117, 26, 4, 'Confirm event and contact details', 'Prepare quotation and endorsement letter', 'None', '1 working day', 'CBAO Staff In-charge'),
(118, 26, 5, 'Wait for confirmation', 'Review documents, sign and endorse to President\'s Office', 'None', '1 working day', 'CBAO Head'),
(119, 26, 6, 'Wait for approval', 'Review and approve rental', 'Based on quotation / As quoted', '1 working day', 'University President'),
(120, 26, 7, 'Receive notification, pay fees', 'Notify client, issue payment instructions', 'None', 'Within 2 working days', 'CBAO Staff in-charge'),
(121, 26, 8, 'Pay rental fees', 'Receive payment, issue Official Receipt', 'None', NULL, 'CBAO Staff in-charge'),
(122, 27, 1, '1. Upon receipt of certification of available slot from the Dean, qualified transferees\' TOR will be evaluated by the OAR.', '1.1 Evaluate - approval/disapproval.', 'None', '10 Minutes', 'Office of Admission and Registration/ Dean/Director'),
(123, 27, 2, '2. Once approved, qualified applicant will receive username, password and link to be able to enroll.', '2.1 Assist student/s.', 'None', '2 minutes', 'Student'),
(124, 27, 3, '3. Login to enroll.neust.edu.ph using the password and username given before. Fill up needed information and answer questions honestly.', '3.1 Assist student/s.', 'None', '4 minutes', 'Office of Admission and Registration/ Student'),
(125, 27, 4, '4. If verified, print Certificate of Registration (COR).', '4.1 Assist student/s', 'None', '1 Minute', 'Student'),
(126, 28, 1, '1. Present book/s for return/check-in', '1.1 Receive material/s for return/check-in', 'None', '2 Minutes', 'Librarian/Library staff, Office of the University Library'),
(127, 28, 2, '2. Wait for the printout of the transaction receipt for the books returned', '2.1 Scan the barcode of the book to be returned/checked in', 'None', '2 Minutes', 'Librarian/Library staff, Office of the University Library'),
(128, 28, 3, '3. If overdue, the librarian/library staff shall fill out the overdue fine slip and give it to the borrower to be paid at the Cashier.', '3.1 The Librarian/library staff shall provide the Overdue Fine Slip.', 'Library Fines', '2 Minutes', 'Librarian/Library staff, Office of the University Library'),
(129, 28, 4, '4. The cashier shall provide an Official Receipt for the payment of the overdue book/s.', '4.1 The Librarian/library staff shall verify the Official Receipt for overdue books if already paid.', 'None', '4 Minutes', 'Librarian/Library staff, Office of the University Library'),
(130, 28, 5, '5. The borrower shall submit the Official Receipt with the Overdue Fine Slip.', '5.1 Official Receipt, together with the overdue fine slip, must be presented after payment.', 'None', '1 Minute', 'Librarian/Library staff, Office of the University Library'),
(131, 28, 6, '6. The librarian/library staff shall take notes in the logbook for overdue fines, the Official Receipt Number, and the borrower will sign his/her name in the logbook.', '6.1 The librarian/library staff shall log-in the name of the borrower\'s name in the log-book for easy retrieval and references', 'None', '4 Minutes', 'Librarian/Library staff, Office of the University Library'),
(132, 28, 7, '7. The librarian/library staff shall release the borrower\'s library card.', '7.1 The librarian/library staff shall release the borrower\'s library card and a duly signed library clearance', 'None', '1 Minute', 'Librarian/Library staff, Office of the University Library'),
(133, 29, 1, '1. Submit application form and required documents', 'a. Review application form and requirements (Hostel/Dormitory In-Charge)', 'Based on approved dormitory/hostel rates (as indicated in billing statement)', NULL, 'Dormitory/Hostel In-Charge - for applications, billing, and occupancy management'),
(134, 29, 2, '2. Receive billing statement', 'b. Issue billing statement (Dormitory In-Charge)', 'Based on approved dormitory/hostel rates (as indicated in billing statement)', 'Application Review and Billing: 1 working day', 'Dormitory/Hostel In-Charge'),
(135, 29, 3, '3. Pay at the CBAO', 'c. Accept payment CBAO', 'Based on approved dormitory/hostel rates (as indicated in billing statement)', 'Payment and Verification: Same day', 'CBAO - for accepting payments'),
(136, 29, 4, '4. Present proof of payment to Hostel/Dormitory In-Charge', 'd. Verify proof of payment (Hostel/Dormitory In-Charge)', 'Based on approved dormitory/hostel rates (as indicated in billing statement)', 'Check-in and Accommodation: Immediate upon verification', 'Finance Office - for financial reporting'),
(137, 29, 4, NULL, 'e. Monitor attendance and prepare reports', 'Based on approved dormitory/hostel rates (as indicated in billing statement)', NULL, 'Finance Office - for financial reporting'),
(138, 29, 5, '5. Check-in to assigned accommodation. Receive notification, pay fees. Pay rental fees.', 'f. Prepare monthly and quarterly financial reports (Finance/Accounting Office)', 'Based on approved dormitory/hostel rates (as indicated in billing statement)', NULL, NULL),
(139, 30, 1, '1. Fill up request form.', '1.1 Check completeness of request form and advise client to proceed to the Cashiers\' office.', 'None', '3 Minutes', 'Office of Admission and Registration Staff'),
(140, 30, 2, '2. Pay required fee', '2.1 Receive payment', 'Certificate of Grades: 10.00;\nCertificate of Enrolment: 10.00;\nCertificate of Earned Units: 10.00;\nCertificate of English as Medium of Instruction: 50.00;\nDocumentary Stamp: 30.00;\nCertificate of General Weighted Average: 50.00;\nDocumentary Stamp: 30.00;\nCertificate of Honorable Dismissal: 10.00;', '2 Minutes', 'Cashier'),
(141, 30, 3, '3. Present receipt to the OAR staff', '3.1 Release requested certificate.', 'None', '3 Minutes', 'Office of Admission and Registration Staff'),
(142, 31, 1, '1. Accomplish the Student Health Profile and PhilHealth Membership Form on-line', '1.1 Receiving of the data', 'None', '5 Minutes', 'Assigned MIS Personnel, MIS Office'),
(143, 31, 2, '2. Annual Physical Examination (Medical/Dental)', '2.1 Limited to those with problems, the Health Services Unit will conduct the examination on a scheduled date', 'None', '20 Minutes', 'Medical Officer, Dentist, Nurse, Health Services Unit (HSU)'),
(144, 31, 3, '3. Referral to specialists or further diagnostic evaluation when deemed necessary to those patients with findings', '3.1 Referral to proper specialist', 'None', '5 Minutes', 'Medical Officer'),
(145, 31, 3, NULL, '3.2 Follow up result of referral for monitoring of patient\'s condition', 'None', NULL, NULL),
(146, 32, 1, '1. Fill up request form.', '1.1 Check completeness of request form and advise client to proceed to the Cashiers\' office', 'None', '3 Minutes', 'Office of Admission and Registration Staff'),
(147, 32, 2, '2. Complete Clearance', '2.1 Check and verify the completeness and authenticity of the signatories and provide payment slip', 'None', '2 Minutes', 'Office of Admission and Registration Staff'),
(148, 32, 3, '3. Pay required fee', '3.1 Receive payment', 'Documentary Stamp - ₱10.00', '2 minutes', 'Cashier'),
(149, 32, 4, '4. Present Official Receipt', '4.1 Process and prepare TOR.', 'None', '3 days', 'Office of Admission and Registration Staff'),
(150, 32, 4, NULL, '4.2 Issue Claim slip', 'None', NULL, 'Office of Admission and Registration Staff'),
(151, 32, 5, '5. Claim document on scheduled Release date', '5.1 Release TOR', 'None', '2 minutes', 'Office of Admission and Registration Staff'),
(152, 33, 1, '1. Persons losing personal property items should report the details to the Office of the Civil Security Unit, who shall immediately check if the items were surrendered or found soon as they become aware of the loss.', '1.1 Check if the item were surrendered or found.', 'None', '1 Minute', 'Requestor / CSU Admin Clerk'),
(153, 33, 2, '2. The claimant shall fill up the claim form and present his/her ID Card and provide necessary information needed (if item is available).', '2.1 The Security Personnel shall verify the requirements for claiming the found item/s.', 'None', '2 Minutes', 'Requestor / CSU Admin Clerk'),
(154, 33, 3, '3. The claimant shall check the found item/s for verification', '3.1 The Security Personnel shall request the claimer to sign the security Record Book (Claim Form) before the turnover of item/s.', 'None', '2 Minutes', 'CSU Admin Clerk'),
(155, 34, 1, '1. Fill out request form.', '1.1 Review completeness of request and endorse to Dean for approval', 'None', '5 Minutes', 'Office of Admission and Registration Staff'),
(156, 34, 2, '2. Accomplish request form', '2.1 Check and verify the authenticity of the signatory', 'None', '1 Minute', 'Office of Admission and Registration Staff'),
(157, 34, 3, '3. Pay required fee', '3.1 Receive payment', 'TOR - ₱150.00 per page, Documentary Stamp - ₱30.00', '2 minutes', 'Cashier'),
(158, 34, 4, '4. Present Official Receipt', '4.1 Prepare and process TOR', 'None', '3 days', 'Office of Admission and Registration Staff'),
(159, 34, 4, NULL, '4.2 Issue Claim slip', 'None', NULL, NULL),
(160, 34, 5, '5. Claim document on scheduled Release date', '5.1 Release TOR', 'None', '2 minutes', 'Office of Admission and Registration Staff'),
(161, 35, 1, '1. Proceed to the General Reference Section and fill out the Library Orientation Session Logbook', '1.1 Hand-in Library Orientation Session Logbook to the client', 'None', '1 Minute', 'Librarian/Library Staff, Office of the University Library'),
(162, 35, 2, '2. Fill out the Library Orientation Session Logbook by giving details of the request (date, time, course, no. of attendees, etc.)', '2.1 Take note of the details of the request', 'None', '3 Minutes', 'Librarian/Library Staff, Office of the University Library'),
(163, 35, 3, '3. Hand in the completed Library Orientation Schedule Logbook to the Reference Librarian', '3.1 Verify availability of the time schedule and the venue', 'None', '2 Minutes', 'Librarian/Library Staff, Office of the University Library'),
(164, 35, 4, '4. Wait for the confirmation of the availability of the requested time schedule and venue', '4.1 Verify availability of the time schedule and the venue', 'None', '1 Minutes', 'Librarian/Library Staff, Office of the University Library'),
(165, 35, 5, '5. Receive confirmation of the schedule of Library Orientation', '5.1 Confirm with the Faculty schedule and venue of the Library Orientation', 'None', '1 Minutes', 'Librarian/Library Staff, Office of the University Library'),
(166, 36, 1, '1. The requestor shall fill up the gate pass form and provide all the requirements needed and submit to the office of Civil Security Unit', '1.1 The security guard on duty shall check all the requirements and make sure that license and or/cr is not expired; if there is an expired requirements the guard on duty shall request the requestor to renew all the expired documents.', 'None', '2 Minutes', 'CSU Admin Clerk'),
(167, 36, 2, '2. The requestor shall wait for the approval of the gate pass form', '2.1 Final checking and signing of all gate pass form', 'None', '1 Day', 'Chief CSU'),
(168, 36, 3, '3. The requestor shall get the approved copy of gate pass form', '3.1 CSU Admin Clerk shall print an approved copy of gate pass form and give to the requestor and advice to pay at the Marketing Center', 'None', '1 Minute', 'CSU Admin Clerk'),
(169, 36, 4, '4. The requestor shall present the receipt of payment to the CSU office', '4.1 CSU Admin Clerk shall issue Gate Pass/RFID Sticker to the requestor', 'None', '1 Minute', 'CSU Admin Clerk'),
(170, 37, 1, '1. Submit request and document', '1.1 Verify authenticity of records', 'None', '10 Minutes', 'Office of Admission and Registration Staff'),
(171, 37, 2, '2. Pay required fee', '2.1 Received payment', 'CAV - ₱50.00;Documentary stamp - ₱30.00', '2 Minutes', 'Cashier'),
(172, 37, 3, '3. Present Official Receipt', '3.1 Prepare CAV and endorse for release', 'None', '6 working days', 'Office of Admission and Registration Staff'),
(173, 37, 3, NULL, '3.2 Issue Claim slip', 'None', NULL, 'Office of Admission and Registration Staff'),
(174, 37, 4, '4. Claim document on scheduled Release date', '4.1 Release TOR', 'None', '2 minutes', 'Office of Admission and Registration Staff'),
(175, 38, 1, '1. State query or reference question/s', '1.1 Receive query from client', 'None', '1 minute', 'Librarian/Library Staff, Office of the University Library'),
(176, 38, 2, '2. Verify and negotiate the query with the reference librarian', '2.1 Conduct reference interview (to clarify and negotiate query)', 'None', '5 minutes', 'Librarian/Library Staff, Office of the University Library'),
(177, 38, 3, '3. Wait for the reference query to be processed and the information source determined', '3.1 Analyze the query and determine a possible information source', 'None', '5 minutes', 'Librarian/Library Staff, Office of the University Library'),
(178, 38, 3, NULL, '3.2 Identify an information source to find the answer to the query', 'None', '5 minutes', 'Librarian/Library Staff, Office of the University Library'),
(179, 38, 4, '4. Receive answer/s to query/queries', '4.1 If answer/s to the query are found, provide answers to the client\'s query/queries', 'None', '5 minutes', 'Librarian/Library Staff, Office of the University Library'),
(180, 38, 4, NULL, '4.2 If answer/s to the query are not found from the available library resources, refer to other libraries', 'None', '5 minutes', 'Librarian/Library Staff, Office of the University Library'),
(181, 39, 1, '1. The Requestor shall fill up the request form for the reviewing of CCTV and shall indicate the scope of date and time where the incident happens.', '1.1 The Security Personnel shall verify the requirements for reviewing the CCTV footage and forward the request to the Chief Security.', 'None', '1 Minute', 'CSU Admin Clerk'),
(182, 39, 1, NULL, '1.2 The Chief Security shall review the Request before the approval of the CCTV review.', 'None', '2 Minutes', 'Chief, Civil Security Unit'),
(183, 39, 1, NULL, '1.3 The Security Personnel shall carefully review the CCTV base on the requested scope of time and date where the incident happens. Incident report shall properly record to the security record book.', 'None', '4 Minutes', 'CSU Admin Clerk'),
(184, 40, 1, '1. Submit accomplished request form.', '1.1 Verify student identity and check Record availability', 'None', '10 Minutes', 'Office of Admission and Registration Staff'),
(185, 40, 2, '2. Pay required fee', '2.1 Received payment', 'Documentary stamp - ₱30.00, TOR - ₱150.00 per page', '2 Minutes', 'Cashier'),
(186, 40, 3, '3. Present Official Receipt', '3.1 Retrieve archived records, verify authenticity, prepare TOR', 'None', '6 days', 'Office of Admission and Registration Staff'),
(187, 40, 3, NULL, '3.2 Issue Claim slip', 'None', '6 days', 'Office of Admission and Registration Staff'),
(188, 40, 4, '4. Claim document on scheduled Release date', 'Release TOR', 'None', '2 minutes', 'Office of Admission and Registration Staff'),
(189, 41, 1, '1. Proceed to the Electronic Resources Room and log-in to the Facilities Use Transactions Log', '1.1 Instruct the client to log-in', 'None', '1 minute', 'Librarian/Library Staff, Office of the University Library'),
(190, 41, 2, '2. Present Library Card', '2.1 Receive the Library card', 'None', '1 minute', 'Librarian/Library Staff, Office of the University Library'),
(191, 41, 3, '4. Proceed to use available PCs', NULL, 'None', '1 minute', 'Librarian/Library Staff, Office of the University Library'),
(192, 42, 1, '1. University students must fill up the Google form (ID) given by MIS staff.', '1.1 The assigned MIS staff must check the google form submitted by the students.', 'None', '10 Minutes', 'Assigned MIS staff on the Issuance of ID, MIS Office'),
(193, 42, 1, NULL, '1.2 After verification the MIS staff shall start to gather information from the google form submitted by the students.', 'None', '', 'Assigned MIS staff on the Issuance of ID, MIS Office'),
(194, 42, 1, NULL, '1.3 The MIS personnel will encode the data from the google form submitted by the students.', 'None', '', 'Assigned MIS staff on the Issuance of ID, MIS Office'),
(195, 42, 1, NULL, '1.4 The MIS personnel will have the student\'s Scan photo captured digitally and student\'s E-signature after the encoding.', 'None', '', 'Assigned MIS staff on the Issuance of ID, MIS Office'),
(196, 42, 1, NULL, '1.5 The MIS personnel will generate and print the ID Card', 'None', '', 'Assigned MIS staff on the Issuance of ID, MIS Office'),
(197, 42, 2, '2. The student must wait their Adviser announcement if when they can get their Identification Card', '2.1 The MIS personnel will bring the Printed Identification Card in Office of the Registrar.', 'None', '10 Minutes', 'Assigned MIS staff on the Issuance of ID, MIS Office'),
(198, 43, 1, '1. Pay document fee at the Cashier\'s Office.', '1. Issue an Official Receipt', '10.00 pesos for one printed copy of the certificate', '1 minute', 'Cashier'),
(199, 43, 2, '2. Fill out the Good Moral Certificate Logbook in the office or online.', '2. Issue the Certification of Good Moral Character signed by the Guidance Counselor or designated personnel.', 'Not applicable', '1 minute', 'Guidance personnel'),
(200, 43, 3, '3. Fill out the Client Feedback Form online.', '3. Improve services based on evaluation', 'Not applicable', '1 minute', 'Guidance personnel'),
(201, 43, 4, '4. If the University Seal is needed, proceed to the Office of Admission and Registration.', '3. OAR seals the certificate.', 'Not applicable', '1 minute', 'Office of Admission and Registration Personnel'),
(202, 44, 1, '1. Present the letter of request. Accomplish logbook for reservation for the use of the NEUST Libraries space addressed to the University President, recommending approval by the University Librarian, and noted by the Campus Librarian.', '1.1 Receive reservation', 'None', '1 minute', 'Librarian/Library Staff, Office of the University Library'),
(203, 44, 2, '2. Wait for the request to be processed', '1.2 Verify the availability of the space requested and forward it to the Office of the University Librarian', 'None', '2 minutes', 'Librarian/Library Staff, Office of the University Library'),
(204, 44, 2, NULL, '2.1 Approval of request (from the office of the University Librarian)', 'None', '3 minutes', 'Librarian/Library Staff, Office of the University Library'),
(205, 45, 1, '1. Requesting clients will write the president asking permission to conduct a training or request a resource person', 'MIS office will forward the request to OUP', 'None', '5 Minutes', 'MIS Director / Email / Helpdesk Clerk'),
(206, 45, 2, '2. Once endorsed by the president, the MIS director will coordinate with the requesting client to discuss the details through an exploratory meeting.', 'MIS meeting with requesting party.', 'None', '1 Hour', 'MIS/Client'),
(207, 46, 1, '1. Student walks into the office or secures an appointment in-person or online.', '1.1 Guidance counselor provides counseling or telecounseling to student', 'Not applicable', '60 minutes', 'Registered Guidance Counselor and Guidance Staff'),
(208, 46, 1, NULL, '1.2 If the counselor is on official business, on leave, or in class, an appointment will be scheduled for the counselee', 'Not applicable', '60 minutes', 'Registered Guidance Counselor and Guidance Staff'),
(209, 46, 2, '2. Evaluate the service', '2.1 Improve services based on evaluation', 'Not applicable', '1 minute', 'Guidance personnel'),
(210, 46, 3, '3. If needed, schedule another session.', '3.1 Follow up with the counselee', 'Not applicable', '60 minutes', 'Registered Guidance Counselor'),
(211, 47, 1, '1. Present a valid ID with a picture and a referral letter at the Registration Desk', '1.1 Check/Verify the validity of the ID presented', 'None', '1 Minute', 'Librarian/Library Staff, Office of the University Library'),
(212, 47, 2, '2. Log in the logbook', '2.1 Check for completeness of information', 'None', '2 Minutes', 'Librarian/Library Staff, Office of the University Library'),
(213, 47, 3, '3. Receive a valid Library Card/Permit', '3.1 Issue a valid Library Card/Permit', 'None', '1 Minute', 'Librarian/Library Staff, Office of the University Library'),
(214, 48, 1, '1. Requesting clients will write the president asking permission to invite an MIS personnel/s consultant/s', 'MIS office will forward the request to OUP', 'None', '5 Minutes', 'MIS Director Email Helpdesk Clerk'),
(215, 48, 2, '2. Once endorsed by the president, the MIS director will coordinate with the requesting client to discuss the details through an exploratory meeting.', 'MIS meeting with requesting party.', 'None', '1 Hour', 'MIS/Client'),
(216, 49, 1, 'Present books for renewal', '1.1 Receive books for processing of renewal (Go to the Check-in tab of the Follett Circulation module to check-in first the book/s for renewal)', 'None', '1 minute', NULL),
(217, 49, 1, NULL, '1.2 Retrieve and pull-out the book card of the book/s for renewal', 'None', '1 minute', NULL),
(218, 49, 1, NULL, '1.3 Hand in book card to the client', 'None', NULL, NULL),
(219, 49, 2, 'Fill out the book card with name, college, and student/employee number, and present library staff at the Circulation desk', '2.1 Sign the book card', 'None', '2 Minutes', 'Librarian/Library Staff, Office of the University Library'),
(220, 49, 2, NULL, '2.2 Scan the barcode of the book to be checked out under the borrower\'s account', 'None', '2 Minutes', 'Librarian/Library Staff, Office of the University Library'),
(221, 49, 3, 'Wait for the books to be processed for renewal', '3.1 Print the receipt for the book/s renewed', 'None', '1 Minute', NULL),
(222, 49, 4, 'Receive book/s renewed with receipt', '4.1 Issue book/s borrowed plus the receipt', 'None', '1 Minute', NULL),
(223, 50, 1, 'Log in to the logbook and request the material to the Library staff on duty at the request desk', '1.1 Instruct the client to log in to the logbook and get the request', 'None', '1 Minute', NULL),
(224, 50, 2, 'Hand in request (Book call slip) to library staff on duty at the Request Desk', '2.1 Receive book call slip from client', 'None', '1 Minute', NULL),
(225, 50, 3, 'Wait for the requested title to be paged/searched from the shelves', '3.1 Get book/s from the shelf', 'None', '3 Minutes', 'Librarian/Library Staff, Office of the University Library'),
(226, 50, 4, 'Present ID (valid and countersigned) to the library staff', '4.1 Require the cilent to present ID and attach it to the book card,', 'None', '1 Minute', NULL),
(227, 50, 5, 'Present ID (valid and countersigned) to the library staff', '5.1 Require the client to present ID and attach it to the book card', 'None', '1 Minute', NULL),
(228, 51, 1, 'The University student shall visit the Facebook page of the Scholarship and Financial Assistance and fill out the TES E-Logbook.\n_\nhttps://bit.ly/CertOfFHE', '1.1 The SFAO personnel shall gather the information provided by the student TES in the E-Logbook.', 'None', '5 Minutes', 'SFAO personnel, Office of Student Affairs'),
(229, 51, 1, NULL, '1.2 The SFAO personnel shall verify and collect the accomplished TES E-Logbook.', 'None', '3 Minutes', 'SFAO personnel, Office of Student Affairs');
INSERT INTO `document_steps` (`id`, `document_id`, `step_number`, `client_step`, `agency_action`, `fees`, `processing_time`, `person_responsible`) VALUES
(230, 51, 1, NULL, '1.3 The SFAO personnel will upload the gathered Information to the UniFAST Portal.', 'None', '3 Minutes', 'SFAO personnel, Office of Student Affairs'),
(231, 11, 2, '2. The University student can print the signed PDF or proceed to the Office.', '', 'None', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `document_transaction_types`
--

CREATE TABLE `document_transaction_types` (
  `document_id` int(11) NOT NULL,
  `transaction_type_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `document_transaction_types`
--

INSERT INTO `document_transaction_types` (`document_id`, `transaction_type_id`) VALUES
(1, 1),
(2, 2),
(3, 2),
(4, 1),
(5, 2),
(6, 2),
(7, 1),
(8, 2),
(9, 3),
(10, 1),
(11, 2),
(12, 3),
(13, 1),
(14, 2),
(15, 3),
(16, 1),
(17, 3),
(18, 1),
(19, 2),
(20, 4),
(21, 1),
(22, 2),
(23, 2),
(24, 1),
(25, 5),
(26, 4),
(27, 1),
(28, 5),
(29, 6),
(30, 1),
(31, 5),
(32, 1),
(33, 5),
(34, 1),
(35, 5),
(36, 5),
(37, 1),
(38, 5),
(39, 5),
(40, 1),
(41, 5),
(42, 2),
(43, 2),
(44, 5),
(45, 2),
(46, 2),
(47, 2),
(48, 2),
(49, 5),
(50, 5),
(51, 2);

-- --------------------------------------------------------

--
-- Table structure for table `feedback_tickets`
--

CREATE TABLE `feedback_tickets` (
  `id` int(11) NOT NULL,
  `ticket_code` varchar(40) NOT NULL,
  `submitter_name` varchar(200) NOT NULL,
  `submitter_email` varchar(254) NOT NULL,
  `message` text NOT NULL,
  `status` enum('open','resolved','closed') NOT NULL DEFAULT 'open',
  `is_priority` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback_tickets`
--

INSERT INTO `feedback_tickets` (`id`, `ticket_code`, `submitter_name`, `submitter_email`, `message`, `status`, `is_priority`, `created_at`, `updated_at`) VALUES
(1, 'TKT-20261013173217-0D7A4D', 'Soledad Muning', 'kheyjheysoledad@gmail.com', 'heelllooooo', 'resolved', 1, '2026-10-13 09:32:17', '2026-10-15 07:06:30');

-- --------------------------------------------------------

--
-- Table structure for table `homepage_content`
--

CREATE TABLE `homepage_content` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `content_json` longtext NOT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `homepage_content`
--

INSERT INTO `homepage_content` (`id`, `content_json`, `updated_by`, `updated_at`) VALUES
(1, '{\"headline\":\"Welcome to your campus dashboarder\",\"intro\":\"Access campus services and navigate the campus with a single click.\\r\\nEverything you need for quick and easier campus operations is here.\",\"navigation_title\":\"Campus Navigation\",\"navigation_features\":[\"Quick route planning across buildings\",\"Office and room highlights for easier location finding\",\"Fast access to campus services and navigation support\"],\"campus_subtitle\":\"NEUST main campus\",\"buildings\":[\"CICT\",\"COED\",\"CMBT\",\"LHS\"],\"campus_description\":\"The campus map covers three floor levels, with room and office locations across the CICT, COED, CMBT, and LHS buildings.\",\"quick_title\":\"Quick destinations\",\"quick_subtitle\":\"Open a highlighted location on the campus map\",\"destinations\":[{\"label\":\"Clinic\",\"target\":\"wp-clinic\",\"icon\":\"fa-kit-medical\"},{\"label\":\"Registrar\",\"target\":\"wp-registrar\",\"icon\":\"fa-file-signature\"}],\"campus_position\":1,\"destinations_position\":2,\"campus_width\":5,\"destinations_width\":7}', 26, '2026-10-13 05:28:59');

-- --------------------------------------------------------

--
-- Table structure for table `home_page_cards`
--

CREATE TABLE `home_page_cards` (
  `card_key` varchar(40) NOT NULL,
  `content` longtext NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `home_page_cards`
--

INSERT INTO `home_page_cards` (`card_key`, `content`, `updated_at`) VALUES
('destinations', '{\"title\":\"Quick destinations\",\"subtitle\":\"Open a highlighted location on the campus map\",\"items\":[{\"label\":\"Clinic\",\"target\":\"wp-mv7134ts-sauy6c\"},{\"label\":\"Registrar\",\"target\":\"wp-mv7lb748-du2gm6\"},{\"label\":\"Cashier\",\"target\":\"wp-mv7lbx2e-fvv4vw\"}]}', '2026-10-21 02:49:05'),
('navigation', '{\"title\":\"Campus Navigation\",\"features\":[\"Quick route planning across buildings\",\"Office and room highlights for easier location finding\",\"Fast access to campus services and navigation support\"]}', '2026-10-13 09:52:30'),
('overview', '{\"title\":\"Campus at a glance\",\"subtitle\":\"NEUST main campus\",\"buildings\":[\"CICT\",\"COED\",\"CMBT\",\"LHS\"],\"description\":\"The campus map covers three floor levels, with room and office locations across the CICT, COED, CMBT, and LHS buildings.\"}', '2026-10-13 06:02:51'),
('welcome', '{\"badge\":\"NEUST Smart Campussy\",\"title\":\"Welcome to your campus dashboard\",\"description\":\"Access campus services and navigate the campus with a single click. Everything you need for quick and easier campus operations is here.\",\"button\":\"Open Campus Map\"}', '2026-10-13 06:23:43');

-- --------------------------------------------------------

--
-- Table structure for table `offices`
--

CREATE TABLE `offices` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `offices`
--

INSERT INTO `offices` (`id`, `name`) VALUES
(4, 'Accounting Office'),
(16, 'Business Permits and Licensing Office (BPLO)'),
(5, 'Cashier\'s Office'),
(18, 'City Social Welfare and Development Office (CSWDO)'),
(12, 'Civil Security Unit'),
(7, 'Corporate and Business Affairs Office (CBAO) / Office of the President'),
(8, 'Corporate and Business Affairs Office - EFWRS'),
(9, 'Corporate and Business Affairs Office - RENTAL OF UNIVERSITY FACILITIES'),
(14, 'Guidance and Counseling Office'),
(11, 'Health Services Unit (HSU)'),
(10, 'Hostel and Dormitory Services Unit (under CBAO & Auxiliary Services)'),
(13, 'Management Information Systems Office'),
(1, 'Office of Admission and Registration'),
(15, 'Office of Student Organizations Activities and Development'),
(3, 'Office of Student Organizations, Activities and Development'),
(17, 'Office of the Barangay Captain / Secretarial Division'),
(2, 'Office of the Student Affairs/ Scholarship and Financial Assistance'),
(6, 'Office of the University Library');

-- --------------------------------------------------------

--
-- Table structure for table `requirements`
--

CREATE TABLE `requirements` (
  `id` int(11) NOT NULL,
  `document_id` int(11) NOT NULL,
  `requirement_name` text NOT NULL,
  `where_to_secure` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `requirements`
--

INSERT INTO `requirements` (`id`, `document_id`, `requirement_name`, `where_to_secure`) VALUES
(1, 1, '2x2 picture', 'Student applicant'),
(2, 1, 'E-signature', 'Student applicant'),
(3, 1, 'Grade 12 Report Card (SF9) with 1st Quarter Grades or Student Permanent Record (SF 10)', 'Student applicant'),
(4, 2, 'Photocopy of Certificate of Registration', 'The client will provide'),
(5, 3, 'Application for Recognition (NEUST-OSAS-F026) (3 copies)', 'NEUST students/college or campus student and advisers'),
(6, 3, 'Letter of acceptance of adviser (NEUST-OSAS-F027) (3 copies)', NULL),
(7, 3, 'List of officers and members (NEUST-OSAS-F029) (3 copies)', NULL),
(8, 3, 'List of Projects and Proposed activities (NEUST-OSAS-F030) (3 copies)', NULL),
(9, 3, 'Constitution and By laws (3 copies)', NULL),
(10, 4, 'Certified True Copy of Form 137-A/SF10 (Grade 5)', 'Student applicant'),
(11, 4, 'Certified True Copy of Report Card/SF9 With 1st Quarter Grades (Grade 6)', 'Student applicant'),
(12, 4, 'Two pieces of identical 2x2 picture with white background.', 'Student applicant'),
(13, 4, 'Certificate of Good Moral Character.', 'Student applicant'),
(14, 5, 'Photocopy of Certificate of Registration', 'The client will provide'),
(15, 6, 'Application for Recognition (NEUST-OSAS-F026) (3 copies)', 'NEUST students/college or campus student and advisers'),
(16, 6, 'Letter of acceptance of adviser (NEUST-OSAS-F027) (3 copies)', NULL),
(17, 6, 'List of officers and members (NEUST-OSAS-F029) (3 copies)', NULL),
(18, 6, 'List of Projects and Proposed activities (NEUST-OSAS-F030) (3 copies)', NULL),
(19, 6, 'Renewal of Recognition (NEUST-OSAS-F031) (3 copies)', NULL),
(20, 6, 'Constitution and By Laws (3 copies)', NULL),
(21, 7, 'Form 137 and 138 (Original copy)', 'Qualified applicant'),
(22, 8, 'Photocopy of Certificate of Registration', 'The client will provide'),
(23, 8, 'Photocopy of NEUST student ID', NULL),
(24, 8, 'Photocopy DSWD Household ID or Listahanan ID', NULL),
(25, 9, 'For plane fare, quotations of three travel agencies or its equivalent for foreign travel (1 copy)', 'University personnel'),
(26, 9, 'Payment Details - Name of host/sponsoring agency/organization, bank details (in case of deposit)', NULL),
(27, 9, 'Computation of daily travel expenses (DTE)', 'Accounting Office'),
(28, 9, 'Certification from the accountant that the previous cash advance has been liquidated and accounted for in the books (for Cash advance)', NULL),
(29, 9, 'Disbursement Voucher', NULL),
(30, 10, 'Original Form 137 and 138', 'Qualified applicants'),
(31, 11, 'Certificate of Registration', 'The client will provide'),
(32, 12, 'Disbursement Voucher with necessary attachments (minimum of 3 copies)', 'University personnel'),
(33, 12, 'Payroll with necessary attachment (minimum of 3 copies)', NULL),
(34, 13, 'Student login username and password', 'Old college students (enrolled last semester/year)'),
(35, 14, 'Photocopy of Certificate of Registration', 'The client will provide'),
(36, 15, 'Disbursement Voucher with necessary attachments (minimum of 3 copies)', 'University personnel'),
(37, 15, 'Payroll with necessary attachment (minimum of 3 copies)', NULL),
(38, 16, 'Original copy of TOR in Undergraduate Degree (for applicant in Masteral Studies)', 'Student-Applicant'),
(39, 16, 'TOR in Master\'s Degree for applicant in Doctoral Studies', NULL),
(40, 16, '1 pc Passport Size Photograph', NULL),
(41, 16, '2 letters of Recommendation', NULL),
(42, 17, 'Disbursement Voucher with necessary attachments (minimum of 3 copies)', 'University personnel'),
(43, 17, 'Payroll with necessary attachment (minimum of 3 copies)', NULL),
(44, 18, 'Latest 2x2 formal picture, Selfie and digital signature', 'Student-Applicant'),
(46, 19, 'Certificate of registration (1 copy)', 'The client will provide'),
(47, 19, '1x1 ID Picture (1 copy)', NULL),
(48, 20, 'Letter of intent', 'Applicant'),
(49, 20, 'Resume', 'Applicant'),
(50, 20, 'Government-issued ID', 'Applicant'),
(51, 20, 'Endorsement Letter', 'CBAO'),
(52, 20, 'Contract of Lease', 'CBAO'),
(53, 20, 'Post-Dated Checks', 'Applicant'),
(54, 20, 'Business Permit', 'Applicant'),
(55, 20, 'DTI & BIR Registration', 'Applicant'),
(56, 20, 'Sanitary Permit', 'Applicant'),
(57, 20, 'Health Card', 'Applicant'),
(58, 20, 'Official Receipts (Rental & Utilities)', 'CBAO'),
(59, 20, 'Rental ledger', 'CBAO'),
(60, 20, 'Water & Electricity bills', 'Service Providers'),
(61, 20, 'Water & Electricity ledgers', 'CBAO'),
(62, 20, 'Monthly Report of Accounts Receivable', 'CBAO'),
(63, 20, 'Notice of Disconnection', 'CBAO'),
(64, 20, 'Report of Disconnection & Documentation', 'Building Maintenance'),
(65, 21, 'Original Form 138', 'Student'),
(66, 22, 'Clearance form', 'The client will provide'),
(67, 23, 'Order slip (filled by staff)', 'Business Center'),
(68, 23, 'Payment (cash/GCash etc.), Order Slip', 'Client / Internal'),
(69, 23, 'Order Slip, Official Receipt', 'EFWRS Office'),
(70, 23, 'Delivery Receipt (prepared by EFWRS)', 'EFWRS Office'),
(71, 23, 'Delivery Receipt (original & duplicate)', 'Provided by EFWRS'),
(72, 23, 'Delivery Receipt Duplicate (signed by client)', 'Internal'),
(73, 24, 'Student login username and password', 'Current Junior High School Students;(Completers of Grade 10)'),
(74, 25, 'Library articles, books, and other materials', 'NEUST faculty/students'),
(75, 26, 'Letter of Intent', 'Provided by CBAO Staff in-charge'),
(76, 26, 'Facility Reservation Slip', 'To be completed with Staff in-charge Client'),
(77, 26, 'Event Details (Schedule, Date, Time, Venue, Participants, Title, Arrangements)', NULL),
(78, 26, 'End-user Details (Contact Information, Organization, etc.)', NULL),
(79, 27, 'Original Transcript of Records (1 copy)', 'Student-Applicant'),
(80, 27, 'Certificate of Good Moral Character (1 copy)', NULL),
(81, 27, 'Certificate of Honorable Dismissal (1 copy)', NULL),
(82, 27, 'Evaluation Form (1 copy)', NULL),
(83, 28, 'Date Due slip', 'NEUST faculty/students'),
(84, 28, 'Library card', NULL),
(85, 28, 'Book card', NULL),
(86, 28, 'Official Receipt', NULL),
(87, 29, 'Duly accomplished Accommodation Application Form', 'Application Form - Hostel/Dormitory Office or University Website'),
(88, 29, 'Valid ID (University ID or Government-issued)', 'Billing Statement - Dormitory/ Hostel In-Charge'),
(89, 29, 'Payment Receipt or Proof of Billing', 'Approved Communication (for non-paying occupants such as scholars or university guests) - Issuing University Office (e.g., Scholarships Office, Admin Dept.)'),
(90, 29, 'Approved Communication (for non-paying occupants such as scholars or university guests)', NULL),
(91, 30, 'Accomplished request Form', 'OAR'),
(92, 30, 'Valid ID', 'Client'),
(93, 30, 'Official Receipt', 'Cashier\'s Office'),
(94, 31, 'Student Health Profile (1 copy)', 'NEUST Students'),
(95, 31, 'PhilHealth Membership Form', 'NEUST students/college or campus student and advisers'),
(96, 31, 'Annual Physical Examinations (Medical/Dental)', NULL),
(97, 32, 'Accomplished Request Form', 'OAR'),
(98, 32, 'Valid ID', 'Client'),
(99, 32, 'Official Receipt', 'Cashier'),
(100, 32, 'Clearance Form', 'OAR'),
(101, 33, 'Valid ID (1 copy)', 'University personnel, students, visitors and guests'),
(102, 33, 'Claim Form (1 copy)', NULL),
(103, 34, 'Accomplished Request Form', 'OAR'),
(104, 34, 'Valid Government-Issued ID', 'Client'),
(105, 34, 'Official Receipt', 'Cashier'),
(106, 35, 'Valid ID for the current semester', 'NEUST faculty/students'),
(107, 35, 'Copy of Faculty/Employee\'s Appointment', NULL),
(108, 36, 'Gate Pass form (1 photocopy)', 'University personnel, students and concessionaires'),
(109, 36, 'Vehicles OR/CR (1 photocopy)', NULL),
(110, 36, 'Driver\'s license (1 photocopy), 2x2 picture (1 original copy)', NULL),
(111, 36, 'If employee or student (photocopy of ID); If student (1 photocopy of COR)', NULL),
(112, 37, 'Accomplished Request Form', 'OAR'),
(113, 37, 'Original Academic Documents (TOR/Diploma)', 'Client'),
(114, 37, 'Valid Government-issued ID', 'Client'),
(115, 37, 'Official Receipt', 'Cashier'),
(116, 38, 'Valid ID for the current semester', 'NEUST faculty/students'),
(117, 38, 'Library card', NULL),
(118, 39, 'Request Form (1 copy)', 'University personnel, students, Outside Visitors/Guests'),
(119, 40, 'Accomplished Request Form', 'OAR'),
(120, 40, 'Valid Government-issued ID', 'Client'),
(121, 40, 'Affidavit of Loss (if applicable)', 'Client'),
(122, 40, 'Official Receipt', 'Cashier\'s Office'),
(123, 41, 'Valid ID for the current semester', 'NEUST faculty/students'),
(124, 41, 'Library card', NULL),
(125, 42, 'Fully accomplished Google form for Identification Card given by MIS Staff.', 'University students'),
(126, 43, 'Official Receipt amounting to Php 10.00 per copy', 'Cashier\'s Office'),
(127, 43, 'Authorization letter signed by the student or graduate and a valid ID of the requesting party and that of the representative', 'Student, Graduate and their Representative'),
(128, 44, 'Request Letter', 'NEUST faculty/students'),
(129, 44, 'Valid ID', NULL),
(130, 45, 'Letter of Request addressed to the President', 'Provided by requesting party'),
(131, 45, 'Training Design', NULL),
(132, 47, 'Graduate students from other universities/schools, private and government researchers, who need to have access', 'The client will provide'),
(133, 47, 'Valid ID with picture', NULL),
(134, 47, 'Referral letter', NULL),
(135, 48, 'Letter of Request addressed to the President', 'Provided by requesting party'),
(136, 48, 'Training Design', NULL),
(137, 49, 'Library logbook', 'NEUST faculty/students'),
(138, 49, 'Log sheet (1 Copy)', NULL),
(139, 49, 'Library card', NULL),
(140, 50, 'Valid ID for the current semester, Library Card', 'NEUST faculty/students'),
(141, 51, 'Photocopy of Certificate of Registration', 'The client will provide'),
(142, 51, 'Photocopy of NEUST student ID', NULL),
(143, 51, 'Photocopy DSWD Household ID or Listahanan ID', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`role_id`, `role_name`) VALUES
(0, 'User'),
(1, 'Admin');

-- --------------------------------------------------------

--
-- Table structure for table `total`
--

CREATE TABLE `total` (
  `id` int(11) NOT NULL,
  `document_id` int(11) DEFAULT NULL,
  `fees` varchar(255) DEFAULT NULL,
  `time` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `total`
--

INSERT INTO `total` (`id`, `document_id`, `fees`, `time`) VALUES
(1, 1, 'None', '4 days, 1 hr, 47 minutes'),
(2, 2, 'None', '2 minutes'),
(3, 3, 'None', '20 minutes'),
(4, 4, '₱ 180.00', '5 days, 8 minutes'),
(5, 5, 'None', '4 minutes'),
(6, 6, 'None', '20 minutes'),
(7, 7, '₱ 6,910.00', '10 minutes'),
(8, 8, 'None', '12 minutes'),
(9, 9, 'None', '17 minutes'),
(10, 10, 'None', '7 minutes'),
(11, 11, 'None', '2 minutes'),
(12, 12, 'None', '1 day, 13 minutes'),
(13, 13, 'None', '10 minutes'),
(14, 14, 'None', '4 minutes'),
(15, 15, 'None', '1 day, 17 minutes'),
(16, 16, '₱ 8,140.00 Masteral', '10 minutes if payment is made in NEUST Cashier\'s Office'),
(17, 16, '₱ 9,690.00 Doctoral', '15 minutes if payment is made through online'),
(18, 17, 'None', '10 minutes'),
(19, 18, 'None', '7 minutes if payment is made in NEUST Cashier\'s Office'),
(20, 18, 'None', '11 minutes if payment is made through online'),
(21, 19, 'None', '7 minutes'),
(22, 20, 'None', ''),
(23, 21, '₱ 8,610.00', '7 minutes'),
(24, 22, 'None', '7 minutes'),
(25, 23, 'None', '52 minutes'),
(26, 24, '₱ 3,337.50', '7 minutes'),
(27, 25, 'None', '8 minutes'),
(28, 26, 'None', '8 days'),
(29, 27, 'None', '17 minutes'),
(30, 28, 'None', '16 minutes'),
(31, 29, 'None', '8 days'),
(32, 30, 'None', '8 minutes'),
(33, 31, 'None', '30 minutes'),
(34, 32, 'None', '3 days, 9 minutes frontline processing'),
(35, 33, 'None', '5 minutes'),
(36, 34, 'None', '3 days, 10 minutes frontline processing'),
(37, 35, 'None', '8 minutes'),
(38, 36, 'None', '1 day, 4 minutes'),
(39, 37, 'None', '6 days, 14 minutes frontline processing'),
(40, 38, 'None', '26 minutes'),
(41, 39, 'None', '7 minutes'),
(42, 40, 'None', '6 days, 14 minutes frontline processing'),
(43, 41, 'None', '3 minutes'),
(44, 42, 'None', '20 minutes'),
(45, 43, '₱ 10.00 per copy', '3-4 minutes'),
(46, 44, 'None', '6 minutes'),
(47, 45, 'None', '1 hr, 5 minutes'),
(48, 46, 'None', '61-121 minutes'),
(49, 47, 'None', '4 minutes'),
(50, 48, 'None', '1 hr, 5 minutes'),
(51, 49, 'None', '8 minutes'),
(52, 50, 'None', '7 minutes'),
(53, 51, 'None', '11 minutes');

-- --------------------------------------------------------

--
-- Table structure for table `transaction_types`
--

CREATE TABLE `transaction_types` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaction_types`
--

INSERT INTO `transaction_types` (`id`, `name`) VALUES
(7, 'G2B - Government to Business'),
(2, 'G2C - Government to Citizen'),
(6, 'G2C - Government to Citizen (for students and guests); G2G - Government to Government (for internal university personnel and other government agencies)'),
(4, 'G2C - Government to Citizen / G2B - Government to Business'),
(5, 'G2C - Government to Citizen / G2G - Government to Government'),
(1, 'G2C - Government to Citizens (Students)'),
(3, 'G2G - Government to Government');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `registration_number` varchar(50) DEFAULT NULL,
  `student_number` varchar(50) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `barangay` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `sex` varchar(20) DEFAULT NULL,
  `mobile_number` varchar(20) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `password_reset_otp` varchar(255) DEFAULT NULL,
  `password_reset_expires_at` datetime DEFAULT NULL,
  `password_reset_attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `password_reset_locked_until` datetime DEFAULT NULL,
  `password_reset_requested_at` datetime DEFAULT NULL,
  `role_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `registration_number`, `student_number`, `last_name`, `first_name`, `middle_name`, `suffix`, `barangay`, `city`, `province`, `birthday`, `sex`, `mobile_number`, `email`, `password`, `password_reset_otp`, `password_reset_expires_at`, `password_reset_attempts`, `password_reset_locked_until`, `password_reset_requested_at`, `role_id`, `created_at`, `updated_at`) VALUES
(14, '09973225711', '09973225711', 'cubos', 'michael', 'villaviza', '', 'pambuan', 'gapan', 'nueva ecija', '2003-12-09', 'Male', '09973225711', 'michaelcubos54321@gmail.com', '$2y$10$F3v.hU4QOmwIiATl4SFqxeXe8SdiT3PSDP/kaUXiww3SMXErZNLGm', NULL, NULL, 0, NULL, NULL, 1, '2026-09-28 11:10:49', '2026-10-02 05:33:08'),
(18, 'REG-2026-001', '26-0001', 'Octanil', 'Abe', 'Santos', '', 'Poblacion', 'San Isidro', 'Nueva Ecija', '2002-04-12', 'Male', '09171234567', 'akoctanil@gmail.com', '$2y$10$WbjEBrtag8kmZtFPQwXbMeUjCCi3Bp1H7LV5/L5C6pEcwxwXz7wzu', NULL, NULL, 3, '2026-10-22 10:33:26', '2026-10-22 10:00:35', 0, '2026-09-29 04:45:58', '2026-10-22 02:03:26'),
(24, '1243124', '124124', 'Nath', 'Nath', 'N', '', 'Pambuan', 'Gapan', 'Nueva Ecija', '2026-10-02', 'Male', '09922598630', 'nathaniel.gabrieles69420@gmail.com', '$2y$10$QG.6NXoKw4K6PpnkBdGp9.9EcDcITC6ztjTAcTyjUYspSzGFyOMbG', NULL, NULL, 0, NULL, NULL, 1, '2026-10-02 06:00:00', '2026-10-21 02:44:57'),
(26, NULL, NULL, 'r', 'j', ' ', '', NULL, NULL, NULL, NULL, NULL, NULL, 'dela@gmail.com', '$2y$10$YBfLWHPlNwUMqA7Jut81JegRgdkOWSSIXPpZFCHNEoqqFxroKeIN6', NULL, NULL, 0, NULL, NULL, 1, '2026-10-02 09:28:16', '2026-10-12 06:27:21'),
(27, '24', '24', 'Muning', 'Soledad', '', '', 'San Josef', 'Jaen', 'Nueva Ecija', '1999-04-24', 'Male', '900900790909090', 'kheyjheysoledad@gmail.com', '$2y$10$Q19p/ipP2lsltejZ8tRgDOgIbk9MNmQu9apDt/ka/yUwzP5BZwxpm', NULL, NULL, 0, NULL, NULL, 0, '2026-10-13 09:09:23', '2026-10-13 09:09:23'),
(34, '2026-0001', 'S1234567', 'Santos', 'Juan', 'Dela Cruz', 'Jr.', 'San Isidro', 'Quezon City', 'Metro Manila', '2002-05-14', 'Male', '9171234567', 'juan.santos@example.com', '$2y$10$XwQkcGK8DbqnGcEipRv/GumufQDkxwx8UY3nX7oqbUCPe6M2Y6uhO', NULL, NULL, 0, NULL, NULL, 0, '2026-10-24 15:09:15', '2026-10-24 15:09:15'),
(35, '2026-0002', 'S1234568', 'Reyes', 'Maria', 'Clara', '', 'Bayanihan', 'Gapan City', 'Nueva Ecija', '2003-08-22', 'Female', '9189876543', 'maria.reyes@example.com', '$2y$10$4EnE4fH83jHdDmROrKVjqetSrWmOa92Q2jiJfYJ1BnVtvwlEJ7oNG', NULL, NULL, 0, NULL, NULL, 0, '2026-10-24 15:09:20', '2026-10-24 15:09:20'),
(36, '2026-0003', 'S1234569', 'Mendoza', 'Christian', 'Gonzales', '', 'Poblacion', 'Cabanatuan City', 'Nueva Ecija', '2001-11-03', 'Male', '9203456789', 'christian.mendoza@example.com', '$2y$10$NxuW/5Tb3oNCS2Z6lq96He7jdbZZoKb80ScqxxFvDnUbyJppCPZnK', NULL, NULL, 0, NULL, NULL, 0, '2026-10-24 15:09:25', '2026-10-24 15:09:25'),
(37, '2026-0004', 'S1234570', 'Garcia', 'Alyssa', 'Bautista', '', 'Bel-Air', 'Makati City', 'Metro Manila', '2004-01-19', 'Female', '9175551234', 'alyssa.garcia@example.com', '$2y$10$KPevlUmpNPW.jdR0l2n0sea7AwlMLVAfg5fcsu/5KaT6MajMoAcKS', NULL, NULL, 0, NULL, NULL, 0, '2026-10-24 15:09:29', '2026-10-24 15:09:29'),
(38, '2026-0005', 'S1234571', 'Cruz', 'Mark Anthony', 'Ramos', 'Iii', 'Malipangpang', 'San Ildefonso', 'Bulacan', '2002-12-10', 'Male', '9278889900', 'mark.cruz@example.com', '$2y$10$QRahnMusK9PvnR8Cq/JoJO0mb5g0eLC.RVSO6f0LsTCZBpr0aM3gC', NULL, NULL, 0, NULL, NULL, 0, '2026-10-24 15:09:34', '2026-10-24 15:09:34');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_created` (`created_at`),
  ADD KEY `idx_audit_user_created` (`user_id`,`created_at`),
  ADD KEY `idx_audit_action_created` (`action`,`created_at`),
  ADD KEY `idx_audit_module_created` (`module`,`created_at`),
  ADD KEY `idx_audit_target` (`target_type`,`target_id`),
  ADD KEY `idx_audit_status_created` (`status`,`created_at`);

--
-- Indexes for table `classifications`
--
ALTER TABLE `classifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `deleted_documents`
--
ALTER TABLE `deleted_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `office_id` (`office_id`),
  ADD KEY `classification_id` (`classification_id`);

--
-- Indexes for table `deleted_document_steps`
--
ALTER TABLE `deleted_document_steps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `document_id` (`document_id`);

--
-- Indexes for table `deleted_document_transaction_types`
--
ALTER TABLE `deleted_document_transaction_types`
  ADD PRIMARY KEY (`document_id`,`transaction_type_id`),
  ADD KEY `idx_document_id` (`document_id`),
  ADD KEY `idx_transaction_type_id` (`transaction_type_id`);

--
-- Indexes for table `deleted_requirements`
--
ALTER TABLE `deleted_requirements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `document_id` (`document_id`);

--
-- Indexes for table `deleted_total`
--
ALTER TABLE `deleted_total`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_total_documents` (`document_id`);

--
-- Indexes for table `deleted_users`
--
ALTER TABLE `deleted_users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_user_roles` (`role_id`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `office_id` (`office_id`),
  ADD KEY `classification_id` (`classification_id`);

--
-- Indexes for table `document_requests`
--
ALTER TABLE `document_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD UNIQUE KEY `uq_document_request_batch` (`request_batch_id`),
  ADD UNIQUE KEY `uq_document_request_access_token` (`request_access_token_hash`),
  ADD KEY `idx_document_requests_status_created` (`status`,`created_at`),
  ADD KEY `idx_document_requests_user` (`requester_user_id`);

--
-- Indexes for table `document_request_access_tokens`
--
ALTER TABLE `document_request_access_tokens`
  ADD PRIMARY KEY (`access_token_id`),
  ADD UNIQUE KEY `uq_document_request_access_token_hash` (`token_hash`),
  ADD KEY `idx_document_request_access_expiry` (`request_id`,`expires_at`);

--
-- Indexes for table `document_request_items`
--
ALTER TABLE `document_request_items`
  ADD PRIMARY KEY (`item_id`),
  ADD UNIQUE KEY `uq_document_request_item` (`request_id`,`source_document_id`),
  ADD KEY `idx_document_request_items_document` (`source_document_id`);

--
-- Indexes for table `document_steps`
--
ALTER TABLE `document_steps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `document_id` (`document_id`);

--
-- Indexes for table `document_transaction_types`
--
ALTER TABLE `document_transaction_types`
  ADD PRIMARY KEY (`document_id`,`transaction_type_id`),
  ADD KEY `idx_document_id` (`document_id`),
  ADD KEY `idx_transaction_type_id` (`transaction_type_id`);

--
-- Indexes for table `feedback_tickets`
--
ALTER TABLE `feedback_tickets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_feedback_tickets_ticket_code` (`ticket_code`),
  ADD KEY `idx_feedback_tickets_status_priority_created` (`status`,`is_priority`,`created_at`);

--
-- Indexes for table `homepage_content`
--
ALTER TABLE `homepage_content`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `home_page_cards`
--
ALTER TABLE `home_page_cards`
  ADD PRIMARY KEY (`card_key`);

--
-- Indexes for table `offices`
--
ALTER TABLE `offices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `requirements`
--
ALTER TABLE `requirements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `document_id` (`document_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`);

--
-- Indexes for table `total`
--
ALTER TABLE `total`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_total_documents` (`document_id`);

--
-- Indexes for table `transaction_types`
--
ALTER TABLE `transaction_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_user_roles` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=191;

--
-- AUTO_INCREMENT for table `classifications`
--
ALTER TABLE `classifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `deleted_documents`
--
ALTER TABLE `deleted_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `deleted_document_steps`
--
ALTER TABLE `deleted_document_steps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=290;

--
-- AUTO_INCREMENT for table `deleted_requirements`
--
ALTER TABLE `deleted_requirements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=195;

--
-- AUTO_INCREMENT for table `deleted_total`
--
ALTER TABLE `deleted_total`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT for table `deleted_users`
--
ALTER TABLE `deleted_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `document_requests`
--
ALTER TABLE `document_requests`
  MODIFY `request_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `document_request_access_tokens`
--
ALTER TABLE `document_request_access_tokens`
  MODIFY `access_token_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `document_request_items`
--
ALTER TABLE `document_request_items`
  MODIFY `item_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=91;

--
-- AUTO_INCREMENT for table `document_steps`
--
ALTER TABLE `document_steps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=290;

--
-- AUTO_INCREMENT for table `feedback_tickets`
--
ALTER TABLE `feedback_tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `offices`
--
ALTER TABLE `offices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `requirements`
--
ALTER TABLE `requirements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=195;

--
-- AUTO_INCREMENT for table `total`
--
ALTER TABLE `total`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT for table `transaction_types`
--
ALTER TABLE `transaction_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `documents_ibfk_2` FOREIGN KEY (`classification_id`) REFERENCES `classifications` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `document_request_access_tokens`
--
ALTER TABLE `document_request_access_tokens`
  ADD CONSTRAINT `fk_document_request_access_request` FOREIGN KEY (`request_id`) REFERENCES `document_requests` (`request_id`) ON DELETE CASCADE;

--
-- Constraints for table `document_request_items`
--
ALTER TABLE `document_request_items`
  ADD CONSTRAINT `fk_document_request_items_request` FOREIGN KEY (`request_id`) REFERENCES `document_requests` (`request_id`) ON DELETE CASCADE;

--
-- Constraints for table `document_steps`
--
ALTER TABLE `document_steps`
  ADD CONSTRAINT `document_steps_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `document_transaction_types`
--
ALTER TABLE `document_transaction_types`
  ADD CONSTRAINT `fk_document_transaction_types_document` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_document_transaction_types_transaction` FOREIGN KEY (`transaction_type_id`) REFERENCES `transaction_types` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `requirements`
--
ALTER TABLE `requirements`
  ADD CONSTRAINT `requirements_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `total`
--
ALTER TABLE `total`
  ADD CONSTRAINT `fk_total_documents` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user`
--
ALTER TABLE `user`
  ADD CONSTRAINT `fk_user_roles` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
