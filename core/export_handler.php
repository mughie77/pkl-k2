<?php
require_once __DIR__ . '/../config/config.php';

// Pustaka PhpSpreadsheet harus dimuat melalui autoloader Composer
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    die("Composer autoloader not found. Please run 'composer install'.");
}
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

// Security check: only logged-in users can export
if (!isset($_SESSION['user_id'])) {
    die('Akses ditolak.');
}

$export_type = $_GET['type'] ?? '';
$user_role = $_SESSION['user_role'];
$user_id = $_SESSION['user_id'];
$academic_year_id = $_SESSION['selected_academic_year_id'] ?? null;

if (!$academic_year_id) {
    die('Tahun ajaran belum dipilih.');
}

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
        // ... (logika rekap absen tetap sama)
        break;

    case 'rekap_nilai': // Untuk Admin & Waka Humas
        if (!in_array($user_role, ['admin', 'waka_humas'])) {
            die('Akses ditolak untuk peran ini.');
        }
        set_headers('Rekap_Skor_Siswa_Global.xlsx');
        $sheet->setTitle('Rekap Skor Global');

        $query = "SELECT s.name as student_name, pk.program_name, i.name as instructor_name, a.score_1, a.score_2, a.score_3, a.score_4, ((a.score_1 + a.score_2 + a.score_3 + a.score_4) / 4) as average_score, a.notes FROM internship_assessments a JOIN students s ON a.student_id = s.id JOIN internship_mappings m ON s.id = m.student_id JOIN kelas k ON s.kelas_id = k.id JOIN konsentrasi_keahlian kk ON k.konsentrasi_id = kk.id JOIN program_keahlian pk ON kk.program_id = pk.id JOIN instructors i ON a.instructor_id = i.id WHERE m.academic_year_id = :academic_year_id ORDER BY s.name ASC";
        $stmt = $pdo->prepare($query);
        $stmt->execute([':academic_year_id' => $academic_year_id]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $header = ['Nama Siswa', 'Program Keahlian', 'Dinilai oleh', 'Skor 1: Memahami alur bisnis', 'Skor 2: Menerapkan soft skill', 'Skor 3: Menerapkan norma, SOP, K3LH', 'Skor 4: Menerapkan kompetensi teknis', 'Rata-rata', 'Catatan'];
        $sheet->fromArray($header, NULL, 'A1');
        $sheet->fromArray($data, NULL, 'A2');

        break;

    case 'rekap_nilai_guru': // Khusus untuk Guru
        if ($user_role !== 'teacher') {
            die('Akses ditolak untuk peran ini.');
        }
        set_headers('Rekap_Skor_Siswa_Bimbingan.xlsx');
        $sheet->setTitle('Rekap Skor Bimbingan');

        $query = "SELECT s.name as student_name, pk.program_name, i.name as instructor_name, a.score_1, a.score_2, a.score_3, a.score_4, ((a.score_1 + a.score_2 + a.score_3 + a.score_4) / 4) as average_score, a.notes FROM internship_assessments a JOIN students s ON a.student_id = s.id JOIN internship_mappings m ON s.id = m.student_id JOIN kelas k ON s.kelas_id = k.id JOIN konsentrasi_keahlian kk ON k.konsentrasi_id = kk.id JOIN program_keahlian pk ON kk.program_id = pk.id JOIN instructors i ON a.instructor_id = i.id WHERE m.teacher_id = :teacher_id AND m.academic_year_id = :academic_year_id ORDER BY s.name ASC";
        $stmt = $pdo->prepare($query);
        $stmt->execute([':teacher_id' => $user_id, ':academic_year_id' => $academic_year_id]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $header = ['Nama Siswa', 'Program Keahlian', 'Dinilai oleh', 'Skor 1: Memahami alur bisnis', 'Skor 2: Menerapkan soft skill', 'Skor 3: Menerapkan norma, SOP, K3LH', 'Skor 4: Menerapkan kompetensi teknis', 'Rata-rata', 'Catatan'];
        $sheet->fromArray($header, NULL, 'A1');
        $sheet->fromArray($data, NULL, 'A2');

        break;

    case 'rekap_masalah':
        // ... (logika rekap masalah tetap sama)
        break;

    default:
        die('Jenis ekspor tidak valid.');
}

// Auto-size columns for better readability
foreach (range('A', $sheet->getHighestDataColumn()) as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}
// Style header
$sheet->getStyle('A1:' . $sheet->getHighestDataColumn() . '1')->getFont()->setBold(true);
$sheet->getStyle('A1:' . $sheet->getHighestDataColumn() . '1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);


// Write spreadsheet to output
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>