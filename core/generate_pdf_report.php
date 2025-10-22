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
            s.id as student_id, s.name as student_name, s.nisn,
            k.kelas_name as class_name,
            pk.program_name,
            kk.konsentrasi_name,
            c.name as company_name,
            ip.start_date, ip.end_date,
            i.name as instructor_name,
            t.name as teacher_name,
            a.score_1, a.score_2, a.score_3, a.score_4, a.notes
        FROM internship_assessments a
        JOIN students s ON a.student_id = s.id
        JOIN internship_mappings ip ON s.id = ip.student_id AND a.instructor_id = ip.instructor_id
        JOIN teachers t ON ip.teacher_id = t.id
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

// --- ATTENDANCE CALCULATION ---
$attendance_counts = [
    'Sakit' => 0,
    'Izin' => 0,
    'Tanpa Keterangan' => 0,
];

try {
    $student_id = $data['student_id'];
    $start_date = new DateTime($data['start_date']);
    $end_date = new DateTime($data['end_date']);

    // 1. Get all approved leave dates
    $stmt_leaves = $pdo->prepare("
        SELECT start_date, end_date, leave_type FROM leave_requests
        WHERE student_id = :student_id AND status = 'Approved'
    ");
    $stmt_leaves->execute([':student_id' => $student_id]);
    $leaves = $stmt_leaves->fetchAll(PDO::FETCH_ASSOC);

    $leave_dates = [];
    foreach ($leaves as $leave) {
        $period = new DatePeriod(new DateTime($leave['start_date']), new DateInterval('P1D'), (new DateTime($leave['end_date']))->modify('+1 day'));
        foreach ($period as $date) {
            $date_str = $date->format('Y-m-d');
            if (!isset($leave_dates[$date_str])) {
                $leave_dates[$date_str] = $leave['leave_type'];
            }
        }
    }

    // 2. Get all journal dates (days the student was present)
    $stmt_journals = $pdo->prepare("
        SELECT DISTINCT journal_date FROM internship_journals
        WHERE student_id = :student_id
    ");
    $stmt_journals->execute([':student_id' => $student_id]);
    $journal_dates = $stmt_journals->fetchAll(PDO::FETCH_COLUMN, 0);
    $present_dates = array_flip($journal_dates); // Use as a hash set for quick lookups

    // 3. Iterate through the entire internship period to calculate totals
    $internship_period = new DatePeriod($start_date, new DateInterval('P1D'), $end_date->modify('+1 day'));
    foreach ($internship_period as $date) {
        // Skip Sundays
        if ($date->format('w') == 0) {
            continue;
        }
        $date_str = $date->format('Y-m-d');

        if (isset($leave_dates[$date_str])) {
            $leave_type = $leave_dates[$date_str];
            if (isset($attendance_counts[$leave_type])) {
                $attendance_counts[$leave_type]++;
            }
        } elseif (!isset($present_dates[$date_str])) {
            // Not on leave and not present = absent without notice
            $attendance_counts['Tanpa Keterangan']++;
        }
    }

} catch (Exception $e) {
    // If date processing fails, counts will remain 0.
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

// Fetch academic year name directly to ensure it's always available
$academic_year_name = 'Tidak Diketahui'; // Default value
if (isset($_SESSION['selected_academic_year_id'])) {
    try {
        $stmt_year = $pdo->prepare("SELECT year_name FROM academic_years WHERE id = :id");
        $stmt_year->execute([':id' => $_SESSION['selected_academic_year_id']]);
        $year_data = $stmt_year->fetch(PDO::FETCH_ASSOC);
        if ($year_data) {
            $academic_year_name = $year_data['year_name'];
        }
    } catch (PDOException $e) {
        // In case of error, the default value will be used.
    }
}

// Helper function to format dates
function format_date($date_string) {
    if (empty($date_string)) return '-';
    $date = new DateTime($date_string);
    $months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return $date->format('d') . ' ' . $months[(int)$date->format('m') - 1] . ' ' . $date->format('Y');
}

$start_date_formatted = format_date($data['start_date']);
$end_date_formatted = format_date($data['end_date']);
$company_name = htmlspecialchars($data['company_name']);

// Header
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 8, 'PENILAIAN PRAKTIK KERJA LAPANGAN (PKL)', 0, 1, 'C');
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 7, strtoupper($app_settings['school_name']), 0, 1, 'C');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 6, 'Tahun Pelajaran ' . $academic_year_name, 0, 1, 'C');
$pdf->Line(15, $pdf->GetY() + 2, 195, $pdf->GetY() + 2);
$pdf->Ln(5);

// Student Identity
$pdf->SetFont('helvetica', '', 10);
$identity_html = <<<EOD
<table cellpadding="2" cellspacing="0" border="0" style="font-size: 10pt;">
    <tr>
        <td width="30%">Nama</td>
        <td width="2%">:</td>
        <td width="68%"><b>{$data['student_name']}</b></td>
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
        <td>Konsentrasi Keahlian</td>
        <td>:</td>
        <td>{$data['konsentrasi_name']}</td>
    </tr>
    <tr>
        <td>Tempat PKL (Nama Dudika)</td>
        <td>:</td>
        <td>{$company_name}</td>
    </tr>
    <tr>
        <td>Tanggal PKL</td>
        <td>:</td>
        <td>Mulai: {$start_date_formatted} &nbsp;&nbsp; Selesai: {$end_date_formatted}</td>
    </tr>
    <tr>
        <td>Nama Instruktur</td>
        <td>:</td>
        <td>{$data['instructor_name']}</td>
    </tr>
    <tr>
        <td>Nama Pembimbing</td>
        <td>:</td>
        <td>{$data['teacher_name']}</td>
    </tr>
</table>
EOD;
$pdf->writeHTML($identity_html, true, false, true, false, '');
$pdf->Ln(5);

// Assessment Table
$scores_data = [
    [
        'tujuan' => 'Memahami Alur Bisnis Proses di Industri',
        'skor' => $data['score_1'],
        'deskripsi' => "Kompeten dalam memahami alur bisnis dunia kerja di {$company_name} dan wawasan wirausaha"
    ],
    [
        'tujuan' => 'Menerapkan Prosedur Kerja dan Menjaga Budaya Kerja Industri',
        'skor' => $data['score_2'],
        'deskripsi' => "Kompeten dalam menerapkan kompetensi"
    ],
    [
        'tujuan' => 'Menerapkan Norma, Standar, Prosedur, dan Kaidah K3LH',
        'skor' => $data['score_3'],
        'deskripsi' => "Kompeten dalam menerapkan norma, SOP dan K3LH yang ada di {$company_name}"
    ],
    [
        'tujuan' => 'Melakukan Kompetensi Teknis Sesuai Bidang',
        'skor' => $data['score_4'],
        'deskripsi' => "Kompeten dalam menerapkan soft skills yang dibutuhkan di {$company_name}"
    ]
];

$assessment_table_html = <<<EOD
<table cellpadding="5" cellspacing="0" border="1" style="font-size: 10pt;">
    <tr style="background-color:#E0E0E0; text-align:center; font-weight:bold;">
        <th width="5%">No.</th>
        <th width="40%">Tujuan Pembelajaran (Komponen Penilaian)</th>
        <th width="10%">Skor (Nilai)</th>
        <th width="45%">Deskripsi</th>
    </tr>
EOD;
$no = 1;
foreach ($scores_data as $item) {
    $assessment_table_html .= '
    <tr>
        <td style="text-align:center;">' . $no++ . '</td>
        <td>' . $item['tujuan'] . '</td>
        <td style="text-align:center;">' . $item['skor'] . '</td>
        <td>' . $item['deskripsi'] . '</td>
    </tr>';
}
$assessment_table_html .= '</table>';
$pdf->writeHTML($assessment_table_html, true, false, true, false, '');
$pdf->Ln(5);

// Instructor Notes
$pdf->SetFont('helvetica', '', 10);
$notes_html = '<b>Catatan dari Instruktur Dudika:</b><br>' . (!empty($data['notes']) ? nl2br(htmlspecialchars($data['notes'])) : 'Tidak ada catatan.');
$pdf->writeHTMLCell(0, '', '', '', $notes_html, 1, 1, 0, true, 'L', true);
$pdf->Ln(5);

// Attendance Recap
$attendance_html = <<<EOD
<b style="font-size: 10pt;">Rekap Ketidakhadiran Siswa:</b>
<table cellpadding="5" cellspacing="0" border="1" style="font-size: 10pt;">
    <tr style="background-color:#E0E0E0; text-align:center; font-weight:bold;">
        <th width="33.3%">Sakit</th>
        <th width="33.3%">Izin</th>
        <th width="33.4%">Tanpa Keterangan/Alpa</th>
    </tr>
    <tr>
        <td style="text-align:center;">{$attendance_counts['Sakit']} Hari</td>
        <td style="text-align:center;">{$attendance_counts['Izin']} Hari</td>
        <td style="text-align:center;">{$attendance_counts['Tanpa Keterangan']} Hari</td>
    </tr>
</table>
EOD;
$pdf->writeHTML($attendance_html, true, false, true, false, '');
$pdf->Ln(10);

// Signature Block
$date = 'Bondowoso, ' . format_date(date('Y-m-d'));
$signature_html = <<<EOD
<table cellpadding="2" cellspacing="0" border="0" style="font-size: 10pt;">
    <tr>
        <td width="50%" style="text-align:center;">Guru Pembimbing</td>
        <td width="50%" style="text-align:center;">{$date}</td>
    </tr>
    <tr>
        <td></td>
        <td style="text-align:center;">Pembimbing Dunia Kerja</td>
    </tr>
    <tr><td colspan="2"><br><br><br><br></td></tr>
    <tr>
        <td style="text-align:center;"><b><u>{$data['teacher_name']}</u></b></td>
        <td style="text-align:center;"><b><u>{$data['instructor_name']}</u></b></td>
    </tr>
</table>
EOD;
$pdf->writeHTML($signature_html, true, false, true, false, '');

// Close and output PDF document
$pdf->Output('Laporan_PKL_' . str_replace(' ', '_', $data['student_name']) . '.pdf', 'I');
