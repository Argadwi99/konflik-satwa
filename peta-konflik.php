<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

require_once('../config/database.php');

// Ambil semua laporan dengan koordinat
$query = "SELECT l.*, js.nama_satwa
    FROM laporan_konflik l
    LEFT JOIN jenis_satwa js ON l.jenis_satwa_id = js.id
    WHERE (l.latitude IS NOT NULL AND l.longitude IS NOT NULL)
    OR (l.kabupaten IS NOT NULL)
    ORDER BY l.created_at DESC";
$result = mysqli_query($conn, $query);

// Siapkan data untuk peta
$markers_data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $markers_data[] = $row;
}

// Statistik per kabupaten
$query_stats = "SELECT kabupaten, COUNT(*) as total, 
    SUM(CASE WHEN status = 'baru' THEN 1 ELSE 0 END) as baru,
    SUM(CASE WHEN prioritas = 'urgent' THEN 1 ELSE 0 END) as urgent
    FROM laporan_konflik 
    GROUP BY kabupaten 
    ORDER BY total DESC 
    LIMIT 10";
$stats_result = mysqli_query($conn, $query_stats);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peta Konflik Satwa - GIS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css" />
    
    <style>
        #map {
            height: 600px;
            width: 100%;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .map-controls {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .map-controls button {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }
        .stats-kabupaten {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .stats-kabupaten-item {
            background: white;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        .stats-kabupaten-item h4 {
            margin-bottom: 8px;
            color: #333;
        }
        .stats-kabupaten-item .numbers {
            display: flex;
            gap: 15px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
       <!-- SIDEBAR - Copy struktur ini ke SEMUA file di folder pages/ -->
<div class="sidebar">
    <div class="sidebar-header">
        <h3>🐾 BKSDA Jateng</h3>
        <p><?php echo $_SESSION['nama_lengkap']; ?></p>
        <small style="color: #999;"><?php echo ucfirst($_SESSION['role']); ?></small>
    </div>
    
    <ul class="sidebar-menu">
        <li><a href="dashboard.php">📊 Dashboard</a></li>
        <li><a href="dashboard-analytics.php">📈 Analytics</a></li>
        <li><a href="laporan-baru.php">➕ Laporan Baru</a></li>
        <li><a href="daftar-laporan.php">📋 Daftar Laporan</a></li>
        <li><a href="peta-konflik.php">🗺️ Peta GIS</a></li>
        <li><a href="laporan-berkala.php">📑 Export Laporan</a></li>
    </ul>

    <!-- FIX: Logout button dengan class baru -->
    <div class="sidebar-logout">
        <a href="../logout.php" class="logout-btn" onclick="return confirm('Yakin ingin keluar?')">
            🚪 Logout
        </a>
    </div>
</div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="top-bar">
                <h1>🗺️ Peta Konflik Satwa (GIS)</h1>
            </div>

            <!-- Kontrol Peta -->
            <div class="content-box">
                <h2>Peta Interaktif Lokasi Konflik</h2>
                
                <div class="map-controls">
                    <button onclick="fitAllMarkers()" style="background: #667eea; color: white;">
                        🎯 Fit Semua Marker
                    </button>
                    <button onclick="filterByPrioritas('urgent')" style="background: #dc3545; color: white;">
                        🔴 Urgent
                    </button>
                    <button onclick="filterByPrioritas('tinggi')" style="background: #fd7e14; color: white;">
                        🟠 Tinggi
                    </button>
                    <button onclick="filterByPrioritas('sedang')" style="background: #ffc107; color: #333;">
                        🟡 Sedang
                    </button>
                    <button onclick="filterByPrioritas('rendah')" style="background: #28a745; color: white;">
                        🟢 Rendah
                    </button>
                    <button onclick="showAllMarkers()" style="background: #6c757d; color: white;">
                        👁️ Tampilkan Semua
                    </button>
                </div>

                <div id="map"></div>

                <div style="margin-top: 15px; padding: 15px; background: #e7f3ff; border-radius: 8px; font-size: 13px;">
                    <strong>💡 Tips:</strong>
                    <ul style="margin: 10px 0 0 20px; line-height: 1.8;">
                        <li>Klik marker untuk melihat detail laporan</li>
                        <li>Gunakan scroll mouse untuk zoom in/out</li>
                        <li>Drag peta untuk menggeser area</li>
                        <li>Filter berdasarkan prioritas menggunakan tombol di atas</li>
                    </ul>
                </div>
            </div>

            <!-- Statistik per Kabupaten -->
            <div class="content-box">
                <h2>📊 Top 10 Kabupaten dengan Konflik Tertinggi</h2>
                
                <div class="stats-kabupaten">
                    <?php while ($stat = mysqli_fetch_assoc($stats_result)): ?>
                        <div class="stats-kabupaten-item" onclick="focusKabupaten('<?php echo $stat['kabupaten']; ?>')">
                            <h4><?php echo $stat['kabupaten']; ?></h4>
                            <div class="numbers">
                                <span><strong><?php echo $stat['total']; ?></strong> Total</span>
                                <span style="color: #dc3545;"><strong><?php echo $stat['urgent']; ?></strong> Urgent</span>
                                <span style="color: #ffc107;"><strong><?php echo $stat['baru']; ?></strong> Baru</span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Leaflet JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>
    <script src="../assets/js/map.js"></script>
    <script src="../assets/js/script.js" defer></script>
    
    <script>
        // Data laporan dari PHP
        const laporanData = <?php echo json_encode($markers_data); ?>;
        
        // Koordinat kabupaten di Jawa Tengah (sample)
        const kabupatenCoords = {
            'Cilacap': [-7.7262, 109.0094],
            'Banyumas': [-7.5162, 109.2942],
            'Purbalingga': [-7.3886, 109.3674],
            'Banjarnegara': [-7.3853, 109.6858],
            'Kebumen': [-7.6707, 109.6547],
            'Purworejo': [-7.7193, 110.0081],
            'Wonosobo': [-7.3661, 109.9029],
            'Magelang': [-7.4769, 110.2196],
            'Boyolali': [-7.5302, 110.5960],
            'Klaten': [-7.7055, 110.6060],
            'Sukoharjo': [-7.6829, 110.8337],
            'Wonogiri': [-7.8136, 110.9268],
            'Karanganyar': [-7.6045, 110.9471],
            'Sragen': [-7.4253, 110.9977],
            'Grobogan': [-7.0543, 110.9073],
            'Blora': [-6.9742, 111.4185],
            'Rembang': [-6.7087, 111.3426],
            'Pati': [-6.7557, 111.0381],
            'Kudus': [-6.8047, 110.8405],
            'Jepara': [-6.5886, 110.6688],
            'Demak': [-6.8906, 110.6396],
            'Semarang': [-7.1510, 110.4403],
            'Temanggung': [-7.3149, 110.1709],
            'Kendal': [-6.9267, 110.2037],
            'Batang': [-6.9107, 109.7245],
            'Pekalongan': [-6.8886, 109.6753],
            'Pemalang': [-6.8927, 109.3781],
            'Tegal': [-6.8694, 109.1402],
            'Brebes': [-6.8733, 108.8416]
        };
        
        let allMarkers = [];
        
        // Inisialisasi peta
        document.addEventListener('DOMContentLoaded', function() {
            initMap('map', [-7.150975, 110.140259], 8);
            
            // Tambah legend
            addLegend();
            
            // Load semua marker
            loadMarkers();
        });
        
        function loadMarkers() {
            laporanData.forEach(laporan => {
                let lat = laporan.latitude;
                let lng = laporan.longitude;
                
                // Jika tidak ada koordinat, gunakan koordinat kabupaten
                if (!lat || !lng) {
                    if (kabupatenCoords[laporan.kabupaten]) {
                        lat = kabupatenCoords[laporan.kabupaten][0] + (Math.random() - 0.5) * 0.1;
                        lng = kabupatenCoords[laporan.kabupaten][1] + (Math.random() - 0.5) * 0.1;
                    } else {
                        return; // Skip jika tidak ada koordinat
                    }
                }
                
                // Buat popup content
                const popupContent = `
                    <div style="min-width: 200px;">
                        <h4 style="margin: 0 0 10px 0; color: #667eea;">${laporan.nomor_registrasi}</h4>
                        <table style="width: 100%; font-size: 12px;">
                            <tr>
                                <td><strong>Lokasi:</strong></td>
                                <td>${laporan.kabupaten}, ${laporan.kecamatan}</td>
                            </tr>
                            <tr>
                                <td><strong>Satwa:</strong></td>
                                <td>${laporan.nama_satwa || '-'}</td>
                            </tr>
                            <tr>
                                <td><strong>Tanggal:</strong></td>
                                <td>${new Date(laporan.tanggal_laporan).toLocaleDateString('id-ID')}</td>
                            </tr>
                            <tr>
                                <td><strong>Status:</strong></td>
                                <td><span class="badge ${laporan.status}">${laporan.status}</span></td>
                            </tr>
                            <tr>
                                <td><strong>Prioritas:</strong></td>
                                <td><span class="badge ${laporan.prioritas}">${laporan.prioritas}</span></td>
                            </tr>
                        </table>
                        <a href="detail-laporan.php?id=${laporan.id}" 
                           style="display: inline-block; margin-top: 10px; padding: 5px 15px; 
                                  background: #667eea; color: white; text-decoration: none; 
                                  border-radius: 4px; font-size: 12px;">
                            Lihat Detail →
                        </a>
                    </div>
                `;
                
                // Tambah marker
                const marker = addMarker(lat, lng, popupContent, laporan.prioritas);
                
                // Simpan data untuk filtering
                if (marker) {
                    marker.prioritas = laporan.prioritas;
                    marker.kabupaten = laporan.kabupaten;
                    allMarkers.push(marker);
                }
            });
            
            // Fit bounds ke semua marker
            setTimeout(() => fitBounds(), 500);
        }
        
        function fitAllMarkers() {
            fitBounds();
        }
        
        function filterByPrioritas(prioritas) {
            clearMarkers();
            
            allMarkers.forEach(marker => {
                if (marker.prioritas === prioritas) {
                    marker.addTo(map);
                    markers.push(marker);
                }
            });
            
            fitBounds();
        }
        
        function showAllMarkers() {
            clearMarkers();
            
            allMarkers.forEach(marker => {
                marker.addTo(map);
                markers.push(marker);
            });
            
            fitBounds();
        }
        
        function focusKabupaten(kabupaten) {
            if (kabupatenCoords[kabupaten]) {
                map.setView(kabupatenCoords[kabupaten], 11);
                
                // Filter marker kabupaten tersebut
                clearMarkers();
                allMarkers.forEach(marker => {
                    if (marker.kabupaten === kabupaten) {
                        marker.addTo(map);
                        markers.push(marker);
                    }
                });
            }
        }
    </script>
</body>
</html>