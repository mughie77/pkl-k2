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
    header("Location: ../manage_kelas.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = trim($_POST['kelas_name']);
        $konsentrasi_id = $_POST['konsentrasi_id'];
        if (empty($name) || empty($konsentrasi_id)) {
            set_flash_message('danger', 'Semua kolom wajib diisi.');
            redirect_to_manage_page();
        }
        try {
            $stmt = $pdo->prepare("INSERT INTO kelas (konsentrasi_id, kelas_name) VALUES (:konsentrasi_id, :name)");
            $stmt->execute([':konsentrasi_id' => $konsentrasi_id, ':name' => $name]);
            set_flash_message('success', 'Kelas berhasil ditambahkan.');
        } catch (PDOException $e) {
            set_flash_message('danger', 'Gagal: ' . $e->getMessage());
        }
        redirect_to_manage_page();
    }

    if ($action === 'update') {
        $id = $_POST['kelas_id'];
        $name = trim($_POST['kelas_name']);
        $konsentrasi_id = $_POST['konsentrasi_id'];
        if (empty($id) || empty($name) || empty($konsentrasi_id)) {
            set_flash_message('danger', 'Data tidak lengkap.');
            redirect_to_manage_page();
        }
        try {
            $stmt = $pdo->prepare("UPDATE kelas SET konsentrasi_id = :konsentrasi_id, kelas_name = :name WHERE id = :id");
            $stmt->execute([':konsentrasi_id' => $konsentrasi_id, ':name' => $name, ':id' => $id]);
            set_flash_message('success', 'Kelas berhasil diperbarui.');
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
            $stmt = $pdo->prepare("DELETE FROM kelas WHERE id = :id");
            $stmt->execute([':id' => $id]);
            set_flash_message('success', 'Kelas berhasil dihapus.');
        } catch (PDOException $e) {
            set_flash_message('danger', 'Gagal menghapus. Pastikan tidak ada siswa yang terkait dengan kelas ini.');
        }
    }
    redirect_to_manage_page();
}
?>