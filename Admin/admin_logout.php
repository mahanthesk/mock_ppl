<?php
session_start();
// Unset Auction Module specific session variables without affecting other modules
unset($_SESSION['auction_admin_id']);
unset($_SESSION['auction_admin_username']);
unset($_SESSION['auction_admin_full_name']);
unset($_SESSION['auction_admin_role']);
unset($_SESSION['auction_admin_logged_in']);
if (isset($_SESSION['logged_in_roles']['auction_admin'])) {
    unset($_SESSION['logged_in_roles']['auction_admin']);
}
if (isset($_SESSION['role']) && $_SESSION['role'] === 'auction_admin') {
    unset($_SESSION['role']);
    unset($_SESSION['is_logged_in']);
    unset($_SESSION['user_id']);
    unset($_SESSION['username']);
}
header('Location: admin_login.php');
exit;
?>