<?php
/**
 * INSTALLER OTOMATIS SISTEM E-REPORTING
 * 
 * File ini akan membuat database dan tabel secara otomatis
 * Akses: http://localhost/konflik-satwa/install.php
 */

// Cek apakah sudah pernah install
if (file_exists('INSTALLED.txt')) {
    die('
    <html>
    <head>
        <title>Sudah Terinstall</title>
        <style>
            body { font-family: Arial; text-align: center; padding: 50px; background: #f5f5f5; }
            .box { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 500px; margin: 0 auto; }
            h1 { color: #dc3545; }
        </style>
    </head>
    <body>
        <div class="box">
            <h1>⚠️ Sistem Sudah Terinstall</h1>
            <p>Sistem sudah pernah diinstall sebelumnya.</p>
            <p>Jika ingin install ulang, hapus file <strong>INSTALLED.txt</strong> terlebih dahulu.</p>
            <br>
            <a href="index.php" style="color: #667eea; text-decoration: none; font-weight: bold;">← Kembali ke Login</a>
        </div>
    </body>
    </html>
    ');
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $db_host = $_POST['db_host'];
    $db_user = $_POST['db_user'];
    $db_pass = $_POST['db_pass'];
    $db_name = $_POST['db_name'];
    
    // Koneksi ke MySQL (tanpa database dulu)
    $conn = mysqli_connect($db_host, $db_user, $db_pass);
    
    if (!$conn) {
        $error = "Koneksi gagal: " . mysqli_connect_error();
    } else {
        // Buat database
        $sql_create_db = "CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci";
        
        if (!mysqli_query($conn, $sql_create_db)) {
            $error = "Gagal membuat database: " . mysqli_error($conn);
        } else {
            // Pilih database
            mysqli_select_db($conn, $db_name);
            
            // Baca file SQL
            $sql_file = file_get_contents('database.sql');
            
            if (!$sql_file) {
                $error = "File database.sql tidak ditemukan!";
            } else {
                // Split query by semicolon
                $queries = explode(';', $sql_file);
                $query_success = true;
                
                foreach ($queries as $query) {
                    $query = trim($query);
                    if (!empty($query)) {
                        if (!mysqli_query($conn, $query)) {
                            $query_success = false;
                            $error = "Error: " . mysqli_error($conn) . "<br>Query: " . substr($query, 0, 100);
                            break;
                        }
                    }
                }
                
                if ($query_success) {
                    // Buat file config/database.php
                    $config_content = "<?php
// Konfigurasi database
define('DB_HOST', '$db_host');
define('DB_USER', '$db_user');
define('DB_PASS', '$db_pass');
define('DB_NAME', '$db_name');

// Koneksi ke database
\$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Cek koneksi
if (!\$conn) {
    die(\"Koneksi gagal: \" . mysqli_connect_error());
}

// Set charset UTF-8
mysqli_set_charset(\$conn, \"utf8\");

// Fungsi untuk generate nomor registrasi
function generateNomorRegistrasi(\$conn) {
    \$tahun = date('Y');
    \$bulan = date('m');
    
    \$query = \"SELECT COUNT(*) as total FROM laporan_konflik 
              WHERE YEAR(tanggal_laporan) = '\$tahun' 
              AND MONTH(tanggal_laporan) = '\$bulan'\";
    \$result = mysqli_query(\$conn, \$query);
    \$row = mysqli_fetch_assoc(\$result);
    \$urut = \$row['total'] + 1;
    
    return sprintf(\"BKSDA/KS/%s/%s/%04d\", \$tahun, \$bulan, \$urut);
}

// Fungsi untuk sanitasi input
function clean(\$conn, \$data) {
    \$data = trim(\$data);
    \$data = stripslashes(\$data);
    \$data = htmlspecialchars(\$data);
    return mysqli_real_escape_string(\$conn, \$data);
}
?>";
                    
                    file_put_contents('config/database.php', $config_content);
                    
                    // Buat file penanda sudah install
                    file_put_contents('INSTALLED.txt', date('Y-m-d H:i:s'));
                    
                    $success = true;
                }
            }
        }
        
        mysqli_close($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalasi Sistem E-Reporting</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .install-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 500px;
            width: 100%;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            text-align: center;
        }
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
        }
        input:focus {
            outline: none;
            border-color: #667eea;
        }
        .btn {
            width: 100%;
            padding: 14px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn:hover {
            background: #5568d3;
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .info-box {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #004085;
        }
        .success-box {
            text-align: center;
        }
        .success-box h2 {
            color: #28a745;
            margin-bottom: 20px;
        }
        .login-link {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 30px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="install-container">
        <?php if ($success): ?>
            <div class="success-box">
                <h2>✅ Instalasi Berhasil!</h2>
                <p>Database dan sistem telah berhasil diinstall.</p>
                
                <div class="info-box" style="text-align: left; margin-top: 20px;">
                    <strong>🔑 Akun Default:</strong><br>
                    <strong>Admin:</strong> admin / admin123<br>
                    <strong>Petugas:</strong> petugas1 / petugas123<br>
                    <strong>Kepala Seksi:</strong> kepala / kepala123
                </div>
                
                <p style="color: #dc3545; font-size: 12px; margin-top: 15px;">
                    ⚠️ Jangan lupa ganti password setelah login!
                </p>
                
                <a href="index.php" class="login-link">Masuk ke Sistem →</a>
                
                <p style="margin-top: 20px; font-size: 12px; color: #999;">
                    File <strong>install.php</strong> bisa dihapus untuk keamanan.
                </p>
            </div>
        <?php else: ?>
            <h1>🐾 Instalasi Sistem</h1>
            <p class="subtitle">E-Reporting Konflik Satwa BKSDA Jawa Tengah</p>
            
            <div class="info-box">
                📌 <strong>Persyaratan:</strong><br>
                • XAMPP sudah terinstall<br>
                • Apache & MySQL sudah running<br>
                • File database.sql ada di folder aplikasi
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    ❌ <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Database Host</label>
                    <input type="text" name="db_host" value="localhost" required>
                </div>
                
                <div class="form-group">
                    <label>Database Username</label>
                    <input type="text" name="db_user" value="root" required>
                </div>
                
                <div class="form-group">
                    <label>Database Password</label>
                    <input type="password" name="db_pass" placeholder="Kosongkan jika default XAMPP">
                </div>
                
                <div class="form-group">
                    <label>Database Name</label>
                    <input type="text" name="db_name" value="konflik_satwa" required>
                </div>
                
                <button type="submit" class="btn">🚀 Install Sekarang</button>
            </form>
            
            <p style="text-align: center; margin-top: 20px; font-size: 12px; color: #999;">
                Proses ini akan membuat database dan tabel secara otomatis
            </p>
        <?php endif; ?>
    </div>
</body>
</html>