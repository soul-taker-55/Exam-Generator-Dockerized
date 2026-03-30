-- phpMyAdmin SQL Dump
-- version 4.7.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 05, 2025 at 10:53 PM
-- Server version: 5.7.17
-- PHP Version: 7.1.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `exam_generator_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `exam`
--

CREATE TABLE `exam` (
  `exam_ID` int(11) NOT NULL,
  `professor_ID` int(11) NOT NULL,
  `university_ID` int(11) NOT NULL,
  `subject_ID` int(11) NOT NULL,
  `creation_date` date NOT NULL,
  `exam_date` date DEFAULT NULL,
  `pdf_file_path` varchar(255) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_question`
--

CREATE TABLE `exam_question` (
  `exam_question_id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `question_order` int(11) NOT NULL,
  `marks` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `professor`
--

CREATE TABLE `professor` (
  `prof_ID` int(11) NOT NULL,
  `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `first_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `last_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `registration_date` date NOT NULL,
  `phone_number` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `verification_token` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT '0',
  `tokens` int(11) DEFAULT '10',
  `two_factor_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `two_factor_code_hash` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `two_factor_expires_at` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `professor`
--

INSERT INTO `professor` (`prof_ID`, `username`, `password`, `first_name`, `last_name`, `email`, `registration_date`, `phone_number`, `verification_token`, `is_verified`, `tokens`, `two_factor_enabled`, `two_factor_code_hash`, `two_factor_expires_at`) VALUES
(1, 'lkjhgf', '$2y$10$MmvuDrHNc.wzrQGWdQo8h.hBPOfdGi7cxC1PXmYqawbjLOoDC4ZJS', 'kjhgf', '', 'mauriciosayegh77@gmail.com', '2025-04-16', 'lkjhgf', NULL, 0, 10, 0, NULL, NULL),
(2, 'Mauricio_7', '$2y$10$OtfpP45LRyv7DGyLidoXOeKGbDdCzO7FMcK1OKgkCf3z3yP.VuVAe', 'Mauricio', 'Sayegh', 'mauriciosayegh7@gmail.com', '2025-04-16', '0937568644', NULL, 1, 20, 0, NULL, NULL),
(3, 'Mauricio_8', '$2y$10$u3KP2tbtHbw53yHHygT.iuq/oPMgMlbkd7Vx2gq1HL62TY/5tHdXO', 'Mauricio', 'Sayegh', 'mauriciosayegh@gmail.com', '2025-04-16', '0937568644', NULL, 0, 10, 0, NULL, NULL),
(4, 'bvcftyuiklm', '$2y$10$WWs5MbFgt2BrIPY6t8y6ZeD.h0cFnoFW0wRPcJIDnQ3WIhrYKVFRG', 'mnbvfrtyujm', '', 'mnbvfgy@gmail.com', '2025-04-16', '0987654321', NULL, 0, 10, 0, NULL, NULL),
(5, 'nbgyuj', '$2y$10$42F9G6x9R2ROup7LS9yHu./e20f25g.hbOq2d2dozJCUgL23HZLgS', 'lkjbgyj', '', 'mnbvftyuj@gmail.com', '2025-04-16', '0987654321', NULL, 0, 10, 0, NULL, NULL),
(6, 'kjhguik', '$2y$10$kbvJYWcMw6Q5X2eW85NLnOSlg4ab7USJ8OWTYxNCLlavjRY7OWduS', ',kjhg', '', 'kjbgfgyj@gmail.com', '2025-04-16', '0987654321', NULL, 0, 10, 0, NULL, NULL),
(7, 'mnbvgj', '$2y$10$d35YsOLa0ZtlTVz4g.UdHeXzdFVBJ6/XgjfRypliv8EMKPTjvSb3G', 'kjhgftyu', '', 'kjbgfgyj@gmail.com', '2025-04-16', '0987654321', NULL, 0, 10, 0, NULL, NULL),
(8, 'mnbvcxzasdfghjk', '$2y$10$wbiuJR2BVDJ3K2FkBMv1sOxT1Oc/ehd2Q5VOvpRDCJWueTGCbxsXG', 'mnbvcxsdrftghj', '', 'kjbgfgyffj@gmail.com', '2025-04-16', '0987654321', NULL, 0, 10, 0, NULL, NULL),
(9, 'lkjhgfdxcvbn', '$2y$10$8tlRPHBUielo3BzBEhmM4usD.umPHqcSQalab35muMa9uUUpMHU1i', 'mnbvcxzasdfghjkl', '', 'dfkjbgfgyj@gmail.com', '2025-04-16', '0987654321', NULL, 0, 10, 0, NULL, NULL),
(11, 'testemailverification', '$2y$10$IDmHYaqz7Ey28UMvEHKVse1ExQIMAGPr1WKcI.el3yuut09iwQAhG', 'testemailverification', '', 'abdallah.sayegh@gmail.com', '2025-04-17', '0987654321', 'df710f952208be346493d638420d544520fe29ff0210e2db043be25e82105416', 0, 10, 0, NULL, NULL),
(18, 'Naya_777', '$2y$10$Kt071C1OA.N5nWh5/2bHgelOMFyjmZzQ6SBLkPH/Wj2DOgkXZqq9e', 'Naya', 'Marjamakk', 'eliaszikra23@gmail.com', '2025-09-05', '0932940127', 'a4b7cf280ee9676449e65b9e3e1477fd00d5b9fa28c20314c897fc31026812cd', 0, 10, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `professor_university`
--

CREATE TABLE `professor_university` (
  `prof_uni_ID` int(11) NOT NULL,
  `professor_ID` int(11) NOT NULL,
  `university_ID` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `professor_university`
--

INSERT INTO `professor_university` (`prof_uni_ID`, `professor_ID`, `university_ID`) VALUES
(1, 9, 6),
(2, 10, 6),
(3, 11, 6),
(4, 2, 1),
(5, 3, 2),
(6, 3, 3),
(11, 12, 8),
(12, 13, 8),
(13, 14, 2),
(14, 15, 2),
(15, 16, 2),
(16, 17, 2),
(17, 18, 2),
(18, 19, 6),
(19, 20, 6);

-- --------------------------------------------------------

--
-- Table structure for table `question`
--

CREATE TABLE `question` (
  `question_ID` int(11) NOT NULL,
  `professor_ID` int(11) NOT NULL,
  `subject_ID` int(11) NOT NULL,
  `question_text` text COLLATE utf8_unicode_ci NOT NULL,
  `img_path` varchar(1024) COLLATE utf8_unicode_ci DEFAULT NULL,
  `difficulty` int(11) NOT NULL,
  `ans_A` text COLLATE utf8_unicode_ci NOT NULL,
  `is_correct_A` tinyint(1) NOT NULL,
  `ans_B` text COLLATE utf8_unicode_ci NOT NULL,
  `is_correct_B` tinyint(1) NOT NULL,
  `ans_C` text COLLATE utf8_unicode_ci NOT NULL,
  `is_correct_C` tinyint(1) NOT NULL,
  `ans_D` text COLLATE utf8_unicode_ci NOT NULL,
  `is_correct_D` tinyint(1) NOT NULL,
  `ans_E` text COLLATE utf8_unicode_ci NOT NULL,
  `is_correct_E` tinyint(1) NOT NULL,
  `group_num` int(11) NOT NULL,
  `is_sub` tinyint(1) NOT NULL,
  `date` date NOT NULL,
  `mark` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `question`
--

INSERT INTO `question` (`question_ID`, `professor_ID`, `subject_ID`, `question_text`, `img_path`, `difficulty`, `ans_A`, `is_correct_A`, `ans_B`, `is_correct_B`, `ans_C`, `is_correct_C`, `ans_D`, `is_correct_D`, `ans_E`, `is_correct_E`, `group_num`, `is_sub`, `date`, `mark`) VALUES
(1, 2, 3, 'Calculate the derivative of \\( f(x) = x^2\\) then find the limit to infinity of the function \\(\\lim_{x \\to \\infty}f(x)\\)', NULL, 2, '14', 0, '15', 0, '16', 1, '17', 0, '17', 0, 1, 0, '2025-04-24', 1),
(2, 2, 3, 'Please calculate \\(\\int f(x)dx\\)', NULL, 1, '13', 0, '14', 1, '15', 0, '16', 0, '', 0, 2, 0, '2025-04-26', 2),
(3, 2, 3, 'Test', NULL, 3, '13', 0, '14', 1, '15', 0, '16', 0, '17', 0, 3, 0, '2025-05-15', 3),
(4, 2, 3, 'Test2', NULL, 3, '1', 1, '2', 0, '3', 0, '4', 0, '5', 0, 3, 0, '2025-05-15', 3),
(5, 2, 3, 'Test3', NULL, 3, '1', 0, '2', 0, '3', 1, '4', 0, '5', 0, 4, 1, '2025-05-15', 1),
(6, 2, 3, 'test4', NULL, 3, '1', 1, '2', 0, '3', 0, '4', 0, '5', 0, 5, 0, '2025-05-15', 1),
(7, 2, 3, 'Hello!', NULL, 1, '1', 1, '2', 0, '3', 0, '4', 0, '', 0, 6, 1, '2025-05-22', 2),
(11, 2, 3, 'Calculate \\(\\sum\\) i', NULL, 1, '\\(n*(n+1)/3\\)', 1, '\\(n*(n-1)/2\\)', 0, '0', 0, '0', 0, '', 0, 10, 0, '2025-08-03', 1),
(12, 2, 3, 'Find the area of a rectangle of side \\(a = 6 \\)', NULL, 1, '34', 0, '36', 1, '40', 0, '31', 0, '', 0, 11, 0, '2025-08-04', 1),
(13, 2, 3, 'For any positive integer \\(x\\) what is the value of \\(\\iiint x^5\\)?', NULL, 1, '30', 1, '40', 0, '50', 0, '60', 0, '', 0, 12, 0, '2025-08-04', 1),
(9, 2, 3, 'Solve for \\(x \\) in the equation \\(2x+6 = 0 \\)', NULL, 1, '-1', 0, '-2', 0, '-3', 1, '3', 0, '', 0, 8, 1, '2025-05-23', 1),
(10, 2, 3, '\\(x=3+6 \\) what is the value of x?', NULL, 1, '7', 0, '9', 1, '8', 0, '10', 0, '0', 0, 9, 0, '2025-05-23', 1),
(14, 2, 3, 'What is the derivative of \\( x^2\\)', NULL, 2, '\\( x\\)', 0, '\\(2*x\\)', 1, '0', 0, '0', 0, '', 0, 13, 0, '2025-08-04', 5);

-- --------------------------------------------------------

--
-- Table structure for table `subject`
--

CREATE TABLE `subject` (
  `subject_ID` int(11) NOT NULL,
  `university_ID` int(11) NOT NULL,
  `professor_ID` int(11) NOT NULL,
  `subject_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `total_mark` int(11) NOT NULL,
  `duration` varchar(25) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `subject`
--

INSERT INTO `subject` (`subject_ID`, `university_ID`, `professor_ID`, `subject_name`, `total_mark`, `duration`) VALUES
(6, 1, 2, 'Programming -2-', 70, '90 Minute'),
(3, 1, 2, 'Algorithms -1-', 70, '90 Minutes'),
(7, 1, 2, 'Algorithms -2-', 70, '60 Minute'),
(8, 1, 2, 'Programming in Logic', 70, '150 Minute'),
(9, 1, 2, 'Cyber Security ', 70, '60 Minute');

-- --------------------------------------------------------

--
-- Table structure for table `token_transactions`
--

CREATE TABLE `token_transactions` (
  `transaction_id` int(11) NOT NULL,
  `professor_id` int(11) NOT NULL,
  `tokens_purchased` int(11) NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `transaction_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('pending','completed','failed') COLLATE utf8_unicode_ci DEFAULT 'pending',
  `payment_reference` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `token_transactions`
--

INSERT INTO `token_transactions` (`transaction_id`, `professor_id`, `tokens_purchased`, `amount_paid`, `payment_method`, `transaction_date`, `status`, `payment_reference`) VALUES
(1, 2, 10, '4.99', NULL, '2025-05-04 13:59:43', 'completed', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `university`
--

CREATE TABLE `university` (
  `university_ID` int(11) NOT NULL,
  `university_name_en` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `university_name_ar` varchar(255) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `university`
--

INSERT INTO `university` (`university_ID`, `university_name_en`, `university_name_ar`) VALUES
(1, 'University of Aleppo', 'جامعة حلب'),
(2, 'Al-Manara University for Medical Sciences', 'جامعة المنارة للعلوم الطبية'),
(3, 'Arab International University (AIU)', 'الجامعة العربية الدولية'),
(4, 'Al-Shahba Private University', 'جامعة الشهباء الخاصة'),
(5, 'Al-Union Private University', 'جامعة الاتحاد الخاصة'),
(6, 'Al-Ala Private University', 'جامعة العلا الخاصة'),
(7, 'International University for Science and Technology (IUST)', 'الجامعة الدولية للعلوم والتكنولوجيا'),
(8, 'Higher Institute for Applied Sciences and Technology (HIAST)', 'المعهد العالي للعلوم التطبيقية والتكنولوجيا'),
(9, 'Cordoba Private University (CPU)', 'جامعة قرطبة الخاصة');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `exam`
--
ALTER TABLE `exam`
  ADD PRIMARY KEY (`exam_ID`),
  ADD KEY `fk_exam_university` (`university_ID`),
  ADD KEY `idx_exam_professor` (`professor_ID`),
  ADD KEY `idx_exam_subject` (`subject_ID`);

--
-- Indexes for table `exam_question`
--
ALTER TABLE `exam_question`
  ADD PRIMARY KEY (`exam_question_id`),
  ADD UNIQUE KEY `exam_id` (`exam_id`,`question_id`),
  ADD KEY `idx_exam_question_exam` (`exam_id`),
  ADD KEY `idx_exam_question_question` (`question_id`);

--
-- Indexes for table `professor`
--
ALTER TABLE `professor`
  ADD PRIMARY KEY (`prof_ID`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `professor_university`
--
ALTER TABLE `professor_university`
  ADD PRIMARY KEY (`prof_uni_ID`),
  ADD KEY `fk_profuni_professor` (`professor_ID`),
  ADD KEY `fk_profuni_university` (`university_ID`);

--
-- Indexes for table `question`
--
ALTER TABLE `question`
  ADD PRIMARY KEY (`question_ID`),
  ADD KEY `fk_question_professor` (`professor_ID`),
  ADD KEY `idx_question_subject` (`subject_ID`);

--
-- Indexes for table `subject`
--
ALTER TABLE `subject`
  ADD PRIMARY KEY (`subject_ID`),
  ADD KEY `fk_subject_professor` (`professor_ID`),
  ADD KEY `fk_subject_university` (`university_ID`);

--
-- Indexes for table `token_transactions`
--
ALTER TABLE `token_transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `professor_id` (`professor_id`);

--
-- Indexes for table `university`
--
ALTER TABLE `university`
  ADD PRIMARY KEY (`university_ID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `exam`
--
ALTER TABLE `exam`
  MODIFY `exam_ID` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `exam_question`
--
ALTER TABLE `exam_question`
  MODIFY `exam_question_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `professor`
--
ALTER TABLE `professor`
  MODIFY `prof_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;
--
-- AUTO_INCREMENT for table `professor_university`
--
ALTER TABLE `professor_university`
  MODIFY `prof_uni_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;
--
-- AUTO_INCREMENT for table `question`
--
ALTER TABLE `question`
  MODIFY `question_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;
--
-- AUTO_INCREMENT for table `subject`
--
ALTER TABLE `subject`
  MODIFY `subject_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
--
-- AUTO_INCREMENT for table `token_transactions`
--
ALTER TABLE `token_transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `university`
--
ALTER TABLE `university`
  MODIFY `university_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
