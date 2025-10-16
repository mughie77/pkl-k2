<?php
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/sidebar.php';

// Proteksi halaman
if ($_SESSION['user_role'] !== 'waka_humas') {
    header("Location: login.php");
    exit;
}

$page_title = "Direktori DUDIKA";

try {
    // Ambil semua data perusahaan beserta instruktur terkait
    $stmt = $pdo->query("
        SELECT
            c.id, c.name, c.address, c.contact_person, c.contact_phone,
            GROUP_CONCAT(i.name SEPARATOR ', ') as instructors
        FROM companies c
        LEFT JOIN instructors i ON c.id = i.company_id
        GROUP BY c.id
        ORDER BY c.name ASC
    ");
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800"><?php echo $page_title; ?></h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-address-book me-2"></i>Daftar Kontak DUDIKA dan Instruktur</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Nama DUDIKA</th>
                            <th>Alamat</th>
                            <th>Narahubung</th>
                            <th>Kontak</th>
                            <th>Instruktur</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($companies as $company): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($company['name']); ?></td>
                            <td><?php echo htmlspecialchars($company['address']); ?></td>
                            <td><?php echo htmlspecialchars($company['contact_person']); ?></td>
                            <td>
                                <?php if (!empty($company['contact_phone'])):
                                    $phone = htmlspecialchars($company['contact_phone']);
                                    $wa_phone = preg_replace('/^0/', '62', $phone);
                                ?>
                                    <a href="https://wa.me/<?php echo $wa_phone; ?>" target="_blank" class="btn btn-success btn-sm me-1">
                                        <i class="fab fa-whatsapp"></i> WA
                                    </a>
                                    <a href="tel:<?php echo $phone; ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-phone"></i> Call
                                    </a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($company['instructors'] ?? 'Belum ada'); ?></td>
                        </tr>
                        <?php endforeach; ?>
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