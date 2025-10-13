<?php
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/sidebar.php';

// Proteksi halaman
if ($_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Ambil data perusahaan dari database
try {
    $stmt = $pdo->query("SELECT * FROM companies ORDER BY name ASC");
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error: Could not fetch companies data. " . $e->getMessage());
}

?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Manajemen Data DUDIKA</h1>

    <button type="button" class="btn btn-primary mb-4" data-bs-toggle="modal" data-bs-target="#companyModal">
        <i class="fas fa-plus-circle me-2"></i> Tambah DUDIKA Baru
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
            <h6 class="m-0 font-weight-bold text-primary">Daftar DUDIKA</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nama DUDIKA</th>
                            <th>Alamat</th>
                            <th>Narahubung</th>
                            <th>Email Kontak</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($companies) > 0): ?>
                            <?php foreach ($companies as $index => $company): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($company['name']); ?></td>
                                    <td><?php echo htmlspecialchars($company['address']); ?></td>
                                    <td><?php echo htmlspecialchars($company['contact_person']); ?></td>
                                    <td><?php echo htmlspecialchars($company['contact_email']); ?></td>
                                    <td>
                                        <button class="btn btn-warning btn-sm edit-btn"
                                                data-id="<?php echo $company['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($company['name']); ?>"
                                                data-address="<?php echo htmlspecialchars($company['address']); ?>"
                                                data-contact_person="<?php echo htmlspecialchars($company['contact_person']); ?>"
                                                data-contact_email="<?php echo htmlspecialchars($company['contact_email']); ?>"
                                                data-bs-toggle="modal" data-bs-target="#companyModal">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="core/company_actions.php?action=delete&id=<?php echo $company['id']; ?>"
                                           class="btn btn-danger btn-sm btn-delete"
                                           onclick="return confirm('Menghapus DUDIKA akan menghapus semua instruktur terkait. Lanjutkan?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">Belum ada data DUDIKA.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk Tambah/Edit DUDIKA -->
<div class="modal fade" id="companyModal" tabindex="-1" aria-labelledby="companyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="companyModalLabel">Form Data DUDIKA</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="companyForm" action="core/company_actions.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="company_id" id="company_id">
                    <input type="hidden" name="action" id="form_action" value="create">

                    <div class="mb-3">
                        <label for="name" class="form-label">Nama DUDIKA</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">Alamat</label>
                        <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="contact_person" class="form-label">Narahubung (Contact Person)</label>
                        <input type="text" class="form-control" id="contact_person" name="contact_person">
                    </div>
                    <div class="mb-3">
                        <label for="contact_email" class="form-label">Email Kontak</label>
                        <input type="email" class="form-control" id="contact_email" name="contact_email">
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
    const companyModal = document.getElementById('companyModal');
    companyModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const modalTitle = companyModal.querySelector('.modal-title');
        const form = document.getElementById('companyForm');
        const actionInput = document.getElementById('form_action');
        const companyIdInput = document.getElementById('company_id');

        if (button.classList.contains('edit-btn')) {
            modalTitle.textContent = 'Edit Data DUDIKA';
            actionInput.value = 'update';
            companyIdInput.value = button.dataset.id;

            document.getElementById('name').value = button.dataset.name;
            document.getElementById('address').value = button.dataset.address;
            document.getElementById('contact_person').value = button.dataset.contact_person;
            document.getElementById('contact_email').value = button.dataset.contact_email;
        } else {
            modalTitle.textContent = 'Tambah DUDIKA Baru';
            actionInput.value = 'create';
            form.reset();
            companyIdInput.value = '';
        }
    });
});
</script>

<?php
require_once __DIR__ . '/templates/footer.php';
?>