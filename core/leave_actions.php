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

// --- LOGIKA UNTUK SISWA ---
if ($user_role === 'student') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'request_leave') {
        $leave_type = $_POST['leave_type'];
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $reason = trim($_POST['reason']);

        if (empty($leave_type) || empty($start_date) || empty($end_date) || empty($reason)) {
            set_flash_message('danger', 'Semua kolom wajib diisi.');
            header("Location: ../request_leave.php");
            exit;
        }

        if ($start_date > $end_date) {
            set_flash_message('danger', 'Tanggal mulai tidak boleh lebih akhir dari tanggal selesai.');
            header("Location: ../request_leave.php");
            exit;
        }

        try {
            // Dapatkan ID instruktur dari mapping
            $map_stmt = $pdo->prepare("SELECT instructor_id FROM internship_mappings WHERE student_id = :student_id");
            $map_stmt->execute([':student_id' => $user_id]);
            $mapping = $map_stmt->fetch(PDO::FETCH_ASSOC);

            if (!$mapping) {
                set_flash_message('danger', 'Anda tidak ter-mapping dengan instruktur manapun. Tidak dapat mengajukan izin.');
                header("Location: ../request_leave.php");
                exit;
            }
            $instructor_id = $mapping['instructor_id'];

            $stmt = $pdo->prepare("INSERT INTO leave_requests (student_id, instructor_id, leave_type, start_date, end_date, reason) VALUES (:student_id, :instructor_id, :leave_type, :start_date, :end_date, :reason)");
            $stmt->execute([
                ':student_id' => $user_id,
                ':instructor_id' => $instructor_id,
                ':leave_type' => $leave_type,
                ':start_date' => $start_date,
                ':end_date' => $end_date,
                ':reason' => $reason
            ]);
            set_flash_message('success', 'Pengajuan berhasil dikirim.');
        } catch (PDOException $e) {
            set_flash_message('danger', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
    header("Location: ../request_leave.php");
    exit;
}

// --- LOGIKA UNTUK INSTRUKTUR ---
if ($user_role === 'instructor') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id']) && isset($_POST['action'])) {
        $request_id = $_POST['request_id'];
        $action = $_POST['action'];
        $new_status = '';

        if ($action === 'approve_leave') {
            $new_status = 'Approved';
        } elseif ($action === 'reject_leave') {
            $new_status = 'Rejected';
        } else {
            set_flash_message('danger', 'Aksi tidak valid.');
            header("Location: ../manage_leave_requests.php");
            exit;
        }

        try {
            // Verifikasi bahwa instruktur ini berhak memproses pengajuan ini
            $verify_stmt = $pdo->prepare("SELECT COUNT(*) FROM leave_requests WHERE id = :request_id AND instructor_id = :instructor_id");
            $verify_stmt->execute([':request_id' => $request_id, ':instructor_id' => $user_id]);

            if ($verify_stmt->fetchColumn() > 0) {
                $update_stmt = $pdo->prepare("UPDATE leave_requests SET status = :status WHERE id = :id");
                $update_stmt->execute([':status' => $new_status, ':id' => $request_id]);
                set_flash_message('success', "Pengajuan berhasil di-{$new_status}.");
            } else {
                set_flash_message('danger', 'Anda tidak berhak memproses pengajuan ini.');
            }
        } catch (PDOException $e) {
            set_flash_message('danger', 'Terjadi kesalahan database: ' . $e->getMessage());
        }
    }
    header("Location: ../manage_leave_requests.php");
    exit;
}

// Fallback
header("Location: ../login.php");
exit;
?>