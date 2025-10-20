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

function redirect_to_manage_departments() {
    header("Location: ../manage_departments.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Aksi: Tambah Jurusan
    if ($action === 'create') {
        $name = trim($_POST['department_name']);
        if (!empty($name)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO departments (department_name) VALUES (:name)");
                $stmt->execute([':name' => $name]);
                set_flash_message('success', 'Jurusan berhasil ditambahkan.');
            } catch (PDOException $e) {
                set_flash_message('danger', 'Gagal menambahkan jurusan. Mungkin sudah ada.');
            }
        } else {
            set_flash_message('danger', 'Nama jurusan tidak boleh kosong.');
        }
        redirect_to_manage_departments();
    }

    // Aksi: Update Jurusan
    if ($action === 'update') {
        $id = $_POST['department_id'];
        $name = trim($_POST['department_name']);
        if (!empty($id) && !empty($name)) {
            try {
                $stmt = $pdo->prepare("UPDATE departments SET department_name = :name WHERE id = :id");
                $stmt->execute([':name' => $name, ':id' => $id]);
                set_flash_message('success', 'Jurusan berhasil diperbarui.');
            } catch (PDOException $e) {
                set_flash_message('danger', 'Gagal memperbarui jurusan.');
            }
        } else {
            set_flash_message('danger', 'Data tidak lengkap.');
        }
        redirect_to_manage_departments();
    }
}

// Aksi: Hapus Jurusan
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'delete') {
    $id = $_GET['id'] ?? null;
    if (!empty($id)) {
        try {
            $stmt = $pdo->prepare("DELETE FROM departments WHERE id = :id");
            $stmt->execute([':id' => $id]);
            set_flash_message('success', 'Jurusan berhasil dihapus.');
        } catch (PDOException $e) {
            set_flash_message('danger', 'Gagal menghapus jurusan. Pastikan tidak ada siswa yang terdaftar di jurusan ini.');
        }
    }
    redirect_to_manage_departments();
}

redirect_to_manage_departments();
?>