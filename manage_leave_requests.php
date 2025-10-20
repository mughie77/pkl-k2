<?php
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/sidebar.php';

// Proteksi halaman
if ($_SESSION['user_role'] !== 'instructor') {
    header("Location: login.php");
    exit;
}

$instructor_id = $_SESSION['user_id'];

try {
    // Ambil pengajuan yang menunggu persetujuan dari siswa bimbingan instruktur ini
    $stmt = $pdo->prepare("
        SELECT
            lr.id, lr.leave_type, lr.start_date, lr.end_date, lr.leave_reason,
            s.student_name
        FROM leave_requests lr
        JOIN students s ON lr.student_id = s.id
        WHERE lr.instructor_id = :instructor_id AND lr.leave_status = 'Pending'
        ORDER BY lr.created_at ASC
    ");
    $stmt->execute([':instructor_id' => $instructor_id]);
    $pending_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error: Could not fetch leave requests. " . $e->getMessage());
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Manajemen Pengajuan Izin/Cuti</h1>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['flash_message']['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['flash_message']['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Pengajuan Menunggu Persetujuan</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Siswa</th>
                            <th>Jenis</th>
                            <th>Periode</th>
                            <th>Alasan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($pending_requests) > 0): ?>
                            <?php foreach ($pending_requests as $request): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($request['student_name']); ?></td>
                                    <td><?php echo htmlspecialchars($request['leave_type']); ?></td>
                                    <td><?php echo date('d M Y', strtotime($request['start_date'])) . ' - ' . date('d M Y', strtotime($request['end_date'])); ?></td>
                                    <td><?php echo nl2br(htmlspecialchars($request['leave_reason'])); ?></td>
                                    <td>
                                        <form action="core/leave_actions.php" method="POST" class="d-inline">
                                            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                            <button type="submit" name="action" value="approve_leave" class="btn btn-success btn-sm mb-1">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                        </form>
                                        <form action="core/leave_actions.php" method="POST" class="d-inline">
                                            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                            <button type="submit" name="action" value="reject_leave" class="btn btn-danger btn-sm">
                                                <i class="fas fa-times"></i> Reject
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">Tidak ada pengajuan yang menunggu persetujuan.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/templates/footer.php';
?>