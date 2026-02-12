<?php
require_once __DIR__ . '/../config/config.php';

// Keamanan: Pastikan hanya guru yang bisa mengakses
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') {
    $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Akses ditolak.'];
    header("Location: ../login.php");
    exit;
}

function set_flash_message($type, $message) {
    $_SESSION['flash_message'] = ['type' => $type, 'message' => $message];
}

function redirect_to_set_locations() {
    header("Location: ../teacher_set_locations.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'set_company_location') {
    $company_id = $_POST['company_id'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    $teacher_id = $_SESSION['user_id'];

    // Validasi input
    if (empty($company_id) || !is_numeric($latitude) || !is_numeric($longitude)) {
        set_flash_message('danger', 'Data lokasi tidak valid.');
        redirect_to_set_locations();
    }

    try {
        // Verifikasi bahwa guru ini berhak mengubah lokasi perusahaan ini
        $stmt_verify = $pdo->prepare("
            SELECT COUNT(*) FROM internship_mappings m
            JOIN instructors i ON m.instructor_id = i.id
            WHERE m.teacher_id = :teacher_id AND i.company_id = :company_id
        ");
        $stmt_verify->execute([':teacher_id' => $teacher_id, ':company_id' => $company_id]);
        $count = $stmt_verify->fetchColumn();

        if ($count > 0) {
            $stmt_update = $pdo->prepare("UPDATE companies SET latitude = :latitude, longitude = :longitude WHERE id = :id");
            $stmt_update->execute([
                ':latitude' => $latitude,
                ':longitude' => $longitude,
                ':id' => $company_id
            ]);
            set_flash_message('success', 'Lokasi DUDIKA berhasil diperbarui.');
        } else {
            set_flash_message('danger', 'Anda tidak berhak mengubah lokasi DUDIKA ini.');
        }
    } catch (PDOException $e) {
        set_flash_message('danger', 'Gagal memperbarui lokasi: ' . $e->getMessage());
    }
    redirect_to_set_locations();
} else {
    redirect_to_set_locations();
}
?>
