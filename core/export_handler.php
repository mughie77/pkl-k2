<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Keamanan: Pastikan hanya peran yang berwenang yang dapat mengakses
$allowed_roles = ['admin', 'teacher', 'waka_humas', 'instructor'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    die("Akses ditolak.");
}

$report_type = $_GET['report_type'] ?? '';

if ($report_type === 'journal_recap') {
    // --- Logika Ekspor Rekap Jurnal ---

    $teacher_id = $_SESSION['user_role'] === 'teacher' ? $_SESSION['user_id'] : null;

    // Ambil filter dari URL
    $filter_student_id = $_GET['student_id'] ?? 'all';
    $filter_start_date = $_GET['start_date'] ?? '';
    $filter_end_date = $_GET['end_date'] ?? '';

    try {
        // Rekonstruksi query berdasarkan filter
        $query = "
            SELECT
                j.journal_date,
                s.name as student_name,
                s.work_start_time,
                s.work_end_time,
                j.check_in_time,
                j.check_out_time,
                j.status,
                j.activities
            FROM internship_journals j
            JOIN students s ON j.student_id = s.id
            JOIN internship_mappings m ON j.student_id = m.student_id
        ";

        // Sesuaikan WHERE clause berdasarkan peran
        if ($_SESSION['user_role'] === 'teacher') {
            $query .= " WHERE m.teacher_id = :user_id";
            $params = [':user_id' => $teacher_id];
        } else {
            // Untuk admin atau waka humas, mungkin perlu logika filter yang berbeda
            // Untuk saat ini, kita asumsikan mereka bisa melihat semua jika tidak ada filter
            $query .= " WHERE 1=1";
            $params = [];
        }


        if ($filter_student_id !== 'all' && !empty($filter_student_id)) {
            $query .= " AND j.student_id = :student_id";
            $params[':student_id'] = $filter_student_id;
        }
        if (!empty($filter_start_date)) {
            $query .= " AND j.journal_date >= :start_date";
            $params[':start_date'] = $filter_start_date;
        }
        if (!empty($filter_end_date)) {
            $query .= " AND j.journal_date <= :end_date";
            $params[':end_date'] = $filter_end_date;
        }

        $query .= " ORDER BY j.journal_date DESC, s.name ASC";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Buat file Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Jurnal Siswa');

        // Header
        $headers = ['Tanggal', 'Nama Siswa', 'Jam Kerja', 'Check-in', 'Check-out', 'Status', 'Kegiatan'];
        $sheet->fromArray($headers, NULL, 'A1');

        // Styling Header
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4285F4']]
        ];
        $sheet->getStyle('A1:G1')->applyFromArray($headerStyle);

        // Isi Data
        $rowNum = 2;
        foreach ($data as $row) {
            $sheet->setCellValue('A' . $rowNum, date('d M Y', strtotime($row['journal_date'])));
            $sheet->setCellValue('B' . $rowNum, $row['student_name']);
            $sheet->setCellValue('C' . $rowNum, date('H:i', strtotime($row['work_start_time'])) . ' - ' . date('H:i', strtotime($row['work_end_time'])));
            $sheet->setCellValue('D' . $rowNum, $row['check_in_time'] ? date('H:i', strtotime($row['check_in_time'])) : 'N/A');
            $sheet->setCellValue('E' . $rowNum, $row['check_out_time'] ? date('H:i', strtotime($row['check_out_time'])) : 'N/A');
            $sheet->setCellValue('F' . $rowNum, $row['status']);
            $sheet->setCellValue('G' . $rowNum, $row['activities']);
            $rowNum++;
        }

        // Auto-size columns
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Output ke browser
        $filename = 'rekap_jurnal_siswa_' . date('Ymd') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        ob_end_clean(); // Hapus output buffer sebelum mengirim file
        $writer->save('php://output');
        exit;

    } catch (PDOException $e) {
        die("Error saat mengambil data untuk ekspor: " . $e->getMessage());
    }
}

} elseif ($report_type === 'student_problems') {
    // --- Logika Ekspor Masalah Siswa ---

    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['user_role'];
    $student_id = $_GET['student_id'] ?? null;

    if (!$student_id) {
        die("ID Siswa tidak ditemukan.");
    }

    try {
        // Ambil nama siswa untuk nama file
        $stmt_student = $pdo->prepare("SELECT name FROM students WHERE id = :id");
        $stmt_student->execute([':id' => $student_id]);
        $student = $stmt_student->fetch(PDO::FETCH_ASSOC);
        $student_name = $student ? $student['name'] : 'Unknown';

        // Rekonstruksi query dari student_problems.php
        $query = "
            SELECT s.name as student_name, n.note, n.created_at, n.creator_role,
                   CASE
                       WHEN n.creator_role = 'teacher' THEN t.name
                       WHEN n.creator_role = 'instructor' THEN i.name
                   END as creator_name
            FROM student_notes n
            JOIN students s ON n.student_id = s.id
            LEFT JOIN teachers t ON n.creator_id = t.id AND n.creator_role = 'teacher'
            LEFT JOIN instructors i ON n.creator_id = i.id AND n.creator_role = 'instructor'
            WHERE n.student_id = :student_id
        ";

        $params = [':student_id' => $student_id];

        // Terapkan aturan visibilitas yang sama
        if ($user_role === 'instructor') {
            $query .= " AND (n.creator_role = 'instructor' AND n.creator_id = :user_id)";
            $params[':user_id'] = $user_id;
        }

        $query .= " ORDER BY n.created_at DESC";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Buat file Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan Masalah Siswa');

        // Header
        $headers = ['Tanggal', 'Siswa', 'Pelapor', 'Peran', 'Catatan Masalah'];
        $sheet->fromArray($headers, NULL, 'A1');
        $sheet->getStyle('A1:E1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9534F']]
        ]);

        // Isi Data
        $rowNum = 2;
        foreach ($data as $row) {
            $sheet->setCellValue('A' . $rowNum, date('d M Y, H:i', strtotime($row['created_at'])));
            $sheet->setCellValue('B' . $rowNum, $row['student_name']);
            $sheet->setCellValue('C' . $rowNum, $row['creator_name']);
            $sheet->setCellValue('D' . $rowNum, ucwords($row['creator_role']));
            $sheet->setCellValue('E' . $rowNum, $row['note']);
            $rowNum++;
        }

        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Output ke browser
        $safe_student_name = preg_replace('/[^A-Za-z0-9\-]/', '_', $student_name);
        $filename = 'laporan_masalah_' . $safe_student_name . '_' . date('Ymd') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        ob_end_clean();
        $writer->save('php://output');
        exit;

    } catch (PDOException $e) {
        die("Error saat mengambil data untuk ekspor: " . $e->getMessage());
    }
}


} elseif ($report_type === 'teacher_assessments') {
    // --- Logika Ekspor Nilai Siswa (untuk Guru) ---

    if ($_SESSION['user_role'] !== 'teacher') {
        die("Akses ditolak.");
    }
    $teacher_id = $_SESSION['user_id'];

    try {
        // Query untuk mengambil data penilaian
        $stmt = $pdo->prepare("
            SELECT
                s.name as student_name,
                pk.program_name,
                a.score_1, a.score_2, a.score_3, a.score_4,
                (a.score_1 + a.score_2 + a.score_3 + a.score_4) / 4 as average_score,
                i.name as instructor_name,
                a.notes
            FROM internship_assessments a
            JOIN students s ON a.student_id = s.id
            JOIN kelas k ON s.kelas_id = k.id
            JOIN konsentrasi_keahlian kk ON k.konsentrasi_id = kk.id
            JOIN program_keahlian pk ON kk.program_id = pk.id
            JOIN instructors i ON a.instructor_id = i.id
            JOIN internship_mappings m ON a.student_id = m.student_id
            WHERE m.teacher_id = :teacher_id
            ORDER BY s.name ASC
        ");
        $stmt->execute([':teacher_id' => $teacher_id]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Buat file Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Nilai Siswa');

        // Header
        $headers = [
            'Nama Siswa', 'Program Keahlian', 'Memahami Alur Bisnis',
            'Menerapkan Soft Skill', 'Norma, SOP, K3LH', 'Kompetensi Teknis',
            'Rata-rata', 'Dinilai oleh', 'Catatan Instruktur'
        ];
        $sheet->fromArray($headers, NULL, 'A1');
        $sheet->getStyle('A1:I1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '198754']]
        ]);

        // Isi Data
        $rowNum = 2;
        foreach ($data as $row) {
            $sheet->fromArray([
                $row['student_name'],
                $row['program_name'],
                $row['score_1'],
                $row['score_2'],
                $row['score_3'],
                $row['score_4'],
                number_format($row['average_score'], 2),
                $row['instructor_name'],
                $row['notes']
            ], NULL, 'A' . $rowNum);
            $rowNum++;
        }

        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Output ke browser
        $filename = 'rekap_nilai_siswa_' . date('Ymd') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        ob_end_clean();
        $writer->save('php://output');
        exit;

    } catch (PDOException $e) {
        die("Error saat mengambil data untuk ekspor: " . $e->getMessage());
    }
}

// Fallback jika report_type tidak dikenal
die("Jenis laporan tidak valid.");
?>
