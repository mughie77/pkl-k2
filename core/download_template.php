<?php
// Script to generate and download the Excel template for student import
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// Keamanan: Pastikan hanya admin yang dapat mengakses
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    die("Akses ditolak.");
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Template Import Siswa');

// Define headers
$headers = [
    'Nama Lengkap',
    'NIS',
    'NISN',
    'Email',
    'Tempat Lahir',
    'Tanggal Lahir (YYYY-MM-DD)',
    'Alamat',
    'No. HP Siswa',
    'No. HP Orang Tua',
    'Kelas (Nama Lengkap)', // e.g., 'Teknik Komputer dan Jaringan - Teknik Komputer - TKJ XI A'
    'Tahun Pelajaran (e.g., 2023/2024)'
];

// Set headers in the first row
$sheet->fromArray($headers, null, 'A1');

// Style the header row
$headerStyle = [
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '4285F4'],
    ],
];
$sheet->getStyle('A1:K1')->applyFromArray($headerStyle);

// Auto-size columns for better readability
foreach (range('A', 'K') as $columnID) {
    $sheet->getColumnDimension($columnID)->setAutoSize(true);
}

// Add a comment to the 'Kelas' header for clarification
$sheet->getComment('J1')->getText()->createTextRun('Masukkan nama lengkap kelas persis seperti yang ada di sistem, contoh: "Teknik Komputer dan Jaringan - Teknik Komputer - TKJ XI A".');

// Add a comment to the 'Tahun Pelajaran' header
$sheet->getComment('K1')->getText()->createTextRun('Masukkan nama tahun pelajaran yang aktif, contoh: "2023/2024".');

// Set headers for download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="template_import_siswa.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
ob_end_clean(); // Clean any output buffer
$writer->save('php://output');
exit;
?>
