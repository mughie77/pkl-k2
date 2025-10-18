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
    $depts = $pdo->query("SELECT id, program_name as department_name FROM program_keahlian ORDER BY program_name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $teachers = $pdo->query("SELECT id, name FROM teachers ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $companies = $pdo->query("SELECT id, name FROM companies ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching filter data: " . $e->getMessage());
}

// Proses filter
$filter_program = $_GET['program_id'] ?? 'all';
$filter_teacher = $_GET['teacher_id'] ?? 'all';
$filter_company = $_GET['company_id'] ?? 'all';
$filter_start_date = $_GET['start_date'] ?? '';
$filter_end_date = $_GET['end_date'] ?? '';

$query = "
    SELECT
        m.start_date, m.end_date, m.status,
        s.name as student_name,
        pk.program_name,
        c.name as company_name,
        t.name as teacher_name,
        i.name as instructor_name
    FROM internship_mappings m
    JOIN students s ON m.student_id = s.id
    JOIN kelas k ON s.kelas_id = k.id
    JOIN konsentrasi_keahlian kk ON k.konsentrasi_id = kk.id
    JOIN program_keahlian pk ON kk.program_id = pk.id
    JOIN instructors i ON m.instructor_id = i.id
    JOIN companies c ON i.company_id = c.id
    JOIN teachers t ON m.teacher_id = t.id
";
$params = [];
$where_clauses = [];

if ($filter_program !== 'all') { $where_clauses[] = "pk.id = :program_id"; $params[':program_id'] = $filter_program; }
if ($filter_teacher !== 'all') { $where_clauses[] = "m.teacher_id = :teacher_id"; $params[':teacher_id'] = $filter_teacher; }
if ($filter_company !== 'all') { $where_clauses[] = "i.company_id = :company_id"; $params[':company_id'] = $filter_company; }
if (!empty($filter_start_date)) { $where_clauses[] = "m.start_date >= :start_date"; $params[':start_date'] = $filter_start_date; }
if (!empty($filter_end_date)) { $where_clauses[] = "m.end_date <= :end_date"; $params[':end_date'] = $filter_end_date; }

if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(" AND ", $where_clauses);
}

$query .= " ORDER BY c.name, s.name ASC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $mappings_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching mapping data: " . $e->getMessage());
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Laporan Pemetaan Siswa</h1>

    <div class="card shadow mb-4">
        <div class="card-header"><h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-filter me-2"></i>Filter Data</h6></div>
        <div class="card-body">
            <form method="GET" action="mapping_report.php" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">DUDIKA</label>
                    <select name="company_id" class="form-select select2">
                        <option value="all">Semua DUDIKA</option>
                        <?php foreach ($companies as $c): ?><option value="<?php echo $c['id']; ?>" <?php echo ($filter_company == $c['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['name']); ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Guru</label>
                    <select name="teacher_id" class="form-select select2">
                        <option value="all">Semua Guru</option>
                        <?php foreach ($teachers as $t): ?><option value="<?php echo $t['id']; ?>" <?php echo ($filter_teacher == $t['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($t['name']); ?></option><?php endforeach; ?>
                    </select>
                </div>
                 <div class="col-md-2">
                    <label class="form-label">Program Keahlian</label>
                    <select name="program_id" class="form-select select2">
                        <option value="all">Semua Program</option>
                        <?php foreach ($depts as $d): ?><option value="<?php echo $d['id']; ?>" <?php echo ($filter_program == $d['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($d['department_name']); ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Dari</label>
                    <input type="date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($filter_start_date); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Sampai</label>
                    <input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($filter_end_date); ?>">
                </div>
                <div class="col-md-12 text-end">
                    <button type="submit" class="btn btn-primary">Cari</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Hasil Laporan</h6></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Nama Siswa</th>
                            <th>Program Keahlian</th>
                            <th>DUDIKA</th>
                            <th>Guru Pembimbing</th>
                            <th>Instruktur Lapangan</th>
                            <th>Periode</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($mappings_data) > 0): ?>
                            <?php foreach ($mappings_data as $map): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($map['student_name']); ?></td>
                                    <td><?php echo htmlspecialchars($map['program_name']); ?></td>
                                    <td><?php echo htmlspecialchars($map['company_name']); ?></td>
                                    <td><?php echo htmlspecialchars($map['teacher_name']); ?></td>
                                    <td><?php echo htmlspecialchars($map['instructor_name']); ?></td>
                                    <td><?php echo date('d M Y', strtotime($map['start_date'])) . ' - ' . date('d M Y', strtotime($map['end_date'])); ?></td>
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
    $('.select2').select2({ theme: 'bootstrap-5' });
});
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>