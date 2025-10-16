<?php
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/sidebar.php';

// Proteksi halaman
if ($_SESSION['user_role'] !== 'waka_humas') {
    header("Location: login.php");
    exit;
}

$page_title = "Catatan Permasalahan Siswa";

try {
    // Ambil semua catatan dari guru dan instruktur
    $notes_stmt = $pdo->query("
        SELECT
            sn.id, sn.note, sn.created_at, sn.creator_role,
            s.name as student_name,
            CASE
                WHEN sn.creator_role = 'teacher' THEN t.name
                WHEN sn.creator_role = 'instructor' THEN i.name
            END as creator_name
        FROM student_notes sn
        JOIN students s ON sn.student_id = s.id
        LEFT JOIN teachers t ON sn.creator_id = t.id AND sn.creator_role = 'teacher'
        LEFT JOIN instructors i ON sn.creator_id = i.id AND sn.creator_role = 'instructor'
        ORDER BY sn.created_at DESC
    ");
    $all_notes = $notes_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800"><?php echo $page_title; ?></h1>

    <div class="card shadow mb-4">
        <div class="card-header">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-list-alt me-2"></i>Semua Catatan Permasalahan</h6>
        </div>
        <div class="card-body">
             <?php if (empty($all_notes)): ?>
                <p class="text-center">Belum ada catatan permasalahan yang dibuat.</p>
            <?php else: ?>
                <ul class="list-group list-group-flush">
                <?php foreach ($all_notes as $note): ?>
                    <li class="list-group-item mb-3 border-start-4 <?php echo $note['creator_role'] === 'teacher' ? 'border-primary' : 'border-success'; ?>">
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1">Siswa: <?php echo htmlspecialchars($note['student_name']); ?></h5>
                            <small class="text-muted"><?php echo date('d M Y, H:i', strtotime($note['created_at'])); ?></small>
                        </div>
                        <p class="mb-1 fst-italic">"<?php echo nl2br(htmlspecialchars($note['note'])); ?>"</p>
                        <small class="text-muted">
                            Dibuat oleh <?php echo htmlspecialchars(ucfirst($note['creator_role'])); ?>:
                            <strong><?php echo htmlspecialchars($note['creator_name']); ?></strong>
                        </small>
                    </li>
                <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/templates/footer.php'; ?>