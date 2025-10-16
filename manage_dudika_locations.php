<?php
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/sidebar.php';

// Proteksi halaman
if ($_SESSION['user_role'] !== 'waka_humas') {
    header("Location: login.php");
    exit;
}

$page_title = "Penitikan Lokasi DUDIKA";

try {
    // Ambil semua data perusahaan
    $stmt = $pdo->query("SELECT id, name, latitude, longitude FROM companies ORDER BY name ASC");
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800"><?php echo $page_title; ?></h1>

    <?php if (isset($_SESSION['flash_message'])): ?>
    <div class="alert alert-<?php echo $_SESSION['flash_message']['type']; ?> alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['flash_message']['message']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-map-marked-alt me-2"></i>Daftar Lokasi DUDIKA</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Nama DUDIKA</th>
                            <th>Latitude</th>
                            <th>Longitude</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($companies as $company): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($company['name']); ?></td>
                            <td><?php echo htmlspecialchars($company['latitude'] ?? 'Belum diatur'); ?></td>
                            <td><?php echo htmlspecialchars($company['longitude'] ?? 'Belum diatur'); ?></td>
                            <td>
                                <button class="btn btn-warning btn-sm edit-location-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#locationModal"
                                        data-company-id="<?php echo $company['id']; ?>"
                                        data-company-name="<?php echo htmlspecialchars($company['name']); ?>"
                                        data-latitude="<?php echo htmlspecialchars($company['latitude'] ?? ''); ?>"
                                        data-longitude="<?php echo htmlspecialchars($company['longitude'] ?? ''); ?>">
                                    <i class="fas fa-edit"></i> Edit Lokasi
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit Lokasi -->
<div class="modal fade" id="locationModal" tabindex="-1" aria-labelledby="locationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="locationModalLabel">Edit Lokasi DUDIKA</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="locationForm" action="core/location_actions.php" method="POST">
                    <input type="hidden" name="action" value="update_location">
                    <input type="hidden" name="company_id" id="company_id_input">

                    <h6 id="companyName" class="mb-3"></h6>

                    <div id="map" style="height: 400px; width: 100%;" class="mb-3"></div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="latitude" class="form-label">Latitude</label>
                            <input type="text" class="form-control" id="latitude" name="latitude" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="longitude" class="form-label">Longitude</label>
                            <input type="text" class="form-control" id="longitude" name="longitude" required>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-secondary" id="useGpsBtn"><i class="fas fa-map-marker-alt me-2"></i>Gunakan GPS Saya</button>
                        <button type="submit" class="btn btn-primary">Simpan Lokasi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    let map;
    let marker;
    const locationModal = document.getElementById('locationModal');
    const latInput = document.getElementById('latitude');
    const lonInput = document.getElementById('longitude');

    locationModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const companyId = button.dataset.companyId;
        const companyName = button.dataset.companyName;
        let latitude = button.dataset.latitude;
        let longitude = button.dataset.longitude;

        document.getElementById('company_id_input').value = companyId;
        document.getElementById('companyName').textContent = companyName;
        latInput.value = latitude;
        lonInput.value = longitude;

        const initialCoords = (latitude && longitude) ? [latitude, longitude] : [-6.200000, 106.816666];

        if (!map) {
            map = L.map('map').setView(initialCoords, 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);
        } else {
            map.setView(initialCoords, 13);
        }

        if (latitude && longitude) {
            marker = L.marker(initialCoords).addTo(map);
        }

        map.on('click', function(e) {
            latInput.value = e.latlng.lat;
            lonInput.value = e.latlng.lng;
            if (marker) {
                marker.setLatLng(e.latlng);
            } else {
                marker = L.marker(e.latlng).addTo(map);
            }
        });

        setTimeout(() => map.invalidateSize(), 500);
    });

    document.getElementById('useGpsBtn').addEventListener('click', function() {
        if (navigator.geolocation) {
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mencari...';
            navigator.geolocation.getCurrentPosition((position) => {
                const lat = position.coords.latitude;
                const lon = position.coords.longitude;
                latInput.value = lat;
                lonInput.value = lon;
                map.setView([lat, lon], 16);
                if (marker) {
                    marker.setLatLng([lat, lon]);
                } else {
                    marker = L.marker([lat, lon]).addTo(map);
                }
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-map-marker-alt me-2"></i>Gunakan GPS Saya';
            }, (error) => {
                alert('Gagal mendapatkan lokasi GPS: ' + error.message);
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-map-marker-alt me-2"></i>Gunakan GPS Saya';
            });
        } else {
            alert('Geolocation tidak didukung oleh browser ini.');
        }
    });
});
</script>
<?php
require_once __DIR__ . '/templates/footer.php';
?>