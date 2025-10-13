<?php
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/sidebar.php';

// Proteksi halaman
if ($_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Dashboard Admin</h1>

    <!-- Konten Dashboard Admin akan ditambahkan di sini -->
    <div class="alert alert-info">
        Selamat datang, <?php echo htmlspecialchars($_SESSION['user_name']); ?>! Anda login sebagai Admin.
    </div>

</div>

<?php
require_once __DIR__ . '/templates/footer.php';
?>