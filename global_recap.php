<?php
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/sidebar.php';

// Proteksi halaman
if (!in_array($_SESSION['user_role'], ['admin', 'instructor'])) {
    header("Location: login.php");
    exit;
}

$active_year_id = $active_year['id'] ?? 0;

$selected_month = $_GET['month'] ?? date('Y-m');
$rekap_type = $_GET['rekap_type'] ?? 'attendance';

$user_role = $_SESSION['user_role'];
$instructor_id = ($user_role === 'instructor') ? $_SESSION['user_id'] : null;

$rekap_data = [];

try {
    $base_query = "
        FROM students s
        JOIN kelas k ON s.kelas_id = k.id
        JOIN konsentrasi_keahlian kk ON k.konsentrasi_id = kk.id
        JOIN program_keahlian pk ON kk.program_id = pk.id
        LEFT JOIN internship_mappings m ON s.id = m.student_id
    ";

    $where_clause = "WHERE s.academic_year_id = :year_id";
    $params = [':year_id' => $active_year_id];

    if ($instructor_id) {
        $where_clause .= " AND m.instructor_id = :instructor_id";
        $params[':instructor_id'] = $instructor_id;
    }

    if ($rekap_type === 'attendance') {
        $select_clause = "
            SELECT s.name as student_name, pk.program_name, COUNT(j.id) as total_hadir
        ";
        $joins = "LEFT JOIN internship_journals j ON s.id = j.student_id AND DATE_FORMAT(j.journal_date, '%Y-%m') = :month";
        $group_by = "GROUP BY s.id, s.name, pk.program_name";
        $order_by = "ORDER BY s.name ASC";
        $params[':month'] = $selected_month;

        $final_query = $select_clause . $base_query . $joins . " " . $where_clause . " " . $group_by . " " . $order_by;

    } else { // assessment
        $select_clause = "
            SELECT
                s.name as student_name, pk.program_name,
                a.score_1, a.score_2, a.score_3, a.score_4,
                (a.score_1 + a.score_2 + a.score_3 + a.score_4) / 4 as average_score
        ";
        $joins = "LEFT JOIN internship_assessments a ON s.id = a.student_id";
        $group_by = ""; // No group by for assessment
        $order_by = "ORDER BY s.name ASC";

        $final_query = $select_clause . $base_query . $joins . " " . $where_clause . " " . $order_by;
    }

    $stmt = $pdo->prepare($final_query);
    $stmt->execute($params);
    $rekap_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error fetching recap data: " . $e->getMessage());
}

?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Rekapitulasi Global <span class="badge bg-info"><?php echo htmlspecialchars($active_year['year_name'] ?? 'Tahun Ajaran Belum Dipilih'); ?></span></h1>

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
                            <th>Program Keahlian</th>
                            <th>Total Kehadiran (Hari)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rekap_data as $data): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($data['student_name']); ?></td>
                            <td><?php echo htmlspecialchars($data['program_name']); ?></td>
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
                            <th>Program Keahlian</th>
                            <th>Alur Bisnis</th>
                            <th>Kompetensi Teknis</th>
                            <th>Norma & SOP</th>
                            <th>Soft Skills</th>
                            <th>Rata-rata</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rekap_data as $data): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($data['student_name']); ?></td>
                            <td><?php echo htmlspecialchars($data['program_name']); ?></td>
                            <td><?php echo $data['score_1'] ?? 'N/A'; ?></td>
                            <td><?php echo $data['score_2'] ?? 'N/A'; ?></td>
                            <td><?php echo $data['score_3'] ?? 'N/A'; ?></td>
                            <td><?php echo $data['score_4'] ?? 'N/A'; ?></td>
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
document.addEventListener('DOMContentLoaded', function() {
    toggleMonthFilter(document.getElementById('rekap_type').value);
    $('.form-select').select2({ theme: 'bootstrap-5' });
});
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>