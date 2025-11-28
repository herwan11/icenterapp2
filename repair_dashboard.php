<?php
// repair_dashboard.php

// Sertakan header halaman
require_once 'includes/header.php';

// --- Logika untuk mengambil data dari database ---

// Tentukan bulan dan tahun saat ini
$bulan_sekarang = date('m');
$tahun_sekarang = date('Y');
$jumlah_hari = date('t'); // Mendapatkan jumlah hari dalam bulan ini

// 1. Hitung Penghasilan Service (bulanan) - HANYA YANG STATUSNYA 'Diambil' DAN 'Lunas'
// PERBAIKAN LOGIKA: Menambahkan filter status_service = 'Diambil'
$query_service_bulanan = "SELECT SUM(sub_total) as total_service 
                          FROM service 
                          WHERE MONTH(tanggal) = ? 
                          AND YEAR(tanggal) = ? 
                          AND status_pembayaran = 'Lunas' 
                          AND status_service = 'Diambil'"; // Filter baru

$stmt_service_bulanan = $conn->prepare($query_service_bulanan);
$stmt_service_bulanan->bind_param("ss", $bulan_sekarang, $tahun_sekarang);
$stmt_service_bulanan->execute();
$result_service_bulanan = $stmt_service_bulanan->get_result()->fetch_assoc();
$penghasilan_service = $result_service_bulanan['total_service'] ?? 0;
$stmt_service_bulanan->close();

// 2. Hitung Penggunaan Sparepart Toko (bulanan) - (Tetap menggunakan logika penjualan sparepart global untuk saat ini)
// Jika ingin lebih spesifik, bisa difilter juga, tapi biasanya penjualan sparepart langsung dianggap 'selesai' saat lunas.
$query_sparepart = "SELECT SUM(total) as total_sparepart FROM penjualan_sparepart WHERE MONTH(tanggal) = ? AND YEAR(tanggal) = ?";
$stmt_sparepart = $conn->prepare($query_sparepart);
$stmt_sparepart->bind_param("ss", $bulan_sekarang, $tahun_sekarang);
$stmt_sparepart->execute();
$result_sparepart = $stmt_sparepart->get_result()->fetch_assoc();
$penggunaan_sparepart_toko = $result_sparepart['total_sparepart'] ?? 0;
$stmt_sparepart->close();

// 3. Pembelian Sparepart Luar (data statis - nanti bisa diganti query)
$pembelian_sparepart_luar = 200000;

// 4. Hitung Laba
$laba = ($penghasilan_service + $penggunaan_sparepart_toko) - $pembelian_sparepart_luar;

// --- Data untuk Grafik (Dinamis per Hari) - HANYA YANG STATUSNYA 'Diambil' DAN 'Lunas' ---
$labels_harian = [];
for ($i = 1; $i <= $jumlah_hari; $i++) {
    $labels_harian[] = $i;
}

$data_harian = array_fill(0, $jumlah_hari, 0); // Buat array berisi 0 sebanyak jumlah hari

// PERBAIKAN LOGIKA GRAFIK: Menambahkan filter status_service = 'Diambil'
$query_grafik = "SELECT DAY(tanggal) as hari, SUM(sub_total) as total_harian 
                 FROM service 
                 WHERE MONTH(tanggal) = ? 
                 AND YEAR(tanggal) = ? 
                 AND status_pembayaran = 'Lunas'
                 AND status_service = 'Diambil' 
                 GROUP BY DAY(tanggal)";

$stmt_grafik = $conn->prepare($query_grafik);
$stmt_grafik->bind_param("ss", $bulan_sekarang, $tahun_sekarang);
$stmt_grafik->execute();
$result_grafik = $stmt_grafik->get_result();

while ($row = $result_grafik->fetch_assoc()) {
    // Masukkan data ke array pada indeks yang benar (hari - 1)
    $hari = (int)$row['hari'];
    $total = (float)$row['total_harian'];
    $data_harian[$hari - 1] = $total;
}
$stmt_grafik->close();
?>

<!-- Sertakan library Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Konten Utama Halaman -->
<h1 class="page-title">Service Dashboard</h1>

<!-- Kartu Statistik -->
<div class="stats-cards-service">
    <div class="card-service glass-effect" style="--accent-color: var(--accent-danger);">
        <div class="card-service-icon"><i class="fas fa-tools"></i></div>
        <div class="card-service-content">
            <p>Penggunaan Sparepart Toko</p>
            <h3>Rp <?php echo number_format($penggunaan_sparepart_toko, 0, ',', '.'); ?></h3>
        </div>
    </div>
    <div class="card-service glass-effect" style="--accent-color: var(--accent-warning);">
        <div class="card-service-icon"><i class="fas fa-shopping-cart"></i></div>
        <div class="card-service-content">
            <p>Pembelian Sparepart Luar</p>
            <h3>Rp <?php echo number_format($pembelian_sparepart_luar, 0, ',', '.'); ?></h3>
        </div>
    </div>
    <div class="card-service glass-effect" style="--accent-color: var(--accent-primary);">
        <div class="card-service-icon"><i class="fas fa-hand-holding-usd"></i></div>
        <div class="card-service-content">
            <p>Penghasilan Service (Diambil & Lunas)</p>
            <h3>Rp <?php echo number_format($penghasilan_service, 0, ',', '.'); ?></h3>
        </div>
    </div>
    <div class="card-service glass-effect" style="--accent-color: var(--accent-success);">
        <div class="card-service-icon"><i class="fas fa-dollar-sign"></i></div>
        <div class="card-service-content">
            <p>Laba</p>
            <h3>Rp <?php echo number_format($laba, 0, ',', '.'); ?></h3>
        </div>
    </div>
</div>

<!-- Grafik -->
<div class="chart-container glass-effect">
    <h2 class="chart-title">Grafik Service (Status: Diambil & Lunas) Bulan Ini</h2>
    <div class="chart-wrapper">
        <canvas id="serviceChart"></canvas>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('serviceChart').getContext('2d');
    const serviceChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($labels_harian); ?>,
            datasets: [{
                label: 'Penghasilan Service Harian',
                data: <?php echo json_encode($data_harian); ?>,
                backgroundColor: 'rgba(0, 122, 255, 0.6)',
                borderColor: 'rgba(0, 122, 255, 1)',
                borderWidth: 1,
                borderRadius: 5,
                barThickness: 20,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: '#636366'
                    },
                    grid: {
                        color: 'rgba(0,0,0,0.05)'
                    }
                },
                x: {
                    ticks: {
                        color: '#636366'
                    },
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(context.parsed.y);
                            }
                            return label;
                        }
                    }
                }
            }
        }
    });
});
</script>

<?php
// Sertakan footer halaman
require_once 'includes/footer.php';
?>