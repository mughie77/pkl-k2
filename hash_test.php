<?php
// Panggil header untuk styling yang konsisten
require_once __DIR__ . '/templates/header.php';

$input_string = '';
$hashed_password = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password_to_hash'])) {
    $input_string = $_POST['password_to_hash'];
    if (!empty($input_string)) {
        // Hashing password menggunakan algoritma default (saat ini bcrypt)
        $hashed_password = password_hash($input_string, PASSWORD_DEFAULT);
    }
}
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-dark text-white">
                    <h5 class="m-0"><i class="fas fa-key me-2"></i>PHP Password Hash Generator</h5>
                </div>
                <div class="card-body">
                    <p>Masukkan sebuah string (misalnya: password, NIP, NISN) untuk men-generate hash menggunakan fungsi <code>password_hash()</code> dengan algoritma <code>PASSWORD_DEFAULT</code>.</p>

                    <form action="hash_test.php" method="POST">
                        <div class="mb-3">
                            <label for="password_to_hash" class="form-label">String untuk di-Hash:</label>
                            <input type="text" class="form-control" id="password_to_hash" name="password_to_hash" value="<?php echo htmlspecialchars($input_string); ?>" required autofocus>
                        </div>
                        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-cogs me-2"></i>Generate Hash</button>
                    </form>

                    <?php if (!empty($hashed_password)): ?>
                    <div class="alert alert-success mt-4">
                        <h6 class="alert-heading">Hasil Hash:</h6>
                        <hr>
                        <p><strong>String Asli:</strong><br><code><?php echo htmlspecialchars($input_string); ?></code></p>
                        <p class="mb-0"><strong>Hasil Hash:</strong><br><code style="word-wrap: break-word;"><?php echo htmlspecialchars($hashed_password); ?></code></p>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer text-muted small">
                    Skrip ini berguna untuk membuat nilai hash manual yang dapat dimasukkan ke dalam database untuk pengujian atau pengaturan awal.
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Panggil footer
require_once __DIR__ . '/templates/footer.php';
?>