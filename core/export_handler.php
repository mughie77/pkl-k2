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

// Fallback jika report_type tidak dikenal
die("Jenis laporan tidak valid.");
?>
