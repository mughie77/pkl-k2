<?php
require_once __DIR__ . '/../config/config.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;

// Create new Spreadsheet object
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set header values
$headers = [
    'Nama Lengkap',
    'NISN',
    'Email',
    'Tempat Lahir',
    'Tanggal Lahir (YYYY-MM-DD)',
    'Alamat',
    'No. HP Siswa',
    'No. HP Orang Tua',
    'Jurusan'
];

$sheet->fromArray($headers, NULL, 'A1');

// Style the header
$headerStyle = [
    'font' => [
        'bold' => true,
        'color' => ['argb' => 'FFFFFFFF'],
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['argb' => 'FF4F81BD'],
    ],
];
$sheet->getStyle('A1:I1')->applyFromArray($headerStyle);

// Auto size columns
foreach (range('A', 'I') as $columnID) {
    $sheet->getColumnDimension($columnID)->setAutoSize(true);
}

// Create a writer
$writer = new Xlsx($spreadsheet);

// Set headers to force download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="template_import_siswa.xlsx"');
header('Cache-Control: max-age=0');

$writer->save('php://output');
exit;
?>