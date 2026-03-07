-- Database Schema for Online Examination System

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- 1. Users Table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) UNIQUE NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'student') DEFAULT 'student',
    `register_number` VARCHAR(50),
    `degree` VARCHAR(100),
    `batch` VARCHAR(20),
    `college` VARCHAR(100) DEFAULT 'VEL TECH UNIVERSITY',
    `faculty_id` VARCHAR(50),
    `theme_color` VARCHAR(20) DEFAULT '#4F46E5',
    `dark_mode` TINYINT(1) DEFAULT 0,
    `avatar_id` INT DEFAULT 1,
    `cover_gradient` VARCHAR(255) DEFAULT 'linear-gradient(135deg, #4F46E5 0%, #8B5CF6 100%)',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Exams Table
CREATE TABLE IF NOT EXISTS `exams` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(100) NOT NULL,
    `type` ENUM('MCQ', 'Coding') NOT NULL,
    `duration` INT NOT NULL COMMENT 'Duration in minutes',
    `status` ENUM('active', 'inactive') DEFAULT 'inactive',
    `total_marks` INT DEFAULT 0,
    `start_date` DATETIME,
    `end_date` DATETIME,
    `created_by` INT,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Questions Table
CREATE TABLE IF NOT EXISTS `questions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `exam_id` INT NOT NULL,
    `question_text` TEXT NOT NULL,
    `type` ENUM('MCQ', 'Coding') NOT NULL,
    -- MCQ Specific Fields
    `option_a` TEXT,
    `option_b` TEXT,
    `option_c` TEXT,
    `option_d` TEXT,
    `correct_option` CHAR(1) COMMENT 'A, B, C, or D',
    -- Coding Specific Fields
    `coding_template` TEXT,
    `test_case_1_input` TEXT,
    `test_case_1_output` TEXT,
    `test_case_2_input` TEXT,
    `test_case_2_output` TEXT,
    `test_case_3_input` TEXT,
    `test_case_3_output` TEXT,
    `marks` INT DEFAULT 1,
    FOREIGN KEY (`exam_id`) REFERENCES `exams`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Submissions Table (Storing Student Answers/Scores)
CREATE TABLE IF NOT EXISTS `submissions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `exam_id` INT NOT NULL,
    `score` DECIMAL(5,2) DEFAULT 0.00,
    `status` ENUM('pending', 'evaluated', 'published') DEFAULT 'pending',
    `started_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `finished_at` DATETIME,
    FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`exam_id`) REFERENCES `exams`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Student Answers (Details for each question)
CREATE TABLE IF NOT EXISTS `student_answers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `submission_id` INT NOT NULL,
    `question_id` INT NOT NULL,
    `selected_option` CHAR(1), -- For MCQ
    `submitted_code` TEXT,      -- For Coding
    `student_language` VARCHAR(20), -- For Coding (Java, Python, C, C++)
    `is_correct` TINYINT(1) DEFAULT 0,
    `marks_obtained` DECIMAL(5,2) DEFAULT 0.00,
    `passed_test_cases` INT DEFAULT 0,
    `total_test_cases` INT DEFAULT 0,
    `evaluation_status` ENUM('Accepted', 'Partial', 'Wrong Answer') DEFAULT NULL,
    FOREIGN KEY (`submission_id`) REFERENCES `submissions`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`question_id`) REFERENCES `questions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert Sample Admin
INSERT INTO `users` (`full_name`, `email`, `password`, `role`) VALUES 
('Admin User', 'admin@example.com', 'admin123', 'admin');
-- Password is 'admin123'

COMMIT;
