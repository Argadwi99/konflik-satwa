<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

require_once('../config/database.php');

// Filter
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_kabupaten = isset($_GET['kabupaten']) ? $_GET['kabupaten'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Query dengan filter
$query = "SELECT l.*, js.nama_satwa, u.nama_lengkap as petugas_nama
    FROM laporan_konflik l
    LEFT JOIN jenis_satwa js ON l.jenis_satwa_id = js.id
    LEFT JOIN users u ON l.petugas_id = u.id
    WHERE 1=1";

if (!empty($filter_status)) {
    $query .= " AND l.status = '" . clean($conn, $filter_status) . "'";
}

if (!empty($filter_kabupaten)) {
    $query .= " AND l.kabupaten = '" . clean($conn, $filter_kabupaten) . "'";
}

if (!empty($search)) {
    $search_clean = clean($conn, $search);
    $query .= " AND (l.nomor_registrasi LIKE '%$search_clean%' 
                OR l.pelapor_nama LIKE '%$search_clean%'
                OR l.kecamatan LIKE '%$search_clean%')";
}

$query .= " ORDER BY l.created_at DESC";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Laporan - Sistem E-Reporting Konflik Satwa</title>
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
                <h1>Daftar Laporan</h1>
            </div>

            <div class="content-box">
                <h2>Filter & Pencarian</h2>
                
                <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
                    <div class="form-group" style="margin: 0;">
                        <label>Status</label>
                        <select name="status">
                            <option value="">Semua Status</option>
                            <option value="baru" <?php echo $filter_status == 'baru' ? 'selected' : ''; ?>>Baru</option>
                            <option value="proses" <?php echo $filter_status == 'proses' ? 'selected' : ''; ?>>Proses</option>
                            <option value="selesai" <?php echo $filter_status == 'selesai' ? 'selected' : ''; ?>>Selesai</option>
                            <option value="monitoring" <?php echo $filter_status == 'monitoring' ? 'selected' : ''; ?>>Monitoring</option>
                        </select>
                    </div>

                    <div class="form-group" style="margin: 0;">
                        <label>Kabupaten</label>
                        <select name="kabupaten">
                            <option value="">Semua Kabupaten</option>
                            <option <?php echo $filter_kabupaten == 'Cilacap' ? 'selected' : ''; ?>>Cilacap</option>
                            <option <?php echo $filter_kabupaten == 'Banyumas' ? 'selected' : ''; ?>>Banyumas</option>
                            <option <?php echo $filter_kabupaten == 'Semarang' ? 'selected' : ''; ?>>Semarang</option>
                            <option <?php echo $filter_kabupaten == 'Pekalongan' ? 'selected' : ''; ?>>Pekalongan</option>
                        </select>
                    </div>

                    <div class="form-group" style="margin: 0;">
                        <label>Pencarian</label>
                        <input type="text" name="search" placeholder="No. Reg / Nama / Kecamatan" value="<?php echo htmlspecialchars($search); ?>">
                    </div>

                    <div class="form-group" style="margin: 0; display: flex; align-items: flex-end;">
                        <button type="submit" class="btn btn-primary" style="margin-right: 10px;">🔍 Filter</button>
                        <a href="daftar-laporan.php" class="btn" style="background: #6c757d; color: white; text-decoration: none; text-align: center; padding: 12px 20px;">Reset</a>
                    </div>
                </form>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>No. Registrasi</th>
                                <th>Tanggal</th>
                                <th>Pelapor</th>
                                <th>Lokasi</th>
                                <th>Jenis Satwa</th>
                                <th>Jenis Konflik</th>
                                <th>Prioritas</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($result) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td><strong><?php echo $row['nomor_registrasi']; ?></strong></td>
                                        <td><?php echo date('d/m/Y', strtotime($row['tanggal_laporan'])); ?></td>
                                        <td><?php echo $row['pelapor_nama']; ?></td>
                                        <td><?php echo $row['kabupaten'] . ', ' . $row['kecamatan']; ?></td>
                                        <td><?php echo $row['nama_satwa'] ?? '-'; ?></td>
                                        <td><?php echo str_replace('_', ' ', ucwords($row['jenis_konflik'], '_')); ?></td>
                                        <td><span class="badge <?php echo $row['prioritas']; ?>"><?php echo ucfirst($row['prioritas']); ?></span></td>
                                        <td><span class="badge <?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                                        <td>
                                            <a href="detail-laporan.php?id=<?php echo $row['id']; ?>" style="color: #667eea; text-decoration: none; font-weight: 600;">Detail →</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; color: #999; padding: 40px;">
                                        Tidak ada laporan yang sesuai dengan filter
                                    </td>
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