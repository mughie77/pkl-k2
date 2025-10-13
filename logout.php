<?php
// Mulai sesi untuk mengakses variabel sesi
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Hapus semua variabel sesi
$_SESSION = array();

// Hancurkan sesi
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Panggil config untuk mendapatkan BASE_URL
require_once __DIR__ . '/config/config.php';

// Arahkan kembali ke halaman login
header("Location: login.php");
exit;
?>