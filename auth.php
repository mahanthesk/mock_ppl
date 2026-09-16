<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Checks if a specific role is logged into the session.
 *
 * @param string $role
 * @return bool
 */
function is_role_logged_in($role) {
    if (!empty($_SESSION['logged_in_roles'][$role])) {
        return true;
    }
    if ($role === 'registration_admin' && (!empty($_SESSION['reg_admin_logged_in']) || !empty($_SESSION['admin_id']))) {
        $_SESSION['logged_in_roles']['registration_admin'] = true;
        return true;
    }
    if ($role === 'auction_admin' && (!empty($_SESSION['auction_admin_logged_in']) || !empty($_SESSION['auction_admin_id']))) {
        $_SESSION['logged_in_roles']['auction_admin'] = true;
        return true;
    }
    if ($role === 'team_owner' && (!empty($_SESSION['owner_logged_in']) || !empty($_SESSION['owner_team_id']))) {
        $_SESSION['logged_in_roles']['team_owner'] = true;
        return true;
    }
    if (!empty($_SESSION['is_logged_in']) && isset($_SESSION['role']) && $_SESSION['role'] === $role) {
        $_SESSION['logged_in_roles'][$role] = true;
        return true;
    }
    return false;
}

/**
 * Checks if the user has the required role to access the page.
 * If not, it redirects them to the appropriate login page based on the required role.
 *
 * @param string|array $required_roles The role or array of roles required to access the page.
 */
function check_access($required_roles) {
    if (!is_array($required_roles)) {
        $required_roles = [$required_roles];
    }

    // Check if ANY of the required roles is currently logged in
    $has_access = false;
    $matched_role = null;
    foreach ($required_roles as $role) {
        if (is_role_logged_in($role)) {
            $has_access = true;
            $matched_role = $role;
            break;
        }
    }

    // If user has access for this module, synchronize generic role variable and return without redirecting
    if ($has_access) {
        $_SESSION['role'] = $matched_role;
        $_SESSION['is_logged_in'] = true;
        return;
    }

    // Determine if we are currently inside the 'Admin' folder
    $in_admin_folder = strpos($_SERVER['SCRIPT_NAME'], '/Admin/') !== false;
    
    // Prefix for redirecting to root (if we are in Admin folder)
    $root_prefix = $in_admin_folder ? '../' : '';
    // Prefix for redirecting to Admin folder (if we are in root folder)
    $admin_prefix = $in_admin_folder ? '' : 'Admin/';

    // Not logged in for any required role of this module -> redirect to appropriate login page
    $primary_role = $required_roles[0];
    
    if ($primary_role === 'registration_admin') {
        header("Location: " . $root_prefix . "admin_login.php");
    } elseif ($primary_role === 'auction_admin') {
        header("Location: " . $admin_prefix . "admin_login.php");
    } elseif ($primary_role === 'team_owner') {
        header("Location: " . $admin_prefix . "owner_login.php");
    } else {
        die("Invalid role configuration.");
    }
    exit;
}
?>
