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
    $stmt_depts = $pdo->query("SELECT id, department_name FROM departments ORDER BY department_name ASC");
    $departments = $stmt_depts->fetchAll(PDO::FETCH_ASSOC);

    $stmt_years = $pdo->query("SELECT id, year_name FROM academic_years ORDER BY year_name DESC");
    $academic_years = $stmt_years->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching dropdown data: " . $e->getMessage());
}

// Ambil data siswa dari tahun ajaran aktif
$active_year_id = $active_year['id'] ?? 0;
try {
    $stmt = $pdo->prepare("
        SELECT s.*, d.department_name
        FROM students s
        JOIN departments d ON s.department_id = d.id
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
            <?php if (isset($_SESSION['error_report_file'])): ?>
                <a href="uploads/<?php echo $_SESSION['error_report_file']; ?>" class="alert-link">Unduh Laporan Error</a>
                <?php unset($_SESSION['error_report_file']); ?>
            <?php endif; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <!-- Form Import Siswa -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-file-excel me-2"></i>Import Data Siswa</h6>
        </div>
        <div class="card-body">
            <form action="core/import_actions.php" method="POST" enctype="multipart/form-data">
                <p>Gunakan template ini untuk mengimpor data siswa secara massal. Pastikan format data sesuai dengan template.</p>
                <div class="d-flex align-items-center">
                    <a href="core/download_template.php" class="btn btn-success me-3">
                        <i class="fas fa-download me-2"></i>Unduh Template
                    </a>
                    <div class="flex-grow-1">
                        <input type="file" class="form-control" name="excel_file" id="excel_file" accept=".xlsx, .xls" required>
                    </div>
                    <button type="submit" name="import_students" class="btn btn-info ms-3">
                        <i class="fas fa-upload me-2"></i>Import Data
                    </button>
                </div>
            </form>
        </div>
    </div>

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
                            <th>Jurusan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['name']); ?></td>
                                <td><?php echo htmlspecialchars($student['nisn']); ?></td>
                                <td><?php echo htmlspecialchars($student['department_name']); ?></td>
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
                        <div class="col-md-6 mb-3"><label for="department_id" class="form-label">Jurusan</label><select class="form-select" id="department_id" name="department_id" required><option value="">-- Pilih Jurusan --</option><?php foreach ($departments as $dept): ?><option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['department_name']); ?></option><?php endforeach; ?></select></div>
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
        $('#department_id, #academic_year_id').select2({ theme: 'bootstrap-5', dropdownParent: $('#studentModal') });
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

            $('#student_id').val(studentData.id);
            $('#name').val(studentData.name);
            $('#email').val(studentData.email);
            $('#nis').val(studentData.nis);
            $('#nisn').val(studentData.nisn);
            $('#birth_place').val(studentData.birth_place);
            $('#birth_date').val(studentData.birth_date);
            $('#address').val(studentData.address);
            $('#phone').val(studentData.phone);
            $('#parent_phone').val(studentData.parent_phone);
            $('#department_id').val(studentData.department_id).trigger('change');
            $('#academic_year_id').val(studentData.academic_year_id).trigger('change');
        } else {
            form.querySelector('.modal-title').textContent = 'Tambah Siswa Baru';
            form.querySelector('#form_action').value = 'create';
            form.reset();
            $('#department_id, #academic_year_id').val(null).trigger('change');
            $('#academic_year_id').val('<?php echo $active_year_id; ?>').trigger('change');
        }
    });
});
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>