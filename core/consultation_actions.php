<?php
require_once __DIR__ . '/../config/config.php';

// Keamanan: Pastikan pengguna sudah login
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

function set_flash_message($type, $message) {
    $_SESSION['flash_message'] = ['type' => $type, 'message' => $message];
}

// --- LOGIKA UNTUK GURU ---
if ($user_role === 'teacher') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'give_feedback') {
        $report_id = $_POST['report_id'];
        $feedback = trim($_POST['feedback']);

        if (empty($report_id) || empty($feedback)) {
            set_flash_message('danger', 'Feedback tidak boleh kosong.');
            header("Location: ../report_consultation.php");
            exit;
        }

        try {
            // Verifikasi bahwa guru ini berhak memberi feedback pada laporan ini
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM report_consultations WHERE id = :report_id AND teacher_id = :teacher_id");
            $stmt->execute([':report_id' => $report_id, ':teacher_id' => $user_id]);

            if ($stmt->fetchColumn() > 0) {
                $update_stmt = $pdo->prepare("UPDATE report_consultations SET feedback = :feedback WHERE id = :id");
                $update_stmt->execute([':feedback' => $feedback, ':id' => $report_id]);
                set_flash_message('success', 'Feedback berhasil disimpan.');
            } else {
                set_flash_message('danger', 'Anda tidak berhak memberikan feedback untuk laporan ini.');
            }
        } catch (PDOException $e) {
            set_flash_message('danger', 'Terjadi kesalahan database: ' . $e->getMessage());
        }
    }
    header("Location: ../report_consultation.php");
    exit;
}

// --- LOGIKA UNTUK SISWA ---
if ($user_role === 'student') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_report') {
        if (isset($_FILES['report_file']) && $_FILES['report_file']['error'] == 0) {
            $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            $max_size = 5 * 1024 * 1024; // 5 MB

            if (in_array($_FILES['report_file']['type'], $allowed_types) && $_FILES['report_file']['size'] <= $max_size) {
                try {
                    // Dapatkan ID guru pembimbing siswa
                    $map_stmt = $pdo->prepare("SELECT teacher_id FROM internship_mappings WHERE student_id = :student_id");
                    $map_stmt->execute([':student_id' => $user_id]);
                    $mapping = $map_stmt->fetch(PDO::FETCH_ASSOC);

                    if ($mapping) {
                        $teacher_id = $mapping['teacher_id'];

                        // Define paths relative to the project root
                        $upload_dir_relative = 'uploads/reports/';
                        $upload_dir_absolute = dirname(__DIR__) . '/' . $upload_dir_relative;

                        if (!is_dir($upload_dir_absolute)) {
                            mkdir($upload_dir_absolute, 0777, true);
                        }

                        $file_name = time() . '_' . uniqid() . '_' . basename($_FILES['report_file']['name']);
                        $destination_absolute = $upload_dir_absolute . $file_name;
                        $destination_relative_for_db = $upload_dir_relative . $file_name;

                        if (move_uploaded_file($_FILES['report_file']['tmp_name'], $destination_absolute)) {
                            $insert_stmt = $pdo->prepare("INSERT INTO report_consultations (student_id, teacher_id, file_path) VALUES (:student_id, :teacher_id, :file_path)");
                            $insert_stmt->execute([
                                ':student_id' => $user_id,
                                ':teacher_id' => $teacher_id,
                                ':file_path' => $destination_relative_for_db
                            ]);
                            set_flash_message('success', 'File laporan berhasil diunggah.');
                        } else {
                            set_flash_message('danger', 'Gagal memindahkan file yang diunggah.');
                        }
                    } else {
                        set_flash_message('danger', 'Anda tidak ter-mapping dengan guru pembimbing manapun.');
                    }
                } catch (PDOException $e) {
                    set_flash_message('danger', 'Database error: ' . $e->getMessage());
                }
            } else {
                set_flash_message('danger', 'File tidak valid. Pastikan format file adalah PDF/DOC/DOCX dan ukuran maksimal 5MB.');
            }
        } else {
            set_flash_message('danger', 'Tidak ada file yang diunggah atau terjadi error.');
        }
    }
    header("Location: ../upload_report.php");
    exit;
}

// Fallback
header("Location: ../login.php");
exit;
?>