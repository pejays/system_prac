<?php
/**
 * auth/login.php
 * Handles login form submission.
 * Validates input, checks credentials, starts session, redirects.
 */
session_start();

require_once __DIR__ . '/../config/db.php';

// ---- Only allow POST ----
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /system_prac/index.php');
    exit;
}

// ---- 1. Grab + trim inputs ----
$email    = trim($_POST['email']    ?? '');
$password = trim($_POST['password'] ?? '');

// ---- 2. Validate ----
$errors = [];

if ($email === '') {
    $errors[] = 'Email is required.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email address.';
}

if ($password === '') {
    $errors[] = 'Password is required.';
}

if (!empty($errors)) {
    $_SESSION['error'] = implode(' ', $errors);
    header('Location: /system_prac/index.php');
    exit;
}

// ---- 3. Look up user ----
try {
    $stmt = $pdo->prepare("SELECT id, full_name, email, password, role FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        $_SESSION['error'] = 'No account found with that email.';
        header('Location: /system_prac/index.php');
        exit;
    }

    // ---- 4. Verify password ----
    if (!password_verify($password, $user['password'])) {
        $_SESSION['error'] = 'Incorrect password. Please try again.';
        header('Location: /system_prac/index.php');
        exit;
    }

    // ---- 5. Login success — store session ----
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['email']     = $user['email'];
    $_SESSION['role']      = $user['role'];

    header('Location: /system_prac/dashboard.php');
    exit;

} catch (PDOException $e) {
    $_SESSION['error'] = 'Login failed: ' . $e->getMessage();
    header('Location: /system_prac/index.php');
    exit;
}