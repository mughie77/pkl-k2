<?php
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/sidebar.php';

// Proteksi halaman
if ($_SESSION['user_role'] !== 'waka_humas') {
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

<div class="container-fluid waka-humas-dashboard student-dashboard">
    <!-- Header Dashboard -->
    <div class="dashboard-header card p-3 mb-4 shadow-sm">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
            <div class="text-center text-md-start">
                <p class="text-muted mb-0"><?php echo date('d M Y'); ?></p>
                <h5 class="mb-1"><?php echo $greeting; ?>!</h5>
                <h3 class="fw-bold mb-0"><?php echo htmlspecialchars($waka_name); ?></h3>
            </div>
            <div class="text-center text-md-end mt-3 mt-md-0">
                <p class="text-muted mb-0">Peran</p>
                <h5 class="fw-bold mb-0">Waka Humas</h5>
            </div>
        </div>
    </div>

    <!-- Menu Ikon -->
    <div class="row row-cols-2 row-cols-md-4 text-center g-3 mb-4">
        <div class="col">
            <a href="rekap_absensi_waka.php" class="icon-menu-item">
                <div class="icon-circle bg-primary text-white"><i class="fas fa-calendar-check"></i></div>
                <span class="icon-label">Rekap Absensi</span>
            </a>
        </div>
        <div class="col">
            <a href="manage_dudika_locations.php" class="icon-menu-item">
                <div class="icon-circle bg-success text-white"><i class="fas fa-map-marked-alt"></i></div>
                <span class="icon-label">Penitikan Lokasi</span>
            </a>
        </div>
        <div class="col">
            <a href="direktori_dudika.php" class="icon-menu-item">
                <div class="icon-circle bg-info text-white"><i class="fas fa-address-book"></i></div>
                <span class="icon-label">Direktori DUDIKA</span>
            </a>
        </div>
        <div class="col">
            <a href="waka_view_problems.php" class="icon-menu-item">
                <div class="icon-circle bg-danger text-white"><i class="fas fa-exclamation-triangle"></i></div>
                <span class="icon-label">Permasalahan Siswa</span>
            </a>
        </div>
    </div>

    <!-- Informasi Tambahan atau Statistik bisa ditambahkan di sini -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h6 class="m-0 font-weight-bold text-primary">Selamat Datang di Dashboard Waka Humas</h6>
        </div>
        <div class="card-body">
            <p>Gunakan menu di atas untuk mengakses fitur yang tersedia. Anda dapat memonitor seluruh data absensi siswa, mengelola lokasi DUDIKA, melihat direktori kontak, dan meninjau permasalahan siswa secara terpusat.</p>
        </div>
    </div>
</div>

<?php
if (in_array($_SESSION['user_role'], ['student', 'teacher', 'instructor'])) {
    echo '</div>';
}
require_once __DIR__ . '/templates/footer.php';
?>