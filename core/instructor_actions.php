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

function redirect_to_manage_instructors() {
    header("Location: ../manage_instructors.php");
    exit;
}

// Logika utama
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Aksi: Tambah Instruktur (Create)
    if ($action === 'create') {
        $name = trim($_POST['name']);
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $company_id = $_POST['company_id'];
        $position = trim($_POST['position']);
        $password = $_POST['password'];

        if (empty($name) || empty($email) || empty($company_id) || empty($password)) {
            set_flash_message('danger', 'Nama, email, DUDIKA, dan password wajib diisi.');
            redirect_to_manage_instructors();
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("INSERT INTO instructors (name, email, company_id, position, password) VALUES (:name, :email, :company_id, :position, :password)");
            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':company_id' => $company_id,
                ':position' => $position,
                ':password' => $hashed_password
            ]);
            set_flash_message('success', 'Data instruktur berhasil ditambahkan.');
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                set_flash_message('danger', 'Gagal menambahkan data. Email sudah terdaftar.');
            } else {
                set_flash_message('danger', 'Terjadi kesalahan: ' . $e->getMessage());
            }
        }
        redirect_to_manage_instructors();
    }

    // Aksi: Perbarui Instruktur (Update)
    if ($action === 'update') {
        $instructor_id = $_POST['instructor_id'];
        $name = trim($_POST['name']);
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $company_id = $_POST['company_id'];
        $position = trim($_POST['position']);
        $password = $_POST['password'];

        if (empty($instructor_id) || empty($name) || empty($email) || empty($company_id)) {
            set_flash_message('danger', 'Nama, email, dan DUDIKA wajib diisi.');
            redirect_to_manage_instructors();
        }

        try {
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE instructors SET name = :name, email = :email, company_id = :company_id, position = :position, password = :password WHERE id = :id");
                $stmt->execute([
                    ':name' => $name,
                    ':email' => $email,
                    ':company_id' => $company_id,
                    ':position' => $position,
                    ':password' => $hashed_password,
                    ':id' => $instructor_id
                ]);
            } else {
                $stmt = $pdo->prepare("UPDATE instructors SET name = :name, email = :email, company_id = :company_id, position = :position WHERE id = :id");
                $stmt->execute([
                    ':name' => $name,
                    ':email' => $email,
                    ':company_id' => $company_id,
                    ':position' => $position,
                    ':id' => $instructor_id
                ]);
            }
            set_flash_message('success', 'Data instruktur berhasil diperbarui.');
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                set_flash_message('danger', 'Gagal memperbarui data. Email sudah digunakan oleh instruktur lain.');
            } else {
                set_flash_message('danger', 'Terjadi kesalahan: ' . $e->getMessage());
            }
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