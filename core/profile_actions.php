<?php
require_once __DIR__ . '/../config/config.php';

// Keamanan: Pastikan pengguna sudah login
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header("Location: ../login.php");
    exit;
}

function set_flash_message($type, $message) {
    $_SESSION['flash_message'] = ['type' => $type, 'message' => $message];
}

function redirect_to_profile() {
    header("Location: ../profile.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {

    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['user_role'];

    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validasi dasar
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        set_flash_message('danger', 'Semua kolom wajib diisi.');
        redirect_to_profile();
    }

    if ($new_password !== $confirm_password) {
        set_flash_message('danger', 'Password baru dan konfirmasi password tidak cocok.');
        redirect_to_profile();
    }

    if (strlen($new_password) < 6) {
        set_flash_message('danger', 'Password baru minimal harus 6 karakter.');
        redirect_to_profile();
    }

    // Tentukan tabel berdasarkan peran
    $table_map = [
        'admin' => 'admins',
        'teacher' => 'teachers',
        'instructor' => 'instructors',
        'student' => 'students'
    ];
    $table_name = $table_map[$user_role];

    try {
        // 1. Ambil hash password saat ini dari database
        $stmt = $pdo->prepare("SELECT password FROM {$table_name} WHERE id = :id");
        $stmt->execute([':id' => $user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            set_flash_message('danger', 'Pengguna tidak ditemukan.');
            redirect_to_profile();
        }

        // 2. Verifikasi password saat ini
        if (!password_verify($current_password, $user['password'])) {
            set_flash_message('danger', 'Password saat ini yang Anda masukkan salah.');
            redirect_to_profile();
        }

        // 3. Jika semua validasi lolos, hash dan update password baru
        $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        $update_stmt = $pdo->prepare("UPDATE {$table_name} SET password = :password WHERE id = :id");
        $update_stmt->execute([':password' => $new_hashed_password, ':id' => $user_id]);

        set_flash_message('success', 'Password Anda telah berhasil diubah.');

    } catch (PDOException $e) {
        set_flash_message('danger', 'Terjadi kesalahan pada database: ' . $e->getMessage());
    }

    redirect_to_profile();

} else {
    set_flash_message('warning', 'Aksi tidak valid.');
    redirect_to_profile();
}
?>