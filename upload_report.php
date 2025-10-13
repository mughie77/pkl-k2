<?php
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/sidebar.php';

// Proteksi halaman
if ($_SESSION['user_role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$student_id = $_SESSION['user_id'];

try {
    // Ambil riwayat unggahan laporan oleh siswa ini
    $stmt = $pdo->prepare("
        SELECT id, file_path, feedback, upload_date
        FROM report_consultations
        WHERE student_id = :student_id
        ORDER BY upload_date DESC
    ");
    $stmt->execute([':student_id' => $student_id]);
    $uploaded_reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Cek apakah siswa sudah di-mapping untuk mengaktifkan/menonaktifkan form
    $map_stmt = $pdo->prepare("SELECT COUNT(*) FROM internship_mappings WHERE student_id = :student_id");
    $map_stmt->execute(['student_id' => $student_id]);
    $is_mapped = $map_stmt->fetchColumn() > 0;

} catch (PDOException $e) {
    die("Error: Could not fetch report data. " . $e->getMessage());
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Unggah Draf Laporan</h1>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['flash_message']['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['flash_message']['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <!-- Form Unggah Laporan -->
    <div class="card shadow mb-4">
        <div class="card-header bg-primary text-white">
            <h6 class="m-0 font-weight-bold"><i class="fas fa-upload me-2"></i>Form Unggah</h6>
        </div>
        <div class="card-body">
            <?php if ($is_mapped): ?>
            <form action="core/consultation_actions.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload_report">
                <div class="mb-3">
                    <label for="report_file" class="form-label">Pilih File Laporan (PDF, DOC, DOCX - Max 5MB)</label>
                    <input class="form-control" type="file" id="report_file" name="report_file" required>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane me-2"></i>Unggah File</button>
            </form>
            <?php else: ?>
            <div class="alert alert-warning">Anda tidak dapat mengunggah laporan karena data PKL Anda belum di-mapping oleh admin.</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Riwayat Unggahan -->
    <div class="card shadow mb-4">
        <div class="card-header">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-history me-2"></i>Riwayat Unggahan Laporan</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Tanggal Unggah</th>
                            <th>File</th>
                            <th>Feedback dari Guru</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($uploaded_reports) > 0): ?>
                            <?php foreach ($uploaded_reports as $report): ?>
                                <tr>
                                    <td><?php echo date('d M Y, H:i', strtotime($report['upload_date'])); ?></td>
                                    <td>
                                        <a href="<?php echo BASE_URL . '/' . htmlspecialchars($report['file_path']); ?>" target="_blank">
                                            <i class="fas fa-file-alt"></i> <?php echo basename($report['file_path']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php if (!empty($report['feedback'])): ?>
                                            <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#feedbackViewModal" data-feedback="<?php echo htmlspecialchars($report['feedback']); ?>">
                                                <i class="fas fa-eye"></i> Lihat Feedback
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted">Belum ada feedback</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center">Anda belum pernah mengunggah laporan.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk Melihat Feedback -->
<div class="modal fade" id="feedbackViewModal" tabindex="-1" aria-labelledby="feedbackViewModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="feedbackViewModalLabel">Feedback dari Guru Pembimbing</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="feedback_content"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const feedbackModal = document.getElementById('feedbackViewModal');
    feedbackModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const feedback = button.dataset.feedback;
        const feedbackContent = feedbackModal.querySelector('#feedback_content');
        feedbackContent.textContent = feedback;
    });
});
</script>

<?php
require_once __DIR__ . '/templates/footer.php';
?>