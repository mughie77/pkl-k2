<?php
require_once __DIR__ . '/../config/config.php';

// Keamanan: Pastikan pengguna adalah guru atau instruktur
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['teacher', 'instructor'])) {
    header("Location: ../login.php");
    exit;
}

$creator_id = $_SESSION['user_id'];
$creator_role = $_SESSION['user_role'];

function set_flash_message($type, $message) {
    $_SESSION['flash_message'] = ['type' => $type, 'message' => $message];
}

function redirect_to_problems_page($student_id) {
    header("Location: ../student_problems.php?student_id=" . $student_id);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_note') {

    $student_id = $_POST['student_id'];
    $note = trim($_POST['note']);

    if (empty($student_id) || empty($note)) {
        set_flash_message('danger', 'Siswa harus dipilih dan catatan tidak boleh kosong.');
        header("Location: ../student_problems.php");
        exit;
    }

    try {
        // Verifikasi bahwa pengguna berhak membuat catatan untuk siswa ini
        if ($creator_role === 'teacher') {
            $stmt_verify = $pdo->prepare("SELECT COUNT(*) FROM internship_mappings WHERE student_id = :student_id AND teacher_id = :creator_id");
        } else { // instructor
            $stmt_verify = $pdo->prepare("SELECT COUNT(*) FROM internship_mappings WHERE student_id = :student_id AND instructor_id = :creator_id");
        }
        $stmt_verify->execute([':student_id' => $student_id, ':creator_id' => $creator_id]);

        if ($stmt_verify->fetchColumn() > 0) {
            $stmt_insert = $pdo->prepare("INSERT INTO student_notes (student_id, creator_id, creator_role, note) VALUES (:student_id, :creator_id, :creator_role, :note)");
            $stmt_insert->execute([
                ':student_id' => $student_id,
                ':creator_id' => $creator_id,
                ':creator_role' => $creator_role,
                ':note' => $note
            ]);
            set_flash_message('success', 'Catatan berhasil disimpan.');
        } else {
            set_flash_message('danger', 'Anda tidak berhak membuat catatan untuk siswa ini.');
        }

    } catch (PDOException $e) {
        set_flash_message('danger', 'Terjadi kesalahan database: ' . $e->getMessage());
    }

    redirect_to_problems_page($student_id);

} else {
    header("Location: ../login.php");
    exit;
}
?>