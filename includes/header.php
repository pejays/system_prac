<?php
/**
 * includes/header.php
 * Shared header for all pages. Starts session and displays nav.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) : 'Campus Equipment Borrowing System' ?></title>
    <link rel="stylesheet" href="/system_prac/assets/css/style.css">
</head>
<body>

<header class="topbar">
    <div class="container">
        <a href="/system_prac/dashboard.php" class="logo">🏫 Campus Equipment Borrowing</a>

        <?php if (isset($_SESSION['user_id'])): ?>
            <nav class="nav">
                <span class="welcome">
                    Hi, <?= htmlspecialchars($_SESSION['full_name']) ?>
                    <small>(<?= htmlspecialchars($_SESSION['role']) ?>)</small>
                </span>

                <?php if ($_SESSION['role'] === 'staff'): ?>
                    <a href="/system_prac/modules/equipment.php">Equipment</a>
                    <a href="/system_prac/modules/approvals.php">Approvals</a>
                    <a href="/system_prac/modules/returns.php">Returns</a>
                <?php else: ?>
                    <a href="/system_prac/modules/requests.php">My Requests</a>
                <?php endif; ?>

                <a href="/system_prac/auth/logout.php" class="btn-logout">Logout</a>
            </nav>
        <?php endif; ?>
    </div>
</header>

<main class="container">