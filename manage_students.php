<?php
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/sidebar.php';

// Proteksi halaman
if ($_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Ambil data siswa dari database
try {
    $stmt = $pdo->query("SELECT * FROM students ORDER BY name ASC");
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error: Could not fetch students data. " . $e->getMessage());
}

?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Manajemen Data Siswa</h1>

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
            <h6 class="m-0 font-weight-bold text-primary">Daftar Siswa</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nama Lengkap</th>
                            <th>NISN (Username)</th>
                            <th>Email (Opsional)</th>
                            <th>Jurusan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($students) > 0): ?>
                            <?php foreach ($students as $index => $student): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($student['name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['nisn']); ?></td>
                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                    <td><?php echo htmlspecialchars($student['department']); ?></td>
                                    <td>
                                        <button class="btn btn-warning btn-sm edit-btn"
                                                data-id="<?php echo $student['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($student['name']); ?>"
                                                data-nisn="<?php echo htmlspecialchars($student['nisn']); ?>"
                                                data-email="<?php echo htmlspecialchars($student['email']); ?>"
                                                data-department="<?php echo htmlspecialchars($student['department']); ?>"
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
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">Belum ada data siswa.</td>
                            </tr>
                        <?php endif; ?>
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
                        <label for="nisn" class="form-label">NISN (Nomor Induk Siswa Nasional)</label>
                        <input type="text" class="form-control" id="nisn" name="nisn" required>
                        <small class="form-text text-muted">NISN akan digunakan sebagai username dan password default.</small>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email (Opsional)</label>
                        <input type="email" class="form-control" id="email" name="email">
                    </div>
                    <div class="mb-3">
                        <label for="department" class="form-label">Jurusan</label>
                        <input type="text" class="form-control" id="department" name="department" required>
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
document.addEventListener('DOMContentLoaded', function() {
    const studentModal = document.getElementById('studentModal');
    studentModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const modalTitle = studentModal.querySelector('.modal-title');
        const form = document.getElementById('studentForm');
        const actionInput = document.getElementById('form_action');
        const studentIdInput = document.getElementById('student_id');

        if (button.classList.contains('edit-btn')) {
            modalTitle.textContent = 'Edit Data Siswa';
            actionInput.value = 'update';
            studentIdInput.value = button.dataset.id;

            document.getElementById('name').value = button.dataset.name;
            document.getElementById('nisn').value = button.dataset.nisn;
            document.getElementById('email').value = button.dataset.email;
            document.getElementById('department').value = button.dataset.department;

        } else {
            modalTitle.textContent = 'Tambah Siswa Baru';
            actionInput.value = 'create';
            form.reset();
            studentIdInput.value = '';
        }
    });
});
</script>

<?php
require_once __DIR__ . '/templates/footer.php';
?>