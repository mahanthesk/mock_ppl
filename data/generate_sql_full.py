import pandas as pd
import numpy as np

file_path = "data/Players Stats.xlsx"
sql_file_path = "data/import_data.sql"
xls = pd.ExcelFile(file_path)

# 1. Schema Definition
schema_sql = """
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `category_name` (`category_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `player_stats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `player_id` varchar(20) NOT NULL,
  `matches_played` int(11) DEFAULT 0,
  `innings` int(11) DEFAULT 0,
  `runs_scored` int(11) DEFAULT 0,
  `highest_score` int(11) DEFAULT 0,
  `batting_avg` decimal(6,2) DEFAULT 0.00,
  `strike_rate` decimal(6,2) DEFAULT 0.00,
  `fifties` int(11) DEFAULT 0,
  `hundreds` int(11) DEFAULT 0,
  `wickets` int(11) DEFAULT 0,
  `bowling_avg` decimal(6,2) DEFAULT 0.00,
  `economy` decimal(6,2) DEFAULT 0.00,
  `best_bowling` varchar(10) DEFAULT NULL,
  `catches` int(11) DEFAULT 0,
  `stumpings` int(11) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_player` (`player_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `registrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `player_id` varchar(20) DEFAULT NULL,
  `salutation` varchar(10) DEFAULT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `address` text DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `prod_media_id` varchar(255) DEFAULT NULL,
  `role` varchar(50) DEFAULT NULL,
  `experience` varchar(100) DEFAULT NULL,
  `batting_type` varchar(50) DEFAULT NULL,
  `bowling_type` varchar(50) DEFAULT NULL,
  `jersey_nickname` varchar(50) DEFAULT NULL,
  `jersey_number` int(11) DEFAULT NULL,
  `jersey_size` varchar(5) DEFAULT NULL,
  `track_pant_size` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `registered_by` varchar(100) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  `team_id` int(11) DEFAULT NULL,
  `bid_points` int(11) DEFAULT NULL,
  `booking_status` int(11) DEFAULT 0,
  `emp_id` varchar(50) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `player_id` (`player_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

TRUNCATE TABLE `categories`;
TRUNCATE TABLE `player_stats`;
TRUNCATE TABLE `registrations`;

"""

sheets_categories = {
    "Top 20 Batters": "Top 20 Batters (Marquee A)",
    "Top 20 Bowlers": "Top 20 Bowlers (Marquee B)",
    "PPL PLayers": "PPL PLayers (Marquee C)",
    "New Players": "New Players (Marquee D)"
}

# Preload reference data
df_mvp = pd.DataFrame()
if "MVP Details" in xls.sheet_names:
    df_mvp = pd.read_excel(xls, sheet_name="MVP Details")
    df_mvp.columns = df_mvp.columns.str.strip()

df_registered = pd.DataFrame()
if "Registered Players" in xls.sheet_names:
    df_registered = pd.read_excel(xls, sheet_name="Registered Players")
    df_registered.columns = df_registered.columns.str.strip()

def escape_sql(val):
    if pd.isna(val) or val is None or str(val).lower() == 'nan':
        return "NULL"
    if isinstance(val, (int, float, np.integer, np.floating)):
        return str(val)
    return "'" + str(val).replace("'", "''") + "'"

sql_statements = [schema_sql]

cat_inserts = []
for k, v in sheets_categories.items():
    cat_inserts.append(f"({escape_sql(v)})")
if cat_inserts:
    sql_statements.append("INSERT INTO `categories` (`category_name`) VALUES\n" + ",\n".join(cat_inserts) + ";\n")

reg_inserts = []
stat_inserts = []

player_counter = 1

for sheet_name, cat_name in sheets_categories.items():
    if sheet_name in xls.sheet_names:
        df_sheet = pd.read_excel(xls, sheet_name=sheet_name)
        df_sheet.columns = df_sheet.columns.str.strip()
        
        for _, row in df_sheet.iterrows():
            emp_id = row.get('Emp ID', None)
            player_name = row.get('Player Name', '')
            if pd.isna(player_name):
                continue
            
            p_id = f"PL{player_counter:04d}"
            player_counter += 1
            
            # Default values
            department = row.get('BU', '')
            mobile = None
            email = None
            role = None
            experience = None
            batting_type = None
            bowling_type = None
            
            stats = None
            if not pd.isna(emp_id):
                # Enrich from MVP Details
                if not df_mvp.empty:
                    matches_mvp = df_mvp[df_mvp['Employee ID'] == emp_id]
                    if not matches_mvp.empty:
                        stats = matches_mvp.iloc[0]
                        
                        department = stats.get('Department', department)
                        role = stats.get('Primary Skill', role)
                        experience = stats.get('Previous PPL Editions', experience)
                        batting_type = stats.get('Batting Hand', batting_type)
                        bowling_type = stats.get('Bowling Style', bowling_type)

                # Enrich from Registered Players
                if not df_registered.empty:
                    matches_reg = df_registered[df_registered['Employee ID'] == emp_id]
                    if not matches_reg.empty:
                        reg_info = matches_reg.iloc[0]
                        mobile = reg_info.get('Phone Number', mobile)
                        email = reg_info.get('Official Email Address', email)
                        # Fallback if MVP doesn't have it
                        if pd.isna(department) or department == '': department = reg_info.get('Department', department)
                        if pd.isna(role): role = reg_info.get('Primary Skill', role)
                        if pd.isna(experience): experience = reg_info.get('Participated in Previous PPL Editions', experience)

            # Registration Insert
            reg_inserts.append(
                f"({escape_sql(p_id)}, {escape_sql(player_name)}, {escape_sql(cat_name)}, "
                f"{escape_sql(emp_id)}, {escape_sql(department)}, "
                f"{escape_sql(mobile)}, {escape_sql(email)}, {escape_sql(role)}, "
                f"{escape_sql(experience)}, {escape_sql(batting_type)}, {escape_sql(bowling_type)})"
            )
            
            # Stat Insert
            if stats is not None:
                m_played = stats.get('Bat Total Match', 0)
                if pd.isna(m_played): m_played = stats.get('Bowl Total Match', 0)
                
                innings = stats.get('Bat Innings', 0)
                runs = stats.get('Total Runs', 0)
                hs = stats.get('Highest Run', 0)
                b_avg = stats.get('Average', 0)
                sr = stats.get('Strike Rate', 0)
                fifties = stats.get('50s', 0)
                hundreds = stats.get('100s', 0)
                wickets = stats.get('Total Wickets', 0)
                bowl_avg = stats.get('Average.1', 0)
                econ = stats.get('Economy', 0)
                best_bowl = stats.get('Highest Wicket', 0)
                if pd.isna(best_bowl):
                    best_bowl_str = "NULL"
                else:
                    best_bowl_str = escape_sql(str(best_bowl))
                
                stat_inserts.append(f"({escape_sql(p_id)}, {escape_sql(m_played)}, {escape_sql(innings)}, {escape_sql(runs)}, {escape_sql(hs)}, {escape_sql(b_avg)}, {escape_sql(sr)}, {escape_sql(fifties)}, {escape_sql(hundreds)}, {escape_sql(wickets)}, {escape_sql(bowl_avg)}, {escape_sql(econ)}, {best_bowl_str})")
            else:
                stat_inserts.append(f"({escape_sql(p_id)}, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL)")

if reg_inserts:
    reg_sql = "INSERT INTO `registrations` (`player_id`, `full_name`, `category`, `emp_id`, `department`, `mobile`, `email`, `role`, `experience`, `batting_type`, `bowling_type`) VALUES\n"
    sql_statements.append(reg_sql + ",\n".join(reg_inserts) + ";\n")

if stat_inserts:
    sql_statements.append("INSERT INTO `player_stats` (`player_id`, `matches_played`, `innings`, `runs_scored`, `highest_score`, `batting_avg`, `strike_rate`, `fifties`, `hundreds`, `wickets`, `bowling_avg`, `economy`, `best_bowling`) VALUES\n" + ",\n".join(stat_inserts) + ";\n")

with open(sql_file_path, "w", encoding="utf-8") as f:
    f.write("\n".join(sql_statements))

print("SQL Generation complete! Saved to data/import_data.sql")
