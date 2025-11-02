<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

require_once('../config/database.php');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Ambil detail laporan
$query = "SELECT l.*, js.nama_satwa, u.nama_lengkap as petugas_nama
    FROM laporan_konflik l
    LEFT JOIN jenis_satwa js ON l.jenis_satwa_id = js.id
    LEFT JOIN users u ON l.petugas_id = u.id
    WHERE l.id = $id";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    header("Location: daftar-laporan.php");
    exit();
}

$laporan = mysqli_fetch_assoc($result);

// Ambil riwayat tindak lanjut
$query_tl = "SELECT tl.*, u.nama_lengkap as petugas_nama
    FROM tindak_lanjut tl
    LEFT JOIN users u ON tl.petugas_id = u.id
    WHERE tl.laporan_id = $id
    ORDER BY tl.tanggal_tindakan DESC";
$tindak_lanjut = mysqli_query($conn, $query_tl);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Laporan - <?php echo $laporan['nomor_registrasi']; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="../assets/js/script.js" defer></script>
    <style>
        @media print {
            .sidebar, .top-bar, form, .btn { display: none !important; }
            .main-content { padding: 20px; }
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
                <h1>Detail Laporan</h1>
                <a href="daftar-laporan.php" style="padding: 8px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 6px;">← Kembali</a>
            </div>

            <?php if (isset($_GET['updated'])): ?>
                <div class="alert alert-success">
                    ✅ Status berhasil diupdate!
                </div>
            <?php endif; ?>

            <!-- Informasi Umum -->
            <div class="content-box">
                <h2>📋 Informasi Laporan</h2>
                
                <table style="width: 100%; margin-top: 20px;">
                    <tr>
                        <td style="width: 200px; padding: 10px 0; font-weight: 600;">No. Registrasi</td>
                        <td style="padding: 10px 0;"><?php echo $laporan['nomor_registrasi']; ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 0; font-weight: 600;">Tanggal Laporan</td>
                        <td style="padding: 10px 0;"><?php echo date('d F Y', strtotime($laporan['tanggal_laporan'])); ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 0; font-weight: 600;">Waktu Kejadian</td>
                        <td style="padding: 10px 0;"><?php echo date('d F Y, H:i', strtotime($laporan['waktu_kejadian'])); ?> WIB</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 0; font-weight: 600;">Status</td>
                        <td style="padding: 10px 0;">
                            <span class="badge <?php echo $laporan['status']; ?>"><?php echo ucfirst($laporan['status']); ?></span>
                            <span class="badge <?php echo $laporan['prioritas']; ?>"><?php echo ucfirst($laporan['prioritas']); ?></span>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Data Pelapor -->
            <div class="content-box">
                <h2>📞 Data Pelapor</h2>
                <table style="width: 100%; margin-top: 20px;">
                    <tr>
                        <td style="width: 200px; padding: 10px 0; font-weight: 600;">Nama Pelapor</td>
                        <td style="padding: 10px 0;"><?php echo $laporan['pelapor_nama']; ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 0; font-weight: 600;">No. Telepon</td>
                        <td style="padding: 10px 0;"><?php echo $laporan['pelapor_telp'] ?: '-'; ?></td>
                    </tr>
                </table>
            </div>

            <!-- Lokasi -->
            <div class="content-box">
                <h2>📍 Lokasi Kejadian</h2>
                <table style="width: 100%; margin-top: 20px;">
                    <tr>
                        <td style="width: 200px; padding: 10px 0; font-weight: 600;">Kabupaten</td>
                        <td style="padding: 10px 0;"><?php echo $laporan['kabupaten']; ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 0; font-weight: 600;">Kecamatan</td>
                        <td style="padding: 10px 0;"><?php echo $laporan['kecamatan']; ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 0; font-weight: 600;">Desa/Kelurahan</td>
                        <td style="padding: 10px 0;"><?php echo $laporan['desa']; ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 0; font-weight: 600;">Detail Lokasi</td>
                        <td style="padding: 10px 0;"><?php echo $laporan['lokasi_detail'] ?: '-'; ?></td>
                    </tr>
                </table>
            </div>

            <!-- Data Konflik -->
            <div class="content-box">
                <h2>🐾 Data Konflik</h2>
                <table style="width: 100%; margin-top: 20px;">
                    <tr>
                        <td style="width: 200px; padding: 10px 0; font-weight: 600;">Jenis Satwa</td>
                        <td style="padding: 10px 0;"><?php echo $laporan['nama_satwa']; ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 0; font-weight: 600;">Jenis Konflik</td>
                        <td style="padding: 10px 0;"><?php echo str_replace('_', ' ', ucwords($laporan['jenis_konflik'], '_')); ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 0; font-weight: 600; vertical-align: top;">Kronologi</td>
                        <td style="padding: 10px 0;"><?php echo nl2br($laporan['kronologi']); ?></td>
                    </tr>
                </table>
            </div>

            <!-- Update Status -->
            <?php if ($_SESSION['role'] != 'kepala'): ?>
            <div class="content-box">
                <h2>🔄 Update Status & Tindak Lanjut</h2>
                <form action="../process/update-status.php" method="POST">
                    <input type="hidden" name="laporan_id" value="<?php echo $laporan['id']; ?>">
                    
                    <div class="form-group">
                        <label>Status Laporan</label>
                        <select name="status" required>
                            <option value="baru" <?php echo $laporan['status'] == 'baru' ? 'selected' : ''; ?>>Baru</option>
                            <option value="proses" <?php echo $laporan['status'] == 'proses' ? 'selected' : ''; ?>>Dalam Proses</option>
                            <option value="selesai" <?php echo $laporan['status'] == 'selesai' ? 'selected' : ''; ?>>Selesai</option>
                            <option value="monitoring" <?php echo $laporan['status'] == 'monitoring' ? 'selected' : ''; ?>>Monitoring</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Jenis Tindakan</label>
                        <input type="text" name="jenis_tindakan" placeholder="Contoh: Survey Lokasi, Evakuasi Satwa, Edukasi Masyarakat">
                    </div>

                    <div class="form-group">
                        <label>Keterangan Tindakan</label>
                        <textarea name="keterangan" rows="3" placeholder="Jelaskan tindakan yang telah dilakukan..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">💾 Simpan Update</button>
                </form>
            </div>
            <?php endif; ?>

            <!-- Riwayat Tindak Lanjut -->
            <div class="content-box">
                <h2>📜 Riwayat Tindak Lanjut</h2>
                
                <?php if (mysqli_num_rows($tindak_lanjut) > 0): ?>
                    <table style="margin-top: 20px;">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Jenis Tindakan</th>
                                <th>Keterangan</th>
                                <th>Petugas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($tl = mysqli_fetch_assoc($tindak_lanjut)): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($tl['tanggal_tindakan'])); ?></td>
                                    <td><strong><?php echo $tl['jenis_tindakan']; ?></strong></td>
                                    <td><?php echo $tl['keterangan']; ?></td>
                                    <td><?php echo $tl['petugas_nama']; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color: #999; margin-top: 20px; text-align: center;">Belum ada tindak lanjut</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>