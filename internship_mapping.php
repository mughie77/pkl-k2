<?php
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/sidebar.php';

// Proteksi halaman
if ($_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$active_year_id = $active_year['id'] ?? 0;

try {
    // 1. Ambil data mapping yang sudah ada untuk tahun ajaran aktif
    $stmt_mappings = $pdo->prepare("
        SELECT
            im.id, im.start_date, im.end_date,
            s.id as student_id, s.name as student_name,
            t.id as teacher_id, t.name as teacher_name,
            i.id as instructor_id, i.name as instructor_name, c.name as company_name
        FROM internship_mappings im
        JOIN students s ON im.student_id = s.id
        JOIN teachers t ON im.teacher_id = t.id
        JOIN instructors i ON im.instructor_id = i.id
        JOIN companies c ON i.company_id = c.id
        WHERE im.academic_year_id = :year_id
        ORDER BY s.name ASC
    ");
    $stmt_mappings->execute([':year_id' => $active_year_id]);
    $mappings = $stmt_mappings->fetchAll(PDO::FETCH_ASSOC);

    // 2. Ambil data siswa dari tahun ajaran aktif yang BELUM di-mapping
    $stmt_unmapped_students = $pdo->prepare("
        SELECT id, name FROM students
        WHERE academic_year_id = :year_id
        AND id NOT IN (SELECT student_id FROM internship_mappings WHERE academic_year_id = :year_id_in)
        ORDER BY name ASC
    ");
    $stmt_unmapped_students->execute([':year_id' => $active_year_id, ':year_id_in' => $active_year_id]);
    $unmapped_students = $stmt_unmapped_students->fetchAll(PDO::FETCH_ASSOC);

    // 3. Ambil semua data guru
    $stmt_teachers = $pdo->query("SELECT id, name FROM teachers ORDER BY name ASC");
    $teachers = $stmt_teachers->fetchAll(PDO::FETCH_ASSOC);

    // 4. Ambil semua data instruktur
    $stmt_instructors = $pdo->query("
        SELECT i.id, i.name, c.name as company_name
        FROM instructors i
        JOIN companies c ON i.company_id = c.id
        ORDER BY i.name ASC
    ");
    $instructors = $stmt_instructors->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error: Could not fetch data. " . $e->getMessage());
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Mapping Penempatan PKL <span class="badge bg-info"><?php echo htmlspecialchars($active_year['year_name'] ?? 'Tahun Ajaran Belum Dipilih'); ?></span></h1>

    <button type="button" class="btn btn-primary mb-4" data-bs-toggle="modal" data-bs-target="#mappingModal">
        <i class="fas fa-plus-circle me-2"></i> Buat Mapping Baru
    </button>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['flash_message']['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['flash_message']['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Siswa Ter-mapping</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Siswa</th>
                            <th>Guru Pembimbing</th>
                            <th>Instruktur DUDIKA</th>
                            <th>Periode PKL</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mappings as $map): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($map['student_name']); ?></td>
                                <td><?php echo htmlspecialchars($map['teacher_name']); ?></td>
                                <td><?php echo htmlspecialchars($map['instructor_name'] . ' (' . $map['company_name'] . ')'); ?></td>
                                <td><?php echo date('d M Y', strtotime($map['start_date'])) . ' - ' . date('d M Y', strtotime($map['end_date'])); ?></td>
                                <td>
                                    <button class="btn btn-warning btn-sm edit-btn"
                                            data-id="<?php echo $map['id']; ?>"
                                            data-student_id="<?php echo $map['student_id']; ?>"
                                            data-teacher_id="<?php echo $map['teacher_id']; ?>"
                                            data-instructor_id="<?php echo $map['instructor_id']; ?>"
                                            data-start_date="<?php echo $map['start_date']; ?>"
                                            data-end_date="<?php echo $map['end_date']; ?>"
                                            data-bs-toggle="modal" data-bs-target="#mappingModal">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="core/mapping_actions.php?action=delete&id=<?php echo $map['id']; ?>"
                                       class="btn btn-danger btn-sm btn-delete"
                                       onclick="return confirm('Apakah Anda yakin ingin menghapus mapping ini?');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk Tambah/Edit Mapping -->
<div class="modal fade" id="mappingModal" tabindex="-1" aria-labelledby="mappingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="mappingModalLabel">Form Mapping PKL</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="mappingForm" action="core/mapping_actions.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="mapping_id" id="mapping_id">
                    <input type="hidden" name="action" id="form_action" value="create">
                    <input type="hidden" name="academic_year_id" value="<?php echo $active_year_id; ?>">

                    <div class="mb-3">
                        <label for="student_id" class="form-label">Siswa</label>
                        <select class="form-select" id="student_id" name="student_id" required>
                            <option value="" disabled selected>-- Pilih Siswa --</option>
                            <?php foreach ($unmapped_students as $student): ?>
                                <option value="<?php echo $student['id']; ?>"><?php echo htmlspecialchars($student['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="teacher_id" class="form-label">Guru Pembimbing</label>
                        <select class="form-select" id="teacher_id" name="teacher_id" required>
                            <option value="" disabled selected>-- Pilih Guru --</option>
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?php echo $teacher['id']; ?>"><?php echo htmlspecialchars($teacher['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="instructor_id" class="form-label">Instruktur DUDIKA</label>
                        <select class="form-select" id="instructor_id" name="instructor_id" required>
                            <option value="" disabled selected>-- Pilih Instruktur --</option>
                            <?php foreach ($instructors as $instructor): ?>
                                <option value="<?php echo $instructor['id']; ?>"><?php echo htmlspecialchars($instructor['name'] . ' (' . $instructor['company_name'] . ')'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="start_date" class="form-label">Tanggal Mulai</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="end_date" class="form-label">Tanggal Selesai</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Mapping</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Script Select2 yang sudah ada akan tetap berfungsi
$(document).ready(function() {
    const initSelect2 = () => {
        $('#student_id, #teacher_id, #instructor_id').select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#mappingModal')
        });
    };

    const mappingModal = document.getElementById('mappingModal');
    const studentSelect = $('#student_id');

    mappingModal.addEventListener('show.bs.modal', function(event) {
        initSelect2();

        const button = event.relatedTarget;
        const form = document.getElementById('mappingForm');

        studentSelect.prop('disabled', false);

        if (button.classList.contains('edit-btn')) {
            form.querySelector('.modal-title').textContent = 'Edit Mapping PKL';
            form.querySelector('#form_action').value = 'update';
            form.querySelector('#mapping_id').value = button.dataset.id;

            $('#teacher_id').val(button.dataset.teacher_id).trigger('change');
            $('#instructor_id').val(button.dataset.instructor_id).trigger('change');
            $('#start_date').val(button.dataset.start_date);
            $('#end_date').val(button.dataset.end_date);

            const studentId = button.dataset.student_id;
            const studentName = button.closest('tr').querySelector('td:first-child').textContent;

            if (studentSelect.find("option[value='" + studentId + "']").length === 0) {
                const newOption = new Option(studentName, studentId, true, true);
                studentSelect.append(newOption).trigger('change');
            }

            studentSelect.val(studentId).trigger('change');
            studentSelect.prop('disabled', true);
        } else {
            form.querySelector('.modal-title').textContent = 'Buat Mapping PKL Baru';
            form.querySelector('#form_action').value = 'create';
            form.reset();
            $('#student_id, #teacher_id, #instructor_id').val(null).trigger('change');
        }
    });
});
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>