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
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $department = trim($_POST['department']);
        $password = $_POST['password'];

        if (empty($name) || empty($email) || empty($department) || empty($password)) {
            set_flash_message('danger', 'Semua kolom wajib diisi.');
            redirect_to_manage_teachers();
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("INSERT INTO teachers (name, email, department, password) VALUES (:name, :email, :department, :password)");
            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':department' => $department,
                ':password' => $hashed_password
            ]);
            set_flash_message('success', 'Data guru berhasil ditambahkan.');
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                set_flash_message('danger', 'Gagal menambahkan data. Email sudah terdaftar.');
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
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $department = trim($_POST['department']);
        $password = $_POST['password'];

        if (empty($teacher_id) || empty($name) || empty($email) || empty($department)) {
            set_flash_message('danger', 'Semua kolom (kecuali password) wajib diisi.');
            redirect_to_manage_teachers();
        }

        try {
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE teachers SET name = :name, email = :email, department = :department, password = :password WHERE id = :id");
                $stmt->execute([
                    ':name' => $name,
                    ':email' => $email,
                    ':department' => $department,
                    ':password' => $hashed_password,
                    ':id' => $teacher_id
                ]);
            } else {
                $stmt = $pdo->prepare("UPDATE teachers SET name = :name, email = :email, department = :department WHERE id = :id");
                $stmt->execute([
                    ':name' => $name,
                    ':email' => $email,
                    ':department' => $department,
                    ':id' => $teacher_id
                ]);
            }
            set_flash_message('success', 'Data guru berhasil diperbarui.');
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                set_flash_message('danger', 'Gagal memperbarui data. Email sudah digunakan oleh guru lain.');
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