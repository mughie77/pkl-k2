<?php
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/sidebar.php';

// Proteksi halaman
if ($_SESSION['user_role'] !== 'teacher') {
    header("Location: login.php");
    exit;
}

$teacher_id = $_SESSION['user_id'];

// Filter
$filter_student_id = $_GET['student_id'] ?? 'all';
$filter_start_date = $_GET['start_date'] ?? '';
$filter_end_date = $_GET['end_date'] ?? '';

try {
    // Ambil daftar siswa bimbingan untuk filter dropdown
    $stmt_students = $pdo->prepare("
        SELECT s.id, s.name FROM students s
        JOIN internship_mappings m ON s.id = m.student_id
        WHERE m.teacher_id = :teacher_id ORDER BY s.name ASC
    ");
    $stmt_students->execute([':teacher_id' => $teacher_id]);
    $students_for_filter = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

    // Bangun query utama
    $query = "
        SELECT
            j.journal_date, j.check_in_time, j.check_out_time, j.status, j.activities,
            j.check_in_latitude, j.check_in_longitude, j.check_out_latitude, j.check_out_longitude,
            s.name as student_name, s.work_start_time, s.work_end_time,
            c.latitude as company_latitude, c.longitude as company_longitude
        FROM internship_journals j
        JOIN students s ON j.student_id = s.id
        JOIN internship_mappings m ON j.student_id = m.student_id
        LEFT JOIN instructors i ON m.instructor_id = i.id
        LEFT JOIN companies c ON i.company_id = c.id
        WHERE m.teacher_id = :teacher_id
    ";

    $params = [':teacher_id' => $teacher_id];

    if ($filter_student_id !== 'all' && !empty($filter_student_id)) {
        $query .= " AND j.student_id = :student_id";
        $params[':student_id'] = $filter_student_id;
    }
    if (!empty($filter_start_date)) {
        $query .= " AND j.journal_date >= :start_date";
        $params[':start_date'] = $filter_start_date;
    }
    if (!empty($filter_end_date)) {
        $query .= " AND j.journal_date <= :end_date";
        $params[':end_date'] = $filter_end_date;
    }

    $query .= " ORDER BY j.journal_date DESC, s.name ASC";

    $stmt_journals = $pdo->prepare($query);
    $stmt_journals->execute($params);
    $journals = $stmt_journals->fetchAll(PDO::FETCH_ASSOC);

    // Ambil data izin (leave) dengan filter yang sama
    $leave_query = "
        SELECT lr.start_date, lr.end_date, lr.leave_type, lr.reason, lr.status, s.name as student_name
        FROM leave_requests lr
        JOIN students s ON lr.student_id = s.id
        JOIN internship_mappings m ON lr.student_id = m.student_id
        WHERE m.teacher_id = :teacher_id AND lr.status = 'Approved'
    ";
    $leave_params = [':teacher_id' => $teacher_id];
    if ($filter_student_id !== 'all' && !empty($filter_student_id)) {
        $leave_query .= " AND lr.student_id = :student_id";
        $leave_params[':student_id'] = $filter_student_id;
    }
    if (!empty($filter_start_date)) {
        $leave_query .= " AND lr.end_date >= :start_date";
        $leave_params[':start_date'] = $filter_start_date;
    }
    if (!empty($filter_end_date)) {
        $leave_query .= " AND lr.start_date <= :end_date";
        $leave_params[':end_date'] = $filter_end_date;
    }
    $stmt_leaves = $pdo->prepare($leave_query);
    $stmt_leaves->execute($leave_params);
    $leaves = $stmt_leaves->fetchAll(PDO::FETCH_ASSOC);

    // Gabungkan dan urutkan data
    $combined_data = [];
    foreach ($journals as $journal) {
        $combined_data[$journal['journal_date']] = [
            'type' => 'Hadir',
            'date' => $journal['journal_date'],
            'student_name' => $journal['student_name'],
            'details' => 'Check-in: ' . ($journal['check_in_time'] ? date('H:i', strtotime($journal['check_in_time'])) : '-'),
            'journal_data' => $journal // Simpan data asli untuk modal
        ];
    }
    foreach ($leaves as $leave) {
        $period = new DatePeriod(new DateTime($leave['start_date']), new DateInterval('P1D'), (new DateTime($leave['end_date']))->modify('+1 day'));
        foreach ($period as $date) {
            $date_str = $date->format('Y-m-d');
            $combined_data[$date_str] = [
                'type' => $leave['leave_type'],
                'date' => $date_str,
                'student_name' => $leave['student_name'],
                'details' => $leave['reason'],
                'journal_data' => null
            ];
        }
    }
    krsort($combined_data); // Urutkan berdasarkan tanggal (menurun)

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
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Data Jurnal dan Kehadiran</h6>
            <a href="core/export_handler.php?report_type=journal_recap&student_id=<?php echo urlencode($filter_student_id); ?>&start_date=<?php echo urlencode($filter_start_date); ?>&end_date=<?php echo urlencode($filter_end_date); ?>" class="btn btn-sm btn-success">
                <i class="fas fa-file-excel me-2"></i>Export to Excel
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Nama Siswa</th>
                            <th>Status Kehadiran</th>
                            <th>Detail</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($combined_data) > 0): ?>
                            <?php foreach ($combined_data as $item): ?>
                                <tr>
                                    <td><?php echo date('d M Y', strtotime($item['date'])); ?></td>
                                    <td><?php echo htmlspecialchars($item['student_name']); ?></td>
                                    <td>
                                        <?php
                                        $status_badge = 'bg-secondary';
                                        if ($item['type'] === 'Hadir') $status_badge = 'bg-success';
                                        if ($item['type'] === 'Izin') $status_badge = 'bg-warning text-dark';
                                        if ($item['type'] === 'Sakit') $status_badge = 'bg-danger';
                                        ?>
                                        <span class="badge <?php echo $status_badge; ?>"><?php echo htmlspecialchars($item['type']); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['details']); ?></td>
                                    <td>
                                        <?php if ($item['type'] === 'Hadir' && $item['journal_data']): ?>
                                            <button class="btn btn-info btn-sm view-journal-btn"
                                                    data-activities="<?php echo htmlspecialchars($item['journal_data']['activities']); ?>"
                                                    data-student-name="<?php echo htmlspecialchars($item['student_name']); ?>"
                                                    data-journal-date="<?php echo date('d M Y', strtotime($item['date'])); ?>"
                                                    data-bs-toggle="modal" data-bs-target="#viewJournalModal">
                                                <i class="fas fa-eye"></i> Jurnal
                                            </button>
                                            <?php if ($item['journal_data']['check_in_latitude']): ?>
                                                <button class="btn btn-secondary btn-sm view-location-btn"
                                                        data-lat-in="<?php echo $item['journal_data']['check_in_latitude']; ?>"
                                                        data-lng-in="<?php echo $item['journal_data']['check_in_longitude']; ?>"
                                                        data-lat-out="<?php echo $item['journal_data']['check_out_latitude']; ?>"
                                                        data-lng-out="<?php echo $item['journal_data']['check_out_longitude']; ?>"
                                                        data-company-lat="<?php echo $item['journal_data']['company_latitude']; ?>"
                                                        data-company-lng="<?php echo $item['journal_data']['company_longitude']; ?>"
                                                        data-student-name="<?php echo htmlspecialchars($item['student_name']); ?>"
                                                        data-bs-toggle="modal" data-bs-target="#viewLocationModal">
                                                    <i class="fas fa-map-marker-alt"></i> Lokasi
                                                </button>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">Tidak ada data jurnal atau izin yang cocok dengan filter yang diterapkan.</td>
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

<!-- Modal untuk Peta Lokasi -->
<div class="modal fade" id="viewLocationModal" tabindex="-1" aria-labelledby="viewLocationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewLocationModalLabel">Lokasi Absensi Siswa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Lokasi <strong id="location_student_name"></strong> saat melakukan check-in.</p>
                <div id="map" style="height: 400px; width: 100%;"></div>
                <div id="distance-info" class="mt-3"></div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Select2
    $('#student_id').select2({
        theme: 'bootstrap-5'
    });

    const viewJournalModal = document.getElementById('viewJournalModal');
    if (viewJournalModal) {
        viewJournalModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const activities = button.dataset.activities;
            const studentName = button.dataset.studentName;
            const journalDate = button.dataset.journalDate;

            viewJournalModal.querySelector('#modal_student_name').textContent = studentName;
            viewJournalModal.querySelector('#modal_journal_date').textContent = journalDate;
            viewJournalModal.querySelector('#journal_activities_content').textContent = activities;
        });
    }

    let map;
    let markers = [];
    const viewLocationModal = document.getElementById('viewLocationModal');
    if (viewLocationModal) {
        viewLocationModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (!button) return;

            const latIn = parseFloat(button.dataset.latIn);
            const lngIn = parseFloat(button.dataset.lngIn);
            const latOut = button.dataset.latOut ? parseFloat(button.dataset.latOut) : null;
            const lngOut = button.dataset.lngOut ? parseFloat(button.dataset.lngOut) : null;
            const latCompany = button.dataset.companyLat ? parseFloat(button.dataset.companyLat) : null;
            const lngCompany = button.dataset.companyLng ? parseFloat(button.dataset.companyLng) : null;
            const studentName = button.dataset.studentName;

            viewLocationModal.querySelector('#location_student_name').textContent = studentName;

            setTimeout(() => {
                if (!map) {
                    map = L.map('map').setView([latIn, lngIn], 15);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                    }).addTo(map);
                }

                markers.forEach(marker => map.removeLayer(marker));
                markers = [];

                const checkinMarker = L.marker([latIn, lngIn]).addTo(map)
                    .bindPopup(`Lokasi Check-in: ${studentName}`);
                markers.push(checkinMarker);

                if (latOut && lngOut) {
                    const checkoutMarker = L.marker([latOut, lngOut], {
                        icon: L.icon({
                            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png',
                            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png'
                        })
                    }).addTo(map).bindPopup(`Lokasi Check-out: ${studentName}`);
                    markers.push(checkoutMarker);
                }

                if (latCompany && lngCompany) {
                    const companyMarker = L.marker([latCompany, lngCompany], {
                        icon: L.icon({
                            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-green.png',
                            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png'
                        })
                    }).addTo(map).bindPopup('Lokasi DUDIKA');
                    markers.push(companyMarker);
                }

                if (markers.length > 1) {
                    const group = new L.featureGroup(markers);
                    map.fitBounds(group.getBounds().pad(0.5));
                } else if (markers.length === 1) {
                    map.setView(markers[0].getLatLng(), 15);
                    markers[0].openPopup();
                }

                // Calculate and display distances
                const distanceInfo = document.getElementById('distance-info');
                let distanceHTML = '';
                if (latCompany && lngCompany) {
                    const companyLatLng = L.latLng(latCompany, lngCompany);
                    const checkinLatLng = L.latLng(latIn, lngIn);
                    const distanceIn = companyLatLng.distanceTo(checkinLatLng);
                    distanceHTML += `<p class="mb-1">Jarak dari DUDIKA ke Lokasi Check-in: <strong>${formatDistance(distanceIn)}</strong></p>`;

                    if (latOut && lngOut) {
                        const checkoutLatLng = L.latLng(latOut, lngOut);
                        const distanceOut = companyLatLng.distanceTo(checkoutLatLng);
                        distanceHTML += `<p class="mb-0">Jarak dari DUDIKA ke Lokasi Check-out: <strong>${formatDistance(distanceOut)}</strong></p>`;
                    }
                }
                distanceInfo.innerHTML = distanceHTML;

                map.invalidateSize();
            }, 500);
        });

        function formatDistance(meters) {
            if (meters < 1000) {
                return `${Math.round(meters)} meter`;
            } else {
                return `${(meters / 1000).toFixed(2)} km`;
            }
        }
    }
});
</script>

<?php
if (in_array($_SESSION['user_role'], ['student', 'teacher', 'instructor'])) {
    echo '</div>';
}
require_once __DIR__ . '/templates/footer.php';
?>