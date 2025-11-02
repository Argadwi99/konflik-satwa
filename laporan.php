<?php
/**
 * COPY FILE INI KE: C:\xampp\htdocs\konflik-satwa\api\laporan.php
 */

// Cek apakah file config ada
if (!file_exists('../config/database.php')) {
    die(json_encode([
        'status' => 'error',
        'message' => 'Config file not found. Path: ' . realpath('../config/')
    ]));
}

require_once('../config/database.php');

// Headers untuk API
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Fungsi response
function apiResponse($status, $message, $data = null) {
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
    exit;
}

// GET: Ambil daftar laporan
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $status = isset($_GET['status']) ? clean($conn, $_GET['status']) : '';
    $kabupaten = isset($_GET['kabupaten']) ? clean($conn, $_GET['kabupaten']) : '';
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    
    $query = "SELECT 
        l.id,
        l.nomor_registrasi,
        l.tanggal_laporan,
        l.pelapor_nama,
        l.kabupaten,
        l.kecamatan,
        js.nama_satwa,
        l.status,
        l.prioritas
        FROM laporan_konflik l
        LEFT JOIN jenis_satwa js ON l.jenis_satwa_id = js.id
        WHERE 1=1";
    
    if ($status) $query .= " AND l.status = '$status'";
    if ($kabupaten) $query .= " AND l.kabupaten = '$kabupaten'";
    
    $query .= " ORDER BY l.tanggal_laporan DESC LIMIT $limit";
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        apiResponse('error', 'Database error: ' . mysqli_error($conn));
    }
    
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    
    apiResponse('success', 'Data retrieved', [
        'total' => count($data),
        'items' => $data
    ]);
}

// Method lain belum diimplementasi
apiResponse('error', 'Method not supported');
?>