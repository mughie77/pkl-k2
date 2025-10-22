<?php
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/sidebar.php';

// Proteksi halaman
if ($_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$holidays = [];
try {
    $stmt = $pdo->query("SELECT id, holiday_date, description FROM holidays ORDER BY holiday_date DESC");
    $holidays = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Abaikan jika tabel belum ada
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Manajemen Hari Libur</h1>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['flash_message']['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['flash_message']['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <!-- Form Tambah Hari Libur -->
    <div class="card shadow mb-4">
        <div class="card-header">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-plus-circle me-2"></i>Tambah Hari Libur Baru</h6>
        </div>
        <div class="card-body">
            <form action="core/holiday_actions.php" method="POST" class="row g-3 align-items-end">
                <input type="hidden" name="action" value="add_holiday">
                <div class="col-md-4">
                    <label for="holiday_date" class="form-label">Tanggal</label>
                    <input type="date" class="form-control" id="holiday_date" name="holiday_date" required>
                </div>
                <div class="col-md-6">
                    <label for="description" class="form-label">Keterangan</label>
                    <input type="text" class="form-control" id="description" name="description" placeholder="Contoh: Hari Kemerdekaan" required>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Tambah</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Hari Libur -->
    <div class="card shadow mb-4">
        <div class="card-header">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-calendar-alt me-2"></i>Daftar Hari Libur</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Keterangan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($holidays) > 0): ?>
                            <?php foreach ($holidays as $holiday): ?>
                                <tr>
                                    <td><?php echo date('d M Y', strtotime($holiday['holiday_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($holiday['description']); ?></td>
                                    <td>
                                        <form action="core/holiday_actions.php" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus hari libur ini?');">
                                            <input type="hidden" name="action" value="delete_holiday">
                                            <input type="hidden" name="holiday_id" value="<?php echo $holiday['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                <i class="fas fa-trash"></i> Hapus
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center">Belum ada data hari libur.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
