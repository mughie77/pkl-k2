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
            a.id as assessment_id,
            s.name as student_name, pk.program_name,
            a.score_1, a.score_2, a.score_3, a.score_4,
            (a.score_1 + a.score_2 + a.score_3 + a.score_4) / 4 as average_score,
            i.name as instructor_name
        FROM internship_assessments a
        JOIN students s ON a.student_id = s.id
        JOIN kelas k ON s.kelas_id = k.id
        JOIN konsentrasi_keahlian kk ON k.konsentrasi_id = kk.id
        JOIN program_keahlian pk ON kk.program_id = pk.id
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
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Rekapitulasi Nilai Akhir dari Instruktur</h6>
            <a href="core/export_handler.php?report_type=teacher_assessments" class="btn btn-sm btn-success">
                <i class="fas fa-file-excel me-2"></i>Export to Excel
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Nama Siswa</th>
                            <th>Program Keahlian</th>
                            <th>Memahami Alur Bisnis</th>
                            <th>Menerapkan Soft Skill</th>
                            <th>Norma, SOP, K3LH</th>
                            <th>Kompetensi Teknis</th>
                            <th>Rata-rata</th>
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
                                <td><?php echo $data['score_1']; ?></td>
                                <td><?php echo $data['score_2']; ?></td>
                                <td><?php echo $data['score_3']; ?></td>
                                <td><?php echo $data['score_4']; ?></td>
                                <td><strong><?php echo number_format($data['average_score'], 2); ?></strong></td>
                                <td><?php echo htmlspecialchars($data['instructor_name']); ?></td>
                                <td>
                                    <a href="core/generate_pdf_report.php?assessment_id=<?php echo $data['assessment_id']; ?>" class="btn btn-sm btn-info" target="_blank">
                                        <i class="fas fa-print me-2"></i>Cetak Raport
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center">Belum ada siswa bimbingan yang dinilai oleh instruktur.</td>
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