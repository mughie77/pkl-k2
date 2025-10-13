<?php
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/sidebar.php';

// Proteksi halaman
if ($_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Ambil data tahun ajaran dari database
try {
    $stmt = $pdo->query("SELECT * FROM academic_years ORDER BY year_name DESC");
    $academic_years = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error: Could not fetch academic years data. " . $e->getMessage());
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Manajemen Tahun Pelajaran</h1>

    <button type="button" class="btn btn-primary mb-4" data-bs-toggle="modal" data-bs-target="#yearModal">
        <i class="fas fa-plus-circle me-2"></i> Tambah Tahun Pelajaran Baru
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
            <h6 class="m-0 font-weight-bold text-primary">Daftar Tahun Pelajaran</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Tahun Pelajaran</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($academic_years as $year): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($year['year_name']); ?></td>
                                <td>
                                    <?php if ($year['status'] === 'active'): ?>
                                        <span class="badge bg-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Tidak Aktif</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($year['status'] !== 'active'): ?>
                                        <a href="core/academic_year_actions.php?action=activate&id=<?php echo $year['id']; ?>" class="btn btn-success btn-sm">
                                            <i class="fas fa-check"></i> Aktifkan
                                        </a>
                                    <?php endif; ?>
                                    <button class="btn btn-warning btn-sm edit-btn"
                                            data-id="<?php echo $year['id']; ?>"
                                            data-name="<?php echo htmlspecialchars($year['year_name']); ?>"
                                            data-bs-toggle="modal" data-bs-target="#yearModal">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="core/academic_year_actions.php?action=delete&id=<?php echo $year['id']; ?>"
                                       class="btn btn-danger btn-sm btn-delete"
                                       onclick="return confirm('Apakah Anda yakin ingin menghapus tahun pelajaran ini?');">
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

<!-- Modal untuk Tambah/Edit Tahun Pelajaran -->
<div class="modal fade" id="yearModal" tabindex="-1" aria-labelledby="yearModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="yearModalLabel">Form Tahun Pelajaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="yearForm" action="core/academic_year_actions.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="year_id" id="year_id">
                    <input type="hidden" name="action" id="form_action" value="create">
                    <div class="mb-3">
                        <label for="year_name" class="form-label">Tahun Pelajaran (e.g., 2024/2025)</label>
                        <input type="text" class="form-control" id="year_name" name="year_name" required>
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
    const yearModal = document.getElementById('yearModal');
    yearModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const form = document.getElementById('yearForm');

        if (button.classList.contains('edit-btn')) {
            form.querySelector('.modal-title').textContent = 'Edit Tahun Pelajaran';
            form.querySelector('#form_action').value = 'update';
            form.querySelector('#year_id').value = button.dataset.id;
            form.querySelector('#year_name').value = button.dataset.name;
        } else {
            form.querySelector('.modal-title').textContent = 'Tambah Tahun Pelajaran Baru';
            form.querySelector('#form_action').value = 'create';
            form.reset();
        }
    });
});
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>