<?php
// laporan_mingguan.php
require_once 'includes/header.php';

// Ambil tanggal dari parameter GET
// Default: Start = Senin minggu ini, End = Minggu ini
$start_default = date('Y-m-d', strtotime('monday this week'));
$end_default = date('Y-m-d', strtotime('sunday this week'));

$tgl_awal = isset($_GET['start']) ? $_GET['start'] : $start_default;
$tgl_akhir = isset($_GET['end']) ? $_GET['end'] : $end_default;

// Query Data Service Mingguan (Rentang Tanggal)
$sql = "SELECT 
            s.*, 
            c.nama as nama_customer, 
            k.nama as nama_teknisi 
        FROM service s
        LEFT JOIN customers c ON s.customer_id = c.id
        LEFT JOIN karyawan k ON s.teknisi_id = k.id
        WHERE DATE(s.tanggal) BETWEEN ? AND ?
        ORDER BY s.tanggal ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $tgl_awal, $tgl_akhir);
$stmt->execute();
$result = $stmt->get_result();

$data_laporan = [];
$total_pendapatan = 0;
$status_summary = []; // Untuk ringkasan status per minggu

while ($row = $result->fetch_assoc()) {
    $data_laporan[] = $row;
    $total_pendapatan += $row['sub_total'];
    
    // Hitung ringkasan status
    $st = $row['status_service'];
    if (!isset($status_summary[$st])) $status_summary[$st] = 0;
    $status_summary[$st]++;
}
$stmt->close();
?>

<style>
    /* Styling Identik dengan Laporan Harian untuk Konsistensi */
    .filter-section {
        background: white;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        flex-wrap: wrap; gap: 10px;
    }
    .filter-form { display: flex; align-items: center; gap: 10px; }
    .filter-input { padding: 8px; border: 1px solid #ddd; border-radius: 6px; }
    .btn-filter { background: var(--accent-primary); color: white; padding: 8px 15px; border-radius: 6px; border: none; cursor: pointer; }
    .btn-print { background: #34c759; color: white; padding: 10px 25px; border-radius: 8px; border: none; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 8px; }
    .btn-print:hover { background: #2db14d; transform: translateY(-2px); transition: all 0.2s; }

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

    .report-header { border-bottom: 3px double #000; padding-bottom: 15px; margin-bottom: 20px; display: flex; align-items: center; gap: 20px; }
    .report-logo { width: 80px; height: auto; }
    .company-info { flex-grow: 1; }
    .company-name { font-size: 24px; font-weight: bold; text-transform: uppercase; margin-bottom: 5px; }
    .company-address { font-size: 11px; line-height: 1.4; margin-bottom: 5px; }
    .report-meta { text-align: right; width: 200px;}
    .report-title { font-size: 18px; font-weight: bold; text-decoration: underline; margin-bottom: 5px; }
    
    .report-table { width: 100%; border-collapse: collapse; font-size: 10px; margin-bottom: 20px; }
    .report-table th, .report-table td { border: 1px solid #000; padding: 5px; }
    .report-table th { background-color: #f0f0f0; text-transform: uppercase; font-weight: bold; text-align: center; }
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .text-bold { font-weight: bold; }

    /* Summary Grid untuk Mingguan */
    .weekly-summary-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 40px; border-top: 1px dashed #000; padding-top: 20px; }
    .ws-box { border: 1px solid #000; padding: 10px; }
    .ws-title { font-weight: bold; border-bottom: 1px solid #ccc; margin-bottom: 5px; padding-bottom: 3px; font-size: 12px; }
    .ws-item { display: flex; justify-content: space-between; font-size: 11px; margin-bottom: 3px; }

    .report-footer { display: flex; justify-content: space-between; font-size: 12px; margin-top: 30px; }
    .signature-box { text-align: center; min-width: 150px; }
    .signature-space { height: 60px; }
    .print-footer { position: absolute; bottom: 10mm; left: 15mm; right: 15mm; font-size: 10px; border-top: 1px solid #ccc; padding-top: 5px; display: flex; justify-content: space-between; }

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

<h1 class="page-title">Laporan Service Mingguan</h1>

<div class="filter-section">
    <form method="GET" action="" class="filter-form">
        <label>Dari:</label>
        <input type="date" name="start" value="<?php echo $tgl_awal; ?>" class="filter-input">
        <label>Sampai:</label>
        <input type="date" name="end" value="<?php echo $tgl_akhir; ?>" class="filter-input">
        <button type="submit" class="btn-filter">Tampilkan</button>
    </form>
    <button onclick="window.print()" class="btn-print"><i class="fas fa-print"></i> Cetak PDF</button>
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
                Sulawesi Selatan 90611<br>
                WA: 0852-9805-8500 | www.icenterpangkep.my.id
            </div>
        </div>
        <div class="report-meta">
            <div class="report-title">LAPORAN PERIODIK</div>
            <div style="font-size: 11px;">
                Periode:<br>
                <?php echo date('d/m/y', strtotime($tgl_awal)); ?> s/d <?php echo date('d/m/y', strtotime($tgl_akhir)); ?>
            </div>
        </div>
    </div>

    <!-- Tabel Data -->
    <table class="report-table">
        <thead>
            <tr>
                <th style="width: 25px;">No</th>
                <th style="width: 60px;">Tgl</th>
                <th style="width: 70px;">Invoice</th>
                <th>Customer / Unit</th>
                <th>Kerusakan</th>
                <th style="width: 60px;">Teknisi</th>
                <th style="width: 60px;">Status</th>
                <th style="width: 80px;">Biaya</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data_laporan)): ?>
                <tr>
                    <td colspan="8" class="text-center" style="padding: 20px;">Tidak ada data service pada periode ini.</td>
                </tr>
            <?php else: ?>
                <?php $no = 1; foreach ($data_laporan as $row): ?>
                <tr>
                    <td class="text-center"><?php echo $no++; ?></td>
                    <td class="text-center"><?php echo date('d/m', strtotime($row['tanggal'])); ?></td>
                    <td class="text-center" style="font-size: 9px;"><?php echo $row['invoice']; ?></td>
                    <td>
                        <strong><?php echo htmlspecialchars($row['nama_customer'] ?? 'Umum'); ?></strong><br>
                        <?php echo htmlspecialchars($row['merek_hp'] . ' ' . $row['tipe_hp']); ?>
                    </td>
                    <td><?php echo htmlspecialchars($row['kerusakan']); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($row['nama_teknisi'] ?? '-'); ?></td>
                    <td class="text-center"><?php echo $row['status_service']; ?></td>
                    <td class="text-right">Rp <?php echo number_format($row['sub_total'], 0, ',', '.'); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Weekly Summary (Grid Layout) -->
    <div class="weekly-summary-grid">
        <!-- Ringkasan Status -->
        <div class="ws-box">
            <div class="ws-title">Ringkasan Status Service</div>
            <?php if(empty($status_summary)): ?>
                <div class="text-center">-</div>
            <?php else: ?>
                <?php foreach($status_summary as $status => $count): ?>
                <div class="ws-item">
                    <span><?php echo $status; ?></span>
                    <span class="text-bold"><?php echo $count; ?> Unit</span>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Ringkasan Keuangan -->
        <div class="ws-box">
            <div class="ws-title">Total Periode Ini</div>
            <div class="ws-item">
                <span>Total Unit Masuk:</span>
                <span class="text-bold"><?php echo count($data_laporan); ?> Unit</span>
            </div>
            <div class="ws-item" style="margin-top: 10px; font-size: 14px;">
                <span>Est. Pendapatan:</span>
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

    <div class="print-footer">
        <div>Dicetak pada: <?php echo date('d/m/Y H:i:s'); ?></div>
        <div>Page 1 of 1</div>
    </div>

</div>

<?php require_once 'includes/footer.php'; ?>