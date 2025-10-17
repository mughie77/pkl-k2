<?php
require_once __DIR__ . '/templates/header.php';

// Proteksi halaman
$allowed_roles = ['waka_humas', 'admin'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    header("Location: login.php");
    exit;
}

// Ambil data untuk filter
try {
    $depts = $pdo->query("SELECT id, department_name FROM departments ORDER BY department_name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $students = $pdo->query("SELECT id, name, department_id FROM students ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching filter data: " . $e->getMessage());
}

// Proses filter
$filter_dept = $_GET['department_id'] ?? 'all';
$filter_student = $_GET['student_id'] ?? 'all';
$filter_start_date = $_GET['start_date'] ?? '';
$filter_end_date = $_GET['end_date'] ?? '';

$query = "
    SELECT j.*, s.name as student_name, d.department_name
    FROM internship_journals j
    JOIN students s ON j.student_id = s.id
    JOIN departments d ON s.department_id = d.id
";
$params = [];
$where_clauses = [];

if ($filter_dept !== 'all') {
    $where_clauses[] = "s.department_id = :dept_id";
    $params[':dept_id'] = $filter_dept;
}
if ($filter_student !== 'all') {
    $where_clauses[] = "j.student_id = :student_id";
    $params[':student_id'] = $filter_student;
}
if (!empty($filter_start_date)) {
    $where_clauses[] = "j.journal_date >= :start_date";
    $params[':start_date'] = $filter_start_date;
}
if (!empty($filter_end_date)) {
    $where_clauses[] = "j.journal_date <= :end_date";
    $params[':end_date'] = $filter_end_date;
}

if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(" AND ", $where_clauses);
}

$query .= " ORDER BY j.journal_date DESC, s.name ASC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $attendance_recap = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching recap data: " . $e->getMessage());
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
    <h1 class="h3 mb-4 text-gray-800">Rekap Absensi Siswa</h1>

    <div class="card shadow mb-4">
        <div class="card-header"><h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-filter me-2"></i>Filter Data</h6></div>
        <div class="card-body">
            <form method="GET" action="global_recap.php" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="department_id" class="form-label">Jurusan</label>
                    <select name="department_id" id="department_id" class="form-select">
                        <option value="all">Semua Jurusan</option>
                        <?php foreach ($depts as $dept): ?>
                            <option value="<?php echo $dept['id']; ?>" <?php echo ($filter_dept == $dept['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($dept['department_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="student_id" class="form-label">Siswa</label>
                    <select name="student_id" id="student_id" class="form-select">
                        <option value="all" data-dept-id="all">Semua Siswa</option>
                        <?php foreach ($students as $student): ?>
                            <option value="<?php echo $student['id']; ?>" data-dept-id="<?php echo $student['department_id']; ?>" <?php echo ($filter_student == $student['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($student['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="start_date" class="form-label">Dari</label>
                    <input type="date" name="start_date" id="start_date" class="form-control" value="<?php echo htmlspecialchars($filter_start_date); ?>">
                </div>
                <div class="col-md-2">
                    <label for="end_date" class="form-label">Sampai</label>
                    <input type="date" name="end_date" id="end_date" class="form-control" value="<?php echo htmlspecialchars($filter_end_date); ?>">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary w-100">Cari</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Hasil Rekap</h6></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Nama Siswa</th>
                            <th>Jurusan</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Status Jurnal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($attendance_recap) > 0): ?>
                            <?php foreach ($attendance_recap as $recap): ?>
                                <tr>
                                    <td><?php echo date('d M Y', strtotime($recap['journal_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($recap['student_name']); ?></td>
                                    <td><?php echo htmlspecialchars($recap['department_name']); ?></td>
                                    <td><?php echo $recap['check_in_time'] ? date('H:i', strtotime($recap['check_in_time'])) : '-'; ?></td>
                                    <td><?php echo $recap['check_out_time'] ? date('H:i', strtotime($recap['check_out_time'])) : '-'; ?></td>
                                    <td><span class="badge <?php echo get_status_badge($recap['status']); ?>"><?php echo htmlspecialchars($recap['status']); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center">Tidak ada data yang ditemukan.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#department_id, #student_id').select2({ theme: 'bootstrap-5' });

    $('#department_id').on('change', function() {
        const selectedDeptId = $(this).val();
        $('#student_id > option').each(function() {
            const studentDeptId = $(this).data('dept-id');
            if (selectedDeptId === 'all' || studentDeptId === 'all' || studentDeptId == selectedDeptId) {
                $(this).prop('disabled', false);
            } else {
                $(this).prop('disabled', true);
            }
        });
        $('#student_id').val('all').trigger('change.select2');
    });
});
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>