<?php
// laporan_stok_keluar.php
require_once 'includes/header.php';

if (get_user_role() !== 'owner' && get_user_role() !== 'admin') {
    echo "<script>alert('Akses ditolak.'); window.location.href='index.php';</script>";
    exit();
}

$tgl_awal = isset($_GET['start']) ? $_GET['start'] : date('Y-m-01');
$tgl_akhir = isset($_GET['end']) ? $_GET['end'] : date('Y-m-d');

// Gabungkan Data Internal (Sparepart Keluar) dan Eksternal (Pembelian Langsung)
$data_keluar = [];
$total_qty = 0;
$total_nilai = 0;

$sql = "
    SELECT * FROM (
        -- 1. INTERNAL
        SELECT 
            sk.tanggal_keluar as tanggal,
            sk.invoice_service as referensi,
            sk.code_sparepart,
            ms.nama as nama_barang,
            sk.jumlah,
            'Stok Toko' as sumber,
            (sk.jumlah * ms.harga_jual) as total_nominal
        FROM sparepart_keluar sk
        JOIN master_sparepart ms ON sk.code_sparepart = ms.code_sparepart
        WHERE DATE(sk.tanggal_keluar) BETWEEN ? AND ?

        UNION ALL

        -- 2. EKSTERNAL
        SELECT 
            psl.tanggal_beli as tanggal,
            psl.invoice_service as referensi,
            'EXT' as code_sparepart,
            psl.nama_sparepart as nama_barang,
            psl.jumlah,
            'Beli Luar' as sumber,
            psl.total_jual as total_nominal
        FROM pembelian_sparepart_luar psl
        WHERE DATE(psl.tanggal_beli) BETWEEN ? AND ?
    ) as gabungan
    ORDER BY tanggal ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $tgl_awal, $tgl_akhir, $tgl_awal, $tgl_akhir);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $data_keluar[] = $row;
    $total_qty += $row['jumlah'];
    $total_nilai += $row['total_nominal'];
}
?>

<style>
    .paper-container {
        width: 210mm; min-height: 297mm; background: white; margin: 0 auto; padding: 15mm;
        box-shadow: 0 0 20px rgba(0,0,0,0.1); position: relative; font-family: 'Times New Roman', Times, serif; color: #000;
    }
    .report-header { border-bottom: 3px double #000; padding-bottom: 15px; margin-bottom: 20px; display: flex; align-items: center; gap: 20px; }
    .report-logo { width: 80px; height: auto; }
    .company-info { flex-grow: 1; }
    .company-name { font-size: 24px; font-weight: bold; text-transform: uppercase; margin-bottom: 5px; }
    .company-address { font-size: 11px; line-height: 1.4; margin-bottom: 5px; }
    .report-meta { text-align: right; width: 200px; }
    .report-title { font-size: 18px; font-weight: bold; text-decoration: underline; margin-bottom: 5px; }
    .report-table { width: 100%; border-collapse: collapse; font-size: 11px; margin-bottom: 20px; }
    .report-table th, .report-table td { border: 1px solid #000; padding: 5px; }
    .report-table th { background-color: #f0f0f0; text-transform: uppercase; font-weight: bold; text-align: center; }
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .text-bold { font-weight: bold; }
    
    .filter-section {
        background: white; padding: 15px; border-radius: 12px; margin-bottom: 20px;
        display: flex; gap: 10px; align-items: center; box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }
    .filter-input { padding: 8px; border: 1px solid #ddd; border-radius: 6px; }
    .btn-filter { background: var(--accent-primary); color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; }
    .btn-print { background: #34c759; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; margin-left: auto; }

    @media print {
        @page { size: A4; margin: 0; }
        body { background: #fff; margin: 0; padding: 0; }
        .sidebar, .main-header, .filter-section, .page-title, .sidebar-overlay { display: none !important; }
        .main-wrapper { margin: 0; width: 100%; }
        .main-content { padding: 0; }
        .paper-container { box-shadow: none; margin: 0; width: 100%; min-height: auto; padding: 15mm; }
        .app-container { display: block; }
    }
</style>

<h1 class="page-title">Laporan Barang Keluar (Penjualan/Service)</h1>

<div class="filter-section">
    <form method="GET" style="display:flex; gap:10px; align-items:center;">
        <label>Dari:</label>
        <input type="date" name="start" value="<?php echo $tgl_awal; ?>" class="filter-input">
        <label>Sampai:</label>
        <input type="date" name="end" value="<?php echo $tgl_akhir; ?>" class="filter-input">
        <button type="submit" class="btn-filter">Tampilkan</button>
    </form>
    <button onclick="window.print()" class="btn-print"><i class="fas fa-print"></i> Cetak</button>
</div>

<div class="paper-container">
    <div class="report-header">
        <img src="assets/media/icenter.png" alt="Logo" class="report-logo">
        <div class="company-info">
            <div class="company-name">iCenter Apple</div>
            <div class="company-address">Jl. Nangka, Mappasaile, Pangkep, Sulsel</div>
        </div>
        <div class="report-meta">
            <div class="report-title">LAPORAN BARANG KELUAR</div>
            <div style="font-size: 11px;">Periode: <?php echo date('d/m/y', strtotime($tgl_awal)); ?> - <?php echo date('d/m/y', strtotime($tgl_akhir)); ?></div>
        </div>
    </div>

    <table class="report-table">
        <thead>
            <tr>
                <th style="width: 30px;">No</th>
                <th style="width: 100px;">Tanggal</th>
                <th>Referensi / Invoice</th>
                <th>Kode</th>
                <th>Nama Barang</th>
                <th>Sumber</th>
                <th style="width: 50px;">Qty</th>
                <th style="width: 90px;">Nilai Jual</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($data_keluar)): ?>
                <tr><td colspan="8" class="text-center">Tidak ada transaksi keluar pada periode ini.</td></tr>
            <?php else: ?>
                <?php $no=1; foreach($data_keluar as $row): ?>
                <tr>
                    <td class="text-center"><?php echo $no++; ?></td>
                    <td class="text-center"><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                    <td style="font-size: 10px;"><?php echo htmlspecialchars($row['referensi']); ?></td>
                    <td><?php echo htmlspecialchars($row['code_sparepart']); ?></td>
                    <td><?php echo htmlspecialchars($row['nama_barang']); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($row['sumber']); ?></td>
                    <td class="text-center"><?php echo $row['jumlah']; ?></td>
                    <td class="text-right">Rp <?php echo number_format($row['total_nominal'], 0, ',', '.'); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6" class="text-right text-bold">TOTAL PERIODE INI</td>
                <td class="text-center text-bold"><?php echo number_format($total_qty, 0, ',', '.'); ?></td>
                <td class="text-right text-bold">Rp <?php echo number_format($total_nilai, 0, ',', '.'); ?></td>
            </tr>
        </tfoot>
    </table>
    
    <div style="margin-top: 30px; font-size: 11px; text-align: right;">
        Dicetak oleh: <?php echo htmlspecialchars($_SESSION['nama']); ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>