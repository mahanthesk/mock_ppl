<?php
session_start();
// Unset Registration Module specific session variables without affecting other modules
unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);
unset($_SESSION['reg_admin_logged_in']);
if (isset($_SESSION['logged_in_roles']['registration_admin'])) {
    unset($_SESSION['logged_in_roles']['registration_admin']);
}
if (isset($_SESSION['role']) && $_SESSION['role'] === 'registration_admin') {
    unset($_SESSION['role']);
    unset($_SESSION['is_logged_in']);
    unset($_SESSION['user_id']);
    unset($_SESSION['username']);
}
header('Location: admin_login.php');
exit;
?>