<?php
/**
 * Shared Header Template
 * Renders HTML head, CSS link, and role-based navigation bar.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/auth.php';

// Determine base URL path offset for admin folder vs root folder
$base_path = defined('IN_ADMIN') && IN_ADMIN ? '../' : './';
$admin_path = defined('IN_ADMIN') && IN_ADMIN ? './' : 'admin/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - ' : ''; ?>Lost & Found Portal</title>
    <link rel="stylesheet" href="<?php echo $base_path; ?>css/style.css">
</head>
<body>
    <header class="navbar">
        <div class="nav-container">
            <a href="<?php echo $base_path; ?>index.php" class="brand-logo">
                <span class="logo-badge">🎓</span> Campus Lost &amp; Found
            </a>
            <nav class="nav-links">
                <a href="<?php echo $base_path; ?>index.php">Browse Items</a>
                <?php if (is_logged_in()): ?>
                    <a href="<?php echo $base_path; ?>report_item.php">Report Item</a>
                    <a href="<?php echo $base_path; ?>my_items.php">My Reports</a>
                    <a href="<?php echo $base_path; ?>my_claims.php">My Claims</a>
                    <?php if (is_admin()): ?>
                        <a href="<?php echo $admin_path; ?>dashboard.php" class="admin-link">Admin Dashboard</a>
                    <?php endif; ?>
                    <span class="user-greeting">
                        Hi, <strong><?php echo htmlspecialchars($_SESSION['name'] ?? 'User'); ?></strong>
                        <span class="role-pill <?php echo htmlspecialchars($_SESSION['role'] ?? 'student'); ?>">
                            <?php echo ucfirst(htmlspecialchars($_SESSION['role'] ?? 'student')); ?>
                        </span>
                    </span>
                    <a href="<?php echo $base_path; ?>logout.php" class="btn btn-outline-nav">Logout</a>
                <?php else: ?>
                    <a href="<?php echo $base_path; ?>login.php" class="btn btn-outline-nav">Login</a>
                    <a href="<?php echo $base_path; ?>register.php" class="btn btn-primary-nav">Register</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="main-container">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?php 
                    echo htmlspecialchars($_SESSION['success_message']); 
                    unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger">
                <?php 
                    echo htmlspecialchars($_SESSION['error_message']); 
                    unset($_SESSION['error_message']);
                ?>
            </div>
        <?php endif; ?>
