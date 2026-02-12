<?php
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/sidebar.php';

// Proteksi halaman
if ($_SESSION['user_role'] !== 'instructor') {
    header("Location: login.php");
    exit;
}

$instructor_id = $_SESSION['user_id'];
$instructor_name = $_SESSION['user_name'];

// Logika Sapaan Dinamis
$hour = date('H');
$greeting = 'Selamat Pagi';
if ($hour >= 12) $greeting = 'Selamat Siang';
if ($hour >= 15) $greeting = 'Selamat Sore';
if ($hour >= 18) $greeting = 'Selamat Malam';

try {
    // Ambil notifikasi
    $stmt_pending_journals = $pdo->prepare("SELECT COUNT(j.id) FROM internship_journals j JOIN internship_mappings m ON j.student_id = m.student_id WHERE m.instructor_id = :id AND j.status = 'Pending'");
    $stmt_pending_journals->execute([':id' => $instructor_id]);
    $pending_journals_count = $stmt_pending_journals->fetchColumn();

    $stmt_pending_leave = $pdo->prepare("SELECT COUNT(id) FROM leave_requests WHERE instructor_id = :id AND leave_status = 'Pending'");
    $stmt_pending_leave->execute([':id' => $instructor_id]);
    $pending_leave_count = $stmt_pending_leave->fetchColumn();

    // Ambil daftar siswa bimbingan
    $stmt_students = $pdo->prepare("
        SELECT s.id, s.student_name, pk.program_name
        FROM students s
        LEFT JOIN kelas k ON s.kelas_id = k.id
        LEFT JOIN konsentrasi_keahlian kk ON k.konsentrasi_id = kk.id
        LEFT JOIN program_keahlian pk ON kk.program_id = pk.id
        JOIN internship_mappings m ON s.id = m.student_id
        WHERE m.instructor_id = :id ORDER BY s.student_name ASC
    ");
    $stmt_students->execute([':id' => $instructor_id]);
    $assigned_students = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error fetching dashboard data: " . $e->getMessage());
}
?>

<div class="container-fluid instructor-dashboard student-dashboard">
    <!-- Header Dashboard -->
    <div class="dashboard-header card p-3 mb-4 shadow-sm">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <p class="text-muted mb-0"><?php echo date('d M Y'); ?></p>
                <h5 class="mb-1"><?php echo $greeting; ?>!</h5>
                <h3 class="fw-bold mb-0"><?php echo htmlspecialchars($instructor_name); ?></h3>
            </div>
            <div class="text-end">
                <p class="text-muted mb-0">Peran</p>
                <h5 class="fw-bold mb-0">Instruktur DUDIKA</h5>
            </div>
        </div>
    </div>

    <!-- Menu Ikon -->
    <div class="row row-cols-2 row-cols-md-4 text-center g-3 mb-4">
        <div class="col">
            <a href="verify_journals.php" class="icon-menu-item position-relative">
                <div class="icon-circle bg-primary text-white"><i class="fas fa-tasks"></i></div>
                <span class="icon-label">Verifikasi Jurnal</span>
                <?php if ($pending_journals_count > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?php echo $pending_journals_count; ?></span>
                <?php endif; ?>
            </a>
        </div>
        <div class="col">
            <a href="input_assessment.php" class="icon-menu-item">
                <div class="icon-circle bg-success text-white"><i class="fas fa-edit"></i></div>
                <span class="icon-label">Input Penilaian</span>
            </a>
        </div>
        <div class="col">
            <a href="manage_leave_requests.php" class="icon-menu-item position-relative">
                <div class="icon-circle bg-warning text-dark"><i class="fas fa-calendar-check"></i></div>
                <span class="icon-label">Persetujuan Izin</span>
                 <?php if ($pending_leave_count > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?php echo $pending_leave_count; ?></span>
                <?php endif; ?>
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
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($assigned_students) > 0): ?>
                            <?php foreach ($assigned_students as $student): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['student_name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['program_name']); ?></td>
                                    <td>
                                        <a href="student_details.php?id=<?php echo $student['id']; ?>" class="btn btn-info btn-sm">
                                            <i class="fas fa-eye"></i> Detail
                                        </a>
                                    </td>
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