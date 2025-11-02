<?php
/**
 * COPY FILE INI KE: C:\xampp\htdocs\konflik-satwa\api\statistik.php
 */

require_once('../config/database.php');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

function apiResponse($status, $message, $data = null) {
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
    exit;
}

$type = isset($_GET['type']) ? $_GET['type'] : 'summary';

switch ($type) {
    case 'summary':
        // KPI Summary
        $query = "SELECT 
            COUNT(*) as total_laporan,
            SUM(CASE WHEN status = 'baru' THEN 1 ELSE 0 END) as baru,
            SUM(CASE WHEN status = 'proses' THEN 1 ELSE 0 END) as proses,
            SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai,
            SUM(CASE WHEN prioritas = 'urgent' THEN 1 ELSE 0 END) as urgent
            FROM laporan_konflik";
        
        $result = mysqli_query($conn, $query);
        $data = mysqli_fetch_assoc($result);
        
        // Convert to integer
        foreach ($data as $key => $value) {
            $data[$key] = (int)$value;
        }
        
        $total = $data['total_laporan'];
        $data['persentase_selesai'] = $total > 0 ? round(($data['selesai'] / $total) * 100, 2) : 0;
        
        apiResponse('success', 'Summary KPI', $data);
        break;
        
    case 'kabupaten':
        // Statistik per Kabupaten
        $query = "SELECT 
            kabupaten,
            COUNT(*) as total,
            SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai,
            SUM(CASE WHEN prioritas = 'urgent' THEN 1 ELSE 0 END) as urgent
            FROM laporan_konflik
            GROUP BY kabupaten
            ORDER BY total DESC
            LIMIT 10";
        
        $result = mysqli_query($conn, $query);
        $data = [];
        
        while ($row = mysqli_fetch_assoc($result)) {
            $row['total'] = (int)$row['total'];
            $row['selesai'] = (int)$row['selesai'];
            $row['urgent'] = (int)$row['urgent'];
            $data[] = $row;
        }
        
        apiResponse('success', 'Statistik per Kabupaten', $data);
        break;
        
    case 'satwa':
        // Top Jenis Satwa
        $query = "SELECT 
            js.nama_satwa,
            COUNT(*) as total
            FROM laporan_konflik l
            LEFT JOIN jenis_satwa js ON l.jenis_satwa_id = js.id
            GROUP BY l.jenis_satwa_id
            ORDER BY total DESC
            LIMIT 5";
        
        $result = mysqli_query($conn, $query);
        $data = [];
        
        while ($row = mysqli_fetch_assoc($result)) {
            $row['total'] = (int)$row['total'];
            $data[] = $row;
        }
        
        apiResponse('success', 'Top Jenis Satwa', $data);
        break;
        
    case 'tren':
        // Tren 6 Bulan
        $query = "SELECT 
            DATE_FORMAT(tanggal_laporan, '%Y-%m') as bulan,
            DATE_FORMAT(tanggal_laporan, '%b %Y') as bulan_label,
            COUNT(*) as jumlah,
            SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai
            FROM laporan_konflik
            WHERE tanggal_laporan >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY bulan
            ORDER BY bulan ASC";
        
        $result = mysqli_query($conn, $query);
        $data = [];
        
        while ($row = mysqli_fetch_assoc($result)) {
            $row['jumlah'] = (int)$row['jumlah'];
            $row['selesai'] = (int)$row['selesai'];
            $data[] = $row;
        }
        
        apiResponse('success', 'Tren 6 Bulan', $data);
        break;
        
    case 'sla':
        // SLA Performance
        $query = "SELECT 
            AVG(DATEDIFF(tanggal_penanganan, tanggal_laporan)) as rata_waktu,
            COUNT(CASE WHEN DATEDIFF(tanggal_penanganan, tanggal_laporan) <= 3 THEN 1 END) as dalam_sla,
            COUNT(CASE WHEN DATEDIFF(tanggal_penanganan, tanggal_laporan) > 3 THEN 1 END) as lewat_sla
            FROM laporan_konflik
            WHERE tanggal_penanganan IS NOT NULL";
        
        $result = mysqli_query($conn, $query);
        $data = mysqli_fetch_assoc($result);
        
        $data['rata_waktu'] = round((float)$data['rata_waktu'], 1);
        $data['dalam_sla'] = (int)$data['dalam_sla'];
        $data['lewat_sla'] = (int)$data['lewat_sla'];
        
        $total = $data['dalam_sla'] + $data['lewat_sla'];
        $data['persentase_sla'] = $total > 0 ? round(($data['dalam_sla'] / $total) * 100, 2) : 0;
        
        apiResponse('success', 'SLA Performance', $data);
        break;
        
    default:
        apiResponse('error', 'Invalid type parameter');
}
?>