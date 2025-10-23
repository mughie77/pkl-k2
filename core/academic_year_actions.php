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

function redirect_to_manage_years() {
    header("Location: ../manage_academic_years.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = trim($_POST['year_name']);
        if (!empty($name)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO academic_years (year_name) VALUES (:name)");
                $stmt->execute([':name' => $name]);
                set_flash_message('success', 'Tahun pelajaran berhasil ditambahkan.');
            } catch (PDOException $e) {
                set_flash_message('danger', 'Gagal menambahkan. Mungkin tahun pelajaran sudah ada.');
            }
        } else {
            set_flash_message('danger', 'Nama tahun pelajaran tidak boleh kosong.');
        }
        redirect_to_manage_years();
    }

    if ($action === 'update') {
        $id = $_POST['year_id'];
        $name = trim($_POST['year_name']);
        if (!empty($id) && !empty($name)) {
            try {
                $stmt = $pdo->prepare("UPDATE academic_years SET year_name = :name WHERE id = :id");
                $stmt->execute([':name' => $name, ':id' => $id]);
                set_flash_message('success', 'Tahun pelajaran berhasil diperbarui.');
            } catch (PDOException $e) {
                set_flash_message('danger', 'Gagal memperbarui tahun pelajaran.');
            }
        } else {
            set_flash_message('danger', 'Data tidak lengkap.');
        }
        redirect_to_manage_years();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    $action = $_GET['action'];
    $id = $_GET['id'] ?? null;

    if ($action === 'activate' && !empty($id)) {
        try {
            $pdo->beginTransaction();
            // Nonaktifkan semua tahun ajaran lainnya
            $pdo->exec("UPDATE academic_years SET status = 'inactive'");
            // Aktifkan yang dipilih
            $stmt = $pdo->prepare("UPDATE academic_years SET status = 'active' WHERE id = :id");
            $stmt->execute([':id' => $id]);

            // Ambil nama tahun ajaran yang baru diaktifkan
            $stmt_get_name = $pdo->prepare("SELECT year_name FROM academic_years WHERE id = :id");
            $stmt_get_name->execute([':id' => $id]);
            $new_active_year = $stmt_get_name->fetch(PDO::FETCH_ASSOC);

            if ($new_active_year) {
                // Perbarui sesi secara langsung
                $_SESSION['selected_academic_year_id'] = $id;
                $_SESSION['active_academic_year_name'] = $new_active_year['year_name'];
            }

            $pdo->commit();
            set_flash_message('success', 'Tahun pelajaran berhasil diaktifkan dan sesi telah diperbarui.');
        } catch (PDOException $e) {
            $pdo->rollBack();
            set_flash_message('danger', 'Gagal mengaktifkan tahun pelajaran.');
        }
        redirect_to_manage_years();
    }

    if ($action === 'delete' && !empty($id)) {
        try {
            $stmt = $pdo->prepare("DELETE FROM academic_years WHERE id = :id");
            $stmt->execute([':id' => $id]);
            set_flash_message('success', 'Tahun pelajaran berhasil dihapus.');
        } catch (PDOException $e) {
            set_flash_message('danger', 'Gagal menghapus. Pastikan tidak ada siswa yang terdaftar di tahun ini.');
        }
        redirect_to_manage_years();
    }
}

redirect_to_manage_years();
?>