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
    header('Content-Type: text/csv; charset=utf-8');
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
        // Data Rekap Absensi
        $stmt = $pdo->prepare("
            SELECT s.name, s.department, COUNT(j.id) as total_hadir
            FROM students s
            LEFT JOIN internship_journals j ON s.id = j.student_id AND DATE_FORMAT(j.journal_date, '%Y-%m') = :month
            GROUP BY s.id
            ORDER BY s.name ASC
        ");
        $stmt->execute([':month' => $selected_month]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $header = ['Nama Siswa', 'Jurusan', 'Total Kehadiran (Hari)'];
        $filename = 'rekap_absensi_' . str_replace('-', '_', $selected_month) . '.csv';

        output_csv($filename, $header, $data);

    } elseif ($rekap_type === 'assessment') {
        // Data Rekap Nilai
        $stmt = $pdo->prepare("
            SELECT
                s.name as student_name, s.department,
                a.discipline_score, a.skill_score, a.teamwork_score, a.diligence_score,
                (a.discipline_score + a.skill_score + a.teamwork_score + a.diligence_score) / 4 as average_score
            FROM students s
            LEFT JOIN internship_assessments a ON s.id = a.student_id
            ORDER BY s.name ASC
        ");
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $header = ['Nama Siswa', 'Jurusan', 'Disiplin', 'Skill', 'Kerja Tim', 'Kerajinan', 'Rata-rata'];
        $data_to_export = [];
        foreach ($results as $row) {
            $data_to_export[] = [
                $row['student_name'],
                $row['department'],
                $row['discipline_score'] ?? 'N/A',
                $row['skill_score'] ?? 'N/A',
                $row['teamwork_score'] ?? 'N/A',
                $row['diligence_score'] ?? 'N/A',
                isset($row['average_score']) ? number_format($row['average_score'], 2) : 'N/A'
            ];
        }

        $filename = 'rekap_nilai_akhir.csv';
        output_csv($filename, $header, $data_to_export);
    } else {
        die('Jenis rekap tidak valid.');
    }

} catch (PDOException $e) {
    die("Error exporting data: " . $e->getMessage());
}
?>