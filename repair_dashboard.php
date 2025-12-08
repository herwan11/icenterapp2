<?php
// repair_dashboard.php

// Sertakan header halaman
require_once 'includes/header.php';

// --- Logika untuk mengambil data dari database ---

$bulan_sekarang = date('m');
$tahun_sekarang = date('Y');
$jumlah_hari = date('t'); 

// 1. Hitung Jumlah Qty Sparepart Toko Terpakai (Internal)
// Status Service: Diambil (Selesai), Pembayaran: Lunas (Opsional, tapi biasanya barang keluar kalau lunas/selesai)
$query_qty_internal = "
    SELECT SUM(sk.jumlah) as total_qty_internal
    FROM sparepart_keluar sk
    JOIN service s ON sk.invoice_service = s.invoice
    WHERE MONTH(s.tanggal) = ? 
    AND YEAR(s.tanggal) = ?
    AND s.status_service = 'Diambil'";

$stmt_qty_internal = $conn->prepare($query_qty_internal);
$stmt_qty_internal->bind_param("ss", $bulan_sekarang, $tahun_sekarang);
$stmt_qty_internal->execute();
$qty_internal = $stmt_qty_internal->get_result()->fetch_assoc()['total_qty_internal'] ?? 0;
$stmt_qty_internal->close();


// 2. Hitung Jumlah Qty Sparepart Luar Terpakai (Eksternal)
$query_qty_external = "
    SELECT SUM(psl.jumlah) as total_qty_external 
    FROM pembelian_sparepart_luar psl
    JOIN service s ON psl.invoice_service = s.invoice
    WHERE MONTH(s.tanggal) = ? 
    AND YEAR(s.tanggal) = ?
    AND s.status_service = 'Diambil'";

$stmt_qty_external = $conn->prepare($query_qty_external);
$stmt_qty_external->bind_param("ss", $bulan_sekarang, $tahun_sekarang);
$stmt_qty_external->execute();
$qty_external = $stmt_qty_external->get_result()->fetch_assoc()['total_qty_external'] ?? 0;
$stmt_qty_external->close();


// 3. Penghasilan Service (Murni Jasa) - Tetap dipertahankan sebagai indikator kinerja utama teknisi/toko
$query_total_invoice = "
    SELECT SUM(sub_total) as total_invoice 
    FROM service 
    WHERE MONTH(tanggal) = ? 
    AND YEAR(tanggal) = ? 
    AND status_service = 'Diambil'
    AND status_pembayaran = 'Lunas'";

$stmt_invoice = $conn->prepare($query_total_invoice);
$stmt_invoice->bind_param("ss", $bulan_sekarang, $tahun_sekarang);
$stmt_invoice->execute();
$total_nilai_invoice = $stmt_invoice->get_result()->fetch_assoc()['total_invoice'] ?? 0;
$stmt_invoice->close();

// Hitung total nilai part (Jual) untuk pengurang jasa
// Part Internal (Jual)
$query_val_int = "SELECT SUM(sk.jumlah * ms.harga_jual) as val FROM sparepart_keluar sk JOIN master_sparepart ms ON sk.code_sparepart = ms.code_sparepart JOIN service s ON sk.invoice_service = s.invoice WHERE MONTH(s.tanggal) = ? AND YEAR(s.tanggal) = ? AND s.status_service = 'Diambil' AND s.status_pembayaran = 'Lunas'";
$stmt_val_int = $conn->prepare($query_val_int); $stmt_val_int->bind_param("ss", $bulan_sekarang, $tahun_sekarang); $stmt_val_int->execute();
$val_int = $stmt_val_int->get_result()->fetch_assoc()['val'] ?? 0;

// Part Eksternal (Jual)
$query_val_ext = "SELECT SUM(psl.total_jual) as val FROM pembelian_sparepart_luar psl JOIN service s ON psl.invoice_service = s.invoice WHERE MONTH(s.tanggal) = ? AND YEAR(s.tanggal) = ? AND s.status_service = 'Diambil' AND s.status_pembayaran = 'Lunas'";
$stmt_val_ext = $conn->prepare($query_val_ext); $stmt_val_ext->bind_param("ss", $bulan_sekarang, $tahun_sekarang); $stmt_val_ext->execute();
$val_ext = $stmt_val_ext->get_result()->fetch_assoc()['val'] ?? 0;

$penghasilan_jasa_service = $total_nilai_invoice - ($val_int + $val_ext);


// 4. Data Teknisi & Jumlah Service
$teknisi_stats = [];
$query_teknisi = "
    SELECT k.nama, COUNT(s.invoice) as total_job
    FROM service s
    JOIN karyawan k ON s.teknisi_id = k.id
    WHERE MONTH(s.tanggal) = ? AND YEAR(s.tanggal) = ?
    AND s.status_service = 'Diambil'
    GROUP BY k.nama
    ORDER BY total_job DESC";

$stmt_tech = $conn->prepare($query_teknisi);
$stmt_tech->bind_param("ss", $bulan_sekarang, $tahun_sekarang);
$stmt_tech->execute();
$res_tech = $stmt_tech->get_result();
while($row = $res_tech->fetch_assoc()) {
    $teknisi_stats[] = $row;
}
$stmt_tech->close();


// --- Data untuk Grafik ---
$labels_harian = [];
for ($i = 1; $i <= $jumlah_hari; $i++) {
    $labels_harian[] = $i;
}
$data_harian = array_fill(0, $jumlah_hari, 0);

$query_grafik = "SELECT DAY(tanggal) as hari, COUNT(invoice) as total_harian 
                 FROM service 
                 WHERE MONTH(tanggal) = ? 
                 AND YEAR(tanggal) = ? 
                 AND status_service = 'Diambil' 
                 GROUP BY DAY(tanggal)";

$stmt_grafik = $conn->prepare($query_grafik);
$stmt_grafik->bind_param("ss", $bulan_sekarang, $tahun_sekarang);
$stmt_grafik->execute();
$result_grafik = $stmt_grafik->get_result();

while ($row = $result_grafik->fetch_assoc()) {
    $hari = (int)$row['hari'];
    $data_harian[$hari - 1] = (int)$row['total_harian'];
}
$stmt_grafik->close();
?>

<!-- Sertakan library Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Style Tambahan untuk Scrollable List -->
<style>
    .tech-list-container {
        max-height: 300px;
        overflow-y: auto;
        padding-right: 10px;
    }
    .tech-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px;
        border-bottom: 1px solid #f0f0f0;
    }
    .tech-item:last-child { border-bottom: none; }
    .tech-name { font-weight: 600; color: #333; }
    .tech-count { 
        background: var(--accent-primary); 
        color: white; 
        padding: 4px 10px; 
        border-radius: 12px; 
        font-size: 12px; 
        font-weight: 600; 
    }
</style>

<!-- Konten Utama Halaman -->
<h1 class="page-title">Service Dashboard (Operasional)</h1>

<div class="content-container" style="display:flex; gap:24px; flex-wrap:wrap;">
    
    <!-- Kolom Kiri: Statistik Utama -->
    <div style="flex: 2; min-width: 300px;">
        <!-- Kartu Statistik Operasional -->
        <div class="stats-cards-service" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
            <!-- 1. Qty Sparepart Toko -->
            <div class="card-service glass-effect" style="--accent-color: #ff9500;">
                <div class="card-service-icon"><i class="fas fa-box-open"></i></div>
                <div class="card-service-content">
                    <p>Sparepart Toko Terpakai</p>
                    <h3><?php echo number_format($qty_internal); ?> <span style="font-size:14px; font-weight:400; color:#666;">Pcs</span></h3>
                </div>
            </div>

            <!-- 2. Qty Sparepart Luar -->
            <div class="card-service glass-effect" style="--accent-color: #5856d6;">
                <div class="card-service-icon"><i class="fas fa-truck-loading"></i></div>
                <div class="card-service-content">
                    <p>Sparepart Luar Terpakai</p>
                    <h3><?php echo number_format($qty_external); ?> <span style="font-size:14px; font-weight:400; color:#666;">Pcs</span></h3>
                </div>
            </div>

            <!-- 3. Jasa Service (Tetap ada sebagai indikator nilai kerja) -->
            <div class="card-service glass-effect" style="--accent-color: var(--accent-primary);">
                <div class="card-service-icon"><i class="fas fa-wrench"></i></div>
                <div class="card-service-content">
                    <p>Total Nilai Jasa Service</p>
                    <h3>Rp <?php echo number_format($penghasilan_jasa_service, 0, ',', '.'); ?></h3>
                </div>
            </div>
        </div>

        <!-- Grafik Jumlah Service -->
        <div class="chart-container glass-effect" style="margin-top: 24px;">
            <h2 class="chart-title">Grafik Unit Selesai (Harian)</h2>
            <div class="chart-wrapper">
                <canvas id="serviceChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Performa Teknisi -->
    <div style="flex: 1; min-width: 250px;">
        <div class="card glass-effect" style="height: 100%;">
            <div class="card-header" style="padding: 20px; border-bottom: 1px solid #eee;">
                <h3 style="margin:0; font-size:18px;">Performa Teknisi</h3>
                <p style="color:#888; font-size:13px; margin-top:4px;">Unit selesai bulan ini</p>
            </div>
            <div class="card-body tech-list-container">
                <?php if (empty($teknisi_stats)): ?>
                    <div style="text-align:center; padding:20px; color:#999;">Belum ada data service.</div>
                <?php else: ?>
                    <?php foreach ($teknisi_stats as $tech): ?>
                        <div class="tech-item">
                            <span class="tech-name"><i class="fas fa-user-cog" style="color:#aaa; margin-right:8px;"></i> <?php echo htmlspecialchars($tech['nama']); ?></span>
                            <span class="tech-count"><?php echo $tech['total_job']; ?> Unit</span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('serviceChart').getContext('2d');
    const serviceChart = new Chart(ctx, {
        type: 'line', 
        data: {
            labels: <?php echo json_encode($labels_harian); ?>,
            datasets: [{
                label: 'Unit Selesai',
                data: <?php echo json_encode($data_harian); ?>,
                backgroundColor: 'rgba(0, 122, 255, 0.1)',
                borderColor: 'rgba(0, 122, 255, 1)',
                borderWidth: 2,
                tension: 0.4,
                fill: true
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
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.parsed.y + ' Unit';
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