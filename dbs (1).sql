-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 19, 2025 at 05:42 PM
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
-- Database: `dbs`
--

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `post_id`, `user_id`, `content`, `created_at`) VALUES
(1, 17, 9, ';kef;kwe', '2025-11-14 16:13:32'),
(2, 11, 9, '.dkfnlkd', '2025-11-14 16:14:07'),
(5, 18577, 21, 'scjiljqwilf', '2025-11-18 13:30:14');

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `visibility` enum('public','private') NOT NULL DEFAULT 'public',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`id`, `user_id`, `subject_id`, `title`, `content`, `image`, `visibility`, `created_at`, `updated_at`) VALUES
(1, 9, 1, 'effeedef', 'fefedef', '1761594905_Screenshot 2025-10-08 155934.png', 'public', '2025-10-27 17:15:41', '2025-11-18 10:55:32'),
(2, 9, 1, 'DÂTBASEqwdewfewfewfwefewfewf', 'AI đdcfefewfewfdewfefewfewfeeferfeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee', '1762623699_Screenshot 2025-10-08 155943.png', 'public', '2025-11-07 15:46:50', NULL),
(3, 21, 2, 'Bai viet cua toi', 'nhap ten bai viet', '1763464232_chicken.jpg', 'public', '2025-11-18 11:10:32', NULL),
(4, 21, 2, 'ưef', 'ewfe', NULL, 'private', '2025-11-18 15:28:23', NULL),
(5, 21, 3, 'fwef', 'ewf', NULL, 'public', '2025-11-18 15:40:16', NULL),
(6, 21, 3, 'è', 'ewfew', NULL, 'public', '2025-11-18 15:40:24', NULL),
(7, 21, 4, 'fwe', 'fewf', NULL, 'public', '2025-11-19 09:31:43', NULL),
(8, 21, 2, 'ừe', 'fewf', NULL, 'public', '2025-11-19 09:51:51', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `post_likes`
--

CREATE TABLE `post_likes` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `post_likes`
--

INSERT INTO `post_likes` (`id`, `post_id`, `user_id`, `created_at`) VALUES
(2, 11, 9, '2025-11-14 16:14:10'),
(8, 17, 9, '2025-11-15 08:05:35'),
(32, 18577, 21, '2025-11-18 13:29:44'),
(34, 11, 21, '2025-11-18 15:01:41'),
(47, 6, 21, '2025-11-19 10:32:33'),
(57, 8, 21, '2025-11-19 12:22:55');

-- --------------------------------------------------------

--
-- Table structure for table `post_tags`
--

CREATE TABLE `post_tags` (
  `post_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `post_tags`
--

INSERT INTO `post_tags` (`post_id`, `tag_id`) VALUES
(18577, 1),
(18577, 2),
(18577, 6),
(18577, 7);

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `subject_code` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `description` text DEFAULT NULL,
  `school` varchar(255) DEFAULT NULL,
  `degree_level` varchar(100) DEFAULT NULL,
  `prerequisite` text DEFAULT NULL,
  `syllabus_description` text DEFAULT NULL,
  `student_goals` text DEFAULT NULL,
  `syllabus_topics` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `name`, `subject_code`, `created_at`, `description`, `school`, `degree_level`, `prerequisite`, `syllabus_description`, `student_goals`, `syllabus_topics`) VALUES
(1, 'Web Development', NULL, '2025-10-13 04:45:19', 'Môn học Web Development cung cấp cho sinh viên kiến thức nền tảng và kỹ năng thực tiễn để thiết kế, xây dựng và quản lý các ứng dụng web hiện đại. Sinh viên sẽ được học cách phát triển cả front-end (giao diện người dùng) và back-end (xử lý dữ liệu và logic ứng dụng), đồng thời hiểu cách các thành phần này kết hợp để tạo ra một trang web hoàn chỉnh.\n\nTrong quá trình học, sinh viên sẽ:\n\nNắm vững các ngôn ngữ lập trình cơ bản của web như HTML, CSS, JavaScript và các framework phổ biến.\n\nHiểu và triển khai các khái niệm client-server, API, database trong phát triển web.\n\nBiết cách xây dựng giao diện thân thiện với người dùng, tối ưu hiệu suất và khả năng truy cập.\n\nThực hành phát triển các dự án web thực tế, từ website tĩnh đến ứng dụng web động có kết nối cơ sở dữ liệu.\n\nMôn học không chỉ giúp sinh viên phát triển kỹ năng lập trình mà còn rèn luyện tư duy giải quyết vấn đề, thiết kế trải nghiệm người dùng và chuẩn bị nền tảng cho các lĩnh vực công nghệ web nâng cao như e-commerce, web app, và progressive web app (PWA).\n\nMục tiêu cuối cùng: Sau khi hoàn thành môn học, sinh viên có thể tự tin xây dựng các ứng dụng web hoàn chỉnh, áp dụng kiến thức vào các dự án thực tế và sẵn sàng tham gia thị trường công nghệ thông tin.', NULL, NULL, NULL, NULL, NULL, NULL),
(2, 'Database Systems', NULL, '2025-10-13 04:45:19', 'ewfewfewfew', NULL, NULL, NULL, NULL, NULL, NULL),
(3, 'toan', NULL, '2025-11-15 13:16:31', 'day la 1 mon hoc hay', NULL, NULL, NULL, NULL, NULL, NULL),
(4, 'Test Subject 17:19:00', NULL, '2025-11-18 16:19:00', 'This is a test subject for notifications', NULL, NULL, NULL, NULL, NULL, NULL),
(5, 'ưefwe', NULL, '2025-11-18 16:21:17', 'fewfewfew', NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tags`
--

CREATE TABLE `tags` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tags`
--

INSERT INTO `tags` (`id`, `name`) VALUES
(2, 'css'),
(5, 'ghg'),
(6, 'html'),
(7, 'js'),
(3, 'lml'),
(1, 'php'),
(4, 'valorant');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('student','admin') NOT NULL DEFAULT 'student',
  `avatar` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `avatar`, `created_at`) VALUES
(9, 'Nam1', 'Nam1@gmail.com', '$2y$10$KZrchzCL1zug2Ldh7jNNdOSwayGArWslXN9mhwi8QJHbsf7J/YofC', 'admin', 'avatar_9.png', '2025-10-27 14:07:23'),
(21, 'ALOô', 'kaka2@gmail.com', '$2y$10$Ty/2A84Abp2F.nAUpk5UqODcHbtp.b5jKzs8RzzrNIkLd8HRfF5.K', 'student', 'avatar_21.png', '2025-11-15 19:18:22');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `post_likes`
--
ALTER TABLE `post_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_post_like` (`user_id`,`post_id`),
  ADD KEY `post_id` (`post_id`);

--
-- Indexes for table `post_tags`
--
ALTER TABLE `post_tags`
  ADD PRIMARY KEY (`post_id`,`tag_id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `tag_id` (`tag_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tags`
--
ALTER TABLE `tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `post_likes`
--
ALTER TABLE `post_likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tags`
--
ALTER TABLE `tags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `posts_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `post_likes`
--
ALTER TABLE `post_likes`
  ADD CONSTRAINT `post_likes_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `post_likes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `post_tags`
--
ALTER TABLE `post_tags`
  ADD CONSTRAINT `post_tags_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `post_tags_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
