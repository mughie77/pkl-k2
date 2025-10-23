<?php
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/sidebar.php';

// Proteksi halaman
if ($_SESSION['user_role'] !== 'instructor') {
    header("Location: login.php");
    exit;
}

$instructor_id = $_SESSION['user_id'];
$assessments = [];

try {
    // Ambil daftar siswa bimbingan yang BELUM dinilai
    $stmt_unassessed = $pdo->prepare("
        SELECT s.id, s.name
        FROM students s
        JOIN internship_mappings m ON s.id = m.student_id
        LEFT JOIN internship_assessments a ON s.id = a.student_id AND a.instructor_id = :instructor_id
        WHERE m.instructor_id = :instructor_id_map AND a.id IS NULL
        ORDER BY s.name ASC
    ");
    $stmt_unassessed->execute([':instructor_id' => $instructor_id, ':instructor_id_map' => $instructor_id]);
    $unassessed_students = $stmt_unassessed->fetchAll(PDO::FETCH_ASSOC);

    // Ambil data penilaian yang SUDAH diinput oleh instruktur yang login
    $stmt_assessed = $pdo->prepare("
        SELECT
            a.id,
            s.name AS student_name,
            a.score_1, a.score_2, a.score_3, a.score_4,
            a.notes, a.assessment_date
        FROM internship_assessments a
        JOIN students s ON a.student_id = s.id
        WHERE a.instructor_id = :instructor_id
        ORDER BY a.assessment_date DESC
    ");
    $stmt_assessed->execute([':instructor_id' => $instructor_id]);
    $assessments = $stmt_assessed->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error: Could not fetch data. " . $e->getMessage());
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Penilaian Siswa</h1>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['flash_message']['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['flash_message']['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <!-- Form Input Penilaian -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Form Input Penilaian</h6>
        </div>
        <div class="card-body">
            <?php if (count($unassessed_students) > 0): ?>
                <form action="core/assessment_actions.php" method="POST">
                    <input type="hidden" name="action" value="create_assessment">
                    <div class="mb-4">
                        <label for="student_id" class="form-label">Pilih Siswa untuk Dinilai</label>
                        <select class="form-select" id="student_id" name="student_id" required>
                            <option value="" disabled selected>-- Daftar Siswa Belum Dinilai --</option>
                            <?php foreach ($unassessed_students as $student): ?>
                                <option value="<?php echo $student['id']; ?>"><?php echo htmlspecialchars($student['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <hr class="my-4">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label for="score_1" class="form-label">1. Memahami alur bisnis (Skor: <span id="score_1_value">75</span>)</label>
                            <input type="range" class="form-range" id="score_1" name="score_1" min="1" max="100" value="75" oninput="updateSliderValue(this.id, 'score_1_value')">
                        </div>
                        <div class="col-md-6 mb-4">
                            <label for="score_2" class="form-label">2. Menerapkan soft skill (Skor: <span id="score_2_value">75</span>)</label>
                            <input type="range" class="form-range" id="score_2" name="score_2" min="1" max="100" value="75" oninput="updateSliderValue(this.id, 'score_2_value')">
                        </div>
                        <div class="col-md-6 mb-4">
                            <label for="score_3" class="form-label">3. Menerapkan norma, SOP, K3LH (Skor: <span id="score_3_value">75</span>)</label>
                            <input type="range" class="form-range" id="score_3" name="score_3" min="1" max="100" value="75" oninput="updateSliderValue(this.id, 'score_3_value')">
                        </div>
                        <div class="col-md-6 mb-4">
                            <label for="score_4" class="form-label">4. Menerapkan kompetensi teknis (Skor: <span id="score_4_value">75</span>)</label>
                            <input type="range" class="form-range" id="score_4" name="score_4" min="1" max="100" value="75" oninput="updateSliderValue(this.id, 'score_4_value')">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Catatan Tambahan</label>
                        <textarea class="form-control" id="notes" name="notes" rows="4" placeholder="Berikan deskripsi atau masukan..."></textarea>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">Simpan Penilaian</button>
                    </div>
                </form>
            <?php else: ?>
                <div class="alert alert-success text-center">
                    <h4 class="alert-heading">Kerja Bagus!</h4>
                    <p>Semua siswa bimbingan Anda telah selesai dinilai.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tabel Log Penilaian -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Log Penilaian yang Telah Diinput</h6>
        </div>
        <div class="card-body">
            <?php if (count($assessments) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="dataTable" width="100%" cellspacing="0">
                        <thead class="table-dark">
                            <tr>
                                <th>No.</th>
                                <th>Nama Siswa</th>
                                <th>Alur Bisnis</th>
                                <th>Soft Skill</th>
                                <th>Norma/SOP</th>
                                <th>Kompetensi Teknis</th>
                                <th>Catatan</th>
                                <th>Tanggal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assessments as $index => $asm): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($asm['student_name']); ?></td>
                                    <td><?php echo htmlspecialchars($asm['score_1']); ?></td>
                                    <td><?php echo htmlspecialchars($asm['score_2']); ?></td>
                                    <td><?php echo htmlspecialchars($asm['score_3']); ?></td>
                                    <td><?php echo htmlspecialchars($asm['score_4']); ?></td>
                                    <td><?php echo nl2br(htmlspecialchars($asm['notes'])); ?></td>
                                    <td><?php echo date('d M Y H:i', strtotime($asm['assessment_date'])); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-warning edit-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editAssessmentModal"
                                            data-id="<?php echo $asm['id']; ?>"
                                            data-score1="<?php echo $asm['score_1']; ?>"
                                            data-score2="<?php echo $asm['score_2']; ?>"
                                            data-score3="<?php echo $asm['score_3']; ?>"
                                            data-score4="<?php echo $asm['score_4']; ?>"
                                            data-notes="<?php echo htmlspecialchars($asm['notes']); ?>">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    <p>Belum ada data penilaian yang diinput.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Edit Assessment Modal -->
<div class="modal fade" id="editAssessmentModal" tabindex="-1" aria-labelledby="editAssessmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editAssessmentModalLabel">Edit Penilaian Siswa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="core/assessment_actions.php" method="POST" id="editAssessmentForm">
                    <input type="hidden" name="action" value="update_assessment">
                    <input type="hidden" name="assessment_id" id="edit_assessment_id">

                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label for="edit_score_1" class="form-label">1. Memahami alur bisnis (Skor: <span id="edit_score_1_value"></span>)</label>
                            <input type="range" class="form-range" id="edit_score_1" name="score_1" min="1" max="100" oninput="updateSliderValue('edit_score_1', 'edit_score_1_value')">
                        </div>
                        <div class="col-md-6 mb-4">
                            <label for="edit_score_2" class="form-label">2. Menerapkan soft skill (Skor: <span id="edit_score_2_value"></span>)</label>
                            <input type="range" class="form-range" id="edit_score_2" name="score_2" min="1" max="100" oninput="updateSliderValue('edit_score_2', 'edit_score_2_value')">
                        </div>
                        <div class="col-md-6 mb-4">
                            <label for="edit_score_3" class="form-label">3. Menerapkan norma, SOP, K3LH (Skor: <span id="edit_score_3_value"></span>)</label>
                            <input type="range" class="form-range" id="edit_score_3" name="score_3" min="1" max="100" oninput="updateSliderValue('edit_score_3', 'edit_score_3_value')">
                        </div>
                        <div class="col-md-6 mb-4">
                            <label for="edit_score_4" class="form-label">4. Menerapkan kompetensi teknis (Skor: <span id="edit_score_4_value"></span>)</label>
                            <input type="range" class="form-range" id="edit_score_4" name="score_4" min="1" max="100" oninput="updateSliderValue('edit_score_4', 'edit_score_4_value')">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_notes" class="form-label">Catatan Tambahan</label>
                        <textarea class="form-control" id="edit_notes" name="notes" rows="4"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" form="editAssessmentForm" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </div>
    </div>
</div>

<script>
function updateSliderValue(sliderId, displayId) {
    const slider = document.getElementById(sliderId);
    const display = document.getElementById(displayId);
    display.textContent = slider.value;
}

document.addEventListener('DOMContentLoaded', function() {
    const editButtons = document.querySelectorAll('.edit-btn');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const assessmentId = this.dataset.id;
            const score1 = this.dataset.score1;
            const score2 = this.dataset.score2;
            const score3 = this.dataset.score3;
            const score4 = this.dataset.score4;
            const notes = this.dataset.notes;

            document.getElementById('edit_assessment_id').value = assessmentId;

            document.getElementById('edit_score_1').value = score1;
            document.getElementById('edit_score_1_value').textContent = score1;

            document.getElementById('edit_score_2').value = score2;
            document.getElementById('edit_score_2_value').textContent = score2;

            document.getElementById('edit_score_3').value = score3;
            document.getElementById('edit_score_3_value').textContent = score3;

            document.getElementById('edit_score_4').value = score4;
            document.getElementById('edit_score_4_value').textContent = score4;

            document.getElementById('edit_notes').value = notes;
        });
    });
});
</script>

<?php
if (in_array($_SESSION['user_role'], ['student', 'teacher', 'instructor'])) {
    echo '</div>';
}
require_once __DIR__ . '/templates/footer.php';
?>