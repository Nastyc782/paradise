-- ============================================================
-- DIGITAL SMART CLASS — Professional E-Learning Database Schema
-- Compatible with MySQL 5.7+ / 8.0+ / MariaDB / phpMyAdmin
-- Database: digital_smart_class
-- ============================================================

CREATE DATABASE IF NOT EXISTS `digital_smart_class` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `digital_smart_class`;

-- 1. ROLES TABLE
CREATE TABLE IF NOT EXISTS `roles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL UNIQUE, -- 'admin', 'teacher', 'student', 'accountant'
    `display_name` VARCHAR(100) NOT NULL,
    `description` VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `roles` (`id`, `name`, `display_name`, `description`) VALUES
(1, 'admin', 'System Administrator', 'Full platform oversight, approvals, and controls'),
(2, 'teacher', 'Course Instructor', 'Creates courses, lessons, and reviews students'),
(3, 'student', 'Student Learner', 'Discovers, purchases, and studies courses'),
(4, 'accountant', 'Financial Accountant', 'Verifies payments, tracks balances, and audits revenue')
ON DUPLICATE KEY UPDATE `display_name` = VALUES(`display_name`);

-- 2. USERS TABLE
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `role_id` INT NOT NULL DEFAULT 3,
    `full_name` VARCHAR(120) NOT NULL,
    `email` VARCHAR(120) NOT NULL UNIQUE,
    `phone` VARCHAR(30) NULL,
    `password` VARCHAR(255) NOT NULL,
    `avatar` VARCHAR(255) DEFAULT 'assets/images/default-avatar.png',
    `qualification` VARCHAR(200) NULL,
    `experience` VARCHAR(100) NULL,
    `address` VARCHAR(255) NULL,
    `status` ENUM('active', 'inactive', 'suspended', 'pending') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. COURSE CATEGORIES TABLE
CREATE TABLE IF NOT EXISTS `course_categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT NULL,
    `icon` VARCHAR(50) DEFAULT 'fas fa-video',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `course_categories` (`id`, `name`, `slug`, `description`, `icon`) VALUES
(1, 'YouTube & Content Creation', 'youtube-content-creation', 'Learn YouTube from ground up, build channels, and scale audience', 'fab fa-youtube'),
(2, 'Content Creation', 'content-creation', 'Vertical video production, viral algorithms, and hooks', 'fas fa-bolt'),
(3, 'AI & Content Creation', 'ai-content-creation', 'AI scriptwriting, neural voiceovers, and automated production', 'fas fa-robot')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- 4. COURSES TABLE
CREATE TABLE IF NOT EXISTS `courses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT NOT NULL,
    `teacher_id` INT NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `slug` VARCHAR(220) NOT NULL UNIQUE,
    `subtitle` VARCHAR(255) NOT NULL,
    `description` LONGTEXT NOT NULL,
    `learning_objectives` TEXT NULL,
    `level` ENUM('Beginner', 'Beginner → Intermediate', 'Intermediate', 'Advanced', 'All Levels') DEFAULT 'Beginner',
    `duration_weeks` VARCHAR(50) DEFAULT '4 Weeks',
    `duration_hours` INT DEFAULT 6,
    `total_lessons` INT DEFAULT 10,
    `price` DECIMAL(10, 2) NOT NULL DEFAULT 30000.00,
    `currency` VARCHAR(10) DEFAULT 'RWF',
    `thumbnail` VARCHAR(255) DEFAULT 'assets/images/course-default.jpg',
    `is_featured` TINYINT(1) DEFAULT 1,
    `status` ENUM('draft', 'published', 'archived') DEFAULT 'published',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `course_categories`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`teacher_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4b. COURSE ASSIGNMENTS TABLE
CREATE TABLE IF NOT EXISTS `course_assignments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `course_id` INT NOT NULL,
    `module_id` INT NULL,
    `title` VARCHAR(200) NOT NULL,
    `instructions` TEXT NOT NULL,
    `submission_type` ENUM('text', 'file', 'link') DEFAULT 'text',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4c. QUIZZES AND AUTOMATIC MARKING TABLES
CREATE TABLE IF NOT EXISTS `quizzes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `course_id` INT NOT NULL,
    `module_id` INT NULL,
    `title` VARCHAR(200) NOT NULL,
    `description` TEXT NULL,
    `passing_score` TINYINT UNSIGNED DEFAULT 70,
    `is_final_exam` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `quiz_questions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `quiz_id` INT NOT NULL,
    `question` TEXT NOT NULL,
    `options_json` JSON NOT NULL,
    `correct_option` TINYINT UNSIGNED NOT NULL,
    `points` TINYINT UNSIGNED DEFAULT 1,
    `order_num` INT DEFAULT 1,
    FOREIGN KEY (`quiz_id`) REFERENCES `quizzes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `quiz_attempts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `quiz_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `score` DECIMAL(5,2) NOT NULL,
    `passed` TINYINT(1) NOT NULL DEFAULT 0,
    `answers_json` JSON NOT NULL,
    `completed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`quiz_id`) REFERENCES `quizzes`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4d. ASSIGNMENT SUBMISSIONS AND CERTIFICATES
CREATE TABLE IF NOT EXISTS `assignment_submissions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `assignment_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `submission_text` TEXT NULL,
    `file_path` VARCHAR(255) NULL,
    `status` ENUM('submitted', 'reviewed') DEFAULT 'submitted',
    `feedback` TEXT NULL,
    `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `assignment_user_unique` (`assignment_id`, `user_id`),
    FOREIGN KEY (`assignment_id`) REFERENCES `course_assignments`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `course_certificates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `course_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `certificate_code` VARCHAR(40) NOT NULL UNIQUE,
    `issued_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `certificate_course_user_unique` (`course_id`, `user_id`),
    FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. MODULES TABLE
CREATE TABLE IF NOT EXISTS `modules` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `course_id` INT NOT NULL,
    `title` VARCHAR(180) NOT NULL,
    `order_num` INT DEFAULT 1,
    FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. LESSONS TABLE
CREATE TABLE IF NOT EXISTS `lessons` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `module_id` INT NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `content` LONGTEXT NULL,
    `video_url` VARCHAR(255) NULL,
    `duration_minutes` INT DEFAULT 20,
    `order_num` INT DEFAULT 1,
    `is_free_preview` TINYINT(1) DEFAULT 0,
    FOREIGN KEY (`module_id`) REFERENCES `modules`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. MATERIALS TABLE (PDF, Presentations, Notes, Project Files)
CREATE TABLE IF NOT EXISTS `materials` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `course_id` INT NOT NULL,
    `lesson_id` INT NULL,
    `title` VARCHAR(180) NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `file_type` ENUM('pdf', 'presentation', 'document', 'zip', 'notes') DEFAULT 'pdf',
    `file_size` VARCHAR(50) DEFAULT '1.5 MB',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`lesson_id`) REFERENCES `lessons`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. ENROLLMENTS TABLE
CREATE TABLE IF NOT EXISTS `enrollments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `course_id` INT NOT NULL,
    `status` ENUM('pending', 'active', 'completed', 'cancelled') DEFAULT 'pending',
    `enrolled_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `activated_at` TIMESTAMP NULL,
    UNIQUE KEY `user_course_unique` (`user_id`, `course_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. PAYMENTS TABLE (MTN Mobile Money, Airtel Money, Bank Transfer)
CREATE TABLE IF NOT EXISTS `payments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `enrollment_id` INT NULL,
    `user_id` INT NOT NULL,
    `course_id` INT NOT NULL,
    `amount` DECIMAL(10, 2) NOT NULL,
    `currency` VARCHAR(10) DEFAULT 'RWF',
    `payment_method` VARCHAR(50) DEFAULT 'MTN Mobile Money',
    `transaction_ref` VARCHAR(100) NOT NULL,
    `payment_reference` VARCHAR(40) NULL UNIQUE,
    `sender_phone` VARCHAR(30) NULL,
    `proof_file` VARCHAR(255) NULL,
    `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    `accountant_note` TEXT NULL,
    `reviewed_by` INT NULL,
    `reviewed_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`reviewed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. VIDEO_PROGRESS TABLE (Tracks watch minutes & percentage)
CREATE TABLE IF NOT EXISTS `video_progress` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `lesson_id` INT NOT NULL,
    `watched_seconds` INT DEFAULT 0,
    `total_seconds` INT DEFAULT 0,
    `watch_percentage` DECIMAL(5, 2) DEFAULT 0.00,
    `is_completed` TINYINT(1) DEFAULT 0,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `user_video_lesson` (`user_id`, `lesson_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`lesson_id`) REFERENCES `lessons`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. LESSON_PROGRESS TABLE (Mark completed status)
CREATE TABLE IF NOT EXISTS `lesson_progress` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `course_id` INT NOT NULL,
    `lesson_id` INT NOT NULL,
    `is_completed` TINYINT(1) DEFAULT 1,
    `completed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `user_lesson_unique` (`user_id`, `lesson_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`lesson_id`) REFERENCES `lessons`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 12. TEACHER_APPLICATIONS TABLE (Instructor vetting workflow)
CREATE TABLE IF NOT EXISTS `teacher_applications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `full_name` VARCHAR(120) NOT NULL,
    `email` VARCHAR(120) NOT NULL,
    `phone` VARCHAR(30) NOT NULL,
    `qualification` VARCHAR(200) NOT NULL,
    `experience` VARCHAR(100) NOT NULL,
    `address` VARCHAR(255) NULL,
    `cv_file` VARCHAR(255) NULL,
    `certificate_file` VARCHAR(255) NULL,
    `photo_file` VARCHAR(255) NULL,
    `bio` TEXT NULL,
    `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    `admin_note` TEXT NULL,
    `reviewed_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`reviewed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 13. NOTIFICATIONS TABLE
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `title` VARCHAR(150) NOT NULL,
    `message` TEXT NOT NULL,
    `link` VARCHAR(255) NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 14. AUDIT_LOGS TABLE (Security and tracking)
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `action` VARCHAR(100) NOT NULL,
    `details` TEXT NULL,
    `ip_address` VARCHAR(45) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 15. COURSE REVIEWS TABLE
CREATE TABLE IF NOT EXISTS `course_reviews` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `course_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `rating` TINYINT UNSIGNED NOT NULL,
    `review` TEXT NULL,
    `status` ENUM('published', 'hidden') DEFAULT 'published',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `course_user_review_unique` (`course_id`, `user_id`),
    FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 16. COUPONS TABLE
CREATE TABLE IF NOT EXISTS `coupons` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(50) NOT NULL UNIQUE,
    `discount_type` ENUM('percentage', 'fixed') NOT NULL DEFAULT 'percentage',
    `discount_value` DECIMAL(10, 2) NOT NULL,
    `max_uses` INT NULL,
    `used_count` INT NOT NULL DEFAULT 0,
    `starts_at` DATETIME NULL,
    `expires_at` DATETIME NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 17. COUPON USES TABLE
CREATE TABLE IF NOT EXISTS `coupon_uses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `coupon_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `payment_id` INT NULL,
    `used_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `coupon_user_unique` (`coupon_id`, `user_id`),
    FOREIGN KEY (`coupon_id`) REFERENCES `coupons`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`payment_id`) REFERENCES `payments`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- The schema intentionally contains no users, courses, payments, enrollments,
-- lessons, materials, or teacher applications. Real records are created through
-- the application after the first administrator is bootstrapped at setup-admin.php.
