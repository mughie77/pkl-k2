<?php
require_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

function set_flash_message($type, $message) {
    $_SESSION['flash_message'] = ['type' => $type, 'message' => $message];
}

function redirect_to_manage_program() {
    header("Location: ../manage_program_keahlian.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $program_name = trim($_POST['program_name']);
        if (empty($program_name)) {
            set_flash_message('danger', 'Nama Program Keahlian wajib diisi.');
            redirect_to_manage_program();
        }
        try {
            $stmt = $pdo->prepare("INSERT INTO program_keahlian (program_name) VALUES (:name)");
            $stmt->execute([':name' => $program_name]);
            set_flash_message('success', 'Program Keahlian berhasil ditambahkan.');
        } catch (PDOException $e) {
            set_flash_message('danger', 'Gagal menambahkan data: ' . $e->getMessage());
        }
        redirect_to_manage_program();
    }

    if ($action === 'update') {
        $program_id = $_POST['program_id'];
        $program_name = trim($_POST['program_name']);
        if (empty($program_id) || empty($program_name)) {
            set_flash_message('danger', 'Data tidak lengkap.');
            redirect_to_manage_program();
        }
        try {
            $stmt = $pdo->prepare("UPDATE program_keahlian SET program_name = :name WHERE id = :id");
            $stmt->execute([':name' => $program_name, ':id' => $program_id]);
            set_flash_message('success', 'Program Keahlian berhasil diperbarui.');
        } catch (PDOException $e) {
            set_flash_message('danger', 'Gagal memperbarui data: ' . $e->getMessage());
        }
        redirect_to_manage_program();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'delete') {
    $program_id = $_GET['id'] ?? null;
    if (!empty($program_id)) {
        try {
            $stmt = $pdo->prepare("DELETE FROM program_keahlian WHERE id = :id");
            $stmt->execute([':id' => $program_id]);
            set_flash_message('success', 'Program Keahlian berhasil dihapus.');
        } catch (PDOException $e) {
            set_flash_message('danger', 'Gagal menghapus Program Keahlian. Pastikan tidak ada data terkait (konsentrasi, kelas, siswa).');
        }
    }
    redirect_to_manage_program();
}
?>