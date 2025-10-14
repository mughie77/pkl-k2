<?php
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/sidebar.php';

// Proteksi halaman
if ($_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Ambil data instruktur beserta nama perusahaannya
try {
    $stmt = $pdo->query("
        SELECT i.*, c.name as company_name
        FROM instructors i
        JOIN companies c ON i.company_id = c.id
        ORDER BY i.name ASC
    ");
    $instructors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ambil data perusahaan untuk dropdown
    $company_stmt = $pdo->query("SELECT id, name FROM companies ORDER BY name ASC");
    $companies = $company_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error: Could not fetch data. " . $e->getMessage());
}

?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Manajemen Data Instruktur</h1>

    <button type="button" class="btn btn-primary mb-4" data-bs-toggle="modal" data-bs-target="#instructorModal">
        <i class="fas fa-plus-circle me-2"></i> Tambah Instruktur Baru
    </button>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['flash_message']['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['flash_message']['message']; ?>
            <?php if (isset($_SESSION['flash_message']['serial_number'])): ?>
                <br><strong>Username & Password: </strong> <code><?php echo $_SESSION['flash_message']['serial_number']; ?></code>
            <?php endif; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Instruktur DUDIKA</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nama Lengkap</th>
                            <th>No. Seri (Username)</th>
                            <th>Jabatan</th>
                            <th>Asal DUDIKA</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($instructors) > 0): ?>
                            <?php foreach ($instructors as $index => $instructor): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($instructor['name']); ?></td>
                                    <td><?php echo htmlspecialchars($instructor['serial_number']); ?></td>
                                    <td><?php echo htmlspecialchars($instructor['position']); ?></td>
                                    <td><?php echo htmlspecialchars($instructor['company_name']); ?></td>
                                    <td>
                                        <button class="btn btn-warning btn-sm edit-btn"
                                                data-id="<?php echo $instructor['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($instructor['name']); ?>"
                                                data-position="<?php echo htmlspecialchars($instructor['position']); ?>"
                                                data-company_id="<?php echo $instructor['company_id']; ?>"
                                                data-bs-toggle="modal" data-bs-target="#instructorModal">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="core/instructor_actions.php?action=delete&id=<?php echo $instructor['id']; ?>"
                                           class="btn btn-danger btn-sm btn-delete"
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">Belum ada data instruktur.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk Tambah/Edit Instruktur -->
<div class="modal fade" id="instructorModal" tabindex="-1" aria-labelledby="instructorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="instructorModalLabel">Form Data Instruktur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="instructorForm" action="core/instructor_actions.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="instructor_id" id="instructor_id">
                    <input type="hidden" name="action" id="form_action" value="create">

                    <div class="mb-3">
                        <label for="name" class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="company_id" class="form-label">Asal DUDIKA</label>
                        <select class="form-select" id="company_id" name="company_id" required>
                            <option value="" disabled selected>-- Pilih DUDIKA --</option>
                            <?php foreach ($companies as $company): ?>
                                <option value="<?php echo $company['id']; ?>"><?php echo htmlspecialchars($company['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="position" class="form-label">Jabatan</label>
                        <input type="text" class="form-control" id="position" name="position">
                    </div>
                    <div class="alert alert-info">
                        Username (Nomor Seri) dan Password akan dibuat secara otomatis oleh sistem.
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
    const initInstructorSelect2 = () => {
        $('#company_id').select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#instructorModal')
        });
    };

    const instructorModal = document.getElementById('instructorModal');
    instructorModal.addEventListener('show.bs.modal', function(event) {
        initInstructorSelect2();
        const button = event.relatedTarget;
        const modalTitle = instructorModal.querySelector('.modal-title');
        const form = document.getElementById('instructorForm');
        const actionInput = document.getElementById('form_action');
        const instructorIdInput = document.getElementById('instructor_id');

        // Form untuk edit tidak diimplementasikan di sini karena username/password otomatis
        // Jika diperlukan, harus ada logika terpisah untuk reset password.
        // Untuk saat ini, modal hanya untuk 'create'.
        if (button.classList.contains('edit-btn')) {
            modalTitle.textContent = 'Edit Data Instruktur';
            actionInput.value = 'update'; // Aksi update hanya akan mengubah nama, posisi, dan perusahaan
            instructorIdInput.value = button.dataset.id;

            $('#name').val(button.dataset.name);
            $('#position').val(button.dataset.position);
            $('#company_id').val(button.dataset.company_id).trigger('change');
        } else {
            modalTitle.textContent = 'Tambah Instruktur Baru';
            actionInput.value = 'create';
            form.reset();
            instructorIdInput.value = '';
            $('#company_id').val(null).trigger('change');
        }
    });
});
</script>

<?php
require_once __DIR__ . '/templates/footer.php';
?>