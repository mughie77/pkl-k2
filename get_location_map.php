<?php
require_once __DIR__ . '/config/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['teacher', 'instructor'])) {
    echo json_encode(['error' => 'Akses ditolak.']);
    exit;
}

$journal_id = $_GET['journal_id'] ?? null;
$type = $_GET['type'] ?? null; // 'check_in' or 'check_out'

if (!$journal_id || !$type) {
    echo json_encode(['error' => 'Parameter tidak lengkap.']);
    exit;
}

try {
    $lat_column = $type === 'check_in' ? 'check_in_latitude' : 'check_out_latitude';
    $lon_column = $type === 'check_in' ? 'check_in_longitude' : 'check_out_longitude';
    $time_column = $type === 'check_in' ? 'check_in_time' : 'check_out_time';

    $stmt = $pdo->prepare("
        SELECT
            j.$lat_column as lat,
            j.$lon_column as lon,
            j.$time_column as time,
            j.journal_date,
            s.name as student_name
        FROM internship_journals j
        JOIN students s ON j.student_id = s.id
        WHERE j.id = :journal_id
    ");
    $stmt->execute([':journal_id' => $journal_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result || empty($result['lat']) || empty($result['lon'])) {
        echo json_encode(['error' => 'Data lokasi tidak ditemukan.']);
        exit;
    }

    $response = [
        'lat' => $result['lat'],
        'lon' => $result['lon'],
        'time' => date('H:i', strtotime($result['time'])),
        'date' => date('d M Y', strtotime($result['journal_date'])),
        'student_name' => $result['student_name'],
        'type' => ucwords(str_replace('_', ' ', $type))
    ];

    echo json_encode($response);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Kesalahan database: ' . $e->getMessage()]);
}
?>