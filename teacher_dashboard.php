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
    // 1. Ambil daftar siswa bimbingan
    $stmt_students = $pdo->prepare("
        SELECT s.id, s.name, s.department, c.name as company_name
        FROM students s
        JOIN internship_mappings m ON s.id = m.student_id
        JOIN instructors i ON m.instructor_id = i.id
        JOIN companies c ON i.company_id = c.id
        WHERE m.teacher_id = :teacher_id
        ORDER BY s.name ASC
    ");
    $stmt_students->execute([':teacher_id' => $teacher_id]);
    $assigned_students = $stmt_students->fetchAll(PDO::FETCH_ASSOC);
    $total_students = count($assigned_students);

    // 2. Hitung statistik jurnal lancar
    $students_with_recent_journal = 0;
    if ($total_students > 0) {
        // Cari tanggal kerja terakhir (hari ini atau hari kerja sebelumnya)
        $check_date = new DateTime();
        if ($check_date->format('N') == 6) { // Jika Sabtu
            $check_date->modify('-1 day');
        } elseif ($check_date->format('N') == 7) { // Jika Minggu
            $check_date->modify('-2 day');
        }
        $last_workday = $check_date->format('Y-m-d');

        $student_ids = array_column($assigned_students, 'id');
        $placeholders = implode(',', array_fill(0, count($student_ids), '?'));

        $stmt_journals = $pdo->prepare("
            SELECT COUNT(DISTINCT student_id)
            FROM internship_journals
            WHERE student_id IN ($placeholders) AND journal_date >= ?
        ");
        $stmt_journals->execute(array_merge($student_ids, [$last_workday]));
        $students_with_recent_journal = $stmt_journals->fetchColumn();
    }

    $smooth_journal_percentage = ($total_students > 0) ? ($students_with_recent_journal / $total_students) * 100 : 0;
    $smooth_journal_percentage = round($smooth_journal_percentage);

} catch (PDOException $e) {
    die("Error: Could not fetch dashboard data. " . $e->getMessage());
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Dashboard Guru Pembimbing</h1>

    <!-- Statistik Cepat -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Siswa Bimbingan</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_students; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-9 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Jurnal Lancar (Hari Ini)</div>
                            <div class="row no-gutters align-items-center">
                                <div class="col-auto">
                                    <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800"><?php echo $smooth_journal_percentage; ?>%</div>
                                </div>
                                <div class="col">
                                    <div class="progress progress-sm mr-2">
                                        <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $smooth_journal_percentage; ?>%" aria-valuenow="<?php echo $smooth_journal_percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                        </div>
                    </div>
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
                            <th>Ditempatkan di DUDIKA</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($total_students > 0): ?>
                            <?php foreach ($assigned_students as $index => $student): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($student['name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['department']); ?></td>
                                    <td><?php echo htmlspecialchars($student['company_name']); ?></td>
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
.card .border-left-primary { border-left: .25rem solid #4e73df!important; }
.card .border-left-info { border-left: .25rem solid #36b9cc!important; }
.text-xs { font-size: .7rem; }
.progress-sm { height: .5rem; }
</style>

<?php
require_once __DIR__ . '/templates/footer.php';
?>