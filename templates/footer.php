<?php
$user_role = $_SESSION['user_role'] ?? 'guest';

if (in_array($user_role, ['student', 'teacher', 'instructor', 'waka_humas'])) :
    // Menu untuk peran non-admin
?>
    </div> <!-- .content-wrapper (penutup dari sidebar.php atau halaman peran non-admin) -->

    <nav class="bottom-nav">
        <a href="<?php echo $user_role; ?>_dashboard.php" class="bottom-nav-item <?php echo basename($_SERVER['PHP_SELF']) == $user_role.'_dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </a>

        <?php if ($user_role === 'student'): ?>
            <a href="daily_journal.php" class="bottom-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'daily_journal.php' ? 'active' : ''; ?>"><i class="fas fa-qrcode"></i><span>Absen</span></a>
            <a href="request_leave.php" class="bottom-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'request_leave.php' ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i><span>Izin</span></a>
            <a href="view_assessment.php" class="bottom-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'view_assessment.php' ? 'active' : ''; ?>"><i class="fas fa-graduation-cap"></i><span>Nilai</span></a>
        <?php endif; ?>

        <?php if ($user_role === 'teacher'): ?>
            <a href="monitor_journals.php" class="bottom-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'monitor_journals.php' ? 'active' : ''; ?>"><i class="fas fa-book-reader"></i><span>Jurnal</span></a>
            <a href="teacher_set_locations.php" class="bottom-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'teacher_set_locations.php' ? 'active' : ''; ?>"><i class="fas fa-map-marked-alt"></i><span>Lokasi</span></a>
            <a href="student_problems.php" class="bottom-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'student_problems.php' ? 'active' : ''; ?>"><i class="fas fa-exclamation-triangle"></i><span>Masalah</span></a>
            <a href="teacher_view_assessments.php" class="bottom-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'teacher_view_assessments.php' ? 'active' : ''; ?>"><i class="fas fa-graduation-cap"></i><span>Nilai</span></a>
        <?php endif; ?>

        <?php if ($user_role === 'instructor'): ?>
            <a href="verify_journals.php" class="bottom-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'verify_journals.php' ? 'active' : ''; ?>"><i class="fas fa-tasks"></i><span>Verifikasi</span></a>
            <a href="manage_leave_requests.php" class="bottom-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'manage_leave_requests.php' ? 'active' : ''; ?>"><i class="fas fa-calendar-check"></i><span>Izin</span></a>
            <a href="input_assessment.php" class="bottom-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'input_assessment.php' ? 'active' : ''; ?>"><i class="fas fa-edit"></i><span>Menilai</span></a>
        <?php endif; ?>

        <?php if ($user_role === 'waka_humas'): ?>
            <a href="mapping_report.php" class="bottom-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'mapping_report.php' ? 'active' : ''; ?>"><i class="fas fa-sitemap"></i><span>Mapping</span></a>
            <a href="problem_report.php" class="bottom-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'problem_report.php' ? 'active' : ''; ?>"><i class="fas fa-exclamation-triangle"></i><span>Masalah</span></a>
            <a href="assessment_recap.php" class="bottom-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'assessment_recap.php' ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i><span>Rekap</span></a>
        <?php endif; ?>

        <a href="profile.php" class="bottom-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
            <i class="fas fa-user"></i>
            <span>Profil</span>
        </a>
    </nav>

<?php else : // Footer untuk Admin dan halaman publik ?>
    </div> <!-- .content-wrapper -->
    </div> <!-- #wrapper -->

    <footer class="footer bg-dark text-white pt-4 pb-4">
        <div class="container text-center text-md-start">
            <div class="row">
                <div class="col-md-6 mb-3 mb-md-0">
                    <h5 class="text-uppercase"><i class="fas fa-building me-2"></i><?php echo htmlspecialchars($app_settings['school_name'] ?? 'PKL Digital'); ?></h5>
                    <p>
                        Platform terintegrasi untuk manajemen Praktik Kerja Lapangan yang efisien dan modern.
                    </p>
                </div>
                <div class="col-md-6">
                    <h5 class="text-uppercase">Alamat</h5>
                    <p class="text-white-50 mb-0">
                        <?php echo nl2br(htmlspecialchars($app_settings['school_address'] ?? 'Alamat belum diatur.')); ?>
                    </p>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center text-white-50">
                &copy; <?php echo date("Y"); ?> <?php echo htmlspecialchars($app_settings['school_name'] ?? 'PKL Digital'); ?>. All Rights Reserved.
            </div>
        </div>
    </footer>
<?php endif; ?>


<!-- Bootstrap 5 JS Bundle (Popper.js included) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<!-- Custom JS -->
<script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>

</body>
</html>