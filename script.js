// Konfirmasi sebelum logout
function confirmLogout() {
    return confirm('Yakin ingin keluar dari sistem?');
}

// Konfirmasi sebelum submit form
function confirmSubmit(message) {
    return confirm(message || 'Apakah data sudah benar?');
}

// Auto hide alert setelah 5 detik
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s';
            setTimeout(() => {
                alert.style.display = 'none';
            }, 500);
        }, 5000);
    });
});

// Validasi form laporan
function validateLaporanForm(form) {
    const pelapor = form.querySelector('[name="pelapor_nama"]');
    const kabupaten = form.querySelector('[name="kabupaten"]');
    const kecamatan = form.querySelector('[name="kecamatan"]');
    const desa = form.querySelector('[name="desa"]');
    const jenisSatwa = form.querySelector('[name="jenis_satwa_id"]');
    const kronologi = form.querySelector('[name="kronologi"]');
    
    if (!pelapor.value.trim()) {
        alert('Nama pelapor harus diisi!');
        pelapor.focus();
        return false;
    }
    
    if (!kabupaten.value) {
        alert('Kabupaten harus dipilih!');
        kabupaten.focus();
        return false;
    }
    
    if (!kecamatan.value.trim()) {
        alert('Kecamatan harus diisi!');
        kecamatan.focus();
        return false;
    }
    
    if (!desa.value.trim()) {
        alert('Desa/Kelurahan harus diisi!');
        desa.focus();
        return false;
    }
    
    if (!jenisSatwa.value) {
        alert('Jenis satwa harus dipilih!');
        jenisSatwa.focus();
        return false;
    }
    
    if (!kronologi.value.trim() || kronologi.value.trim().length < 20) {
        alert('Kronologi harus diisi minimal 20 karakter!');
        kronologi.focus();
        return false;
    }
    
    return confirm('Apakah semua data sudah benar? Laporan akan disimpan.');
}

// Format nomor telepon Indonesia
function formatPhoneNumber(input) {
    let value = input.value.replace(/\D/g, '');
    
    // Pastikan dimulai dengan 08 atau 62
    if (value.startsWith('62')) {
        value = '0' + value.substring(2);
    }
    
    // Batasi panjang maksimal 13 digit
    if (value.length > 13) {
        value = value.substring(0, 13);
    }
    
    input.value = value;
}

// Print laporan
function printReport() {
    window.print();
}

// Export table to CSV (sederhana)
function exportTableToCSV(filename) {
    const table = document.querySelector('table');
    if (!table) {
        alert('Tidak ada tabel untuk di-export');
        return;
    }
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        const rowData = [];
        cols.forEach(col => {
            // Hilangkan badge dan ambil text saja
            let text = col.innerText.replace(/\n/g, ' ').trim();
            // Escape double quotes
            text = text.replace(/"/g, '""');
            rowData.push('"' + text + '"');
        });
        csv.push(rowData.join(','));
    });
    
    // Download CSV
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    
    link.setAttribute('href', url);
    link.setAttribute('download', filename || 'laporan.csv');
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Highlight search results
function highlightSearch() {
    const urlParams = new URLSearchParams(window.location.search);
    const searchTerm = urlParams.get('search');
    
    if (searchTerm) {
        const cells = document.querySelectorAll('table td');
        cells.forEach(cell => {
            const text = cell.textContent;
            if (text.toLowerCase().includes(searchTerm.toLowerCase())) {
                cell.style.backgroundColor = '#fff3cd';
            }
        });
    }
}

// Load saat halaman siap
document.addEventListener('DOMContentLoaded', function() {
    highlightSearch();
    
    // Tambahkan event listener untuk phone input
    const phoneInputs = document.querySelectorAll('input[type="tel"]');
    phoneInputs.forEach(input => {
        input.addEventListener('input', function() {
            formatPhoneNumber(this);
        });
    });
    
    // Konfirmasi logout pada link logout
    const logoutLinks = document.querySelectorAll('a[href*="logout"]');
    logoutLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            if (!confirmLogout()) {
                e.preventDefault();
            }
        });
    });
    
    // Validasi form laporan baru
    const laporanForm = document.querySelector('form[action*="submit-laporan"]');
    if (laporanForm) {
        laporanForm.addEventListener('submit', function(e) {
            if (!validateLaporanForm(this)) {
                e.preventDefault();
            }
        });
    }
});