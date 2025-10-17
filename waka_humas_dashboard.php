<?php
require_once __DIR__ . '/templates/header.php';

// Proteksi halaman
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'waka_humas') {
    header("Location: login.php");
    exit;
}
?>
<div class="container-fluid">
    <div class="row">
        <main class="col-md-12 ms-sm-auto col-lg-12 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Dashboard Waka Humas</h1>
            </div>

            <div class="alert alert-success" role="alert">
                Selamat datang, <?php echo htmlspecialchars($_SESSION['user_name']); ?>! Anda telah berhasil login sebagai Waka Humas.
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            Menu Navigasi
                        </div>
                        <div class="card-body">
                            <p>Fitur untuk Waka Humas akan ditambahkan di sini.</p>
                            <!-- Tambahkan link ke fitur-fitur waka humas di sini -->
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>
<?php
require_once __DIR__ . '/templates/footer.php';
?>