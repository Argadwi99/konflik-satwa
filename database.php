<?php
// Konfigurasi database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Kosongkan jika default XAMPP
define('DB_NAME', 'konflik_satwa');

// Koneksi ke database
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Cek koneksi
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Set charset UTF-8
mysqli_set_charset($conn, "utf8");

// Fungsi untuk generate nomor registrasi
function generateNomorRegistrasi($conn) {
    $tahun = date('Y');
    $bulan = date('m');
    
    // Ambil nomor urut terakhir bulan ini
    $query = "SELECT COUNT(*) as total FROM laporan_konflik 
              WHERE YEAR(tanggal_laporan) = '$tahun' 
              AND MONTH(tanggal_laporan) = '$bulan'";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    $urut = $row['total'] + 1;
    
    // Format: BKSDA/KS/2024/01/0001
    return sprintf("BKSDA/KS/%s/%s/%04d", $tahun, $bulan, $urut);
}

// Fungsi untuk sanitasi input
function clean($conn, $data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return mysqli_real_escape_string($conn, $data);
}
?>