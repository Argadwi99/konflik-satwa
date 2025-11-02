<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

require_once('../config/database.php');

// Ambil daftar jenis satwa
$query_satwa = "SELECT * FROM jenis_satwa ORDER BY nama_satwa";
$list_satwa = mysqli_query($conn, $query_satwa);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Baru - Sistem E-Reporting Konflik Satwa</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css" />
    <script src="../assets/js/script.js" defer></script>
    <style>
        #mapPicker {
            height: 400px;
            width: 100%;
            border-radius: 8px;
            margin-top: 10px;
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
                <h1>Buat Laporan Baru</h1>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    ✅ Laporan berhasil disimpan dengan nomor: <strong><?php echo $_GET['noreg']; ?></strong>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-error">
                    ❌ Gagal menyimpan laporan. Silakan coba lagi.
                </div>
            <?php endif; ?>

            <div class="content-box">
                <h2>Form Laporan Konflik Satwa</h2>
                
                <form action="../process/submit-laporan.php" method="POST">
                    <h3 style="margin: 20px 0 15px; color: #667eea;">📞 Data Pelapor</h3>
                    
                    <div class="form-group">
                        <label>Nama Pelapor *</label>
                        <input type="text" name="pelapor_nama" required>
                    </div>

                    <div class="form-group">
                        <label>No. Telepon</label>
                        <input type="tel" name="pelapor_telp" placeholder="08xxxxxxxxxx">
                    </div>

                    <h3 style="margin: 30px 0 15px; color: #667eea;">📍 Lokasi Kejadian</h3>

                    <div class="form-group">
                        <label>Kabupaten *</label>
                        <select name="kabupaten" required>
                            <option value="">-- Pilih Kabupaten --</option>
                            <option>Cilacap</option>
                            <option>Banyumas</option>
                            <option>Purbalingga</option>
                            <option>Banjarnegara</option>
                            <option>Kebumen</option>
                            <option>Purworejo</option>
                            <option>Wonosobo</option>
                            <option>Magelang</option>
                            <option>Boyolali</option>
                            <option>Klaten</option>
                            <option>Sukoharjo</option>
                            <option>Wonogiri</option>
                            <option>Karanganyar</option>
                            <option>Sragen</option>
                            <option>Grobogan</option>
                            <option>Blora</option>
                            <option>Rembang</option>
                            <option>Pati</option>
                            <option>Kudus</option>
                            <option>Jepara</option>
                            <option>Demak</option>
                            <option>Semarang</option>
                            <option>Temanggung</option>
                            <option>Kendal</option>
                            <option>Batang</option>
                            <option>Pekalongan</option>
                            <option>Pemalang</option>
                            <option>Tegal</option>
                            <option>Brebes</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Kecamatan *</label>
                        <input type="text" name="kecamatan" required>
                    </div>

                    <div class="form-group">
                        <label>Desa/Kelurahan *</label>
                        <input type="text" name="desa" required>
                    </div>

                    <div class="form-group">
                        <label>Detail Lokasi</label>
                        <textarea name="lokasi_detail" rows="2" placeholder="Contoh: Dekat pos kamling, RT 02 RW 03"></textarea>
                    </div>

                    <h3 style="margin: 30px 0 15px; color: #667eea;">🐾 Data Konflik</h3>

                    <div class="form-group">
                        <label>Tanggal Laporan *</label>
                        <input type="date" name="tanggal_laporan" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Waktu Kejadian *</label>
                        <input type="datetime-local" name="waktu_kejadian" required>
                    </div>

                    <div class="form-group">
                        <label>Jenis Satwa *</label>
                        <select name="jenis_satwa_id" required>
                            <option value="">-- Pilih Jenis Satwa --</option>
                            <?php while ($satwa = mysqli_fetch_assoc($list_satwa)): ?>
                                <option value="<?php echo $satwa['id']; ?>"><?php echo $satwa['nama_satwa']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Jenis Konflik *</label>
                        <select name="jenis_konflik" required>
                            <option value="">-- Pilih Jenis Konflik --</option>
                            <option value="masuk_pemukiman">Masuk Pemukiman</option>
                            <option value="serang_ternak">Menyerang Ternak</option>
                            <option value="rusak_tanaman">Merusak Tanaman</option>
                            <option value="ancam_manusia">Mengancam Manusia</option>
                            <option value="lainnya">Lainnya</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Prioritas *</label>
                        <select name="prioritas" required>
                            <option value="rendah">Rendah</option>
                            <option value="sedang" selected>Sedang</option>
                            <option value="tinggi">Tinggi</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Kronologi Kejadian *</label>
                        <textarea name="kronologi" rows="5" required placeholder="Jelaskan kronologi kejadian secara detail..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">💾 Simpan Laporan</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>