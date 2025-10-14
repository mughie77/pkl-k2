<?php
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/sidebar.php';

// Proteksi halaman
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['teacher', 'instructor'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$selected_student_id = $_GET['student_id'] ?? null;

$students_list = [];
$notes = [];

try {
    // Ambil daftar siswa bimbingan
    if ($user_role === 'teacher') {
        $stmt_students = $pdo->prepare("SELECT s.id, s.name FROM students s JOIN internship_mappings m ON s.id = m.student_id WHERE m.teacher_id = :user_id ORDER BY s.name ASC");
    } else { // instructor
        $stmt_students = $pdo->prepare("SELECT s.id, s.name FROM students s JOIN internship_mappings m ON s.id = m.student_id WHERE m.instructor_id = :user_id ORDER BY s.name ASC");
    }
    $stmt_students->execute([':user_id' => $user_id]);
    $students_list = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

    // Jika siswa dipilih, ambil catatannya
    if ($selected_student_id) {
        $query = "
            SELECT n.note, n.created_at, n.creator_role,
                   CASE
                       WHEN n.creator_role = 'teacher' THEN t.name
                       WHEN n.creator_role = 'instructor' THEN i.name
                   END as creator_name
            FROM student_notes n
            LEFT JOIN teachers t ON n.creator_id = t.id AND n.creator_role = 'teacher'
            LEFT JOIN instructors i ON n.creator_id = i.id AND n.creator_role = 'instructor'
            WHERE n.student_id = :student_id
        ";

        $params = [':student_id' => $selected_student_id];

        // Aturan visibilitas
        if ($user_role === 'instructor') {
            $query .= " AND (n.creator_role = 'instructor' AND n.creator_id = :user_id)";
            $params[':user_id'] = $user_id;
        }

        $query .= " ORDER BY n.created_at DESC";
        $stmt_notes = $pdo->prepare($query);
        $stmt_notes->execute($params);
        $notes = $stmt_notes->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    die("Error fetching data: " . $e->getMessage());
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Laporan Permasalahan Siswa</h1>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['flash_message']['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['flash_message']['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <!-- Filter Siswa -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="GET" action="student_problems.php" class="row align-items-end">
                <div class="col-md-6">
                    <label for="student_id" class="form-label">Pilih Siswa untuk Dilihat/Ditambahkan Catatan:</label>
                    <select name="student_id" id="student_id" class="form-select" onchange="this.form.submit()">
                        <option value="">-- Pilih Siswa --</option>
                        <?php foreach ($students_list as $student): ?>
                            <option value="<?php echo $student['id']; ?>" <?php echo ($selected_student_id == $student['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($student['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <?php if ($selected_student_id): ?>
    <div class="row">
        <!-- Kolom Daftar Catatan -->
        <div class="col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Riwayat Catatan</h6></div>
                <div class="card-body">
                    <?php if (count($notes) > 0): ?>
                        <?php foreach ($notes as $note): ?>
                            <div class="alert alert-<?php echo $note['creator_role'] === 'teacher' ? 'warning' : 'info'; ?>">
                                <p class="mb-1"><?php echo nl2br(htmlspecialchars($note['note'])); ?></p>
                                <hr>
                                <small class="text-muted">
                                    Oleh: <?php echo htmlspecialchars($note['creator_name']); ?> (<?php echo ucwords($note['creator_role']); ?>)
                                    pada <?php echo date('d M Y, H:i', strtotime($note['created_at'])); ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-center">Belum ada catatan untuk siswa ini.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Kolom Tambah Catatan -->
        <div class="col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Tambah Catatan Baru</h6></div>
                <div class="card-body">
                    <form action="core/note_actions.php" method="POST">
                        <input type="hidden" name="action" value="create_note">
                        <input type="hidden" name="student_id" value="<?php echo $selected_student_id; ?>">
                        <div class="mb-3">
                            <label for="note" class="form-label">Deskripsi Masalah/Catatan:</label>
                            <textarea name="note" id="note" class="form-control" rows="8" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Simpan Catatan</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
$(document).ready(function() {
    $('#student_id').select2({
        theme: 'bootstrap-5'
    });
});
</script>
<?php require_once __DIR__ . '/templates/footer.php'; ?>