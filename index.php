<?php
session_start();

// Jika sudah login, redirect ke dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: pages/dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem E-Reporting Konflik Satwa</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/script.js" defer></script>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h2>🐾 Konflik Satwa BKSDA</h2>
            <p>Sistem E-Reporting Jawa Tengah</p>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-error">
                    <?php 
                    if ($_GET['error'] == 'invalid') {
                        echo "Username atau password salah!";
                    } else if ($_GET['error'] == 'empty') {
                        echo "Harap isi semua field!";
                    }
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['logout'])): ?>
                <div class="alert alert-success">
                    Anda berhasil logout!
                </div>
            <?php endif; ?>
            
            <form action="process/login-process.php" method="POST">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required>
                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Masuk</button>
            </form>

            <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; font-size: 12px;">
                <strong>Akun Demo:</strong><br>
                Admin: admin / admin123<br>
                Petugas: petugas1 / petugas123<br>
                Kepala: kepala / kepala123
            </div>
        </div>
    </div>
</body>
</html>