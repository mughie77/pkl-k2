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
                                            data-id="<?php echo $student['id']; ?>"
                                            data-name="<?php echo htmlspecialchars($student['name']); ?>"
                                            data-nisn="<?php echo htmlspecialchars($student['nisn']); ?>"
                                            data-email="<?php echo htmlspecialchars($student['email']); ?>"
                                            data-department_id="<?php echo $student['department_id']; ?>"
                                            data-academic_year_id="<?php echo $student['academic_year_id']; ?>"
                                            data-bs-toggle="modal" data-bs-target="#studentModal">
                                        <i class="fas fa-edit"></i>
                                    </button>
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

                    <div class="mb-3">
                        <label for="name" class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="nisn" class="form-label">NISN</label>
                        <input type="text" class="form-control" id="nisn" name="nisn" required>
                    </div>
                     <div class="mb-3">
                        <label for="department_id" class="form-label">Jurusan</label>
                        <select class="form-select" id="department_id" name="department_id" required>
                            <option value="">-- Pilih Jurusan --</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['department_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                     <div class="mb-3">
                        <label for="academic_year_id" class="form-label">Tahun Pelajaran</label>
                        <select class="form-select" id="academic_year_id" name="academic_year_id" required>
                            <option value="">-- Pilih Tahun Pelajaran --</option>
                            <?php foreach ($academic_years as $year): ?>
                                <option value="<?php echo $year['id']; ?>"><?php echo htmlspecialchars($year['year_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email (Opsional)</label>
                        <input type="email" class="form-control" id="email" name="email">
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
    // Inisialisasi Select2 pada modal
    const initStudentSelect2 = () => {
        $('#department_id, #academic_year_id').select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#studentModal')
        });
    };

    const studentModal = document.getElementById('studentModal');
    studentModal.addEventListener('show.bs.modal', function(event) {
        initStudentSelect2();

        const button = event.relatedTarget;
        const form = document.getElementById('studentForm');

        if (button.classList.contains('edit-btn')) {
            form.querySelector('.modal-title').textContent = 'Edit Data Siswa';
            form.querySelector('#form_action').value = 'update';
            form.querySelector('#student_id').value = button.dataset.id;
            form.querySelector('#name').value = button.dataset.name;
            form.querySelector('#nisn').value = button.dataset.nisn;
            form.querySelector('#email').value = button.dataset.email;

            $('#department_id').val(button.dataset.department_id).trigger('change');
            $('#academic_year_id').val(button.dataset.academic_year_id).trigger('change');
        } else {
            form.querySelector('.modal-title').textContent = 'Tambah Siswa Baru';
            form.querySelector('#form_action').value = 'create';
            form.reset();
            $('#department_id, #academic_year_id').val(null).trigger('change');
            // Set tahun ajaran aktif sebagai default
            $('#academic_year_id').val('<?php echo $active_year_id; ?>').trigger('change');
        }
    });
});
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>