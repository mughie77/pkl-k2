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

    // Data dari form
    $name = trim($_POST['student_name']);
    $nis = trim($_POST['nis']);
    $nisn = trim($_POST['nisn']);
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL) ?: null;
    $birth_place = trim($_POST['birth_place']);
    $birth_date = !empty($_POST['birth_date']) ? $_POST['birth_date'] : null;
    $address = trim($_POST['address']);
    $phone = trim($_POST['phone']);
    $parent_phone = trim($_POST['parent_phone']);
    $kelas_id = $_POST['kelas_id'];
    $academic_year_id = $_POST['academic_year_id'];

    if ($action === 'create') {
        if (empty($name) || empty($nisn) || empty($kelas_id) || empty($academic_year_id)) {
            set_flash_message('danger', 'Nama, NISN, Kelas, dan Tahun Pelajaran wajib diisi.');
            redirect_to_manage_students();
        }

        $hashed_password = password_hash($nisn, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("INSERT INTO students (name, nis, email, password, nisn, birth_place, birth_date, address, phone, parent_phone, kelas_id, academic_year_id) VALUES (:name, :nis, :email, :password, :nisn, :birth_place, :birth_date, :address, :phone, :parent_phone, :kelas_id, :academic_year_id)");
            $stmt->execute([
                ':name' => $name, ':nis' => $nis, ':email' => $email, ':password' => $hashed_password, ':nisn' => $nisn,
                ':birth_place' => $birth_place, ':birth_date' => $birth_date, ':address' => $address, ':phone' => $phone,
                ':parent_phone' => $parent_phone, ':kelas_id' => $kelas_id, ':academic_year_id' => $academic_year_id
            ]);
            set_flash_message('success', 'Data siswa berhasil ditambahkan.');
        } catch (PDOException $e) {
            set_flash_message('danger', 'Gagal menambahkan data: ' . $e->getMessage());
        }
        redirect_to_manage_students();
    }

    if ($action === 'update') {
        $student_id = $_POST['student_id'];
        if (empty($student_id) || empty($name) || empty($nisn) || empty($kelas_id) || empty($academic_year_id)) {
            set_flash_message('danger', 'Data wajib tidak boleh kosong.');
            redirect_to_manage_students();
        }

        $hashed_password = password_hash($nisn, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("UPDATE students SET name = :name, nis = :nis, email = :email, password = :password, nisn = :nisn, birth_place = :birth_place, birth_date = :birth_date, address = :address, phone = :phone, parent_phone = :parent_phone, kelas_id = :kelas_id, academic_year_id = :academic_year_id WHERE id = :id");
            $stmt->execute([
                ':name' => $name, ':nis' => $nis, ':email' => $email, ':password' => $hashed_password, ':nisn' => $nisn,
                ':birth_place' => $birth_place, ':birth_date' => $birth_date, ':address' => $address, ':phone' => $phone,
                ':parent_phone' => $parent_phone, ':kelas_id' => $kelas_id, ':academic_year_id' => $academic_year_id,
                ':id' => $student_id
            ]);
            set_flash_message('success', 'Data siswa berhasil diperbarui.');
        } catch (PDOException $e) {
            set_flash_message('danger', 'Gagal memperbarui data: ' . $e->getMessage());
        }
        redirect_to_manage_students();
    }
}

// Aksi: Hapus Data Siswa (Delete)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'delete') {
    $student_id = $_GET['id'] ?? null;
    if (!empty($student_id)) {
        try {
            $stmt = $pdo->prepare("DELETE FROM students WHERE id = :id");
            $stmt->execute([':id' => $student_id]);
            set_flash_message('success', 'Data siswa berhasil dihapus.');
        } catch (PDOException $e) {
            set_flash_message('danger', 'Gagal menghapus data siswa.');
        }
    }
    redirect_to_manage_students();
}

redirect_to_manage_students();
?>