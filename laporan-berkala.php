<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

require_once('../config/database.php');

// Hitung statistik untuk preview
$bulan_ini = date('Y-m');
$query_stats = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai,
    SUM(CASE WHEN prioritas = 'urgent' THEN 1 ELSE 0 END) as urgent
    FROM laporan_konflik
    WHERE DATE_FORMAT(tanggal_laporan, '%Y-%m') = '$bulan_ini'";
$stats = mysqli_fetch_assoc(mysqli_query($conn, $query_stats));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Berkala - Export</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .export-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        .export-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .export-option {
            border: 2px solid #e0e0e0;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
        }
        .export-option:hover {
            border-color: #667eea;
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.2);
        }
        .export-option i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #667eea;
        }
        .export-option h3 {
            margin-bottom: 10px;
            color: #333;
        }
        .export-option p {
            color: #666;
            font-size: 14px;
            margin-bottom: 20px;
        }
        .btn-export {
            padding: 10px 25px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-excel { background: #28a745; color: white; }
        .btn-pdf { background: #dc3545; color: white; }
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
                <h1>📑 Laporan Berkala</h1>
            </div>

            <!-- Preview Statistik Bulan Ini -->
            <div class="stats-grid">
                <div class="stat-card blue">
                    <h3>Laporan Bulan Ini</h3>
                    <div class="number"><?php echo $stats['total'] ?? 0; ?></div>
                </div>
                <div class="stat-card green">
                    <h3>Selesai Ditangani</h3>
                    <div class="number"><?php echo $stats['selesai'] ?? 0; ?></div>
                </div>
                <div class="stat-card red">
                    <h3>Kasus Urgent</h3>
                    <div class="number"><?php echo $stats['urgent'] ?? 0; ?></div>
                </div>
            </div>

            <!-- Form Filter Export -->
            <div class="export-card">
                <h2>Filter Periode Laporan</h2>
                <form method="GET" action="../process/export-excel.php" id="formExport">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 20px;">
                        <div class="form-group" style="margin: 0;">
                            <label>Periode</label>
                            <select name="periode" id="periodeSelect" onchange="toggleCustomDate()">
                                <option value="bulan_ini">Bulan Ini</option>
                                <option value="bulan_lalu">Bulan Lalu</option>
                                <option value="triwulan">Triwulan Ini</option>
                                <option value="tahun_ini">Tahun Ini</option>
                                <option value="custom">Custom</option>
                            </select>
                        </div>

                        <div class="form-group" style="margin: 0; display: none;" id="customDateStart">
                            <label>Tanggal Mulai</label>
                            <input type="date" name="tanggal_mulai">
                        </div>

                        <div class="form-group" style="margin: 0; display: none;" id="customDateEnd">
                            <label>Tanggal Selesai</label>
                            <input type="date" name="tanggal_selesai">
                        </div>

                        <div class="form-group" style="margin: 0;">
                            <label>Kabupaten</label>
                            <select name="kabupaten">
                                <option value="">Semua Kabupaten</option>
                                <option>Cilacap</option>
                                <option>Banyumas</option>
                                <option>Semarang</option>
                                <option>Pekalongan</option>
                                <option>Kendal</option>
                            </select>
                        </div>

                        <div class="form-group" style="margin: 0;">
                            <label>Status</label>
                            <select name="status">
                                <option value="">Semua Status</option>
                                <option value="baru">Baru</option>
                                <option value="proses">Proses</option>
                                <option value="selesai">Selesai</option>
                                <option value="monitoring">Monitoring</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Pilihan Format Export -->
            <div class="export-card">
                <h2>Pilih Format Export</h2>
                <div class="export-grid">
                    <!-- Export Excel -->
                    <div class="export-option" onclick="exportData('excel')">
                        <div style="font-size: 48px; color: #28a745;">📊</div>
                        <h3>Export ke Excel</h3>
                        <p>Format .xlsx untuk analisis data lebih lanjut dengan Microsoft Excel atau LibreOffice</p>
                        <button class="btn-export btn-excel">📥 Download Excel</button>
                    </div>

                    <!-- Export PDF -->
                    <div class="export-option" onclick="exportData('pdf')">
                        <div style="font-size: 48px; color: #dc3545;">📄</div>
                        <h3>Export ke PDF</h3>
                        <p>Format .pdf untuk laporan formal yang siap dicetak atau dibagikan</p>
                        <button class="btn-export btn-pdf">📥 Download PDF</button>
                    </div>
                </div>
            </div>

            <!-- Info -->
            <div style="background: #e7f3ff; padding: 20px; border-radius: 10px; margin-top: 20px;">
                <h4 style="margin-bottom: 10px; color: #004085;">💡 Informasi Export:</h4>
                <ul style="margin: 0; padding-left: 20px; color: #004085; line-height: 2;">
                    <li><strong>Excel:</strong> Cocok untuk analisis data, pivot table, dan grafik</li>
                    <li><strong>PDF:</strong> Cocok untuk laporan resmi, rapat, dan arsip dokumentasi</li>
                    <li>File akan otomatis terdownload setelah Anda klik tombol export</li>
                    <li>Gunakan filter untuk mendapatkan data spesifik sesuai kebutuhan</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        function toggleCustomDate() {
            const periode = document.getElementById('periodeSelect').value;
            const startDiv = document.getElementById('customDateStart');
            const endDiv = document.getElementById('customDateEnd');
            
            if (periode === 'custom') {
                startDiv.style.display = 'block';
                endDiv.style.display = 'block';
            } else {
                startDiv.style.display = 'none';
                endDiv.style.display = 'none';
            }
        }

        function exportData(format) {
            const form = document.getElementById('formExport');
            
            if (format === 'excel') {
                form.action = '../process/export-excel.php';
            } else {
                form.action = '../process/export-pdf.php';
            }
            
            form.submit();
        }
    </script>
</body>
</html>