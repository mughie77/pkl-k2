<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// Helper functions
function set_flash_message($type, $message) {
    $_SESSION['flash_message'] = ['type' => $type, 'message' => $message];
}

function redirect_to_manage_students() {
    header("Location: ../manage_students.php");
    exit;
}

// Security check: only admins can access
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    set_flash_message('danger', 'Akses ditolak.');
    redirect_to_manage_students();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excelFile'])) {
    $file = $_FILES['excelFile'];

    // Validate file upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        set_flash_message('danger', 'Terjadi kesalahan saat mengunggah file.');
        redirect_to_manage_students();
    }

    $fileType = IOFactory::identify($file['tmp_name']);
    if ($fileType !== 'Xlsx') {
        set_flash_message('danger', 'Hanya file .xlsx yang diizinkan.');
        redirect_to_manage_students();
    }

    $spreadsheet = IOFactory::load($file['tmp_name']);
    $sheet = $spreadsheet->getActiveSheet();
    $data = $sheet->toArray(null, true, true, true);

    $header = array_shift($data); // Remove header row

    $pdo->beginTransaction();
    try {
        $stmt_insert = $pdo->prepare(
            "INSERT INTO students (name, nis, nisn, email, birth_place, birth_date, address, phone, parent_phone, kelas_id, academic_year_id, password)
             VALUES (:name, :nis, :nisn, :email, :birth_place, :birth_date, :address, :phone, :parent_phone, :kelas_id, :academic_year_id, :password)"
        );
        $stmt_kelas = $pdo->prepare("SELECT id FROM kelas WHERE CONCAT(program_name, ' - ', konsentrasi_name, ' - ', kelas_name) = :name");
        $stmt_year = $pdo->prepare("SELECT id FROM academic_years WHERE year_name = :name");

        $imported_count = 0;
        foreach ($data as $row) {
            // Map columns to variables
            $name = trim($row['A']);
            $nis = trim($row['B']);
            $nisn = trim($row['C']);

            if (empty($name) || empty($nisn)) {
                continue; // Skip empty rows
            }

            // Find kelas_id
            $kelas_full_name = trim($row['J']);
            // This is a simplified lookup. A more robust solution would join the tables.
            // For now, let's assume the full name is constructed this way.
             $stmt_kelas_lookup = $pdo->prepare("
                SELECT k.id FROM kelas k
                JOIN konsentrasi_keahlian kk ON k.konsentrasi_id = kk.id
                JOIN program_keahlian pk ON kk.program_id = pk.id
                WHERE CONCAT(pk.program_name, ' - ', kk.konsentrasi_name, ' - ', k.kelas_name) = :name
            ");
            $stmt_kelas_lookup->execute([':name' => $kelas_full_name]);
            $kelas = $stmt_kelas_lookup->fetch();
            if (!$kelas) {
                throw new Exception("Kelas '{$kelas_full_name}' tidak ditemukan untuk siswa '{$name}'.");
            }

            // Find academic_year_id
            $year_name = trim($row['K']);
            $stmt_year->execute([':name' => $year_name]);
            $year = $stmt_year->fetch();
            if (!$year) {
                throw new Exception("Tahun Pelajaran '{$year_name}' tidak ditemukan untuk siswa '{$name}'.");
            }

            $stmt_insert->execute([
                ':name' => $name,
                ':nis' => $nis,
                ':nisn' => $nisn,
                ':email' => trim($row['D']),
                ':birth_place' => trim($row['E']),
                ':birth_date' => !empty($row['F']) ? date('Y-m-d', strtotime($row['F'])) : null,
                ':address' => trim($row['G']),
                ':phone' => trim($row['H']),
                ':parent_phone' => trim($row['I']),
                ':kelas_id' => $kelas['id'],
                ':academic_year_id' => $year['id'],
                ':password' => password_hash($nisn, PASSWORD_DEFAULT)
            ]);
            $imported_count++;
        }

        $pdo->commit();
        set_flash_message('success', "Berhasil mengimpor {$imported_count} data siswa.");

    } catch (Exception $e) {
        $pdo->rollBack();
        set_flash_message('danger', 'Gagal mengimpor data: ' . $e->getMessage());
    }

    redirect_to_manage_students();
} else {
    set_flash_message('danger', 'Tidak ada file yang diunggah.');
    redirect_to_manage_students();
}
?>
