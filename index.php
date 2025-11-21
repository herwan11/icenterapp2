<?php
// index.php

// Sertakan header halaman (sudah termasuk pengecekan login)
require_once 'includes/header.php';

// Atur locale ke Indonesia untuk format tanggal
setlocale(LC_TIME, 'id_ID.UTF-8', 'Indonesian_Indonesia.1252');
$bulan_tahun = ucwords(strftime('%B %Y')); // Contoh: Agustus 2025

// --- Data Placeholder (Nanti diganti dengan query asli) ---
$data_laporan = [
    "Service" => ["laba" => 5500000, "pengeluaran" => 0],
    "Penjualan Sparepart" => ["laba" => 2500000, "pengeluaran" => 0],
    "Penjualan Perangkat" => ["laba" => 4000000, "pengeluaran" => 0],
    "Customer Debt" => ["laba" => 0, "pengeluaran" => 1000000],
    "Sparepart Service" => ["laba" => 1500000, "pengeluaran" => 0],
    "Percentage Technician" => ["laba" => 0, "pengeluaran" => 1500000],
    "Pengeluaran Toko" => ["laba" => 0, "pengeluaran" => 2000000],
    "Operasional (OpEx)" => ["laba" => 0, "pengeluaran" => 3500000],
];

$total_laba = array_sum(array_column($data_laporan, 'laba'));
$total_pengeluaran = array_sum(array_column($data_laporan, 'pengeluaran'));
$profit = $total_laba - $total_pengeluaran;
?>

<!-- Konten Halaman Home dengan Layout Baru -->
<div class="content-container">

    <!-- Kolom Tengah (Tabel Laporan) -->
    <div class="main-column">
        <div class="page-controls">
            <span class="page-path">My shop</span>
            <div class="filter-buttons">
                <button class="btn btn-primary">Select Filter <i class="fas fa-chevron-down"></i></button>
                <button class="btn btn-secondary">Default</button>
            </div>
        </div>

        <div class="report-card glass-effect">
            <h2 class="report-title"><?php echo $bulan_tahun; ?></h2>
            <!-- Wrapper untuk tabel agar responsif -->
            <div class="table-wrapper">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>NAME</th>
                            <th class="text-right">LABA</th>
                            <th class="text-right">PENGELUARAN</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data_laporan as $nama => $nilai): ?>
                        <tr>
                            <td><?php echo $nama; ?></td>
                            <td class="text-right text-success"><?php echo ($nilai['laba'] > 0) ? number_format($nilai['laba'], 2, ',', '.') : '-'; ?></td>
                            <td class="text-right text-danger"><?php echo ($nilai['pengeluaran'] > 0) ? number_format($nilai['pengeluaran'], 2, ',', '.') : '-'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="total-row">
                            <td>TOTAL</td>
                            <td class="text-right text-success"><?php echo number_format($total_laba, 2, ',', '.'); ?></td>
                            <td class="text-right text-danger"><?php echo number_format($total_pengeluaran, 2, ',', '.'); ?></td>
                        </tr>
                        <tr class="profit-row">
                            <td>PROFIT</td>
                            <td colspan="2" class="text-right text-profit"><?php echo number_format($profit, 2, ',', '.'); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Kolom Kanan (Ringkasan) -->
    <aside class="right-column">
        <!-- Supplier Card -->
        <div class="summary-card glass-effect">
            <h3 class="summary-title">Supplier</h3>
            <div class="summary-item">
                <div class="summary-icon" style="background-color: #ff3b30;">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="summary-text">
                    <p>Pembayaran Belum Lunas</p>
                    <span>Rp 0.00</span>
                </div>
            </div>
            <div class="summary-item">
                <div class="summary-icon" style="background-color: #34c759;">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="summary-text">
                    <p>Pembayaran Lunas</p>
                    <span>Rp 0.00</span>
                </div>
            </div>
        </div>

        <!-- Asset Store Card -->
        <div class="summary-card glass-effect">
            <div class="summary-header">
                <h3 class="summary-title">Asset Store</h3>
                <button class="btn btn-tertiary">By Category <i class="fas fa-chevron-down"></i></button>
            </div>
            <div class="summary-item">
                <div class="summary-icon" style="background-color: #ff9500;">
                    <i class="fas fa-box"></i>
                </div>
                <div class="summary-text">
                    <p>Product</p>
                    <span>Rp 0.00</span>
                </div>
            </div>
             <div class="summary-item">
                <div class="summary-icon" style="background-color: #ff9500;">
                    <i class="fas fa-mobile-alt"></i>
                </div>
                <div class="summary-text">
                    <p>Perangkat</p>
                    <span>Rp 0.00</span>
                </div>
            </div>
            <div class="summary-item">
                <div class="summary-icon" style="background-color: #ff9500;">
                    <i class="fas fa-tools"></i>
                </div>
                <div class="summary-text">
                    <p>Alat & Inventoris (CapEx)</p>
                    <span>Rp 0.00</span>
                </div>
            </div>
            <div class="summary-total">
                <div class="summary-icon" style="background-color: #007aff;">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="summary-text">
                    <p>TOTAL ASSET</p>
                    <span>Rp 0.00</span>
                </div>
            </div>
        </div>
    </aside>

</div>

<?php
// Sertakan footer halaman
require_once 'includes/footer.php';
?>

