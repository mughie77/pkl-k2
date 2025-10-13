<?php
require_once __DIR__ . '/../config/config.php';

// Keamanan: Pastikan hanya admin yang bisa mengakses
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Akses ditolak.'];
    header("Location: ../login.php");
    exit;
}

function set_flash_message($type, $message) {
    $_SESSION['flash_message'] = ['type' => $type, 'message' => $message];
}

function redirect_to_settings_page() {
    header("Location: ../school_settings.php");
    exit;
}

function update_setting($pdo, $key, $value) {
    $stmt = $pdo->prepare("INSERT INTO school_settings (setting_key, setting_value) VALUES (:key, :value) ON DUPLICATE KEY UPDATE setting_value = :value");
    $stmt->execute([':key' => $key, ':value' => $value]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_school_settings') {

    $school_name = trim($_POST['school_name']);
    $school_address = trim($_POST['school_address']);

    if (empty($school_name) || empty($school_address)) {
        set_flash_message('danger', 'Nama dan Alamat Sekolah wajib diisi.');
        redirect_to_settings_page();
    }

    try {
        $pdo->beginTransaction();

        // Update nama dan alamat
        update_setting($pdo, 'school_name', $school_name);
        update_setting($pdo, 'school_address', $school_address);

        // Handle unggahan logo
        if (isset($_FILES['school_logo']) && $_FILES['school_logo']['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/png'];
            $max_size = 1 * 1024 * 1024; // 1 MB

            if (in_array($_FILES['school_logo']['type'], $allowed_types) && $_FILES['school_logo']['size'] <= $max_size) {

                // Hapus logo lama jika ada
                $stmt_old_logo = $pdo->prepare("SELECT setting_value FROM school_settings WHERE setting_key = 'school_logo'");
                $stmt_old_logo->execute();
                $old_logo_path = $stmt_old_logo->fetchColumn();
                if ($old_logo_path && file_exists('../' . $old_logo_path)) {
                    unlink('../' . $old_logo_path);
                }

                $upload_dir = 'uploads/logo/';
                if (!is_dir('../' . $upload_dir)) {
                    mkdir('../' . $upload_dir, 0777, true);
                }

                $file_extension = pathinfo($_FILES['school_logo']['name'], PATHINFO_EXTENSION);
                $file_name = 'school_logo_' . time() . '.' . $file_extension;
                $file_path = $upload_dir . $file_name;

                if (move_uploaded_file($_FILES['school_logo']['tmp_name'], '../' . $file_path)) {
                    update_setting($pdo, 'school_logo', $file_path);
                } else {
                    throw new Exception('Gagal memindahkan file logo yang diunggah.');
                }
            } else {
                throw new Exception('File logo tidak valid. Pastikan format JPG/PNG dan ukuran maksimal 1MB.');
            }
        }

        $pdo->commit();
        set_flash_message('success', 'Pengaturan sekolah berhasil diperbarui.');

    } catch (Exception $e) {
        $pdo->rollBack();
        set_flash_message('danger', 'Terjadi kesalahan: ' . $e->getMessage());
    }

    redirect_to_settings_page();
}

// Fallback
set_flash_message('warning', 'Aksi tidak diketahui.');
redirect_to_settings_page();
?>