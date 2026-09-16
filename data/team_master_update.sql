-- 1. Remove existing team data
TRUNCATE TABLE `team_master`;

-- 2. Insert new team data
INSERT INTO `team_master` (`id`, `team_logo`, `team_name`, `owner`, `owner_img`, `ic_player`, `ic_player_img`, `pool`, `total_purse`, `max_strength`) VALUES
(1, '', 'Storm Royals', 'Amit M', NULL, 'Kiran N', NULL, NULL, 10000000, 13),
(2, '', 'Royal Super Giants', 'Asif S', NULL, 'Ranjith Bhaskaran', NULL, NULL, 10000000, 13),
(3, '', 'Royal Rangers', 'Saheb P', NULL, 'Ajay Poojary', NULL, NULL, 10000000, 13),
(4, '', 'Fearless Fighters', 'Sharan M', NULL, 'Vishnumohan K P', NULL, NULL, 10000000, 13),
(5, '', 'Invictus', 'Anup Shetty', NULL, 'Vicky Parmar', NULL, NULL, 10000000, 13),
(6, '', 'Ruthless Run Rioters (RRR)', 'Debopam Choudary', NULL, 'Anand P', NULL, NULL, 10000000, 13),
(7, '', 'Royal Champions Brigade(RCB)', 'Prakash Piyoosh', NULL, 'Adithya Vijendranath', NULL, NULL, 10000000, 13),
(8, '', 'Alpha Legends', 'Kuldeep Singhal', NULL, 'Naveen Rudrachari', NULL, NULL, 10000000, 13),
(9, '', 'Royal Crusaders', 'Balu S', NULL, 'Vinay Nagaraj', NULL, NULL, 10000000, 13),
(10, '', 'Accounting Alphas', 'Jayesh Mehtha', NULL, 'Srikanth Gowda', NULL, NULL, 10000000, 13),
(11, '', 'HOA Hurricanes', 'Magill Kunnath', NULL, 'Krishnan Mudeonor', NULL, NULL, 10000000, 13),
(12, '', 'Apex United', 'Keerthi Shrimali', NULL, 'Clement A', NULL, NULL, 10000000, 13),
(13, '', 'Pretium Gryphons', 'Subaya Aiyappa', NULL, 'Sudhanshu Pal Singh', NULL, NULL, 10000000, 13),
(14, '', 'Garuda Force', 'Vinayak Bhakta', NULL, 'Arul B', NULL, NULL, 10000000, 13);
