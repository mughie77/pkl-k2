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

function redirect_to_mapping_page() {
    header("Location: ../internship_mapping.php");
    exit;
}

// Logika utama
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Aksi: Buat Mapping Baru (Create)
    if ($action === 'create') {
        $student_id = $_POST['student_id'];
        $teacher_id = $_POST['teacher_id'];
        $instructor_id = $_POST['instructor_id'];
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $academic_year_id = $_POST['academic_year_id'];

        if (empty($student_id) || empty($teacher_id) || empty($instructor_id) || empty($start_date) || empty($end_date) || empty($academic_year_id)) {
            set_flash_message('danger', 'Semua kolom wajib diisi.');
            redirect_to_mapping_page();
        }

        if ($start_date > $end_date) {
            set_flash_message('danger', 'Tanggal mulai tidak boleh lebih akhir dari tanggal selesai.');
            redirect_to_mapping_page();
        }

        try {
            // Periksa apakah siswa sudah di-mapping
            $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM internship_mappings WHERE student_id = :student_id");
            $check_stmt->execute([':student_id' => $student_id]);
            if ($check_stmt->fetchColumn() > 0) {
                set_flash_message('warning', 'Siswa ini sudah memiliki mapping.');
                redirect_to_mapping_page();
            }

            $stmt = $pdo->prepare("INSERT INTO internship_mappings (student_id, teacher_id, instructor_id, start_date, end_date, academic_year_id) VALUES (:student_id, :teacher_id, :instructor_id, :start_date, :end_date, :academic_year_id)");
            $stmt->execute([
                ':student_id' => $student_id,
                ':teacher_id' => $teacher_id,
                ':instructor_id' => $instructor_id,
                ':start_date' => $start_date,
                ':end_date' => $end_date,
                ':academic_year_id' => $academic_year_id
            ]);
            set_flash_message('success', 'Mapping PKL berhasil dibuat.');
        } catch (PDOException $e) {
            set_flash_message('danger', 'Terjadi kesalahan saat membuat mapping: ' . $e->getMessage());
        }
        redirect_to_mapping_page();
    }

    // Aksi: Perbarui Mapping (Update)
    if ($action === 'update') {
        $mapping_id = $_POST['mapping_id'];
        // student_id tidak bisa diubah, jadi tidak perlu diambil dari POST
        $teacher_id = $_POST['teacher_id'];
        $instructor_id = $_POST['instructor_id'];
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];

        if (empty($mapping_id) || empty($teacher_id) || empty($instructor_id) || empty($start_date) || empty($end_date)) {
            set_flash_message('danger', 'Semua kolom wajib diisi.');
            redirect_to_mapping_page();
        }

        if ($start_date > $end_date) {
            set_flash_message('danger', 'Tanggal mulai tidak boleh lebih akhir dari tanggal selesai.');
            redirect_to_mapping_page();
        }

        try {
            $stmt = $pdo->prepare("UPDATE internship_mappings SET teacher_id = :teacher_id, instructor_id = :instructor_id, start_date = :start_date, end_date = :end_date WHERE id = :id");
            $stmt->execute([
                ':teacher_id' => $teacher_id,
                ':instructor_id' => $instructor_id,
                ':start_date' => $start_date,
                ':end_date' => $end_date,
                ':id' => $mapping_id
            ]);
            set_flash_message('success', 'Mapping PKL berhasil diperbarui.');
        } catch (PDOException $e) {
            set_flash_message('danger', 'Terjadi kesalahan saat memperbarui mapping: ' . $e->getMessage());
        }
        redirect_to_mapping_page();
    }
}

// Aksi: Hapus Mapping (Delete)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'delete') {
    $mapping_id = $_GET['id'] ?? null;

    if (empty($mapping_id)) {
        set_flash_message('danger', 'ID mapping tidak valid.');
        redirect_to_mapping_page();
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM internship_mappings WHERE id = :id");
        $stmt->execute([':id' => $mapping_id]);
        set_flash_message('success', 'Mapping PKL berhasil dihapus.');
    } catch (PDOException $e) {
        set_flash_message('danger', 'Gagal menghapus mapping.');
    }
    redirect_to_mapping_page();
}

// Fallback
set_flash_message('warning', 'Aksi tidak diketahui.');
redirect_to_mapping_page();
?>