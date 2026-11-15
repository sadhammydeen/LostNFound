<?php
/**
 * Authentication and Session Helpers
 * Provides require_login() and require_admin() access guard functions.
 */

// Start session if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if a user is logged in.
 *
 * @return bool True if logged in, false otherwise.
 */
function is_logged_in(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if the currently logged in user has admin role.
 *
 * @return bool True if admin, false otherwise.
 */
function is_admin(): bool {
    return is_logged_in() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Guard function: Redirects to login page if user is not authenticated.
 *
 * @param string $redirect_to Optional relative path to login page.
 */
function require_login(string $redirect_to = 'login.php'): void {
    if (!is_logged_in()) {
        $_SESSION['error_message'] = "Please log in to access this page.";
        header("Location: " . $redirect_to);
        exit;
    }
}

/**
 * Guard function: Restricts access strictly to administrators.
 * Redirects non-admins to the home page or login page with an error.
 *
 * @param string $redirect_to Optional relative path to redirect unauthorized users.
 */
function require_admin(string $redirect_to = '../index.php'): void {
    if (!is_logged_in()) {
        $_SESSION['error_message'] = "Admin login required.";
        header("Location: ../login.php");
        exit;
    }

    if (!is_admin()) {
        $_SESSION['error_message'] = "Access denied. Administrator privileges required.";
        header("Location: " . $redirect_to);
        exit;
    }
}
?>
