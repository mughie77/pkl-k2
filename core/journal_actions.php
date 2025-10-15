<?php
require_once __DIR__ . '/../config/config.php';

// Keamanan: Pastikan pengguna adalah instruktur
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header("Location: ../login.php");
    exit;
}

$user_role = $_SESSION['user_role'];
$user_id = $_SESSION['user_id'];

function set_flash_message($type, $message) {
    $_SESSION['flash_message'] = ['type' => $type, 'message' => $message];
}

function redirect_based_on_role($role) {
    if ($role === 'instructor') {
        header("Location: ../verify_journals.php");
    } else {
        // For student role, the response will be handled by AJAX, so no redirect needed here.
        // For other roles or fallbacks, redirect to login.
        header("Location: ../login.php");
    }
    exit;
}


// --- LOGIKA UNTUK INSTRUKTUR ---
if ($user_role === 'instructor') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['journal_id']) && isset($_POST['action'])) {
        $journal_id = $_POST['journal_id'];
        $action = $_POST['action'];
        $new_status = '';

        if ($action === 'approve') {
            $new_status = 'Approved';
        } elseif ($action === 'reject') {
            $new_status = 'Rejected';
        } else {
            set_flash_message('danger', 'Aksi tidak valid.');
            redirect_based_on_role($user_role);
        }

        try {
            // Verifikasi bahwa instruktur ini berhak memverifikasi jurnal ini
            $verify_stmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM internship_journals j
                JOIN internship_mappings m ON j.student_id = m.student_id
                WHERE j.id = :journal_id AND m.instructor_id = :instructor_id
            ");
            $verify_stmt->execute([':journal_id' => $journal_id, ':instructor_id' => $user_id]);

            if ($verify_stmt->fetchColumn() > 0) {
                // Jika berhak, update status
                $update_stmt = $pdo->prepare("UPDATE internship_journals SET status = :status WHERE id = :id");
                $update_stmt->execute([':status' => $new_status, ':id' => $journal_id]);
                set_flash_message('success', "Jurnal berhasil di-{$action}.");
            } else {
                set_flash_message('danger', 'Anda tidak berhak memverifikasi jurnal ini.');
            }

        } catch (PDOException $e) {
            set_flash_message('danger', 'Terjadi kesalahan database: ' . $e->getMessage());
        }
    }
    redirect_based_on_role($user_role);
}


// --- LOGIKA UNTUK SISWA ---
if ($user_role === 'student') {
    header('Content-Type: application/json');
    $response = ['status' => 'error', 'message' => 'Terjadi kesalahan yang tidak diketahui.'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        $today = date('Y-m-d');

        try {
            $journal_stmt = $pdo->prepare("SELECT * FROM internship_journals WHERE student_id = :student_id AND journal_date = :journal_date");
            $journal_stmt->execute([':student_id' => $user_id, ':journal_date' => $today]);
            $journal = $journal_stmt->fetch(PDO::FETCH_ASSOC);

            $current_time = date('H:i:s');
            $latitude = !empty($_POST['latitude']) ? $_POST['latitude'] : null;
            $longitude = !empty($_POST['longitude']) ? $_POST['longitude'] : null;

            if ($action === 'check_in') {
                if (!$journal) {
                    $stmt = $pdo->prepare("INSERT INTO internship_journals (student_id, journal_date, check_in_time, check_in_latitude, check_in_longitude, activities) VALUES (:student_id, :journal_date, :check_in_time, :lat, :lon, '')");
                    $stmt->execute([':student_id' => $user_id, ':journal_date' => $today, ':check_in_time' => $current_time, ':lat' => $latitude, ':lon' => $longitude]);
                    $response = ['status' => 'success', 'message' => 'Check-in berhasil dicatat. Halaman akan dimuat ulang.'];
                } else {
                    $response = ['status' => 'error', 'message' => 'Anda sudah melakukan check-in hari ini.'];
                }
            } elseif ($action === 'check_out') {
                if ($journal && !$journal['check_out_time']) {
                    $stmt = $pdo->prepare("UPDATE internship_journals SET check_out_time = :check_out_time, check_out_latitude = :lat, check_out_longitude = :lon WHERE id = :id");
                    $stmt->execute([':check_out_time' => $current_time, ':lat' => $latitude, ':lon' => $longitude, ':id' => $journal['id']]);
                    $response = ['status' => 'success', 'message' => 'Check-out berhasil dicatat. Halaman akan dimuat ulang.'];
                } else {
                    $response = ['status' => 'error', 'message' => 'Anda belum check-in atau sudah check-out hari ini.'];
                }
            } elseif ($action === 'submit_journal') {
                $activities = trim($_POST['activities'] ?? '');
                if ($journal && !empty($activities)) {
                    $stmt = $pdo->prepare("UPDATE internship_journals SET activities = :activities WHERE id = :id");
                    $stmt->execute([':activities' => $activities, ':id' => $journal['id']]);
                    // Note: This part is not handled by AJAX in the current implementation, but we'll add a response anyway
                    $response = ['status' => 'success', 'message' => 'Jurnal harian berhasil dikirim.'];
                } else {
                    $response = ['status' => 'error', 'message' => 'Gagal mengirim jurnal. Pastikan Anda sudah check-in dan mengisi kegiatan.'];
                }
            } else {
                $response = ['status' => 'error', 'message' => 'Aksi tidak valid.'];
            }
        } catch (PDOException $e) {
            $response = ['status' => 'error', 'message' => 'Terjadi kesalahan database: ' . $e->getMessage()];
        }
    }

    echo json_encode($response);
    exit;
}

// Fallback jika tidak ada kondisi yang cocok
header("Location: ../login.php");
exit;
?>