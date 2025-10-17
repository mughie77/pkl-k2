<?php
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/sidebar.php';

if ($_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

try {
    $programs = $pdo->query("SELECT * FROM program_keahlian ORDER BY program_name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $stmt = $pdo->query("
        SELECT kk.*, pk.program_name
        FROM konsentrasi_keahlian kk
        JOIN program_keahlian pk ON kk.program_id = pk.id
        ORDER BY pk.program_name, kk.konsentrasi_name ASC
    ");
    $konsentrasi_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Manajemen Konsentrasi Keahlian</h1>

    <button type="button" class="btn btn-primary mb-4" data-bs-toggle="modal" data-bs-target="#konsentrasiModal">
        <i class="fas fa-plus-circle me-2"></i> Tambah Konsentrasi Keahlian
    </button>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['flash_message']['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['flash_message']['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Daftar Konsentrasi Keahlian</h6></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Program Keahlian</th>
                            <th>Nama Konsentrasi Keahlian</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($konsentrasi_list as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['program_name']); ?></td>
                                <td><?php echo htmlspecialchars($item['konsentrasi_name']); ?></td>
                                <td>
                                    <button class="btn btn-warning btn-sm edit-btn"
                                            data-id="<?php echo $item['id']; ?>"
                                            data-name="<?php echo htmlspecialchars($item['konsentrasi_name']); ?>"
                                            data-program-id="<?php echo $item['program_id']; ?>"
                                            data-bs-toggle="modal" data-bs-target="#konsentrasiModal">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="core/konsentrasi_keahlian_actions.php?action=delete&id=<?php echo $item['id']; ?>" class="btn btn-danger btn-sm btn-delete" onclick="return confirm('Yakin ingin menghapus data ini?');"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="konsentrasiModal" tabindex="-1" aria-labelledby="konsentrasiModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="konsentrasiModalLabel">Form Konsentrasi Keahlian</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="konsentrasiForm" action="core/konsentrasi_keahlian_actions.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="konsentrasi_id" id="konsentrasi_id">
                    <input type="hidden" name="action" id="form_action" value="create">
                    <div class="mb-3">
                        <label for="program_id" class="form-label">Program Keahlian</label>
                        <select class="form-select" id="program_id" name="program_id" required>
                            <option value="">-- Pilih Program Keahlian --</option>
                            <?php foreach ($programs as $program): ?>
                                <option value="<?php echo $program['id']; ?>"><?php echo htmlspecialchars($program['program_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="konsentrasi_name" class="form-label">Nama Konsentrasi Keahlian</label>
                        <input type="text" class="form-control" id="konsentrasi_name" name="konsentrasi_name" required>
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
    const modal = document.getElementById('konsentrasiModal');
    modal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const form = document.getElementById('konsentrasiForm');

        if (button.classList.contains('edit-btn')) {
            form.querySelector('.modal-title').textContent = 'Edit Konsentrasi Keahlian';
            document.getElementById('form_action').value = 'update';
            document.getElementById('konsentrasi_id').value = button.dataset.id;
            document.getElementById('program_id').value = button.dataset.programId;
            document.getElementById('konsentrasi_name').value = button.dataset.name;
        } else {
            form.querySelector('.modal-title').textContent = 'Tambah Konsentrasi Keahlian';
            document.getElementById('form_action').value = 'create';
            form.reset();
        }
    });
});
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>