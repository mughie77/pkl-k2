<?php
require_once __DIR__ . '/templates/header.php';

// Proteksi halaman
if ($_SESSION['user_role'] !== 'teacher') {
    header("Location: login.php");
    exit;
}

$teacher_id = $_SESSION['user_id'];

// Proses filter
$filter_start_date = $_GET['start_date'] ?? '';
$filter_end_date = $_GET['end_date'] ?? '';

$query = "
    SELECT
        n.note, n.created_at, n.creator_role,
        s.student_name,
        c.company_name,
        i.instructor_name
    FROM student_notes n
    JOIN students s ON n.student_id = s.id
    JOIN internship_mappings m ON n.student_id = m.student_id
    LEFT JOIN instructors i ON m.instructor_id = i.id
    LEFT JOIN companies c ON m.company_id = c.company_id
    WHERE m.teacher_id = :teacher_id
";
$params = [':teacher_id' => $teacher_id];

if (!empty($filter_start_date)) { $query .= " AND DATE(n.created_at) >= :start_date"; $params[':start_date'] = $filter_start_date; }
if (!empty($filter_end_date)) { $query .= " AND DATE(n.created_at) <= :end_date"; $params[':end_date'] = $filter_end_date; }

$query .= " ORDER BY n.created_at DESC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $notes_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching notes data: " . $e->getMessage());
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Laporan Permasalahan Siswa Bimbingan</h1>
        <a href="#" id="exportBtn" class="btn btn-success">
            <i class="fas fa-file-excel me-2"></i>Ekspor ke Excel
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header"><h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-filter me-2"></i>Filter Data</h6></div>
        <div class="card-body">
            <form method="GET" action="teacher_problem_report.php" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Dari</label>
                    <input type="date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($filter_start_date); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Sampai</label>
                    <input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($filter_end_date); ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Cari</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Hasil Laporan</h6></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Nama Siswa</th>
                            <th>DUDIKA</th>
                            <th>Catatan Masalah</th>
                            <th>Pelapor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($notes_data) > 0): ?>
                            <?php foreach ($notes_data as $note): ?>
                                <tr>
                                    <td><?php echo date('d M Y, H:i', strtotime($note['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($note['student_name']); ?></td>
                                    <td><?php echo htmlspecialchars($note['company_name'] ?? '-'); ?></td>
                                    <td style="white-space: pre-wrap;"><?php echo htmlspecialchars($note['note']); ?></td>
                                    <td>
                                        <?php
                                            $creator_name = ($note['creator_role'] == 'teacher') ? 'Anda' : $note['instructor_name'];
                                            echo htmlspecialchars(ucwords($note['creator_role'])) . '<br><small>(' . htmlspecialchars($creator_name ?? 'N/A') . ')</small>';
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center">Tidak ada data yang ditemukan.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    function updateExportLink() {
        const params = new URLSearchParams(window.location.search);
        params.set('type', 'rekap_masalah');
        $('#exportBtn').attr('href', 'core/export_handler.php?' + params.toString());
    }
    updateExportLink();
    $('input[type=date]').on('change', updateExportLink);
});
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>