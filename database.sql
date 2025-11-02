-- Database: konflik_satwa
-- Version: 1.0.0
-- Dibuat: 2024-11

CREATE DATABASE IF NOT EXISTS konflik_satwa;
USE konflik_satwa;

-- Tabel user
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    role ENUM('admin', 'petugas', 'kepala') DEFAULT 'petugas',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel jenis satwa
CREATE TABLE jenis_satwa (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama_satwa VARCHAR(100) NOT NULL
);

-- Tabel laporan konflik
CREATE TABLE laporan_konflik (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nomor_registrasi VARCHAR(50) UNIQUE NOT NULL,
    tanggal_laporan DATE NOT NULL,
    waktu_kejadian DATETIME NOT NULL,
    pelapor_nama VARCHAR(100) NOT NULL,
    pelapor_telp VARCHAR(20),
    kabupaten VARCHAR(50) NOT NULL,
    kecamatan VARCHAR(50) NOT NULL,
    desa VARCHAR(50) NOT NULL,
    lokasi_detail TEXT,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    jenis_satwa_id INT,
    jenis_konflik ENUM('masuk_pemukiman', 'serang_ternak', 'rusak_tanaman', 'ancam_manusia', 'lainnya') NOT NULL,
    kronologi TEXT NOT NULL,
    foto_bukti VARCHAR(255),
    status ENUM('baru', 'proses', 'selesai', 'monitoring') DEFAULT 'baru',
    prioritas ENUM('rendah', 'sedang', 'tinggi', 'urgent') DEFAULT 'sedang',
    petugas_id INT,
    catatan_petugas TEXT,
    tanggal_penanganan DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (jenis_satwa_id) REFERENCES jenis_satwa(id),
    FOREIGN KEY (petugas_id) REFERENCES users(id)
);

-- Tabel tindak lanjut
CREATE TABLE tindak_lanjut (
    id INT PRIMARY KEY AUTO_INCREMENT,
    laporan_id INT NOT NULL,
    tanggal_tindakan DATE NOT NULL,
    jenis_tindakan VARCHAR(100) NOT NULL,
    keterangan TEXT,
    petugas_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (laporan_id) REFERENCES laporan_konflik(id) ON DELETE CASCADE,
    FOREIGN KEY (petugas_id) REFERENCES users(id)
);

-- Insert data users
INSERT INTO users (username, password, nama_lengkap, role) VALUES
('admin', MD5('admin123'), 'Administrator', 'admin'),
('petugas1', MD5('petugas123'), 'Petugas Lapangan 1', 'petugas'),
('kepala', MD5('kepala123'), 'Kepala Seksi', 'kepala');

-- Insert data jenis satwa
INSERT INTO jenis_satwa (nama_satwa) VALUES
('Harimau Jawa'),
('Macan Tutul'),
('Babi Hutan'),
('Gajah'),
('Beruang Madu'),
('Monyet Ekor Panjang'),
('Ular Piton'),
('Buaya'),
('Landak'),
('Lutung'),
('Rusa'),
('Kijang'),
('Kera'),
('Musang'),
('Elang');

-- View statistik bulanan
CREATE VIEW view_statistik_bulanan AS
SELECT 
    DATE_FORMAT(tanggal_laporan, '%Y-%m') AS bulan,
    COUNT(*) AS jumlah_laporan,
    SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) AS selesai,
    SUM(CASE WHEN status = 'proses' THEN 1 ELSE 0 END) AS proses,
    SUM(CASE WHEN status = 'baru' THEN 1 ELSE 0 END) AS baru
FROM laporan_konflik
GROUP BY bulan
ORDER BY bulan DESC;

-- View laporan per kabupaten
CREATE VIEW view_laporan_per_kabupaten AS
SELECT 
    kabupaten,
    COUNT(*) AS total_laporan,
    SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) AS selesai,
    SUM(CASE WHEN prioritas = 'urgent' THEN 1 ELSE 0 END) AS urgent
FROM laporan_konflik
GROUP BY kabupaten
ORDER BY total_laporan DESC;

USE konflik_satwa;

-- Tabel untuk log notifikasi
CREATE TABLE IF NOT EXISTS notifikasi_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    laporan_id INT NOT NULL,
    jenis_notifikasi ENUM('whatsapp', 'email', 'sms') NOT NULL,
    tujuan VARCHAR(100) NOT NULL,
    pesan TEXT NOT NULL,
    status ENUM('pending', 'terkirim', 'gagal') DEFAULT 'pending',
    response TEXT,
    sent_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (laporan_id) REFERENCES laporan_konflik(id) ON DELETE CASCADE,
    INDEX idx_laporan (laporan_id),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Tambah kolom latitude & longitude jika belum ada
ALTER TABLE laporan_konflik 
ADD COLUMN IF NOT EXISTS latitude DECIMAL(10, 8),
ADD COLUMN IF NOT EXISTS longitude DECIMAL(11, 8);

-- View untuk monitoring notifikasi
CREATE OR REPLACE VIEW view_notifikasi_status AS
SELECT 
    DATE(sent_at) as tanggal,
    jenis_notifikasi,
    COUNT(*) as total,
    SUM(CASE WHEN status = 'terkirim' THEN 1 ELSE 0 END) as berhasil,
    SUM(CASE WHEN status = 'gagal' THEN 1 ELSE 0 END) as gagal
FROM notifikasi_log
WHERE sent_at IS NOT NULL
GROUP BY DATE(sent_at), jenis_notifikasi
ORDER BY tanggal DESC;

-- Tampilkan hasil
SELECT 'Tabel notifikasi_log berhasil ditambahkan!' as status;