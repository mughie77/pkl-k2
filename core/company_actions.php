<?php
require_once __DIR__ . '/../config/config.php';

// Keamanan: Pastikan hanya admin atau waka humas yang bisa mengakses
$allowed_roles = ['admin', 'waka_humas'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Akses ditolak.'];
    header("Location: ../login.php");
    exit;
}

function set_flash_message($type, $message) {
    $_SESSION['flash_message'] = ['type' => $type, 'message' => $message];
}

function redirect_to_manage_companies() {
    header("Location: ../manage_companies.php");
    exit;
}

// Logika utama
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Aksi: Tambah DUDIKA (Create)
    if ($action === 'create') {
        $name = trim($_POST['name']);
        $address = trim($_POST['address']);
        $contact_person = trim($_POST['contact_person']);

        if (empty($name)) {
            set_flash_message('danger', 'Nama DUDIKA wajib diisi.');
            redirect_to_manage_companies();
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO companies (name, address, contact_person) VALUES (:name, :address, :contact_person)");
            $stmt->execute([
                ':name' => $name,
                ':address' => $address,
                ':contact_person' => $contact_person
            ]);
            set_flash_message('success', 'Data DUDIKA berhasil ditambahkan.');
        } catch (PDOException $e) {
            set_flash_message('danger', 'Terjadi kesalahan saat menambahkan data: ' . $e->getMessage());
        }
        redirect_to_manage_companies();
    }

    // Aksi: Set Lokasi GPS (oleh Waka Humas)
    if ($action === 'set_location_waka') {
        if ($_SESSION['user_role'] !== 'waka_humas') {
            set_flash_message('danger', 'Anda tidak memiliki izin untuk melakukan aksi ini.');
            header("Location: ../login.php");
            exit;
        }

        $company_id = $_POST['company_id'];
        $latitude = $_POST['latitude'];
        $longitude = $_POST['longitude'];

        if (empty($company_id) || !is_numeric($latitude) || !is_numeric($longitude)) {
            $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Data lokasi tidak valid.'];
            header("Location: ../manage_dudika_locations.php");
            exit;
        }

        try {
            $stmt = $pdo->prepare("UPDATE companies SET latitude = :latitude, longitude = :longitude WHERE id = :id");
            $stmt->execute([
                ':latitude' => $latitude,
                ':longitude' => $longitude,
                ':id' => $company_id
            ]);
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Lokasi DUDIKA berhasil diperbarui.'];
        } catch (PDOException $e) {
            $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Gagal memperbarui lokasi: ' . $e->getMessage()];
        }
        header("Location: ../manage_dudika_locations.php");
        exit;
    }

    // Aksi: Perbarui DUDIKA (Update)
    if ($action === 'update') {
        if ($_SESSION['user_role'] !== 'admin') {
            set_flash_message('danger', 'Anda tidak memiliki izin untuk melakukan aksi ini.');
            redirect_to_manage_companies();
        }
        $company_id = $_POST['company_id'];
        $name = trim($_POST['name']);
        $address = trim($_POST['address']);
        $contact_person = trim($_POST['contact_person']);

        if (empty($company_id) || empty($name)) {
            set_flash_message('danger', 'Nama DUDIKA wajib diisi.');
            redirect_to_manage_companies();
        }

        try {
            $stmt = $pdo->prepare("UPDATE companies SET name = :name, address = :address, contact_person = :contact_person WHERE id = :id");
            $stmt->execute([
                ':name' => $name,
                ':address' => $address,
                ':contact_person' => $contact_person,
                ':id' => $company_id
            ]);
            set_flash_message('success', 'Data DUDIKA berhasil diperbarui.');
        } catch (PDOException $e) {
            set_flash_message('danger', 'Terjadi kesalahan saat memperbarui data: ' . $e->getMessage());
        }
        redirect_to_manage_companies();
    }
}

// Aksi: Hapus DUDIKA (Delete)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'delete') {
    $company_id = $_GET['id'] ?? null;

    if (empty($company_id)) {
        set_flash_message('danger', 'ID DUDIKA tidak valid.');
        redirect_to_manage_companies();
    }

    try {
        // Menggunakan ON DELETE CASCADE di database, jadi instruktur terkait akan terhapus otomatis.
        $stmt = $pdo->prepare("DELETE FROM companies WHERE id = :id");
        $stmt->execute([':id' => $company_id]);
        set_flash_message('success', 'Data DUDIKA dan semua instruktur terkait berhasil dihapus.');
    } catch (PDOException $e) {
        set_flash_message('danger', 'Gagal menghapus data DUDIKA. Mungkin data ini terkait dengan data lain yang tidak bisa dihapus secara otomatis.');
    }
    redirect_to_manage_companies();
}

// Fallback
set_flash_message('warning', 'Aksi tidak diketahui.');
redirect_to_manage_companies();
?>