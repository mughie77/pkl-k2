<?php
// Pastikan sesi sudah dimulai
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Periksa apakah pengguna sudah login. Jika belum, jangan tampilkan sidebar.
// Halaman login.php dan logout.php dikecualikan dari aturan ini.
$is_auth_page = in_array(basename($_SERVER['PHP_SELF']), ['login.php', 'logout.php']);
if (!isset($_SESSION['user_role']) && !$is_auth_page) {
    // Jika tidak ada peran dan bukan halaman auth, mungkin ada masalah sesi.
    // Untuk keamanan, bisa redirect ke login, tapi untuk sekarang kita biarkan kosong.
    return;
}

// Untuk semua peran yang login, buka wrapper kontennya.
// Sidebar akan tetap dirender setelah ini.
if (isset($_SESSION['user_role'])) {
    echo '<div class="content-wrapper p-3 p-md-4">';
}

$user_role = $_SESSION['user_role'];
$current_page = basename($_SERVER['PHP_SELF']);

// Fungsi untuk membuat item menu
function create_nav_item($link, $icon, $text, $current_page) {
    $active_class = ($current_page == $link) ? 'active' : '';
    echo "<li class='nav-item'>";
    echo "<a class='nav-link {$active_class}' href='{$link}'>";
    echo "<i class='fas {$icon} me-2'></i>{$text}";
    echo "</a>";
    echo "</li>";
}

?>

<!-- Sidebar -->
<div class="sidebar bg-white p-3 shadow-sm">
    <h5 class="sidebar-heading text-center mb-4">Menu <?php echo ucwords(str_replace('_', ' ', $user_role)); ?></h5>
    <ul class="nav flex-column">

        <?php if ($user_role == 'admin'): ?>
            <?php create_nav_item('admin_dashboard.php', 'fa-tachometer-alt', 'Dashboard', $current_page); ?>
            <?php create_nav_item('manage_students.php', 'fa-user-graduate', 'Manajemen Siswa', $current_page); ?>
            <?php create_nav_item('manage_teachers.php', 'fa-chalkboard-teacher', 'Manajemen Guru', $current_page); ?>
            <?php create_nav_item('manage_companies.php', 'fa-building', 'Manajemen DUDIKA', $current_page); ?>
            <?php create_nav_item('manage_instructors.php', 'fa-user-tie', 'Manajemen Instruktur', $current_page); ?>
            <?php create_nav_item('internship_mapping.php', 'fa-project-diagram', 'Mapping PKL', $current_page); ?>
            <?php create_nav_item('global_recap.php', 'fa-file-invoice', 'Rekapitulasi Global', $current_page); ?>
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseSettings" aria-expanded="false" aria-controls="collapseSettings">
                    <i class="fas fa-cogs me-2"></i>Pengaturan
                </a>
                <div id="collapseSettings" class="collapse" aria-labelledby="headingSettings" data-bs-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item nav-link ms-3" href="school_settings.php">Profil Sekolah</a>
                        <a class="collapse-item nav-link ms-3" href="manage_program_keahlian.php">Program Keahlian</a>
                        <a class="collapse-item nav-link ms-3" href="manage_konsentrasi_keahlian.php">Konsentrasi Keahlian</a>
                        <a class="collapse-item nav-link ms-3" href="manage_kelas.php">Manajemen Kelas</a>
                        <a class="collapse-item nav-link ms-3" href="manage_academic_years.php">Tahun Pelajaran</a>
                    </div>
                </div>
            </li>
        <?php endif; ?>

        <?php if ($user_role == 'teacher'): ?>
            <?php create_nav_item('teacher_dashboard.php', 'fa-tachometer-alt', 'Dashboard', $current_page); ?>
            <?php create_nav_item('monitor_journals.php', 'fa-book-reader', 'Monitoring Jurnal', $current_page); ?>
            <?php create_nav_item('report_consultation.php', 'fa-file-alt', 'Konsultasi Laporan', $current_page); ?>
            <?php create_nav_item('student_problems.php', 'fa-exclamation-triangle', 'Catatan Masalah Siswa', $current_page); ?>
            <?php create_nav_item('teacher_view_assessments.php', 'fa-graduation-cap', 'Lihat Nilai Siswa', $current_page); ?>
            <?php create_nav_item('teacher_set_locations.php', 'fa-map-marked-alt', 'Set Lokasi DUDIKA', $current_page); ?>
        <?php endif; ?>

        <?php if ($user_role == 'instructor'): ?>
            <?php create_nav_item('instructor_dashboard.php', 'fa-tachometer-alt', 'Dashboard', $current_page); ?>
            <?php create_nav_item('verify_journals.php', 'fa-tasks', 'Verifikasi Jurnal', $current_page); ?>
            <?php create_nav_item('manage_leave_requests.php', 'fa-calendar-check', 'Persetujuan Izin/Cuti', $current_page); ?>
            <?php create_nav_item('input_assessment.php', 'fa-edit', 'Input Penilaian', $current_page); ?>
            <?php create_nav_item('student_problems.php', 'fa-exclamation-triangle', 'Catatan Masalah Siswa', $current_page); ?>
            <?php create_nav_item('global_recap.php', 'fa-chart-bar', 'Rekap Laporan', $current_page); ?>
        <?php endif; ?>

        <?php if ($user_role == 'student'): ?>
            <?php create_nav_item('student_dashboard.php', 'fa-tachometer-alt', 'Dashboard', $current_page); ?>
            <?php create_nav_item('daily_journal.php', 'fa-book', 'Jurnal Harian', $current_page); ?>
            <?php create_nav_item('view_assessment.php', 'fa-chart-bar', 'Lihat Penilaian', $current_page); ?>
            <?php create_nav_item('upload_report.php', 'fa-file-upload', 'Unggah Laporan', $current_page); ?>
        <?php endif; ?>

        <?php if ($user_role == 'waka_humas'): ?>
            <?php create_nav_item('waka_dashboard.php', 'fa-tachometer-alt', 'Dashboard', $current_page); ?>
            <?php create_nav_item('mapping_report.php', 'fa-sitemap', 'Laporan Mapping', $current_page); ?>
            <?php create_nav_item('problem_report.php', 'fa-exclamation-triangle', 'Laporan Masalah', $current_page); ?>
            <?php create_nav_item('assessment_recap.php', 'fa-chart-line', 'Rekap Nilai', $current_page); ?>
        <?php endif; ?>

    </ul>
</div>

<!-- Content Wrapper (moved to individual pages) -->