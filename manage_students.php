<?php
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/sidebar.php';

// Proteksi halaman
if ($_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Ambil data untuk dropdowns
try {
    // Mengambil data kelas dengan nama program dan konsentrasi keahlian
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
    die("Error fetching dropdown data: " . $e->getMessage());
}

// Ambil data siswa dari tahun ajaran aktif
$active_year_id = $active_year['id'] ?? 0;
try {
    $stmt = $pdo->prepare("
        SELECT s.id, s.name, s.nisn, s.nis, s.email, s.birth_place, s.birth_date, s.address, s.phone, s.parent_phone, s.academic_year_id, s.kelas_id,
        k.kelas_name, kk.konsentrasi_name, pk.program_name
        FROM students s
        LEFT JOIN kelas k ON s.kelas_id = k.id
        LEFT JOIN konsentrasi_keahlian kk ON k.konsentrasi_id = kk.id
        LEFT JOIN program_keahlian pk ON kk.program_id = pk.id
        WHERE s.academic_year_id = :year_id
        ORDER BY s.name ASC
    ");
    $stmt->execute([':year_id' => $active_year_id]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error: Could not fetch students data. " . $e->getMessage());
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Manajemen Data Siswa <span class="badge bg-info"><?php echo htmlspecialchars($active_year['year_name'] ?? 'Tahun Ajaran Belum Dipilih'); ?></span></h1>

    <button type="button" class="btn btn-primary mb-4" data-bs-toggle="modal" data-bs-target="#studentModal">
        <i class="fas fa-plus-circle me-2"></i> Tambah Siswa Baru
    </button>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['flash_message']['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['flash_message']['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Siswa Tahun Pelajaran Aktif</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Nama Lengkap</th>
                            <th>NISN</th>
                            <th>Program Keahlian</th>
                            <th>Kelas</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['name']); ?></td>
                                <td><?php echo htmlspecialchars($student['nisn']); ?></td>
                                <td><?php echo htmlspecialchars($student['program_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['kelas_name']); ?></td>
                                <td>
                                    <button class="btn btn-warning btn-sm edit-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#studentModal"
                                            data-student='<?php echo htmlspecialchars(json_encode($student), ENT_QUOTES, 'UTF-8'); ?>'>
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="student_details.php?id=<?php echo $student['id']; ?>" class="btn btn-info btn-sm">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="core/student_actions.php?action=delete&id=<?php echo $student['id']; ?>"
                                       class="btn btn-danger btn-sm btn-delete"
                                       onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk Tambah/Edit Siswa -->
<div class="modal fade" id="studentModal" tabindex="-1" aria-labelledby="studentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="studentModalLabel">Form Data Siswa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="studentForm" action="core/student_actions.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="student_id" id="student_id">
                    <input type="hidden" name="action" id="form_action" value="create">

                    <div class="row">
                        <div class="col-md-6 mb-3"><label for="name" class="form-label">Nama Lengkap</label><input type="text" class="form-control" id="name" name="name" required></div>
                        <div class="col-md-6 mb-3"><label for="email" class="form-label">Email (Opsional)</label><input type="email" class="form-control" id="email" name="email"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label for="nis" class="form-label">NIS</label><input type="text" class="form-control" id="nis" name="nis"></div>
                        <div class="col-md-6 mb-3"><label for="nisn" class="form-label">NISN</label><input type="text" class="form-control" id="nisn" name="nisn" required></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label for="birth_place" class="form-label">Tempat Lahir</label><input type="text" class="form-control" id="birth_place" name="birth_place"></div>
                        <div class="col-md-6 mb-3"><label for="birth_date" class="form-label">Tanggal Lahir</label><input type="date" class="form-control" id="birth_date" name="birth_date"></div>
                    </div>
                    <div class="mb-3"><label for="address" class="form-label">Alamat Lengkap</label><textarea class="form-control" id="address" name="address" rows="2"></textarea></div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label for="phone" class="form-label">No. HP Siswa</label><input type="tel" class="form-control" id="phone" name="phone"></div>
                        <div class="col-md-6 mb-3"><label for="parent_phone" class="form-label">No. HP Orang Tua</label><input type="tel" class="form-control" id="parent_phone" name="parent_phone"></div>
                    </div>
                     <div class="row">
                        <div class="col-md-6 mb-3"><label for="kelas_id" class="form-label">Kelas</label><select class="form-select" id="kelas_id" name="kelas_id" required><option value="">-- Pilih Kelas --</option><?php foreach ($classes as $class): ?><option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['full_kelas_name']); ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-6 mb-3"><label for="academic_year_id" class="form-label">Tahun Pelajaran</label><select class="form-select" id="academic_year_id" name="academic_year_id" required><option value="">-- Pilih Tahun Pelajaran --</option><?php foreach ($academic_years as $year): ?><option value="<?php echo $year['id']; ?>"><?php echo htmlspecialchars($year['year_name']); ?></option><?php endforeach; ?></select></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    const initStudentSelect2 = () => {
        $('#kelas_id, #academic_year_id').select2({ theme: 'bootstrap-5', dropdownParent: $('#studentModal') });
    };

    const studentModal = document.getElementById('studentModal');
    studentModal.addEventListener('show.bs.modal', function(event) {
        initStudentSelect2();
        const button = event.relatedTarget;
        const form = document.getElementById('studentForm');

        if (button.classList.contains('edit-btn')) {
            form.querySelector('.modal-title').textContent = 'Edit Data Siswa';
            form.querySelector('#form_action').value = 'update';
            const studentData = JSON.parse(button.dataset.student);

            document.getElementById('student_id').value = studentData.id;
            document.getElementById('name').value = studentData.name;
            document.getElementById('email').value = studentData.email || '';
            document.getElementById('nis').value = studentData.nis || '';
            document.getElementById('nisn').value = studentData.nisn || '';
            document.getElementById('birth_place').value = studentData.birth_place || '';
            document.getElementById('birth_date').value = studentData.birth_date || '';
            document.getElementById('address').value = studentData.address || '';
            document.getElementById('phone').value = studentData.phone || '';
            document.getElementById('parent_phone').value = studentData.parent_phone || '';

            // Tetap gunakan jQuery untuk Select2 karena memerlukan trigger
            $('#kelas_id').val(studentData.kelas_id).trigger('change');
            $('#academic_year_id').val(studentData.academic_year_id).trigger('change');
        } else {
            form.querySelector('.modal-title').textContent = 'Tambah Siswa Baru';
            form.querySelector('#form_action').value = 'create';
            form.reset();
            $('#kelas_id, #academic_year_id').val(null).trigger('change');
            $('#academic_year_id').val('<?php echo $active_year_id; ?>').trigger('change');
        }
    });
});
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>