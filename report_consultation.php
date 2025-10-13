<?php
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/sidebar.php';

// Proteksi halaman
if ($_SESSION['user_role'] !== 'teacher') {
    header("Location: login.php");
    exit;
}

$teacher_id = $_SESSION['user_id'];

try {
    // Ambil data laporan yang diunggah oleh siswa bimbingan guru ini
    $stmt = $pdo->prepare("
        SELECT
            rc.id, rc.file_path, rc.feedback, rc.upload_date,
            s.name as student_name
        FROM report_consultations rc
        JOIN students s ON rc.student_id = s.id
        WHERE rc.teacher_id = :teacher_id
        ORDER BY rc.upload_date DESC
    ");
    $stmt->execute([':teacher_id' => $teacher_id]);
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error: Could not fetch report data. " . $e->getMessage());
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Konsultasi Laporan PKL</h1>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['flash_message']['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['flash_message']['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Laporan Unggahan Siswa</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Tanggal Unggah</th>
                            <th>Nama Siswa</th>
                            <th>File Laporan</th>
                            <th>Feedback Anda</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($reports) > 0): ?>
                            <?php foreach ($reports as $report): ?>
                                <tr>
                                    <td><?php echo date('d M Y, H:i', strtotime($report['upload_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($report['student_name']); ?></td>
                                    <td>
                                        <a href="<?php echo BASE_URL . '/' . htmlspecialchars($report['file_path']); ?>" target="_blank">
                                            <i class="fas fa-file-download"></i> <?php echo basename($report['file_path']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <p><?php echo nl2br(htmlspecialchars($report['feedback'] ?? 'Belum ada feedback.')); ?></p>
                                    </td>
                                    <td>
                                        <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#feedbackModal"
                                                data-report-id="<?php echo $report['id']; ?>"
                                                data-current-feedback="<?php echo htmlspecialchars($report['feedback'] ?? ''); ?>">
                                            <i class="fas fa-edit"></i> Beri/Edit Feedback
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">Belum ada laporan yang diunggah oleh siswa bimbingan Anda.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk Memberi/Edit Feedback -->
<div class="modal fade" id="feedbackModal" tabindex="-1" aria-labelledby="feedbackModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="feedbackModalLabel">Form Feedback Laporan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="core/consultation_actions.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="give_feedback">
                    <input type="hidden" name="report_id" id="report_id">
                    <div class="mb-3">
                        <label for="feedback" class="form-label">Catatan Feedback</label>
                        <textarea class="form-control" name="feedback" id="feedback" rows="6" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Simpan Feedback</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const feedbackModal = document.getElementById('feedbackModal');
    feedbackModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const reportId = button.dataset.reportId;
        const currentFeedback = button.dataset.currentFeedback;

        const reportIdInput = feedbackModal.querySelector('#report_id');
        const feedbackTextarea = feedbackModal.querySelector('#feedback');

        reportIdInput.value = reportId;
        feedbackTextarea.value = currentFeedback;
    });
});
</script>

<?php
require_once __DIR__ . '/templates/footer.php';
?>