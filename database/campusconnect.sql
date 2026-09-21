-- ==========================================================
-- CampusConnect: College Event & Club Management Portal
-- Database: campusconnect
-- Compatible with: MySQL 5.7+ / MariaDB / XAMPP phpMyAdmin
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `campusconnect` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `campusconnect`;

-- --------------------------------------------------------
-- Table: users
-- Roles: 'student', 'organizer', 'admin'
-- Passwords below are hashed using PHP password_hash(..., PASSWORD_DEFAULT)
-- Demo Passwords:
--   admin@campus.edu          => admin123
--   organizer.tech@campus.edu => org123
--   organizer.cult@campus.edu => org123
--   anshul@student.edu        => student123
--   rahul@student.edu         => student123
--   priya@student.edu         => student123
--   arjun@student.edu         => student123
-- --------------------------------------------------------

DROP TABLE IF EXISTS `registrations`;
DROP TABLE IF EXISTS `events`;
DROP TABLE IF EXISTS `clubs`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('student', 'organizer', 'admin') NOT NULL DEFAULT 'student',
  `department` VARCHAR(100) DEFAULT 'Information Technology',
  `phone` VARCHAR(20) DEFAULT NULL,
  `enrollment_no` VARCHAR(50) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Users
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `department`, `phone`, `enrollment_no`, `created_at`) VALUES
(1, 'System Administrator', 'admin@campus.edu', 'y$LBaQ9Bw5vZhahdvKo7HhPuvSBw2VC/6DG2fnOJLxaQtqVQfNxZwhe', 'admin', 'Administration', '9876543210', 'ADM-001', NOW()),
(2, 'Aarav Sharma (Coding Club)', 'organizer.tech@campus.edu', 'y$IiBx9OpYhQ/mjhJlQJJncuEnHcAcv8JVqH8OK8z7V3iL/DLZya0AW', 'organizer', 'Information Technology', '9811223344', 'ORG-001', NOW()),
(3, 'Sneha Verma (Cultural Society)', 'organizer.cult@campus.edu', 'y$IiBx9OpYhQ/mjhJlQJJncuEnHcAcv8JVqH8OK8z7V3iL/DLZya0AW', 'organizer', 'Computer Science', '9822334455', 'ORG-002', NOW()),
(4, 'Anshul Jain', 'anshul@student.edu', 'y$E2h26CQONGocIuq2XbBuMe6NVe4BXHNOqY9BmVOevtQ5gi2xK5tmG', 'student', 'Information Technology', '9899001122', '01815603124', NOW()),
(5, 'Rahul Sharma', 'rahul@student.edu', 'y$E2h26CQONGocIuq2XbBuMe6NVe4BXHNOqY9BmVOevtQ5gi2xK5tmG', 'student', 'Information Technology', '9876512345', '01915603124', NOW()),
(6, 'Priya Patel', 'priya@student.edu', 'y$E2h26CQONGocIuq2XbBuMe6NVe4BXHNOqY9BmVOevtQ5gi2xK5tmG', 'student', 'Computer Science', '9812345678', '02015603124', NOW()),
(7, 'Arjun Mehta', 'arjun@student.edu', 'y$E2h26CQONGocIuq2XbBuMe6NVe4BXHNOqY9BmVOevtQ5gi2xK5tmG', 'student', 'Electronics & Comm.', '9833445566', '02115603124', NOW());

-- --------------------------------------------------------
-- Table: clubs
-- Represents college societies/clubs managed by an organizer
-- --------------------------------------------------------

CREATE TABLE `clubs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `club_name` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `category` VARCHAR(50) DEFAULT 'Technical',
  `organizer_id` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`organizer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Clubs
INSERT INTO `clubs` (`id`, `club_name`, `description`, `category`, `organizer_id`, `created_at`) VALUES
(1, 'Coding Club (ByteCraft)', 'Dedicated to competitive programming, hackathons, and open source web development.', 'Technical', 2, NOW()),
(2, 'Cultural Society (Taranuum)', 'Fosters musical, theatrical, dance, and fine arts talents across the college.', 'Cultural', 3, NOW()),
(3, 'Robotics & IoT Club', 'Hands-on hardware development, drone design, and robotics competitions.', 'Technical', 2, NOW()),
(4, 'Sports Council', 'Organizing intra-college tournaments in cricket, football, basketball, and athletics.', 'Sports', 2, NOW());

-- --------------------------------------------------------
-- Table: events
-- Status: 'pending', 'approved', 'rejected', 'completed'
-- --------------------------------------------------------

CREATE TABLE `events` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `club_id` INT NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `description` TEXT NOT NULL,
  `category` ENUM('Technical', 'Cultural', 'Sports', 'Workshop', 'Competition', 'Seminar') NOT NULL DEFAULT 'Technical',
  `event_date` DATE NOT NULL,
  `event_time` TIME NOT NULL,
  `venue` VARCHAR(150) NOT NULL,
  `max_participants` INT NOT NULL DEFAULT 100,
  `registration_deadline` DATE NOT NULL,
  `banner_image` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Events
INSERT INTO `events` (`id`, `club_id`, `title`, `description`, `category`, `event_date`, `event_time`, `venue`, `max_participants`, `registration_deadline`, `banner_image`, `status`, `created_at`) VALUES
(1, 1, 'HackSphere 2026: 24-Hour Hackathon', 'Join the largest annual college hackathon! Build innovative web and AI solutions, win prizes worth Rs. 50,000, and network with industry mentors.', 'Competition', DATE_ADD(CURDATE(), INTERVAL 14 DAY), '09:00:00', 'Main Auditorium & Computer Labs', 120, DATE_ADD(CURDATE(), INTERVAL 10 DAY), 'tech_hackathon.svg', 'approved', NOW()),
(2, 1, 'Full-Stack Web Development Bootcamp', 'Hands-on 3-day workshop on modern web technologies including HTML5, CSS3, JavaScript, PHP, and MySQL database design.', 'Workshop', DATE_ADD(CURDATE(), INTERVAL 7 DAY), '14:00:00', 'Lab 3, IT Department', 60, DATE_ADD(CURDATE(), INTERVAL 5 DAY), 'tech_workshop.svg', 'approved', NOW()),
(3, 2, 'Rhythm 2026: Annual Cultural Fest', 'A vibrant celebration of music, street play, classical dance, and band performances. Open to all students with exciting stage events.', 'Cultural', DATE_ADD(CURDATE(), INTERVAL 20 DAY), '11:00:00', 'Open Air Amphitheatre', 250, DATE_ADD(CURDATE(), INTERVAL 16 DAY), 'cultural_fest.svg', 'approved', NOW()),
(4, 4, 'Inter-Department Cricket Tournament', 'Battle of the departments! Form your team and compete for the prestigious College Championship Trophy.', 'Sports', DATE_ADD(CURDATE(), INTERVAL 12 DAY), '08:30:00', 'College Sports Ground', 80, DATE_ADD(CURDATE(), INTERVAL 8 DAY), 'sports_cricket.svg', 'approved', NOW()),
(5, 3, 'AI & Robotics Guest Seminar', 'Distinguished lecture by leading industry researchers on embedded robotics, computer vision, and career paths in artificial intelligence.', 'Seminar', DATE_ADD(CURDATE(), INTERVAL 9 DAY), '10:30:00', 'Seminar Hall 1', 90, DATE_ADD(CURDATE(), INTERVAL 6 DAY), 'seminar_ai.svg', 'approved', NOW()),
(6, 1, 'CodeSprint: Algorithmic Duel', 'Fast-paced algorithmic challenge testing data structures and problem solving speed on HackerRank platform.', 'Technical', DATE_ADD(CURDATE(), INTERVAL 18 DAY), '15:00:00', 'Central Computing Centre', 50, DATE_ADD(CURDATE(), INTERVAL 15 DAY), 'tech_coding.svg', 'pending', NOW()),
(7, 2, 'Acoustic Night & Open Mic', 'Unplugged singing, poetry, and storytelling evening under the stars. Refreshments provided.', 'Cultural', DATE_ADD(CURDATE(), INTERVAL 25 DAY), '17:30:00', 'Student Activity Centre (SAC)', 70, DATE_ADD(CURDATE(), INTERVAL 22 DAY), 'cultural_openmic.svg', 'pending', NOW());

-- --------------------------------------------------------
-- Table: registrations
-- Prevents duplicate registrations with unique composite key
-- --------------------------------------------------------

CREATE TABLE `registrations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `event_id` INT NOT NULL,
  `student_id` INT NOT NULL,
  `registration_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('confirmed', 'cancelled') DEFAULT 'confirmed',
  UNIQUE KEY `unique_event_student` (`event_id`, `student_id`),
  FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Registrations
INSERT INTO `registrations` (`id`, `event_id`, `student_id`, `registration_date`, `status`) VALUES
(1, 1, 4, NOW(), 'confirmed'),
(2, 2, 4, NOW(), 'confirmed'),
(3, 1, 5, NOW(), 'confirmed'),
(4, 3, 5, NOW(), 'confirmed'),
(5, 2, 6, NOW(), 'confirmed'),
(6, 4, 6, NOW(), 'confirmed'),
(7, 5, 7, NOW(), 'confirmed');
