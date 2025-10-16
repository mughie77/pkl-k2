document.addEventListener('DOMContentLoaded', function() {
    let map;
    let marker;
    const locationModal = document.getElementById('locationModal');

    if (locationModal) {
        locationModal.addEventListener('show.bs.modal', function (event) {
            if (!map) {
                map = L.map('map').setView([-6.200000, 106.816666], 13); // Default view
                L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
                    maxZoom: 19
                }).addTo(map);
            }

            const button = event.relatedTarget;
            const journalId = button.dataset.journalId;
            const type = button.dataset.locationType;

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

                    setTimeout(function() {
                        map.invalidateSize();
                    }, 500);
                },
                error: function() {
                     $('#map').html('<div class="alert alert-danger">Gagal memuat data lokasi.</div>');
                }
            });
        });
    }
});