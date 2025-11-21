<?php
// sparepart_dashboard.php

// Sertakan header halaman
require_once 'includes/header.php';

// Cek hak akses
if (get_user_role() !== 'owner' && get_user_role() !== 'admin') {
    header("Location: index.php");
    exit();
}

// --- Logika untuk mengambil data dari database ---

// Tentukan bulan dan tahun saat ini
$bulan_sekarang = date('m');
$tahun_sekarang = date('Y');

// 1. Total Penjualan Sparepart Langsung (Hanya yang Lunas)
$query_penjualan_langsung = "
    SELECT SUM(sk.jumlah * ms.harga_jual) as total_penjualan
    FROM sparepart_keluar sk
    JOIN master_sparepart ms ON sk.code_sparepart = ms.code_sparepart
    WHERE sk.invoice_service LIKE 'DIRECT-%' 
    AND MONTH(sk.tanggal_keluar) = ? 
    AND YEAR(sk.tanggal_keluar) = ?";

$stmt_langsung = $conn->prepare($query_penjualan_langsung);
$stmt_langsung->bind_param("ss", $bulan_sekarang, $tahun_sekarang);
$stmt_langsung->execute();
$result_langsung = $stmt_langsung->get_result()->fetch_assoc();
$penjualan_langsung_lunas = $result_langsung['total_penjualan'] ?? 0;
$stmt_langsung->close();

// 2. Total Penggunaan Sparepart Service (Hanya dari Service yang Lunas) - Untuk perhitungan REVENUE
$query_penggunaan_service = "
    SELECT SUM(sk.jumlah * ms.harga_jual) as total_penggunaan
    FROM sparepart_keluar sk
    JOIN master_sparepart ms ON sk.code_sparepart = ms.code_sparepart
    JOIN service s ON sk.invoice_service = s.invoice
    WHERE sk.invoice_service NOT LIKE 'DIRECT-%' 
    AND MONTH(sk.tanggal_keluar) = ? 
    AND YEAR(sk.tanggal_keluar) = ?
    AND s.status_pembayaran = 'Lunas'"; // Tetap menggunakan Lunas untuk perhitungan pemasukan aktual

$stmt_service = $conn->prepare($query_penggunaan_service);
$stmt_service->bind_param("ss", $bulan_sekarang, $tahun_sekarang);
$stmt_service->execute();
$result_service = $stmt_service->get_result()->fetch_assoc();
$penggunaan_service_lunas = $result_service['total_penggunaan'] ?? 0;
$stmt_service->close();


// 3. Total Nilai Stok Tersedia (Stok Saat Ini)
$query_stok_value = "SELECT SUM(stok_tersedia * harga_beli) as total_value FROM master_sparepart";
$result_stok_value = $conn->query($query_stok_value)->fetch_assoc();
$total_nilai_stok = $result_stok_value['total_value'] ?? 0;


// 4. Hitung Total Pemasukan Sparepart (Lunas)
$total_pemasukan_sparepart = $penjualan_langsung_lunas + $penggunaan_service_lunas;


// 5. Hitung Nilai Modal (Asumsi laba kotor 40% dari penjualan total)
$nilai_modal = $total_pemasukan_sparepart / 1.4; 
$laba_kotor_sparepart = $total_pemasukan_sparepart - $nilai_modal; 
$nilai_modal = $total_pemasukan_sparepart - $laba_kotor_sparepart; 

// --- Data untuk Grafik (Jumlah Transaksi Harian) ---
$jumlah_hari = date('t');
$labels_harian = [];
for ($i = 1; $i <= $jumlah_hari; $i++) {
    $labels_harian[] = $i;
}

$data_harian_transaksi = array_fill(0, $jumlah_hari, 0);

// PERBAIKAN: Logika query grafik sekarang menghitung transaksi Penjualan Langsung (Lunas) 
// DAN Penggunaan Service yang status perbaikannya sudah Selesai (siap tagih/sudah terpakai)
$query_grafik = "
    SELECT 
        DAY(sk.tanggal_keluar) as hari, 
        COUNT(sk.id) as total_harian
    FROM sparepart_keluar sk
    LEFT JOIN service s ON sk.invoice_service = s.invoice
    WHERE MONTH(sk.tanggal_keluar) = ? AND YEAR(sk.tanggal_keluar) = ?
    AND (
        sk.invoice_service LIKE 'DIRECT-%'  /* Penjualan Langsung */
        OR s.status_service = 'Selesai'      /* FIX: Menggunakan kolom status_service */
    )
    GROUP BY DAY(sk.tanggal_keluar)";


$stmt_grafik = $conn->prepare($query_grafik);
$stmt_grafik->bind_param("ss", $bulan_sekarang, $tahun_sekarang);
$stmt_grafik->execute();
$result_grafik = $stmt_grafik->get_result();

while ($row = $result_grafik->fetch_assoc()) {
    $hari = (int)$row['hari'];
    $total = (float)$row['total_harian'];
    $data_harian_transaksi[$hari - 1] = $total;
}
$stmt_grafik->close();

$conn->close();
?>

<!-- Sertakan library Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Konten Utama Halaman -->
<h1 class="page-title">Dashboard Sparepart & Aksesori</h1>

<!-- Kartu Statistik -->
<div class="stats-cards-service">
    <div class="card-service glass-effect" style="--accent-color: var(--accent-success);">
        <div class="card-service-icon"><i class="fas fa-boxes"></i></div>
        <div class="card-service-content">
            <p>Total Nilai Stok (Modal)</p>
            <h3>Rp <?php echo number_format($total_nilai_stok, 0, ',', '.'); ?></h3>
        </div>
    </div>
    <div class="card-service glass-effect" style="--accent-color: var(--accent-primary);">
        <div class="card-service-icon"><i class="fas fa-hand-holding-usd"></i></div>
        <div class="card-service-content">
            <p>Penjualan Langsung (Lunas)</p>
            <h3>Rp <?php echo number_format($penjualan_langsung_lunas, 0, ',', '.'); ?></h3>
        </div>
    </div>
    <div class="card-service glass-effect" style="--accent-color: #ff9500;">
        <div class="card-service-icon"><i class="fas fa-tools"></i></div>
        <div class="card-service-content">
            <p>Penggunaan Service (Lunas)</p>
            <h3>Rp <?php echo number_format($penggunaan_service_lunas, 0, ',', '.'); ?></h3>
        </div>
    </div>
    <div class="card-service glass-effect" style="--accent-color: #34c759;">
        <div class="card-service-icon"><i class="fas fa-chart-line"></i></div>
        <div class="card-service-content">
            <p>Laba Kotor Sparepart (Est.)</p>
            <h3>Rp <?php echo number_format($laba_kotor_sparepart, 0, ',', '.'); ?></h3>
        </div>
    </div>
</div>

<!-- Grafik -->
<div class="chart-container glass-effect">
    <h2 class="chart-title">Grafik Jumlah Transaksi Sparepart (Penjualan Langsung Lunas & Penggunaan Service Selesai) Bulan Ini</h2>
    <div class="chart-wrapper">
        <canvas id="sparepartChart"></canvas>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('sparepartChart').getContext('2d');
    const sparepartChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($labels_harian); ?>,
            datasets: [{
                label: 'Jumlah Transaksi Harian',
                data: <?php echo json_encode($data_harian_transaksi); ?>,
                backgroundColor: 'rgba(52, 199, 89, 0.4)',
                borderColor: 'rgba(52, 199, 89, 1)',
                borderWidth: 2,
                tension: 0.4,
                fill: true,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: '#636366',
                        precision: 0 // Pastikan sumbu Y adalah bilangan bulat (jumlah transaksi)
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
                                label += new Intl.NumberFormat('id-ID').format(context.parsed.y) + ' Transaksi';
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