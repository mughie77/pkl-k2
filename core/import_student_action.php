<?php
require_once __DIR__ . '/../config/config.php';

// Use PhpSpreadsheet classes
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

// Set flash message and redirect
function set_flash_and_redirect($type, $message) {
    $_SESSION['flash_message'] = ['type' => $type, 'message' => $message];
    header("Location: ../manage_students.php");
    exit;
}

// Security checks
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    set_flash_and_redirect('danger', 'Anda tidak memiliki akses ke halaman ini.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_flash_and_redirect('danger', 'Metode permintaan tidak valid.');
}

if (!isset($_FILES['excelFile']) || $_FILES['excelFile']['error'] !== UPLOAD_ERR_OK) {
    set_flash_and_redirect('danger', 'Gagal mengunggah file. Silakan coba lagi.');
}

// File validation
$file_tmp_path = $_FILES['excelFile']['tmp_name'];
$file_name = $_FILES['excelFile']['name'];
$file_extension = pathinfo($file_name, PATHINFO_EXTENSION);

if ($file_extension !== 'xlsx') {
    set_flash_and_redirect('danger', 'Format file tidak valid. Harap unggah file .xlsx');
}

$academic_year_id = $_POST['academic_year_id'] ?? null;
if (empty($academic_year_id)) {
    set_flash_and_redirect('danger', 'Tahun Pelajaran wajib dipilih untuk impor.');
}

// --- Main Logic ---

// Fetch all departments to map names to IDs
try {
    $stmt_depts = $pdo->query("SELECT id, LOWER(department_name) as department_name FROM departments");
    $departments = $stmt_depts->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    set_flash_and_redirect('danger', 'Gagal mengambil data jurusan: ' . $e->getMessage());
}

$success_count = 0;
$error_count = 0;
$error_messages = [];

try {
    // Load the spreadsheet
    $spreadsheet = IOFactory::load($file_tmp_path);
    $sheet = $spreadsheet->getActiveSheet();
    $highest_row = $sheet->getHighestRow();

    // Start transaction
    $pdo->beginTransaction();

    // Loop through each row of the spreadsheet (starting from row 2 to skip header)
    for ($row = 2; $row <= $highest_row; $row++) {
        // Extract data from cells
        $nisn = trim($sheet->getCell('A' . $row)->getValue());

        // Skip empty rows
        if (empty($nisn)) {
            continue;
        }

        $nis = trim($sheet->getCell('B' . $row)->getValue());
        $name = trim($sheet->getCell('C' . $row)->getValue());
        $email = trim($sheet->getCell('D' . $row)->getValue());
        $birth_place = trim($sheet->getCell('E' . $row)->getValue());

        // Handle Excel date which is a number
        $birth_date_excel = $sheet->getCell('F' . $row)->getValue();
        $birth_date = null;
        if (!empty($birth_date_excel) && is_numeric($birth_date_excel)) {
            $birth_date = Date::excelToDateTimeObject($birth_date_excel)->format('Y-m-d');
        } elseif (!empty($birth_date_excel)) {
            // Attempt to parse string dates, though format should be enforced
            try {
                $birth_date = new DateTime($birth_date_excel);
                $birth_date = $birth_date->format('Y-m-d');
            } catch (Exception $e) {
                $birth_date = null;
            }
        }

        $address = trim($sheet->getCell('G' . $row)->getValue());
        $phone = trim($sheet->getCell('H' . $row)->getValue());
        $parent_phone = trim($sheet->getCell('I' . $row)->getValue());
        $department_name = strtolower(trim($sheet->getCell('J' . $row)->getValue()));

        // --- Data Validation ---
        if (empty($name) || empty($department_name)) {
            $error_count++;
            $error_messages[] = "Baris $row: Nama dan Jurusan tidak boleh kosong.";
            continue;
        }

        // Find department ID from name
        $department_id = array_search($department_name, $departments);
        if ($department_id === false) {
            $error_count++;
            $error_messages[] = "Baris $row: Jurusan '$department_name' tidak ditemukan.";
            continue;
        }

        // Hash password from NISN
        $hashed_password = password_hash($nisn, PASSWORD_DEFAULT);

        // Prepare and execute insert statement
        $stmt = $pdo->prepare(
            "INSERT INTO students (nisn, nis, name, email, password, birth_place, birth_date, address, phone, parent_phone, department_id, academic_year_id)
             VALUES (:nisn, :nis, :name, :email, :password, :birth_place, :birth_date, :address, :phone, :parent_phone, :department_id, :academic_year_id)"
        );

        $stmt->execute([
            ':nisn' => $nisn,
            ':nis' => $nis,
            ':name' => $name,
            ':email' => !empty($email) ? filter_var($email, FILTER_VALIDATE_EMAIL) : null,
            ':password' => $hashed_password,
            ':birth_place' => $birth_place,
            ':birth_date' => $birth_date,
            ':address' => $address,
            ':phone' => $phone,
            ':parent_phone' => $parent_phone,
            ':department_id' => $department_id,
            ':academic_year_id' => $academic_year_id
        ]);

        $success_count++;
    }

    // If there were errors, rollback. Otherwise, commit.
    if ($error_count > 0) {
        $pdo->rollBack();
        $final_message = "Impor Gagal. Terdapat $error_count kesalahan. Tidak ada data yang diimpor. Detail: <br>" . implode('<br>', array_slice($error_messages, 0, 5));
        set_flash_and_redirect('danger', $final_message);
    } else {
        $pdo->commit();
        set_flash_and_redirect('success', "Impor berhasil! Sejumlah $success_count data siswa baru telah ditambahkan.");
    }

} catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    set_flash_and_redirect('danger', 'Gagal membaca file Excel: ' . $e->getMessage());
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Check for duplicate entry
    if ($e->errorInfo[1] == 1062) {
         set_flash_and_redirect('danger', 'Impor gagal: Terdapat duplikasi data NISN. Pastikan semua NISN unik.');
    } else {
         set_flash_and_redirect('danger', 'Gagal menyimpan ke database: ' . $e->getMessage());
    }
}
?>