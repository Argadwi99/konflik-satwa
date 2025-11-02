<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

require_once('../config/database.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Generate nomor registrasi
    $nomor_registrasi = generateNomorRegistrasi($conn);
    
    // Sanitasi input
    $tanggal_laporan = clean($conn, $_POST['tanggal_laporan']);
    $waktu_kejadian = clean($conn, $_POST['waktu_kejadian']);
    $pelapor_nama = clean($conn, $_POST['pelapor_nama']);
    $pelapor_telp = clean($conn, $_POST['pelapor_telp']);
    $kabupaten = clean($conn, $_POST['kabupaten']);
    $kecamatan = clean($conn, $_POST['kecamatan']);
    $desa = clean($conn, $_POST['desa']);
    $lokasi_detail = clean($conn, $_POST['lokasi_detail']);
    $jenis_satwa_id = (int)$_POST['jenis_satwa_id'];
    $jenis_konflik = clean($conn, $_POST['jenis_konflik']);
    $prioritas = clean($conn, $_POST['prioritas']);
    $kronologi = clean($conn, $_POST['kronologi']);
    
    // Koordinat GPS (opsional)
    $latitude = !empty($_POST['latitude']) ? (float)$_POST['latitude'] : null;
    $longitude = !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null;
    
    // Query insert
    $query = "INSERT INTO laporan_konflik (
        nomor_registrasi, tanggal_laporan, waktu_kejadian,
        pelapor_nama, pelapor_telp, kabupaten, kecamatan, desa,
        lokasi_detail, latitude, longitude, jenis_satwa_id, jenis_konflik, kronologi,
        prioritas, status
    ) VALUES (
        '$nomor_registrasi', '$tanggal_laporan', '$waktu_kejadian',
        '$pelapor_nama', '$pelapor_telp', '$kabupaten', '$kecamatan', '$desa',
        '$lokasi_detail', " . ($latitude ? $latitude : "NULL") . ", " . ($longitude ? $longitude : "NULL") . ", $jenis_satwa_id, '$jenis_konflik', '$kronologi',
        '$prioritas', 'baru'
    )";
    
    if (mysqli_query($conn, $query)) {
        header("Location: ../pages/laporan-baru.php?success=1&noreg=" . urlencode($nomor_registrasi));
    } else {
        header("Location: ../pages/laporan-baru.php?error=1");
    }
    exit();
} else {
    header("Location: ../pages/laporan-baru.php");
    exit();
}
?>