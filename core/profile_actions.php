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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['user_role'];

    // Aksi: Update Jam Kerja (hanya untuk siswa)
    if ($action === 'update_work_hours' && $user_role === 'student') {
        $start_time = $_POST['work_start_time'];
        $end_time = $_POST['work_end_time'];

        if (!empty($start_time) && !empty($end_time)) {
            try {
                $stmt = $pdo->prepare("UPDATE students SET work_start_time = :start_time, work_end_time = :end_time WHERE id = :id");
                $stmt->execute([':start_time' => $start_time, ':end_time' => $end_time, ':id' => $user_id]);
                set_flash_message('success', 'Jam kerja berhasil diperbarui.');
            } catch (PDOException $e) {
                set_flash_message('danger', 'Gagal memperbarui jam kerja.');
            }
        } else {
            set_flash_message('danger', 'Jam mulai dan selesai tidak boleh kosong.');
        }
        redirect_to_profile();
    }

    // Aksi: Ubah Password (untuk semua peran)
    if ($action === 'change_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

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

        $table_map = [
            'admin' => 'admins',
            'teacher' => 'teachers',
            'instructor' => 'instructors',
            'student' => 'students'
        ];
        $table_name = $table_map[$user_role];

        try {
            $stmt = $pdo->prepare("SELECT password FROM {$table_name} WHERE id = :id");
            $stmt->execute([':id' => $user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                set_flash_message('danger', 'Pengguna tidak ditemukan.');
                redirect_to_profile();
            }

            if (!password_verify($current_password, $user['password'])) {
                set_flash_message('danger', 'Password saat ini yang Anda masukkan salah.');
                redirect_to_profile();
            }

            $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            $update_stmt = $pdo->prepare("UPDATE {$table_name} SET password = :password WHERE id = :id");
            $update_stmt->execute([':password' => $new_hashed_password, ':id' => $user_id]);

            set_flash_message('success', 'Password Anda telah berhasil diubah.');

        } catch (PDOException $e) {
            set_flash_message('danger', 'Terjadi kesalahan pada database: ' . $e->getMessage());
        }

        redirect_to_profile();
    }
}

// Fallback jika tidak ada aksi yang cocok
// set_flash_message('warning', 'Aksi tidak diketahui.');
// redirect_to_profile();
?>