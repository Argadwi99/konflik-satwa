<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

require_once('../config/database.php');

// Statistik umum
$stats = [
    'total' => 0,
    'baru' => 0,
    'proses' => 0,
    'selesai' => 0,
    'urgent' => 0
];

$query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'baru' THEN 1 ELSE 0 END) as baru,
    SUM(CASE WHEN status = 'proses' THEN 1 ELSE 0 END) as proses,
    SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai,
    SUM(CASE WHEN prioritas = 'urgent' THEN 1 ELSE 0 END) as urgent
    FROM laporan_konflik";
$result = mysqli_query($conn, $query);
if ($row = mysqli_fetch_assoc($result)) {
    $stats = $row;
}

// Data untuk chart tren bulanan (6 bulan terakhir)
$query_tren = "SELECT 
    DATE_FORMAT(tanggal_laporan, '%Y-%m') as bulan,
    DATE_FORMAT(tanggal_laporan, '%b %Y') as bulan_label,
    COUNT(*) as jumlah
    FROM laporan_konflik
    WHERE tanggal_laporan >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY bulan
    ORDER BY bulan ASC";
$tren_result = mysqli_query($conn, $query_tren);
$tren_data = [];
while ($row = mysqli_fetch_assoc($tren_result)) {
    $tren_data[] = $row;
}

// Data untuk chart per kabupaten (Top 10)
$query_kab = "SELECT kabupaten, COUNT(*) as total
    FROM laporan_konflik
    GROUP BY kabupaten
    ORDER BY total DESC
    LIMIT 10";
$kab_result = mysqli_query($conn, $query_kab);
$kab_data = [];
while ($row = mysqli_fetch_assoc($kab_result)) {
    $kab_data[] = $row;
}

// Data untuk chart jenis satwa (Top 5)
$query_satwa = "SELECT js.nama_satwa, COUNT(*) as total
    FROM laporan_konflik l
    LEFT JOIN jenis_satwa js ON l.jenis_satwa_id = js.id
    GROUP BY l.jenis_satwa_id
    ORDER BY total DESC
    LIMIT 5";
$satwa_result = mysqli_query($conn, $query_satwa);
$satwa_data = [];
while ($row = mysqli_fetch_assoc($satwa_result)) {
    $satwa_data[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Analytics - Sistem E-Reporting</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .chart-container {
            position: relative;
            height: 350px;
            margin-bottom: 30px;
        }
        .chart-box {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        .chart-box h3 {
            margin-bottom: 20px;
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
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
                <h1>📈 Dashboard Analytics</h1>
                <span><?php echo date('d F Y'); ?></span>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card blue">
                    <h3>Total Laporan</h3>
                    <div class="number"><?php echo $stats['total']; ?></div>
                </div>
                <div class="stat-card yellow">
                    <h3>Laporan Baru</h3>
                    <div class="number"><?php echo $stats['baru']; ?></div>
                </div>
                <div class="stat-card red">
                    <h3>Dalam Proses</h3>
                    <div class="number"><?php echo $stats['proses']; ?></div>
                </div>
                <div class="stat-card green">
                    <h3>Selesai</h3>
                    <div class="number"><?php echo $stats['selesai']; ?></div>
                </div>
            </div>

            <!-- Chart Tren Bulanan -->
            <div class="chart-box">
                <h3>📊 Tren Laporan 6 Bulan Terakhir</h3>
                <div class="chart-container">
                    <canvas id="chartTren"></canvas>
                </div>
            </div>

            <!-- Chart Per Kabupaten & Jenis Satwa -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px;">
                <div class="chart-box">
                    <h3>🗺️ Top 10 Kabupaten Konflik Tertinggi</h3>
                    <div class="chart-container">
                        <canvas id="chartKabupaten"></canvas>
                    </div>
                </div>

                <div class="chart-box">
                    <h3>🐾 Top 5 Jenis Satwa Konflik</h3>
                    <div class="chart-container">
                        <canvas id="chartSatwa"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Data dari PHP
        const trenData = <?php echo json_encode($tren_data); ?>;
        const kabData = <?php echo json_encode($kab_data); ?>;
        const satwaData = <?php echo json_encode($satwa_data); ?>;

        // Chart Tren Bulanan
        const ctxTren = document.getElementById('chartTren').getContext('2d');
        new Chart(ctxTren, {
            type: 'line',
            data: {
                labels: trenData.map(d => d.bulan_label),
                datasets: [{
                    label: 'Jumlah Laporan',
                    data: trenData.map(d => d.jumlah),
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: true }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        // Chart Per Kabupaten
        const ctxKab = document.getElementById('chartKabupaten').getContext('2d');
        new Chart(ctxKab, {
            type: 'bar',
            data: {
                labels: kabData.map(d => d.kabupaten),
                datasets: [{
                    label: 'Jumlah Laporan',
                    data: kabData.map(d => d.total),
                    backgroundColor: '#667eea'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        // Chart Jenis Satwa
        const ctxSatwa = document.getElementById('chartSatwa').getContext('2d');
        new Chart(ctxSatwa, {
            type: 'doughnut',
            data: {
                labels: satwaData.map(d => d.nama_satwa),
                datasets: [{
                    data: satwaData.map(d => d.total),
                    backgroundColor: [
                        '#667eea',
                        '#764ba2',
                        '#f093fb',
                        '#4facfe',
                        '#43e97b'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    </script>
</body>
</html>