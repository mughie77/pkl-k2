<?php
require_once __DIR__ . '/config/config.php';

// Jika pengguna sudah login, arahkan ke dashboard yang sesuai
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['user_role'];
    header("Location: {$role}_dashboard.php");
    exit;
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error_message = 'Username dan password wajib diisi.';
    } else {
        // Daftar peran dan konfigurasi tabelnya
        $roles_config = [
            'admin' => ['table' => 'admins', 'user_col' => 'username'],
            'teacher' => ['table' => 'teachers', 'user_col' => 'nip'],
            'instructor' => ['table' => 'instructors', 'user_col' => 'serial_number'],
            'student' => ['table' => 'students', 'user_col' => 'nisn']
        ];

        $user_found = false;

        try {
            // Iterasi melalui setiap peran untuk mencari username
            foreach ($roles_config as $role => $config) {
                $table_name = $config['table'];
                $user_col = $config['user_col'];

                $stmt = $pdo->prepare("SELECT * FROM {$table_name} WHERE {$user_col} = :username");
                $stmt->execute([':username' => $username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($password, $user['password'])) {
                    // Jika pengguna ditemukan dan password cocok
                    session_regenerate_id(true);

                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_role'] = $role;

                    // Simpan email jika ada (untuk siswa)
                    if ($role === 'student' && isset($user['email'])) {
                        $_SESSION['user_email'] = $user['email'];
                    }

                    $user_found = true;
                    header("Location: {$role}_dashboard.php");
                    exit;
                }
            }

            // Jika setelah iterasi pengguna tidak ditemukan
            if (!$user_found) {
                $error_message = 'Username atau password salah.';
            }

        } catch (PDOException $e) {
            $error_message = 'Terjadi kesalahan pada server. Silakan coba lagi nanti.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PKL Digital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .login-container { background: var(--light-color); }
        .login-card { border: 1px solid #dee2e6; }
        .login-header {
            background-color: var(--dark-color);
            color: var(--white-color);
            border-top-left-radius: 1rem;
            border-top-right-radius: 1rem;
            padding: 1.5rem;
            text-align: center;
        }
        .login-header .fas { font-size: 2rem; margin-bottom: 0.5rem; }
    </style>
</head>
<body>

<div class="login-container">
    <div class="card login-card shadow-lg">
        <div class="login-header">
            <i class="fas fa-digital-tachograph"></i>
            <h4 class="mb-0">Aplikasi PKL Digital</h4>
            <p class="mb-0 small">Silakan login untuk melanjutkan</p>
        </div>
        <div class="card-body p-4 p-md-5">
            <?php if ($error_message): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            <form action="login.php" method="POST" novalidate>
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" id="username" name="username" placeholder="Masukkan username Anda" required>
                    </div>
                    <small class="form-text text-muted">Contoh: admin, NISN, NIP, atau No. Seri</small>
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" placeholder="********" required>
                    </div>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg fw-bold">
                        <i class="fas fa-sign-in-alt me-2"></i> Login
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>