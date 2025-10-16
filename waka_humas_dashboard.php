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

<?php
try {
    $stmt_companies = $pdo->query("SELECT name, contact_person, address FROM companies ORDER BY name ASC");
    $all_companies = $stmt_companies->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching company data: " . $e->getMessage());
}
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

    <!-- Daftar DUDIKA -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h6 class="m-0 font-weight-bold text-primary">Daftar DUDIKA Terdaftar</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Nama DUDIKA</th>
                            <th>Alamat</th>
                            <th>Narahubung</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($all_companies) > 0): ?>
                            <?php foreach ($all_companies as $company): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($company['name']); ?></td>
                                    <td><?php echo htmlspecialchars($company['address']); ?></td>
                                    <td><?php echo htmlspecialchars($company['contact_person']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center p-4">Belum ada data DUDIKA yang terdaftar.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
if (in_array($_SESSION['user_role'], ['student', 'teacher', 'instructor'])) {
    echo '</div>';
}
require_once __DIR__ . '/templates/footer.php';
?>