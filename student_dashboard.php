<?php
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/sidebar.php';

// Proteksi halaman
if ($_SESSION['user_role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$student_id = $_SESSION['user_id'];
$info_loaded = false;

try {
    // 1. Ambil informasi mapping (guru, instruktur, tanggal pkl)
    $stmt_map = $pdo->prepare("
        SELECT
            t.name as teacher_name,
            i.name as instructor_name,
            c.name as company_name,
            im.start_date,
            im.end_date
        FROM internship_mappings im
        JOIN teachers t ON im.teacher_id = t.id
        JOIN instructors i ON im.instructor_id = i.id
        JOIN companies c ON i.company_id = c.id
        WHERE im.student_id = :student_id
    ");
    $stmt_map->execute([':student_id' => $student_id]);
    $mapping_info = $stmt_map->fetch(PDO::FETCH_ASSOC);

    if ($mapping_info) {
        $info_loaded = true;

        // Hitung total hari kerja (Senin-Jumat) dalam periode PKL
        $start = new DateTime($mapping_info['start_date']);
        $end = new DateTime($mapping_info['end_date']);
        $end->modify('+1 day'); // Include the end date
        $interval = new DateInterval('P1D');
        $date_range = new DatePeriod($start, $interval, $end);
        $total_work_days = 0;
        foreach ($date_range as $date) {
            if ($date->format('N') < 6) { // 1 (Mon) to 5 (Fri)
                $total_work_days++;
            }
        }

        // 2. Ambil statistik jurnal
        $stmt_journals = $pdo->prepare("
            SELECT
                COUNT(*) as total_sent,
                SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as total_approved,
                COUNT(DISTINCT journal_date) as attendance_days
            FROM internship_journals
            WHERE student_id = :student_id
        ");
        $stmt_journals->execute([':student_id' => $student_id]);
        $journal_stats = $stmt_journals->fetch(PDO::FETCH_ASSOC);

        // 3. Hitung persentase
        $attendance_percentage = ($total_work_days > 0) ? ($journal_stats['attendance_days'] / $total_work_days) * 100 : 0;
        $attendance_percentage = min(100, round($attendance_percentage)); // Cap at 100%
    }

} catch (PDOException $e) {
    die("Error: Could not fetch dashboard data. " . $e->getMessage());
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Dashboard Siswa</h1>

    <?php if ($info_loaded): ?>
    <!-- Info Cards -->
    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header bg-primary text-white">
                    <h6 class="m-0 font-weight-bold"><i class="fas fa-info-circle me-2"></i>Informasi Pembimbing</h6>
                </div>
                <div class="card-body">
                    <p><strong>Guru Pembimbing:</strong> <?php echo htmlspecialchars($mapping_info['teacher_name']); ?></p>
                    <p class="mb-0"><strong>Instruktur DUDIKA:</strong> <?php echo htmlspecialchars($mapping_info['instructor_name'] . ' (' . $mapping_info['company_name'] . ')'); ?></p>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header bg-success text-white">
                    <h6 class="m-0 font-weight-bold"><i class="fas fa-calendar-alt me-2"></i>Periode PKL</h6>
                </div>
                <div class="card-body">
                     <p><strong>Tanggal Mulai:</strong> <?php echo date('d M Y', strtotime($mapping_info['start_date'])); ?></p>
                     <p class="mb-0"><strong>Tanggal Selesai:</strong> <?php echo date('d M Y', strtotime($mapping_info['end_date'])); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Progress Cards -->
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card shadow">
                <div class="card-body">
                    <h6 class="card-title text-primary">Kehadiran</h6>
                    <div class="progress mb-2">
                        <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $attendance_percentage; ?>%" aria-valuenow="<?php echo $attendance_percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <p class="card-text text-center fw-bold fs-5"><?php echo $attendance_percentage; ?>% <span class="fs-6 fw-normal">(<?php echo $journal_stats['attendance_days']; ?> dari <?php echo $total_work_days; ?> hari)</span></p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card shadow">
                <div class="card-body">
                    <h6 class="card-title text-primary">Jurnal Terkirim</h6>
                    <p class="card-text text-center fw-bold fs-3"><?php echo $journal_stats['total_sent']; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card shadow">
                <div class="card-body">
                    <h6 class="card-title text-primary">Jurnal Terverifikasi</h6>
                    <p class="card-text text-center fw-bold fs-3 text-success"><?php echo $journal_stats['total_approved']; ?></p>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="alert alert-warning text-center">
        <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
        <h4>Data PKL Belum Lengkap</h4>
        <p>Anda belum di-mapping ke Guru Pembimbing atau Instruktur DUDIKA. Silakan hubungi Admin Sekolah.</p>
    </div>
    <?php endif; ?>

</div>

<?php
require_once __DIR__ . '/templates/footer.php';
?>