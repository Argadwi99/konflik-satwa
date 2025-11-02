<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

require_once('../config/database.php');

// Ambil statistik
$stats = [
    'total' => 0,
    'baru' => 0,
    'proses' => 0,
    'selesai' => 0
];

$query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'baru' THEN 1 ELSE 0 END) as baru,
    SUM(CASE WHEN status = 'proses' THEN 1 ELSE 0 END) as proses,
    SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai
    FROM laporan_konflik";
$result = mysqli_query($conn, $query);
if ($row = mysqli_fetch_assoc($result)) {
    $stats = $row;
}

// Laporan terbaru
$query_recent = "SELECT l.*, js.nama_satwa, u.nama_lengkap as petugas_nama
    FROM laporan_konflik l
    LEFT JOIN jenis_satwa js ON l.jenis_satwa_id = js.id
    LEFT JOIN users u ON l.petugas_id = u.id
    ORDER BY l.created_at DESC
    LIMIT 10";
$recent_reports = mysqli_query($conn, $query_recent);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistem E-Reporting Konflik Satwa</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="../assets/js/script.js" defer></script>
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
                <h1>Dashboard</h1>
                <div class="user-info">
                    <span><?php echo date('d F Y'); ?></span>
                </div>
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

            <!-- Recent Reports -->
            <div class="content-box">
                <h2>Laporan Terbaru</h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>No. Registrasi</th>
                                <th>Tanggal</th>
                                <th>Lokasi</th>
                                <th>Jenis Satwa</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($recent_reports) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($recent_reports)): ?>
                                    <tr>
                                        <td><?php echo $row['nomor_registrasi']; ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($row['tanggal_laporan'])); ?></td>
                                        <td><?php echo $row['kabupaten'] . ', ' . $row['kecamatan']; ?></td>
                                        <td><?php echo $row['nama_satwa'] ?? '-'; ?></td>
                                        <td><span class="badge <?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                                        <td>
                                            <a href="detail-laporan.php?id=<?php echo $row['id']; ?>" style="color: #667eea; text-decoration: none;">Lihat Detail</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; color: #999;">Belum ada laporan</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>