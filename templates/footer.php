<?php
$user_role = $_SESSION['user_role'] ?? 'guest';

if ($user_role === 'student') :
?>
    </div> <!-- .content-wrapper (penutup dari sidebar.php atau halaman siswa) -->

    <nav class="bottom-nav">
        <a href="student_dashboard.php" class="bottom-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'student_dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </a>
        <a href="daily_journal.php" class="bottom-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'daily_journal.php' ? 'active' : ''; ?>">
            <i class="fas fa-qrcode"></i>
            <span>Absen</span>
        </a>
        <a href="request_leave.php" class="bottom-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'request_leave.php' ? 'active' : ''; ?>">
            <i class="fas fa-calendar-alt"></i>
            <span>Izin</span>
        </a>
        <a href="upload_report.php" class="bottom-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'upload_report.php' ? 'active' : ''; ?>">
            <i class="fas fa-file-upload"></i>
            <span>Laporan</span>
        </a>
        <a href="profile.php" class="bottom-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
            <i class="fas fa-user"></i>
            <span>Profil</span>
        </a>
    </nav>

<?php else : ?>
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


<!-- jQuery (diperlukan oleh Select2) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

<!-- Bootstrap 5 JS Bundle (Popper.js included) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- Custom JS -->
<script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>

</body>
</html>