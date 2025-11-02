<?php
/**
 * File: process/send-notification.php
 * Mengirim notifikasi ke pelapor saat status berubah
 */

require_once('../config/database.php');

/**
 * Kirim notifikasi WhatsApp via Fonnte
 */
function sendWhatsApp($phone, $message) {
    $token = 'YOUR_FONNTE_TOKEN'; // Ganti dengan token dari fonnte.com
    
    // Format nomor Indonesia
    if (substr($phone, 0, 1) == '0') {
        $phone = '62' . substr($phone, 1);
    }
    
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.fonnte.com/send',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => array(
            'target' => $phone,
            'message' => $message,
            'countryCode' => '62'
        ),
        CURLOPT_HTTPHEADER => array(
            'Authorization: ' . $token
        ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);
    
    return json_decode($response, true);
}

/**
 * Kirim Email via PHP Mailer
 */
function sendEmail($to, $subject, $message) {
    $headers = "From: BKSDA Jawa Tengah <noreply@bksda-jateng.go.id>\r\n";
    $headers .= "Reply-To: admin@bksda-jateng.go.id\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    $body = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #667eea; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .footer { text-align: center; padding: 15px; color: #999; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>🐾 BKSDA Jawa Tengah</h2>
                <p>Sistem E-Reporting Konflik Satwa</p>
            </div>
            <div class='content'>
                $message
            </div>
            <div class='footer'>
                <p>Email ini dikirim otomatis oleh sistem. Harap tidak membalas email ini.</p>
                <p>&copy; 2024 BKSDA Jawa Tengah</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return mail($to, $subject, $body, $headers);
}

/**
 * Kirim SMS via Zenziva
 */
function sendSMS($phone, $message) {
    $userkey = 'YOUR_ZENZIVA_USERKEY';
    $passkey = 'YOUR_ZENZIVA_PASSKEY';
    
    $url = 'https://console.zenziva.net/wareguler/api/sendWA/';
    $curlHandle = curl_init();
    curl_setopt($curlHandle, CURLOPT_URL, $url);
    curl_setopt($curlHandle, CURLOPT_HEADER, 0);
    curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curlHandle, CURLOPT_TIMEOUT, 30);
    curl_setopt($curlHandle, CURLOPT_POST, 1);
    curl_setopt($curlHandle, CURLOPT_POSTFIELDS, array(
        'userkey' => $userkey,
        'passkey' => $passkey,
        'to' => $phone,
        'message' => $message
    ));
    
    $results = curl_exec($curlHandle);
    curl_close($curlHandle);
    
    return json_decode($results, true);
}

/**
 * Fungsi utama untuk notifikasi update status
 */
function notifyStatusUpdate($laporan_id, $status_baru) {
    global $conn;
    
    // Ambil data laporan
    $query = "SELECT l.nomor_registrasi, l.pelapor_nama, l.pelapor_telp, 
              l.status, l.kabupaten, l.kecamatan
              FROM laporan_konflik l
              WHERE l.id = $laporan_id";
    $result = mysqli_query($conn, $query);
    $laporan = mysqli_fetch_assoc($result);
    
    if (!$laporan || empty($laporan['pelapor_telp'])) {
        return false;
    }
    
    // Template pesan
    $status_label = [
        'baru' => 'Diterima',
        'proses' => 'Sedang Diproses',
        'selesai' => 'Selesai Ditangani',
        'monitoring' => 'Dalam Monitoring'
    ];
    
    $message = "*BKSDA JAWA TENGAH*\n\n";
    $message .= "Kepada Yth. {$laporan['pelapor_nama']}\n\n";
    $message .= "Laporan konflik satwa Anda:\n";
    $message .= "No. Registrasi: *{$laporan['nomor_registrasi']}*\n";
    $message .= "Lokasi: {$laporan['kabupaten']}, {$laporan['kecamatan']}\n\n";
    $message .= "Status: *" . $status_label[$status_baru] . "*\n\n";
    
    if ($status_baru == 'selesai') {
        $message .= "Terima kasih atas laporan Anda. Kasus telah ditangani oleh petugas kami.\n\n";
    } else {
        $message .= "Tim kami akan segera menindaklanjuti laporan Anda.\n\n";
    }
    
    $message .= "Salam Konservasi 🐾";
    
    // Kirim WhatsApp
    $wa_result = sendWhatsApp($laporan['pelapor_telp'], $message);
    
    // Log notifikasi
    $log_status = $wa_result['status'] ?? 'gagal';
    $query_log = "INSERT INTO notifikasi_log (laporan_id, jenis_notifikasi, tujuan, pesan, status, sent_at)
                  VALUES ($laporan_id, 'whatsapp', '{$laporan['pelapor_telp']}', 
                  '" . mysqli_real_escape_string($conn, $message) . "', 
                  '$log_status', NOW())";
    mysqli_query($conn, $query_log);
    
    return true;
}

// Contoh penggunaan (dipanggil dari update-status.php)
if (isset($_GET['test'])) {
    // Test notification
    notifyStatusUpdate(1, 'proses');
    echo "Notifikasi test terkirim!";
}
?>