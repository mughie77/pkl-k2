<?php
require_once __DIR__ . '/../config/config.php';

// Keamanan: Pastikan hanya admin yang bisa mengakses
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Akses ditolak.'];
    header("Location: ../login.php");
    exit;
}

function set_flash_message($type, $message, $serial_number = null) {
    $_SESSION['flash_message'] = ['type' => $type, 'message' => $message];
    if ($serial_number) {
        $_SESSION['flash_message']['serial_number'] = $serial_number;
    }
}

function redirect_to_manage_instructors() {
    header("Location: ../manage_instructors.php");
    exit;
}

// Fungsi untuk generate nomor seri unik
function generate_serial_number($pdo) {
    do {
        // Format: DDK- seguito da 8 caratteri alfanumerici casuali
        $serial = 'DDK-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM instructors WHERE instructor_serial_number = :serial");
        $stmt->execute([':serial' => $serial]);
    } while ($stmt->fetchColumn() > 0);
    return $serial;
}


// Logika utama
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Aksi: Tambah Instruktur (Create)
    if ($action === 'create') {
        $instructor_name = trim($_POST['instructor_name']);
        $company_id = $_POST['company_id'];
        $instructor_position = trim($_POST['instructor_position']);

        if (empty($instructor_name) || empty($company_id)) {
            set_flash_message('danger', 'Nama dan DUDIKA wajib diisi.');
            redirect_to_manage_instructors();
        }

        $serial_number = generate_serial_number($pdo);
        $hashed_password = password_hash($serial_number, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("INSERT INTO instructors (instructor_name, company_id, instructor_position, instructor_serial_number, instructor_password) VALUES (:instructor_name, :company_id, :instructor_position, :instructor_serial_number, :instructor_password)");
            $stmt->execute([
                ':instructor_name' => $instructor_name,
                ':company_id' => $company_id,
                ':instructor_position' => $instructor_position,
                ':instructor_serial_number' => $serial_number,
                ':instructor_password' => $hashed_password
            ]);
            set_flash_message('success', 'Data instruktur berhasil ditambahkan.', $serial_number);
        } catch (PDOException $e) {
            set_flash_message('danger', 'Terjadi kesalahan: ' . $e->getMessage());
        }
        redirect_to_manage_instructors();
    }

    // Aksi: Perbarui Instruktur (Update)
    // Tidak mengubah nomor seri atau password, hanya data lainnya.
    if ($action === 'update') {
        $instructor_id = $_POST['instructor_id'];
        $instructor_name = trim($_POST['instructor_name']);
        $company_id = $_POST['company_id'];
        $instructor_position = trim($_POST['instructor_position']);

        if (empty($instructor_id) || empty($instructor_name) || empty($company_id)) {
            set_flash_message('danger', 'Nama dan DUDIKA wajib diisi.');
            redirect_to_manage_instructors();
        }

        try {
            $stmt = $pdo->prepare("UPDATE instructors SET instructor_name = :instructor_name, company_id = :company_id, instructor_position = :instructor_position WHERE id = :id");
            $stmt->execute([
                ':instructor_name' => $instructor_name,
                ':company_id' => $company_id,
                ':instructor_position' => $instructor_position,
                ':id' => $instructor_id
            ]);
            set_flash_message('success', 'Data instruktur berhasil diperbarui.');
        } catch (PDOException $e) {
            set_flash_message('danger', 'Terjadi kesalahan saat memperbarui data: ' . $e->getMessage());
        }
        redirect_to_manage_instructors();
    }
}

// Aksi: Hapus Instruktur (Delete)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'delete') {
    $instructor_id = $_GET['id'] ?? null;

    if (empty($instructor_id)) {
        set_flash_message('danger', 'ID instruktur tidak valid.');
        redirect_to_manage_instructors();
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM instructors WHERE id = :id");
        $stmt->execute([':id' => $instructor_id]);
        set_flash_message('success', 'Data instruktur berhasil dihapus.');
    } catch (PDOException $e) {
        set_flash_message('danger', 'Gagal menghapus data. Mungkin instruktur ini terkait dengan data PKL.');
    }
    redirect_to_manage_instructors();
}

// Fallback
set_flash_message('warning', 'Aksi tidak diketahui.');
redirect_to_manage_instructors();
?>