<?php
require_once __DIR__ . '/../config/config.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Security check: only logged-in users can export
if (!isset($_SESSION['user_id'])) {
    die('Akses ditolak.');
}

$export_type = $_GET['type'] ?? '';
$user_role = $_SESSION['user_role'];
$user_id = $_SESSION['user_id'];

function set_headers($filename) {
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Main logic based on export type
switch ($export_type) {
    case 'rekap_absen':
        set_headers('Rekap_Absensi_Siswa.xlsx');
        $sheet->setTitle('Rekap Absensi');

        // Copy query and filter logic from global_recap.php
        $filter_program = $_GET['program_id'] ?? 'all';
        $filter_teacher = $_GET['teacher_id'] ?? 'all';
        $filter_company = $_GET['company_id'] ?? 'all';
        $filter_student = $_GET['student_id'] ?? 'all';
        $filter_start_date = $_GET['start_date'] ?? '';
        $filter_end_date = $_GET['end_date'] ?? '';

        $query = "SELECT j.journal_date, s.name as student_name, pk.program_name, c.name as company_name, t.name as teacher_name, j.check_in_time, j.check_out_time, j.status FROM internship_journals j JOIN students s ON j.student_id = s.id JOIN kelas k ON s.kelas_id = k.id JOIN konsentrasi_keahlian kk ON k.konsentrasi_id = kk.id JOIN program_keahlian pk ON kk.program_id = pk.id LEFT JOIN internship_mappings m ON j.student_id = m.student_id LEFT JOIN instructors i ON m.instructor_id = i.id LEFT JOIN companies c ON i.company_id = c.id LEFT JOIN teachers t ON m.teacher_id = t.id";
        $params = [];
        $where_clauses = [];

        if ($user_role === 'instructor') { $where_clauses[] = "m.instructor_id = :user_id"; $params[':user_id'] = $user_id; }
        if ($user_role === 'teacher') { $where_clauses[] = "m.teacher_id = :user_id"; $params[':user_id'] = $user_id; }
        if ($filter_program !== 'all') { $where_clauses[] = "pk.id = :program_id"; $params[':program_id'] = $filter_program; }
        if ($filter_teacher !== 'all') { $where_clauses[] = "m.teacher_id = :teacher_id"; $params[':teacher_id'] = $filter_teacher; }
        if ($filter_company !== 'all') { $where_clauses[] = "i.company_id = :company_id"; $params[':company_id'] = $filter_company; }
        if ($filter_student !== 'all') { $where_clauses[] = "j.student_id = :student_id"; $params[':student_id'] = $filter_student; }
        if (!empty($filter_start_date)) { $where_clauses[] = "j.journal_date >= :start_date"; $params[':start_date'] = $filter_start_date; }
        if (!empty($filter_end_date)) { $where_clauses[] = "j.journal_date <= :end_date"; $params[':end_date'] = $filter_end_date; }

        if (!empty($where_clauses)) { $query .= " WHERE " . implode(" AND ", $where_clauses); }
        $query .= " ORDER BY j.journal_date DESC, s.name ASC";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $header = ['Tanggal', 'Nama Siswa', 'Program Keahlian', 'DUDIKA', 'Guru Pembimbing', 'Check-in', 'Check-out', 'Status Jurnal'];
        $sheet->fromArray($header, NULL, 'A1');
        $sheet->fromArray($data, NULL, 'A2');

        break;

    case 'rekap_nilai':
        set_headers('Rekap_Skor_Siswa.xlsx');
        $sheet->setTitle('Rekap Skor');

        $query = "SELECT s.name as student_name, pk.program_name, i.name as instructor_name, a.score_1, a.score_2, a.score_3, a.score_4, ((a.score_1 + a.score_2 + a.score_3 + a.score_4) / 4) as average_score, a.notes FROM internship_assessments a JOIN students s ON a.student_id = s.id JOIN kelas k ON s.kelas_id = k.id JOIN konsentrasi_keahlian kk ON k.konsentrasi_id = kk.id JOIN program_keahlian pk ON kk.program_id = pk.id JOIN instructors i ON a.instructor_id = i.id";
        $params = [];

        if ($user_role === 'teacher') {
            $query .= " JOIN internship_mappings m ON a.student_id = m.student_id WHERE m.teacher_id = :user_id";
            $params[':user_id'] = $user_id;
        }

        $query .= " ORDER BY s.name ASC";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $header = ['Nama Siswa', 'Program Keahlian', 'Dinilai oleh', 'Skor 1', 'Skor 2', 'Skor 3', 'Skor 4', 'Rata-rata', 'Catatan'];
        $sheet->fromArray($header, NULL, 'A1');
        $sheet->fromArray($data, NULL, 'A2');

        break;

    case 'rekap_masalah':
        set_headers('Rekap_Masalah_Siswa.xlsx');
        $sheet->setTitle('Rekap Masalah');

        $filter_program = $_GET['program_id'] ?? 'all';
        $filter_teacher = $_GET['teacher_id'] ?? 'all';
        $filter_company = $_GET['company_id'] ?? 'all';
        $filter_start_date = $_GET['start_date'] ?? '';
        $filter_end_date = $_GET['end_date'] ?? '';

        $query = "SELECT n.created_at, s.name as student_name, pk.program_name, c.name as company_name, n.note, n.creator_role, t.name as teacher_name, i.name as instructor_name FROM student_notes n JOIN students s ON n.student_id = s.id JOIN kelas k ON s.kelas_id = k.id JOIN konsentrasi_keahlian kk ON k.konsentrasi_id = kk.id JOIN program_keahlian pk ON kk.program_id = pk.id LEFT JOIN internship_mappings m ON n.student_id = m.student_id LEFT JOIN instructors i ON m.instructor_id = i.id LEFT JOIN companies c ON i.company_id = c.id LEFT JOIN teachers t ON m.teacher_id = t.id";
        $params = [];
        $where_clauses = [];

        if ($filter_program !== 'all') { $where_clauses[] = "pk.id = :program_id"; $params[':program_id'] = $filter_program; }
        if ($filter_teacher !== 'all') { $where_clauses[] = "m.teacher_id = :teacher_id"; $params[':teacher_id'] = $filter_teacher; }
        if ($filter_company !== 'all') { $where_clauses[] = "i.company_id = :company_id"; $params[':company_id'] = $filter_company; }
        if (!empty($filter_start_date)) { $where_clauses[] = "DATE(n.created_at) >= :start_date"; $params[':start_date'] = $filter_start_date; }
        if (!empty($filter_end_date)) { $where_clauses[] = "DATE(n.created_at) <= :end_date"; $params[':end_date'] = $filter_end_date; }

        if (!empty($where_clauses)) { $query .= " WHERE " . implode(" AND ", $where_clauses); }
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

        break;

    default:
        die('Jenis ekspor tidak valid.');
}

// Write spreadsheet to output
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>