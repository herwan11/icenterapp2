<?php
// all_data_service.php

// Sertakan header halaman (wajib)
require_once 'includes/header.php';

// --- Ambil SEMUA data dari database ---
$service_data = [];
// Query diperbarui untuk mengambil semua kolom dan nama dari tabel relasi
$sql = "SELECT 
            s.*, 
            c.nama as nama_customer, 
            k.nama as nama_teknisi,
            u.nama as nama_kasir
        FROM service s
        LEFT JOIN customers c ON s.customer_id = c.id
        LEFT JOIN karyawan k ON s.teknisi_id = k.id
        LEFT JOIN users u ON s.kasir_id = u.id
        ORDER BY s.tanggal DESC";

$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $service_data[] = $row;
    }
}
$conn->close();

// Fungsi untuk menentukan warna status
function get_status_color($status) {
    switch (strtolower($status)) {
        case 'selesai':
        case 'diambil':
            return '#28a745'; // Hijau
        case 'proses':
            return '#007bff'; // Biru
        case 'batal':
        case 'refund':
            return '#dc3545'; // Merah
        case 'antrian':
        default:
            return '#ffc107'; // Kuning
    }
}
?>

<!-- Style khusus untuk tabel di halaman ini -->
<style>
    .data-table-container {
        padding: 24px;
    }
    .data-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px; /* Ukuran font lebih kecil untuk memuat banyak kolom */
    }
    .data-table th, .data-table td {
        padding: 10px 12px;
        text-align: left;
        border-bottom: 1px solid var(--border-color);
        white-space: nowrap;
    }
    .data-table thead th {
        background-color: #f8f9fa;
        color: var(--text-secondary);
        font-weight: 600;
        text-transform: uppercase;
        font-size: 11px;
    }
    .data-table tbody tr:hover {
        background-color: #f1f1f1;
    }
    /* Gaya untuk badge status */
    .status-badge-all {
        padding: 5px 10px;
        border-radius: 15px;
        color: white;
        font-size: 11px;
        font-weight: 500;
        text-align: center;
    }
</style>

<!-- Konten Utama Halaman -->
<h1 class="page-title">All Service Data</h1>

<div class="glass-effect data-table-container">
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Tanggal</th>
                    <th>Kasir</th>
                    <th>Customer</th>
                    <th>Teknisi</th>
                    <th>Perangkat</th>
                    <th>IMEI/SN</th>
                    <th>Kerusakan</th>
                    <th>Kelengkapan</th>
                    <th>Sub Total</th>
                    <th>Uang Muka</th>
                    <th>Status Service</th>
                    <th>Status Pembayaran</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($service_data)): ?>
                    <tr>
                        <td colspan="13" style="text-align: center; padding: 20px;">Belum ada data service.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($service_data as $data): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($data['invoice']); ?></td>
                            <td><?php echo date('d M Y, H:i', strtotime($data['tanggal'])); ?></td>
                            <td><?php echo htmlspecialchars($data['nama_kasir'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($data['nama_customer'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($data['nama_teknisi'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($data['merek_hp'] . ' ' . $data['tipe_hp']); ?></td>
                            <td><?php echo htmlspecialchars($data['imei_sn']); ?></td>
                            <td><?php echo htmlspecialchars($data['kerusakan']); ?></td>
                            <td><?php echo htmlspecialchars($data['kelengkapan']); ?></td>
                            <td><?php echo number_format($data['sub_total'], 0, ',', '.'); ?></td>
                            <td><?php echo number_format($data['uang_muka'], 0, ',', '.'); ?></td>
                            <td>
                                <span class="status-badge-all" style="background-color: <?php echo get_status_color($data['status_service']); ?>;">
                                    <?php echo htmlspecialchars($data['status_service']); ?>
                                </span>
                            </td>
                             <td>
                                <span class="status-badge-all" style="background-color: <?php echo ($data['status_pembayaran'] == 'Lunas') ? get_status_color('selesai') : get_status_color('batal'); ?>;">
                                    <?php echo htmlspecialchars($data['status_pembayaran']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>


<?php
// Sertakan footer halaman (wajib)
require_once 'includes/footer.php';
?>
