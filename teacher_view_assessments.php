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
    // Ambil data penilaian dari siswa bimbingan guru ini
    $stmt = $pdo->prepare("
        SELECT
            s.name as student_name, d.department_name,
            a.discipline_score, a.skill_score, a.teamwork_score, a.diligence_score,
            (a.discipline_score + a.skill_score + a.teamwork_score + a.diligence_score) / 4 as average_score,
            i.name as instructor_name
        FROM internship_assessments a
        JOIN students s ON a.student_id = s.id
        JOIN departments d ON s.department_id = d.id
        JOIN instructors i ON a.instructor_id = i.id
        JOIN internship_mappings m ON a.student_id = m.student_id
        WHERE m.teacher_id = :teacher_id
        ORDER BY s.name ASC
    ");
    $stmt->execute([':teacher_id' => $teacher_id]);
    $assessments = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error fetching assessment data: " . $e->getMessage());
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Daftar Nilai Siswa Bimbingan</h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Rekapitulasi Nilai Akhir dari Instruktur</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Nama Siswa</th>
                            <th>Jurusan</th>
                            <th>Disiplin</th>
                            <th>Skill</th>
                            <th>Kerja Tim</th>
                            <th>Kerajinan</th>
                            <th>Rata-rata</th>
                            <th>Dinilai oleh</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($assessments) > 0): ?>
                            <?php foreach ($assessments as $data): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($data['student_name']); ?></td>
                                <td><?php echo htmlspecialchars($data['department_name']); ?></td>
                                <td><?php echo $data['discipline_score']; ?></td>
                                <td><?php echo $data['skill_score']; ?></td>
                                <td><?php echo $data['teamwork_score']; ?></td>
                                <td><?php echo $data['diligence_score']; ?></td>
                                <td><strong><?php echo number_format($data['average_score'], 2); ?></strong></td>
                                <td><?php echo htmlspecialchars($data['instructor_name']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">Belum ada siswa bimbingan yang dinilai oleh instruktur.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
if (in_array($_SESSION['user_role'], ['student', 'teacher', 'instructor'])) {
    echo '</div>';
}
require_once __DIR__ . '/templates/footer.php';
?>