<?php
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/sidebar.php';

// Proteksi halaman
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'instructor'])) {
    header("Location: login.php");
    exit;
}

$student_id = $_GET['id'] ?? 0;

if (empty($student_id)) {
    die("Error: ID Siswa tidak valid.");
}

try {
    // Ambil semua data terkait siswa
    $stmt = $pdo->prepare("
        SELECT
            s.*,
            d.department_name,
            ay.year_name,
            t.name as teacher_name, t.phone as teacher_phone,
            i.name as instructor_name,
            c.name as company_name
        FROM students s
        LEFT JOIN departments d ON s.department_id = d.id
        LEFT JOIN academic_years ay ON s.academic_year_id = ay.id
        LEFT JOIN internship_mappings m ON s.id = m.student_id
        LEFT JOIN teachers t ON m.teacher_id = t.id
        LEFT JOIN instructors i ON m.instructor_id = i.id
        LEFT JOIN companies c ON i.company_id = c.id
        WHERE s.id = :id
    ");
    $stmt->execute([':id' => $student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        die("Data siswa tidak ditemukan.");
    }

} catch (PDOException $e) {
    die("Error fetching student details: " . $e->getMessage());
}

// Fungsi untuk membuat link WhatsApp
function create_whatsapp_link($phone) {
    if (empty($phone)) return '#';
    // Asumsi nomor HP disimpan dalam format lokal (e.g., 0812...), ubah ke format internasional
    $number = preg_replace('/[^0-9]/', '', $phone);
    if (substr($number, 0, 1) == '0') {
        $number = '62' . substr($number, 1);
    }
    return 'https://wa.me/' . $number;
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Detail Siswa</h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-user-graduate me-2"></i>
                <?php echo htmlspecialchars($student['name']); ?>
            </h6>
            <a href="javascript:history.back()" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left me-2"></i>Kembali</a>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h5>Data Diri Siswa</h5>
                    <table class="table table-sm table-borderless">
                        <tr><th>NIS</th><td>: <?php echo htmlspecialchars($student['nis'] ?? '-'); ?></td></tr>
                        <tr><th>NISN</th><td>: <?php echo htmlspecialchars($student['nisn'] ?? '-'); ?></td></tr>
                        <tr><th>TTL</th><td>: <?php echo htmlspecialchars($student['birth_place'] ?? '-'); ?>, <?php echo $student['birth_date'] ? date('d M Y', strtotime($student['birth_date'])) : '-'; ?></td></tr>
                        <tr><th>Alamat</th><td>: <?php echo htmlspecialchars($student['address'] ?? '-'); ?></td></tr>
                        <tr><th>Jurusan</th><td>: <?php echo htmlspecialchars($student['department_name'] ?? '-'); ?></td></tr>
                        <tr><th>Thn. Pelajaran</th><td>: <?php echo htmlspecialchars($student['year_name'] ?? '-'); ?></td></tr>
                        <tr><th>No. HP</th><td>: <?php echo htmlspecialchars($student['phone'] ?? '-'); ?>
                            <?php if(!empty($student['phone'])): ?>
                                <a href="<?php echo create_whatsapp_link($student['phone']); ?>" target="_blank" class="btn btn-success btn-sm ms-2"><i class="fab fa-whatsapp"></i></a>
                            <?php endif; ?>
                        </td></tr>
                        <tr><th>No. HP Ortu</th><td>: <?php echo htmlspecialchars($student['parent_phone'] ?? '-'); ?>
                             <?php if(!empty($student['parent_phone'])): ?>
                                <a href="<?php echo create_whatsapp_link($student['parent_phone']); ?>" target="_blank" class="btn btn-success btn-sm ms-2"><i class="fab fa-whatsapp"></i></a>
                            <?php endif; ?>
                        </td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h5>Informasi PKL</h5>
                     <table class="table table-sm table-borderless">
                        <tr><th>Tempat PKL</th><td>: <?php echo htmlspecialchars($student['company_name'] ?? 'Belum di-mapping'); ?></td></tr>
                        <tr><th>Instruktur</th><td>: <?php echo htmlspecialchars($student['instructor_name'] ?? 'Belum di-mapping'); ?></td></tr>
                        <tr><th>Guru Pembimbing</th><td>: <?php echo htmlspecialchars($student['teacher_name'] ?? 'Belum di-mapping'); ?></td></tr>
                        <tr><th>No. HP Guru</th><td>: <?php echo htmlspecialchars($student['teacher_phone'] ?? '-'); ?>
                             <?php if(!empty($student['teacher_phone'])): ?>
                                <a href="<?php echo create_whatsapp_link($student['teacher_phone']); ?>" target="_blank" class="btn btn-success btn-sm ms-2"><i class="fab fa-whatsapp"></i></a>
                            <?php endif; ?>
                        </td></tr>
                     </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>