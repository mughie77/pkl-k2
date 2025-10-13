<?php
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/sidebar.php';

// Proteksi halaman - harus login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$user_info = [];

// Tentukan tabel dan kolom username berdasarkan peran
$auth_config = [
    'admin' => ['table' => 'admins', 'user_col' => 'username'],
    'teacher' => ['table' => 'teachers', 'user_col' => 'nip'],
    'instructor' => ['table' => 'instructors', 'user_col' => 'serial_number'],
    'student' => ['table' => 'students', 'user_col' => 'nisn']
];

$table = $auth_config[$user_role]['table'];
$user_col = $auth_config[$user_role]['user_col'];

try {
    $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE id = :id");
    $stmt->execute([':id' => $user_id]);
    $user_info = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching user profile: " . $e->getMessage());
}

?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Profil Saya</h1>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['flash_message']['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['flash_message']['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <div class="row">
        <!-- Kolom Info Profil -->
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Informasi Personal</h6>
                </div>
                <div class="card-body">
                    <p><strong>Nama:</strong> <?php echo htmlspecialchars($user_info['name']); ?></p>
                    <p><strong>Peran:</strong> <?php echo ucwords($user_role); ?></p>
                    <p><strong>Username:</strong> <?php echo htmlspecialchars($user_info[$user_col]); ?></p>
                    <?php if ($user_role === 'student'): ?>
                        <p><strong>Jurusan:</strong> <?php echo htmlspecialchars($user_info['department']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($user_info['email'] ?? '-'); ?></p>
                    <?php elseif ($user_role === 'teacher'): ?>
                        <p><strong>Bidang:</strong> <?php echo htmlspecialchars($user_info['department']); ?></p>
                    <?php elseif ($user_role === 'instructor'): ?>
                        <p><strong>Jabatan:</strong> <?php echo htmlspecialchars($user_info['position']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Kolom Ubah Password -->
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Ubah Password</h6>
                </div>
                <div class="card-body">
                    <form action="core/profile_actions.php" method="POST">
                        <input type="hidden" name="action" value="change_password">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Password Saat Ini</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Password Baru</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-key me-2"></i>Ubah Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/templates/footer.php';
?>