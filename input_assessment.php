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
    // Ambil daftar siswa bimbingan yang BELUM dinilai
    $stmt = $pdo->prepare("
        SELECT s.id, s.name
        FROM students s
        JOIN internship_mappings m ON s.id = m.student_id
        LEFT JOIN internship_assessments a ON s.id = a.student_id AND a.instructor_id = :instructor_id
        WHERE m.instructor_id = :instructor_id_map AND a.id IS NULL
        ORDER BY s.name ASC
    ");
    $stmt->execute([':instructor_id' => $instructor_id, ':instructor_id_map' => $instructor_id]);
    $unassessed_students = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error: Could not fetch students data. " . $e->getMessage());
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Input Penilaian Observasi Siswa</h1>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['flash_message']['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['flash_message']['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Form Penilaian</h6>
        </div>
        <div class="card-body">
            <?php if (count($unassessed_students) > 0): ?>
                <form action="core/assessment_actions.php" method="POST">
                    <input type="hidden" name="action" value="create_assessment">

                    <div class="mb-4">
                        <label for="student_id" class="form-label">Pilih Siswa yang Akan Dinilai</label>
                        <select class="form-select" id="student_id" name="student_id" required>
                            <option value="" disabled selected>-- Daftar Siswa Belum Dinilai --</option>
                            <?php foreach ($unassessed_students as $student): ?>
                                <option value="<?php echo $student['id']; ?>"><?php echo htmlspecialchars($student['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <hr class="my-4">

                    <div class="row">
                        <!-- Penilaian Disiplin -->
                        <div class="col-md-6 mb-4">
                            <label for="discipline_score" class="form-label">1. Kedisiplinan (Skor: <span id="discipline_value">75</span>)</label>
                            <input type="range" class="form-range" id="discipline_score" name="discipline_score" min="1" max="100" value="75" oninput="updateSliderValue(this.id, 'discipline_value')">
                        </div>

                        <!-- Penilaian Keahlian -->
                        <div class="col-md-6 mb-4">
                            <label for="skill_score" class="form-label">2. Keahlian/Skill (Skor: <span id="skill_value">75</span>)</label>
                            <input type="range" class="form-range" id="skill_score" name="skill_score" min="1" max="100" value="75" oninput="updateSliderValue(this.id, 'skill_value')">
                        </div>

                        <!-- Penilaian Kerja Tim -->
                        <div class="col-md-6 mb-4">
                            <label for="teamwork_score" class="form-label">3. Kerja Tim (Skor: <span id="teamwork_value">75</span>)</label>
                            <input type="range" class="form-range" id="teamwork_score" name="teamwork_score" min="1" max="100" value="75" oninput="updateSliderValue(this.id, 'teamwork_value')">
                        </div>

                        <!-- Penilaian Kerajinan -->
                        <div class="col-md-6 mb-4">
                            <label for="diligence_score" class="form-label">4. Kerajinan/Ketekunan (Skor: <span id="diligence_value">75</span>)</label>
                            <input type="range" class="form-range" id="diligence_score" name="diligence_score" min="1" max="100" value="75" oninput="updateSliderValue(this.id, 'diligence_value')">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="feedback" class="form-label">Catatan dan Feedback Tambahan</label>
                        <textarea class="form-control" id="feedback" name="feedback" rows="4" placeholder="Berikan deskripsi atau masukan mengenai kinerja siswa..."></textarea>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save me-2"></i> Simpan Penilaian
                        </button>
                    </div>

                </form>
            <?php else: ?>
                <div class="alert alert-success text-center">
                    <i class="fas fa-check-circle fa-3x mb-3"></i>
                    <h4 class="alert-heading">Kerja Bagus!</h4>
                    <p>Semua siswa bimbingan Anda telah selesai dinilai.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function updateSliderValue(sliderId, displayId) {
    const slider = document.getElementById(sliderId);
    const display = document.getElementById(displayId);
    display.textContent = slider.value;
}
</script>

<?php
require_once __DIR__ . '/templates/footer.php';
?>