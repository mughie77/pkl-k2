<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Panggil config di sini agar BASE_URL tersedia
require_once __DIR__ . '/../config/config.php';

$current_page = basename($_SERVER['PHP_SELF']);

// Menentukan judul halaman berdasarkan file yang sedang diakses
$page_title = "PKL Digital"; // Judul default
if (isset($_SESSION['user_role'])) {
    $role = ucwords(str_replace('_', ' ', $_SESSION['user_role']));
    $page_title = "$role Dashboard";
} else {
    // Jika di halaman login, set judul spesifik
    if ($current_page == 'login.php' || $current_page == 'hash_test.php') {
        $page_title = "Tools - PKL Digital";
    }
}

$is_dashboard_page = isset($_SESSION['user_id']);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">

</head>
<body class="<?php echo $is_dashboard_page ? 'dashboard-body' : ''; ?>">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm">
    <div class="container-fluid">
        <?php if ($is_dashboard_page): ?>
            <!-- Tombol Toggle Sidebar -->
            <button class="btn btn-dark" id="sidebarToggle" type="button">
                <i class="fas fa-bars"></i>
            </button>
        <?php endif; ?>

        <a class="navbar-brand fw-bold ms-2" href="<?php echo $is_dashboard_page ? '#' : 'login.php'; ?>">
            <i class="fas fa-digital-tachograph"></i> PKL Digital
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-1"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-cog me-2"></i>Profil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div id="wrapper" class="d-flex">