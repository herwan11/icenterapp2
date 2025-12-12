<?php
// laporan_harian.php
require_once 'includes/header.php';

// Ambil tanggal dari parameter GET atau default hari ini
$tanggal_filter = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');

// Query Data Service Harian
// Mengambil semua data service berdasarkan tanggal yang dipilih
$sql = "SELECT 
            s.*, 
            c.nama as nama_customer, 
            k.nama as nama_teknisi,
            u.nama as nama_kasir
        FROM service s
        LEFT JOIN customers c ON s.customer_id = c.id
        LEFT JOIN karyawan k ON s.teknisi_id = k.id
        LEFT JOIN users u ON s.kasir_id = u.id
        WHERE DATE(s.tanggal) = ?
        ORDER BY s.invoice ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $tanggal_filter);
$stmt->execute();
$result = $stmt->get_result();

$data_laporan = [];
$total_pendapatan = 0;
$total_unit = 0;

while ($row = $result->fetch_assoc()) {
    $data_laporan[] = $row;
    $total_pendapatan += $row['sub_total'];
    $total_unit++;
}
$stmt->close();
?>

<style>
    /* Styling untuk Tampilan Layar (Screen) */
    .filter-section {
        background: white;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
    }
    
    .filter-form { display: flex; align-items: center; gap: 15px; }
    .filter-input { padding: 10px; border: 1px solid #ddd; border-radius: 8px; font-family: 'Inter', sans-serif; }
    .btn-filter { background: var(--accent-primary); color: white; padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer; }
    .btn-print { background: #34c759; color: white; padding: 10px 25px; border-radius: 8px; border: none; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 8px; }
    .btn-print:hover { background: #2db14d; transform: translateY(-2px); transition: all 0.2s; }

    /* Styling Kertas PDF A4 */
    .paper-container {
        width: 210mm;
        min-height: 297mm;
        background: white;
        margin: 0 auto;
        padding: 15mm;
        box-shadow: 0 0 20px rgba(0,0,0,0.1);
        position: relative;
        font-family: 'Times New Roman', Times, serif; /* Font formal untuk laporan */
        color: #000;
    }

    /* Header Laporan */
    .report-header {
        border-bottom: 3px double #000;
        padding-bottom: 15px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 20px;
    }
    .report-logo { width: 80px; height: auto; }
    .company-info { flex-grow: 1; }
    .company-name { font-size: 24px; font-weight: bold; text-transform: uppercase; margin-bottom: 5px; }
    .company-address { font-size: 11px; line-height: 1.4; margin-bottom: 5px; }
    .report-meta { text-align: right; }
    .report-title { font-size: 18px; font-weight: bold; text-decoration: underline; margin-bottom: 5px; }
    
    /* Tabel Laporan */
    .report-table { width: 100%; border-collapse: collapse; font-size: 11px; margin-bottom: 20px; }
    .report-table th, .report-table td { border: 1px solid #000; padding: 6px 8px; }
    .report-table th { background-color: #f0f0f0; text-transform: uppercase; font-weight: bold; text-align: center; }
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .text-bold { font-weight: bold; }

    /* Footer Laporan */
    .report-summary { display: flex; justify-content: flex-end; margin-bottom: 40px; }
    .summary-box { border: 1px solid #000; padding: 10px 20px; min-width: 200px; }
    .summary-row { display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 12px; }
    
    .report-footer { display: flex; justify-content: space-between; font-size: 12px; margin-top: 50px; }
    .signature-box { text-align: center; min-width: 150px; }
    .signature-space { height: 60px; }
    .print-footer { position: absolute; bottom: 10mm; left: 15mm; right: 15mm; font-size: 10px; border-top: 1px solid #ccc; padding-top: 5px; display: flex; justify-content: space-between; }

    /* PRINT QUERY */
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

<!-- Judul Halaman (Hanya Tampil di Layar) -->
<h1 class="page-title">Laporan Service Harian</h1>

<!-- Filter & Tombol Print -->
<div class="filter-section">
    <form method="GET" action="" class="filter-form">
        <label style="font-weight:600;">Pilih Tanggal:</label>
        <input type="date" name="tanggal" value="<?php echo $tanggal_filter; ?>" class="filter-input">
        <button type="submit" class="btn-filter"><i class="fas fa-search"></i> Tampilkan</button>
    </form>
    <button onclick="window.print()" class="btn-print"><i class="fas fa-print"></i> Cetak PDF</button>
</div>

<!-- Kertas Laporan -->
<div class="paper-container">
    
    <!-- Header -->
    <div class="report-header">
        <img src="assets/media/icenter.png" alt="Logo" class="report-logo">
        <div class="company-info">
            <div class="company-name">iCenter Apple</div>
            <div class="company-address">
                Jl. Nangka, Mappasaile, Kec. Pangkajene,<br>
                Kabupaten Pangkajene Dan Kepulauan,<br>
                Sulawesi Selatan 90611<br>
                WA: 0852-9805-8500 | www.icenterpangkep.my.id
            </div>
        </div>
        <div class="report-meta">
            <div class="report-title">LAPORAN HARIAN</div>
            <div style="font-size: 12px;">Tanggal: <?php echo date('d F Y', strtotime($tanggal_filter)); ?></div>
        </div>
    </div>

    <!-- Tabel Data -->
    <table class="report-table">
        <thead>
            <tr>
                <th style="width: 30px;">No</th>
                <th style="width: 80px;">Invoice</th>
                <th style="width: 100px;">Customer</th>
                <th style="width: 100px;">Unit / Tipe</th>
                <th>Kerusakan</th>
                <th style="width: 80px;">Teknisi</th>
                <th style="width: 70px;">Status</th>
                <th style="width: 90px;">Biaya</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data_laporan)): ?>
                <tr>
                    <td colspan="8" class="text-center" style="padding: 20px;">Tidak ada data service pada tanggal ini.</td>
                </tr>
            <?php else: ?>
                <?php $no = 1; foreach ($data_laporan as $row): ?>
                <tr>
                    <td class="text-center"><?php echo $no++; ?></td>
                    <td class="text-center"><?php echo $row['invoice']; ?></td>
                    <td><?php echo htmlspecialchars($row['nama_customer'] ?? 'Umum'); ?></td>
                    <td><?php echo htmlspecialchars($row['merek_hp'] . ' ' . $row['tipe_hp']); ?></td>
                    <td><?php echo htmlspecialchars($row['kerusakan']); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($row['nama_teknisi'] ?? '-'); ?></td>
                    <td class="text-center"><?php echo $row['status_service']; ?></td>
                    <td class="text-right">Rp <?php echo number_format($row['sub_total'], 0, ',', '.'); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Summary -->
    <div class="report-summary">
        <div class="summary-box">
            <div class="summary-row">
                <span>Total Unit Masuk:</span>
                <span class="text-bold"><?php echo $total_unit; ?> Unit</span>
            </div>
            <div class="summary-row" style="border-top: 1px solid #ccc; padding-top: 5px; margin-top: 5px;">
                <span class="text-bold">Est. Pendapatan:</span>
                <span class="text-bold">Rp <?php echo number_format($total_pendapatan, 0, ',', '.'); ?></span>
            </div>
        </div>
    </div>

    <!-- Tanda Tangan -->
    <div class="report-footer">
        <div class="signature-box">
            <div>Dibuat Oleh,</div>
            <div class="signature-space"></div>
            <div class="text-bold"><?php echo htmlspecialchars($_SESSION['nama'] ?? 'Admin'); ?></div>
        </div>
        <div class="signature-box">
            <div>Mengetahui,</div>
            <div class="signature-space"></div>
            <div class="text-bold">Owner / Manager</div>
        </div>
    </div>

    <!-- Footer Cetak -->
    <div class="print-footer">
        <div>Dicetak pada: <?php echo date('d/m/Y H:i:s'); ?></div>
        <div>Halaman 1 dari 1</div>
    </div>

</div>

<?php require_once 'includes/footer.php'; ?>