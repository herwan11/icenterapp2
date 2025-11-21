<?php
// customers.php

require_once 'includes/header.php';

// --- Query Data Pelanggan & Statistik ---
// Menggunakan Subquery untuk menghitung riwayat transaksi secara efisien
$sql = "SELECT p.*, 
        (SELECT COUNT(*) FROM service s WHERE s.customer_id = p.id) as total_service,
        (SELECT COUNT(*) FROM penjualan_sparepart ps WHERE ps.pelanggan_id = p.id) as total_beli_part,
        (SELECT COUNT(*) FROM penjualan_perangkat pp WHERE pp.pelanggan_id = p.id) as total_beli_perangkat
        FROM pelanggan p 
        ORDER BY p.nama ASC";

$result = $conn->query($sql);
$customers = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $customers[] = $row;
    }
}
?>

<!-- Style khusus (Mengikuti tema Waiting List & All Data) -->
<style>
    .data-table-container { padding: 24px; }
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table th, .data-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #e9ecef; vertical-align: middle; }
    .data-table thead th { background-color: #f8f9fa; font-weight: 600; color: #495057; text-transform: uppercase; font-size: 12px; }
    .data-table tbody tr:hover { background-color: #f1f3f5; }
    
    /* Badge untuk riwayat */
    .history-badge { 
        display: inline-flex; 
        align-items: center; 
        gap: 6px; 
        padding: 4px 10px; 
        border-radius: 20px; 
        font-size: 11px; 
        font-weight: 600;
        color: white;
    }
    .bg-service { background-color: #007aff; } /* Biru */
    .bg-shop { background-color: #34c759; }    /* Hijau */
    .bg-none { background-color: #e5e5ea; color: #8a8a8e; } /* Abu-abu */

    .contact-info { display: flex; flex-direction: column; }
    .contact-wa { font-size: 12px; color: #8a8a8e; display: flex; align-items: center; gap: 4px; }
    .btn-wa { color: #25D366; text-decoration: none; font-weight: 600; }
    .btn-wa:hover { text-decoration: underline; }

    /* Modal Styles (Reuse from waiting_list.php) */
    .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6); align-items: center; justify-content: center; backdrop-filter: blur(5px); }
    .modal-content { background-color: #ffffff; margin: auto; padding: 0; border: none; width: 90%; max-width: 700px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); overflow: hidden; }
    .modal-header { display: flex; justify-content: space-between; align-items: center; background-color: #f8f9fa; border-bottom: 1px solid #dee2e6; padding: 16px 24px; }
    .modal-header h2 { font-size: 18px; margin: 0; color: #333; }
    .modal-body { padding: 24px; max-height: 70vh; overflow-y: auto; }
    .modal-footer { background-color: #f8f9fa; border-top: 1px solid #dee2e6; padding: 16px 24px; text-align: right; }
    .close-btn { color: #6c757d; font-size: 28px; font-weight: bold; cursor: pointer; background: none; border: none; }
    
    .detail-section { margin-bottom: 24px; }
    .detail-section h4 { font-size: 14px; text-transform: uppercase; color: #8a8a8e; margin-bottom: 12px; border-bottom: 1px solid #eee; padding-bottom: 6px; }
    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .info-item label { display: block; font-size: 12px; color: #8a8a8e; margin-bottom: 4px; }
    .info-item p { font-size: 14px; font-weight: 500; color: #1c1c1e; }
    
    .mini-table { width: 100%; font-size: 13px; border-collapse: collapse; }
    .mini-table th { text-align: left; color: #8a8a8e; font-weight: 500; border-bottom: 1px solid #eee; padding: 8px; }
    .mini-table td { padding: 8px; border-bottom: 1px solid #f5f5f7; }
    .status-pill { padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: 600; color: white; }
</style>

<!-- KONTEN UTAMA -->
<h1 class="page-title">Data Pelanggan</h1>

<div class="glass-effect data-table-container">
    <div class="table-header" style="display:flex; justify-content:space-between; margin-bottom:16px;">
        <h3>List Pelanggan Terdaftar</h3>
        <!-- Tombol Tambah Customer (Opsional, karena sudah ada di Input Service) -->
        <button onclick="alert('Fitur tambah pelanggan langsung akan segera hadir. Gunakan menu Input Service untuk saat ini.')" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Pelanggan Baru</button>
    </div>

    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nama Pelanggan</th>
                    <th>Kontak / WhatsApp</th>
                    <th>Alamat</th>
                    <th>Riwayat Service</th>
                    <th>Riwayat Belanja</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($customers)): ?>
                    <tr><td colspan="6" style="text-align: center; padding: 20px;">Belum ada data pelanggan.</td></tr>
                <?php else: ?>
                    <?php foreach ($customers as $c): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($c['nama']); ?></strong>
                                <br><span style="font-size:11px; color:#888;">ID: <?php echo $c['id']; ?></span>
                            </td>
                            <td>
                                <div class="contact-info">
                                    <span><?php echo htmlspecialchars($c['no_hp']); ?></span>
                                    <span class="contact-wa">
                                        <i class="fab fa-whatsapp"></i> 
                                        <a href="https://wa.me/<?php echo preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $c['no_hp'])); ?>" target="_blank" class="btn-wa">Chat WA</a>
                                    </span>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($c['alamat']); ?></td>
                            
                            <!-- Kolom Riwayat Service -->
                            <td>
                                <?php if ($c['total_service'] > 0): ?>
                                    <span class="history-badge bg-service">
                                        <i class="fas fa-tools"></i> <?php echo $c['total_service']; ?>x Service
                                    </span>
                                <?php else: ?>
                                    <span class="history-badge bg-none">Belum Pernah</span>
                                <?php endif; ?>
                            </td>

                            <!-- Kolom Riwayat Belanja (Sparepart + Perangkat) -->
                            <td>
                                <?php 
                                    $total_belanja = $c['total_beli_part'] + $c['total_beli_perangkat'];
                                    if ($total_belanja > 0): 
                                ?>
                                    <span class="history-badge bg-shop">
                                        <i class="fas fa-shopping-bag"></i> <?php echo $total_belanja; ?>x Transaksi
                                    </span>
                                <?php else: ?>
                                    <span class="history-badge bg-none">Belum Pernah</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <button class="btn btn-tertiary btn-sm btn-detail" data-id="<?php echo $c['id']; ?>" title="Lihat Detail"><i class="fas fa-eye"></i></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL DETAIL PELANGGAN -->
<div id="customerDetailModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Detail Pelanggan</h2>
            <button type="button" class="close-btn">&times;</button>
        </div>
        <div class="modal-body" id="modalContent">
            <!-- Konten akan diisi via AJAX -->
            <div style="text-align:center; padding:20px;">Memuat data...</div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary close-btn">Tutup</button>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('customerDetailModal');
    const closeBtns = modal.querySelectorAll('.close-btn');
    const detailBtns = document.querySelectorAll('.btn-detail');
    const modalContent = document.getElementById('modalContent');

    function formatRupiah(angka) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
    }

    function getStatusColor(status) {
        status = status.toLowerCase();
        if(status === 'selesai' || status === 'lunas') return '#34c759'; // Hijau
        if(status === 'proses') return '#007aff'; // Biru
        if(status === 'batal') return '#ff3b30'; // Merah
        return '#8a8a8e'; // Abu
    }

    detailBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const customerId = this.dataset.id;
            modal.style.display = 'flex';
            modalContent.innerHTML = '<div style="text-align:center; padding:20px;"><i class="fas fa-spinner fa-spin"></i> Memuat data...</div>';

            fetch(`get_customer_detail.php?id=${customerId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const c = data.customer;
                        
                        let serviceHtml = '';
                        if(data.services.length > 0) {
                            serviceHtml = `<table class="mini-table">
                                <thead><tr><th>Tanggal</th><th>Perangkat</th><th>Kerusakan</th><th>Status</th><th>Biaya</th></tr></thead>
                                <tbody>${data.services.map(s => `
                                    <tr>
                                        <td>${s.tanggal.split(' ')[0]}</td>
                                        <td>${s.merek_hp} ${s.tipe_hp}</td>
                                        <td>${s.kerusakan}</td>
                                        <td><span class="status-pill" style="background:${getStatusColor(s.status_service)}">${s.status_service}</span></td>
                                        <td>${formatRupiah(s.sub_total)}</td>
                                    </tr>
                                `).join('')}</tbody></table>`;
                        } else {
                            serviceHtml = '<p style="color:#888; font-style:italic;">Belum ada riwayat service.</p>';
                        }

                        let purchaseHtml = '';
                        if(data.purchases.length > 0) {
                            purchaseHtml = `<table class="mini-table">
                                <thead><tr><th>Tanggal</th><th>Total</th><th>Status</th></tr></thead>
                                <tbody>${data.purchases.map(p => `
                                    <tr>
                                        <td>${p.tanggal}</td>
                                        <td>${formatRupiah(p.total)}</td>
                                        <td><span class="status-pill" style="background:${getStatusColor(p.status_pembayaran)}">${p.status_pembayaran}</span></td>
                                    </tr>
                                `).join('')}</tbody></table>`;
                        } else {
                            purchaseHtml = '<p style="color:#888; font-style:italic;">Belum ada riwayat pembelian sparepart.</p>';
                        }

                        modalContent.innerHTML = `
                            <div class="detail-section">
                                <h4>Profil</h4>
                                <div class="info-grid">
                                    <div class="info-item"><label>Nama</label><p>${c.nama}</p></div>
                                    <div class="info-item"><label>Kontak</label><p>${c.no_hp}</p></div>
                                    <div class="info-item" style="grid-column: span 2;"><label>Alamat</label><p>${c.alamat || '-'}</p></div>
                                    <div class="info-item" style="grid-column: span 2;"><label>Keluhan Utama/Catatan</label><p>${c.keluhan || '-'}</p></div>
                                </div>
                            </div>
                            <div class="detail-section">
                                <h4>5 Riwayat Service Terakhir</h4>
                                ${serviceHtml}
                            </div>
                            <div class="detail-section">
                                <h4>5 Pembelian Sparepart Terakhir</h4>
                                ${purchaseHtml}
                            </div>
                        `;
                    } else {
                        modalContent.innerHTML = `<div style="color:red; text-align:center;">${data.message}</div>`;
                    }
                })
                .catch(err => {
                    console.error(err);
                    modalContent.innerHTML = '<div style="color:red; text-align:center;">Terjadi kesalahan koneksi.</div>';
                });
        });
    });

    closeBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            modal.style.display = 'none';
        });
    });

    window.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });
});
</script>