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
    'admin' => ['table' => 'admins', 'user_col' => 'username', 'name_col' => 'name'],
    'waka_humas' => ['table' => 'waka_humas', 'user_col' => 'username', 'name_col' => 'name'],
    'teacher' => ['table' => 'teachers', 'user_col' => 'nip', 'name_col' => 'name'],
    'instructor' => ['table' => 'instructors', 'user_col' => 'serial_number', 'name_col' => 'name'],
    'student' => ['table' => 'students', 'user_col' => 'nisn', 'name_col' => 'name']
];

$table = $auth_config[$user_role]['table'];
$user_col = $auth_config[$user_role]['user_col'];
$name_col = $auth_config[$user_role]['name_col'];

try {
    if ($user_role === 'student') {
        $stmt = $pdo->prepare("
            SELECT s.*, k.kelas_name, kk.konsentrasi_name, pk.program_name
            FROM students s
            LEFT JOIN kelas k ON s.kelas_id = k.id
            LEFT JOIN konsentrasi_keahlian kk ON k.konsentrasi_id = kk.id
            LEFT JOIN program_keahlian pk ON kk.program_id = pk.id
            WHERE s.id = :id
        ");
    } else {
        $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE id = :id");
    }
    $stmt->execute([':id' => $user_id]);
    $user_info = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching user profile: " . $e->getMessage());
}

?>

<div class="container-fluid content-wrapper student-view">
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
                    <p><strong>Nama:</strong> <?php echo htmlspecialchars($user_info[$name_col]); ?></p>
                    <p><strong>Peran:</strong> <?php echo ucwords(str_replace('_', ' ', $user_role)); ?></p>
                    <p><strong>Username:</strong> <?php echo htmlspecialchars($user_info[$user_col]); ?></p>
                    <?php if ($user_role === 'student'): ?>
                        <p><strong>Program Keahlian:</strong> <?php echo htmlspecialchars($user_info['program_name'] ?? '-'); ?></p>
                        <p><strong>Konsentrasi Keahlian:</strong> <?php echo htmlspecialchars($user_info['konsentrasi_name'] ?? '-'); ?></p>
                        <p><strong>Kelas:</strong> <?php echo htmlspecialchars($user_info['kelas_name'] ?? '-'); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($user_info['email'] ?? '-'); ?></p>
                        <hr>
                        <form action="core/profile_actions.php" method="POST">
                            <input type="hidden" name="action" value="update_work_hours">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="work_start_time" class="form-label">Jam Mulai Kerja</label>
                                    <input type="time" class="form-control" name="work_start_time" id="work_start_time" value="<?php echo htmlspecialchars($user_info['work_start_time'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="work_end_time" class="form-label">Jam Selesai Kerja</label>
                                    <input type="time" class="form-control" name="work_end_time" id="work_end_time" value="<?php echo htmlspecialchars($user_info['work_end_time'] ?? ''); ?>">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-info btn-sm"><i class="fas fa-save me-2"></i>Simpan Jam Kerja</button>
                        </form>
                    <?php elseif ($user_role === 'instructor'): ?>
                        <p><strong>Jabatan:</strong> <?php echo htmlspecialchars($user_info['instructor_position'] ?? '-'); ?></p>
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

    <?php if (in_array($user_role, ['student', 'teacher', 'instructor'])): ?>
    <div class="mt-4 d-grid">
        <a href="logout.php" class="btn btn-danger btn-lg"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
    </div>
    <?php endif; ?>
</div>

<?php
// Karena siswa tidak punya sidebar, div penutupnya harus ada di sini
if ($_SESSION['user_role'] === 'student') {
    echo '</div>';
}
require_once __DIR__ . '/templates/footer.php';
?>