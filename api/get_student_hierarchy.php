<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';

if (!isset($_GET['student_id'])) {
    echo json_encode(['error' => 'Student ID not provided.']);
    exit;
}

$student_id = $_GET['student_id'];

try {
    $stmt = $pdo->prepare("
        SELECT
            k.id as kelas_id,
            kk.id as konsentrasi_id,
            pk.id as program_id
        FROM students s
        JOIN kelas k ON s.kelas_id = k.id
        JOIN konsentrasi_keahlian kk ON k.konsentrasi_id = kk.id
        JOIN program_keahlian pk ON kk.program_id = pk.id
        WHERE s.id = :student_id
    ");
    $stmt->execute([':student_id' => $student_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        echo json_encode($result);
    } else {
        echo json_encode(['error' => 'Student not found.']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>