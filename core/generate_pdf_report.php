<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

// Check if user is logged in and has the correct role (teacher or waka_humas)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['teacher', 'waka_humas'])) {
    http_response_code(403);
    die('Akses ditolak.');
}

// Get assessment_id from URL
if (!isset($_GET['assessment_id']) || !is_numeric($_GET['assessment_id'])) {
    http_response_code(400);
    die('ID Penilaian tidak valid.');
}
$assessment_id = (int)$_GET['assessment_id'];

// Fetch data from database
try {
    $stmt = $pdo->prepare("
        SELECT
            s.name as student_name, s.nisn,
            k.kelas_name as class_name,
            pk.program_name,
            c.name as company_name, c.address as company_address,
            i.name as instructor_name, i.position as instructor_position,
            a.score_1, a.score_2, a.score_3, a.score_4, a.notes
        FROM internship_assessments a
        JOIN students s ON a.student_id = s.id
        JOIN kelas k ON s.kelas_id = k.id
        JOIN konsentrasi_keahlian kk ON k.konsentrasi_id = kk.id
        JOIN program_keahlian pk ON kk.program_id = pk.id
        JOIN instructors i ON a.instructor_id = i.id
        JOIN companies c ON i.company_id = c.id
        WHERE a.id = :assessment_id
    ");
    $stmt->execute([':assessment_id' => $assessment_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        http_response_code(404);
        die('Data penilaian tidak ditemukan.');
    }
} catch (PDOException $e) {
    http_response_code(500);
    die("Error fetching data: " . $e->getMessage());
}

// Create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor($app_settings['school_name']);
$pdf->SetTitle('Laporan Penilaian PKL - ' . $data['student_name']);
$pdf->SetSubject('Laporan Penilaian PKL');

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set margins
$pdf->SetMargins(15, 15, 15);

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 10);

// --- PDF CONTENT ---

// Header
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 8, 'PENILAIAN PRAKTIK KERJA LAPANGAN (PKL)', 0, 1, 'C');
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 7, strtoupper($app_settings['school_name']), 0, 1, 'C');
$pdf->Line(15, $pdf->GetY() + 2, 195, $pdf->GetY() + 2);
$pdf->Ln(4);

// Student and Company Info
$pdf->SetFont('helvetica', '', 10);

$info_html = <<<EOD
<table cellpadding="4" cellspacing="0" border="0">
    <tr>
        <td width="25%">Nama Peserta Didik</td>
        <td width="2%">:</td>
        <td width="73%"><b>{$data['student_name']}</b></td>
    </tr>
    <tr>
        <td>NISN</td>
        <td>:</td>
        <td>{$data['nisn']}</td>
    </tr>
    <tr>
        <td>Kelas</td>
        <td>:</td>
        <td>{$data['class_name']}</td>
    </tr>
    <tr>
        <td>Program Keahlian</td>
        <td>:</td>
        <td>{$data['program_name']}</td>
    </tr>
    <tr>
        <td>Nama DU/DI/Instansi</td>
        <td>:</td>
        <td><b>{$data['company_name']}</b></td>
    </tr>
    <tr>
        <td>Alamat</td>
        <td>:</td>
        <td>{$data['company_address']}</td>
    </tr>
</table>
EOD;

$pdf->writeHTML($info_html, true, false, true, false, '');
$pdf->Ln(5);

// Scores Table
$pdf->SetFont('helvetica', '', 10);

// Data and labels
$scores = [
    'Memahami Alur Bisnis Proses di Industri' => $data['score_1'],
    'Menerapkan Prosedur Kerja dan Menjaga Budaya Kerja Industri' => $data['score_2'],
    'Menerapkan Norma, Standar, Prosedur, dan Kaidah K3LH' => $data['score_3'],
    'Melakukan Kompetensi Teknis Sesuai Bidang' => $data['score_4']
];

function getPredicate($score) {
    if ($score >= 91) return 'Sangat Baik';
    if ($score >= 81) return 'Baik';
    if ($score >= 71) return 'Cukup';
    return 'Kurang';
}

$total_score = array_sum($scores);
$average_score = $total_score / count($scores);
$final_predicate = getPredicate($average_score);

// Table HTML
$table_html = <<<EOD
<table cellpadding="6" cellspacing="0" border="1">
    <tr style="background-color:#E0E0E0; text-align:center; font-weight:bold;">
        <th width="5%">No</th>
        <th width="55%">Komponen Penilaian</th>
        <th width="15%">Nilai</th>
        <th width="25%">Predikat</th>
    </tr>
EOD;

$num = 1;
foreach ($scores as $component => $score) {
    $predicate = getPredicate($score);
    $table_html .= <<<EOD
    <tr>
        <td style="text-align:center;">{$num}</td>
        <td>{$component}</td>
        <td style="text-align:center;">{$score}</td>
        <td style="text-align:center;">{$predicate}</td>
    </tr>
EOD;
    $num++;
}

$table_html .= <<<EOD
    <tr style="font-weight:bold;">
        <td colspan="2" style="text-align:right;">Jumlah Nilai</td>
        <td style="text-align:center;">{$total_score}</td>
        <td></td>
    </tr>
    <tr style="font-weight:bold;">
        <td colspan="2" style="text-align:right;">Rata-rata Nilai</td>
        <td style="text-align:center;">{$average_score}</td>
        <td style="text-align:center;">{$final_predicate}</td>
    </tr>
</table>
EOD;

$pdf->writeHTML($table_html, true, false, true, false, '');
$pdf->Ln(5);

// Notes Section
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 8, 'Catatan Instruktur:', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 10);
$pdf->MultiCell(0, 10, !empty($data['notes']) ? $data['notes'] : 'Tidak ada catatan.', 1, 'L', 0, 1, '', '', true, 0, false, true, 40, 'T');
$pdf->Ln(10);

// Signature
$pdf->SetFont('helvetica', '', 10);
$date = 'Bondowoso, ' . date('d F Y'); // Or use a specific date from the database if available

$signature_html = <<<EOD
<table cellpadding="4" cellspacing="0" border="0">
    <tr>
        <td width="50%"></td>
        <td width="50%" style="text-align:center;">{$date}</td>
    </tr>
    <tr>
        <td></td>
        <td style="text-align:center;">Pembimbing DU/DI/Instansi,</td>
    </tr>
    <tr><td colspan="2"><br><br><br><br></td></tr>
    <tr>
        <td></td>
        <td style="text-align:center;"><b><u>{$data['instructor_name']}</u></b></td>
    </tr>
    <tr>
        <td></td>
        <td style="text-align:center;">{$data['instructor_position']}</td>
    </tr>
</table>
EOD;

$pdf->writeHTML($signature_html, true, false, true, false, '');

// Close and output PDF document
$pdf->Output('Laporan_PKL_' . str_replace(' ', '_', $data['student_name']) . '.pdf', 'I');
