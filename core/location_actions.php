<?php
require_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'waka_humas') {
    // Redirect non-Waka Humas users or unauthenticated users
    header("Location: ../login.php");
    exit;
}

function set_flash_message($type, $message) {
    $_SESSION['flash_message'] = ['type' => $type, 'message' => $message];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_location') {
        $company_id = $_POST['company_id'] ?? null;
        $latitude = $_POST['latitude'] ?? null;
        $longitude = $_POST['longitude'] ?? null;

        if ($company_id && $latitude && $longitude) {
            try {
                $stmt = $pdo->prepare("UPDATE companies SET latitude = :lat, longitude = :lon WHERE id = :id");
                $stmt->execute([
                    ':lat' => $latitude,
                    ':lon' => $longitude,
                    ':id' => $company_id
                ]);
                set_flash_message('success', 'Lokasi DUDIKA berhasil diperbarui.');
            } catch (PDOException $e) {
                set_flash_message('danger', 'Gagal memperbarui lokasi: ' . $e->getMessage());
            }
        } else {
            set_flash_message('danger', 'Data tidak lengkap untuk memperbarui lokasi.');
        }
    }
}

header("Location: ../manage_dudika_locations.php");
exit;
?>