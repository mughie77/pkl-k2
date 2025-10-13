<?php
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/sidebar.php'; // Sidebar akan otomatis di-handle

// Proteksi halaman
if ($_SESSION['user_role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$student_id = $_SESSION['user_id'];
$student_name = $_SESSION['user_name'];
$today = date('Y-m-d');

// Logika Sapaan Dinamis
$hour = date('H');
$greeting = 'Selamat Pagi';
if ($hour >= 12) {
    $greeting = 'Selamat Siang';
}
if ($hour >= 15) {
    $greeting = 'Selamat Sore';
}
if ($hour >= 18) {
    $greeting = 'Selamat Malam';
}

try {
    // Ambil data siswa termasuk jam kerja
    $stmt_student = $pdo->prepare("SELECT work_start_time, work_end_time FROM students WHERE id = :id");
    $stmt_student->execute([':id' => $student_id]);
    $student_data = $stmt_student->fetch(PDO::FETCH_ASSOC);

    // Ambil data absensi hari ini
    $stmt_today = $pdo->prepare("SELECT check_in_time, check_out_time FROM internship_journals WHERE student_id = :student_id AND journal_date = :today");
    $stmt_today->execute([':student_id' => $student_id, ':today' => $today]);
    $today_attendance = $stmt_today->fetch(PDO::FETCH_ASSOC);

    // Ambil riwayat absensi 7 hari terakhir
    $one_week_ago = date('Y-m-d', strtotime('-7 days'));
    $stmt_history = $pdo->prepare("
        SELECT journal_date, check_in_time, check_out_time
        FROM internship_journals
        WHERE student_id = :student_id AND journal_date >= :one_week_ago
        ORDER BY journal_date DESC
    ");
    $stmt_history->execute([':student_id' => $student_id, ':one_week_ago' => $one_week_ago]);
    $week_history = $stmt_history->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error fetching dashboard data: " . $e->getMessage());
}
?>

<div class="container-fluid student-dashboard">
    <!-- Header Dashboard -->
    <div class="dashboard-header card p-3 mb-4 shadow-sm">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <p class="text-muted mb-0"><?php echo date('d M Y'); ?></p>
                <h5 class="mb-1"><?php echo $greeting; ?>!</h5>
                <h3 class="fw-bold mb-0"><?php echo htmlspecialchars($student_name); ?></h3>
            </div>
            <div class="text-end">
                <p class="text-muted mb-0">Jam Kerja</p>
                <h5 class="fw-bold mb-0">
                    <?php echo date('H:i', strtotime($student_data['work_start_time'])); ?> -
                    <?php echo date('H:i', strtotime($student_data['work_end_time'])); ?>
                </h5>
            </div>
        </div>
    </div>

    <!-- Menu Ikon -->
    <div class="row text-center g-3 mb-4">
        <div class="col">
            <a href="daily_journal.php" class="icon-menu-item">
                <div class="icon-circle bg-danger text-white"><i class="fas fa-qrcode"></i></div>
                <span class="icon-label">Absen</span>
            </a>
        </div>
        <div class="col">
            <a href="request_leave.php" class="icon-menu-item">
                <div class="icon-circle bg-warning text-white"><i class="fas fa-file-alt"></i></div>
                <span class="icon-label">Izin</span>
            </a>
        </div>
        <div class="col">
            <a href="request_leave.php?type=Cuti" class="icon-menu-item">
                <div class="icon-circle bg-primary text-white"><i class="fas fa-calendar-times"></i></div>
                <span class="icon-label">Cuti</span>
            </a>
        </div>
        <div class="col">
            <a href="upload_report.php" class="icon-menu-item">
                <div class="icon-circle bg-info text-white"><i class="fas fa-file-upload"></i></div>
                <span class="icon-label">Unggah Laporan</span>
            </a>
        </div>
        <div class="col">
            <a href="profile.php" class="icon-menu-item">
                <div class="icon-circle bg-success text-white"><i class="fas fa-user"></i></div>
                <span class="icon-label">Profil</span>
            </a>
        </div>
    </div>

    <!-- Status Absensi Hari Ini -->
    <div class="row g-3 mb-4">
        <div class="col-6">
            <div class="card status-card <?php echo $today_attendance && $today_attendance['check_in_time'] ? 'bg-success' : 'bg-light-green'; ?> text-white">
                <div class="card-body">
                    <h6 class="status-title">Absen Masuk</h6>
                    <p class="status-time fw-bold"><?php echo $today_attendance && $today_attendance['check_in_time'] ? date('H:i', strtotime($today_attendance['check_in_time'])) : 'Belum Absen'; ?></p>
                </div>
            </div>
        </div>
        <div class="col-6">
            <div class="card status-card <?php echo $today_attendance && $today_attendance['check_out_time'] ? 'bg-danger' : 'bg-light-red'; ?> text-white">
                <div class="card-body">
                    <h6 class="status-title">Absen Pulang</h6>
                    <p class="status-time fw-bold"><?php echo $today_attendance && $today_attendance['check_out_time'] ? date('H:i', strtotime($today_attendance['check_out_time'])) : 'Belum Absen'; ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Riwayat Absensi Mingguan -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h6 class="m-0 font-weight-bold text-primary">Absensi 1 Minggu Terakhir</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="bg-warning text-white">
                        <tr>
                            <th>Tanggal</th>
                            <th>Jam Masuk</th>
                            <th>Jam Pulang</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($week_history) > 0): ?>
                            <?php foreach ($week_history as $history): ?>
                                <tr>
                                    <td><?php echo date('d M Y', strtotime($history['journal_date'])); ?></td>
                                    <td><?php echo $history['check_in_time'] ? date('H:i', strtotime($history['check_in_time'])) : '-'; ?></td>
                                    <td><?php echo $history['check_out_time'] ? date('H:i', strtotime($history['check_out_time'])) : '-'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center p-4">Belum ada riwayat absensi.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
// Karena siswa tidak punya sidebar, div penutupnya harus ada di sini
if ($_SESSION['user_role'] === 'student') {
    echo '</div>';
}
require_once __DIR__ . '/templates/footer.php';
?>