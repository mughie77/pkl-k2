<?php
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/sidebar.php';

// Proteksi halaman
if ($_SESSION['user_role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$student_id = $_SESSION['user_id'];
$leave_type_default = $_GET['type'] ?? 'Izin';

try {
    // Ambil riwayat pengajuan
    $stmt = $pdo->prepare("SELECT * FROM leave_requests WHERE student_id = :student_id ORDER BY created_at DESC");
    $stmt->execute([':student_id' => $student_id]);
    $leave_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error fetching leave requests: " . $e->getMessage());
}

function get_status_badge($status) {
    switch ($status) {
        case 'Approved': return 'bg-success';
        case 'Rejected': return 'bg-danger';
        case 'Pending': return 'bg-warning text-dark';
        default: return 'bg-secondary';
    }
}
?>

<div class="container-fluid content-wrapper student-view">
    <h1 class="h3 mb-4 text-gray-800">Pengajuan Izin / Hari Libur</h1>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['flash_message']['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['flash_message']['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <!-- Form Pengajuan -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Form Pengajuan</h6>
        </div>
        <div class="card-body">
            <form action="core/leave_actions.php" method="POST">
                <input type="hidden" name="action" value="request_leave">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="leave_type" class="form-label">Jenis Pengajuan</label>
                        <select name="leave_type" id="leave_type" class="form-select">
                            <option value="Izin" <?php echo ($leave_type_default === 'Izin') ? 'selected' : ''; ?>>Izin</option>
                            <option value="Sakit" <?php echo ($leave_type_default === 'Sakit') ? 'selected' : ''; ?>>Sakit</option>
                            <option value="Hari Libur" <?php echo ($leave_type_default === 'Hari Libur') ? 'selected' : ''; ?>>Hari Libur</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="start_date" class="form-label">Tanggal Mulai</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="end_date" class="form-label">Tanggal Selesai</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="reason" class="form-label">Alasan</label>
                    <textarea name="reason" id="reason" class="form-control" rows="4" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Kirim Pengajuan</button>
            </form>
        </div>
    </div>

    <!-- Riwayat Pengajuan -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Riwayat Pengajuan Anda</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Jenis</th>
                            <th>Periode</th>
                            <th>Alasan</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($leave_history) > 0): ?>
                            <?php foreach ($leave_history as $request): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($request['leave_type']); ?></td>
                                    <td><?php echo date('d M Y', strtotime($request['start_date'])) . ' - ' . date('d M Y', strtotime($request['end_date'])); ?></td>
                                    <td><?php echo nl2br(htmlspecialchars($request['reason'])); ?></td>
                                    <td>
                                        <span class="badge <?php echo get_status_badge($request['status']); ?>">
                                            <?php echo htmlspecialchars($request['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center">Belum ada riwayat pengajuan.</td>
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
?>
<script>
$(document).ready(function() {
    $('#leave_type').select2({
        theme: 'bootstrap-5',
        minimumResultsForSearch: Infinity // Sembunyikan search box karena hanya ada 2 pilihan
    });
});
</script>
<?php
require_once __DIR__ . '/templates/footer.php';
?>