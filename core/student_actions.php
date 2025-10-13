<?php
require_once __DIR__ . '/../config/config.php';

// Pastikan hanya admin yang bisa mengakses
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Anda tidak memiliki akses ke halaman ini.'];
    header("Location: ../login.php");
    exit;
}

function set_flash_message($type, $message) {
    $_SESSION['flash_message'] = ['type' => $type, 'message' => $message];
}

function redirect_to_manage_students() {
    header("Location: ../manage_students.php");
    exit;
}

// Logika utama
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Aksi: Tambah Siswa Baru (Create)
    if ($action === 'create') {
        $name = trim($_POST['name']);
        $nisn = trim($_POST['nisn']);
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL) ?: null;
        $department_id = $_POST['department_id'];
        $academic_year_id = $_POST['academic_year_id'];

        if (empty($name) || empty($nisn) || empty($department_id) || empty($academic_year_id)) {
            set_flash_message('danger', 'Semua kolom wajib diisi.');
            redirect_to_manage_students();
        }

        $hashed_password = password_hash($nisn, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("INSERT INTO students (name, nisn, email, department_id, academic_year_id, password) VALUES (:name, :nisn, :email, :department_id, :academic_year_id, :password)");
            $stmt->execute([
                ':name' => $name,
                ':nisn' => $nisn,
                ':email' => $email,
                ':department_id' => $department_id,
                ':academic_year_id' => $academic_year_id,
                ':password' => $hashed_password
            ]);
            set_flash_message('success', 'Data siswa berhasil ditambahkan.');
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                set_flash_message('danger', 'Gagal menambahkan data. NISN sudah terdaftar.');
            } else {
                set_flash_message('danger', 'Terjadi kesalahan saat menambahkan data: ' . $e->getMessage());
            }
        }
        redirect_to_manage_students();
    }

    // Aksi: Perbarui Data Siswa (Update)
    if ($action === 'update') {
        $student_id = $_POST['student_id'];
        $name = trim($_POST['name']);
        $nisn = trim($_POST['nisn']);
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL) ?: null;
        $department_id = $_POST['department_id'];
        $academic_year_id = $_POST['academic_year_id'];

        if (empty($student_id) || empty($name) || empty($nisn) || empty($department_id) || empty($academic_year_id)) {
            set_flash_message('danger', 'Semua kolom wajib diisi.');
            redirect_to_manage_students();
        }

        $hashed_password = password_hash($nisn, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("UPDATE students SET name = :name, nisn = :nisn, email = :email, department_id = :department_id, academic_year_id = :academic_year_id, password = :password WHERE id = :id");
            $stmt->execute([
                ':name' => $name,
                ':nisn' => $nisn,
                ':email' => $email,
                ':department_id' => $department_id,
                ':academic_year_id' => $academic_year_id,
                ':password' => $hashed_password,
                ':id' => $student_id
            ]);
            set_flash_message('success', 'Data siswa berhasil diperbarui.');
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                set_flash_message('danger', 'Gagal memperbarui data. NISN sudah digunakan oleh siswa lain.');
            } else {
                set_flash_message('danger', 'Terjadi kesalahan saat memperbarui data: ' . $e->getMessage());
            }
        }
        redirect_to_manage_students();
    }
}

// Aksi: Hapus Data Siswa (Delete)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'delete') {
    $student_id = $_GET['id'] ?? null;

    if (empty($student_id)) {
        set_flash_message('danger', 'ID siswa tidak valid.');
        redirect_to_manage_students();
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM students WHERE id = :id");
        $stmt->execute([':id' => $student_id]);
        set_flash_message('success', 'Data siswa berhasil dihapus.');
    } catch (PDOException $e) {
        set_flash_message('danger', 'Gagal menghapus data siswa. Mungkin data ini terkait dengan data lain.');
    }
    redirect_to_manage_students();
}

redirect_to_manage_students();
?>