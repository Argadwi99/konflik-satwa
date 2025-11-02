<?php
/**
 * File: api/tren-data.php
 * Backend API khusus untuk analisis tren temporal
 * 
 * Types:
 * - harian: Tren per hari (30 hari terakhir)
 * - mingguan: Tren per minggu
 * - bulanan: Tren per bulan
 * - tahunan: Perbandingan tahun
 * - jam: Distribusi jam kejadian (peak hours)
 */

require_once('../config/api-config.php');

validateMethod(['GET']);

$type = getParam('type', 'bulanan', true);

switch ($type) {
    case 'harian':
        getTrenHarian();
        break;
    case 'mingguan':
        getTrenMingguan();
        break;
    case 'bulanan':
        getTrenBulanan();
        break;
    case 'tahunan':
        getTrenTahunan();
        break;
    case 'jam':
        getDistribusiJam();
        break;
    case 'hari':
        getDistribusiHari();
        break;
    default:
        errorResponse('Invalid type parameter');
}

/**
 * Tren Harian (30 hari terakhir)
 */
function getTrenHarian() {
    global $conn;
    
    $hari = getParam('hari', 30);
    
    $query = "SELECT 
        DATE(tanggal_laporan) as tanggal,
        DATE_FORMAT(tanggal_laporan, '%d %b') as tanggal_label,
        COUNT(*) as jumlah,
        SUM(CASE WHEN prioritas = 'urgent' THEN 1 ELSE 0 END) as urgent
        FROM laporan_konflik
        WHERE tanggal_laporan >= DATE_SUB(NOW(), INTERVAL $hari DAY)
        GROUP BY DATE(tanggal_laporan)
        ORDER BY tanggal ASC";
    
    $result = mysqli_query($conn, $query);
    $data = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $row['jumlah'] = (int)$row['jumlah'];
        $row['urgent'] = (int)$row['urgent'];
        $data[] = $row;
    }
    
    successResponse('Tren Harian ' . $hari . ' Hari Terakhir', $data);
}

/**
 * Tren Mingguan
 */
function getTrenMingguan() {
    global $conn;
    
    $minggu = getParam('minggu', 12);
    
    $query = "SELECT 
        YEARWEEK(tanggal_laporan) as minggu_tahun,
        DATE_FORMAT(MIN(tanggal_laporan), '%d %b') as minggu_mulai,
        DATE_FORMAT(MAX(tanggal_laporan), '%d %b %Y') as minggu_selesai,
        COUNT(*) as jumlah
        FROM laporan_konflik
        WHERE tanggal_laporan >= DATE_SUB(NOW(), INTERVAL $minggu WEEK)
        GROUP BY YEARWEEK(tanggal_laporan)
        ORDER BY minggu_tahun ASC";
    
    $result = mysqli_query($conn, $query);
    $data = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $row['jumlah'] = (int)$row['jumlah'];
        $row['label'] = $row['minggu_mulai'] . ' - ' . $row['minggu_selesai'];
        $data[] = $row;
    }
    
    successResponse('Tren Mingguan ' . $minggu . ' Minggu Terakhir', $data);
}

/**
 * Tren Bulanan
 */
function getTrenBulanan() {
    global $conn;
    
    $bulan = getParam('bulan', 12);
    
    $query = "SELECT 
        DATE_FORMAT(tanggal_laporan, '%Y-%m') as bulan,
        DATE_FORMAT(tanggal_laporan, '%b %Y') as bulan_label,
        COUNT(*) as jumlah,
        SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai,
        ROUND(AVG(CASE WHEN status = 'selesai' THEN 
            DATEDIFF(tanggal_penanganan, tanggal_laporan) END), 1) as rata_penanganan
        FROM laporan_konflik
        WHERE tanggal_laporan >= DATE_SUB(NOW(), INTERVAL $bulan MONTH)
        GROUP BY bulan
        ORDER BY bulan ASC";
    
    $result = mysqli_query($conn, $query);
    $data = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $row['jumlah'] = (int)$row['jumlah'];
        $row['selesai'] = (int)$row['selesai'];
        $row['rata_penanganan'] = $row['rata_penanganan'] ? (float)$row['rata_penanganan'] : null;
        $data[] = $row;
    }
    
    successResponse('Tren Bulanan ' . $bulan . ' Bulan Terakhir', $data);
}

/**
 * Perbandingan Tahunan
 */
function getTrenTahunan() {
    global $conn;
    
    $query = "SELECT 
        YEAR(tanggal_laporan) as tahun,
        COUNT(*) as total,
        SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai,
        SUM(CASE WHEN prioritas = 'urgent' THEN 1 ELSE 0 END) as urgent,
        COUNT(DISTINCT kabupaten) as kabupaten_terdampak
        FROM laporan_konflik
        GROUP BY YEAR(tanggal_laporan)
        ORDER BY tahun ASC";
    
    $result = mysqli_query($conn, $query);
    $data = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $row['tahun'] = (int)$row['tahun'];
        $row['total'] = (int)$row['total'];
        $row['selesai'] = (int)$row['selesai'];
        $row['urgent'] = (int)$row['urgent'];
        $row['kabupaten_terdampak'] = (int)$row['kabupaten_terdampak'];
        
        // Growth rate (jika ada tahun sebelumnya)
        $data[] = $row;
    }
    
    // Hitung growth rate
    for ($i = 1; $i < count($data); $i++) {
        $prev = $data[$i-1]['total'];
        $curr = $data[$i]['total'];
        $data[$i]['growth_rate'] = $prev > 0 ? round((($curr - $prev) / $prev) * 100, 2) : 0;
    }
    
    successResponse('Tren Tahunan', $data);
}

/**
 * Distribusi Jam Kejadian (Peak Hours)
 */
function getDistribusiJam() {
    global $conn;
    
    $query = "SELECT 
        HOUR(waktu_kejadian) as jam,
        COUNT(*) as jumlah,
        SUM(CASE WHEN prioritas = 'urgent' THEN 1 ELSE 0 END) as urgent
        FROM laporan_konflik
        WHERE waktu_kejadian IS NOT NULL
        GROUP BY HOUR(waktu_kejadian)
        ORDER BY jam ASC";
    
    $result = mysqli_query($conn, $query);
    $data = [];
    
    // Initialize semua jam (0-23)
    for ($i = 0; $i < 24; $i++) {
        $data[$i] = [
            'jam' => $i,
            'jam_label' => sprintf('%02d:00', $i),
            'jumlah' => 0,
            'urgent' => 0
        ];
    }
    
    // Fill dengan data dari database
    while ($row = mysqli_fetch_assoc($result)) {
        $jam = (int)$row['jam'];
        $data[$jam]['jumlah'] = (int)$row['jumlah'];
        $data[$jam]['urgent'] = (int)$row['urgent'];
    }
    
    // Tentukan peak hours
    $max_jumlah = max(array_column($data, 'jumlah'));
    foreach ($data as &$item) {
        $item['is_peak'] = ($item['jumlah'] >= $max_jumlah * 0.7);
    }
    
    successResponse('Distribusi Jam Kejadian', array_values($data));
}

/**
 * Distribusi Hari dalam Minggu
 */
function getDistribusiHari() {
    global $conn;
    
    $query = "SELECT 
        DAYOFWEEK(waktu_kejadian) as hari_angka,
        CASE DAYOFWEEK(waktu_kejadian)
            WHEN 1 THEN 'Minggu'
            WHEN 2 THEN 'Senin'
            WHEN 3 THEN 'Selasa'
            WHEN 4 THEN 'Rabu'
            WHEN 5 THEN 'Kamis'
            WHEN 6 THEN 'Jumat'
            WHEN 7 THEN 'Sabtu'
        END as hari_nama,
        COUNT(*) as jumlah,
        SUM(CASE WHEN prioritas = 'urgent' THEN 1 ELSE 0 END) as urgent
        FROM laporan_konflik
        WHERE waktu_kejadian IS NOT NULL
        GROUP BY DAYOFWEEK(waktu_kejadian)
        ORDER BY hari_angka ASC";
    
    $result = mysqli_query($conn, $query);
    $data = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $row['jumlah'] = (int)$row['jumlah'];
        $row['urgent'] = (int)$row['urgent'];
        $data[] = $row;
    }
    
    successResponse('Distribusi Hari dalam Minggu', $data);
}
?>