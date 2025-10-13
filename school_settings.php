<?php
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/sidebar.php';

// Proteksi halaman
if ($_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Ambil data pengaturan sekolah dari database
try {
    $stmt = $pdo->query("SELECT * FROM school_settings");
    $settings_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $settings = [];
    foreach ($settings_raw as $setting) {
        $settings[$setting['setting_key']] = $setting['setting_value'];
    }
} catch (PDOException $e) {
    die("Error: Could not fetch school settings. " . $e->getMessage());
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Pengaturan Profil Sekolah</h1>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['flash_message']['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['flash_message']['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Form Pengaturan</h6>
        </div>
        <div class="card-body">
            <form action="core/settings_actions.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update_school_settings">

                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="school_name" class="form-label">Nama Sekolah</label>
                            <input type="text" class="form-control" id="school_name" name="school_name" value="<?php echo htmlspecialchars($settings['school_name'] ?? ''); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="school_address" class="form-label">Alamat Sekolah</label>
                            <textarea class="form-control" id="school_address" name="school_address" rows="3" required><?php echo htmlspecialchars($settings['school_address'] ?? ''); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="school_logo" class="form-label">Logo Sekolah (Opsional)</label>
                            <input class="form-control" type="file" id="school_logo" name="school_logo" accept="image/png, image/jpeg">
                            <small class="form-text text-muted">Kosongkan jika tidak ingin mengubah logo. Format: JPG, PNG. Ukuran maks: 1MB.</small>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <label class="form-label">Logo Saat Ini:</label><br>
                        <?php if (!empty($settings['school_logo']) && file_exists($settings['school_logo'])): ?>
                            <img src="<?php echo BASE_URL . '/' . htmlspecialchars($settings['school_logo']); ?>" alt="Logo Sekolah" class="img-thumbnail" style="max-height: 150px;">
                        <?php else: ?>
                            <div class="p-3 border bg-light text-muted">
                                <i class="fas fa-image fa-3x"></i><br>
                                <span>Logo belum diatur</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <hr>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Simpan Pengaturan</button>
            </form>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/templates/footer.php';
?>