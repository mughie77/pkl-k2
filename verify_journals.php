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
    // Base query to get all journals for the instructor's students
    $sql = "
        SELECT
            j.id, j.journal_date, j.check_in_time, j.check_out_time, j.activities, j.status,
            s.name as student_name
        FROM internship_journals j
        JOIN internship_mappings m ON j.student_id = m.student_id
        JOIN students s ON j.student_id = s.id
        WHERE m.instructor_id = :instructor_id
    ";

    $params = [':instructor_id' => $instructor_id];

    // Add search functionality
    if (!empty($search_query)) {
        $sql .= " AND s.name LIKE :search_query";
        $params[':search_query'] = '%' . $search_query . '%';
    }

    $sql .= " ORDER BY j.journal_date DESC, s.name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $journals = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error: Could not fetch journals data. " . $e->getMessage());
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Log & Verifikasi Jurnal Harian</h1>

    <!-- Search Form -->
    <div class="card shadow mb-4">
        <div class="card-header">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-search me-2"></i>Cari Jurnal Siswa</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="verify_journals.php" class="row g-3 align-items-end">
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
            <h6 class="m-0 font-weight-bold text-primary">Log Jurnal Siswa</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Nama Siswa</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($journals) > 0): ?>
                            <?php foreach ($journals as $journal): ?>
                                <tr>
                                    <td><?php echo date('d M Y', strtotime($journal['journal_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($journal['student_name']); ?></td>
                                    <td><?php echo $journal['check_in_time'] ? date('H:i', strtotime($journal['check_in_time'])) : '-'; ?></td>
                                    <td><?php echo $journal['check_out_time'] ? date('H:i', strtotime($journal['check_out_time'])) : '-'; ?></td>
                                    <td>
                                        <?php
                                        $status_badge = 'secondary';
                                        if ($journal['status'] === 'Approved') {
                                            $status_badge = 'success';
                                        } elseif ($journal['status'] === 'Rejected') {
                                            $status_badge = 'danger';
                                        }
                                        ?>
                                        <span class="badge bg-<?php echo $status_badge; ?>"><?php echo htmlspecialchars($journal['status']); ?></span>
                                    </td>
                                    <td>
                                        <button class="btn btn-info btn-sm view-btn"
                                                data-id="<?php echo $journal['id']; ?>"
                                                data-student="<?php echo htmlspecialchars($journal['student_name']); ?>"
                                                data-date="<?php echo date('d M Y', strtotime($journal['journal_date'])); ?>"
                                                data-checkin="<?php echo $journal['check_in_time'] ? date('H:i', strtotime($journal['check_in_time'])) : 'N/A'; ?>"
                                                data-checkout="<?php echo $journal['check_out_time'] ? date('H:i', strtotime($journal['check_out_time'])) : 'N/A'; ?>"
                                                data-activities="<?php echo htmlspecialchars($journal['activities']); ?>"
                                                data-status="<?php echo $journal['status']; ?>"
                                                data-bs-toggle="modal" data-bs-target="#journalModal">
                                            <i class="fas fa-eye me-1"></i>
                                            <?php echo ($journal['status'] === 'Pending') ? 'Detail & Aksi' : 'Lihat Detail'; ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">
                                    <?php if (!empty($search_query)): ?>
                                        Tidak ada jurnal yang cocok dengan pencarian "<?php echo htmlspecialchars($search_query); ?>".
                                    <?php else: ?>
                                        Belum ada jurnal yang tercatat.
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

<!-- Modal untuk Detail dan Verifikasi Jurnal -->
<div class="modal fade" id="journalModal" tabindex="-1" aria-labelledby="journalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="journalModalLabel">Detail Jurnal Harian</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6 class="mb-3">Siswa: <span id="modal_student_name" class="fw-normal"></span></h6>
                <p><strong>Tanggal:</strong> <span id="modal_journal_date"></span></p>
                <p><strong>Waktu:</strong> <span id="modal_check_in_time"></span> - <span id="modal_check_out_time"></span></p>
                <hr>
                <h6><strong>Deskripsi Kegiatan:</strong></h6>
                <p id="modal_activities" style="white-space: pre-wrap;"></p>
            </div>
            <div class="modal-footer" id="modal_footer_actions">
                <form action="core/journal_actions.php" method="POST" class="d-inline">
                    <input type="hidden" name="journal_id" id="modal_journal_id">
                    <input type="hidden" name="action" value="reject">
                    <button type="submit" class="btn btn-danger"><i class="fas fa-times me-1"></i> Tolak</button>
                </form>
                <form action="core/journal_actions.php" method="POST" class="d-inline">
                    <input type="hidden" name="journal_id" id="modal_journal_id_approve">
                    <input type="hidden" name="action" value="approve">
                    <button type="submit" class="btn btn-success"><i class="fas fa-check me-1"></i> Setujui</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const journalModal = document.getElementById('journalModal');
    const modalFooterActions = document.getElementById('modal_footer_actions');

    journalModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;

        // Populate modal with data
        document.getElementById('modal_student_name').textContent = button.dataset.student;
        document.getElementById('modal_journal_date').textContent = button.dataset.date;
        document.getElementById('modal_check_in_time').textContent = button.dataset.checkin;
        document.getElementById('modal_check_out_time').textContent = button.dataset.checkout;
        document.getElementById('modal_activities').textContent = button.dataset.activities;
        document.getElementById('modal_journal_id').value = button.dataset.id;
        document.getElementById('modal_journal_id_approve').value = button.dataset.id;

        // Show/hide action buttons based on status
        if (button.dataset.status === 'Pending') {
            modalFooterActions.style.display = 'block';
        } else {
            modalFooterActions.style.display = 'none';
        }
    });
});
</script>

<?php
if (in_array($_SESSION['user_role'], ['student', 'teacher', 'instructor'])) {
    echo '</div>';
}
require_once __DIR__ . '/templates/footer.php';
?>