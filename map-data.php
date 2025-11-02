<?php
/**
 * COPY FILE INI KE: C:\xampp\htdocs\konflik-satwa\api\map-data.php
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

$type = isset($_GET['type']) ? $_GET['type'] : 'hotspot';

switch ($type) {
    case 'hotspot':
        // Data Marker untuk Peta
        $query = "SELECT 
            l.id,
            l.nomor_registrasi,
            l.tanggal_laporan,
            l.kabupaten,
            l.kecamatan,
            l.desa,
            l.latitude,
            l.longitude,
            js.nama_satwa,
            l.jenis_konflik,
            l.prioritas,
            l.status
            FROM laporan_konflik l
            LEFT JOIN jenis_satwa js ON l.jenis_satwa_id = js.id
            WHERE l.latitude IS NOT NULL AND l.longitude IS NOT NULL
            ORDER BY l.tanggal_laporan DESC
            LIMIT 100";
        
        $result = mysqli_query($conn, $query);
        $markers = [];
        
        while ($row = mysqli_fetch_assoc($result)) {
            $markers[] = [
                'id' => (int)$row['id'],
                'nomor_registrasi' => $row['nomor_registrasi'],
                'tanggal' => $row['tanggal_laporan'],
                'lokasi' => [
                    'kabupaten' => $row['kabupaten'],
                    'kecamatan' => $row['kecamatan'],
                    'desa' => $row['desa'],
                    'latitude' => (float)$row['latitude'],
                    'longitude' => (float)$row['longitude']
                ],
                'satwa' => $row['nama_satwa'],
                'jenis_konflik' => $row['jenis_konflik'],
                'prioritas' => $row['prioritas'],
                'status' => $row['status']
            ];
        }
        
        apiResponse('success', 'Hotspot data', [
            'total' => count($markers),
            'markers' => $markers
        ]);
        break;
        
    case 'heatmap':
        // Data Heatmap
        $query = "SELECT 
            latitude,
            longitude,
            CASE 
                WHEN prioritas = 'urgent' THEN 1.0
                WHEN prioritas = 'tinggi' THEN 0.8
                WHEN prioritas = 'sedang' THEN 0.6
                ELSE 0.4
            END as intensity
            FROM laporan_konflik
            WHERE latitude IS NOT NULL AND longitude IS NOT NULL";
        
        $result = mysqli_query($conn, $query);
        $points = [];
        
        while ($row = mysqli_fetch_assoc($result)) {
            $points[] = [
                (float)$row['latitude'],
                (float)$row['longitude'],
                (float)$row['intensity']
            ];
        }
        
        apiResponse('success', 'Heatmap data', [
            'total' => count($points),
            'points' => $points
        ]);
        break;
        
    case 'cluster':
        // Cluster per Kabupaten
        $query = "SELECT 
            kabupaten,
            AVG(latitude) as center_lat,
            AVG(longitude) as center_lng,
            COUNT(*) as jumlah_kasus,
            SUM(CASE WHEN prioritas = 'urgent' THEN 1 ELSE 0 END) as urgent_count
            FROM laporan_konflik
            WHERE latitude IS NOT NULL AND longitude IS NOT NULL
            GROUP BY kabupaten
            ORDER BY jumlah_kasus DESC";
        
        $result = mysqli_query($conn, $query);
        $clusters = [];
        
        while ($row = mysqli_fetch_assoc($result)) {
            $jumlah = (int)$row['jumlah_kasus'];
            $tingkat = $jumlah > 20 ? 'tinggi' : ($jumlah > 10 ? 'sedang' : 'rendah');
            
            $clusters[] = [
                'kabupaten' => $row['kabupaten'],
                'center' => [
                    'lat' => (float)$row['center_lat'],
                    'lng' => (float)$row['center_lng']
                ],
                'jumlah_kasus' => $jumlah,
                'urgent_count' => (int)$row['urgent_count'],
                'tingkat_kerawanan' => $tingkat
            ];
        }
        
        apiResponse('success', 'Cluster data', [
            'total' => count($clusters),
            'clusters' => $clusters
        ]);
        break;
        
    default:
        apiResponse('error', 'Invalid type parameter');
}
?>