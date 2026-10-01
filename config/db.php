<?php
/**
 * config/db.php
 * Database connection using PDO.
 * All modules include this file to access the database.
 */

// Show errors during development (turn OFF before final submission)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ---- Database credentials (XAMPP defaults) ----
$db_host = 'localhost';
$db_name = 'system_prac';
$db_user = 'root';
$db_pass = '';        // XAMPP default = empty password

// ---- Connection string ----
$dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";

// ---- PDO options ----
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// ---- Connect ----
try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}