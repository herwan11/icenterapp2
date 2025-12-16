<?php
// laporan_stok.php
require_once 'includes/header.php';

// Cek hak akses
if (get_user_role() !== 'owner' && get_user_role() !== 'admin') {
    echo "<script>alert('Akses ditolak.'); window.location.href='index.php';</script>";
    exit();
}

// Ambil Data Stok
$stok_data = [];
$total_aset = 0;
$total_qty = 0;

$sql = "SELECT * FROM master_sparepart ORDER BY nama ASC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $nilai_aset = $row['stok_tersedia'] * $row['harga_beli'];
        $row['nilai_aset'] = $nilai_aset;
        
        $total_aset += $nilai_aset;
        $total_qty += $row['stok_tersedia'];
        
        $stok_data[] = $row;
    }
}
?>

<style>
    /* Styling Kertas PDF A4 (Mirip laporan_harian.php) */
    .paper-container {
        width: 210mm;
        min-height: 297mm;
        background: white;
        margin: 0 auto;
        padding: 15mm;
        box-shadow: 0 0 20px rgba(0,0,0,0.1);
        position: relative;
        font-family: 'Times New Roman', Times, serif;
        color: #000;
    }
    .report-header {
        border-bottom: 3px double #000;
        padding-bottom: 15px; margin-bottom: 20px;
        display: flex; align-items: center; gap: 20px;
    }
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

    .print-controls {
        background: white; padding: 15px; border-radius: 12px; margin-bottom: 20px;
        display: flex; justify-content: space-between; align-items: center;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }
    
    @media print {
        @page { size: A4; margin: 0; }
        body { background: #fff; margin: 0; padding: 0; }
        .sidebar, .main-header, .print-controls, .page-title, .sidebar-overlay { display: none !important; }
        .main-wrapper { margin: 0; width: 100%; }
        .main-content { padding: 0; }
        .paper-container { box-shadow: none; margin: 0; width: 100%; min-height: auto; padding: 15mm; }
        .app-container { display: block; }
    }
</style>

<h1 class="page-title">Laporan Posisi Stok Sparepart</h1>

<div class="print-controls">
    <div><strong>Total Item:</strong> <?php echo count($stok_data); ?> SKU</div>
    <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print"></i> Cetak Laporan</button>
</div>

<div class="paper-container">
    <!-- Header -->
    <div class="report-header">
        <img src="assets/media/icenter.png" alt="Logo" class="report-logo">
        <div class="company-info">
            <div class="company-name">iCenter Apple</div>
            <div class="company-address">
                Jl. Nangka, Mappasaile, Kec. Pangkajene,<br>
                Kabupaten Pangkajene Dan Kepulauan,<br>
                Sulawesi Selatan 90611
            </div>
        </div>
        <div class="report-meta">
            <div class="report-title">LAPORAN STOK</div>
            <div style="font-size: 11px;">Per Tanggal: <?php echo date('d F Y'); ?></div>
        </div>
    </div>

    <!-- Tabel -->
    <table class="report-table">
        <thead>
            <tr>
                <th style="width: 30px;">No</th>
                <th>Kode Part</th>
                <th>Nama Barang</th>
                <th>Kategori</th>
                <th>Satuan</th>
                <th style="width: 50px;">Stok</th>
                <th style="width: 80px;">Harga Beli</th>
                <th style="width: 90px;">Total Aset</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($stok_data)): ?>
                <tr><td colspan="8" class="text-center">Tidak ada data stok.</td></tr>
            <?php else: ?>
                <?php $no=1; foreach($stok_data as $row): ?>
                <tr>
                    <td class="text-center"><?php echo $no++; ?></td>
                    <td><?php echo htmlspecialchars($row['code_sparepart']); ?></td>
                    <td><?php echo htmlspecialchars($row['nama']); ?></td>
                    <td><?php echo htmlspecialchars($row['kategori']); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($row['satuan']); ?></td>
                    <td class="text-center text-bold"><?php echo $row['stok_tersedia']; ?></td>
                    <td class="text-right">Rp <?php echo number_format($row['harga_beli'], 0, ',', '.'); ?></td>
                    <td class="text-right">Rp <?php echo number_format($row['nilai_aset'], 0, ',', '.'); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" class="text-right text-bold">TOTAL KESELURUHAN</td>
                <td class="text-center text-bold"><?php echo number_format($total_qty, 0, ',', '.'); ?></td>
                <td></td>
                <td class="text-right text-bold">Rp <?php echo number_format($total_aset, 0, ',', '.'); ?></td>
            </tr>
        </tfoot>
    </table>

    <div style="margin-top: 30px; font-size: 11px; text-align: right;">
        Dicetak oleh: <?php echo htmlspecialchars($_SESSION['nama']); ?> pada <?php echo date('d/m/Y H:i:s'); ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>