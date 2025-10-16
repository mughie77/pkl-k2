<?php
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/sidebar.php';

// Proteksi halaman
if (!in_array($_SESSION['user_role'], ['teacher', 'instructor'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// Filter
$filter_student_id = $_GET['student_id'] ?? 'all';
$filter_start_date = $_GET['start_date'] ?? '';
$filter_end_date = $_GET['end_date'] ?? '';

try {
    // Ambil daftar siswa bimbingan untuk filter dropdown
    $student_query_sql = "SELECT s.id, s.name FROM students s JOIN internship_mappings m ON s.id = m.student_id WHERE ";
    if ($user_role === 'teacher') {
        $student_query_sql .= "m.teacher_id = :user_id";
    } else {
        $student_query_sql .= "m.instructor_id = :user_id";
    }
    $student_query_sql .= " ORDER BY s.name ASC";
    $stmt_students = $pdo->prepare($student_query_sql);
    $stmt_students->execute([':user_id' => $user_id]);
    $students_for_filter = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

    // Bangun query utama
    $query = "
        SELECT
            j.id as journal_id, j.journal_date, j.check_in_time, j.check_out_time, j.status, j.activities,
            j.check_in_latitude, j.check_in_longitude, j.check_out_latitude, j.check_out_longitude,
            s.name as student_name, s.work_start_time, s.work_end_time
        FROM internship_journals j
        JOIN students s ON j.student_id = s.id
        JOIN internship_mappings m ON j.student_id = m.student_id
    ";

    $where_clauses = [];
    $params = [];

    if ($user_role === 'teacher') {
        $where_clauses[] = "m.teacher_id = :user_id";
    } else {
        $where_clauses[] = "m.instructor_id = :user_id";
    }
    $params[':user_id'] = $user_id;

    if ($filter_student_id !== 'all' && !empty($filter_student_id)) {
        $where_clauses[] = "j.student_id = :student_id";
        $params[':student_id'] = $filter_student_id;
    }
    if (!empty($filter_start_date)) {
        $where_clauses[] = "j.journal_date >= :start_date";
        $params[':start_date'] = $filter_start_date;
    }
    if (!empty($filter_end_date)) {
        $where_clauses[] = "j.journal_date <= :end_date";
        $params[':end_date'] = $filter_end_date;
    }

    if (count($where_clauses) > 0) {
        $query .= " WHERE " . implode(" AND ", $where_clauses);
    }

    $query .= " ORDER BY j.journal_date DESC, s.name ASC";

    $stmt_journals = $pdo->prepare($query);
    $stmt_journals->execute($params);
    $journals = $stmt_journals->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error: Could not fetch journals data. " . $e->getMessage());
}

function get_status_badge($status) {
    switch ($status) {
        case 'Approved': return 'bg-success';
        case 'Rejected': return 'bg-danger';
        case 'Pending': return 'bg-warning text-dark';
        default: return 'bg-secondary';
    }
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Monitoring Jurnal dan Absensi Siswa</h1>

    <!-- Filter Form -->
    <div class="card shadow mb-4">
        <div class="card-header">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-filter me-2"></i>Filter Data</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="monitor_journals.php" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="student_id" class="form-label">Pilih Siswa</label>
                    <select name="student_id" id="student_id" class="form-select">
                        <option value="all">Semua Siswa Bimbingan</option>
                        <?php foreach ($students_for_filter as $student): ?>
                            <option value="<?php echo $student['id']; ?>" <?php echo ($filter_student_id == $student['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($student['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="start_date" class="form-label">Dari Tanggal</label>
                    <input type="date" name="start_date" id="start_date" class="form-control" value="<?php echo htmlspecialchars($filter_start_date); ?>">
                </div>
                <div class="col-md-3">
                    <label for="end_date" class="form-label">Sampai Tanggal</label>
                    <input type="date" name="end_date" id="end_date" class="form-control" value="<?php echo htmlspecialchars($filter_end_date); ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Terapkan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Data Jurnal dan Kehadiran</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Nama Siswa</th>
                            <th>Jam Kerja</th>
                            <th>Absensi (Check-in)</th>
                            <th>Status Jurnal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($journals) > 0): ?>
                            <?php foreach ($journals as $journal): ?>
                                <tr>
                                    <td><?php echo date('d M Y', strtotime($journal['journal_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($journal['student_name']); ?></td>
                                    <td><?php echo date('H:i', strtotime($journal['work_start_time'])) . ' - ' . date('H:i', strtotime($journal['work_end_time'])); ?></td>
                                    <td><?php echo $journal['check_in_time'] ? date('H:i', strtotime($journal['check_in_time'])) : '<span class="badge bg-secondary">N/A</span>'; ?></td>
                                    <td>
                                        <span class="badge <?php echo get_status_badge($journal['status']); ?>">
                                            <?php echo htmlspecialchars($journal['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-info btn-sm view-journal-btn"
                                                data-activities="<?php echo htmlspecialchars($journal['activities']); ?>"
                                                data-student-name="<?php echo htmlspecialchars($journal['student_name']); ?>"
                                                data-journal-date="<?php echo date('d M Y', strtotime($journal['journal_date'])); ?>"
                                                data-bs-toggle="modal" data-bs-target="#viewJournalModal">
                                            <i class="fas fa-eye"></i>
                                        </button>

                                        <?php if (!empty($journal['check_in_latitude']) && !empty($journal['check_in_longitude'])): ?>
                                            <button class="btn btn-success btn-sm view-location-btn"
                                                    data-journal-id="<?php echo $journal['journal_id']; ?>"
                                                    data-location-type="check_in"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#locationModal"
                                                    title="Lihat Lokasi Check-in">
                                                <i class="fas fa-map-marker-alt"></i> In
                                            </button>
                                        <?php endif; ?>

                                        <?php if (!empty($journal['check_out_latitude']) && !empty($journal['check_out_longitude'])): ?>
                                            <button class="btn btn-danger btn-sm view-location-btn"
                                                    data-journal-id="<?php echo $journal['journal_id']; ?>"
                                                    data-location-type="check_out"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#locationModal"
                                                    title="Lihat Lokasi Check-out">
                                                <i class="fas fa-map-marker-alt"></i> Out
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">Tidak ada data yang cocok dengan filter yang diterapkan.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk Melihat Jurnal -->
<div class="modal fade" id="viewJournalModal" tabindex="-1" aria-labelledby="viewJournalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewJournalModalLabel">Detail Jurnal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6 class="mb-3">Jurnal: <span id="modal_student_name" class="fw-normal"></span> - <span id="modal_journal_date" class="fw-normal"></span></h6>
                <hr>
                <p id="journal_activities_content" style="white-space: pre-wrap;"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk Peta Lokasi -->
<div class="modal fade" id="locationModal" tabindex="-1" aria-labelledby="locationModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="locationModalLabel">Lokasi Absen Siswa</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="map" style="height: 450px;"></div>
      </div>
    </div>
  </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Select2
    $('#student_id').select2({
        theme: 'bootstrap-5'
    });

    const viewJournalModal = document.getElementById('viewJournalModal');
    viewJournalModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const activities = button.dataset.activities;
        const studentName = button.dataset.studentName;
        const journalDate = button.dataset.journalDate;

        viewJournalModal.querySelector('#modal_student_name').textContent = studentName;
        viewJournalModal.querySelector('#modal_journal_date').textContent = journalDate;
        viewJournalModal.querySelector('#journal_activities_content').textContent = activities;
    });
});
</script>

<?php
if (in_array($_SESSION['user_role'], ['student', 'teacher', 'instructor', 'waka_humas'])) {
    echo '</div>';
}
require_once __DIR__ . '/templates/footer.php';
?>