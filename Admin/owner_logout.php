<?php
session_start();
// Unset Team Owner Module specific session variables without affecting other modules
unset($_SESSION['owner_id']);
unset($_SESSION['owner_team_id']);
unset($_SESSION['owner_username']);
unset($_SESSION['owner_team_name']);
unset($_SESSION['owner_full_name']);
unset($_SESSION['owner_email']);
unset($_SESSION['owner_logged_in']);
if (isset($_SESSION['logged_in_roles']['team_owner'])) {
    unset($_SESSION['logged_in_roles']['team_owner']);
}
if (isset($_SESSION['role']) && $_SESSION['role'] === 'team_owner') {
    unset($_SESSION['role']);
    unset($_SESSION['is_logged_in']);
    unset($_SESSION['user_id']);
    unset($_SESSION['username']);
}
header('Location: owner_login.php');
exit;
?>
