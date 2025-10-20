<?php
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/sidebar.php';

// Proteksi halaman
if ($_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Ambil data jurusan dari database
try {
    $stmt = $pdo->query("SELECT * FROM departments ORDER BY department_name ASC");
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error: Could not fetch departments data. " . $e->getMessage());
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Manajemen Data Jurusan</h1>

    <button type="button" class="btn btn-primary mb-4" data-bs-toggle="modal" data-bs-target="#departmentModal">
        <i class="fas fa-plus-circle me-2"></i> Tambah Jurusan Baru
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
            <h6 class="m-0 font-weight-bold text-primary">Daftar Jurusan</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nama Jurusan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($departments) > 0): ?>
                            <?php foreach ($departments as $index => $department): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($department['department_name']); ?></td>
                                    <td>
                                        <button class="btn btn-warning btn-sm edit-btn"
                                                data-id="<?php echo $department['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($department['department_name']); ?>"
                                                data-bs-toggle="modal" data-bs-target="#departmentModal">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="core/department_actions.php?action=delete&id=<?php echo $department['id']; ?>"
                                           class="btn btn-danger btn-sm btn-delete"
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus jurusan ini?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center">Belum ada data jurusan.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk Tambah/Edit Jurusan -->
<div class="modal fade" id="departmentModal" tabindex="-1" aria-labelledby="departmentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="departmentModalLabel">Form Data Jurusan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="departmentForm" action="core/department_actions.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="department_id" id="department_id">
                    <input type="hidden" name="action" id="form_action" value="create">
                    <div class="mb-3">
                        <label for="department_name" class="form-label">Nama Jurusan</label>
                        <input type="text" class="form-control" id="department_name" name="department_name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const departmentModal = document.getElementById('departmentModal');
    departmentModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const modalTitle = departmentModal.querySelector('.modal-title');
        const form = document.getElementById('departmentForm');
        const actionInput = document.getElementById('form_action');
        const departmentIdInput = document.getElementById('department_id');

        if (button.classList.contains('edit-btn')) {
            modalTitle.textContent = 'Edit Data Jurusan';
            actionInput.value = 'update';
            departmentIdInput.value = button.dataset.id;
            document.getElementById('department_name').value = button.dataset.name;
        } else {
            modalTitle.textContent = 'Tambah Jurusan Baru';
            actionInput.value = 'create';
            form.reset();
            departmentIdInput.value = '';
        }
    });
});
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>