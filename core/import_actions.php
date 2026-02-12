<?php
require_once __DIR__ . '/../config/config.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function set_flash_message($type, $message) {
    $_SESSION['flash_message'] = ['type' => $type, 'message' => $message];
}

if (isset($_POST['import_students'])) {
    if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] == 0) {
        $inputFileName = $_FILES['excel_file']['tmp_name'];

        try {
            $spreadsheet = IOFactory::load($inputFileName);
            $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

            $valid_rows = [];
            $invalid_rows = [];

            $dept_stmt = $pdo->query("SELECT id, LOWER(department_name) as name FROM departments");
            $departments = $dept_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

            $active_year_id = $pdo->query("SELECT id FROM academic_years WHERE status = 'active' LIMIT 1")->fetchColumn();
            if (!$active_year_id) {
                throw new Exception("Tidak ada tahun ajaran yang aktif. Silakan aktifkan satu di pengaturan.");
            }

            // Skip header row
            array_shift($sheetData);

            foreach ($sheetData as $row) {
                $name = trim($row['A']);
                $nisn = trim($row['B']);
                $email = trim($row['C']);
                $birth_place = trim($row['D']);
                $birth_date = trim($row['E']);
                $address = trim($row['F']);
                $phone = trim($row['G']);
                $parent_phone = trim($row['H']);
                $department_name = strtolower(trim($row['I']));
                $error = '';

                if (empty($name) || empty($nisn) || empty($department_name)) {
                    $error = 'Nama, NISN, dan Jurusan wajib diisi.';
                } elseif (!isset($departments[$department_name])) {
                    $error = 'Jurusan tidak ditemukan di database.';
                } else {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE nisn = :nisn");
                    $stmt->execute([':nisn' => $nisn]);
                    if ($stmt->fetchColumn() > 0) {
                        $error = 'NISN sudah ada di database.';
                    }
                }

                if ($error) {
                    $row['J'] = $error;
                    $invalid_rows[] = $row;
                } else {
                    $valid_rows[] = [
                        'name' => $name, 'nisn' => $nisn, 'email' => $email, 'birth_place' => $birth_place,
                        'birth_date' => $birth_date, 'address' => $address, 'phone' => $phone,
                        'parent_phone' => $parent_phone, 'department_id' => $departments[$department_name],
                        'academic_year_id' => $active_year_id,
                        'password' => password_hash($nisn, PASSWORD_DEFAULT)
                    ];
                }
            }

            // Insert valid data
            if (!empty($valid_rows)) {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("INSERT INTO students (name, nisn, email, birth_place, birth_date, address, phone, parent_phone, department_id, academic_year_id, password) VALUES (:name, :nisn, :email, :birth_place, :birth_date, :address, :phone, :parent_phone, :department_id, :academic_year_id, :password)");
                foreach ($valid_rows as $student) {
                    $stmt->execute($student);
                }
                $pdo->commit();
            }

            $success_count = count($valid_rows);
            $fail_count = count($invalid_rows);
            $message = "Berhasil mengimpor {$success_count} siswa.";

            if ($fail_count > 0) {
                $message .= " Gagal mengimpor {$fail_count} siswa.";
                // Create error report
                $error_spreadsheet = new Spreadsheet();
                $error_sheet = $error_spreadsheet->getActiveSheet();
                $error_sheet->fromArray(['Nama Lengkap', 'NISN', 'Email', 'Tempat Lahir', 'Tanggal Lahir', 'Alamat', 'No. HP Siswa', 'No. HP Orang Tua', 'Jurusan', 'Error'], NULL, 'A1');
                $error_sheet->fromArray($invalid_rows, NULL, 'A2');

                $writer = new Xlsx($error_spreadsheet);
                $upload_dir = __DIR__ . '/../uploads';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                $error_filename = 'laporan_error_import_' . date('YmdHis') . '.xlsx';
                $error_filepath = $upload_dir . '/' . $error_filename;
                $writer->save($error_filepath);

                $_SESSION['error_report_file'] = $error_filename;
            }

            set_flash_message('success', $message);

        } catch (Exception $e) {
            set_flash_message('danger', 'Terjadi kesalahan saat memproses file: ' . $e->getMessage());
        }
    } else {
        set_flash_message('danger', 'Gagal mengunggah file. Pastikan file tidak rusak.');
    }
}

header("Location: ../manage_students.php");
exit;
?>