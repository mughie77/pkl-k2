<?php
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/sidebar.php';

// Proteksi halaman
if ($_SESSION['user_role'] !== 'instructor') {
    header("Location: login.php");
    exit;
}

$instructor_id = $_SESSION['user_id'];

try {
    // Ambil jurnal yang menunggu verifikasi dari siswa bimbingan instruktur ini
    $stmt = $pdo->prepare("
        SELECT
            j.id, j.journal_date, j.check_in_time, j.check_out_time, j.activities,
            j.check_in_latitude, j.check_in_longitude, j.check_out_latitude, j.check_out_longitude,
            s.name as student_name
        FROM internship_journals j
        JOIN internship_mappings m ON j.student_id = m.student_id
        JOIN students s ON j.student_id = s.id
        WHERE m.instructor_id = :instructor_id AND j.status = 'Pending'
        ORDER BY j.journal_date DESC
    ");
    $stmt->execute([':instructor_id' => $instructor_id]);
    $pending_journals = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error: Could not fetch journals data. " . $e->getMessage());
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Verifikasi Jurnal Harian</h1>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['flash_message']['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['flash_message']['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Jurnal Menunggu Persetujuan</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Nama Siswa</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($pending_journals) > 0): ?>
                            <?php foreach ($pending_journals as $journal): ?>
                                <tr>
                                    <td><?php echo date('d M Y', strtotime($journal['journal_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($journal['student_name']); ?></td>
                                    <td><?php echo $journal['check_in_time'] ? date('H:i', strtotime($journal['check_in_time'])) : '-'; ?></td>
                                    <td><?php echo $journal['check_out_time'] ? date('H:i', strtotime($journal['check_out_time'])) : '-'; ?></td>
                                    <td>
                                        <button class="btn btn-info btn-sm view-btn"
                                                data-id="<?php echo $journal['id']; ?>"
                                                data-student="<?php echo htmlspecialchars($journal['student_name']); ?>"
                                                data-date="<?php echo date('d M Y', strtotime($journal['journal_date'])); ?>"
                                                data-checkin="<?php echo $journal['check_in_time'] ? date('H:i', strtotime($journal['check_in_time'])) : 'N/A'; ?>"
                                                data-checkout="<?php echo $journal['check_out_time'] ? date('H:i', strtotime($journal['check_out_time'])) : 'N/A'; ?>"
                                                data-activities="<?php echo htmlspecialchars($journal['activities']); ?>"
                                                data-bs-toggle="modal" data-bs-target="#journalModal">
                                            <i class="fas fa-eye me-1"></i> Detail
                                        </button>
                                         <?php if (!empty($journal['check_in_latitude']) && !empty($journal['check_in_longitude'])): ?>
                                            <button class="btn btn-success btn-sm view-location-btn"
                                                    data-journal-id="<?php echo $journal['id']; ?>"
                                                    data-location-type="check_in"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#locationModal"
                                                    title="Lihat Lokasi Check-in">
                                                <i class="fas fa-map-marker-alt"></i> In
                                            </button>
                                        <?php endif; ?>

                                        <?php if (!empty($journal['check_out_latitude']) && !empty($journal['check_out_longitude'])): ?>
                                            <button class="btn btn-danger btn-sm view-location-btn"
                                                    data-journal-id="<?php echo $journal['id']; ?>"
                                                    data-location-type="check_out"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#locationModal"
                                                    title="Lihat Lokasi Check-out">
                                                <i class="fas fa-map-marker-alt"></i> Out
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">Tidak ada jurnal yang menunggu verifikasi saat ini.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk Detail dan Verifikasi Jurnal -->
<div class="modal fade" id="journalModal" tabindex="-1" aria-labelledby="journalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="journalModalLabel">Detail Jurnal Harian</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6 class="mb-3">Siswa: <span id="modal_student_name" class="fw-normal"></span></h6>
                <p><strong>Tanggal:</strong> <span id="modal_journal_date"></span></p>
                <p><strong>Waktu:</strong> <span id="modal_check_in_time"></span> - <span id="modal_check_out_time"></span></p>
                <hr>
                <h6><strong>Deskripsi Kegiatan:</strong></h6>
                <p id="modal_activities" style="white-space: pre-wrap;"></p>
            </div>
            <div class="modal-footer">
                <form action="core/journal_actions.php" method="POST" class="d-inline">
                    <input type="hidden" name="journal_id" id="modal_journal_id">
                    <input type="hidden" name="action" value="reject">
                    <button type="submit" class="btn btn-danger"><i class="fas fa-times me-1"></i> Reject</button>
                </form>
                <form action="core/journal_actions.php" method="POST" class="d-inline">
                    <input type="hidden" name="journal_id" id="modal_journal_id_approve">
                    <input type="hidden" name="action" value="approve">
                    <button type="submit" class="btn btn-success"><i class="fas fa-check me-1"></i> Approve</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk Peta Lokasi -->
<div class="modal fade" id="locationModal" tabindex="-1" aria-labelledby="locationModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="locationModalLabel">Lokasi Absen Siswa</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="map" style="height: 450px;"></div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const journalModal = document.getElementById('journalModal');
    journalModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;

        document.getElementById('modal_student_name').textContent = button.dataset.student;
        document.getElementById('modal_journal_date').textContent = button.dataset.date;
        document.getElementById('modal_check_in_time').textContent = button.dataset.checkin;
        document.getElementById('modal_check_out_time').textContent = button.dataset.checkout;
        document.getElementById('modal_activities').textContent = button.dataset.activities;
        document.getElementById('modal_journal_id').value = button.dataset.id;
        document.getElementById('modal_journal_id_approve').value = button.dataset.id;
    });

    // Handle location modal
    let map;
    let marker;
    const locationModal = document.getElementById('locationModal');

    // Inisialisasi peta saat modal pertama kali akan ditampilkan
    locationModal.addEventListener('show.bs.modal', function (event) {
        if (!map) {
            map = L.map('map').setView([-6.200000, 106.816666], 13); // Default view
            L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
                maxZoom: 19
            }).addTo(map);
        }

        // Ambil data dan perbarui peta
        const button = event.relatedTarget;
        const journalId = button.dataset.journalId;
        const type = button.dataset.locationType;

        $('#map').html('<div class="d-flex justify-content-center align-items-center h-100"><i class="fas fa-spinner fa-spin fa-3x"></i></div>');


        $.ajax({
            url: `get_location_map.php?journal_id=${journalId}&type=${type}`,
            success: function(data) {
                const locationData = data;
                if(locationData.error) {
                    $('#map').html(`<div class="alert alert-danger">${locationData.error}</div>`);
                    return;
                }

                $('#locationModalLabel').text(`Lokasi ${locationData.type} - ${locationData.student_name} (${locationData.date})`);

                const latLon = [locationData.lat, locationData.lon];
                map.setView(latLon, 16);

                if (marker) {
                    map.removeLayer(marker);
                }
                marker = L.marker(latLon).addTo(map)
                    .bindPopup(`<b>${locationData.type}</b><br>Pukul: ${locationData.time}`)
                    .openPopup();

                // Pastikan ukuran peta benar setelah modal ditampilkan
                setTimeout(function() {
                    map.invalidateSize();
                }, 500);
            },
            error: function() {
                 $('#map').html('<div class="alert alert-danger">Gagal memuat data lokasi.</div>');
            }
        });
    });
});
</script>

<?php
if (in_array($_SESSION['user_role'], ['student', 'teacher', 'instructor'])) {
    echo '</div>';
}
require_once __DIR__ . '/templates/footer.php';
?>