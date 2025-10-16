<?php
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/sidebar.php';

// Proteksi halaman
if ($_SESSION['user_role'] !== 'waka_humas') {
    header("Location: login.php");
    exit;
}

// Filter
$filter_company_id = $_GET['company_id'] ?? 'all';
$filter_department_id = $_GET['department_id'] ?? 'all';
$filter_teacher_id = $_GET['teacher_id'] ?? 'all';
$filter_search = $_GET['search'] ?? '';
$filter_start_date = $_GET['start_date'] ?? '';
$filter_end_date = $_GET['end_date'] ?? '';

try {
    // Ambil data untuk filter
    $companies = $pdo->query("SELECT id, name FROM companies ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $departments = $pdo->query("SELECT id, department_name FROM departments ORDER BY department_name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $teachers = $pdo->query("SELECT id, name FROM teachers ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

    // Bangun query utama
    $query = "
        SELECT
            j.id as journal_id, j.journal_date, j.check_in_time, j.check_out_time, j.status,
            s.name as student_name,
            c.name as company_name,
            d.department_name,
            t.name as teacher_name
        FROM internship_journals j
        JOIN students s ON j.student_id = s.id
        JOIN internship_mappings m ON s.id = m.student_id
        JOIN instructors i ON m.instructor_id = i.id
        JOIN companies c ON i.company_id = c.id
        JOIN departments d ON s.department_id = d.id
        JOIN teachers t ON m.teacher_id = t.id
    ";

    $params = [];
    $where_clauses = [];

    if ($filter_company_id !== 'all') {
        $where_clauses[] = "c.id = :company_id";
        $params[':company_id'] = $filter_company_id;
    }
    if ($filter_department_id !== 'all') {
        $where_clauses[] = "d.id = :department_id";
        $params[':department_id'] = $filter_department_id;
    }
    if ($filter_teacher_id !== 'all') {
        $where_clauses[] = "t.id = :teacher_id";
        $params[':teacher_id'] = $filter_teacher_id;
    }
    if (!empty($filter_search)) {
        $where_clauses[] = "s.name LIKE :search";
        $params[':search'] = "%" . $filter_search . "%";
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
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-filter me-2"></i>Filter Data Absensi</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="rekap_absensi_waka.php" class="row g-3">
                <div class="col-md-3">
                    <label for="search" class="form-label">Cari Nama Siswa</label>
                    <input type="text" name="search" id="search" class="form-control" value="<?php echo htmlspecialchars($filter_search); ?>" placeholder="Ketik nama...">
                </div>
                <div class="col-md-3">
                    <label for="company_id" class="form-label">DUDIKA</label>
                    <select name="company_id" id="company_id" class="form-select">
                        <option value="all">Semua DUDIKA</option>
                        <?php foreach ($companies as $company): ?>
                            <option value="<?php echo $company['id']; ?>" <?php echo ($filter_company_id == $company['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($company['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="department_id" class="form-label">Jurusan</label>
                    <select name="department_id" id="department_id" class="form-select">
                        <option value="all">Semua Jurusan</option>
                        <?php foreach ($departments as $department): ?>
                            <option value="<?php echo $department['id']; ?>" <?php echo ($filter_department_id == $department['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($department['department_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="teacher_id" class="form-label">Guru Pembimbing</label>
                    <select name="teacher_id" id="teacher_id" class="form-select">
                        <option value="all">Semua Guru</option>
                        <?php foreach ($teachers as $teacher): ?>
                            <option value="<?php echo $teacher['id']; ?>" <?php echo ($filter_teacher_id == $teacher['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($teacher['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="start_date" class="form-label">Dari Tanggal</label>
                    <input type="date" name="start_date" id="start_date" class="form-control" value="<?php echo htmlspecialchars($filter_start_date); ?>">
                </div>
                <div class="col-md-2">
                    <label for="end_date" class="form-label">Sampai Tanggal</label>
                    <input type="date" name="end_date" id="end_date" class="form-control" value="<?php echo htmlspecialchars($filter_end_date); ?>">
                </div>
                <div class="col-md-2 align-self-end">
                    <button type="submit" class="btn btn-primary w-100">Terapkan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Rekapitulasi Absensi dan Jurnal Siswa</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Nama Siswa</th>
                            <th>Jurusan</th>
                            <th>DUDIKA</th>
                            <th>Guru Pembimbing</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Status Jurnal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($journals) > 0): ?>
                            <?php foreach ($journals as $journal): ?>
                                <tr>
                                    <td><?php echo date('d M Y', strtotime($journal['journal_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($journal['student_name']); ?></td>
                                    <td><?php echo htmlspecialchars($journal['department_name']); ?></td>
                                    <td><?php echo htmlspecialchars($journal['company_name']); ?></td>
                                    <td><?php echo htmlspecialchars($journal['teacher_name']); ?></td>
                                    <td><?php echo $journal['check_in_time'] ? date('H:i', strtotime($journal['check_in_time'])) : '<span class="badge bg-secondary">N/A</span>'; ?></td>
                                    <td><?php echo $journal['check_out_time'] ? date('H:i', strtotime($journal['check_out_time'])) : '<span class="badge bg-secondary">N/A</span>'; ?></td>
                                    <td>
                                        <span class="badge <?php echo get_status_badge($journal['status']); ?>">
                                            <?php echo htmlspecialchars($journal['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">Tidak ada data yang cocok dengan filter yang diterapkan.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk Peta Lokasi -->
<div class="modal fade" id="locationModal" tabindex="-1" aria-labelledby="locationModalLabel" aria-hidden="true">
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
    $('#company_id, #department_id, #teacher_id').select2({
        theme: 'bootstrap-5'
    });
});
</script>

<?php
require_once __DIR__ . '/templates/footer.php';
?>