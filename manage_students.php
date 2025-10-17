<?php
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/sidebar.php';

// Proteksi halaman
if ($_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Ambil data untuk dropdowns
try {
    $programs = $pdo->query("SELECT * FROM program_keahlian ORDER BY program_name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $konsentrasi_list = $pdo->query("SELECT * FROM konsentrasi_keahlian ORDER BY konsentrasi_name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $kelas_list = $pdo->query("SELECT * FROM kelas ORDER BY kelas_name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $academic_years = $pdo->query("SELECT id, year_name FROM academic_years ORDER BY year_name DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching dropdown data: " . $e->getMessage());
}

// Ambil data siswa dari tahun ajaran aktif
$active_year_id = $active_year['id'] ?? 0;
try {
    $stmt = $pdo->prepare("
        SELECT s.*, k.kelas_name, kk.konsentrasi_name, pk.program_name
        FROM students s
        JOIN kelas k ON s.kelas_id = k.id
        JOIN konsentrasi_keahlian kk ON k.konsentrasi_id = kk.id
        JOIN program_keahlian pk ON kk.program_id = pk.id
        WHERE s.academic_year_id = :year_id
        ORDER BY s.name ASC
    ");
    $stmt->execute([':year_id' => $active_year_id]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error: Could not fetch students data. " . $e->getMessage());
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Manajemen Data Siswa <span class="badge bg-info"><?php echo htmlspecialchars($active_year['year_name'] ?? 'Tahun Ajaran Belum Dipilih'); ?></span></h1>

    <div class="d-flex mb-4">
        <button type="button" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#studentModal">
            <i class="fas fa-plus-circle me-2"></i> Tambah Siswa Baru
        </button>
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#importModal">
            <i class="fas fa-file-excel me-2"></i> Impor dari Excel
        </button>
    </div>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['flash_message']['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['flash_message']['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Siswa Tahun Pelajaran Aktif</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Nama Lengkap</th>
                            <th>NISN</th>
                            <th>Program Keahlian</th>
                            <th>Kelas</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['name']); ?></td>
                                <td><?php echo htmlspecialchars($student['nisn']); ?></td>
                                <td><?php echo htmlspecialchars($student['program_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['kelas_name']); ?></td>
                                <td>
                                    <button class="btn btn-warning btn-sm edit-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#studentModal"
                                            data-student='<?php echo htmlspecialchars(json_encode($student), ENT_QUOTES, 'UTF-8'); ?>'>
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="student_details.php?id=<?php echo $student['id']; ?>" class="btn btn-info btn-sm">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="core/student_actions.php?action=delete&id=<?php echo $student['id']; ?>"
                                       class="btn btn-danger btn-sm btn-delete"
                                       onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?');">
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

<!-- Modal untuk Impor Excel -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importModalLabel">Impor Data Siswa dari Excel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="core/import_student_action.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <p>
                        Unggah file Excel (.xlsx) untuk mengimpor data siswa secara massal. Pastikan file Anda sesuai dengan format template.
                    </p>
                    <p>
                        Kolom yang diperlukan adalah: <strong>NISN, NIS, Nama Lengkap, Email, Tempat Lahir, Tanggal Lahir (YYYY-MM-DD), Alamat, No. HP Siswa, No. HP Orang Tua, Nama Kelas</strong>. Pastikan Nama Kelas sudah terdaftar di sistem.
                    </p>
                    <div class="mb-3">
                        <label for="excelFile" class="form-label">Pilih File Excel</label>
                        <input class="form-control" type="file" id="excelFile" name="excelFile" accept=".xlsx" required>
                    </div>
                     <div class="mb-3">
                        <label for="import_academic_year_id" class="form-label">Tahun Pelajaran untuk Impor</label>
                        <select class="form-select" id="import_academic_year_id" name="academic_year_id" required>
                            <option value="">-- Pilih Tahun Pelajaran --</option>
                            <?php foreach ($academic_years as $year): ?>
                                <option value="<?php echo $year['id']; ?>" <?php echo ($year['id'] == $active_year_id) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($year['year_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload me-2"></i> Unggah dan Impor
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- Modal untuk Tambah/Edit Siswa -->
<div class="modal fade" id="studentModal" tabindex="-1" aria-labelledby="studentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="studentModalLabel">Form Data Siswa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="studentForm" action="core/student_actions.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="student_id" id="student_id">
                    <input type="hidden" name="action" id="form_action" value="create">

                    <div class="row">
                        <div class="col-md-6 mb-3"><label for="name" class="form-label">Nama Lengkap</label><input type="text" class="form-control" id="name" name="name" required></div>
                        <div class="col-md-6 mb-3"><label for="email" class="form-label">Email (Opsional)</label><input type="email" class="form-control" id="email" name="email"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label for="nis" class="form-label">NIS</label><input type="text" class="form-control" id="nis" name="nis"></div>
                        <div class="col-md-6 mb-3"><label for="nisn" class="form-label">NISN</label><input type="text" class="form-control" id="nisn" name="nisn" required></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label for="birth_place" class="form-label">Tempat Lahir</label><input type="text" class="form-control" id="birth_place" name="birth_place"></div>
                        <div class="col-md-6 mb-3"><label for="birth_date" class="form-label">Tanggal Lahir</label><input type="date" class="form-control" id="birth_date" name="birth_date"></div>
                    </div>
                    <div class="mb-3"><label for="address" class="form-label">Alamat Lengkap</label><textarea class="form-control" id="address" name="address" rows="2"></textarea></div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label for="phone" class="form-label">No. HP Siswa</label><input type="tel" class="form-control" id="phone" name="phone"></div>
                        <div class="col-md-6 mb-3"><label for="parent_phone" class="form-label">No. HP Orang Tua</label><input type="tel" class="form-control" id="parent_phone" name="parent_phone"></div>
                    </div>
                     <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="program_id" class="form-label">Program Keahlian</label>
                            <select class="form-select" id="program_id" name="program_id" required>
                                <option value="">-- Pilih Program --</option>
                                <?php foreach ($programs as $program): ?>
                                    <option value="<?php echo $program['id']; ?>"><?php echo htmlspecialchars($program['program_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="konsentrasi_id" class="form-label">Konsentrasi Keahlian</label>
                            <select class="form-select" id="konsentrasi_id" name="konsentrasi_id" required disabled>
                                <option value="">-- Pilih Konsentrasi --</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="kelas_id" class="form-label">Kelas</label>
                            <select class="form-select" id="kelas_id" name="kelas_id" required disabled>
                                <option value="">-- Pilih Kelas --</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="academic_year_id" class="form-label">Tahun Pelajaran</label>
                            <select class="form-select" id="academic_year_id" name="academic_year_id" required>
                                <option value="">-- Pilih Tahun Pelajaran --</option>
                                <?php foreach ($academic_years as $year): ?>
                                    <option value="<?php echo $year['id']; ?>"><?php echo htmlspecialchars($year['year_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Store all options
    const allKonsentrasi = <?php echo json_encode($konsentrasi_list); ?>;
    const allKelas = <?php echo json_encode($kelas_list); ?>;

    function populateKonsentrasi(programId, selectedId = null) {
        const konsentrasiSelect = $('#konsentrasi_id');
        konsentrasiSelect.html('<option value="">-- Pilih Konsentrasi --</option>').prop('disabled', true);
        const filtered = allKonsentrasi.filter(k => k.program_id == programId);

        if (filtered.length > 0) {
            filtered.forEach(k => {
                konsentrasiSelect.append(new Option(k.konsentrasi_name, k.id));
            });
            konsentrasiSelect.prop('disabled', false);
        }
        if(selectedId) konsentrasiSelect.val(selectedId);
        konsentrasiSelect.trigger('change');
    }

    function populateKelas(konsentrasiId, selectedId = null) {
        const kelasSelect = $('#kelas_id');
        kelasSelect.html('<option value="">-- Pilih Kelas --</option>').prop('disabled', true);
        const filtered = allKelas.filter(k => k.konsentrasi_id == konsentrasiId);

        if (filtered.length > 0) {
            filtered.forEach(k => {
                kelasSelect.append(new Option(k.kelas_name, k.id));
            });
            kelasSelect.prop('disabled', false);
        }
        if(selectedId) kelasSelect.val(selectedId);
    }

    $('#program_id').on('change', function() {
        populateKonsentrasi($(this).val());
    });

    $('#konsentrasi_id').on('change', function() {
        populateKelas($(this).val());
    });

    const initStudentSelect2 = () => {
        $('#program_id, #konsentrasi_id, #kelas_id, #academic_year_id').select2({ theme: 'bootstrap-5', dropdownParent: $('#studentModal') });
    };

    const studentModal = document.getElementById('studentModal');
    studentModal.addEventListener('show.bs.modal', function(event) {
        initStudentSelect2();
        const button = event.relatedTarget;
        const form = document.getElementById('studentForm');

        if (button.classList.contains('edit-btn')) {
            form.querySelector('.modal-title').textContent = 'Edit Data Siswa';
            form.querySelector('#form_action').value = 'update';
            const studentData = JSON.parse(button.dataset.student);

            // Populate form fields
            Object.keys(studentData).forEach(key => {
                const el = form.querySelector(`#${key}`);
                if (el) el.value = studentData[key];
            });

            // Handle dropdowns
            $.get('api/get_student_hierarchy.php?student_id=' + studentData.id, function(data) {
                if(data.program_id) {
                    $('#program_id').val(data.program_id).trigger('change');
                    setTimeout(() => {
                        populateKonsentrasi(data.program_id, data.konsentrasi_id);
                        setTimeout(() => {
                           populateKelas(data.konsentrasi_id, data.kelas_id);
                        }, 200);
                    }, 200);
                }
            });
            $('#academic_year_id').val(studentData.academic_year_id).trigger('change');

        } else {
            form.querySelector('.modal-title').textContent = 'Tambah Siswa Baru';
            form.querySelector('#form_action').value = 'create';
            form.reset();
            $('#program_id, #konsentrasi_id, #kelas_id, #academic_year_id').val(null).trigger('change');
            $('#konsentrasi_id, #kelas_id').prop('disabled', true);
            $('#academic_year_id').val('<?php echo $active_year_id; ?>').trigger('change');
        }
    });
});
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>