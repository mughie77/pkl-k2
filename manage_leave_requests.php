<?php
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/sidebar.php';

// Proteksi halaman
if ($_SESSION['user_role'] !== 'instructor') {
    header("Location: login.php");
    exit;
}

$instructor_id = $_SESSION['user_id'];
$search_query = $_GET['search'] ?? '';

try {
    // Base query to get all leave requests for the instructor's students
    $sql = "
        SELECT
            lr.id, lr.leave_type, lr.start_date, lr.end_date, lr.reason, lr.status,
            s.name as student_name
        FROM leave_requests lr
        JOIN students s ON lr.student_id = s.id
        WHERE lr.instructor_id = :instructor_id
    ";

    $params = [':instructor_id' => $instructor_id];

    // Add search functionality
    if (!empty($search_query)) {
        $sql .= " AND s.name LIKE :search_query";
        $params[':search_query'] = '%' . $search_query . '%';
    }

    $sql .= " ORDER BY lr.created_at DESC, s.name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $leave_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error: Could not fetch leave requests. " . $e->getMessage());
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Log & Manajemen Pengajuan Izin</h1>

    <!-- Search Form -->
    <div class="card shadow mb-4">
        <div class="card-header">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-search me-2"></i>Cari Pengajuan Izin Siswa</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="manage_leave_requests.php" class="row g-3 align-items-end">
                <div class="col-md-10">
                    <label for="search" class="form-label">Nama Siswa</label>
                    <input type="text" name="search" id="search" class="form-control" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Ketik nama siswa untuk mencari...">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Cari</button>
                </div>
            </form>
        </div>
    </div>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['flash_message']['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['flash_message']['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Log Pengajuan Izin</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Siswa</th>
                            <th>Jenis</th>
                            <th>Periode</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($leave_requests) > 0): ?>
                            <?php foreach ($leave_requests as $request): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($request['student_name']); ?></td>
                                    <td><span class="badge bg-info"><?php echo htmlspecialchars($request['leave_type']); ?></span></td>
                                    <td><?php echo date('d M Y', strtotime($request['start_date'])) . ' - ' . date('d M Y', strtotime($request['end_date'])); ?></td>
                                    <td>
                                        <?php
                                        $status_badge = 'secondary';
                                        if ($request['status'] === 'Approved') {
                                            $status_badge = 'success';
                                        } elseif ($request['status'] === 'Rejected') {
                                            $status_badge = 'danger';
                                        }
                                        ?>
                                        <span class="badge bg-<?php echo $status_badge; ?>"><?php echo htmlspecialchars($request['status']); ?></span>
                                    </td>
                                    <td>
                                        <button class="btn btn-info btn-sm view-reason-btn" data-bs-toggle="modal" data-bs-target="#reasonModal" data-reason="<?php echo htmlspecialchars($request['reason']); ?>">
                                            <i class="fas fa-eye"></i> Lihat Alasan
                                        </button>
                                        <?php if ($request['status'] === 'Pending'): ?>
                                            <form action="core/leave_actions.php" method="POST" class="d-inline">
                                                <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                <button type="submit" name="action" value="approve_leave" class="btn btn-success btn-sm ms-1">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                            <form action="core/leave_actions.php" method="POST" class="d-inline">
                                                <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                <button type="submit" name="action" value="reject_leave" class="btn btn-danger btn-sm">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">
                                    <?php if (!empty($search_query)): ?>
                                        Tidak ada pengajuan yang cocok dengan pencarian "<?php echo htmlspecialchars($search_query); ?>".
                                    <?php else: ?>
                                        Belum ada pengajuan izin yang tercatat.
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal to Display Reason -->
<div class="modal fade" id="reasonModal" tabindex="-1" aria-labelledby="reasonModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reasonModalLabel">Alasan Pengajuan Izin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="modal_reason_text" style="white-space: pre-wrap;"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const reasonModal = document.getElementById('reasonModal');
    reasonModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const reason = button.getAttribute('data-reason');
        document.getElementById('modal_reason_text').textContent = reason;
    });
});
</script>

<?php
require_once __DIR__ . '/templates/footer.php';
?>