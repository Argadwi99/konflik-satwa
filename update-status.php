<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

require_once('../config/database.php');
require_once('send-notification.php'); // Include notifikasi

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $laporan_id = (int)$_POST['laporan_id'];
    $status = clean($conn, $_POST['status']);
    $jenis_tindakan = clean($conn, $_POST['jenis_tindakan']);
    $keterangan = clean($conn, $_POST['keterangan']);
    $petugas_id = $_SESSION['user_id'];
    
    // Ambil status lama untuk perbandingan
    $query_old = "SELECT status, pelapor_telp FROM laporan_konflik WHERE id = $laporan_id";
    $result_old = mysqli_query($conn, $query_old);
    $old_data = mysqli_fetch_assoc($result_old);
    $status_lama = $old_data['status'];
    
    // Update status laporan
    $query_update = "UPDATE laporan_konflik 
                     SET status = '$status', 
                         petugas_id = $petugas_id,
                         tanggal_penanganan = NOW()
                     WHERE id = $laporan_id";
    
    if (mysqli_query($conn, $query_update)) {
        
        // Insert tindak lanjut jika ada
        if (!empty($jenis_tindakan)) {
            $tanggal_tindakan = date('Y-m-d');
            $query_tl = "INSERT INTO tindak_lanjut (
                laporan_id, tanggal_tindakan, jenis_tindakan, 
                keterangan, petugas_id
            ) VALUES (
                $laporan_id, '$tanggal_tindakan', '$jenis_tindakan',
                '$keterangan', $petugas_id
            )";
            mysqli_query($conn, $query_tl);
        }
        
        // 🔔 KIRIM NOTIFIKASI JIKA STATUS BERUBAH
        if ($status != $status_lama && !empty($old_data['pelapor_telp'])) {
            notifyStatusUpdate($laporan_id, $status);
        }
        
        header("Location: ../pages/detail-laporan.php?id=$laporan_id&updated=1");
    } else {
        header("Location: ../pages/detail-laporan.php?id=$laporan_id&error=1");
    }
    
    exit();
} else {
    header("Location: ../pages/daftar-laporan.php");
    exit();
}
?>