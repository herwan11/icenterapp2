<?php
// index.php

require_once 'includes/header.php';

// Atur locale waktu
setlocale(LC_TIME, 'id_ID.UTF-8', 'Indonesian_Indonesia.1252');
$bulan_tahun = date('F Y'); // Contoh: August 2025
$bulan_ini = date('m');
$tahun_ini = date('Y');
$hari_ini = date('Y-m-d');

// --- 1. Logika Ambil Saldo Kas ---
$saldo_kas_saat_ini = 0;
$result_saldo = $conn->query("SELECT saldo_terakhir FROM transaksi_kas ORDER BY id DESC LIMIT 1");
if ($result_saldo && $result_saldo->num_rows > 0) {
    $saldo_kas_saat_ini = $result_saldo->fetch_assoc()['saldo_terakhir'];
}

// --- 2. Logika Hitung Pemasukan Service (MURNI JASA) ---
// Rumus: Total Subtotal Service (Lunas) - Total Harga Jual Sparepart yang dipakai di service tersebut
// Kita hanya menghitung service di bulan ini yang sudah LUNAS.

// A. Ambil Total Subtotal Service Lunas Bulan Ini
$sql_total_service = "SELECT SUM(sub_total) as total FROM service 
                      WHERE MONTH(tanggal) = '$bulan_ini' 
                      AND YEAR(tanggal) = '$tahun_ini' 
                      AND status_pembayaran = 'Lunas'";
$res_total_service = $conn->query($sql_total_service);
$total_revenue_service = ($res_total_service->fetch_assoc()['total']) ?? 0;

// B. Ambil Total Harga Jual Sparepart yang dipakai di Service Lunas Bulan Ini
// Join sparepart_keluar -> service
$sql_part_service = "SELECT SUM(sk.jumlah * ms.harga_jual) as total_part
                     FROM sparepart_keluar sk
                     JOIN master_sparepart ms ON sk.code_sparepart = ms.code_sparepart
                     JOIN service s ON sk.invoice_service = s.invoice
                     WHERE MONTH(s.tanggal) = '$bulan_ini' 
                     AND YEAR(s.tanggal) = '$tahun_ini'
                     AND s.status_pembayaran = 'Lunas'";
$res_part_service = $conn->query($sql_part_service);
$total_part_in_service = ($res_part_service->fetch_assoc()['total_part']) ?? 0;

// C. Ambil Total Pembelian Sparepart Luar (External) untuk Service Lunas Bulan Ini
$sql_part_external = "SELECT SUM(psl.total_harga) as total_external
                      FROM pembelian_sparepart_luar psl
                      JOIN service s ON psl.invoice_service = s.invoice
                      WHERE MONTH(s.tanggal) = '$bulan_ini'
                      AND YEAR(s.tanggal) = '$tahun_ini'
                      AND s.status_pembayaran = 'Lunas'";
$res_part_external = $conn->query($sql_part_external);
$total_part_external = ($res_part_external->fetch_assoc()['total_external']) ?? 0;

// D. Hitung Jasa Murni
// Jasa = Total Tagihan Service - (Total Part Internal + Total Part External)
$pemasukan_jasa_murni = $total_revenue_service - ($total_part_in_service + $total_part_external);


// --- 3. Logika Hitung Penjualan Sparepart (Gabungan) ---
// A. Penjualan Langsung (Tabel penjualan_sparepart)
$sql_jual_langsung = "SELECT SUM(total) as total FROM penjualan_sparepart 
                      WHERE MONTH(tanggal) = '$bulan_ini' 
                      AND YEAR(tanggal) = '$tahun_ini'
                      AND status_pembayaran = 'Lunas'";
$res_jual_langsung = $conn->query($sql_jual_langsung);
$total_jual_langsung = ($res_jual_langsung->fetch_assoc()['total']) ?? 0;

// B. Sparepart yang terjual via Service (Nilai ini kita ambil dari perhitungan 2B di atas)
// Jadi Total Penjualan Sparepart = Penjualan Langsung + Penggunaan di Service
$total_penjualan_sparepart = $total_jual_langsung + $total_part_in_service;


// --- 4. Data Input Manual (Placeholder Sementara) ---
// Anda bisa mengubah nilai ini secara manual di sini
$manual_penjualan_perangkat = 0;
$manual_customer_debt_laba = 0;
$manual_customer_debt_beban = 0; // Piutang (uang belum masuk) dianggap beban sementara? Atau pengeluaran?
$manual_percentage_tech = 1500000; // Pengeluaran gaji/komisi
$manual_pengeluaran_toko = 500000; // Listrik, air, wifi
$manual_opex = 1000000; // Operasional lain

// --- Menyusun Data Laporan ---
$data_laporan = [
    "Service (Jasa)" => [
        "laba" => $pemasukan_jasa_murni, 
        "pengeluaran" => 0
    ],
    "Penjualan Sparepart" => [
        "laba" => $total_penjualan_sparepart, 
        "pengeluaran" => 0
        // Catatan: Idealnya nanti ada 'pengeluaran' yaitu HPP (Harga Beli) sparepart agar ketahuan laba bersih sparepart
    ],
    "Penjualan Perangkat" => [
        "laba" => $manual_penjualan_perangkat, 
        "pengeluaran" => 0
    ],
    "Customer Debt" => [
        "laba" => $manual_customer_debt_laba, 
        "pengeluaran" => $manual_customer_debt_beban
    ],
    "Percentage Technician" => [
        "laba" => 0, 
        "pengeluaran" => $manual_percentage_tech
    ],
    "Pengeluaran Toko" => [
        "laba" => 0, 
        "pengeluaran" => $manual_pengeluaran_toko
    ],
    "Operasional (OpEx)" => [
        "laba" => 0, 
        "pengeluaran" => $manual_opex
    ],
];

$total_laba = array_sum(array_column($data_laporan, 'laba'));
$total_pengeluaran = array_sum(array_column($data_laporan, 'pengeluaran'));
$profit = $total_laba - $total_pengeluaran;

// --- LOGIKA ABSENSI HARI INI (UPDATED: Termasuk Waktu Keluar) ---
$absensi_list = [];
$sql_absen = "SELECT u.nama, u.role, a.waktu_masuk, a.waktu_keluar 
              FROM users u 
              LEFT JOIN absensi a ON u.id = a.user_id AND a.tanggal = '$hari_ini'
              WHERE u.role != 'owner' 
              ORDER BY a.waktu_masuk DESC, u.nama ASC";
$res_absen = $conn->query($sql_absen);
if($res_absen) {
    while($row = $res_absen->fetch_assoc()) {
        $absensi_list[] = $row;
    }
}
?>

<style>
    .absensi-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #eee; }
    .absensi-item:last-child { border-bottom: none; }
    .absensi-status { font-size: 11px; padding: 4px 8px; border-radius: 12px; font-weight: 600; display: inline-block; text-align: center; min-width: 60px; }
    .status-hadir { background: #d4edda; color: #155724; }
    .status-pulang { background: #e2e3e5; color: #383d41; border: 1px solid #d6d8db; }
    .status-belum { background: #f8d7da; color: #721c24; }
    .absensi-time { font-size: 12px; color: #666; margin-top: 4px; }
</style>

<!-- Konten Halaman Home dengan Layout Baru -->
<div class="content-container">

    <!-- Kolom Tengah (Tabel Laporan) -->
    <div class="main-column">
        <div class="page-controls">
            <span class="page-path">My shop</span>
            <div class="filter-buttons">
                <button class="btn btn-primary">Bulan Ini <i class="fas fa-calendar"></i></button>
            </div>
        </div>

        <div class="report-card glass-effect">
            <h2 class="report-title"><?php echo $bulan_tahun; ?></h2>
            <!-- Wrapper untuk tabel agar responsif -->
            <div class="table-wrapper">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>KATEGORI</th>
                            <th class="text-right">PEMASUKAN (OMSET)</th>
                            <th class="text-right">PENGELUARAN</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data_laporan as $nama => $nilai): ?>
                        <tr>
                            <td><?php echo $nama; ?></td>
                            <td class="text-right text-success"><?php echo ($nilai['laba'] > 0) ? number_format($nilai['laba'], 0, ',', '.') : '-'; ?></td>
                            <td class="text-right text-danger"><?php echo ($nilai['pengeluaran'] > 0) ? number_format($nilai['pengeluaran'], 0, ',', '.') : '-'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="total-row">
                            <td>TOTAL</td>
                            <td class="text-right text-success"><?php echo number_format($total_laba, 0, ',', '.'); ?></td>
                            <td class="text-right text-danger"><?php echo number_format($total_pengeluaran, 0, ',', '.'); ?></td>
                        </tr>
                        <tr class="profit-row">
                            <td>PROFIT BERSIH</td>
                            <td colspan="2" class="text-right text-profit" style="font-size: 18px;">Rp <?php echo number_format($profit, 0, ',', '.'); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Kolom Kanan (Ringkasan) -->
    <aside class="right-column">
        
        <!-- === KARTU BARU: Saldo Kas Saat Ini === -->
        <div class="summary-card glass-effect" style="border-left: 5px solid var(--accent-primary);">
            <div class="summary-header">
                <h3 class="summary-title" style="margin-bottom: 0;">Total Kas Toko</h3>
                <a href="kas.php" class="btn btn-tertiary">Detail <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="summary-total" style="padding-top: 16px; border-top: none;">
                <div class="summary-icon" style="background-color: var(--accent-primary);">
                    <i class="fas fa-wallet"></i>
                </div>
                <div class="summary-text">
                    <p>SALDO SAAT INI</p>
                    <span style="font-size: 24px;">Rp <?php echo number_format($saldo_kas_saat_ini, 0, ',', '.'); ?></span>
                </div>
            </div>
        </div>

        <!-- === KARTU BARU: KEHADIRAN KARYAWAN === -->
        <div class="summary-card glass-effect">
            <h3 class="summary-title">Kehadiran Hari Ini (<?php echo date('d/m'); ?>)</h3>
            <div style="max-height: 300px; overflow-y: auto;">
                <?php if(empty($absensi_list)): ?>
                    <p style="text-align: center; color: #888;">Tidak ada data karyawan.</p>
                <?php else: ?>
                    <?php foreach($absensi_list as $absen): ?>
                        <?php 
                            $is_present = !empty($absen['waktu_masuk']);
                            $is_out = !empty($absen['waktu_keluar']);
                            
                            $jam_masuk = $is_present ? date('H:i', strtotime($absen['waktu_masuk'])) : '-';
                            $jam_keluar = $is_out ? date('H:i', strtotime($absen['waktu_keluar'])) : '-';
                            
                            // Hitung Durasi (Jika Hadir)
                            $durasi_teks = '-';
                            if ($is_present) {
                                $start = new DateTime($absen['waktu_masuk']);
                                $end = $is_out ? new DateTime($absen['waktu_keluar']) : new DateTime();
                                $interval = $start->diff($end);
                                $durasi_teks = $interval->format('%hJ %iM');
                            }
                        ?>
                        <div class="absensi-item">
                            <div>
                                <div style="font-weight: 600; font-size: 13px;"><?php echo htmlspecialchars($absen['nama']); ?></div>
                                <div class="absensi-time">
                                    <?php if($is_present): ?>
                                        <i class="fas fa-arrow-right" style="color:var(--accent-success)"></i> <?php echo $jam_masuk; ?> &nbsp;
                                        <?php if($is_out): ?>
                                            <i class="fas fa-arrow-left" style="color:var(--accent-danger)"></i> <?php echo $jam_keluar; ?>
                                        <?php else: ?>
                                            <span style="color: var(--accent-primary); font-size: 10px;">(Aktif)</span>
                                        <?php endif; ?>
                                        <br>
                                        <span style="color: #888; font-size: 10px;">Durasi: <?php echo $durasi_teks; ?></span>
                                    <?php else: ?>
                                        Belum Absen
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div>
                                <?php if (!$is_present): ?>
                                    <span class="absensi-status status-belum">Alpha</span>
                                <?php elseif ($is_out): ?>
                                    <span class="absensi-status status-pulang">Pulang</span>
                                <?php else: ?>
                                    <span class="absensi-status status-hadir">Hadir</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <!-- ===================================== -->

        <!-- Supplier Card -->
        <div class="summary-card glass-effect">
            <h3 class="summary-title">Supplier</h3>
            <div class="summary-item">
                <div class="summary-icon" style="background-color: #ff3b30;">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="summary-text">
                    <p>Pembayaran Belum Lunas</p>
                    <span>Rp 0</span>
                </div>
            </div>
            <div class="summary-item">
                <div class="summary-icon" style="background-color: #34c759;">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="summary-text">
                    <p>Pembayaran Lunas</p>
                    <span>Rp 0</span>
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
                    <span>Rp 0</span>
                </div>
            </div>
             <div class="summary-item">
                <div class="summary-icon" style="background-color: #ff9500;">
                    <i class="fas fa-mobile-alt"></i>
                </div>
                <div class="summary-text">
                    <p>Perangkat</p>
                    <span>Rp 0</span>
                </div>
            </div>
            <div class="summary-item">
                <div class="summary-icon" style="background-color: #ff9500;">
                    <i class="fas fa-tools"></i>
                </div>
                <div class="summary-text">
                    <p>Alat & Inventoris (CapEx)</p>
                    <span>Rp 0</span>
                </div>
            </div>
            <div class="summary-total">
                <div class="summary-icon" style="background-color: #007aff;">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="summary-text">
                    <p>TOTAL ASSET</p>
                    <span>Rp 0</span>
                </div>
            </div>
        </div>
    </aside>

</div>

<?php
// Sertakan footer halaman
require_once 'includes/footer.php';
?>