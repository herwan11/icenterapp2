<?php
// all_data_service.php

// Sertakan header halaman (wajib)
require_once 'includes/header.php';

// --- Ambil SEMUA data dari database ---
$service_data = [];

// Query diperbarui untuk mengambil data service utama
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
        $invoice = $row['invoice'];
        
        // --- 1. Ambil Sparepart Internal (Stok Toko) ---
        $internal_parts = [];
        $sql_internal = "SELECT ms.nama, sk.jumlah 
                         FROM sparepart_keluar sk 
                         JOIN master_sparepart ms ON sk.code_sparepart = ms.code_sparepart 
                         WHERE sk.invoice_service = '$invoice'";
        $res_internal = $conn->query($sql_internal);
        while($part = $res_internal->fetch_assoc()) {
            $internal_parts[] = $part['nama'] . " (" . $part['jumlah'] . ")";
        }

        // --- 2. Ambil Sparepart Eksternal (Beli Luar) ---
        $external_parts = [];
        $sql_external = "SELECT nama_sparepart, jumlah 
                         FROM pembelian_sparepart_luar 
                         WHERE invoice_service = '$invoice'";
        $res_external = $conn->query($sql_external);
        while($part = $res_external->fetch_assoc()) {
            $external_parts[] = $part['nama_sparepart'] . " (" . $part['jumlah'] . ")"; // Perbaikan nama kolom
        }

        // Gabungkan kedua jenis sparepart
        $all_spareparts = array_merge($internal_parts, $external_parts);
        
        // Simpan sebagai string yang dipisahkan koma, atau '-' jika kosong
        $row['sparepart_list'] = !empty($all_spareparts) ? implode(", ", $all_spareparts) : "-";

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
        font-size: 12px;
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
    .status-badge-all {
        padding: 5px 10px;
        border-radius: 15px;
        color: white;
        font-size: 11px;
        font-weight: 500;
        text-align: center;
    }
    /* Style khusus untuk kolom sparepart agar bisa wrap jika panjang */
    .col-sparepart {
        white-space: normal !important; 
        max-width: 250px;
        font-size: 11px;
        color: #555;
        line-height: 1.4;
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
                    <th>Kerusakan</th>
                    <th>Detail Sparepart</th> <!-- Kolom Baru -->
                    <th>Sub Total</th>
                    <th>Uang Muka</th>
                    <th>Status Service</th>
                    <th>Status Pembayaran</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($service_data)): ?>
                    <tr>
                        <td colspan="12" style="text-align: center; padding: 20px;">Belum ada data service.</td>
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
                            <td><?php echo htmlspecialchars($data['kerusakan']); ?></td>
                            
                            <!-- Menampilkan Sparepart -->
                            <td class="col-sparepart">
                                <?php echo htmlspecialchars($data['sparepart_list']); ?>
                            </td>

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