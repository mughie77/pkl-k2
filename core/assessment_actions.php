<?php
require_once __DIR__ . '/../config/config.php';

// Keamanan: Pastikan pengguna adalah instruktur
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'instructor') {
    header("Location: ../login.php");
    exit;
}

$instructor_id = $_SESSION['user_id'];

function set_flash_message($type, $message) {
    $_SESSION['flash_message'] = ['type' => $type, 'message' => $message];
}

function redirect_to_assessment_page() {
    header("Location: ../input_assessment.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_assessment') {

    $student_id = $_POST['student_id'] ?? null;
    $discipline_score = $_POST['discipline_score'] ?? null;
    $skill_score = $_POST['skill_score'] ?? null;
    $teamwork_score = $_POST['teamwork_score'] ?? null;
    $diligence_score = $_POST['diligence_score'] ?? null;
    $feedback = trim($_POST['feedback'] ?? '');

    // Validasi input
    if (empty($student_id) || !is_numeric($discipline_score) || !is_numeric($skill_score) || !is_numeric($teamwork_score) || !is_numeric($diligence_score)) {
        set_flash_message('danger', 'Data tidak lengkap. Pastikan siswa dipilih dan semua skor terisi.');
        redirect_to_assessment_page();
    }

    try {
        // 1. Verifikasi bahwa siswa ini adalah bimbingan instruktur yang sedang login
        $verify_stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM internship_mappings
            WHERE student_id = :student_id AND instructor_id = :instructor_id
        ");
        $verify_stmt->execute([':student_id' => $student_id, ':instructor_id' => $instructor_id]);

        if ($verify_stmt->fetchColumn() == 0) {
            set_flash_message('danger', 'Error: Anda tidak berhak menilai siswa ini.');
            redirect_to_assessment_page();
        }

        // 2. Verifikasi bahwa siswa ini belum pernah dinilai oleh instruktur ini
        $check_stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM internship_assessments
            WHERE student_id = :student_id AND instructor_id = :instructor_id
        ");
        $check_stmt->execute([':student_id' => $student_id, ':instructor_id' => $instructor_id]);

        if ($check_stmt->fetchColumn() > 0) {
            set_flash_message('warning', 'Siswa ini sudah pernah Anda nilai sebelumnya.');
            redirect_to_assessment_page();
        }

        // 3. Jika semua verifikasi lolos, masukkan data penilaian
        $insert_stmt = $pdo->prepare("
            INSERT INTO internship_assessments
            (student_id, instructor_id, discipline_score, skill_score, teamwork_score, diligence_score, feedback)
            VALUES
            (:student_id, :instructor_id, :discipline_score, :skill_score, :teamwork_score, :diligence_score, :feedback)
        ");

        $insert_stmt->execute([
            ':student_id' => $student_id,
            ':instructor_id' => $instructor_id,
            ':discipline_score' => $discipline_score,
            ':skill_score' => $skill_score,
            ':teamwork_score' => $teamwork_score,
            ':diligence_score' => $diligence_score,
            ':feedback' => $feedback
        ]);

        set_flash_message('success', 'Penilaian untuk siswa berhasil disimpan.');

    } catch (PDOException $e) {
        set_flash_message('danger', 'Terjadi kesalahan pada database: ' . $e->getMessage());
    }

    redirect_to_assessment_page();

} else {
    // Jika akses tidak sah
    set_flash_message('danger', 'Aksi tidak valid.');
    redirect_to_assessment_page();
}
?>