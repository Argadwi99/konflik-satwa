/**
 * MAP.JS - Integrasi Peta GIS dengan Leaflet
 * Untuk menampilkan lokasi konflik satwa di peta interaktif
 */

// Inisialisasi peta
let map;
let markers = [];
let markerCluster;

// Koordinat tengah Jawa Tengah
const CENTER_JATENG = [-7.150975, 110.140259];

/**
 * Inisialisasi peta dasar
 */
function initMap(elementId = 'map', center = CENTER_JATENG, zoom = 8) {
    // Buat peta
    map = L.map(elementId).setView(center, zoom);
    
    // Tambah tile layer (OpenStreetMap)
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(map);
    
    // Tambah kontrol skala
    L.control.scale({ imperial: false, metric: true }).addTo(map);
    
    return map;
}

/**
 * Tambah marker ke peta
 */
function addMarker(lat, lng, popupContent, iconType = 'default') {
    if (!lat || !lng) return null;
    
    // Icon berdasarkan status/prioritas
    const icons = {
        'urgent': L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        }),
        'tinggi': L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-orange.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        }),
        'sedang': L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-yellow.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        }),
        'rendah': L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        }),
        'default': L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        })
    };
    
    const marker = L.marker([lat, lng], {
        icon: icons[iconType] || icons['default']
    }).addTo(map);
    
    if (popupContent) {
        marker.bindPopup(popupContent);
    }
    
    markers.push(marker);
    return marker;
}

/**
 * Hapus semua marker
 */
function clearMarkers() {
    markers.forEach(marker => {
        map.removeLayer(marker);
    });
    markers = [];
}

/**
 * Fit map ke semua marker
 */
function fitBounds() {
    if (markers.length > 0) {
        const group = new L.featureGroup(markers);
        map.fitBounds(group.getBounds().pad(0.1));
    }
}

/**
 * Tambah circle/radius area
 */
function addCircle(lat, lng, radius = 1000, color = '#ff0000') {
    const circle = L.circle([lat, lng], {
        color: color,
        fillColor: color,
        fillOpacity: 0.2,
        radius: radius
    }).addTo(map);
    
    return circle;
}

/**
 * Geocoding: ambil koordinat dari nama lokasi
 */
async function geocodeLocation(kabupaten, kecamatan, desa) {
    const query = `${desa}, ${kecamatan}, ${kabupaten}, Jawa Tengah, Indonesia`;
    const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`;
    
    try {
        const response = await fetch(url);
        const data = await response.json();
        
        if (data && data.length > 0) {
            return {
                lat: parseFloat(data[0].lat),
                lng: parseFloat(data[0].lon)
            };
        }
    } catch (error) {
        console.error('Geocoding error:', error);
    }
    
    return null;
}

/**
 * Click peta untuk ambil koordinat
 */
function enableMapClick(callback) {
    map.on('click', function(e) {
        const lat = e.latlng.lat.toFixed(6);
        const lng = e.latlng.lng.toFixed(6);
        
        if (callback) {
            callback(lat, lng);
        }
        
        // Tampilkan marker sementara
        const tempMarker = L.marker(e.latlng).addTo(map);
        tempMarker.bindPopup(`Koordinat: ${lat}, ${lng}`).openPopup();
        
        // Hapus marker lama jika ada
        if (window.tempMarker) {
            map.removeLayer(window.tempMarker);
        }
        window.tempMarker = tempMarker;
    });
}

/**
 * Heatmap untuk area rawan konflik
 */
function createHeatmap(data) {
    // data format: [[lat, lng, intensity], ...]
    if (typeof L.heatLayer !== 'undefined') {
        const heat = L.heatLayer(data, {
            radius: 25,
            blur: 15,
            maxZoom: 17
        }).addTo(map);
        return heat;
    } else {
        console.warn('Leaflet.heat plugin not loaded');
    }
}

/**
 * Legend untuk peta
 */
function addLegend() {
    const legend = L.control({ position: 'bottomright' });
    
    legend.onAdd = function(map) {
        const div = L.DomUtil.create('div', 'map-legend');
        div.innerHTML = `
            <div style="background: white; padding: 10px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">
                <strong>Prioritas Konflik</strong><br>
                <i style="background: #d63031; width: 12px; height: 12px; display: inline-block; margin-right: 5px;"></i> Urgent<br>
                <i style="background: #e17055; width: 12px; height: 12px; display: inline-block; margin-right: 5px;"></i> Tinggi<br>
                <i style="background: #fdcb6e; width: 12px; height: 12px; display: inline-block; margin-right: 5px;"></i> Sedang<br>
                <i style="background: #00b894; width: 12px; height: 12px; display: inline-block; margin-right: 5px;"></i> Rendah
            </div>
        `;
        return div;
    };
    
    legend.addTo(map);
}

/**
 * Export peta sebagai gambar (screenshot)
 */
function exportMapImage() {
    // Gunakan library leaflet-image jika diperlukan
    if (typeof leafletImage !== 'undefined') {
        leafletImage(map, function(err, canvas) {
            const img = document.createElement('img');
            const dimensions = map.getSize();
            img.width = dimensions.x;
            img.height = dimensions.y;
            img.src = canvas.toDataURL();
            
            // Download
            const link = document.createElement('a');
            link.download = 'peta-konflik-satwa.png';
            link.href = img.src;
            link.click();
        });
    } else {
        alert('Fitur export peta memerlukan plugin tambahan');
    }
}