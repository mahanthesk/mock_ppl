-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 29, 2026 at 01:58 PM
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
-- Database: `parvamco_spl_season2_2026_dashboard`
--

-- --------------------------------------------------------

--
-- Table structure for table `team_master`
--

CREATE TABLE `team_master` (
  `id` int(11) NOT NULL,
  `team_logo` varchar(250) NOT NULL,
  `team_name` varchar(250) NOT NULL,
  `owner` varchar(250) NOT NULL,
  `owner_img` varchar(250) DEFAULT NULL,
  `ic_player` varchar(250) NOT NULL,
  `ic_player_img` varchar(250) DEFAULT NULL,
  `pool` varchar(2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `team_master`
--

INSERT INTO `team_master` (`id`, `team_logo`, `team_name`, `owner`, `owner_img`, `ic_player`, `ic_player_img`, `pool`) VALUES
(1, 'AS Lions_logo.png', 'AS Lions', 'Arjun Somshekar', 'AS Lions_owner.png', 'Anoop Sagar', 'team_assets/icons/Anoop Sagar.png', 'B'),
(2, 'Hoysala Warriors_logo.png', 'Hoysala Warriors', 'Hoysala Kananur', 'Hoysala Warriors_owner.png', 'Vivan Akarsh', 'team_assets/icons/Vivan Akarsh.png', 'A'),
(3, 'JK Panthers_logo.png', 'JK Panthers', 'Siddhu Hugar', 'JK Panthers_owner.png', 'Cockroach Sudhi', 'team_assets/icons/Cockroach Sudhi.png', 'B'),
(4, 'Lion Kings_logo.png', 'Lion Kings', 'Avinash', 'Lion Kings_owner.png', 'Pavan Shetty', 'team_assets/icons/Pavan Shetty.png', 'A'),
(5, 'Master Blasters_logo.png', 'Master Blasters', 'Adv. Murali', 'Master Blasters_owner.png', 'Naga Kiran', 'team_assets/icons/Naga Kiran.png', 'B'),
(6, 'Prince 11_logo.png', 'Prince 11', 'Prince Deepak', 'Prince 11_owner.png', 'Alaknanda Srinivas', 'team_assets/icons/Alaknanda Srinivas.png', 'B'),
(7, 'Radha Rebels_logo.png', 'Radha Rebels', 'Radha Srinivas', 'Radha Rebels_owner.png', 'Vikky Varun', 'team_assets/icons/Vikky Varun.png', 'A'),
(8, 'SMK Mysuru Kings_logo.png', 'SMK Mysuru Kings', 'Mooguru Madhu Dixith', 'SMK Mysuru Kings_owner.png', 'Dharma Keerthiraj', 'team_assets/icons/Dharma Keerthiraj.png', 'A'),
(9, '', 'Team 9 Placeholder', 'Owner 9', '', 'Icon 9', NULL, NULL),
(10, '', 'Team 10 Placeholder', 'Owner 10', '', 'Icon 10', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `team_master`
--
ALTER TABLE `team_master`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `team_master`
--
ALTER TABLE `team_master`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
