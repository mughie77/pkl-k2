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

function redirect_to_manage_teachers() {
    header("Location: ../manage_teachers.php");
    exit;
}

// Logika utama
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Aksi: Tambah Guru (Create)
    if ($action === 'create') {
        $name = trim($_POST['name']);
        $nip = trim($_POST['nip']);
        $department = trim($_POST['department']);

        if (empty($name) || empty($nip) || empty($department)) {
            set_flash_message('danger', 'Semua kolom wajib diisi.');
            redirect_to_manage_teachers();
        }

        // Password di-hash dari NIP
        $hashed_password = password_hash($nip, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("INSERT INTO teachers (name, nip, department, password) VALUES (:name, :nip, :department, :password)");
            $stmt->execute([
                ':name' => $name,
                ':nip' => $nip,
                ':department' => $department,
                ':password' => $hashed_password
            ]);
            set_flash_message('success', 'Data guru berhasil ditambahkan. Username & Password default adalah NIP.');
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                set_flash_message('danger', 'Gagal menambahkan data. NIP sudah terdaftar.');
            } else {
                set_flash_message('danger', 'Terjadi kesalahan: ' . $e->getMessage());
            }
        }
        redirect_to_manage_teachers();
    }

    // Aksi: Perbarui Guru (Update)
    if ($action === 'update') {
        $teacher_id = $_POST['teacher_id'];
        $name = trim($_POST['name']);
        $nip = trim($_POST['nip']);
        $department = trim($_POST['department']);

        if (empty($teacher_id) || empty($name) || empty($nip) || empty($department)) {
            set_flash_message('danger', 'Semua kolom wajib diisi.');
            redirect_to_manage_teachers();
        }

        // Password di-hash ulang dari NIP yang baru
        $hashed_password = password_hash($nip, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("UPDATE teachers SET name = :name, nip = :nip, department = :department, password = :password WHERE id = :id");
            $stmt->execute([
                ':name' => $name,
                ':nip' => $nip,
                ':department' => $department,
                ':password' => $hashed_password,
                ':id' => $teacher_id
            ]);
            set_flash_message('success', 'Data guru berhasil diperbarui. Username & Password direset sesuai NIP.');
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                set_flash_message('danger', 'Gagal memperbarui data. NIP sudah digunakan oleh guru lain.');
            } else {
                set_flash_message('danger', 'Terjadi kesalahan: ' . $e->getMessage());
            }
        }
        redirect_to_manage_teachers();
    }
}

// Aksi: Hapus Guru (Delete)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'delete') {
    $teacher_id = $_GET['id'] ?? null;

    if (empty($teacher_id)) {
        set_flash_message('danger', 'ID guru tidak valid.');
        redirect_to_manage_teachers();
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM teachers WHERE id = :id");
        $stmt->execute([':id' => $teacher_id]);
        set_flash_message('success', 'Data guru berhasil dihapus.');
    } catch (PDOException $e) {
        set_flash_message('danger', 'Gagal menghapus data guru. Mungkin data ini terkait dengan data lain.');
    }
    redirect_to_manage_teachers();
}

// Fallback
set_flash_message('warning', 'Aksi tidak diketahui.');
redirect_to_manage_teachers();
?>