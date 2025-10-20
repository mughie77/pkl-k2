<?php
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/sidebar.php';

// Proteksi halaman
if ($_SESSION['user_role'] !== 'teacher') {
    header("Location: login.php");
    exit;
}

$teacher_id = $_SESSION['user_id'];
$teacher_name = $_SESSION['user_name'];

// Logika Sapaan Dinamis
$hour = date('H');
$greeting = 'Selamat Pagi';
if ($hour >= 12) $greeting = 'Selamat Siang';
if ($hour >= 15) $greeting = 'Selamat Sore';
if ($hour >= 18) $greeting = 'Selamat Malam';

try {
    // Ambil daftar siswa bimbingan
    $stmt_students = $pdo->prepare("
        SELECT s.id, s.student_name, pk.program_name, c.company_name
        FROM students s
        LEFT JOIN kelas k ON s.kelas_id = k.id
        LEFT JOIN konsentrasi_keahlian kk ON k.konsentrasi_id = kk.id
        LEFT JOIN program_keahlian pk ON kk.program_id = pk.id
        LEFT JOIN internship_mappings m ON s.id = m.student_id
        LEFT JOIN companies c ON m.company_id = c.company_id
        WHERE m.teacher_id = :teacher_id
        ORDER BY s.student_name ASC
    ");
    $stmt_students->execute([':teacher_id' => $teacher_id]);
    $assigned_students = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error fetching dashboard data: " . $e->getMessage());
}
?>

<div class="container-fluid teacher-dashboard student-dashboard">
    <!-- Header Dashboard -->
    <div class="dashboard-header card p-3 mb-4 shadow-sm">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <p class="text-muted mb-0"><?php echo date('d M Y'); ?></p>
                <h5 class="mb-1"><?php echo $greeting; ?>!</h5>
                <h3 class="fw-bold mb-0"><?php echo htmlspecialchars($teacher_name); ?></h3>
            </div>
            <div class="text-end">
                <p class="text-muted mb-0">Peran</p>
                <h5 class="fw-bold mb-0">Guru Pembimbing</h5>
            </div>
        </div>
    </div>

    <!-- Menu Ikon -->
    <div class="row row-cols-2 row-cols-md-4 text-center g-3 mb-4">
        <div class="col">
            <a href="monitor_journals.php" class="icon-menu-item">
                <div class="icon-circle bg-primary text-white"><i class="fas fa-book-reader"></i></div>
                <span class="icon-label">Monitoring Jurnal</span>
            </a>
        </div>
        <div class="col">
            <a href="teacher_view_assessments.php" class="icon-menu-item">
                <div class="icon-circle bg-success text-white"><i class="fas fa-graduation-cap"></i></div>
                <span class="icon-label">Lihat Nilai</span>
            </a>
        </div>
        <div class="col">
            <a href="report_consultation.php" class="icon-menu-item">
                <div class="icon-circle bg-info text-white"><i class="fas fa-file-alt"></i></div>
                <span class="icon-label">Konsultasi Laporan</span>
            </a>
        </div>
        <div class="col">
            <a href="student_problems.php" class="icon-menu-item">
                <div class="icon-circle bg-danger text-white"><i class="fas fa-exclamation-triangle"></i></div>
                <span class="icon-label">Catatan Masalah</span>
            </a>
        </div>
    </div>

    <!-- Daftar Siswa Bimbingan -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Siswa Bimbingan Anda</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Nama Siswa</th>
                            <th>Program Keahlian</th>
                            <th>Ditempatkan di</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($assigned_students) > 0): ?>
                            <?php foreach ($assigned_students as $student): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['student_name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['program_name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['company_name']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center p-4">Anda belum memiliki siswa bimbingan.</td>
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