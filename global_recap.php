<?php
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/sidebar.php';

// Proteksi halaman
if ($_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Logika untuk mengambil data rekap akan ditambahkan di sini
// berdasarkan filter yang dipilih.

$selected_month = $_GET['month'] ?? date('Y-m');
$rekap_type = $_GET['rekap_type'] ?? 'attendance';

$attendance_data = [];
$assessment_data = [];

try {
    if ($rekap_type === 'attendance') {
        // Ambil rekap absensi
        $stmt_att = $pdo->prepare("
            SELECT s.name as student_name, s.department, COUNT(j.id) as total_hadir
            FROM students s
            LEFT JOIN internship_journals j ON s.id = j.student_id AND DATE_FORMAT(j.journal_date, '%Y-%m') = :month
            GROUP BY s.id
            ORDER BY s.name ASC
        ");
        $stmt_att->execute([':month' => $selected_month]);
        $attendance_data = $stmt_att->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($rekap_type === 'assessment') {
        // Ambil rekap nilai
        $stmt_ass = $pdo->prepare("
            SELECT
                s.name as student_name, s.department,
                a.discipline_score, a.skill_score, a.teamwork_score, a.diligence_score,
                (a.discipline_score + a.skill_score + a.teamwork_score + a.diligence_score) / 4 as average_score
            FROM students s
            LEFT JOIN internship_assessments a ON s.id = a.student_id
            ORDER BY s.name ASC
        ");
        $stmt_ass->execute();
        $assessment_data = $stmt_ass->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    die("Error fetching recap data: " . $e->getMessage());
}

?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Rekapitulasi Global dan Laporan</h1>

    <!-- Filter Form -->
    <div class="card shadow mb-4">
        <div class="card-header">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-filter me-2"></i>Pilih Jenis Laporan</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="global_recap.php" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="rekap_type" class="form-label">Jenis Rekapitulasi</label>
                    <select name="rekap_type" id="rekap_type" class="form-select" onchange="toggleMonthFilter(this.value)">
                        <option value="attendance" <?php echo ($rekap_type === 'attendance') ? 'selected' : ''; ?>>Rekap Absensi Bulanan</option>
                        <option value="assessment" <?php echo ($rekap_type === 'assessment') ? 'selected' : ''; ?>>Daftar Nilai Akhir</option>
                    </select>
                </div>
                <div class="col-md-4" id="month_filter_container">
                    <label for="month" class="form-label">Pilih Bulan</label>
                    <input type="month" name="month" id="month" class="form-control" value="<?php echo htmlspecialchars($selected_month); ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Tampilkan</button>
                </div>
                <div class="col-md-2">
                    <a href="core/export_actions.php?rekap_type=<?php echo $rekap_type; ?>&month=<?php echo $selected_month; ?>" class="btn btn-success w-100" target="_blank">
                        <i class="fas fa-file-excel me-2"></i>Export
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Hasil Rekap -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <?php echo ($rekap_type === 'attendance') ? 'Hasil Rekap Absensi untuk Bulan ' . date('F Y', strtotime($selected_month)) : 'Hasil Rekap Nilai Akhir'; ?>
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <!-- Tabel Absensi -->
                <?php if ($rekap_type === 'attendance'): ?>
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Nama Siswa</th>
                            <th>Jurusan</th>
                            <th>Total Kehadiran (Hari)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attendance_data as $data): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($data['student_name']); ?></td>
                            <td><?php echo htmlspecialchars($data['department']); ?></td>
                            <td><?php echo $data['total_hadir']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>

                <!-- Tabel Nilai -->
                <?php if ($rekap_type === 'assessment'): ?>
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Nama Siswa</th>
                            <th>Jurusan</th>
                            <th>Disiplin</th>
                            <th>Skill</th>
                            <th>Kerja Tim</th>
                            <th>Kerajinan</th>
                            <th>Rata-rata</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assessment_data as $data): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($data['student_name']); ?></td>
                            <td><?php echo htmlspecialchars($data['department']); ?></td>
                            <td><?php echo $data['discipline_score'] ?? 'N/A'; ?></td>
                            <td><?php echo $data['skill_score'] ?? 'N/A'; ?></td>
                            <td><?php echo $data['teamwork_score'] ?? 'N/A'; ?></td>
                            <td><?php echo $data['diligence_score'] ?? 'N/A'; ?></td>
                            <td><strong><?php echo isset($data['average_score']) ? number_format($data['average_score'], 2) : 'N/A'; ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function toggleMonthFilter(rekapType) {
    const monthFilter = document.getElementById('month_filter_container');
    if (rekapType === 'attendance') {
        monthFilter.style.display = 'block';
    } else {
        monthFilter.style.display = 'none';
    }
}
// Initial call to set visibility
toggleMonthFilter(document.getElementById('rekap_type').value);
</script>

<?php
require_once __DIR__ . '/templates/footer.php';
?>