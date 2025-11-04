-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 03, 2025 at 06:47 PM
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
-- Database: `kripto`
--

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(100) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `cover_image` varchar(255) DEFAULT 'default_cover.jpg',
  `digital_file_path` varchar(255) DEFAULT NULL,
  `original_filename` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`id`, `title`, `author`, `stock`, `cover_image`, `digital_file_path`, `original_filename`) VALUES
(1, 'gatau', 'dila', 0, '1762162996_Screenshot 2025-10-19 235416.png', NULL, NULL),
(2, 'bbb', 'jaemin', 1, '1762165780_Screenshot 2025-10-27 191328.png', NULL, NULL),
(3, 'pacar jaemin', 'apalah', 997, '1762167147_Screenshot 2025-11-02 201114.png', 'uploads/digital_books/1762167147_Screenshot 2025-10-27 191155.png.enc', 'Screenshot 2025-10-27 191155.png');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `receiver_id` int(11) DEFAULT NULL,
  `thread_id` int(11) DEFAULT NULL,
  `reply_to_id` int(11) DEFAULT NULL,
  `encrypted_text` text DEFAULT NULL,
  `status` enum('Terkirim','Dibaca','Dibalas') DEFAULT 'Terkirim',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `sender_id`, `receiver_id`, `thread_id`, `reply_to_id`, `encrypted_text`, `status`, `created_at`) VALUES
(1, 3, NULL, 1, NULL, 'OXM0L0c3VGg0K1pwMnM0N1ZWeDN0QT09', 'Terkirim', '2025-11-03 10:07:19'),
(2, 3, NULL, 2, NULL, 'MytQV0RaK01sVC9Cb0swQXc3TEZlUT09', 'Terkirim', '2025-11-03 10:10:40'),
(3, 3, 1, 3, NULL, 'L2V5aWlZMENpUytJQllYUXdXRUI4TDRob05aRmQ2VFFkbmxLZzgrZVQ1UT0=', 'Dibalas', '2025-11-03 10:23:32'),
(4, 2, 3, 3, NULL, 'YXg3cEVpYU5lNVl0MEhzYlkzN01IQT09', 'Dibalas', '2025-11-03 10:24:47'),
(5, 3, 1, 5, NULL, 'eWZGNk8wYXR0YzgyeWZyUU9SMG9Jd2I4VERiZXdiNUM4QWlHaGhFZnVzVT0=', 'Dibalas', '2025-11-03 10:30:55'),
(6, 2, 3, 5, NULL, 'ckZWajJMS1NMcVJzR2hkMit2MGgwQT09', 'Dibalas', '2025-11-03 10:32:59');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Pending','Waiting for Confirmation','Completed','Cancelled') DEFAULT 'Pending',
  `proof_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `book_id`, `order_date`, `status`, `proof_path`) VALUES
(1, 3, 1, '2025-11-03 09:57:28', 'Completed', NULL),
(2, 3, 1, '2025-11-03 10:07:52', 'Waiting for Confirmation', 'uploads/stego_img/1762164551_Screenshot 2025-10-16 000109.png'),
(3, 3, 1, '2025-11-03 10:31:27', 'Completed', 'uploads/stego_img/1762166412_Screenshot 2025-10-16 000109.png'),
(4, 3, 2, '2025-11-03 10:31:32', 'Waiting for Confirmation', 'uploads/stego_img/1762165916_Screenshot 2025-10-27 155955.png'),
(5, 3, 3, '2025-11-03 10:53:00', 'Completed', 'uploads/stego_img/1762167236_Screenshot 2025-10-23 144717.png'),
(6, 3, 3, '2025-11-03 11:07:45', 'Completed', 'uploads/stego_img/1762168500_Screenshot 2025-10-21 134907.png'),
(7, 3, 3, '2025-11-03 11:17:36', 'Waiting for Confirmation', 'uploads/stego_img/1762168881_Screenshot 2025-10-21 134907.png');

-- --------------------------------------------------------

--
-- Table structure for table `secure_files`
--

CREATE TABLE `secure_files` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `original_filename` varchar(255) DEFAULT NULL,
  `encrypted_file_path` varchar(255) DEFAULT NULL,
  `upload_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stego_images`
--

CREATE TABLE `stego_images` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `secret_message_path` varchar(255) DEFAULT NULL,
  `upload_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','user') DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `role`) VALUES
(1, 'admin', '$2y$10$iGAYh5hS.A.d.e.eEbT2r.zDk6N9v8/t.v2P9S4.gD9zY/G.I.9uK', 'admin'),
(2, 'dila', '$2y$10$OYpIjTqtgspYJyw.hwuBGuCkjMZ6/XqNj/iPPLh1Aw5YD3MqDXhHC', 'admin'),
(3, 'nia', '$2y$10$p70zJp0KMdmUJGXAuDyMQOvF/n3g6UuNF6iR9lYwXMZ1FkRFXY9aK', 'user');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indexes for table `secure_files`
--
ALTER TABLE `secure_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `stego_images`
--
ALTER TABLE `stego_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `secure_files`
--
ALTER TABLE `secure_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stego_images`
--
ALTER TABLE `stego_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `secure_files`
--
ALTER TABLE `secure_files`
  ADD CONSTRAINT `secure_files_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `stego_images`
--
ALTER TABLE `stego_images`
  ADD CONSTRAINT `stego_images_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
