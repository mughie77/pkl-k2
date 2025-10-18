<?php
require_once __DIR__ . '/templates/header.php';

// Proteksi halaman
$allowed_roles = ['waka_humas', 'teacher'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: login.php");
    exit;
}

$academic_year_id = $_SESSION['selected_academic_year_id'];
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Ambil data untuk filter
$programs = $pdo->query("SELECT * FROM program_keahlian ORDER BY program_name")->fetchAll(PDO::FETCH_ASSOC);
$konsentrasi = [];
if (isset($_GET['program_id']) && !empty($_GET['program_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM konsentrasi_keahlian WHERE program_id = ? ORDER BY name");
    $stmt->execute([$_GET['program_id']]);
    $konsentrasi = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
$kelas = [];
if (isset($_GET['konsentrasi_id']) && !empty($_GET['konsentrasi_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM kelas WHERE konsentrasi_id = ? ORDER BY name");
    $stmt->execute([$_GET['konsentrasi_id']]);
    $kelas = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Bangun query utama
$sql = "
    SELECT s.id, s.name as student_name, s.nisn, k.name as kelas_name, kk.name as konsentrasi_name, pk.program_name
    FROM students s
    JOIN internship_assessments ia ON s.id = ia.student_id
    JOIN kelas k ON s.kelas_id = k.id
    JOIN konsentrasi_keahlian kk ON k.konsentrasi_id = kk.id
    JOIN program_keahlian pk ON kk.program_id = pk.id
    WHERE ia.academic_year_id = :academic_year_id
";

// Filter tambahan untuk guru
if ($role === 'teacher') {
    $sql .= " AND s.id IN (SELECT student_id FROM internship_mappings WHERE teacher_id = :user_id AND academic_year_id = :academic_year_id)";
}

// Terapkan filter dari form
if (!empty($_GET['program_id'])) {
    $sql .= " AND pk.id = :program_id";
}
if (!empty($_GET['konsentrasi_id'])) {
    $sql .= " AND kk.id = :konsentrasi_id";
}
if (!empty($_GET['kelas_id'])) {
    $sql .= " AND k.id = :kelas_id";
}

$sql .= " ORDER BY pk.program_name, kk.name, k.name, s.name";
$stmt = $pdo->prepare($sql);

$params = [':academic_year_id' => $academic_year_id];
if ($role === 'teacher') {
    $params[':user_id'] = $user_id;
}
if (!empty($_GET['program_id'])) {
    $params[':program_id'] = $_GET['program_id'];
}
if (!empty($_GET['konsentrasi_id'])) {
    $params[':konsentrasi_id'] = $_GET['konsentrasi_id'];
}
if (!empty($_GET['kelas_id'])) {
    $params[':kelas_id'] = $_GET['kelas_id'];
}

$stmt->execute($params);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Laporan Penilaian PKL</h1>

    <!-- Filter Form -->
    <div class="card shadow mb-4">
        <div class="card-header">Filter Siswa</div>
        <div class="card-body">
            <form id="filterForm" method="GET" action="">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="program_id" class="form-label">Program Keahlian</label>
                        <select name="program_id" id="program_id" class="form-select" onchange="this.form.submit()">
                            <option value="">Semua Program</option>
                            <?php foreach ($programs as $program): ?>
                                <option value="<?= $program['id'] ?>" <?= (isset($_GET['program_id']) && $_GET['program_id'] == $program['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($program['program_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="konsentrasi_id" class="form-label">Konsentrasi Keahlian</label>
                        <select name="konsentrasi_id" id="konsentrasi_id" class="form-select" onchange="this.form.submit()" <?= empty($_GET['program_id']) ? 'disabled' : '' ?>>
                            <option value="">Semua Konsentrasi</option>
                            <?php foreach ($konsentrasi as $k): ?>
                                <option value="<?= $k['id'] ?>" <?= (isset($_GET['konsentrasi_id']) && $_GET['konsentrasi_id'] == $k['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($k['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="kelas_id" class="form-label">Kelas</label>
                        <select name="kelas_id" id="kelas_id" class="form-select" <?= empty($_GET['konsentrasi_id']) ? 'disabled' : '' ?>>
                            <option value="">Semua Kelas</option>
                            <?php foreach ($kelas as $kl): ?>
                                <option value="<?= $kl['id'] ?>" <?= (isset($_GET['kelas_id']) && $_GET['kelas_id'] == $kl['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($kl['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                 <button type="submit" class="btn btn-primary">Filter</button>
                 <a href="assessment_report.php" class="btn btn-secondary">Reset</a>
            </form>
        </div>
    </div>

    <!-- Student List -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Siswa Telah Dinilai</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>NISN</th>
                            <th>Nama Siswa</th>
                            <th>Kelas</th>
                            <th>Konsentrasi Keahlian</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($students) > 0): ?>
                            <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?= htmlspecialchars($student['nisn']) ?></td>
                                <td><?= htmlspecialchars($student['student_name']) ?></td>
                                <td><?= htmlspecialchars($student['kelas_name']) ?></td>
                                <td><?= htmlspecialchars($student['konsentrasi_name']) ?></td>
                                <td>
                                    <a href="generate_pdf_report.php?student_id=<?= $student['id'] ?>" class="btn btn-danger btn-sm" target="_blank">
                                        <i class="fas fa-file-pdf"></i> Cetak Rapor
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">Tidak ada siswa yang telah dinilai dengan filter yang dipilih.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>