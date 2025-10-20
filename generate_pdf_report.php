<?php
session_start();
require_once 'config/config.php';

// Pustaka TCPDF harus dimuat melalui autoloader Composer
if (!file_exists('vendor/autoload.php')) {
    die("Pustaka TCPDF belum diinstal. Silakan jalankan 'composer install' dari terminal.");
}
require_once 'vendor/autoload.php';

// Validasi Sesi dan Peran
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['waka_humas', 'teacher'])) {
    // Jangan redirect, cukup hentikan eksekusi jika diakses secara tidak sah
    http_response_code(403);
    die("Akses ditolak. Anda harus login sebagai Waka Humas atau Guru.");
}

// Validasi Parameter GET
if (!isset($_GET['student_id'])) {
    die("ID Siswa tidak ditemukan.");
}

// Validasi Sesi Tahun Ajaran (INI KUNCINYA)
if (!isset($_SESSION['selected_academic_year_id'])) {
    die("Error: Tahun ajaran belum dipilih. Silakan kembali ke dasbor dan pilih tahun ajaran terlebih dahulu.");
}

$student_id = $_GET['student_id'];
$academic_year_id = $_SESSION['selected_academic_year_id'];

// 1. Fetch all required data
// =============================

// School Info
$stmt_school = $pdo->prepare("SELECT name FROM school_settings LIMIT 1");
$stmt_school->execute();
$school = $stmt_school->fetch(PDO::FETCH_ASSOC);
$school_name = $school ? strtoupper($school['name']) : 'NAMA SEKOLAH';

// Academic Year
$stmt_year = $pdo->prepare("SELECT year_name FROM academic_years WHERE id = ?");
$stmt_year->execute([$academic_year_id]);
$academic_year = $stmt_year->fetch(PDO::FETCH_ASSOC);
$year_name = $academic_year ? $academic_year['year_name'] : 'TAHUN AJARAN';

// Student, Mapping, and Related Info
$sql_student = "SELECT s.name AS student_name, s.nisn, k.name AS kelas_name, kk.name AS konsentrasi_name, pk.name AS program_name, c.name AS company_name, m.start_date, m.end_date, i.name AS instructor_name, t.name AS teacher_name, i.nip AS instructor_nip, t.nip AS teacher_nip
                FROM students s
                LEFT JOIN internship_mappings m ON s.id = m.student_id
                LEFT JOIN companies c ON m.company_id = c.id
                LEFT JOIN instructors i ON m.instructor_id = i.id
                LEFT JOIN teachers t ON m.teacher_id = t.id
                LEFT JOIN kelas k ON s.kelas_id = k.id
                LEFT JOIN konsentrasi_keahlian kk ON k.konsentrasi_id = kk.id
                LEFT JOIN program_keahlian pk ON kk.program_id = pk.id
                WHERE s.id = :student_id AND m.academic_year_id = :academic_year_id";
$stmt_student = $pdo->prepare($sql_student);
$stmt_student->execute(['student_id' => $student_id, 'academic_year_id' => $academic_year_id]);
$data = $stmt_student->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    die("Data siswa atau pemetaan untuk tahun ajaran ini tidak ditemukan.");
}

// Assessment Scores
$stmt_assessment = $pdo->prepare("
    SELECT a.score_1, a.score_2, a.score_3, a.score_4, a.notes
    FROM internship_assessments a
    JOIN internship_mappings m ON a.student_id = m.student_id
    WHERE a.student_id = :student_id AND m.academic_year_id = :academic_year_id
");
$stmt_assessment->execute([':student_id' => $student_id, ':academic_year_id' => $academic_year_id]);
$assessment = $stmt_assessment->fetch(PDO::FETCH_ASSOC);

if (!$assessment) {
    die("Data penilaian untuk siswa ini pada tahun ajaran ini tidak ditemukan.");
}

// Attendance Summary
$stmt_leave = $pdo->prepare("SELECT leave_type, COUNT(*) as total FROM leave_requests WHERE student_id = ? AND academic_year_id = ? GROUP BY leave_type");
$stmt_leave->execute([$student_id, $academic_year_id]);
$leave_data = $stmt_leave->fetchAll(PDO::FETCH_KEY_PAIR);

$sakit = $leave_data['Sakit'] ?? 0;
$izin = $leave_data['Izin'] ?? 0;
$tanpa_keterangan = $leave_data['Tanpa Keterangan'] ?? 0;

// Hardcoded assessment criteria based on the image
$tujuan_pembelajaran = [
    "Memahami alur bisnis dunia kerja tempat PKL dan wawasan wirausaha",
    "Menerapkan soft skill yang dibutuhkan dalam dunia kerja (tempat PKL)",
    "Menerapkan norma, SOP dan K3LH yang ada pada dunia kerja (tempat PKL)",
    "Menerapkan kompetensi teknis yang sudah dipelajari di sekolah dan/atau baru dipelajari pada dunia kerja (tempat PKL)"
];

$skor = $assessment ? [$assessment['score_1'], $assessment['score_2'], $assessment['score_3'], $assessment['score_4']] : [0, 0, 0, 0];
$company_name = $data['company_name'] ?? 'perusahaan';

$deskripsi = [
    "Kompeten dalam memahami alur bisnis dunia kerja di " . $company_name . " dan wawasan wirausaha",
    "Kompeten dalam menerapkan kompetensi",
    "Kompeten dalam menerapkan norma, SOP dan K3LH yang ada di " . $company_name,
    "Kompeten dalam menerapkan soft skills yang dibutuhkan di " . $company_name
];


// 2. Create PDF using TCPDF
// =========================

$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, 'A4', true, 'UTF-8', false);

// Set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Sistem Informasi PKL');
$pdf->SetTitle('Rapor PKL - ' . $data['student_name']);

// Remove header and footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set margins
$pdf->SetMargins(15, 15, 15);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, 15);

// Add a page
$pdf->AddPage();

// === PDF CONTENT ===

// Title
$pdf->SetFont('times', 'B', 12);
$pdf->Cell(0, 6, $school_name, 0, 1, 'C');
$pdf->Cell(0, 6, 'TAHUN PELAJARAN ' . $year_name, 0, 1, 'C');
$pdf->Ln(5);

// Student Info Section
$pdf->SetFont('times', '', 11);
$info_width_1 = 45;
$info_width_2 = 5;
$info_width_3 = 125;

$info_data = [
    'Nama Peserta Didik' => $data['student_name'],
    'NISN' => $data['nisn'],
    'Kelas' => $data['kelas_name'],
    'Program Keahlian' => $data['program_name'],
    'Konsentrasi Keahlian' => $data['konsentrasi_name'],
    'Tempat PKL' => $data['company_name'],
    'Tanggal PKL' => 'Mulai : ' . ($data['start_date'] ? date('d F Y', strtotime($data['start_date'])) : '-') . '    Selesai : ' . ($data['end_date'] ? date('d F Y', strtotime($data['end_date'])) : '-'),
    'Nama Instruktur' => $data['instructor_name'],
    'Nama Pembimbing' => $data['teacher_name']
];

foreach ($info_data as $label => $value) {
    $pdf->Cell($info_width_1, 6, $label, 0, 0);
    $pdf->Cell($info_width_2, 6, ':', 0, 0, 'C');
    $pdf->MultiCell($info_width_3, 6, $value, 0, 'L', false, 1);
}
$pdf->Ln(5);

// Assessment Table Header
$pdf->SetFont('times', 'B', 11);
$pdf->SetFillColor(230, 230, 230);
$pdf->Cell(90, 8, 'TUJUAN PEMBELAJARAN', 1, 0, 'C', 1);
$pdf->Cell(20, 8, 'SKOR', 1, 0, 'C', 1);
$pdf->Cell(70, 8, 'DESKRIPSI', 1, 1, 'C', 1);

// Assessment Table Body
$pdf->SetFont('times', '', 10);
$col_widths = [90, 20, 70];
for ($i = 0; $i < count($tujuan_pembelajaran); $i++) {
    $h_tujuan = $pdf->getStringHeight($col_widths[0], $tujuan_pembelajaran[$i]);
    $h_deskripsi = $pdf->getStringHeight($col_widths[2], $deskripsi[$i]);
    $row_height = max($h_tujuan, $h_deskripsi, 8); // Ensure a minimum height

    $pdf->MultiCell($col_widths[0], $row_height, $tujuan_pembelajaran[$i], 1, 'L', 0, 0, '', '', true, 0, false, true, $row_height, 'M');
    $pdf->MultiCell($col_widths[1], $row_height, $skor[$i], 1, 'C', 0, 0, '', '', true, 0, false, true, $row_height, 'M');
    $pdf->MultiCell($col_widths[2], $row_height, $deskripsi[$i], 1, 'L', 0, 1, '', '', true, 0, false, true, $row_height, 'M');
}
$pdf->Ln(2);

// Notes Box
$pdf->SetFont('times', 'B', 11);
$pdf->Cell(180, 8, 'Catatan :', 'LTR', 1);
$pdf->SetFont('times', '', 10);
$pdf->MultiCell(180, 15, $assessment['notes'] ?? '-', 'LBR', 'L', false, 1);
$pdf->Ln(5);

// Attendance Table
$pdf->SetFont('times', 'B', 11);
$pdf->Cell(60, 8, 'Ketidakhadiran', 1, 1, 'C', 1);

$pdf->SetFont('times', '', 10);
$pdf->Cell(30, 7, 'Sakit', 1, 0);
$pdf->Cell(30, 7, ': ' . $sakit . ' hari', 1, 1);
$pdf->Cell(30, 7, 'Izin', 1, 0);
$pdf->Cell(30, 7, ': ' . $izin . ' hari', 1, 1);
$pdf->Cell(30, 7, 'Tanpa Keterangan', 1, 0);
$pdf->Cell(30, 7, ': ' . $tanpa_keterangan . ' hari', 1, 1);
$pdf->Ln(10);

// Signature Section
$pdf->SetFont('times', '', 11);
$pdf->Cell(90, 6, '', 0, 0); // Spacer
$pdf->Cell(90, 6, 'Bondowoso, ' . date('d F Y'), 0, 1, 'C');
$pdf->Cell(90, 6, 'Guru Pembimbing', 0, 0, 'C');
$pdf->Cell(90, 6, 'Pembimbing Dunia Kerja', 0, 1, 'C');
$pdf->Ln(20);

// Names and NIP
$pdf->SetFont('times', 'BU', 11);
$pdf->Cell(90, 6, $data['teacher_name'] ?? 'Nama Guru', 0, 0, 'C');
$pdf->Cell(90, 6, $data['instructor_name'] ?? 'Nama Instruktur', 0, 1, 'C');

$pdf->SetFont('times', '', 11);
$pdf->Cell(90, 6, 'NIP. ' . ($data['teacher_nip'] ?? '-'), 0, 0, 'C');
$pdf->Cell(90, 6, 'NIP. ' . ($data['instructor_nip'] ?? '-'), 0, 1, 'C');

// Close and output PDF document
$pdf->Output('Rapor_PKL_' . str_replace(' ', '_', $data['student_name']) . '.pdf', 'I');
?>