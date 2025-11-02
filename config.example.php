<?php
/**
 * File konfigurasi contoh
 * 
 * CARA PAKAI:
 * 1. Copy file ini menjadi database.php
 * 2. Sesuaikan nilai konfigurasi dengan server Anda
 */

// Konfigurasi Database
define('DB_HOST', 'localhost');        // Host database (default: localhost)
define('DB_USER', 'root');             // Username database (default: root)
define('DB_PASS', '');                 // Password database (kosong untuk XAMPP)
define('DB_NAME', 'konflik_satwa');    // Nama database

// Konfigurasi Aplikasi
define('APP_NAME', 'E-Reporting Konflik Satwa');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/konflik-satwa');

// Konfigurasi Session
define('SESSION_LIFETIME', 3600); // 1 jam dalam detik

// Konfigurasi Upload (untuk pengembangan selanjutnya)
define('UPLOAD_PATH', 'uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB dalam bytes
define('ALLOWED_EXTENSIONS', 'jpg,jpeg,png,pdf');

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Error Reporting (Development Mode)
// Untuk production, set ke 0
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug Mode
define('DEBUG_MODE', true); // Set false untuk production
?>