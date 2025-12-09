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

$bulan_sekarang = date('m');
$tahun_sekarang = date('Y');

// --- INDIKATOR KEUANGAN (Dipindahkan dari Repair Dashboard) ---

// 1. Sparepart Toko (Internal) - Jual & Beli
$query_internal = "
    SELECT 
        SUM(sk.jumlah * ms.harga_jual) as total_jual_internal,
        SUM(sk.jumlah * ms.harga_beli) as total_beli_internal
    FROM sparepart_keluar sk
    JOIN master_sparepart ms ON sk.code_sparepart = ms.code_sparepart
    /* Filter: Mencakup Penjualan Langsung & Penggunaan Service */
    /* Untuk service, kita ambil yang statusnya selesai/diambil agar valid sebagai penjualan */
    LEFT JOIN service s ON sk.invoice_service = s.invoice
    WHERE MONTH(sk.tanggal_keluar) = ? 
    AND YEAR(sk.tanggal_keluar) = ?
    AND (
        sk.invoice_service LIKE 'DIRECT-%' /* Penjualan Langsung */
        OR (s.status_service = 'Diambil' AND s.status_pembayaran = 'Lunas') /* Service Selesai */
    )";

$stmt_internal = $conn->prepare($query_internal);
$stmt_internal->bind_param("ss", $bulan_sekarang, $tahun_sekarang);
$stmt_internal->execute();
$res_internal = $stmt_internal->get_result()->fetch_assoc();

$part_toko_jual = $res_internal['total_jual_internal'] ?? 0;
$part_toko_modal = $res_internal['total_beli_internal'] ?? 0;
$stmt_internal->close();


// 2. Sparepart Luar (Eksternal) - Jual & Beli
// Ambil Total Jual (Omset)
$query_ext_jual = "
    SELECT SUM(psl.total_jual) as total_jual_luar 
    FROM pembelian_sparepart_luar psl
    JOIN service s ON psl.invoice_service = s.invoice
    WHERE MONTH(s.tanggal) = ? 
    AND YEAR(s.tanggal) = ?
    AND s.status_service = 'Diambil'
    AND s.status_pembayaran = 'Lunas'";

$stmt_ext_jual = $conn->prepare($query_ext_jual);
$stmt_ext_jual->bind_param("ss", $bulan_sekarang, $tahun_sekarang);
$stmt_ext_jual->execute();
$part_luar_jual = $stmt_ext_jual->get_result()->fetch_assoc()['total_jual_luar'] ?? 0;
$stmt_ext_jual->close();

// Ambil Total Beli (Modal) dari Transaksi Kas
$query_ext_beli = "
    SELECT SUM(jumlah) as total_modal_luar
    FROM transaksi_kas
    WHERE jenis = 'keluar'
    AND keterangan LIKE 'Beli sparepart luar%'
    AND MONTH(tanggal) = ? 
    AND YEAR(tanggal) = ?";

$stmt_ext_beli = $conn->prepare($query_ext_beli);
$stmt_ext_beli->bind_param("ss", $bulan_sekarang, $tahun_sekarang);
$stmt_ext_beli->execute();
$part_luar_modal = $stmt_ext_beli->get_result()->fetch_assoc()['total_modal_luar'] ?? 0;
$stmt_ext_beli->close();


// --- INDIKATOR LAIN (Yang sudah ada) ---
// Total Nilai Stok Tersedia
$query_stok_value = "SELECT SUM(stok_tersedia * harga_beli) as total_value FROM master_sparepart";
$result_stok_value = $conn->query($query_stok_value)->fetch_assoc();
$total_nilai_stok = $result_stok_value['total_value'] ?? 0;


// --- Data Grafik Transaksi ---
$jumlah_hari = date('t');
$labels_harian = [];
for ($i = 1; $i <= $jumlah_hari; $i++) {
    $labels_harian[] = $i;
}
$data_harian_transaksi = array_fill(0, $jumlah_hari, 0);

$query_grafik = "
    SELECT DAY(sk.tanggal_keluar) as hari, COUNT(sk.id) as total_harian
    FROM sparepart_keluar sk
    WHERE MONTH(sk.tanggal_keluar) = ? AND YEAR(sk.tanggal_keluar) = ?
    GROUP BY DAY(sk.tanggal_keluar)";

$stmt_grafik = $conn->prepare($query_grafik);
$stmt_grafik->bind_param("ss", $bulan_sekarang, $tahun_sekarang);
$stmt_grafik->execute();
$result_grafik = $stmt_grafik->get_result();
while ($row = $result_grafik->fetch_assoc()) {
    $data_harian_transaksi[(int)$row['hari'] - 1] = (int)$row['total_harian'];
}
$stmt_grafik->close();
?>

<!-- Sertakan library Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Konten Utama Halaman -->
<h1 class="page-title">Dashboard Bisnis Sparepart</h1>

<!-- Kartu Statistik -->
<div class="stats-cards-service">
    
    <!-- Sparepart Toko (Jual) -->
    <div class="card-service glass-effect" style="--accent-color: var(--accent-primary);">
        <div class="card-service-icon"><i class="fas fa-store"></i></div>
        <div class="card-service-content">
            <p>Penjualan Part Toko</p>
            <h3>Rp <?php echo number_format($part_toko_jual, 0, ',', '.'); ?></h3>
            <small style="color:#888;">Modal: Rp <?php echo number_format($part_toko_modal, 0, ',', '.'); ?></small>
        </div>
    </div>

    <!-- Sparepart Luar (Jual) -->
    <div class="card-service glass-effect" style="--accent-color: #ff9500;">
        <div class="card-service-icon"><i class="fas fa-external-link-alt"></i></div>
        <div class="card-service-content">
            <p>Penjualan Part Luar</p>
            <h3>Rp <?php echo number_format($part_luar_jual, 0, ',', '.'); ?></h3>
            <small style="color:#888;">Modal: Rp <?php echo number_format($part_luar_modal, 0, ',', '.'); ?></small>
        </div>
    </div>

    <!-- Nilai Aset Stok -->
    <div class="card-service glass-effect" style="--accent-color: #5856d6;">
        <div class="card-service-icon"><i class="fas fa-cubes"></i></div>
        <div class="card-service-content">
            <p>Nilai Aset Stok (Modal)</p>
            <h3>Rp <?php echo number_format($total_nilai_stok, 0, ',', '.'); ?></h3>
        </div>
    </div>
</div>

<!-- Grafik -->
<div class="chart-container glass-effect">
    <h2 class="chart-title">Grafik Volume Transaksi Sparepart (Harian)</h2>
    <div class="chart-wrapper">
        <canvas id="sparepartChart"></canvas>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('sparepartChart').getContext('2d');
    const sparepartChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($labels_harian); ?>,
            datasets: [{
                label: 'Jumlah Transaksi',
                data: <?php echo json_encode($data_harian_transaksi); ?>,
                backgroundColor: 'rgba(52, 199, 89, 0.6)',
                borderColor: 'rgba(52, 199, 89, 1)',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0, color: '#636366' },
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                x: {
                    ticks: { color: '#636366' },
                    grid: { display: false }
                }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
});
</script>

<?php
// Sertakan footer halaman
require_once 'includes/footer.php';
?>