<?php
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/sidebar.php';

// Proteksi halaman
if ($_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Ambil data guru dari database
try {
    $stmt = $pdo->query("SELECT * FROM teachers ORDER BY name ASC");
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error: Could not fetch teachers data. " . $e->getMessage());
}

?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Manajemen Data Guru Pembimbing</h1>

    <button type="button" class="btn btn-primary mb-4" data-bs-toggle="modal" data-bs-target="#teacherModal">
        <i class="fas fa-plus-circle me-2"></i> Tambah Guru Baru
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
            <h6 class="m-0 font-weight-bold text-primary">Daftar Guru Pembimbing</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nama Lengkap</th>
                            <th>NIP (Username)</th>
                            <th>No. HP</th>
                            <th>Jurusan/Bidang</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($teachers) > 0): ?>
                            <?php foreach ($teachers as $index => $teacher): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($teacher['name']); ?></td>
                                    <td><?php echo htmlspecialchars($teacher['nip']); ?></td>
                                    <td><?php echo htmlspecialchars($teacher['phone']); ?></td>
                                    <td><?php echo htmlspecialchars($teacher['department']); ?></td>
                                    <td>
                                        <button class="btn btn-warning btn-sm edit-btn"
                                                data-id="<?php echo $teacher['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($teacher['name']); ?>"
                                                data-nip="<?php echo htmlspecialchars($teacher['nip']); ?>"
                                                data-phone="<?php echo htmlspecialchars($teacher['phone']); ?>"
                                                data-department="<?php echo htmlspecialchars($teacher['department']); ?>"
                                                data-bs-toggle="modal" data-bs-target="#teacherModal">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="core/teacher_actions.php?action=delete&id=<?php echo $teacher['id']; ?>"
                                           class="btn btn-danger btn-sm btn-delete"
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">Belum ada data guru.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk Tambah/Edit Guru -->
<div class="modal fade" id="teacherModal" tabindex="-1" aria-labelledby="teacherModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="teacherModalLabel">Form Data Guru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="teacherForm" action="core/teacher_actions.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="teacher_id" id="teacher_id">
                    <input type="hidden" name="action" id="form_action" value="create">

                    <div class="mb-3">
                        <label for="name" class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="nip" class="form-label">NIP (Nomor Induk Pegawai)</label>
                        <input type="text" class="form-control" id="nip" name="nip" required>
                        <small class="form-text text-muted">NIP akan digunakan sebagai username dan password default.</small>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">No. HP</label>
                        <input type="tel" class="form-control" id="phone" name="phone">
                    </div>
                    <div class="mb-3">
                        <label for="department" class="form-label">Jurusan/Bidang Keahlian</label>
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
    const teacherModal = document.getElementById('teacherModal');
    teacherModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const modalTitle = teacherModal.querySelector('.modal-title');
        const form = document.getElementById('teacherForm');
        const actionInput = document.getElementById('form_action');
        const teacherIdInput = document.getElementById('teacher_id');

        if (button.classList.contains('edit-btn')) {
            modalTitle.textContent = 'Edit Data Guru';
            actionInput.value = 'update';
            teacherIdInput.value = button.dataset.id;

            document.getElementById('name').value = button.dataset.name;
            document.getElementById('nip').value = button.dataset.nip;
            document.getElementById('phone').value = button.dataset.phone;
            document.getElementById('department').value = button.dataset.department;
        } else {
            modalTitle.textContent = 'Tambah Guru Baru';
            actionInput.value = 'create';
            form.reset();
            teacherIdInput.value = '';
        }
    });
});
</script>

<?php
require_once __DIR__ . '/templates/footer.php';
?>