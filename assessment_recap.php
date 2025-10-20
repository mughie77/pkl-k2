<?php
require_once __DIR__ . '/templates/header.php';

// Proteksi halaman
$allowed_roles = ['waka_humas', 'admin'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    header("Location: login.php");
    exit;
}

// Validasi sesi tahun ajaran
if (!isset($_SESSION['selected_academic_year_id'])) {
    $_SESSION['error_message'] = "Silakan pilih tahun ajaran terlebih dahulu.";
    $dashboard = ($_SESSION['user_role'] === 'admin') ? 'admin_dashboard.php' : 'waka_humas_dashboard.php';
    header("Location: $dashboard");
    exit;
}
$academic_year_id = $_SESSION['selected_academic_year_id'];

try {
    // Query yang diperbaiki: JOIN dengan internship_mappings dan filter academic_year_id dari sana
    $stmt = $pdo->prepare("
        SELECT
            s.id as student_id, s.student_name, pk.program_name,
            a.score_1, a.score_2, a.score_3, a.score_4, a.notes,
            (a.score_1 + a.score_2 + a.score_3 + a.score_4) / 4 as average_score,
            i.instructor_name
        FROM internship_assessments a
        JOIN students s ON a.student_id = s.id
        JOIN internship_mappings m ON s.id = m.student_id
        JOIN kelas k ON s.kelas_id = k.id
        JOIN konsentrasi_keahlian kk ON k.konsentrasi_id = kk.id
        JOIN program_keahlian pk ON kk.program_id = pk.id
        JOIN instructors i ON a.instructor_id = i.id
        WHERE m.academic_year_id = :academic_year_id
        ORDER BY s.student_name ASC
    ");
    $stmt->execute([':academic_year_id' => $academic_year_id]);
    $assessments = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error fetching assessment data: " . $e->getMessage());
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Rekapitulasi Skor Siswa</h1>
        <a href="core/export_handler.php?type=rekap_nilai" class="btn btn-success">
            <i class="fas fa-file-excel me-2"></i>Ekspor ke Excel
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Rekapitulasi Skor Akhir dari Instruktur</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Nama Siswa</th>
                            <th>Program Keahlian</th>
                            <th>Skor Rata-rata</th>
                            <th>Dinilai oleh</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($assessments) > 0): ?>
                            <?php foreach ($assessments as $data): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($data['student_name']); ?></td>
                                <td><?php echo htmlspecialchars($data['program_name']); ?></td>
                                <td><strong><?php echo number_format($data['average_score'], 2); ?></strong></td>
                                <td><?php echo htmlspecialchars($data['instructor_name']); ?></td>
                                <td>
                                    <button class="btn btn-info btn-sm view-details-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#detailsModal"
                                            data-assessment='<?php echo htmlspecialchars(json_encode($data), ENT_QUOTES, 'UTF-8'); ?>'>
                                        <i class="fas fa-eye"></i> Detail
                                    </button>
                                    <a href="generate_pdf_report.php?student_id=<?php echo $data['student_id']; ?>" class="btn btn-danger btn-sm" target="_blank">
                                        <i class="fas fa-file-pdf"></i> Cetak Rapor
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">Belum ada siswa yang dinilai pada tahun ajaran ini.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk Detail Penilaian -->
<div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailsModalLabel">Detail Skor Siswa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6 class="mb-3">Siswa: <span id="modal_student_name" class="fw-normal"></span></h6>
                <table class="table table-striped">
                    <tr><td>1. Memahami alur bisnis dunia kerja tempat PKL dan wawasan wirausaha</td><td class="text-end"><span id="modal_score_1" class="badge bg-primary"></span></td></tr>
                    <tr><td>2. Menerapkan soft skill yang dibutuhkan dalam dunia kerja</td><td class="text-end"><span id="modal_score_2" class="badge bg-primary"></span></td></tr>
                    <tr><td>3. Menerapkan norma, SOP dan K3LH yang ada pada dunia kerja</td><td class="text-end"><span id="modal_score_3" class="badge bg-primary"></span></td></tr>
                    <tr><td>4. Menerapkan kompetensi teknis yang sudah dipelajari di sekolah dan/ atau baru dipelajari pada dunia kerja</td><td class="text-end"><span id="modal_score_4" class="badge bg-primary"></span></td></tr>
                </table>
                <hr>
                <h6>Catatan dari Instruktur:</h6>
                <p id="modal_notes" class="fst-italic bg-light p-2 rounded"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const detailsModal = document.getElementById('detailsModal');
    detailsModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const data = JSON.parse(button.dataset.assessment);
        document.getElementById('modal_student_name').textContent = data.student_name;
        document.getElementById('modal_score_1').textContent = data.score_1;
        document.getElementById('modal_score_2').textContent = data.score_2;
        document.getElementById('modal_score_3').textContent = data.score_3;
        document.getElementById('modal_score_4').textContent = data.score_4;
        document.getElementById('modal_notes').textContent = data.notes || 'Tidak ada catatan.';
    });
});
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>