<?php
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/sidebar.php';

// Proteksi halaman
if ($_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Ambil data program keahlian dari database
try {
    $stmt = $pdo->query("SELECT * FROM program_keahlian ORDER BY program_name ASC");
    $programs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error: Could not fetch program keahlian data. " . $e->getMessage());
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Manajemen Program Keahlian</h1>

    <button type="button" class="btn btn-primary mb-4" data-bs-toggle="modal" data-bs-target="#programKeahlianModal">
        <i class="fas fa-plus-circle me-2"></i> Tambah Program Keahlian Baru
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
            <h6 class="m-0 font-weight-bold text-primary">Daftar Program Keahlian</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nama Program Keahlian</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($programs) > 0): ?>
                            <?php foreach ($programs as $index => $program): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($program['program_name']); ?></td>
                                    <td>
                                        <button class="btn btn-warning btn-sm edit-btn"
                                                data-id="<?php echo $program['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($program['program_name']); ?>"
                                                data-bs-toggle="modal" data-bs-target="#programKeahlianModal">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="core/program_keahlian_actions.php?action=delete&id=<?php echo $program['id']; ?>"
                                           class="btn btn-danger btn-sm btn-delete"
                                           onclick="return confirm('Menghapus ini akan menghapus semua data terkait (konsentrasi, kelas, siswa). Lanjutkan?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center">Belum ada data program keahlian.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk Tambah/Edit Program Keahlian -->
<div class="modal fade" id="programKeahlianModal" tabindex="-1" aria-labelledby="programKeahlianModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="programKeahlianModalLabel">Form Program Keahlian</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="programForm" action="core/program_keahlian_actions.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="program_id" id="program_id">
                    <input type="hidden" name="action" id="form_action" value="create">
                    <div class="mb-3">
                        <label for="program_name" class="form-label">Nama Program Keahlian</label>
                        <input type="text" class="form-control" id="program_name" name="program_name" required>
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
    const programModal = document.getElementById('programKeahlianModal');
    programModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const modalTitle = programModal.querySelector('.modal-title');
        const form = document.getElementById('programForm');
        const actionInput = document.getElementById('form_action');
        const programIdInput = document.getElementById('program_id');

        if (button.classList.contains('edit-btn')) {
            modalTitle.textContent = 'Edit Program Keahlian';
            actionInput.value = 'update';
            programIdInput.value = button.dataset.id;
            document.getElementById('program_name').value = button.dataset.name;
        } else {
            modalTitle.textContent = 'Tambah Program Keahlian Baru';
            actionInput.value = 'create';
            form.reset();
            programIdInput.value = '';
        }
    });
});
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>