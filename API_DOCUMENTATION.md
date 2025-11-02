# 📡 API DOCUMENTATION
## Sistem E-Reporting Konflik Satwa - Backend API

---

## 🔗 Base URL
```
http://localhost/konflik-satwa/api/
```

---

## 📋 1. LAPORAN API

### GET /api/laporan.php
Mendapatkan daftar laporan dengan filter

**Parameters:**
- `status` (optional): baru | proses | selesai | monitoring
- `kabupaten` (optional): Nama kabupaten
- `prioritas` (optional): rendah | sedang | tinggi | urgent
- `limit` (optional): Default 100
- `offset` (optional): Default 0

**Example Request:**
```http
GET /api/laporan.php?status=baru&kabupaten=Semarang&limit=10
```

**Example Response:**
```json
{
  "status": "success",
  "message": "Data laporan berhasil diambil",
  "timestamp": "2024-11-02 14:30:00",
  "data": {
    "total": 45,
    "limit": 10,
    "offset": 0,
    "items": [
      {
        "id": 1,
        "nomor_registrasi": "BKSDA/KS/2024/11/0001",
        "tanggal_laporan": "2024-11-01",
        "pelapor_nama": "John Doe",
        "kabupaten": "Semarang",
        "kecamatan": "Ngaliyan",
        "nama_satwa": "Monyet Ekor Panjang",
        "prioritas": "sedang",
        "status": "baru"
      }
    ]
  }
}
```

---

### POST /api/laporan.php
Membuat laporan baru

**Headers:**
```
Content-Type: application/json
```

**Request Body:**
```json
{
  "pelapor_nama": "John Doe",
  "pelapor_telp": "081234567890",
  "kabupaten": "Semarang",
  "kecamatan": "Ngaliyan",
  "desa": "Ngaliyan",
  "jenis_satwa_id": 6,
  "jenis_konflik": "masuk_pemukiman",
  "prioritas": "sedang",
  "kronologi": "Monyet masuk ke rumah warga...",
  "latitude": -7.0537,
  "longitude": 110.3397
}
```

**Example Response:**
```json
{
  "status": "success",
  "message": "Laporan berhasil disimpan",
  "timestamp": "2024-11-02 14:35:00",
  "data": {
    "id": 123,
    "nomor_registrasi": "BKSDA/KS/2024/11/0045"
  }
}
```

---

## 📊 2. STATISTIK API

### GET /api/statistik.php?type=summary
Mendapatkan summary KPI

**Example Response:**
```json
{
  "status": "success",
  "message": "Summary KPI",
  "data": {
    "total_laporan": 150,
    "baru": 25,
    "proses": 30,
    "selesai": 85,
    "monitoring": 10,
    "urgent": 5,
    "tinggi": 15,
    "jumlah_kabupaten": 12,
    "jumlah_jenis_satwa": 8,
    "persentase_selesai": 56.67,
    "persentase_proses": 20.0
  }
}
```

---

### GET /api/statistik.php?type=kabupaten
Statistik per kabupaten

**Example Response:**
```json
{
  "status": "success",
  "message": "Statistik per Kabupaten",
  "data": [
    {
      "kabupaten": "Semarang",
      "total": 45,
      "selesai": 30,
      "proses": 10,
      "baru": 5,
      "urgent": 2
    }
  ]
}
```

---

### GET /api/statistik.php?type=tren
Tren 12 bulan terakhir

**Example Response:**
```json
{
  "status": "success",
  "message": "Tren 12 Bulan Terakhir",
  "data": [
    {
      "bulan": "2024-01",
      "bulan_label": "Jan 2024",
      "jumlah": 12,
      "selesai": 10,
      "urgent": 1
    }
  ]
}
```

---

### GET /api/statistik.php?type=sla
Service Level Agreement

**Example Response:**
```json
{
  "status": "success",
  "message": "SLA Penanganan",
  "data": {
    "rata_waktu_penanganan": 2.5,
    "tercepat": 1,
    "terlama": 7,
    "dalam_sla": 80,
    "lewat_sla": 20,
    "persentase_sla": 80.0,
    "target_sla_hari": 3
  }
}
```

---

## 🗺️ 3. MAP DATA API

### GET /api/map-data.php?type=hotspot
Data marker untuk peta

**Parameters:**
- `status` (optional)
- `prioritas` (optional)
- `kabupaten` (optional)

**Example Response:**
```json
{
  "status": "success",
  "message": "Hotspot data",
  "data": {
    "total": 45,
    "markers": [
      {
        "id": 1,
        "nomor_registrasi": "BKSDA/KS/2024/11/0001",
        "tanggal": "2024-11-01",
        "lokasi": {
          "kabupaten": "Semarang",
          "kecamatan": "Ngaliyan",
          "desa": "Ngaliyan",
          "latitude": -7.0537,
          "longitude": 110.3397
        },
        "satwa": "Monyet Ekor Panjang",
        "jenis_konflik": "masuk_pemukiman",
        "prioritas": "sedang",
        "status": "baru"
      }
    ]
  }
}
```

---

### GET /api/map-data.php?type=heatmap
Data untuk heatmap

**Example Response:**
```json
{
  "status": "success",
  "message": "Heatmap data",
  "data": {
    "total": 150,
    "points": [
      [-7.0537, 110.3397, 0.8],
      [-7.1234, 110.4567, 0.6]
    ]
  }
}
```

---

### GET /api/map-data.php?type=cluster
Data clustering per kabupaten

**Example Response:**
```json
{
  "status": "success",
  "message": "Cluster data",
  "data": {
    "total": 15,
    "clusters": [
      {
        "kabupaten": "Semarang",
        "center": {
          "lat": -7.0537,
          "lng": 110.3397
        },
        "jumlah_kasus": 45,
        "urgent_count": 5,
        "tingkat_kerawanan": "tinggi"
      }
    ]
  }
}
```

---

## 🔐 Error Handling

### Error Response Format:
```json
{
  "status": "error",
  "message": "Deskripsi error",
  "timestamp": "2024-11-02 14:30:00"
}
```

### HTTP Status Codes:
- `200` - Success
- `400` - Bad Request (parameter salah)
- `405` - Method Not Allowed
- `500` - Internal Server Error

---

## 🧪 Testing API

### Using curl:
```bash
# GET Request
curl http://localhost/konflik-satwa/api/statistik.php?type=summary

# POST Request
curl -X POST http://localhost/konflik-satwa/api/laporan.php \
  -H "Content-Type: application/json" \
  -d '{"pelapor_nama":"Test","kabupaten":"Semarang",...}'
```

### Using Browser:
```
http://localhost/konflik-satwa/api/statistik.php?type=summary
http://localhost/konflik-satwa/api/laporan.php?limit=5
http://localhost/konflik-satwa/api/map-data.php?type=hotspot
```

---

## 📝 Notes

1. **Authentication:** Saat ini API belum menggunakan authentication. Untuk production, tambahkan API key atau JWT token.

2. **Rate Limiting:** Belum ada rate limiting. Pertimbangkan menambahkan untuk production.

3. **CORS:** Saat ini Allow-Origin: *. Untuk production, batasi ke domain tertentu.

4. **Caching:** Implementasikan caching untuk query yang berat (Redis/Memcached).

---

**Version:** 1.0  
**Last Updated:** November 2024