<?php
// Start output buffering to catch any stray output from warnings or errors
ob_start();

require_once __DIR__ . '/../config/config.php';

// Pustaka PhpSpreadsheet harus dimuat melalui autoloader Composer
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    ob_end_clean(); // Clean buffer before dying
    die("Composer autoloader not found. Please run 'composer install'.");
}
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

// Security check: only logged-in users can export
if (!isset($_SESSION['user_id'])) {
    ob_end_clean();
    die('Akses ditolak.');
}

$export_type = $_GET['type'] ?? '';
$user_role = $_SESSION['user_role'];
$user_id = $_SESSION['user_id'];
$academic_year_id = $_SESSION['selected_academic_year_id'] ?? null;

if (!$academic_year_id) {
    ob_end_clean();
    die('Tahun ajaran belum dipilih.');
}

function set_headers($filename) {
    if (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    header('Pragma: public');
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

try {
    switch ($export_type) {
        case 'rekap_absen':
            $sheet->setTitle('Rekap Absensi');
            $filter_program = $_GET['program_id'] ?? '';
            $filter_teacher = $_GET['teacher_id'] ?? '';
            $filter_company = $_GET['company_id'] ?? '';
            $filter_student = $_GET['student_id'] ?? '';
            $filter_start_date = $_GET['start_date'] ?? '';
            $filter_end_date = $_GET['end_date'] ?? '';

            $query = "SELECT j.journal_date, s.student_name, pk.program_name, c.company_name, t.teacher_name, j.check_in_time, j.check_out_time, j.status FROM internship_journals j JOIN students s ON j.student_id = s.id JOIN internship_mappings m ON s.id = m.student_id JOIN kelas kls ON s.kelas_id = kls.id JOIN konsentrasi_keahlian kk ON kls.konsentrasi_id = kk.id JOIN program_keahlian pk ON kk.program_id = pk.id LEFT JOIN companies c ON m.company_id = c.company_id LEFT JOIN teachers t ON m.teacher_id = t.id";

            $where_clauses = ["m.academic_year_id = :academic_year_id"];
            $params = [':academic_year_id' => $academic_year_id];

            if ($user_role === 'instructor') { $where_clauses[] = "m.instructor_id = :user_id"; $params[':user_id'] = $user_id; }
            if ($user_role === 'teacher') { $where_clauses[] = "m.teacher_id = :user_id"; $params[':user_id'] = $user_id; }
            if (!empty($filter_program)) { $where_clauses[] = "pk.id = :program_id"; $params[':program_id'] = $filter_program; }
            if (!empty($filter_teacher)) { $where_clauses[] = "m.teacher_id = :teacher_id"; $params[':teacher_id'] = $filter_teacher; }
            if (!empty($filter_company)) { $where_clauses[] = "m.company_id = :company_id"; $params[':company_id'] = $filter_company; }
            if (!empty($filter_student)) { $where_clauses[] = "j.student_id = :student_id"; $params[':student_id'] = $filter_student; }
            if (!empty($filter_start_date)) { $where_clauses[] = "j.journal_date >= :start_date"; $params[':start_date'] = $filter_start_date; }
            if (!empty($filter_end_date)) { $where_clauses[] = "j.journal_date <= :end_date"; $params[':end_date'] = $filter_end_date; }

            $query .= " WHERE " . implode(" AND ", $where_clauses);
            $query .= " ORDER BY j.journal_date DESC, s.student_name ASC";

            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $data_to_write = [];
            foreach($results as $row) {
                $data_to_write[] = [
                    $row['journal_date'], $row['student_name'], $row['program_name'], $row['company_name'],
                    $row['teacher_name'], $row['check_in_time'], $row['check_out_time'], $row['status']
                ];
            }

            $header = ['Tanggal', 'Nama Siswa', 'Program Keahlian', 'DUDIKA', 'Guru Pembimbing', 'Check-in', 'Check-out', 'Status Jurnal'];
            $sheet->fromArray($header, NULL, 'A1');
            $sheet->fromArray($data_to_write, NULL, 'A2');

            set_headers('Rekap_Absensi_Siswa.xlsx');
            break;

        case 'rekap_nilai':
            $sheet->setTitle('Rekap Skor Global');
            if (!in_array($user_role, ['admin', 'waka_humas'])) { die('Akses ditolak.'); }

            $query = "SELECT s.student_name, pk.program_name, i.instructor_name, a.score_1, a.score_2, a.score_3, a.score_4, ((a.score_1 + a.score_2 + a.score_3 + a.score_4) / 4) as average_score, a.notes FROM internship_assessments a JOIN students s ON a.student_id = s.id JOIN internship_mappings m ON s.id = m.student_id JOIN kelas kls ON s.kelas_id = kls.id JOIN konsentrasi_keahlian kk ON kls.konsentrasi_id = kk.id JOIN program_keahlian pk ON kk.program_id = pk.id JOIN instructors i ON a.instructor_id = i.id WHERE m.academic_year_id = :academic_year_id ORDER BY s.student_name ASC";
            $stmt = $pdo->prepare($query);
            $stmt->execute([':academic_year_id' => $academic_year_id]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $header = ['Nama Siswa', 'Program Keahlian', 'Dinilai oleh', 'Skor 1: Memahami alur bisnis', 'Skor 2: Menerapkan soft skill', 'Skor 3: Menerapkan norma, SOP, K3LH', 'Skor 4: Menerapkan kompetensi teknis', 'Rata-rata', 'Catatan'];
            $sheet->fromArray($header, NULL, 'A1');
            $sheet->fromArray($data, NULL, 'A2');

            set_headers('Rekap_Skor_Siswa_Global.xlsx');
            break;

        case 'rekap_nilai_guru':
            $sheet->setTitle('Rekap Skor Bimbingan');
            if ($user_role !== 'teacher') { die('Akses ditolak.'); }

            $query = "SELECT s.student_name, pk.program_name, i.instructor_name, a.score_1, a.score_2, a.score_3, a.score_4, ((a.score_1 + a.score_2 + a.score_3 + a.score_4) / 4) as average_score, a.notes FROM internship_assessments a JOIN students s ON a.student_id = s.id JOIN internship_mappings m ON s.id = m.student_id JOIN kelas kls ON s.kelas_id = kls.id JOIN konsentrasi_keahlian kk ON kls.konsentrasi_id = kk.id JOIN program_keahlian pk ON kk.program_id = pk.id JOIN instructors i ON a.instructor_id = i.id WHERE m.teacher_id = :teacher_id AND m.academic_year_id = :academic_year_id ORDER BY s.student_name ASC";
            $stmt = $pdo->prepare($query);
            $stmt->execute([':teacher_id' => $user_id, ':academic_year_id' => $academic_year_id]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $header = ['Nama Siswa', 'Program Keahlian', 'Dinilai oleh', 'Skor 1', 'Skor 2', 'Skor 3', 'Skor 4', 'Rata-rata', 'Catatan'];
            $sheet->fromArray($header, NULL, 'A1');
            $sheet->fromArray($data, NULL, 'A2');

            set_headers('Rekap_Skor_Siswa_Bimbingan.xlsx');
            break;

        case 'rekap_masalah':
            $sheet->setTitle('Rekap Masalah');
            $filter_program = $_GET['program_id'] ?? '';
            $filter_teacher = $_GET['teacher_id'] ?? '';
            $filter_company = $_GET['company_id'] ?? '';
            $filter_start_date = $_GET['start_date'] ?? '';
            $filter_end_date = $_GET['end_date'] ?? '';

            $query = "SELECT n.created_at, s.student_name, pk.program_name, c.company_name, n.note, n.creator_role, t.teacher_name, i.instructor_name FROM student_notes n JOIN students s ON n.student_id = s.id JOIN internship_mappings m ON s.id = m.student_id JOIN kelas kls ON s.kelas_id = kls.id JOIN konsentrasi_keahlian kk ON kls.konsentrasi_id = kk.id JOIN program_keahlian pk ON kk.program_id = pk.id LEFT JOIN companies c ON m.company_id = c.company_id LEFT JOIN teachers t ON m.teacher_id = t.id LEFT JOIN instructors i ON m.instructor_id = i.id";

            $where_clauses = ["m.academic_year_id = :academic_year_id"];
            $params = [':academic_year_id' => $academic_year_id];

            if (!empty($filter_program)) { $where_clauses[] = "pk.id = :program_id"; $params[':program_id'] = $filter_program; }
            if (!empty($filter_teacher)) { $where_clauses[] = "m.teacher_id = :teacher_id"; $params[':teacher_id'] = $filter_teacher; }
            if (!empty($filter_company)) { $where_clauses[] = "m.company_id = :company_id"; $params[':company_id'] = $filter_company; }
            if (!empty($filter_start_date)) { $where_clauses[] = "DATE(n.created_at) >= :start_date"; $params[':start_date'] = $filter_start_date; }
            if (!empty($filter_end_date)) { $where_clauses[] = "DATE(n.created_at) <= :end_date"; $params[':end_date'] = $filter_end_date; }

            $query .= " WHERE " . implode(" AND ", $where_clauses);
            $query .= " ORDER BY n.created_at DESC";

            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $header = ['Tanggal', 'Nama Siswa', 'Program Keahlian', 'DUDIKA', 'Catatan Masalah', 'Pelapor', 'Nama Pelapor'];
            $sheet->fromArray($header, NULL, 'A1');

            $row_num = 2;
            foreach ($results as $row) {
                $creator_name = ($row['creator_role'] == 'teacher') ? $row['teacher_name'] : $row['instructor_name'];
                $sheet->setCellValue('A' . $row_num, $row['created_at']);
                $sheet->setCellValue('B' . $row_num, $row['student_name']);
                $sheet->setCellValue('C' . $row_num, $row['program_name']);
                $sheet->setCellValue('D' . $row_num, $row['company_name']);
                $sheet->setCellValue('E' . $row_num, $row['note']);
                $sheet->setCellValue('F' . $row_num, ucwords($row['creator_role']));
                $sheet->setCellValue('G' . $row_num, $creator_name);
                $row_num++;
            }

            set_headers('Rekap_Masalah_Siswa.xlsx');
            break;

        default:
            die('Jenis ekspor tidak valid.');
    }

    // Auto-size columns for better readability
    foreach (range('A', $sheet->getHighestDataColumn()) as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    $sheet->getStyle('A1:' . $sheet->getHighestDataColumn() . '1')->getFont()->setBold(true);
    $sheet->getStyle('A1:' . $sheet->getHighestDataColumn() . '1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;

} catch (PDOException $e) {
    if (ob_get_level()) { ob_end_clean(); }
    error_log("Excel Export PDO Error: " . $e->getMessage());
    die("Terjadi kesalahan database saat membuat file Excel: " . $e->getMessage());
} catch (Exception $e) {
    if (ob_get_level()) { ob_end_clean(); }
    error_log("General Export Error: " . $e->getMessage());
    die("Terjadi kesalahan umum saat membuat file Excel. Silakan coba lagi.");
}
?>