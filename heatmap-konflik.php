<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

require_once('../config/database.php');

// Ambil data untuk heatmap
$query = "SELECT latitude, longitude, prioritas 
    FROM laporan_konflik 
    WHERE latitude IS NOT NULL AND longitude IS NOT NULL";
$result = mysqli_query($conn, $query);

$heatmap_data = [];
while ($row = mysqli_fetch_assoc($result)) {
    // Intensity berdasarkan prioritas
    $intensity = 0.5;
    switch ($row['prioritas']) {
        case 'urgent': $intensity = 1.0; break;
        case 'tinggi': $intensity = 0.8; break;
        case 'sedang': $intensity = 0.6; break;
        case 'rendah': $intensity = 0.4; break;
    }
    
    $heatmap_data[] = [
        'lat' => (float)$row['latitude'],
        'lng' => (float)$row['longitude'],
        'intensity' => $intensity
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Heatmap Area Rawan Konflik</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css" />
    <style>
        #heatmap { height: 600px; border-radius: 10px; }
    </style>
</head>
<body>
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

        <div class="main-content">
            <div class="top-bar">
                <h1>🔥 Heatmap Area Rawan Konflik</h1>
            </div>

            <div class="content-box">
                <h2>Visualisasi Kepadatan Konflik Satwa</h2>
                <div id="heatmap"></div>
                <p style="margin-top: 15px; color: #666; font-size: 14px;">
                    <strong>Interpretasi:</strong> Area berwarna merah menunjukkan tingkat kerawanan konflik tinggi
                </p>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.heat/0.2.0/leaflet-heat.js"></script>
    <script>
        const heatmapData = <?php echo json_encode($heatmap_data); ?>;
        
        const map = L.map('heatmap').setView([-7.150975, 110.140259], 8);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
        
        // Format data untuk leaflet-heat
        const points = heatmapData.map(d => [d.lat, d.lng, d.intensity]);
        
        L.heatLayer(points, {
            radius: 25,
            blur: 15,
            maxZoom: 17,
            max: 1.0
        }).addTo(map);
    </script>
</body>
</html>