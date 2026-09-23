-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 23, 2026 at 12:43 PM
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
-- Database: `lms_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `activitylog`
--

CREATE TABLE `activitylog` (
  `LogID` int(11) NOT NULL,
  `UserID` int(11) DEFAULT NULL,
  `Action` text DEFAULT NULL,
  `LogDate` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activitylog`
--

INSERT INTO `activitylog` (`LogID`, `UserID`, `Action`, `LogDate`) VALUES
(1, 1, 'Created announcement: Welcome to Data Structures and Algorithms', '2026-07-22 18:40:34'),
(2, 1, 'Deleted announcement: Welcome to Data Structures and Algorithms', '2026-07-22 18:44:52'),
(3, 1, 'Created announcement: \'Welcome to Data Structures and Algorithms', '2026-07-22 18:45:34'),
(4, 1, 'Created announcement: zcea', '2026-07-22 18:52:43'),
(5, 1, 'Created announcement: zcea', '2026-07-22 18:53:55'),
(6, 1, 'Deleted announcement: zcea', '2026-07-22 18:56:14'),
(7, 1, 'Deleted announcement: zcea', '2026-07-22 18:56:40'),
(8, 1, 'Created announcement: drfrer', '2026-07-22 18:57:13'),
(9, 29, 'Created announcement: wdwfe', '2026-07-22 18:57:50'),
(10, 29, 'Deleted announcement: wdwfe', '2026-07-22 19:02:10'),
(11, 29, 'Deleted announcement: drfrer', '2026-07-22 19:02:13'),
(12, 29, 'Deleted announcement: \'Welcome to Data Structures and Algorithms', '2026-07-22 19:02:16'),
(13, 1, 'Deleted announcement: exam will be start ', '2026-08-02 15:59:36'),
(14, 1, 'Created announcement: Mid-Term Exam Schedule', '2026-08-09 04:05:59');

-- --------------------------------------------------------

--
-- Table structure for table `announcement`
--

CREATE TABLE `announcement` (
  `AnnouncementID` int(11) NOT NULL,
  `Title` varchar(255) DEFAULT NULL,
  `Message` text DEFAULT NULL,
  `CreatedDate` timestamp NOT NULL DEFAULT current_timestamp(),
  `CourseID` int(11) DEFAULT NULL,
  `UserID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcement`
--

INSERT INTO `announcement` (`AnnouncementID`, `Title`, `Message`, `CreatedDate`, `CourseID`, `UserID`) VALUES
(1, 'Exam ', '20.05.2026 Start', '2026-05-02 13:57:32', 1, 4),
(9, 'Welcome to Data Structures and Algorithms', 'Welcome to the class! Please ensure you review the syllabus and install the required development environment before our first lab session', '2026-07-22 19:04:49', 4, 9),
(10, 'Mid-Term Exam Schedule', 'The upcoming mid-term examination for Principles of Management will cover chapters 1 through 5. Please review the syllabus.', '2026-08-09 04:05:59', NULL, 1),
(11, 'Assignment 1 Submission Deadline', 'Kindly submit your Financial Accounting practical assignments via the portal before Friday midnight. No late submissions accepted.', '2026-08-09 04:12:37', 1, 4);

-- --------------------------------------------------------

--
-- Table structure for table `assignment`
--

CREATE TABLE `assignment` (
  `AssignmentID` int(11) NOT NULL,
  `Title` varchar(200) DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `Deadline` datetime DEFAULT NULL,
  `CourseID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assignment`
--

INSERT INTO `assignment` (`AssignmentID`, `Title`, `Description`, `Deadline`, `CourseID`) VALUES
(3, 'HTML Assignment', 'Create webpage', '2026-05-30 12:00:00', 1),
(4, 'css', 'this week assiment ', '2026-05-05 00:00:00', 1),
(5, 'avscchjv chat ', 'pfd file ', '2026-07-22 00:00:00', 1),
(6, '`fzEW', 'ewg4wg', '2026-07-22 22:10:00', 1),
(7, 'Data Structures and Algorithms - Semester 1 (Assignments 1 to 10)', 'Please provide the instructions, description, or topic you would like me to work on, and I will gladly generate the complete response or PDF material for you!', '2026-07-29 00:00:00', 4),
(8, 'Assignment 2', 'i will prov Assingment quc', '2026-08-10 00:00:00', 1);

-- --------------------------------------------------------

--
-- Table structure for table `assignmentsubmission`
--

CREATE TABLE `assignmentsubmission` (
  `SubmissionID` int(11) NOT NULL,
  `AssignmentID` int(11) DEFAULT NULL,
  `StudentID` int(11) DEFAULT NULL,
  `SubmissionFile` varchar(255) DEFAULT NULL,
  `SubmissionDate` timestamp NOT NULL DEFAULT current_timestamp(),
  `Marks` int(11) DEFAULT NULL,
  `Feedback` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assignmentsubmission`
--

INSERT INTO `assignmentsubmission` (`SubmissionID`, `AssignmentID`, `StudentID`, `SubmissionFile`, `SubmissionDate`, `Marks`, `Feedback`) VALUES
(1, 3, 1, '1777776936_html.pdf', '2026-05-03 02:55:36', 90, 'good better try'),
(2, 5, 3, '1784648956_E2240361- Final Report 1.pdf', '2026-07-21 15:49:16', 98, 'better'),
(3, 6, 3, '1784652058_E2240361-_Final_Report_1.pdf', '2026-07-21 16:40:58', NULL, NULL),
(4, 8, 3, '1786248878_DSA_Semester_1_Assignments.pdf', '2026-08-09 04:14:38', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `AttendanceID` int(11) NOT NULL,
  `StudentID` int(11) DEFAULT NULL,
  `CourseID` int(11) DEFAULT NULL,
  `AttendanceDate` date DEFAULT NULL,
  `Status` enum('Present','Absent') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`AttendanceID`, `StudentID`, `CourseID`, `AttendanceDate`, `Status`) VALUES
(1, 1, 1, '2026-05-02', 'Present'),
(2, 4, 3, '2026-07-20', 'Present'),
(3, 7, 4, '2026-07-22', 'Present'),
(4, 5, 4, '2026-07-22', 'Present'),
(5, 7, 4, '2026-07-23', 'Present'),
(6, 5, 4, '2026-07-23', 'Present'),
(7, 3, 1, '2026-08-09', 'Present');

-- --------------------------------------------------------

--
-- Table structure for table `course`
--

CREATE TABLE `course` (
  `CourseID` int(11) NOT NULL,
  `CourseTitle` varchar(150) NOT NULL,
  `CourseDescription` text DEFAULT NULL,
  `SubjectID` int(11) DEFAULT NULL,
  `TeacherID` int(11) DEFAULT NULL,
  `Semester` varchar(20) DEFAULT NULL,
  `CreatedDate` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `course`
--

INSERT INTO `course` (`CourseID`, `CourseTitle`, `CourseDescription`, `SubjectID`, `TeacherID`, `Semester`, `CreatedDate`) VALUES
(1, 'Web Dev Basics', 'HTML CSS JS', 1, 1, 'Semester 1', '2026-05-02 05:56:20'),
(3, 'AVD CSS', 'ADvance', 3, 3, 'semester 1', '2026-07-19 14:52:51'),
(4, 'Data Structures and Algorithms - Semester 1', 'Comprehensive study of advanced data structures and algorithm efficiency.', 5, 4, 'Semester 1', '2026-07-22 18:16:07'),
(5, 'Calculus II - Semester 1', 'Advanced integration techniques, differential equations, and infinite series', 6, 7, 'Semester 1', '2026-07-22 18:23:53'),
(6, 'Technical Communication - Semester 2', 'Professional writing and communication strategies for technical fields', 8, 5, 'Semester 2', '2026-07-22 18:28:05'),
(7, 'Principles of Management Basics', 'Comprehensive overview of organizational management, leadership styles, and corporate planning.', 9, 4, 'semester 1', '2026-08-09 04:04:43');

-- --------------------------------------------------------

--
-- Table structure for table `department`
--

CREATE TABLE `department` (
  `DepartmentID` int(11) NOT NULL,
  `DepartmentName` varchar(100) NOT NULL,
  `Description` text DEFAULT NULL,
  `CreatedDate` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `department`
--

INSERT INTO `department` (`DepartmentID`, `DepartmentName`, `Description`, `CreatedDate`) VALUES
(1, 'IT', 'Information Technology', '2026-05-02 05:51:05'),
(3, 'Computer Science', 'Department of Computer Science and Software Engineering', '2025-12-31 18:30:00'),
(4, 'Mathematics', 'Department of Mathematics and Statistics', '2025-12-31 18:30:00'),
(5, 'Physics', 'Department of Physical Sciences and Physics', '2025-12-31 18:30:00'),
(6, 'Languages', 'Department of English', '2025-12-31 18:30:00'),
(7, 'Business Administration', 'Covers management, finance, marketing, and corporate strategy.', '2026-08-09 04:01:24');

-- --------------------------------------------------------

--
-- Table structure for table `enrollment`
--

CREATE TABLE `enrollment` (
  `EnrollmentID` int(11) NOT NULL,
  `StudentID` int(11) DEFAULT NULL,
  `CourseID` int(11) DEFAULT NULL,
  `EnrollmentDate` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enrollment`
--

INSERT INTO `enrollment` (`EnrollmentID`, `StudentID`, `CourseID`, `EnrollmentDate`) VALUES
(3, 3, 1, '2026-06-23'),
(5, 5, 4, '2026-07-22'),
(6, 7, 4, '2026-07-23'),
(8, 8, 7, '2026-08-09'),
(9, 7, 7, '2026-08-09');

-- --------------------------------------------------------

--
-- Table structure for table `message`
--

CREATE TABLE `message` (
  `MessageID` int(11) NOT NULL,
  `SenderID` int(11) DEFAULT NULL,
  `ReceiverID` int(11) DEFAULT NULL,
  `MessageText` text DEFAULT NULL,
  `SentDate` timestamp NOT NULL DEFAULT current_timestamp(),
  `ReadStatus` enum('Unread','Read') DEFAULT 'Unread'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `message`
--

INSERT INTO `message` (`MessageID`, `SenderID`, `ReceiverID`, `MessageText`, `SentDate`, `ReadStatus`) VALUES
(1, 4, 2, 'good morning', '2026-05-02 12:39:03', 'Read'),
(2, 4, 2, 'good', '2026-05-02 19:24:33', 'Read'),
(3, 4, 2, 'ready to exam ', '2026-05-03 10:00:59', 'Read'),
(4, 6, 7, 'well come ', '2026-06-23 18:15:18', 'Read'),
(5, 4, 7, 'efrew', '2026-07-21 15:02:29', 'Read'),
(6, 7, 4, 'hi', '2026-07-21 15:09:02', 'Read'),
(7, 4, 7, 'hi', '2026-07-21 15:11:55', 'Read'),
(8, 4, 7, 'hi', '2026-07-21 15:12:58', 'Read'),
(9, 7, 4, 'jksgicd', '2026-07-21 15:15:31', 'Read'),
(10, 4, 7, 'rgersg', '2026-07-21 15:25:10', 'Read'),
(11, 9, 29, 'hi', '2026-07-26 18:41:46', 'Read'),
(12, 9, 29, '14', '2026-08-02 16:01:29', 'Read'),
(13, 29, 9, 'ok', '2026-08-02 16:04:47', 'Unread'),
(14, 4, 7, 'hi', '2026-08-09 04:12:52', 'Read'),
(15, 7, 4, 'hi', '2026-08-09 04:13:58', 'Unread');

-- --------------------------------------------------------

--
-- Table structure for table `quiz`
--

CREATE TABLE `quiz` (
  `QuizID` int(11) NOT NULL,
  `QuizTitle` varchar(200) DEFAULT NULL,
  `TimeLimit` int(11) DEFAULT NULL,
  `TotalMarks` int(11) DEFAULT NULL,
  `CourseID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quiz`
--

INSERT INTO `quiz` (`QuizID`, `QuizTitle`, `TimeLimit`, `TotalMarks`, `CourseID`) VALUES
(1, 'HTML Quiz', 60, NULL, 1),
(3, 'specifically lessons 1 through 10', 60, NULL, 4),
(4, 'HTML 2', 20, NULL, 1),
(5, 'qu 3', 20, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `quizquestion`
--

CREATE TABLE `quizquestion` (
  `QuestionID` int(11) NOT NULL,
  `QuizID` int(11) DEFAULT NULL,
  `Question` text DEFAULT NULL,
  `OptionA` text DEFAULT NULL,
  `OptionB` text DEFAULT NULL,
  `OptionC` text DEFAULT NULL,
  `OptionD` text DEFAULT NULL,
  `CorrectAnswer` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quizquestion`
--

INSERT INTO `quizquestion` (`QuestionID`, `QuizID`, `Question`, `OptionA`, `OptionB`, `OptionC`, `OptionD`, `CorrectAnswer`) VALUES
(1, 1, 'What is HTML?', 'Language', 'Protocol', 'Tool', 'Software', 'A'),
(2, 1, 'HTML stands for?', 'HyperText', 'HighText', 'HomeText', 'HyperTool', 'A'),
(4, 3, 'What is the tightest upper bound time complexity for the following code snippet?\r\n\r\n\r\nfor (int i = 1; i <= n; i *= 2) {\r\n    for (int j = 1; j <= n; j++) {\r\n        // constant time operation\r\n    }\r\n}', 'O(n 2)', 'O(nlogn)', 'O(logn)', 'O(n)', 'B'),
(5, 3, 'In a Row-Major 2D array representation of $A[M][N]$, what is the formula to find the memory address of element $A[i][j]$ given base address $B$ and element size $W$?', 'B+(j×M+i)×W', 'B+(i+j)×W', 'B+(i×j)×W', 'B+(i×N+j)×W', 'D'),
(6, 3, 'Which sorting algorithm performs best when the input array is already almost sorted, achieving a time complexity of O(n)?', 'Quick Sort', 'Merge Sort', 'Insertion Sort', 'Selection Sort', 'C'),
(7, 4, 'Which of the following best defines the primary functions of management?', 'Planning, Organizing, Staffing, Budgeting, Reporting', 'Planning, Organizing, Leading, Controlling', 'Directing, Producing, Marketing, Accounting', 'Forecasting, Scheduling, Inspecting, Evaluating', 'B'),
(8, 4, 'Management is considered a \"science\" because:', ') It relies entirely on personal intuition and charisma.', 'It involves creative problem-solving and improvisation.', 'It is based on systematically organized principles, theories, and empirical knowledge.', ') It is an exact physical science like physics or chemistry.', 'C'),
(9, 5, 'Which level of management is primarily responsible for establishing overall organizational goals, long-term strategies, and company-wide policies?', 'Operational (Lower-level) Management', 'Middle Management', 'Top-level Management', 'Supervisory Management', 'C');

-- --------------------------------------------------------

--
-- Table structure for table `quizsubmission`
--

CREATE TABLE `quizsubmission` (
  `SubmissionID` int(11) NOT NULL,
  `QuizID` int(11) DEFAULT NULL,
  `StudentID` int(11) DEFAULT NULL,
  `Score` int(11) DEFAULT NULL,
  `SubmittedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quizsubmission`
--

INSERT INTO `quizsubmission` (`SubmissionID`, `QuizID`, `StudentID`, `Score`, `SubmittedAt`) VALUES
(1, 1, 1, 1, '2026-05-03 05:26:44'),
(2, 4, 3, 2, '2026-08-09 04:15:09'),
(3, 5, 3, 0, '2026-08-09 04:27:59');

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `StudentID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `RegistrationNumber` varchar(50) NOT NULL,
  `DepartmentID` int(11) DEFAULT NULL,
  `EnrollmentYear` year(4) DEFAULT NULL,
  `Status` enum('Active','Inactive') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`StudentID`, `UserID`, `RegistrationNumber`, `DepartmentID`, `EnrollmentYear`, `Status`) VALUES
(3, 7, 'STU003', 1, '2026', 'Active'),
(4, 2, 'STU004', 1, '2026', 'Active'),
(5, 29, 'STU005', 3, '2026', 'Active'),
(6, 26, 'STU006', 3, '2026', 'Active'),
(7, 22, 'STU007', 3, '2026', 'Active'),
(8, 31, 'STU009', 7, '2026', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `studymaterial`
--

CREATE TABLE `studymaterial` (
  `MaterialID` int(11) NOT NULL,
  `Title` varchar(200) DEFAULT NULL,
  `FilePath` varchar(255) DEFAULT NULL,
  `FileType` varchar(50) DEFAULT NULL,
  `CourseID` int(11) DEFAULT NULL,
  `UploadDate` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `studymaterial`
--

INSERT INTO `studymaterial` (`MaterialID`, `Title`, `FilePath`, `FileType`, `CourseID`, `UploadDate`) VALUES
(2, 'HTML', '1777730174_69f6027e5c67b_html.pdf', 'pdf', 1, '2026-05-02 13:56:14'),
(3, 'html', '1777802223_69f71befdd70c_CSS_Guidelines.pdf', 'pdf', 1, '2026-05-03 09:57:03'),
(4, 'Introduction to DSA and Algorithm Analysis', '1784748797_6a611afd9d080_Week_1_Introduction_to_DSA_and_Algorithm_Analysis.pdf', 'pdf', 4, '2026-07-22 19:33:17'),
(5, 'Data Structures and Algorithms - Semester 1 (Assignments 1 to 10)', '1785087916_6a6647ac31580_DSA_Semester_1_Assignments.pdf', 'pdf', 4, '2026-07-26 17:45:16'),
(6, 'week 4', '1786248481_6a77fd2127316_Week_1_Introduction_to_DSA_and_Algorithm_Analysis.pdf', 'pdf', 1, '2026-08-09 04:08:01');

-- --------------------------------------------------------

--
-- Table structure for table `subject`
--

CREATE TABLE `subject` (
  `SubjectID` int(11) NOT NULL,
  `SubjectName` varchar(100) NOT NULL,
  `SubjectCode` varchar(50) NOT NULL,
  `DepartmentID` int(11) DEFAULT NULL,
  `CreatedDate` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subject`
--

INSERT INTO `subject` (`SubjectID`, `SubjectName`, `SubjectCode`, `DepartmentID`, `CreatedDate`) VALUES
(1, 'Web Development', 'WD101', 1, '2026-05-02 05:52:04'),
(2, 'Data Base', 'WD102', 1, '2026-05-03 09:51:44'),
(3, 'css', 'css0001', 1, '2026-05-03 10:06:42'),
(4, 'IT Project Management', 'IT3020', 1, '2026-07-22 17:00:55'),
(5, 'Data Structures and Algorithms', 'CS1010', 3, '2026-07-22 17:05:13'),
(6, 'Calculus II', 'MTH2010', 4, '2026-07-22 17:09:12'),
(7, 'Electromagnetism', 'PHY3020', 5, '2026-07-22 17:10:10'),
(8, 'Technical Communication', 'LAN4020', 6, '2026-07-22 17:10:48'),
(9, 'Principles of Management', 'BUS101', 7, '2026-08-09 04:03:40');

-- --------------------------------------------------------

--
-- Table structure for table `teacher`
--

CREATE TABLE `teacher` (
  `TeacherID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `Qualification` varchar(100) DEFAULT NULL,
  `ExperienceYears` int(11) DEFAULT NULL,
  `DepartmentID` int(11) DEFAULT NULL,
  `JoinedDate` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher`
--

INSERT INTO `teacher` (`TeacherID`, `UserID`, `Qualification`, `ExperienceYears`, `DepartmentID`, `JoinedDate`) VALUES
(1, 4, 'MSc IT', 5, 1, '2025-01-01'),
(3, 6, 'ABC', 5, 1, '2026-06-22'),
(4, 9, 'Ph.D. in Computer Science', 5, 3, '2026-06-01'),
(5, 13, 'M.A. in English Literature', 4, 6, '2026-07-01'),
(6, 11, 'M.Sc. in Physics', 8, 5, '2026-07-01'),
(7, 10, 'Ph.D. in Mathematics', 6, 4, '2026-07-01'),
(8, 30, 'MAC Managment', 5, 7, '2026-08-09');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `role` enum('admin','teacher','student') NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `full_name`, `email`, `phone`, `role`, `status`, `created_at`) VALUES
(1, 'ahamed', '$2y$10$3kVRgfoX4Oq.yDzDMoVGwOfI9AHKiP/UrNC21c3Ib.FRW3y5pV3n2', 'Ahamed Hather', 'hatherahamed2001@gmail.com', '0750173057', 'admin', 'active', '2026-04-30 14:50:17'),
(2, 'thaj', '$2y$10$MdQvLFnisR4aXISjvKN98eHcRu35k3iKTDVNKTzdX70cVsyAlDeS6', 'thaj', 'thajmohamed@gmail.com', '0771234563', 'student', 'active', '2026-05-01 05:53:20'),
(3, 'kumar', '$2y$10$mIw9RmBa8PpHYw2FGYia3eMVglmVcH4HDYiu6jEjxdUU0eIkhPDiq', 'kumar', 'kumar2001@gmail.com', '0751234567', 'teacher', 'active', '2026-05-02 05:25:16'),
(4, 'danus', '$2y$10$K314vE5LqImU1aVZ4OvXtuW5/WrpyhOPau6jypKap2XJVXM2M.gZW', 'Danus', 'danus@gmail.com', '0771234561', 'teacher', 'active', '2026-05-02 05:49:55'),
(5, 'rajkumar', '$2y$10$uszr6ezncIFni5J9fOAPgOeJ8AvYZ7WijZ9CcYpmsrIXc8hG1Hpyi', 'raj', 'rajkumar@gmail.com', '0771234567', 'student', 'active', '2026-05-03 09:47:50'),
(6, 'kamal', '$2y$10$fzTG1C8x0h.w9zpPRljXBuYfOQ8cvANUVvQFP.9wHMaPZSU84RJ/K', 'kumar thaj', 'kamal@gmail.com', '0750120450', 'teacher', 'active', '2026-06-23 18:08:00'),
(7, 'zayan', '$2y$10$cqf1Xl2x4RW.g.ErfWDwUOZrmfF41qap4WOeV8r/d7W8m0ANS8vu.', 'zayan', 'zayan@gmail.com', '0750123045', 'student', 'active', '2026-06-23 18:10:19'),
(8, 'luna', '$2y$10$WTHE6Y32wFzBdSerdXYhRevWxM.eU45QZpkOHen7mf3Madwyb3Hae', 'luna', 'hatherahamed201@gmail.com', '0750123041', 'admin', 'active', '2026-07-16 07:26:37'),
(9, 'jdoe', '$2y$10$UyJOs1pUU51E7M2D16XGjePjGbBLTwefHznGIh.u9e6el41fyr7fC', 'Dr. John Doe', 'john.doe@lms.gmail.com', '+94712345671', 'teacher', 'active', '2026-07-21 17:31:06'),
(10, 'asmith', '$2y$10$5yKEpdaV6qE0P7L2l2rUp.djQUxSrX2oYrpuOhMCt3IxaY0ht5VJe', 'Prof. Alice Smith', 'alice.smith@lms.gmail.com', '+94712345672', 'teacher', 'active', '2026-07-21 17:31:06'),
(11, 'bjohnson', '$2y$10$WsBfLFr8DOrtwk4QH81JJOrizHsppzQ7RqVnpFPr3L1sjFYitPJAK', 'Dr. Robert Johnson', 'robert.johnson@lms.gmail.com', '+94712345673', 'teacher', 'active', '2026-07-21 17:31:06'),
(12, 'ewilliams', '$2y$10$UeIGvIjwLiTaOPvDgdr1WeSY2W1Whd/DQdxLwl59Gs9Q74KISQrBq', 'Ms. Emily Williams', 'emily.williams@lms.gmail.com', '+94712345674', 'teacher', 'active', '2026-07-21 17:31:06'),
(13, 'mdata', '$2y$10$eAY2jWF2YxKAQ/pWs/PTPOEtZ5Neina8OyBjwsmr2Fh7mlZnPboQu', 'Mr. Michael Brown', 'michael.brown@lms.gmail.com', '+94712345675', 'teacher', 'active', '2026-07-21 17:31:06'),
(14, 'jdavis', '$2y$10$M0nRiWHNVAONezbadeLolOFeYEtv2f2.z2BpLceSlBP0oYp9ww7xa', 'Dr. Jessica Davis', 'jessica.davis@lms.gmail.com', '+94712345676', 'teacher', 'active', '2026-07-21 17:31:06'),
(15, 'cmiller', '$2y$10$ExampleHashStringForPassword7', 'MM. Charles Miller', 'charles.miller@lms.gmail.com', '+94712345677', 'teacher', 'active', '2026-07-21 17:31:06'),
(16, 'swilson', '$2y$10$ExampleHashStringForPassword8', 'Dr. Sarah Wilson', 'sarah.wilson@lms.gmail.com', '+94712345678', 'teacher', 'active', '2026-07-21 17:31:06'),
(17, 'moore', '$2y$10$8.N.TTOTC1ZAdK3rluEGvOrG4BunqH7W1049WJs5cVJDaHjkGyKz2', 'Mr. David Moore', 'david.moore@lms.gmail.com', '+94712345679', 'teacher', 'active', '2026-07-21 17:31:06'),
(18, 'rtaylor', '$2y$10$uG6/PHlpwR7SEkdjvOmXjOSGplmD7mHi1E8H3u1ndvfo4RmD1BLUu', 'Ms. Rachel Taylor', 'rachel.taylor@lms.gmail.com', '+94712345680', 'teacher', 'active', '2026-07-21 17:31:06'),
(19, 'sperera', '$2y$10$ExampleHashStringForPassword11', 'Sunil Perera', 'sunil.perera@lms.gmail.com', '+94723456781', 'student', 'active', '2026-07-21 17:32:45'),
(20, 'ffernando', '$2y$10$ExampleHashStringForPassword12', 'Fathima Fernando', 'fathima.fernando@lms.gmail.com', '+94723456782', 'student', 'active', '2026-07-21 17:32:45'),
(21, 'dkumara', '$2y$10$ExampleHashStringForPassword13', 'Dinesh Kumara', 'dinesh.kumara@lms.gmail.com', '+94723456783', 'student', 'active', '2026-07-21 17:32:45'),
(22, 'asilva', '$2y$10$Cj5L0beJiR0IdmcMiRpszObJfMXPr5GGXh5FN5rJNuIs4xr0CFNjC', 'Amara Silva', 'amara.silva@lms.gmail.com', '+94723456784', 'student', 'active', '2026-07-21 17:32:45'),
(23, 'njayawardena', '$2y$10$lzZEYQv6z.f1WLi0iYkZh.D2rstUMvYvI9K5sGYio7nuAvGIx9p5m', 'Nimal Jayawardena', 'nimal.jayawardena@lms.gmail.com', '+94723456785', 'student', 'active', '2026-07-21 17:32:45'),
(24, 'cgunaratne', '$2y$10$rDuXeUO/5CMK1dyxoYY2Vexm2Or8u4S6hhNBz9MMk6tGmcZYiel0e', 'Chathuri Gunaratne', 'chathuri.gunaratne@lms.gmail.com', '+94723456786', 'student', 'active', '2026-07-21 17:32:45'),
(25, 'rbandara', '$2y$10$UuuIM5nB37NRbRRq6TXqw.yVsjILlrnDw7E4Yc.mefo9/v8qcjKa6', 'Nuwan Bandara', 'nuwan.bandara@lms.gmail.com', '+94723456787', 'student', 'active', '2026-07-21 17:32:45'),
(26, 'prajapaksha', '$2y$10$O0pY3xUmycmhl7GabtFrc.0Dlu3i09q6/QklPgUw/Q3p17zX88ejm', 'Priya Rajapaksha', 'priya.rajapaksha@lms.gmail.com', '+94723456788', 'student', 'active', '2026-07-21 17:32:45'),
(27, 'akarunaratne', '$2y$10$6iS9l/r9Wy9FGGfdw8YGm.oY2YAFsNlRlFsYWrGMheieRkRnMu1Gi', 'Asanka Karunaratne', 'asanka.karunaratne@lms.gmail.com', '+94723456789', 'student', 'active', '2026-07-21 17:32:45'),
(28, 'mwijesinghe', '$2y$10$pxsNx.JiWT1e7s6Dh2LFe.hjq8bObiSq4pfStbwVz5HX/C2K/HKdK', 'Malini Wijesinghe', 'malini.wijesinghe@lms.gmail.com', '+94723456790', 'student', 'active', '2026-07-21 17:32:45'),
(29, 'riyas', '$2y$10$CAhSR52Ht9X.VLB521vb8ukFHLuCr4NBQzKjI67RyElApaonHOatW', 'MM.Riyas', 'Riyas@lms.gmail.com', '+94723456781', 'student', 'active', '2026-07-21 17:34:35'),
(30, 'TR_Ifrath', '$2y$10$ix9Y2xRRw5.DwTphuE/8C.n4uyYJYaR8AgY2IubiFqRRCMBZcBlhC', 'John Doe', 'john.teacher@lms.com', '0712345678', 'teacher', 'active', '2026-08-09 04:00:25'),
(31, 'ST_Afrath', '$2y$10$Gr6aSHp6Z4.3MI8AMmofyeaJKHOR4LVGmuc0duqL4Z834dxV24br6', 'Alex Johnson', 'alex.student@lms.com', '0759876543', 'student', 'active', '2026-08-09 04:01:03');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activitylog`
--
ALTER TABLE `activitylog`
  ADD PRIMARY KEY (`LogID`);

--
-- Indexes for table `announcement`
--
ALTER TABLE `announcement`
  ADD PRIMARY KEY (`AnnouncementID`);

--
-- Indexes for table `assignment`
--
ALTER TABLE `assignment`
  ADD PRIMARY KEY (`AssignmentID`);

--
-- Indexes for table `assignmentsubmission`
--
ALTER TABLE `assignmentsubmission`
  ADD PRIMARY KEY (`SubmissionID`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`AttendanceID`);

--
-- Indexes for table `course`
--
ALTER TABLE `course`
  ADD PRIMARY KEY (`CourseID`),
  ADD KEY `SubjectID` (`SubjectID`),
  ADD KEY `TeacherID` (`TeacherID`);

--
-- Indexes for table `department`
--
ALTER TABLE `department`
  ADD PRIMARY KEY (`DepartmentID`);

--
-- Indexes for table `enrollment`
--
ALTER TABLE `enrollment`
  ADD PRIMARY KEY (`EnrollmentID`);

--
-- Indexes for table `message`
--
ALTER TABLE `message`
  ADD PRIMARY KEY (`MessageID`);

--
-- Indexes for table `quiz`
--
ALTER TABLE `quiz`
  ADD PRIMARY KEY (`QuizID`);

--
-- Indexes for table `quizquestion`
--
ALTER TABLE `quizquestion`
  ADD PRIMARY KEY (`QuestionID`);

--
-- Indexes for table `quizsubmission`
--
ALTER TABLE `quizsubmission`
  ADD PRIMARY KEY (`SubmissionID`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`StudentID`),
  ADD UNIQUE KEY `RegistrationNumber` (`RegistrationNumber`),
  ADD KEY `UserID` (`UserID`),
  ADD KEY `DepartmentID` (`DepartmentID`);

--
-- Indexes for table `studymaterial`
--
ALTER TABLE `studymaterial`
  ADD PRIMARY KEY (`MaterialID`);

--
-- Indexes for table `subject`
--
ALTER TABLE `subject`
  ADD PRIMARY KEY (`SubjectID`),
  ADD UNIQUE KEY `SubjectCode` (`SubjectCode`),
  ADD KEY `DepartmentID` (`DepartmentID`);

--
-- Indexes for table `teacher`
--
ALTER TABLE `teacher`
  ADD PRIMARY KEY (`TeacherID`),
  ADD KEY `UserID` (`UserID`),
  ADD KEY `DepartmentID` (`DepartmentID`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `username_2` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `email_2` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activitylog`
--
ALTER TABLE `activitylog`
  MODIFY `LogID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `announcement`
--
ALTER TABLE `announcement`
  MODIFY `AnnouncementID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `assignment`
--
ALTER TABLE `assignment`
  MODIFY `AssignmentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `assignmentsubmission`
--
ALTER TABLE `assignmentsubmission`
  MODIFY `SubmissionID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `AttendanceID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `course`
--
ALTER TABLE `course`
  MODIFY `CourseID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `department`
--
ALTER TABLE `department`
  MODIFY `DepartmentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `enrollment`
--
ALTER TABLE `enrollment`
  MODIFY `EnrollmentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `message`
--
ALTER TABLE `message`
  MODIFY `MessageID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `quiz`
--
ALTER TABLE `quiz`
  MODIFY `QuizID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `quizquestion`
--
ALTER TABLE `quizquestion`
  MODIFY `QuestionID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `quizsubmission`
--
ALTER TABLE `quizsubmission`
  MODIFY `SubmissionID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `student`
--
ALTER TABLE `student`
  MODIFY `StudentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `studymaterial`
--
ALTER TABLE `studymaterial`
  MODIFY `MaterialID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `subject`
--
ALTER TABLE `subject`
  MODIFY `SubjectID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `teacher`
--
ALTER TABLE `teacher`
  MODIFY `TeacherID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `course`
--
ALTER TABLE `course`
  ADD CONSTRAINT `course_ibfk_1` FOREIGN KEY (`SubjectID`) REFERENCES `subject` (`SubjectID`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `course_ibfk_2` FOREIGN KEY (`TeacherID`) REFERENCES `teacher` (`TeacherID`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `student`
--
ALTER TABLE `student`
  ADD CONSTRAINT `student_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `student_ibfk_2` FOREIGN KEY (`DepartmentID`) REFERENCES `department` (`DepartmentID`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `subject`
--
ALTER TABLE `subject`
  ADD CONSTRAINT `subject_ibfk_1` FOREIGN KEY (`DepartmentID`) REFERENCES `department` (`DepartmentID`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `teacher`
--
ALTER TABLE `teacher`
  ADD CONSTRAINT `teacher_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `teacher_ibfk_2` FOREIGN KEY (`DepartmentID`) REFERENCES `department` (`DepartmentID`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
