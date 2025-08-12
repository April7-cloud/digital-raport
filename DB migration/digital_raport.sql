-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 12, 2025 at 05:17 AM
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
-- Database: `digital_raport`
--

-- --------------------------------------------------------

--
-- Table structure for table `assessments`
--

CREATE TABLE `assessments` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `type` enum('UH','UTS','UAS','Tugas') NOT NULL,
  `score` decimal(5,2) NOT NULL,
  `date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `assessment_type` enum('UH','UTS','UAS','Tugas') NOT NULL,
  `semester` enum('1','2') NOT NULL,
  `academic_year` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `id` int(11) NOT NULL,
  `level` varchar(10) NOT NULL COMMENT 'Class level (e.g., X, XI, XII)',
  `name` varchar(50) NOT NULL COMMENT 'Class name (e.g., IPA 1, IPS 2)',
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grades`
--

CREATE TABLE `grades` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `semester` tinyint(1) NOT NULL,
  `academic_year` varchar(9) NOT NULL,
  `grade` decimal(5,2) DEFAULT NULL,
  `competency_achievement` text DEFAULT NULL,
  `predikat` varchar(1) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `keterampilan` text DEFAULT NULL,
  `note` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `journal_assignments`
--

CREATE TABLE `journal_assignments` (
  `id` int(11) NOT NULL,
  `journal_id` int(11) NOT NULL,
  `assignment_name` varchar(255) NOT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `max_score` decimal(5,2) DEFAULT 100.00,
  `assignment_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `journal_entries`
--

CREATE TABLE `journal_entries` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `class` varchar(50) NOT NULL,
  `semester` enum('1','2') NOT NULL,
  `academic_year` varchar(9) NOT NULL,
  `entry_date` date NOT NULL,
  `entry_time` time NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempt_time` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `email` varchar(100) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `description`, `is_system`, `created_at`, `updated_at`) VALUES
(1, 'users.view', 'View users', 1, '2025-08-04 05:12:13', '2025-08-04 05:12:13'),
(2, 'users.create', 'Create users', 1, '2025-08-04 05:12:13', '2025-08-04 05:12:13'),
(3, 'users.edit', 'Edit users', 1, '2025-08-04 05:12:13', '2025-08-04 05:12:13'),
(4, 'users.delete', 'Delete users', 1, '2025-08-04 05:12:13', '2025-08-04 05:12:13'),
(5, 'users.roles', 'Manage user roles', 1, '2025-08-04 05:12:13', '2025-08-04 05:12:13'),
(6, 'roles.view', 'View roles', 1, '2025-08-04 05:12:13', '2025-08-04 05:12:13'),
(7, 'roles.create', 'Create roles', 1, '2025-08-04 05:12:13', '2025-08-04 05:12:13'),
(8, 'roles.edit', 'Edit roles', 1, '2025-08-04 05:12:13', '2025-08-04 05:12:13'),
(9, 'roles.delete', 'Delete roles', 1, '2025-08-04 05:12:13', '2025-08-04 05:12:13'),
(10, 'roles.permissions', 'Manage role permissions', 1, '2025-08-04 05:12:13', '2025-08-04 05:12:13'),
(11, 'students.view', 'View students', 1, '2025-08-04 05:12:13', '2025-08-04 05:12:13'),
(12, 'students.create', 'Create students', 1, '2025-08-04 05:12:13', '2025-08-04 05:12:13'),
(13, 'students.edit', 'Edit students', 1, '2025-08-04 05:12:13', '2025-08-04 05:12:13'),
(14, 'students.delete', 'Delete students', 1, '2025-08-04 05:12:13', '2025-08-04 05:12:13'),
(15, 'grades.view', 'View grades', 1, '2025-08-04 05:12:13', '2025-08-04 05:12:13'),
(16, 'grades.create', 'Create grades', 1, '2025-08-04 05:12:13', '2025-08-04 05:12:13'),
(17, 'grades.edit', 'Edit grades', 1, '2025-08-04 05:12:13', '2025-08-04 05:12:13'),
(18, 'grades.delete', 'Delete grades', 1, '2025-08-04 05:12:13', '2025-08-04 05:12:13'),
(19, 'reports.view', 'View reports', 1, '2025-08-04 05:12:13', '2025-08-04 05:12:13'),
(20, 'reports.generate', 'Generate reports', 1, '2025-08-04 05:12:13', '2025-08-04 05:12:13'),
(21, 'view_dashboard', 'View Dashboard', 0, '2025-08-04 07:54:04', '2025-08-04 07:54:04'),
(22, 'manage_users', 'Manage Users', 0, '2025-08-04 07:54:04', '2025-08-04 07:54:04'),
(23, 'manage_roles', 'Manage Roles', 0, '2025-08-04 07:54:04', '2025-08-04 07:54:04'),
(24, 'manage_permissions', 'Manage Permissions', 0, '2025-08-04 07:54:04', '2025-08-04 07:54:04'),
(25, 'manage_students', 'Manage Students', 0, '2025-08-04 07:54:04', '2025-08-04 07:54:04'),
(26, 'manage_teachers', 'Manage Teachers', 0, '2025-08-04 07:54:04', '2025-08-04 07:54:04'),
(27, 'manage_classes', 'Manage Classes', 0, '2025-08-04 07:54:04', '2025-08-04 07:54:04'),
(28, 'manage_subjects', 'Manage Subjects', 0, '2025-08-04 07:54:04', '2025-08-04 07:54:04'),
(29, 'manage_assessments', 'Manage Assessments', 0, '2025-08-04 07:54:05', '2025-08-04 07:54:05'),
(30, 'view_reports', 'View Reports', 0, '2025-08-04 07:54:05', '2025-08-04 07:54:05'),
(31, 'export_reports', 'Export Reports', 0, '2025-08-04 07:54:05', '2025-08-04 07:54:05');

-- --------------------------------------------------------

--
-- Table structure for table `report_cards`
--

CREATE TABLE `report_cards` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `semester` enum('1','2') NOT NULL,
  `academic_year` varchar(10) NOT NULL,
  `class` varchar(10) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `description`, `is_system`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'Administrator', 1, '2025-08-04 05:12:13', '2025-08-04 05:12:13'),
(2, 'teacher', 'Teacher', 1, '2025-08-04 05:12:13', '2025-08-04 05:12:13'),
(3, 'student', 'Student', 1, '2025-08-04 05:12:13', '2025-08-04 05:12:13'),
(4, 'parent', 'Parent/Guardian', 1, '2025-08-04 05:12:13', '2025-08-04 05:12:13');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES
(1, 1, '2025-08-04 05:12:13'),
(1, 2, '2025-08-04 05:12:13'),
(1, 3, '2025-08-04 05:12:13'),
(1, 4, '2025-08-04 05:12:13'),
(1, 5, '2025-08-04 05:12:13'),
(1, 6, '2025-08-04 05:12:13'),
(1, 7, '2025-08-04 05:12:13'),
(1, 8, '2025-08-04 05:12:13'),
(1, 9, '2025-08-04 05:12:13'),
(1, 10, '2025-08-04 05:12:13'),
(1, 11, '2025-08-04 05:12:13'),
(1, 12, '2025-08-04 05:12:13'),
(1, 13, '2025-08-04 05:12:13'),
(1, 14, '2025-08-04 05:12:13'),
(1, 15, '2025-08-04 05:12:13'),
(1, 16, '2025-08-04 05:12:13'),
(1, 17, '2025-08-04 05:12:13'),
(1, 18, '2025-08-04 05:12:13'),
(1, 19, '2025-08-04 05:12:13'),
(1, 20, '2025-08-04 05:12:13'),
(1, 21, '2025-08-04 07:54:05'),
(1, 22, '2025-08-04 07:54:05'),
(1, 23, '2025-08-04 07:54:05'),
(1, 24, '2025-08-04 07:54:05'),
(1, 25, '2025-08-04 07:54:05'),
(1, 26, '2025-08-04 07:54:05'),
(1, 27, '2025-08-04 07:54:05'),
(1, 28, '2025-08-04 07:54:05'),
(1, 29, '2025-08-04 07:54:05'),
(1, 30, '2025-08-04 07:54:05'),
(1, 31, '2025-08-04 07:54:05'),
(2, 11, '2025-08-04 05:12:13'),
(2, 15, '2025-08-04 05:12:13'),
(2, 16, '2025-08-04 05:12:13'),
(2, 17, '2025-08-04 05:12:13'),
(2, 19, '2025-08-04 05:12:13'),
(2, 20, '2025-08-04 05:12:13'),
(2, 21, '2025-08-04 07:54:05'),
(2, 25, '2025-08-04 07:54:05'),
(2, 29, '2025-08-04 07:54:05'),
(2, 30, '2025-08-04 07:54:05'),
(3, 11, '2025-08-04 05:12:13'),
(3, 15, '2025-08-04 05:12:13'),
(3, 19, '2025-08-04 05:12:13'),
(3, 21, '2025-08-04 07:54:05'),
(4, 11, '2025-08-04 05:12:13'),
(4, 15, '2025-08-04 05:12:13'),
(4, 19, '2025-08-04 05:12:13');

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(128) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `nis` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `class` varchar(20) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `gender` enum('L','P') DEFAULT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `birth_place` varchar(50) DEFAULT NULL,
  `parent_name` varchar(100) DEFAULT NULL,
  `parent_phone` varchar(15) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `nis`, `name`, `class`, `birth_date`, `gender`, `address`, `phone`, `email`, `created_at`, `updated_at`, `birth_place`, `parent_name`, `parent_phone`) VALUES
(4, '123456', 'John Doe', '10A', NULL, 'L', '123 Main St', '555-1234', NULL, '2025-08-11 08:04:38', '2025-08-11 08:04:38', NULL, 'Jane Doe', NULL),
(5, '654321', 'Jane Smith', '10B', NULL, 'P', '456 Elm St', '555-5678', NULL, '2025-08-11 08:04:38', '2025-08-11 08:04:38', NULL, 'John Smith', NULL),
(6, '10032', 'ABRAHAM VASCALIS ORBASAN', 'SMP VII A', NULL, 'L', 'Jalan Mawar Putih Rembug RT 001 RW 011 Sidomulyo Batu', '0853-3041-2409', NULL, '2025-08-11 08:04:38', '2025-08-11 08:04:38', NULL, 'Catur Muchamad Orbason', NULL),
(7, '10033', 'AHMAD DANESH AKRAM', 'SMP VII A', NULL, 'L', 'Jalan Perintis 134 A RT 001 RW 007 Babat Lamongan', '0816-5476-011', NULL, '2025-08-11 08:04:38', '2025-08-11 08:04:38', NULL, 'Ahmad Zainal Fanani', NULL),
(8, '10034', 'ATHARAKA AHMAD RAJENDRA', 'SMP VII A', NULL, 'L', 'Kranggan Gg IA Blok B No 17 RT 004 RW 001 Kranggan Mojokerto', '0813-3500-0346', NULL, '2025-08-11 08:04:38', '2025-08-11 08:04:38', NULL, 'Didik Indrawanto', NULL),
(9, '10035', 'BINTANG SAKTI AGUSTINO', 'SMP VII A', NULL, 'L', 'Jln Raya Tlogomas RT 004 RW 006 Tlogomas Lowokwaru Kota Malang', '0819-4598-6611', NULL, '2025-08-11 08:04:38', '2025-08-11 08:04:38', NULL, 'Hutri Agustino', NULL),
(10, '10036', 'DANENDRA WARDANA MUZZAKI', 'SMP VII A', NULL, 'L', 'Jln Raya Kendalpayak RT 003 RW 003 Kendalpayak Pakisaji Malang', '0895-3232-73810', NULL, '2025-08-11 08:04:38', '2025-08-11 08:04:38', NULL, 'Munir Muzakki', NULL),
(11, '10037', 'DASTAN MUHAMMAD DIEN IZZAT', 'SMP VII A', NULL, 'L', 'Jln Kanjeng Jimat No 34 RT 001 RW 006 Gedangan Sidoarjo', '0821-3128-0404', NULL, '2025-08-11 08:04:38', '2025-08-11 08:04:38', NULL, 'Misbahuddin', NULL),
(12, '10038', 'DZABDAN ARKANANTA NASRULLAH', 'SMP VII A', NULL, 'L', 'Medokan Ayu Utara VII B7 RT 005 RW 011 Rungkut Surabaya', '0856-4923-5633', NULL, '2025-08-11 08:04:38', '2025-08-11 08:04:38', NULL, 'Dede Nasrullah', NULL),
(13, '10039', 'FATHAN ALMAIZAN ZHAFAR', 'SMP VII A', NULL, 'L', 'Dsn Sukomaju RT 032 RW 010 Kunir Lumajang', '0878-7476-6716', NULL, '2025-08-11 08:04:38', '2025-08-11 08:04:38', NULL, 'Hariyono Effendi', NULL),
(14, '10040', 'HAFIDZURIZKY RAZQA AL-FATIH', 'SMP VII A', NULL, 'L', 'Dsn Talangrejo RT 004 RW 005 Gunungsari Bumiaji Kota Batu', '0823-3123-0721', NULL, '2025-08-11 08:04:38', '2025-08-11 08:04:38', NULL, 'Budi Suhandriyo', NULL),
(15, '10041', 'HAIDAR LABIB EL BIRRUNI', 'SMP VII A', NULL, 'L', 'Jln Ikan Belanak II 8 BP Kulon Sidokumpul Gresik', '0821-3168-8818', NULL, '2025-08-11 08:04:38', '2025-08-11 08:04:38', NULL, 'Andi Hakim Ahmad', NULL),
(16, '10042', 'KEIYFAL ZAFNI AL-FARISI', 'SMP VII A', NULL, 'L', 'Perum Puri Kalitengah Blok K No 8 RT 003 RW 005 Tanggulangin Sidoarjo', '0821-3917-2065', NULL, '2025-08-11 08:04:38', '2025-08-11 08:04:38', NULL, 'Hasan Ubaidillah', NULL),
(17, '10043', 'KHALEEFA IZZAT FARAZI', 'SMP VII A', NULL, 'L', 'Jalan Mentawan No 12 RT 004 RW 002 Sananwetan Kota Blitar', '0812-5224-9398', NULL, '2025-08-11 08:04:38', '2025-08-11 08:04:38', NULL, 'Choirul Umam', NULL),
(18, '10044', 'M RIZKI YOGA RAMADHAN', 'SMP VII A', NULL, 'L', 'Jalan DI Panjaitan No 74A RT 001 RW 002 Purbosuman Ponorogo', '0813-3455-5277', NULL, '2025-08-11 08:04:38', '2025-08-11 08:04:38', NULL, 'Yudha Yoga Pamungkas', NULL),
(19, '10045', 'MU’ADZ AHMAD ABYAN', 'SMP VII A', NULL, 'L', 'Perum Berlian Citra Kertanegara Blok C7 RT 006 RW 003 Kebalenan Banyuwangi', '0813-3689-3993', NULL, '2025-08-11 08:04:38', '2025-08-11 08:04:38', NULL, 'Sucipto', NULL),
(20, '10046', 'MUHAMMAD FAIZ', 'SMP VII A', NULL, 'L', 'Jln Tirtosari Perum Graha TIrta Asri C2 RT 001 RW 009 Landungsari Dau Malang', '0812-5262-1965', NULL, '2025-08-11 08:04:38', '2025-08-11 08:04:38', NULL, 'Supriyono', NULL),
(21, '10047', 'MUHAMMAD HASBIY MAULANA', 'SMP VII A', NULL, 'L', 'Jemur Andayani I 31 RT 001 RW 001 Jemur Wonosari Wonocolo Surabaya', '0812-5096-427', NULL, '2025-08-11 08:04:38', '2025-08-11 08:04:38', NULL, 'Puryanto', NULL),
(22, '10048', 'MUHAMMAD SALMAN AD-DAVIQ', 'SMP VII A', NULL, 'L', 'Ds Penatarsewu RT 001 RW 001 Tanggulangin Sidoarjo', '0812-5974-9573', NULL, '2025-08-11 08:04:38', '2025-08-11 08:04:38', NULL, 'Tamhid Masyhudi', NULL),
(23, '10049', 'MUHAMMAD SATRIA ARDANA', 'SMP VII A', NULL, 'L', 'Perum Villa Jasmine 2 Blok E No 24 RT 016 RW 004 Sumberrejo Wonoayu Sidoarjo', '0815-5472-7460', NULL, '2025-08-11 08:04:38', '2025-08-11 08:04:38', NULL, 'Arif Setiawan', NULL),
(24, '10050', 'MUHAMMAD SHAHJAHAN', 'SMP VII A', NULL, 'L', 'Dsn Bangunrejo RT 006 RW 002 Mojopurogede Bungah Gresik', '0822-3332-2765', NULL, '2025-08-11 08:04:38', '2025-08-11 08:04:38', NULL, 'Saroni', NULL),
(25, '10051', 'MUHAMMAD SULTHON BRILLIANT', 'SMP VII A', NULL, 'L', 'Dsn Trembelang RT 006 RW 001 Cluring Banyuwangi', '0812-3531-7345', NULL, '2025-08-11 08:04:38', '2025-08-11 08:04:38', NULL, 'Aris Sugiyanto', NULL),
(26, '10052', 'PRABA BASKARA SALIM', 'SMP VII A', NULL, 'L', 'Perum Sulfat Garden Kav 7 RT 002 RW 004 Pandanwangi Blimbing Kota Malang', '0811-3670-674', NULL, '2025-08-11 08:04:38', '2025-08-11 08:04:38', NULL, 'Muhammad Zan Sukmadi', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `code` varchar(10) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `class` varchar(20) NOT NULL,
  `kkm` decimal(5,2) NOT NULL DEFAULT 70.00,
  `academic_year` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `code`, `name`, `description`, `teacher_id`, `class`, `kkm`, `academic_year`, `created_at`, `updated_at`) VALUES
(9, 'MTK', 'Matematika', 'ajsgfksajgkjdfgakjsdf\r\n\r\njfbkjasgakhsfdkahsfdjahsf', 1, '10 Reguler B', 70.00, '2025/2026', '2025-08-08 03:15:19', '2025-08-08 06:16:30'),
(11, 'OLH', 'Olah Raga', 'sdaksgdajhsgjagsdkjagsa', 3, 'XI IPA A', 70.00, '2025/2026', '2025-08-08 06:18:13', '2025-08-08 06:18:13'),
(13, 'MTK', 'Matematika', 'sjkdakgajgfakjgs', 2, 'X IPA D', 70.00, '2025/2026', '2025-08-08 07:56:05', '2025-08-08 07:56:05'),
(14, 'SOS', 'Sosiologi', 'Mata Pelajaran sosiologi SMP', 6, 'SMP VII A', 70.00, '2025/2026', '2025-08-11 06:24:45', '2025-08-11 06:24:45'),
(15, 'BING', 'Bahasa Inggris', 'Mata pelajaran Bahasa Inggris', 8, 'SMP VII A', 70.00, '2025/2026', '2025-08-11 06:25:59', '2025-08-11 06:25:59'),
(16, 'BIO', 'Biologi', 'Mata Pelajaran Biologi', 10, 'SMP VII A', 70.00, '2025/2026', '2025-08-11 06:27:30', '2025-08-11 06:27:30');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int(11) NOT NULL,
  `nip` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `gender` enum('male','female') DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `nip`, `name`, `email`, `phone`, `address`, `gender`, `photo`, `is_active`, `created_at`, `updated_at`, `user_id`) VALUES
(1, '123456789', 'Teacher Test', 'teacher@example.com', '0811234567', 'nbvjhgvjkhlhk', 'male', NULL, 1, '2025-08-05 07:39:18', '2025-08-08 12:58:44', NULL),
(2, '21231243', 'susan', 'asdas@sadas.it', '0811 1234 1234', 'skjdfnklanflkandf', NULL, NULL, 1, '2025-08-06 09:26:57', '2025-08-06 13:46:03', 3),
(3, 'G12345', 'insan insan', 'insan@amf.sch.id', '081234567890', 'Jl. Pendidikan No. 123', 'male', NULL, 1, '2025-08-06 13:40:42', '2025-08-06 13:40:42', 4),
(6, '199202202403006', 'Akbar Najamuddin', 'amf@amf.sch.id', '0823 0163 9209', 'Dsn. Karobelah 2, RT.003, RW. 003, Kel. Karobelah, Kec. Mojoagung', NULL, NULL, 1, '2025-08-11 06:06:57', '2025-08-11 06:06:57', NULL),
(8, '199405202405007', 'Nur Aini Yan Meinati', 'amf1@amf.sch.id', '0817 0344 6565', 'Perum Pinayungan Asri B2', NULL, NULL, 1, '2025-08-11 06:08:28', '2025-08-11 06:08:28', NULL),
(9, '199908202403008', 'Nadiya Rikha Zhafirah', 'amf2@amf.sch.id', '081 237 047 269', 'Jl. Renang No. 2', NULL, NULL, 1, '2025-08-11 06:09:46', '2025-08-11 06:09:46', NULL),
(10, '199701202403009', 'Zumrotin Firdaus', 'amf3@amf.sch.id', '0857 3168 2008', 'Jl. Balai Desa Kepuharjo, Gg. Randu Asri No. 18 Karangploso', NULL, NULL, 1, '2025-08-11 06:10:50', '2025-08-11 06:10:50', NULL),
(11, '198802202403010', 'Muhammad Musa', 'amf4@amf.sch.id', '0877 3129 5979', 'Kumendaman, Kel. Suryodiningratan, Kec. Matrijeron, Kota Yogyakarta', NULL, NULL, 1, '2025-08-11 06:12:17', '2025-08-11 06:12:17', NULL),
(12, '199807202403011', 'Siti Nur Aini', 'amf5@amf.sch.id', '0812 1632 9146', 'Perum Bumi Mangli Permai Blok IA No. 11', NULL, NULL, 1, '2025-08-11 06:13:52', '2025-08-11 06:13:52', NULL),
(13, '199805202403013', 'Baiq Tety Yuriana', 'amf7@amf.sch.id', '0823 3900 3362', 'Jl. Tirto Utomo Gg. IV No. 42 D 1', NULL, NULL, 1, '2025-08-11 06:15:20', '2025-08-11 06:15:20', NULL),
(14, '199911202403014', 'Salsabila Arinda Putri', 'amf8@amf.sch.id', '0878 5918 1619', 'JL. Kolonel Sugiono V No. 550 Kota Malang', NULL, NULL, 1, '2025-08-11 06:16:39', '2025-08-11 06:16:39', NULL),
(15, '200004202403016', 'Yunus Muhammad Zulkifli', 'amf9@amf.sch.id', '0812 8395 7632', 'Perum Griya Permata Alam Blok KE No. 7, Ngijo', NULL, NULL, 1, '2025-08-11 06:17:49', '2025-08-11 06:17:49', NULL),
(16, '199705202407017', 'Fendiyanto', 'amf10@amf.sch.id', '0819 3404 0764', 'Jl. Raya Torjek Kec. Kangayan Kab. Sumenep', NULL, NULL, 1, '2025-08-11 06:18:51', '2025-08-11 06:18:51', NULL),
(17, '200105202407018', 'Nabila Almayda', 'amf12@amf.sch.id', '0823 3193 7346', 'Jl. Sigura-Gura III No. 28', NULL, NULL, 1, '2025-08-11 06:20:27', '2025-08-11 06:20:27', NULL),
(18, '200110202409019', 'Yolanda Pradiva Dinatri Prameswari', 'amf14@amf.sch.id', '0896 3056 8982', 'Jl. Bulu Tangkis, Kel. Sisir, Kec. Batu, Kota Batu.', NULL, NULL, 1, '2025-08-11 06:21:57', '2025-08-11 06:21:57', NULL),
(19, '199605202405020', 'Yusuf Yoga Adiutama', 'amf15@amf.sch.id', '0857 3190 0066', 'Jl. Tirto Mulyo No. 36 Klandungan, Landungsari, Malang', NULL, NULL, 1, '2025-08-11 06:23:03', '2025-08-11 06:23:03', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `must_change_password` tinyint(1) NOT NULL DEFAULT 0,
  `last_login` datetime DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL,
  `failed_login_attempts` int(11) NOT NULL DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `role` enum('admin','teacher','student') NOT NULL DEFAULT 'teacher'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `is_active`, `must_change_password`, `last_login`, `last_login_ip`, `failed_login_attempts`, `locked_until`, `created_at`, `updated_at`, `role`) VALUES
(2, 'admin', 'admin@example.com', '$2y$10$7WJ/AeeFbZBvdNXJ1rtA5..7p4gg8L4R.qnn.VpEfTxY5JawL/WYS', 'Administrator', 1, 0, '2025-08-12 10:02:17', '::1', 0, NULL, '2025-08-06 06:46:09', '2025-08-12 03:02:17', 'admin'),
(3, 'susan', 'susan@amf.sch.id', '0599ee6397cd7a880c6d1e746950c1a640580ca44a05629f138720d58a0be7c7', 'susan susan', 1, 0, NULL, NULL, 0, NULL, '2025-08-06 13:24:16', '2025-08-06 13:24:16', 'teacher'),
(4, 'insan', 'insan@amf.sch.id', '36a68f9c6508245a95335f85c10ab71a9e598a9b0875ad1381752677010b0544', 'insan insan', 1, 0, NULL, NULL, 0, NULL, '2025-08-06 13:35:16', '2025-08-06 13:35:16', 'teacher');

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE `user_roles` (
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assessments`
--
ALTER TABLE `assessments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `level_name_unique` (`level`,`name`);

--
-- Indexes for table `grades`
--
ALTER TABLE `grades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_grade` (`student_id`,`subject_id`),
  ADD UNIQUE KEY `unique_grade_entry` (`student_id`,`subject_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `idx_grades_student` (`student_id`),
  ADD KEY `idx_grades_subject` (`subject_id`);

--
-- Indexes for table `journal_assignments`
--
ALTER TABLE `journal_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `journal_id` (`journal_id`);

--
-- Indexes for table `journal_entries`
--
ALTER TABLE `journal_entries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `class` (`class`),
  ADD KEY `semester_academic_year` (`semester`,`academic_year`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ip_address` (`ip_address`),
  ADD KEY `username` (`username`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`email`),
  ADD KEY `token` (`token`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `report_cards`
--
ALTER TABLE `report_cards`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_semester_year` (`student_id`,`semester`,`academic_year`);

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
  ADD PRIMARY KEY (`role_id`,`permission_id`),
  ADD KEY `permission_id` (`permission_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `last_activity` (`last_activity`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nis` (`nis`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code_class_unique` (`code`,`class`),
  ADD KEY `fk_subject_teacher` (`teacher_id`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nip` (`nip`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`user_id`,`role_id`),
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `assessments`
--
ALTER TABLE `assessments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grades`
--
ALTER TABLE `grades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `journal_assignments`
--
ALTER TABLE `journal_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `journal_entries`
--
ALTER TABLE `journal_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `report_cards`
--
ALTER TABLE `report_cards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assessments`
--
ALTER TABLE `assessments`
  ADD CONSTRAINT `assessments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  ADD CONSTRAINT `assessments_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`),
  ADD CONSTRAINT `assessments_student_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `grades`
--
ALTER TABLE `grades`
  ADD CONSTRAINT `fk_grades_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_grades_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `grades_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `grades_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `journal_assignments`
--
ALTER TABLE `journal_assignments`
  ADD CONSTRAINT `fk_journal_assignments` FOREIGN KEY (`journal_id`) REFERENCES `journal_entries` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `journal_entries`
--
ALTER TABLE `journal_entries`
  ADD CONSTRAINT `fk_journal_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_journal_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `report_cards`
--
ALTER TABLE `report_cards`
  ADD CONSTRAINT `report_cards_student_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subjects`
--
ALTER TABLE `subjects`
  ADD CONSTRAINT `fk_subject_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_subjects_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `teachers_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD CONSTRAINT `user_roles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_roles_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
