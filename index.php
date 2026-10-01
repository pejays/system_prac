<?php
/**
 * index.php
 * Landing / Login page. Shows the login form.
 * If already logged in, redirects to dashboard.
 */
session_start();

// Already logged in? Go straight to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: /system_prac/dashboard.php');
    exit;
}

// Pull error/success messages from session (set by auth/login.php)
$error   = $_SESSION['error']   ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error'], $_SESSION['success']);

$page_title = "Login";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Campus Equipment Borrowing</title>
    <link rel="stylesheet" href="/system_prac/assets/css/style.css">
</head>
<body>

<div class="login-wrap">
    <h1>🏫 Login</h1>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form action="/system_prac/auth/login.php" method="POST">
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required autofocus>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>

        <button type="submit" class="btn">Log In</button>
    </form>

    <p class="text-center mt-2" style="font-size:13px; color:#6b7280;">
        Test accounts — password: <strong>password123</strong><br>
        student@dnsc.edu.ph · faculty@dnsc.edu.ph · staff@dnsc.edu.ph
    </p>
</div>

</body>
</html>