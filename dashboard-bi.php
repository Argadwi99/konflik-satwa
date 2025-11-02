<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Dashboard BI khusus untuk Kepala/Satgas
if ($_SESSION['role'] != 'kepala' && $_SESSION['role'] != 'satgas' && $_SESSION['role'] != 'admin') {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard BI - Executive Summary</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css" />
    <style>
        .bi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .kpi-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .kpi-value {
            font-size: 36px;
            font-weight: bold;
            color: #667eea;
        }
        .kpi-label {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }
        .chart-box {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        .chart-container {
            position: relative;
            height: 350px;
        }
        #mapBI {
            height: 450px;
            border-radius: 10px;
        }
        .loading {
            text-align: center;
            padding: 50px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
            <div class="sidebar-header">
                <h3>🐾 BKSDA Jateng</h3>
                <p><?php echo $_SESSION['nama_lengkap']; ?></p>
                <small style="color: #999;"><?php echo ucfirst($_SESSION['role']); ?></small>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="dashboard.php">📊 Dashboard</a></li>
                <li><a href="dashboard-bi.php" class="active">📈 Dashboard BI</a></li>
                <li><a href="dashboard-analytics.php">📊 Analytics</a></li>
                <li><a href="daftar-laporan.php">📋 Daftar Laporan</a></li>
                <li><a href="peta-konflik.php">🗺️ Peta GIS</a></li>
                <li><a href="laporan-berkala.php">📑 Export Laporan</a></li>
            </ul>

            <div class="sidebar-logout">
                <a href="../logout.php" class="logout-btn" onclick="return confirm('Yakin ingin keluar?')">
                    🚪 Logout
                </a>
            </div>
        </div>

        <div class="main-content">
            <div class="top-bar">
                <h1>📊 Dashboard Business Intelligence</h1>
                <span id="lastUpdate"></span>
            </div>

            <!-- Loading State -->
            <div id="loading" class="loading">
                <p>⏳ Loading data dari API...</p>
            </div>

            <!-- KPI Summary Cards -->
            <div id="kpiCards" class="bi-grid" style="display: none;">
                <!-- Will be populated by API -->
            </div>

            <!-- Charts Grid -->
            <div id="chartsGrid" style="display: none;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <!-- Tren Bulanan -->
                    <div class="chart-box">
                        <h3>📈 Tren Laporan 12 Bulan</h3>
                        <div class="chart-container">
                            <canvas id="chartTren"></canvas>
                        </div>
                    </div>

                    <!-- SLA Performance -->
                    <div class="chart-box">
                        <h3>⏱️ SLA Performance (Target: 3 Hari)</h3>
                        <div class="chart-container">
                            <canvas id="chartSLA"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Hotspot Map -->
                <div class="chart-box">
                    <h3>🗺️ Peta Hotspot Konflik</h3>
                    <div id="mapBI"></div>
                </div>

                <!-- Top Kabupaten & Satwa -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="chart-box">
                        <h3>🏙️ Top 10 Kabupaten</h3>
                        <div class="chart-container">
                            <canvas id="chartKabupaten"></canvas>
                        </div>
                    </div>

                    <div class="chart-box">
                        <h3>🐾 Top 10 Jenis Satwa</h3>
                        <div class="chart-container">
                            <canvas id="chartSatwa"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>
    <script>
        // Base API URL
        const API_BASE = '../api/';

        // Fetch data from API
        async function fetchAPI(endpoint, params = {}) {
            const queryString = new URLSearchParams(params).toString();
            const url = `${API_BASE}${endpoint}?${queryString}`;
            
            try {
                const response = await fetch(url);
                const data = await response.json();
                
                if (data.status === 'error') {
                    throw new Error(data.message);
                }
                
                return data.data;
            } catch (error) {
                console.error('API Error:', error);
                return null;
            }
        }

        // Load KPI Summary
        async function loadKPIs() {
            const data = await fetchAPI('statistik.php', { type: 'summary' });
            
            if (!data) return;

            const kpiHTML = `
                <div class="kpi-card" style="border-left: 4px solid #667eea;">
                    <div class="kpi-value">${data.total_laporan}</div>
                    <div class="kpi-label">Total Laporan</div>
                </div>
                <div class="kpi-card" style="border-left: 4px solid #ffc107;">
                    <div class="kpi-value">${data.baru}</div>
                    <div class="kpi-label">Laporan Baru</div>
                </div>
                <div class="kpi-card" style="border-left: 4px solid #dc3545;">
                    <div class="kpi-value">${data.urgent}</div>
                    <div class="kpi-label">Urgent</div>
                </div>
                <div class="kpi-card" style="border-left: 4px solid #28a745;">
                    <div class="kpi-value">${data.persentase_selesai}%</div>
                    <div class="kpi-label">Persentase Selesai</div>
                </div>
            `;
            
            document.getElementById('kpiCards').innerHTML = kpiHTML;
        }

        // Load Tren Chart
        async function loadTrenChart() {
            const data = await fetchAPI('statistik.php', { type: 'tren' });
            
            if (!data) return;

            const ctx = document.getElementById('chartTren').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.map(d => d.bulan_label),
                    datasets: [{
                        label: 'Total Laporan',
                        data: data.map(d => d.jumlah),
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        fill: true,
                        tension: 0.4
                    }, {
                        label: 'Selesai',
                        data: data.map(d => d.selesai),
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'top' } },
                    scales: { y: { beginAtZero: true } }
                }
            });
        }

        // Load SLA Chart
        async function loadSLAChart() {
            const data = await fetchAPI('statistik.php', { type: 'sla' });
            
            if (!data) return;

            const ctx = document.getElementById('chartSLA').getContext('2d');
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Dalam SLA', 'Lewat SLA'],
                    datasets: [{
                        data: [data.dalam_sla, data.lewat_sla],
                        backgroundColor: ['#28a745', '#dc3545']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' },
                        title: {
                            display: true,
                            text: `Rata-rata: ${Math.round(data.rata_waktu_penanganan)} hari | ${data.persentase_sla}% Memenuhi SLA`
                        }
                    }
                }
            });
        }

        // Load Kabupaten Chart
        async function loadKabupatenChart() {
            const data = await fetchAPI('statistik.php', { type: 'kabupaten' });
            
            if (!data) return;

            const top10 = data.slice(0, 10);
            const ctx = document.getElementById('chartKabupaten').getContext('2d');
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: top10.map(d => d.kabupaten),
                    datasets: [{
                        label: 'Jumlah Laporan',
                        data: top10.map(d => d.total),
                        backgroundColor: '#667eea'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true } }
                }
            });
        }

        // Load Satwa Chart
        async function loadSatwaChart() {
            const data = await fetchAPI('statistik.php', { type: 'satwa' });
            
            if (!data) return;

            const ctx = document.getElementById('chartSatwa').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.map(d => d.nama_satwa),
                    datasets: [{
                        label: 'Jumlah Konflik',
                        data: data.map(d => d.total),
                        backgroundColor: '#764ba2'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    plugins: { legend: { display: false } },
                    scales: { x: { beginAtZero: true } }
                }
            });
        }

        // Load Map
        async function loadMap() {
            const data = await fetchAPI('map-data.php', { type: 'hotspot' });
            
            if (!data || !data.markers) return;

            const map = L.map('mapBI').setView([-7.150975, 110.140259], 8);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap'
            }).addTo(map);

            // Add markers
            data.markers.forEach(item => {
                const icon = L.icon({
                    iconUrl: `https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-${
                        item.prioritas === 'urgent' ? 'red' :
                        item.prioritas === 'tinggi' ? 'orange' :
                        item.prioritas === 'sedang' ? 'yellow' : 'green'
                    }.png`,
                    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
                    iconSize: [25, 41],
                    iconAnchor: [12, 41]
                });

                L.marker([item.lokasi.latitude, item.lokasi.longitude], { icon })
                    .bindPopup(`
                        <strong>${item.nomor_registrasi}</strong><br>
                        ${item.lokasi.kabupaten}, ${item.lokasi.kecamatan}<br>
                        Satwa: ${item.satwa}<br>
                        Status: ${item.status}
                    `)
                    .addTo(map);
            });
        }

        // Initialize Dashboard
        async function initDashboard() {
            document.getElementById('loading').style.display = 'block';
            
            await Promise.all([
                loadKPIs(),
                loadTrenChart(),
                loadSLAChart(),
                loadKabupatenChart(),
                loadSatwaChart(),
                loadMap()
            ]);
            
            document.getElementById('loading').style.display = 'none';
            document.getElementById('kpiCards').style.display = 'grid';
            document.getElementById('chartsGrid').style.display = 'block';
            document.getElementById('lastUpdate').textContent = 'Update: ' + new Date().toLocaleString('id-ID');
        }

        // Run on page load
        document.addEventListener('DOMContentLoaded', initDashboard);

        // Auto refresh every 5 minutes
        setInterval(initDashboard, 5 * 60 * 1000);
    </script>
</body>
</html>