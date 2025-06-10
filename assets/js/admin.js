document.addEventListener('DOMContentLoaded', function() {
    // Inisialisasi chart
    if (document.getElementById('salesChart')) {
        initSalesChart();
    }
    
    // Tangani tab
    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            
            // Update active tab
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            // Update active content
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            document.getElementById(tabId).classList.add('active');
        });
    });
    
    // Tangani tombol hapus dengan konfirmasi
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!confirm('Apakah Anda yakin ingin menghapus item ini?')) {
                e.preventDefault();
            }
        });
    });
    
    // Tangani preview gambar sebelum upload
    document.querySelectorAll('input[type="file"]').forEach(input => {
        input.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = input.closest('.file-upload').querySelector('.image-preview');
                    if (preview) {
                        preview.innerHTML = `<img src="${e.target.result}" alt="Preview" style="max-width: 100%; max-height: 200px;">`;
                    }
                }
                reader.readAsDataURL(file);
            }
        });
    });
    
    // Tangani tombol cetak laporan
    document.querySelectorAll('.btn-print-report').forEach(btn => {
        btn.addEventListener('click', function() {
            const reportType = this.getAttribute('data-report');
            const params = new URLSearchParams(window.location.search);
            
            let url = `reports.php?action=print&type=${reportType}`;
            params.forEach((value, key) => {
                if (key !== 'action' && key !== 'type') {
                    url += `&${key}=${value}`;
                }
            });
            
            window.open(url, '_blank');
        });
    });
});

function initSalesChart() {
    const ctx = document.getElementById('salesChart').getContext('2d');
    const labels = JSON.parse(document.getElementById('salesChart').getAttribute('data-labels'));
    const data = JSON.parse(document.getElementById('salesChart').getAttribute('data-values'));
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Penjualan Harian',
                data: data,
                backgroundColor: 'rgba(106, 27, 154, 0.1)',
                borderColor: 'rgba(106, 27, 154, 1)',
                borderWidth: 2,
                tension: 0.1,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Rp ' + context.raw.toLocaleString('id-ID');
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + value.toLocaleString('id-ID');
                        }
                    }
                }
            }
        }
    });
}

// Fungsi untuk mengupdate rekomendasi produk
function updateProductRecommendations() {
    showLoading();
    
    fetch('recommendations.php?action=update', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Rekomendasi produk berhasil diperbarui!');
            window.location.reload();
        } else {
            alert('Gagal memperbarui rekomendasi: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan. Silakan coba lagi.');
    })
    .finally(() => {
        hideLoading();
    });
}