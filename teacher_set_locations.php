<?php
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/sidebar.php';

// Proteksi halaman
if ($_SESSION['user_role'] !== 'teacher') {
    header("Location: login.php");
    exit;
}

$teacher_id = $_SESSION['user_id'];

try {
    // Ambil daftar perusahaan yang terkait dengan siswa bimbingan guru
    $stmt = $pdo->prepare("
        SELECT DISTINCT c.id, c.name, c.latitude, c.longitude
        FROM companies c
        JOIN instructors i ON c.id = i.company_id
        JOIN internship_mappings m ON i.id = m.instructor_id
        WHERE m.teacher_id = :teacher_id
        ORDER BY c.name ASC
    ");
    $stmt->execute([':teacher_id' => $teacher_id]);
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching companies: " . $e->getMessage());
}

?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Set Lokasi DUDIKA</h1>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['flash_message']['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['flash_message']['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Daftar DUDIKA Siswa Bimbingan</h6>
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
                                <td id="lat-<?php echo $company['id']; ?>"><?php echo htmlspecialchars($company['latitude']); ?></td>
                                <td id="lng-<?php echo $company['id']; ?>"><?php echo htmlspecialchars($company['longitude']); ?></td>
                                <td>
                                    <button class="btn btn-warning btn-sm edit-location-btn"
                                            data-id="<?php echo $company['id']; ?>"
                                            data-name="<?php echo htmlspecialchars($company['name']); ?>"
                                            data-lat="<?php echo htmlspecialchars($company['latitude']); ?>"
                                            data-lng="<?php echo htmlspecialchars($company['longitude']); ?>"
                                            data-bs-toggle="modal" data-bs-target="#locationModal">
                                        <i class="fas fa-edit"></i> Set/Update Lokasi
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

<!-- Modal untuk Set/Update Lokasi -->
<div class="modal fade" id="locationModal" tabindex="-1" aria-labelledby="locationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="locationModalLabel">Set Lokasi DUDIKA</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="core/location_actions.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="set_company_location">
                    <input type="hidden" name="company_id" id="company_id">
                    <p>Set lokasi untuk: <strong id="company_name"></strong></p>
                    <div class="mb-3">
                        <label for="latitude" class="form-label">Latitude</label>
                        <input type="text" class="form-control" id="latitude" name="latitude" required>
                    </div>
                    <div class="mb-3">
                        <label for="longitude" class="form-label">Longitude</label>
                        <input type="text" class="form-control" id="longitude" name="longitude" required>
                    </div>
                    <div id="map-picker" style="height: 300px; width: 100%; margin-bottom: 15px;"></div>
                    <button type="button" class="btn btn-sm btn-outline-info" id="get-current-location"><i class="fas fa-map-marker-alt"></i> Gunakan Lokasi Saya</button>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Lokasi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let map, marker;
    const latInput = document.getElementById('latitude');
    const lngInput = document.getElementById('longitude');

    const locationModal = document.getElementById('locationModal');
    locationModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const companyId = button.dataset.id;
        const companyName = button.dataset.name;
        const lat = button.dataset.lat || -7.797068; // Default to a central Indonesian location
        const lng = button.dataset.lng || 110.370529; // Default to Yogyakarta

        document.getElementById('company_id').value = companyId;
        document.getElementById('company_name').textContent = companyName;
        latInput.value = button.dataset.lat;
        lngInput.value = button.dataset.lng;

        setTimeout(() => {
            if (!map) {
                map = L.map('map-picker').setView([lat, lng], 13);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
                marker = L.marker([lat, lng], { draggable: true }).addTo(map);

                // Update inputs on marker drag
                marker.on('dragend', function(e) {
                    const newLatLng = e.target.getLatLng();
                    latInput.value = newLatLng.lat.toFixed(8);
                    lngInput.value = newLatLng.lng.toFixed(8);
                });
            } else {
                map.setView([lat, lng], 13);
                marker.setLatLng([lat, lng]);
            }
            map.invalidateSize();
        }, 400); // Delay to ensure modal is visible
    });

    document.getElementById('get-current-location').addEventListener('click', function() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                const newLat = position.coords.latitude;
                const newLng = position.coords.longitude;
                latInput.value = newLat.toFixed(8);
                lngInput.value = newLng.toFixed(8);
                map.setView([newLat, newLng], 15);
                marker.setLatLng([newLat, newLng]);
            }, () => alert("Could not get your location."));
        } else {
            alert("Geolocation is not supported by this browser.");
        }
    });
});
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
