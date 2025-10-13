<?php
// Memanggil file konfigurasi yang akan memulai sesi
require_once __DIR__ . '/config/config.php';

// Periksa apakah pengguna sudah login dan memiliki peran
if (isset($_SESSION['user_id']) && isset($_SESSION['user_role'])) {
    $role = $_SESSION['user_role'];

    // Arahkan ke dashboard yang sesuai dengan peran pengguna
    // Contoh: 'admin' akan diarahkan ke 'admin_dashboard.php'
    header("Location: {$role}_dashboard.php");
    exit;
} else {
    // Jika tidak ada sesi aktif, arahkan ke halaman login
    header("Location: login.php");
    exit;
}
?>