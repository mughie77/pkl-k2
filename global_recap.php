<?php
require_once __DIR__ . '/templates/header.php';

// Proteksi halaman
$allowed_roles = ['waka_humas', 'instructor'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// Ambil data untuk filter
try {
    $depts = $pdo->query("SELECT id, program_name FROM program_keahlian ORDER BY program_name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $teachers = $pdo->query("SELECT id, teacher_name FROM teachers ORDER BY teacher_name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $companies = $pdo->query("SELECT company_id, company_name FROM companies ORDER BY company_name ASC")->fetchAll(PDO::FETCH_ASSOC);

    $student_query = "SELECT s.id, s.student_name, k.id as kelas_id, kk.id as konsentrasi_id, pk.id as program_id FROM students s JOIN kelas k ON s.kelas_id = k.id JOIN konsentrasi_keahlian kk ON k.konsentrasi_id = kk.id JOIN program_keahlian pk ON kk.program_id = pk.id";
    $student_params = [];
    if ($user_role === 'instructor') {
        $student_query .= " JOIN internship_mappings m ON s.id = m.student_id WHERE m.instructor_id = :user_id";
        $student_params[':user_id'] = $user_id;
    }
    $student_query .= " ORDER BY s.student_name ASC";
    $stmt_students = $pdo->prepare($student_query);
    $stmt_students->execute($student_params);
    $students_for_filter = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error fetching filter data: " . $e->getMessage());
}

// Proses filter
$filter_program = $_GET['program_id'] ?? 'all';
$filter_teacher = $_GET['teacher_id'] ?? 'all';
$filter_company = $_GET['company_id'] ?? 'all';
$filter_student = $_GET['student_id'] ?? 'all';
$filter_start_date = $_GET['start_date'] ?? '';
$filter_end_date = $_GET['end_date'] ?? '';

$query = "
    SELECT
        j.journal_date, j.check_in_time, j.check_out_time, j.status, j.activities,
        j.check_in_latitude, j.check_in_longitude, j.check_out_latitude, j.check_out_longitude,
        s.student_name,
        pk.program_name,
        c.company_name, c.latitude as company_latitude, c.longitude as company_longitude,
        t.teacher_name
    FROM internship_journals j
    JOIN students s ON j.student_id = s.id
    JOIN internship_mappings m ON s.id = m.student_id AND m.academic_year_id = :academic_year_id
    JOIN kelas k ON s.kelas_id = k.id
    JOIN konsentrasi_keahlian kk ON k.konsentrasi_id = kk.id
    JOIN program_keahlian pk ON kk.program_id = pk.id
    LEFT JOIN companies c ON m.company_id = c.company_id
    LEFT JOIN teachers t ON m.teacher_id = t.id
";
$params = [':academic_year_id' => $_SESSION['selected_academic_year_id']];
$where_clauses = [];

if ($user_role === 'instructor') {
    $where_clauses[] = "m.instructor_id = :user_id";
    $params[':user_id'] = $user_id;
}

if ($filter_program !== 'all') { $where_clauses[] = "pk.id = :program_id"; $params[':program_id'] = $filter_program; }
if ($filter_teacher !== 'all') { $where_clauses[] = "m.teacher_id = :teacher_id"; $params[':teacher_id'] = $filter_teacher; }
if ($filter_company !== 'all') { $where_clauses[] = "m.company_id = :company_id"; $params[':company_id'] = $filter_company; }
if ($filter_student !== 'all') { $where_clauses[] = "j.student_id = :student_id"; $params[':student_id'] = $filter_student; }
if (!empty($filter_start_date)) { $where_clauses[] = "j.journal_date >= :start_date"; $params[':start_date'] = $filter_start_date; }
if (!empty($filter_end_date)) { $where_clauses[] = "j.journal_date <= :end_date"; $params[':end_date'] = $filter_end_date; }

if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(" AND ", $where_clauses);
}

$query .= " ORDER BY j.journal_date DESC, s.student_name ASC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $recap_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching recap data: " . $e->getMessage());
}

function get_status_badge($status) {
    switch ($status) {
        case 'Approved': return 'bg-success';
        case 'Rejected': return 'bg-danger';
        case 'Pending': return 'bg-warning text-dark';
        default: return 'bg-secondary';
    }
}
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>#map-view { height: 450px; }</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Rekap Absensi & Jurnal Siswa</h1>
        <a href="#" id="exportBtn" class="btn btn-success">
            <i class="fas fa-file-excel me-2"></i>Ekspor ke Excel
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header"><h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-filter me-2"></i>Filter Data</h6></div>
        <div class="card-body">
            <form method="GET" action="global_recap.php" class="row g-3 align-items-end">
                <?php if ($user_role === 'waka_humas'): ?>
                <div class="col-md-3">
                    <label class="form-label">DUDIKA</label>
                    <select name="company_id" id="company_id" class="form-select">
                        <option value="all">Semua DUDIKA</option>
                        <?php foreach ($companies as $c): ?><option value="<?php echo $c['company_id']; ?>" <?php echo ($filter_company == $c['company_id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['company_name']); ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Guru</label>
                    <select name="teacher_id" id="teacher_id" class="form-select">
                        <option value="all">Semua Guru</option>
                        <?php foreach ($teachers as $t): ?><option value="<?php echo $t['id']; ?>" <?php echo ($filter_teacher == $t['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($t['teacher_name']); ?></option><?php endforeach; ?>
                    </select>
                </div>
                 <div class="col-md-3">
                    <label class="form-label">Program Keahlian</label>
                    <select name="program_id" id="program_id" class="form-select">
                        <option value="all">Semua Program</option>
                        <?php foreach ($depts as $p): ?><option value="<?php echo $p['id']; ?>" <?php echo ($filter_program == $p['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($p['program_name']); ?></option><?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="col-md-3">
                    <label class="form-label">Siswa</label>
                    <select name="student_id" id="student_id" class="form-select">
                        <option value="all">Semua Siswa</option>
                        <?php foreach ($students_for_filter as $s): ?><option value="<?php echo $s['id']; ?>" <?php echo ($filter_student == $s['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($s['student_name']); ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Dari</label>
                    <input type="date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($filter_start_date); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Sampai</label>
                    <input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($filter_end_date); ?>">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary w-100">Cari</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Hasil Rekap</h6></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Nama Siswa</th>
                             <?php if ($user_role === 'waka_humas'): ?><th>DUDIKA</th><th>Guru</th><?php endif; ?>
                            <th>Absensi</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($recap_data) > 0): ?>
                            <?php foreach ($recap_data as $recap): ?>
                                <tr>
                                    <td><?php echo date('d M Y', strtotime($recap['journal_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($recap['student_name']); ?></td>
                                    <?php if ($user_role === 'waka_humas'): ?>
                                    <td><?php echo htmlspecialchars($recap['company_name'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($recap['teacher_name'] ?? '-'); ?></td>
                                    <?php endif; ?>
                                    <td><?php echo $recap['check_in_time'] ? date('H:i', strtotime($recap['check_in_time'])) : '-'; ?> s/d <?php echo $recap['check_out_time'] ? date('H:i', strtotime($recap['check_out_time'])) : '-'; ?></td>
                                    <td><span class="badge <?php echo get_status_badge($recap['status']); ?>"><?php echo htmlspecialchars($recap['status']); ?></span></td>
                                    <td>
                                         <?php if (!empty($recap['check_in_latitude'])): ?>
                                            <button class="btn btn-primary btn-sm view-location-btn" data-bs-toggle="modal" data-bs-target="#locationViewModal" data-checkin-lat="<?php echo $recap['check_in_latitude']; ?>" data-checkin-lon="<?php echo $recap['check_in_longitude']; ?>" data-checkout-lat="<?php echo $recap['check_out_latitude']; ?>" data-checkout-lon="<?php echo $recap['check_out_longitude']; ?>" data-company-lat="<?php echo $recap['company_latitude']; ?>" data-company-lon="<?php echo $recap['company_longitude']; ?>" data-student-name="<?php echo htmlspecialchars($recap['student_name']); ?>" data-company-name="<?php echo htmlspecialchars($recap['company_name']); ?>"><i class="fas fa-map-marked-alt"></i></button>
                                        <?php endif; ?>
                                        <button class="btn btn-info btn-sm view-journal-btn" data-activities="<?php echo htmlspecialchars($recap['activities']); ?>" data-student-name="<?php echo htmlspecialchars($recap['student_name']); ?>" data-journal-date="<?php echo date('d M Y', strtotime($recap['journal_date'])); ?>" data-bs-toggle="modal" data-bs-target="#viewJournalModal"><i class="fas fa-eye"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center">Tidak ada data yang ditemukan.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modals -->
<div class="modal fade" id="viewJournalModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Detail Jurnal</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><h6 class="mb-3">Jurnal: <span id="modal_student_name" class="fw-normal"></span> - <span id="modal_journal_date" class="fw-normal"></span></h6><hr><p id="journal_activities_content" style="white-space: pre-wrap;"></p></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button></div></div></div></div>
<div class="modal fade" id="locationViewModal" tabindex="-1"><div class="modal-dialog modal-xl"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Verifikasi Lokasi Absensi</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div id="map-view"></div><div class="alert alert-info mt-3" id="distance-info"></div></div></div></div></div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
$(document).ready(function() {
    function updateExportLink() {
        const params = new URLSearchParams(window.location.search);
        params.set('type', 'rekap_absen');
        $('#exportBtn').attr('href', 'core/export_handler.php?' + params.toString());
    }

    updateExportLink();
    $('select, input[type=date]').on('change', updateExportLink);

    $('select').select2({ theme: 'bootstrap-5' });
    const viewJournalModal = $('#viewJournalModal');
    viewJournalModal.on('show.bs.modal', function(event) {
        const button = $(event.relatedTarget);
        $('#modal_student_name').text(button.data('student-name'));
        $('#modal_journal_date').text(button.data('journal-date'));
        $('#journal_activities_content').text(button.data('activities'));
    });

    const locationViewModal = $('#locationViewModal');
    let mapView = null;
    locationViewModal.on('show.bs.modal', function(event) {
        const button = $(event.relatedTarget);
        const checkinLat = parseFloat(button.data('checkin-lat')), checkinLon = parseFloat(button.data('checkin-lon'));
        const checkoutLat = parseFloat(button.data('checkout-lat')), checkoutLon = parseFloat(button.data('checkout-lon'));
        const companyLat = parseFloat(button.data('company-lat')), companyLon = parseFloat(button.data('company-lon'));

        if (!mapView) {
            mapView = L.map('map-view').setView([checkinLat, checkinLon], 16);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(mapView);
        } else {
            mapView.eachLayer(l => { if (l instanceof L.Marker || l instanceof L.Polyline) mapView.removeLayer(l); });
        }

        const bounds = [];
        let distInfo = '';
        const addMarker = (lat, lon, color, popup) => {
            const marker = L.marker([lat, lon], { icon: L.icon({ iconUrl: `https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-${color}.png`, shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png', iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41] }) }).addTo(mapView).bindPopup(popup);
            bounds.push([lat, lon]);
            return marker;
        };

        addMarker(checkinLat, checkinLon, 'green', `<b>Check-in:</b> ${button.data('student-name')}`);
        if (!isNaN(checkoutLat)) addMarker(checkoutLat, checkoutLon, 'blue', `<b>Check-out:</b> ${button.data('student-name')}`);
        if (!isNaN(companyLat)) {
            const companyLoc = [companyLat, companyLon];
            addMarker(companyLat, companyLon, 'red', `<b>DUDIKA:</b> ${button.data('company-name')}`);
            distInfo += `<li>Jarak Check-in: <strong>${mapView.distance([checkinLat, checkinLon], companyLoc).toFixed(0)} m</strong></li>`;
            if (!isNaN(checkoutLat)) distInfo += `<li>Jarak Check-out: <strong>${mapView.distance([checkoutLat, checkoutLon], companyLoc).toFixed(0)} m</strong></li>`;
        } else {
            distInfo = '<li>Lokasi DUDIKA belum di-set.</li>';
        }
        $('#distance-info').html(`<ul class="list-unstyled mb-0">${distInfo}</ul>`);
        if (bounds.length > 1) mapView.fitBounds(bounds, { padding: [70, 70] });
    });
    locationViewModal.on('shown.bs.modal', () => setTimeout(() => mapView?.invalidateSize(), 10));
});
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>