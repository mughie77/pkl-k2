<?php
// Enable error reporting for development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Timezone Setting
date_default_timezone_set('Asia/Jakarta');

// Database Credentials
define('DB_HOST', '127.0.0.1'); // atau 'localhost'
define('DB_USER', 'root');
define('DB_PASS', ''); // Sesuaikan dengan password database Anda
define('DB_NAME', 'pkl_digital_app');

// Application URL
define('BASE_URL', 'http://localhost/pkl-digital'); // Sesuaikan dengan URL proyek Anda

// PDO Database Connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e){
    die("ERROR: Could not connect. " . $e->getMessage());
}

// Start Session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>