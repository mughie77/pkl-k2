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
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    if (!$email || !$password || !$role) {
        $error_message = 'Email, password, dan peran wajib diisi.';
    } else {
        // Tentukan tabel berdasarkan peran
        $table_map = [
            'admin' => 'admins',
            'teacher' => 'teachers',
            'instructor' => 'instructors',
            'student' => 'students'
        ];

        if (!array_key_exists($role, $table_map)) {
            $error_message = 'Peran tidak valid.';
        } else {
            $table_name = $table_map[$role];

            try {
                $stmt = $pdo->prepare("SELECT * FROM {$table_name} WHERE email = :email");
                $stmt->execute(['email' => $email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($password, $user['password'])) {
                    // Regenerasi session ID untuk keamanan
                    session_regenerate_id(true);

                    // Simpan data pengguna ke sesi
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $role;

                    // Arahkan ke dashboard yang sesuai
                    header("Location: {$role}_dashboard.php");
                    exit;
                } else {
                    $error_message = 'Email atau password salah.';
                }
            } catch (PDOException $e) {
                $error_message = 'Terjadi kesalahan pada server. Silakan coba lagi nanti.';
                // Log the error: error_log($e->getMessage());
            }
        }
    }
}

// Halaman login tidak menggunakan sidebar, jadi kita panggil header dan footer secara manual
// tanpa memasukkan bagian utama yang membutuhkan sidebar.
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
        /* Override untuk halaman login agar tidak ada gradasi */
        .login-container {
            background: var(--light-color);
        }
        .login-card {
            border: 1px solid #dee2e6;
        }
        .login-header {
            background-color: var(--dark-color);
            color: var(--white-color);
            border-top-left-radius: 1rem;
            border-top-right-radius: 1rem;
            padding: 1.5rem;
            text-align: center;
        }
        .login-header .fas {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
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
                    <label for="role" class="form-label">Login Sebagai:</label>
                    <select class="form-select" id="role" name="role" required>
                        <option value="" disabled selected>-- Pilih Peran --</option>
                        <option value="student">Siswa</option>
                        <option value="instructor">Instruktur DUDIKA</option>
                        <option value="teacher">Guru Pembimbing</option>
                        <option value="admin">Admin Sekolah</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        <input type="email" class="form-control" id="email" name="email" placeholder="contoh@email.com" required>
                    </div>
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