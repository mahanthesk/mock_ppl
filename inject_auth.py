import os

root_dir = "c:/xampp/htdocs/SandalwoodPremierLeague"

reg_files = [
    "admin_dashboard.php",
    "admin_players.php",
    "admin_player_view.php",
    "admin_uploads.php"
]

owner_files = [
    "Admin/owner_dashboard.php"
]

auction_files = [
    "Admin/dashboard.php",
    "Admin/all_players.php",
    "Admin/bid_icon.php",
    "Admin/bid_player.php",
    "Admin/final_list.php",
    "Admin/final_pool.php",
    "Admin/pool_setup.php",
    "Admin/team_grid.php",
    "Admin/sold.php",
    "Admin/unsold.php",
    "Admin/unsold-list.php",
    "Admin/team_details.php",
    "Admin/mark_unsold.php",
    "Admin/update_bid.php",
    "Admin/update_icon_bid.php",
    "Admin/add_team_assets.php",
    "Admin/fetch_icons.php",
    "Admin/fetch_player.php",
    "Admin/fetch_teams.php",
    "Admin/player_list.php",
    "Admin/player_listing.php",
    "Admin/player_view.php"
]

def inject_auth(filepath, role):
    full_path = os.path.join(root_dir, filepath)
    if not os.path.exists(full_path):
        print(f"File not found: {full_path}")
        return

    with open(full_path, 'r', encoding='utf-8') as f:
        content = f.read()

    # Determine auth path
    auth_path = "auth.php" if "Admin/" not in filepath else "../auth.php"
    injection = f"require_once '{auth_path}';\ncheck_access('{role}');\n"

    # Avoid double injection
    if "check_access(" in content:
        print(f"Already injected: {filepath}")
        return

    # Find the first <?php tag
    if "<?php" in content:
        # Check if session_start() is near the top and remove it to let auth.php handle it, or just inject after it
        parts = content.split("<?php", 1)
        
        # Check if session_start() exists in the first few lines and replace it
        first_chunk = parts[1][:200]
        if "session_start();" in first_chunk:
            new_first_chunk = first_chunk.replace("session_start();", injection, 1)
            new_content = parts[0] + "<?php\n" + new_first_chunk + parts[1][200:]
        else:
            new_content = parts[0] + "<?php\n" + injection + parts[1]
        
        # if the file specifically checks isset($_SESSION['admin_id']) or something, we should probably remove it
        # but check_access() will trigger before it and handle it, so it's okay for now.
        
        with open(full_path, 'w', encoding='utf-8') as f:
            f.write(new_content)
        print(f"Injected {role} into {filepath}")
    else:
        print(f"No <?php tag found in {filepath}")

for f in reg_files:
    inject_auth(f, 'registration_admin')

for f in owner_files:
    inject_auth(f, 'team_owner')

for f in auction_files:
    inject_auth(f, 'auction_admin')

print("Done injecting RBAC.")
