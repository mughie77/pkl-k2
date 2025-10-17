<?php
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/sidebar.php';

if ($_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

try {
    $konsentrasi_list = $pdo->query("
        SELECT kk.id, kk.konsentrasi_name, pk.program_name
        FROM konsentrasi_keahlian kk
        JOIN program_keahlian pk ON kk.program_id = pk.id
        ORDER BY pk.program_name, kk.konsentrasi_name ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->query("
        SELECT k.*, kk.konsentrasi_name, pk.program_name
        FROM kelas k
        JOIN konsentrasi_keahlian kk ON k.konsentrasi_id = kk.id
        JOIN program_keahlian pk ON kk.program_id = pk.id
        ORDER BY pk.program_name, kk.konsentrasi_name, k.kelas_name ASC
    ");
    $kelas_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Manajemen Kelas</h1>

    <button type="button" class="btn btn-primary mb-4" data-bs-toggle="modal" data-bs-target="#kelasModal">
        <i class="fas fa-plus-circle me-2"></i> Tambah Kelas
    </button>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['flash_message']['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['flash_message']['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Daftar Kelas</h6></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Program Keahlian</th>
                            <th>Konsentrasi Keahlian</th>
                            <th>Nama Kelas</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($kelas_list as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['program_name']); ?></td>
                                <td><?php echo htmlspecialchars($item['konsentrasi_name']); ?></td>
                                <td><?php echo htmlspecialchars($item['kelas_name']); ?></td>
                                <td>
                                    <button class="btn btn-warning btn-sm edit-btn"
                                            data-id="<?php echo $item['id']; ?>"
                                            data-name="<?php echo htmlspecialchars($item['kelas_name']); ?>"
                                            data-konsentrasi-id="<?php echo $item['konsentrasi_id']; ?>"
                                            data-bs-toggle="modal" data-bs-target="#kelasModal">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="core/kelas_actions.php?action=delete&id=<?php echo $item['id']; ?>" class="btn btn-danger btn-sm btn-delete" onclick="return confirm('Yakin ingin menghapus data ini?');"><i class="fas fa-trash"></i></a>
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
<div class="modal fade" id="kelasModal" tabindex="-1" aria-labelledby="kelasModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="kelasModalLabel">Form Kelas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="kelasForm" action="core/kelas_actions.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="kelas_id" id="kelas_id">
                    <input type="hidden" name="action" id="form_action" value="create">
                    <div class="mb-3">
                        <label for="konsentrasi_id" class="form-label">Konsentrasi Keahlian</label>
                        <select class="form-select" id="konsentrasi_id" name="konsentrasi_id" required>
                            <option value="">-- Pilih Konsentrasi --</option>
                            <?php foreach ($konsentrasi_list as $item): ?>
                                <option value="<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['program_name'] . ' - ' . $item['konsentrasi_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="kelas_name" class="form-label">Nama Kelas</label>
                        <input type="text" class="form-control" id="kelas_name" name="kelas_name" required>
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
    const modal = document.getElementById('kelasModal');
    modal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const form = document.getElementById('kelasForm');

        if (button.classList.contains('edit-btn')) {
            form.querySelector('.modal-title').textContent = 'Edit Kelas';
            document.getElementById('form_action').value = 'update';
            document.getElementById('kelas_id').value = button.dataset.id;
            document.getElementById('konsentrasi_id').value = button.dataset.konsentrasiId;
            document.getElementById('kelas_name').value = button.dataset.name;
        } else {
            form.querySelector('.modal-title').textContent = 'Tambah Kelas';
            document.getElementById('form_action').value = 'create';
            form.reset();
        }
    });
});
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>