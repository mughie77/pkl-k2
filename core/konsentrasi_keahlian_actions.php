<?php
require_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

function set_flash_message($type, $message) {
    $_SESSION['flash_message'] = ['type' => $type, 'message' => $message];
}

function redirect_to_manage_page() {
    header("Location: ../manage_konsentrasi_keahlian.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = trim($_POST['konsentrasi_name']);
        $program_id = $_POST['program_id'];
        if (empty($name) || empty($program_id)) {
            set_flash_message('danger', 'Semua kolom wajib diisi.');
            redirect_to_manage_page();
        }
        try {
            $stmt = $pdo->prepare("INSERT INTO konsentrasi_keahlian (program_id, konsentrasi_name) VALUES (:program_id, :name)");
            $stmt->execute([':program_id' => $program_id, ':name' => $name]);
            set_flash_message('success', 'Konsentrasi Keahlian berhasil ditambahkan.');
        } catch (PDOException $e) {
            set_flash_message('danger', 'Gagal: ' . $e->getMessage());
        }
        redirect_to_manage_page();
    }

    if ($action === 'update') {
        $id = $_POST['konsentrasi_id'];
        $name = trim($_POST['konsentrasi_name']);
        $program_id = $_POST['program_id'];
        if (empty($id) || empty($name) || empty($program_id)) {
            set_flash_message('danger', 'Data tidak lengkap.');
            redirect_to_manage_page();
        }
        try {
            $stmt = $pdo->prepare("UPDATE konsentrasi_keahlian SET program_id = :program_id, konsentrasi_name = :name WHERE id = :id");
            $stmt->execute([':program_id' => $program_id, ':name' => $name, ':id' => $id]);
            set_flash_message('success', 'Konsentrasi Keahlian berhasil diperbarui.');
        } catch (PDOException $e) {
            set_flash_message('danger', 'Gagal: ' . $e->getMessage());
        }
        redirect_to_manage_page();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'delete') {
    $id = $_GET['id'] ?? null;
    if (!empty($id)) {
        try {
            $stmt = $pdo->prepare("DELETE FROM konsentrasi_keahlian WHERE id = :id");
            $stmt->execute([':id' => $id]);
            set_flash_message('success', 'Konsentrasi Keahlian berhasil dihapus.');
        } catch (PDOException $e) {
            set_flash_message('danger', 'Gagal menghapus. Pastikan tidak ada data kelas atau siswa yang terkait.');
        }
    }
    redirect_to_manage_page();
}
?>