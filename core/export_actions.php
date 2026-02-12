<?php
require_once __DIR__ . '/../config/config.php';

// Keamanan: Pastikan hanya admin yang bisa mengakses
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    die('Akses ditolak.');
}

$rekap_type = $_GET['rekap_type'] ?? 'attendance';
$selected_month = $_GET['month'] ?? date('Y-m');

function output_csv($filename, $header, $data) {
    header('Content-Type: text/csv; charset=utf-f');
    header('Content-Disposition: attachment; filename=' . $filename);

    $output = fopen('php://output', 'w');
    fputcsv($output, $header);

    foreach ($data as $row) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit();
}

try {
    if ($rekap_type === 'attendance') {
        // Data Rekap Absensi (menggunakan skema LAMA/ASLI)
        $stmt = $pdo->prepare("
            SELECT s.name, d.department_name, COUNT(j.id) as total_hadir
            FROM students s
            JOIN departments d ON s.department_id = d.id
            LEFT JOIN internship_journals j ON s.id = j.student_id AND DATE_FORMAT(j.journal_date, '%Y-%m') = :month
            GROUP BY s.id, s.name, d.department_name
            ORDER BY s.name ASC
        ");
        $stmt->execute([':month' => $selected_month]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $data = [];
        foreach($results as $row) {
            $data[] = [
                $row['name'],
                $row['department_name'],
                $row['total_hadir']
            ];
        }

        $header = ['Nama Siswa', 'Jurusan', 'Total Kehadiran (Hari)'];
        $filename = 'rekap_absensi_' . str_replace('-', '_', $selected_month) . '.csv';

        output_csv($filename, $header, $data);

    } elseif ($rekap_type === 'problems') {
        // Data Rekap Masalah (menggunakan skema LAMA/ASLI)
        $stmt = $pdo->prepare("
            SELECT
                s.name as student_name,
                d.department_name,
                n.note,
                n.creator_role,
                CASE
                    WHEN n.creator_role = 'teacher' THEN t.name
                    WHEN n.creator_role = 'instructor' THEN i.name
                END as creator_name,
                n.created_at
            FROM student_notes n
            JOIN students s ON n.student_id = s.id
            JOIN departments d ON s.department_id = d.id
            LEFT JOIN internship_mappings m ON n.student_id = m.student_id
            LEFT JOIN teachers t ON m.teacher_id = t.id
            LEFT JOIN instructors i ON m.instructor_id = i.id
            ORDER BY n.created_at DESC
        ");
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $header = ['Tanggal', 'Nama Siswa', 'Jurusan', 'Catatan Masalah', 'Pelapor', 'Nama Pelapor'];
        $data_to_export = [];
        foreach ($results as $row) {
            $data_to_export[] = [
                $row['created_at'],
                $row['student_name'],
                $row['department_name'],
                $row['note'],
                ucwords($row['creator_role']),
                $row['creator_name']
            ];
        }

        $filename = 'rekap_masalah_siswa.csv';
        output_csv($filename, $header, $data_to_export);

    } else {
        die('Jenis rekap tidak valid.');
    }

} catch (PDOException $e) {
    die("Error exporting data: " . $e->getMessage());
}
?>