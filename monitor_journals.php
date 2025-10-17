<?php
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/sidebar.php';

// Proteksi halaman
$allowed_roles = ['teacher', 'instructor', 'waka_humas'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// Filter
$filter_student_id = $_GET['student_id'] ?? 'all';
$filter_start_date = $_GET['start_date'] ?? '';
$filter_end_date = $_GET['end_date'] ?? '';

try {
    // Kueri untuk mengambil siswa yang relevan dengan peran pengguna
    $student_query = "SELECT s.id, s.name FROM students s ";
    $student_params = [];
    if ($user_role === 'teacher') {
        $student_query .= "JOIN internship_mappings m ON s.id = m.student_id WHERE m.teacher_id = :user_id";
        $student_params[':user_id'] = $user_id;
    } elseif ($user_role === 'instructor') {
        $student_query .= "JOIN internship_mappings m ON s.id = m.student_id WHERE m.instructor_id = :user_id";
        $student_params[':user_id'] = $user_id;
    }
    $student_query .= " ORDER BY s.name ASC";
    $stmt_students = $pdo->prepare($student_query);
    $stmt_students->execute($student_params);
    $students_for_filter = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

    // Bangun query utama
    $query = "
        SELECT
            j.journal_date, j.check_in_time, j.check_out_time, j.status, j.activities,
            j.check_in_latitude, j.check_in_longitude, j.check_out_latitude, j.check_out_longitude,
            s.name as student_name, s.work_start_time, s.work_end_time,
            c.name as company_name, c.latitude as company_latitude, c.longitude as company_longitude
        FROM internship_journals j
        JOIN students s ON j.student_id = s.id
        LEFT JOIN internship_mappings m ON j.student_id = m.student_id
        LEFT JOIN instructors i ON m.instructor_id = i.id
        LEFT JOIN companies c ON i.company_id = c.id
    ";

    $params = [];
    $where_clauses = [];

    if ($user_role === 'teacher') {
        $where_clauses[] = "m.teacher_id = :user_id";
        $params[':user_id'] = $user_id;
    } elseif ($user_role === 'instructor') {
        $where_clauses[] = "m.instructor_id = :user_id";
        $params[':user_id'] = $user_id;
    }

    if ($filter_student_id !== 'all' && !empty($filter_student_id)) {
        $query .= " AND j.student_id = :student_id";
        $params[':student_id'] = $filter_student_id;
    }
    if (!empty($filter_start_date)) {
        $query .= " AND j.journal_date >= :start_date";
        $params[':start_date'] = $filter_start_date;
    }
    if (!empty($filter_end_date)) {
        $where_clauses[] = "j.journal_date <= :end_date";
        $params[':end_date'] = $filter_end_date;
    }

    if (!empty($where_clauses)) {
        $query .= " WHERE " . implode(" AND ", $where_clauses);
    }

    $query .= " ORDER BY j.journal_date DESC, s.name ASC";

    $stmt_journals = $pdo->prepare($query);
    $stmt_journals->execute($params);
    $journals = $stmt_journals->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error: Could not fetch journals data. " . $e->getMessage());
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

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<style>
    #map-view { height: 450px; }
    .map-legend {
        padding: 6px 8px;
        font: 14px/16px Arial, Helvetica, sans-serif;
        background: white;
        background: rgba(255,255,255,0.8);
        box-shadow: 0 0 15px rgba(0,0,0,0.2);
        border-radius: 5px;
    }
    .map-legend .legend-item {
        display: flex;
        align-items: center;
        margin-bottom: 5px;
    }
    .map-legend .legend-color {
        width: 18px;
        height: 18px;
        margin-right: 8px;
        border-radius: 50%;
        border: 2px solid rgba(0,0,0,0.2);
    }
</style>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Monitoring Jurnal dan Absensi Siswa</h1>

    <!-- Filter Form -->
    <div class="card shadow mb-4">
        <div class="card-header">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-filter me-2"></i>Filter Data</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="monitor_journals.php" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="student_id" class="form-label">Pilih Siswa</label>
                    <select name="student_id" id="student_id" class="form-select">
                        <option value="all">Semua Siswa Bimbingan</option>
                        <?php foreach ($students_for_filter as $student): ?>
                            <option value="<?php echo $student['id']; ?>" <?php echo ($filter_student_id == $student['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($student['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="start_date" class="form-label">Dari Tanggal</label>
                    <input type="date" name="start_date" id="start_date" class="form-control" value="<?php echo htmlspecialchars($filter_start_date); ?>">
                </div>
                <div class="col-md-3">
                    <label for="end_date" class="form-label">Sampai Tanggal</label>
                    <input type="date" name="end_date" id="end_date" class="form-control" value="<?php echo htmlspecialchars($filter_end_date); ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Terapkan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Data Jurnal dan Kehadiran</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Nama Siswa</th>
                            <th>Absensi (Check-in)</th>
                            <th>Lokasi Absen</th>
                            <th>Status Jurnal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($journals) > 0): ?>
                            <?php foreach ($journals as $journal): ?>
                                <tr>
                                    <td><?php echo date('d M Y', strtotime($journal['journal_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($journal['student_name']); ?></td>
                                    <td><?php echo $journal['check_in_time'] ? date('H:i', strtotime($journal['check_in_time'])) : '<span class="badge bg-secondary">N/A</span>'; ?></td>
                                    <td>
                                        <?php if (!empty($journal['check_in_latitude'])): ?>
                                            <button class="btn btn-primary btn-sm view-location-btn"
                                                    data-bs-toggle="modal" data-bs-target="#locationViewModal"
                                                    data-checkin-lat="<?php echo $journal['check_in_latitude']; ?>"
                                                    data-checkin-lon="<?php echo $journal['check_in_longitude']; ?>"
                                                    data-checkout-lat="<?php echo $journal['check_out_latitude']; ?>"
                                                    data-checkout-lon="<?php echo $journal['check_out_longitude']; ?>"
                                                    data-company-lat="<?php echo $journal['company_latitude']; ?>"
                                                    data-company-lon="<?php echo $journal['company_longitude']; ?>"
                                                    data-student-name="<?php echo htmlspecialchars($journal['student_name']); ?>"
                                                    data-company-name="<?php echo htmlspecialchars($journal['company_name']); ?>">
                                                <i class="fas fa-map-marked-alt"></i> Lihat
                                            </button>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo get_status_badge($journal['status']); ?>">
                                            <?php echo htmlspecialchars($journal['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-info btn-sm view-journal-btn"
                                                data-activities="<?php echo htmlspecialchars($journal['activities']); ?>"
                                                data-student-name="<?php echo htmlspecialchars($journal['student_name']); ?>"
                                                data-journal-date="<?php echo date('d M Y', strtotime($journal['journal_date'])); ?>"
                                                data-bs-toggle="modal" data-bs-target="#viewJournalModal">
                                            <i class="fas fa-eye"></i> Lihat Jurnal
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">Tidak ada data yang cocok dengan filter yang diterapkan.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk Melihat Jurnal -->
<div class="modal fade" id="viewJournalModal" tabindex="-1" aria-labelledby="viewJournalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewJournalModalLabel">Detail Jurnal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6 class="mb-3">Jurnal: <span id="modal_student_name" class="fw-normal"></span> - <span id="modal_journal_date" class="fw-normal"></span></h6>
                <hr>
                <p id="journal_activities_content" style="white-space: pre-wrap;"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk Melihat Lokasi -->
<div class="modal fade" id="locationViewModal" tabindex="-1" aria-labelledby="locationViewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="locationViewModalLabel">Verifikasi Lokasi Absensi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="map-view"></div>
                <div id="map-legend" class="mt-2"></div>
                <div class="alert alert-info mt-3" id="distance-info"></div>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
$(document).ready(function() {
    // Initialize Select2
    $('#student_id').select2({
        theme: 'bootstrap-5'
    });

    const viewJournalModal = document.getElementById('viewJournalModal');
    viewJournalModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const activities = button.dataset.activities;
        const studentName = button.dataset.studentName;
        const journalDate = button.dataset.journalDate;

        viewJournalModal.querySelector('#modal_student_name').textContent = studentName;
        viewJournalModal.querySelector('#modal_journal_date').textContent = journalDate;
        viewJournalModal.querySelector('#journal_activities_content').textContent = activities;
    });

    // --- Logic for Location View Modal ---
    const locationViewModal = document.getElementById('locationViewModal');
    let mapView = null;

    locationViewModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const checkinLat = parseFloat(button.dataset.checkinLat);
        const checkinLon = parseFloat(button.dataset.checkinLon);
        const checkoutLat = parseFloat(button.dataset.checkoutLat);
        const checkoutLon = parseFloat(button.dataset.checkoutLon);
        const companyLat = parseFloat(button.dataset.companyLat);
        const companyLon = parseFloat(button.dataset.companyLon);
        const studentName = button.dataset.studentName;
        const companyName = button.dataset.companyName;

        const initialLocation = [checkinLat, checkinLon];

        if (!mapView) {
            mapView = L.map('map-view').setView(initialLocation, 16);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(mapView);
        } else {
             mapView.setView(initialLocation, 16);
        }

        // Clear previous layers
        mapView.eachLayer(layer => {
            if (layer instanceof L.Marker || layer instanceof L.Polyline) {
                mapView.removeLayer(layer);
            }
        });

        const bounds = [];
        let distanceInfoHTML = '';

        // Check-in marker
        const checkinLocation = [checkinLat, checkinLon];
        L.marker(checkinLocation, {
            icon: L.icon({
                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41]
            })
        }).addTo(mapView).bindPopup(`<b>Check-in:</b> ${studentName}`);
        bounds.push(checkinLocation);

        // Check-out marker
        if (!isNaN(checkoutLat) && !isNaN(checkoutLon)) {
            const checkoutLocation = [checkoutLat, checkoutLon];
             L.marker(checkoutLocation, {
                icon: L.icon({
                    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
                    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                    iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41]
                })
            }).addTo(mapView).bindPopup(`<b>Check-out:</b> ${studentName}`);
            bounds.push(checkoutLocation);
        }

        // Company marker and distance calculation
        if (!isNaN(companyLat) && !isNaN(companyLon)) {
            const companyLocation = [companyLat, companyLon];
            L.marker(companyLocation, {
                icon: L.icon({
                    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
                    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                    iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41]
                })
            }).addTo(mapView).bindPopup(`<b>Lokasi DUDIKA:</b><br>${companyName}`);
            bounds.push(companyLocation);

            const distCheckin = mapView.distance(checkinLocation, companyLocation);
            distanceInfoHTML += `<li>Jarak Check-in dari DUDIKA: <strong>${distCheckin.toFixed(0)} meter</strong></li>`;

            if (!isNaN(checkoutLat) && !isNaN(checkoutLon)) {
                const distCheckout = mapView.distance([checkoutLat, checkoutLon], companyLocation);
                distanceInfoHTML += `<li>Jarak Check-out dari DUDIKA: <strong>${distCheckout.toFixed(0)} meter</strong></li>`;
            }
        } else {
            distanceInfoHTML = '<li>Lokasi DUDIKA belum di-set, jarak tidak dapat dihitung.</li>';
        }

        document.getElementById('distance-info').innerHTML = `<ul class="list-unstyled mb-0">${distanceInfoHTML}</ul>`;

        if(bounds.length > 1) {
            mapView.fitBounds(bounds, { padding: [70, 70] });
        }
    });

    locationViewModal.addEventListener('shown.bs.modal', function () {
        if (mapView) {
            mapView.invalidateSize();
        }
    });
});
</script>

<?php
if (in_array($_SESSION['user_role'], ['student', 'teacher', 'instructor'])) {
    echo '</div>';
}
require_once __DIR__ . '/templates/footer.php';
?>