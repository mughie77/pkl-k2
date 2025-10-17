<?php
require_once __DIR__ . '/templates/header.php';

// Proteksi halaman
if ($_SESSION['user_role'] !== 'teacher') {
    header("Location: login.php");
    exit;
}

$teacher_id = $_SESSION['user_id'];

// Ambil data perusahaan yang relevan dengan siswa bimbingan guru
try {
    $stmt = $pdo->prepare("
        SELECT DISTINCT c.id, c.name, c.address, c.latitude, c.longitude
        FROM companies c
        JOIN instructors i ON c.id = i.company_id
        JOIN internship_mappings m ON i.id = m.instructor_id
        WHERE m.teacher_id = :teacher_id
        ORDER BY c.name ASC
    ");
    $stmt->execute([':teacher_id' => $teacher_id]);
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error: Could not fetch companies data. " . $e->getMessage());
}
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    #map { height: 400px; }
</style>

<div class="container-fluid student-dashboard">
    <h1 class="h3 mb-4 text-gray-800">Penitikan Lokasi DUDIKA</h1>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['flash_message']['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['flash_message']['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Daftar DUDIKA Siswa Bimbingan Anda</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Nama DUDIKA</th>
                            <th>Alamat</th>
                            <th>Status Lokasi</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($companies) > 0): ?>
                            <?php foreach ($companies as $company): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($company['name']); ?></td>
                                    <td><?php echo htmlspecialchars($company['address']); ?></td>
                                    <td>
                                        <?php if (!empty($company['latitude']) && !empty($company['longitude'])): ?>
                                            <span class="badge bg-success">Sudah Di-set</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Belum Di-set</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-info btn-sm set-location-btn"
                                                data-company-id="<?php echo $company['id']; ?>"
                                                data-company-name="<?php echo htmlspecialchars($company['name']); ?>"
                                                data-lat="<?php echo $company['latitude'] ?? ''; ?>"
                                                data-lon="<?php echo $company['longitude'] ?? ''; ?>"
                                                data-bs-toggle="modal"
                                                data-bs-target="#locationModal">
                                            <i class="fas fa-map-marker-alt me-2"></i> Set Lokasi
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center">Tidak ada DUDIKA yang terhubung dengan siswa bimbingan Anda.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk Set Lokasi -->
<div class="modal fade" id="locationModal" tabindex="-1" aria-labelledby="locationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="locationModalLabel">Set Lokasi untuk <span id="locationCompanyName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <p class="mb-0">Klik pada peta untuk memilih lokasi, atau gunakan lokasi Anda saat ini.</p>
                    <button type="button" id="useCurrentLocationBtn" class="btn btn-secondary btn-sm">
                        <i class="fas fa-location-arrow me-2"></i>Gunakan Lokasi Saya
                    </button>
                </div>
                <div id="map"></div>
                <form id="locationForm" action="core/company_actions.php" method="POST" class="mt-3">
                    <input type="hidden" name="company_id" id="loc_company_id">
                    <input type="hidden" name="action" value="set_location_teacher">
                    <div class="row">
                        <div class="col-md-6">
                            <label for="latitude" class="form-label">Latitude</label>
                            <input type="text" class="form-control" id="latitude" name="latitude" readonly required>
                        </div>
                        <div class="col-md-6">
                            <label for="longitude" class="form-label">Longitude</label>
                            <input type="text" class="form-control" id="longitude" name="longitude" readonly required>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="saveLocationBtn">Simpan Lokasi</button>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const locationModal = document.getElementById('locationModal');
    let map = null;
    let marker = null;

    function bindMarkerEvents() {
        if (!marker) return;
        marker.on('dragend', function(e) {
            updateFormFields(e.target.getLatLng());
        });
    }

    function updateFormFields(latlng) {
        document.getElementById('latitude').value = latlng.lat.toFixed(8);
        document.getElementById('longitude').value = latlng.lng.toFixed(8);
    }

    locationModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        document.getElementById('locationCompanyName').textContent = button.dataset.companyName;
        document.getElementById('loc_company_id').value = button.dataset.companyId;
        document.getElementById('latitude').value = button.dataset.lat;
        document.getElementById('longitude').value = button.dataset.lon;

        const defaultCenter = [-2.548926, 118.0148634];
        const initialZoom = 5;
        let currentCenter = (button.dataset.lat && button.dataset.lon) ? [button.dataset.lat, button.dataset.lon] : defaultCenter;
        let currentZoom = (button.dataset.lat && button.dataset.lon) ? 18 : initialZoom;

        if (!map) {
            map = L.map('map').setView(currentCenter, currentZoom);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
        } else {
            map.setView(currentCenter, currentZoom);
        }

        if (marker) {
            map.removeLayer(marker);
            marker = null;
        }

        if (button.dataset.lat && button.dataset.lon) {
            marker = L.marker(currentCenter, { draggable: true }).addTo(map);
            bindMarkerEvents();
        }

        map.on('click', function(e) {
            if (marker) {
                marker.setLatLng(e.latlng);
            } else {
                marker = L.marker(e.latlng, { draggable: true }).addTo(map);
                bindMarkerEvents();
            }
            updateFormFields(e.latlng);
        });
    });

    locationModal.addEventListener('shown.bs.modal', () => setTimeout(() => map?.invalidateSize(), 10));

    document.getElementById('saveLocationBtn').addEventListener('click', () => document.getElementById('locationForm').submit());

    document.getElementById('useCurrentLocationBtn').addEventListener('click', function() {
        if (!navigator.geolocation) return alert('Geolocation tidak didukung.');
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Mencari...';
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const latlng = { lat: position.coords.latitude, lng: position.coords.longitude };
                map.setView(latlng, 18);
                if (marker) {
                    marker.setLatLng(latlng);
                } else {
                    marker = L.marker(latlng, { draggable: true }).addTo(map);
                    bindMarkerEvents();
                }
                updateFormFields(latlng);
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-location-arrow me-2"></i>Gunakan Lokasi Saya';
            },
            function() {
                alert('Gagal mendapatkan lokasi Anda.');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-location-arrow me-2"></i>Gunakan Lokasi Saya';
            }
        );
    });
});
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>