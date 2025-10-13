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
    // 1. Hitung jurnal yang menunggu verifikasi dari siswa bimbingan
    $stmt_pending_journals = $pdo->prepare("
        SELECT COUNT(j.id) as total
        FROM internship_journals j
        JOIN internship_mappings m ON j.student_id = m.student_id
        WHERE m.instructor_id = :instructor_id AND j.status = 'Pending'
    ");
    $stmt_pending_journals->execute([':instructor_id' => $instructor_id]);
    $pending_journals_count = $stmt_pending_journals->fetchColumn();

    // 2. Hitung siswa bimbingan yang belum dinilai
    $stmt_unassessed_students = $pdo->prepare("
        SELECT COUNT(m.student_id) as total
        FROM internship_mappings m
        LEFT JOIN internship_assessments a ON m.student_id = a.student_id AND a.instructor_id = m.instructor_id
        WHERE m.instructor_id = :instructor_id AND a.id IS NULL
    ");
    $stmt_unassessed_students->execute([':instructor_id' => $instructor_id]);
    $unassessed_students_count = $stmt_unassessed_students->fetchColumn();

    // 3. Ambil daftar siswa bimbingan
    $stmt_students = $pdo->prepare("
        SELECT s.name, s.department, s.email
        FROM students s
        JOIN internship_mappings m ON s.id = m.student_id
        WHERE m.instructor_id = :instructor_id
        ORDER BY s.name ASC
    ");
    $stmt_students->execute([':instructor_id' => $instructor_id]);
    $assigned_students = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error: Could not fetch dashboard data. " . $e->getMessage());
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Dashboard Instruktur</h1>

    <!-- Action Cards -->
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Jurnal Menunggu Verifikasi</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $pending_journals_count; ?> Jurnal</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-tasks fa-2x text-gray-300"></i>
                        </div>
                    </div>
                    <a href="verify_journals.php" class="stretched-link"></a>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Siswa Belum Dinilai</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $unassessed_students_count; ?> Siswa</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-edit fa-2x text-gray-300"></i>
                        </div>
                    </div>
                     <a href="input_assessment.php" class="stretched-link"></a>
                </div>
            </div>
        </div>
    </div>

    <!-- Daftar Siswa Bimbingan -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Siswa Bimbingan Anda</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nama Siswa</th>
                            <th>Jurusan</th>
                            <th>Email</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($assigned_students) > 0): ?>
                            <?php foreach ($assigned_students as $index => $student): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($student['name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['department']); ?></td>
                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center">Anda belum memiliki siswa bimbingan.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<style>
/* Custom styles for dashboard cards */
.card .border-left-warning { border-left: .25rem solid #f6c23e!important; }
.card .border-left-danger { border-left: .25rem solid #e74a3b!important; }
.text-xs { font-size: .7rem; }
</style>

<?php
require_once __DIR__ . '/templates/footer.php';
?>