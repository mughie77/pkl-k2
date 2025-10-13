<?php
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/sidebar.php';

// Proteksi halaman
if ($_SESSION['user_role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$student_id = $_SESSION['user_id'];
$today = date('Y-m-d');

try {
    // Cek jurnal untuk hari ini
    $stmt_today = $pdo->prepare("SELECT * FROM internship_journals WHERE student_id = :student_id AND journal_date = :today");
    $stmt_today->execute([':student_id' => $student_id, ':today' => $today]);
    $today_journal = $stmt_today->fetch(PDO::FETCH_ASSOC);

    // Ambil riwayat jurnal
    $stmt_history = $pdo->prepare("SELECT * FROM internship_journals WHERE student_id = :student_id ORDER BY journal_date DESC");
    $stmt_history->execute([':student_id' => $student_id]);
    $journal_history = $stmt_history->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error: Could not fetch journal data. " . $e->getMessage());
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

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Jurnal Harian</h1>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['flash_message']['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['flash_message']['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <!-- Form Jurnal Hari Ini -->
    <div class="card shadow mb-4">
        <div class="card-header bg-primary text-white">
            <h6 class="m-0 font-weight-bold"><i class="fas fa-calendar-day me-2"></i>Jurnal untuk Tanggal: <?php echo date('d M Y'); ?></h6>
        </div>
        <div class="card-body">
            <form action="core/journal_actions.php" method="POST">
                <div class="row align-items-center mb-3">
                    <div class="col-md-auto">
                        <strong>Absensi:</strong>
                    </div>
                    <div class="col-md-auto">
                        <?php if (empty($today_journal)): ?>
                            <button type="submit" name="action" value="check_in" class="btn btn-success"><i class="fas fa-play-circle me-2"></i>Check-in</button>
                        <?php else: ?>
                            <button type="button" class="btn btn-success disabled"><i class="fas fa-check-circle me-2"></i>Checked-in at <?php echo date('H:i', strtotime($today_journal['check_in_time'])); ?></button>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-auto">
                         <?php if (!empty($today_journal) && empty($today_journal['check_out_time'])): ?>
                            <button type="submit" name="action" value="check_out" class="btn btn-danger"><i class="fas fa-stop-circle me-2"></i>Check-out</button>
                        <?php elseif(!empty($today_journal) && !empty($today_journal['check_out_time'])): ?>
                            <button type="button" class="btn btn-danger disabled"><i class="fas fa-check-circle me-2"></i>Checked-out at <?php echo date('H:i', strtotime($today_journal['check_out_time'])); ?></button>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="activities" class="form-label"><strong>Deskripsi Kegiatan Harian:</strong></label>
                    <textarea name="activities" id="activities" class="form-control" rows="8" placeholder="Jelaskan kegiatan yang Anda lakukan hari ini..." <?php echo empty($today_journal) ? 'disabled' : ''; ?>><?php echo htmlspecialchars($today_journal['activities'] ?? ''); ?></textarea>
                </div>

                <button type="submit" name="action" value="submit_journal" class="btn btn-primary w-100" <?php echo empty($today_journal) ? 'disabled' : ''; ?>>
                    <i class="fas fa-paper-plane me-2"></i> Kirim Jurnal
                </button>
                <?php if (empty($today_journal)): ?>
                    <small class="form-text text-muted">Anda harus Check-in terlebih dahulu untuk dapat mengisi dan mengirim jurnal.</small>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Riwayat Jurnal -->
    <div class="card shadow mb-4">
        <div class="card-header">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-history me-2"></i>Riwayat Jurnal Anda</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($journal_history) > 0): ?>
                            <?php foreach ($journal_history as $journal): ?>
                                <tr>
                                    <td><?php echo date('d M Y', strtotime($journal['journal_date'])); ?></td>
                                    <td><?php echo $journal['check_in_time'] ? date('H:i', strtotime($journal['check_in_time'])) : '-'; ?></td>
                                    <td><?php echo $journal['check_out_time'] ? date('H:i', strtotime($journal['check_out_time'])) : '-'; ?></td>
                                    <td>
                                        <span class="badge <?php echo get_status_badge($journal['status']); ?>">
                                            <?php echo htmlspecialchars($journal['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-info btn-sm view-journal-btn"
                                                data-activities="<?php echo htmlspecialchars($journal['activities']); ?>"
                                                data-bs-toggle="modal" data-bs-target="#viewJournalModal">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">Belum ada riwayat jurnal.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk Melihat Jurnal -->
<div class="modal fade" id="viewJournalModal" tabindex="-1" aria-labelledby="viewJournalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewJournalModalLabel">Detail Kegiatan Harian</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="journal_activities_content" style="white-space: pre-wrap;"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const viewJournalModal = document.getElementById('viewJournalModal');
    viewJournalModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const activities = button.dataset.activities;
        const modalBody = viewJournalModal.querySelector('#journal_activities_content');
        modalBody.textContent = activities;
    });
});
</script>

<?php
require_once __DIR__ . '/templates/footer.php';
?>