<?php
/**
 * COPY FILE INI KE: C:\xampp\htdocs\konflik-satwa\api\test.php
 * 
 * File untuk testing apakah API berjalan dengan baik
 * Akses: http://localhost/konflik-satwa/api/test.php
 */

header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'status' => 'success',
    'message' => 'API is working! 🎉',
    'info' => [
        'server_time' => date('Y-m-d H:i:s'),
        'php_version' => PHP_VERSION,
        'available_endpoints' => [
            'laporan' => 'api/laporan.php',
            'statistik' => 'api/statistik.php?type=summary',
            'map-data' => 'api/map-data.php?type=hotspot'
        ]
    ],
    'system_check' => [
        'config_exists' => file_exists('../config/database.php'),
        'config_path' => realpath('../config/'),
        'api_path' => __DIR__
    ]
], JSON_PRETTY_PRINT);
?>