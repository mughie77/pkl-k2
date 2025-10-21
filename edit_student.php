<?php
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/sidebar.php';

// Proteksi halaman
if ($_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$student_id = $_GET['id'] ?? 0;
if (empty($student_id)) {
    die("Error: ID Siswa tidak valid.");
}

try {
    // Ambil data siswa yang akan diedit
    $stmt_student = $pdo->prepare("SELECT * FROM students WHERE id = :id");
    $stmt_student->execute([':id' => $student_id]);
    $student = $stmt_student->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        die("Data siswa tidak ditemukan.");
    }

    // Ambil data untuk dropdowns
    $stmt_kelas = $pdo->query("
        SELECT k.id, CONCAT(pk.program_name, ' - ', kk.konsentrasi_name, ' - ', k.kelas_name) as full_kelas_name
        FROM kelas k
        JOIN konsentrasi_keahlian kk ON k.konsentrasi_id = kk.id
        JOIN program_keahlian pk ON kk.program_id = pk.id
        ORDER BY full_kelas_name ASC
    ");
    $classes = $stmt_kelas->fetchAll(PDO::FETCH_ASSOC);

    $stmt_years = $pdo->query("SELECT id, year_name FROM academic_years ORDER BY year_name DESC");
    $academic_years = $stmt_years->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Edit Data Siswa</h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Formulir Edit Siswa</h6>
            <a href="manage_students.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left me-2"></i>Kembali</a>
        </div>
        <div class="card-body">
            <form id="editStudentForm" action="core/student_actions.php" method="POST">
                <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                <input type="hidden" name="action" value="update">

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($student['name']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email (Opsional)</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($student['email']); ?>">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="nis" class="form-label">NIS</label>
                        <input type="text" class="form-control" id="nis" name="nis" value="<?php echo htmlspecialchars($student['nis']); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="nisn" class="form-label">NISN</label>
                        <input type="text" class="form-control" id="nisn" name="nisn" value="<?php echo htmlspecialchars($student['nisn']); ?>" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="birth_place" class="form-label">Tempat Lahir</label>
                        <input type="text" class="form-control" id="birth_place" name="birth_place" value="<?php echo htmlspecialchars($student['birth_place']); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="birth_date" class="form-label">Tanggal Lahir</label>
                        <input type="date" class="form-control" id="birth_date" name="birth_date" value="<?php echo htmlspecialchars($student['birth_date']); ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="address" class="form-label">Alamat Lengkap</label>
                    <textarea class="form-control" id="address" name="address" rows="2"><?php echo htmlspecialchars($student['address']); ?></textarea>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="phone" class="form-label">No. HP Siswa</label>
                        <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($student['phone']); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="parent_phone" class="form-label">No. HP Orang Tua</label>
                        <input type="tel" class="form-control" id="parent_phone" name="parent_phone" value="<?php echo htmlspecialchars($student['parent_phone']); ?>">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="kelas_id" class="form-label">Kelas</label>
                        <select class="form-select" id="kelas_id" name="kelas_id" required>
                            <option value="">-- Pilih Kelas --</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['id']; ?>" <?php echo ($student['kelas_id'] == $class['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($class['full_kelas_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="academic_year_id" class="form-label">Tahun Pelajaran</label>
                        <select class="form-select" id="academic_year_id" name="academic_year_id" required>
                            <option value="">-- Pilih Tahun Pelajaran --</option>
                            <?php foreach ($academic_years as $year): ?>
                                <option value="<?php echo $year['id']; ?>" <?php echo ($student['academic_year_id'] == $year['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($year['year_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    <a href="manage_students.php" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Inisialisasi Select2 pada halaman edit
$(document).ready(function() {
    $('#kelas_id, #academic_year_id').select2({
        theme: 'bootstrap-5'
    });
});
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
