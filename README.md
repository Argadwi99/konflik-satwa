# 🐾 Sistem E-Reporting Konflik Satwa BKSDA Jawa Tengah

Sistem informasi berbasis web untuk mengelola laporan konflik satwa liar dengan masyarakat di wilayah Jawa Tengah.

## 📋 Fitur Utama

✅ **Multi-User Access**
- Admin: Full akses ke semua fitur
- Petugas Lapangan: Input & update laporan
- Kepala Seksi: Monitoring & review

✅ **Manajemen Laporan**
- Form input laporan dengan nomor registrasi otomatis
- Detail lokasi konflik (Kabupaten, Kecamatan, Desa)
- Jenis satwa dan jenis konflik
- Prioritas laporan (Rendah, Sedang, Tinggi, Urgent)

✅ **Tracking & Monitoring**
- Status laporan (Baru, Proses, Selesai, Monitoring)
- Riwayat tindak lanjut per laporan
- Dashboard statistik real-time
- Filter & pencarian laporan

✅ **Reporting**
- Export data ke CSV/Excel
- Print detail laporan
- Nomor registrasi format: BKSDA/KS/YYYY/MM/NNNN

## 🛠️ Teknologi

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Server**: Apache (XAMPP)

## 📦 Instalasi

### 1. Install XAMPP
Download dan install XAMPP dari [https://www.apachefriends.org/](https://www.apachefriends.org/)

### 2. Setup Database
1. Buka phpMyAdmin: `http://localhost/phpmyadmin`
2. Buat database baru: `konflik_satwa`
3. Import file `database.sql`
4. Database sudah siap dengan data sample users

### 3. Setup Aplikasi
1. Copy folder `konflik-satwa` ke `C:\xampp\htdocs\`
2. Pastikan struktur folder sesuai:
```
htdocs/
└── konflik-satwa/
    ├── config/
    ├── assets/
    ├── pages/
    ├── process/
    ├── index.php
    └── ...
```

### 4. Konfigurasi Database
Edit file `config/database.php` jika perlu:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'konflik_satwa');
```

### 5. Akses Aplikasi
Buka browser dan akses: `http://localhost/konflik-satwa`

## 👤 Akun Default

| Role | Username | Password |
|------|----------|----------|
| Admin | admin | admin123 |
| Petugas | petugas1 | petugas123 |
| Kepala Seksi | kepala | kepala123 |

**⚠️ PENTING**: Ganti password default setelah instalasi!

## 📁 Struktur File

```
konflik-satwa/
├── config/
│   └── database.php          # Konfigurasi koneksi database
├── assets/
│   ├── css/
│   │   └── style.css         # Styling aplikasi
│   └── js/
│       └── script.js         # JavaScript functions
├── pages/
│   ├── dashboard.php         # Halaman dashboard
│   ├── laporan-baru.php      # Form input laporan
│   ├── daftar-laporan.php    # List semua laporan
│   └── detail-laporan.php    # Detail & update laporan
├── process/
│   ├── login-process.php     # Proses login
│   ├── submit-laporan.php    # Proses simpan laporan
│   └── update-status.php     # Proses update status
├── index.php                  # Halaman login
├── logout.php                 # Proses logout
├── .htaccess                  # Security & URL rewrite
└── README.md                  # Dokumentasi ini
```

## 🔒 Keamanan

- Password di-hash menggunakan MD5 (untuk demo, production sebaiknya gunakan bcrypt)
- Session management untuk autentikasi
- SQL injection protection dengan `mysqli_real_escape_string()`
- XSS protection dengan `htmlspecialchars()`
- `.htaccess` untuk proteksi file config
- Role-based access control

## 📊 Database Schema

### Tabel Utama:
- **users**: Data user sistem
- **jenis_satwa**: Master data jenis satwa
- **laporan_konflik**: Data laporan konflik
- **tindak_lanjut**: Riwayat tindak lanjut

### View:
- **view_statistik_bulanan**: Agregasi laporan per bulan
- **view_laporan_per_kabupaten**: Statistik per kabupaten

## 🚀 Pengembangan Selanjutnya

Fitur yang bisa ditambahkan:
- [ ] Upload foto/video bukti konflik
- [ ] Integrasi Google Maps untuk lokasi GPS
- [ ] Notifikasi email/WhatsApp otomatis
- [ ] Dashboard analytics dengan chart (Chart.js)
- [ ] Export PDF report
- [ ] API untuk integrasi mobile app
- [ ] Multi-bahasa (Indonesia & English)

## 🐛 Troubleshooting

### Error: "Connection failed"
- Pastikan MySQL di XAMPP sudah running
- Cek kredensial di `config/database.php`

### Error: "404 Not Found"
- Pastikan folder di `htdocs/konflik-satwa`
- Cek URL: `http://localhost/konflik-satwa` (bukan konflik_satwa)

### Tidak bisa login
- Pastikan database sudah di-import
- Cek tabel `users` ada data atau tidak

### CSS/JS tidak load
- Periksa path file di tag `<link>` dan `<script>`
- Clear browser cache (Ctrl + F5)

## 📝 Changelog

### Version 1.0.0 (2024-11)
- Initial release
- Login multi-role
- CRUD laporan konflik
- Dashboard statistik
- Export CSV
- Print laporan

## 📞 Support

Untuk pertanyaan dan bantuan:
- Email: admin@bksda-jateng.go.id
- Website: https://www.bksda-jateng.go.id

## 📄 License

Sistem ini dikembangkan untuk BKSDA Jawa Tengah.
© 2024 BKSDA Jawa Tengah. All rights reserved.

---

**Dibuat dengan ❤️ untuk konservasi satwa liar Indonesia**