// Fungsi untuk menampilkan loading spinner
function showLoading() {
    const loading = document.createElement('div');
    loading.id = 'loading-overlay';
    loading.innerHTML = `
        <div class="loading-spinner">
            <div class="spinner"></div>
            <p>Memproses...</p>
        </div>
    `;
    document.body.appendChild(loading);
}

// Fungsi untuk menyembunyikan loading spinner
function hideLoading() {
    const loading = document.getElementById('loading-overlay');
    if (loading) {
        loading.remove();
    }
}

// Tangani semua form dengan AJAX
document.addEventListener('DOMContentLoaded', function() {
    // Tangani submit form
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            if (this.classList.contains('no-ajax')) return;
            
            e.preventDefault();
            showLoading();
            
            const formData = new FormData(this);
            const action = this.getAttribute('action') || window.location.href;
            const method = this.getAttribute('method') || 'POST';
            
            fetch(action, {
                method: method,
                body: formData
            })
            .then(response => {
                if (response.redirected) {
                    window.location.href = response.url;
                } else {
                    return response.text();
                }
            })
            .then(data => {
                if (data) {
                    // Jika ada data yang dikembalikan (bukan redirect)
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(data, 'text/html');
                    
                    // Ganti konten utama dengan konten baru
                    const newContent = doc.querySelector('main');
                    if (newContent) {
                        document.querySelector('main').innerHTML = newContent.innerHTML;
                    }
                    
                    // Perbarui judul halaman
                    const newTitle = doc.querySelector('title');
                    if (newTitle) {
                        document.title = newTitle.textContent;
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan. Silakan coba lagi.');
            })
            .finally(() => {
                hideLoading();
            });
        });
    });
    
    // Tangani klik pagination
    document.querySelectorAll('.pagination a').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            showLoading();
            
            fetch(this.href)
                .then(response => response.text())
                .then(data => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(data, 'text/html');
                    const newContent = doc.querySelector('main');
                    
                    if (newContent) {
                        document.querySelector('main').innerHTML = newContent.innerHTML;
                        document.title = doc.querySelector('title').textContent;
                        
                        // Scroll ke atas
                        window.scrollTo(0, 0);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan. Silakan coba lagi.');
                })
                .finally(() => {
                    hideLoading();
                });
        });
    });
    
    // Inisialisasi tooltip
    tippy('[data-tippy-content]', {
        arrow: true,
        animation: 'fade',
    });
    
    // Tangani tombol print
    document.querySelectorAll('.btn-print').forEach(btn => {
        btn.addEventListener('click', function() {
            window.print();
        });
    });
    
    // Tangani tombol export PDF
    document.querySelectorAll('.btn-export-pdf').forEach(btn => {
        btn.addEventListener('click', function() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            // Judul
            doc.setFontSize(18);
            doc.text('Laporan Toko Izra Fashion', 105, 15, { align: 'center' });
            
            // Tanggal
            doc.setFontSize(12);
            doc.text(`Dibuat pada: ${new Date().toLocaleDateString()}`, 105, 25, { align: 'center' });
            
            // Data tabel
            const table = this.closest('.table-container').querySelector('table');
            const rows = table.querySelectorAll('tbody tr');
            const headers = table.querySelectorAll('thead th');
            
            let y = 35;
            doc.setFontSize(10);
            
            // Header tabel
            doc.setFillColor(200, 200, 200);
            doc.rect(10, y, 190, 10, 'F');
            
            let x = 15;
            headers.forEach((header, index) => {
                const width = index === 0 ? 15 : 40;
                doc.text(header.textContent.trim(), x, y + 7);
                x += width;
            });
            
            y += 10;
            
            // Isi tabel
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                x = 15;
                
                cells.forEach((cell, index) => {
                    const width = index === 0 ? 15 : 40;
                    doc.text(cell.textContent.trim(), x, y + 7);
                    x += width;
                });
                
                y += 10;
                
                // Tambah halaman baru jika mencapai batas bawah
                if (y > 280) {
                    doc.addPage();
                    y = 20;
                }
            });
            
            // Simpan PDF
            doc.save('laporan_izra_fashion.pdf');
        });
    });
});

// Fungsi untuk menampilkan modal
function showModal(title, content) {
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h2>${title}</h2>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">${content}</div>
            <div class="modal-footer">
                <button class="btn btn-cancel modal-close">Tutup</button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    modal.style.display = 'flex';
    
    // Tangani klik tombol close
    modal.querySelectorAll('.modal-close').forEach(btn => {
        btn.addEventListener('click', () => {
            modal.style.display = 'none';
            setTimeout(() => modal.remove(), 300);
        });
    });
}

// Fungsi untuk memuat konten via AJAX ke dalam modal
function loadModalContent(url, title) {
    showLoading();
    
    fetch(url)
        .then(response => response.text())
        .then(data => {
            showModal(title, data);
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan. Silakan coba lagi.');
        })
        .finally(() => {
            hideLoading();
        });
}