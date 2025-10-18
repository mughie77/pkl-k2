<?php
require_once __DIR__ . '/templates/header.php';

// Proteksi halaman
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'waka_humas') {
    header("Location: login.php");
    exit;
}

$waka_name = $_SESSION['user_name'];

// Logika Sapaan Dinamis
$hour = date('H');
$greeting = 'Selamat Pagi';
if ($hour >= 12) $greeting = 'Selamat Siang';
if ($hour >= 15) $greeting = 'Selamat Sore';
if ($hour >= 18) $greeting = 'Selamat Malam';
?>

<div class="container-fluid student-dashboard">
    <!-- Header Dashboard -->
    <div class="dashboard-header card p-3 mb-4 shadow-sm">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <p class="text-muted mb-0"><?php echo date('d M Y'); ?></p>
                <h5 class="mb-1"><?php echo $greeting; ?>!</h5>
                <h3 class="fw-bold mb-0"><?php echo htmlspecialchars($waka_name); ?></h3>
            </div>
            <div class="text-end">
                <p class="text-muted mb-0">Peran</p>
                <h5 class="fw-bold mb-0">Waka Humas</h5>
            </div>
        </div>
    </div>

    <!-- Menu Ikon -->
    <div class="row row-cols-2 row-cols-md-4 text-center g-3 mb-4">
        <div class="col">
            <a href="manage_dudika_locations.php" class="icon-menu-item">
                <div class="icon-circle bg-primary text-white"><i class="fas fa-map-marked-alt"></i></div>
                <span class="icon-label">Lokasi DUDIKA</span>
            </a>
        </div>
        <div class="col">
            <a href="mapping_report.php" class="icon-menu-item">
                <div class="icon-circle bg-info text-white"><i class="fas fa-sitemap"></i></div>
                <span class="icon-label">Data Pemetaan</span>
            </a>
        </div>
        <div class="col">
            <a href="global_recap.php" class="icon-menu-item">
                <div class="icon-circle bg-success text-white"><i class="fas fa-clipboard-list"></i></div>
                <span class="icon-label">Rekap Absensi</span>
            </a>
        </div>
        <div class="col">
            <a href="problem_report.php" class="icon-menu-item">
                <div class="icon-circle bg-danger text-white"><i class="fas fa-exclamation-triangle"></i></div>
                <span class="icon-label">Masalah Siswa</span>
            </a>
        </div>
        <div class="col">
            <a href="assessment_recap.php" class="icon-menu-item">
                <div class="icon-circle bg-warning text-dark"><i class="fas fa-graduation-cap"></i></div>
                <span class="icon-label">Rekap Skor</span>
            </a>
        </div>
    </div>

    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        Selamat datang di dasbor Waka Humas. Gunakan menu di atas atau di bawah untuk navigasi.
    </div>

    <div class="mt-4 d-grid">
        <a href="logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
    </div>

</div>

<?php
// Penutup div dari header.php dan pemanggilan footer
require_once __DIR__ . '/templates/footer.php';
?>