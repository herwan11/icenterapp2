<?php
// kas.php

require_once 'includes/header.php';

// --- Ambil data dari database ---
// 1. Ambil saldo kas terakhir
$saldo_saat_ini = 0;
$result_saldo = $conn->query("SELECT saldo_terakhir FROM transaksi_kas ORDER BY id DESC LIMIT 1");
if ($result_saldo && $result_saldo->num_rows > 0) {
    $saldo_saat_ini = $result_saldo->fetch_assoc()['saldo_terakhir'];
}

// 2. Ambil riwayat transaksi
$transaksi = [];
$result_transaksi = $conn->query("SELECT * FROM transaksi_kas ORDER BY tanggal DESC, id DESC LIMIT 50"); // Batasi 50 transaksi terakhir
if ($result_transaksi) {
    while($row = $result_transaksi->fetch_assoc()) {
        $transaksi[] = $row;
    }
}
?>

<!-- Style khusus untuk halaman ini -->
<style>
    .kas-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 24px;
        margin-bottom: 24px;
    }
    .saldo-card {
        flex-grow: 1;
        padding: 24px;
    }
    .saldo-card p {
        font-size: 16px;
        color: var(--text-secondary);
        margin-bottom: 8px;
    }
    .saldo-card h2 {
        font-size: 36px;
        color: var(--text-primary);
        font-weight: 700;
    }
    .action-buttons {
        display: flex;
        gap: 12px;
        flex-shrink: 0;
    }
    .btn-kas {
        padding: 12px 20px;
        font-size: 14px;
    }
    .btn-success { background-color: var(--accent-success); color: white; box-shadow: 0 4px 12px rgba(52, 199, 89, 0.3);}
    .btn-danger { background-color: var(--accent-danger); color: white; box-shadow: 0 4px 12px rgba(255, 59, 48, 0.3); }

    /* Gaya untuk tabel riwayat agar mirip all_data_service */
    .text-masuk { color: var(--accent-success); font-weight: 500; }
    .text-keluar { color: var(--accent-danger); font-weight: 500; }
    
    /* Gaya untuk popup/modal yang lebih keren */
    .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); align-items: center; justify-content: center; backdrop-filter: blur(5px); }
    .modal-content { background-color: #ffffff; padding: 0; border: none; width: 90%; max-width: 500px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); overflow: hidden; }
    .modal-header { display: flex; justify-content: space-between; align-items: center; background-color: #f8f9fa; border-bottom: 1px solid #dee2e6; padding: 16px 24px; }
    .modal-header h2 { font-size: 18px; margin: 0; color: #333; }
    .modal-body { padding: 24px; }
    .modal-footer { background-color: #f8f9fa; border-top: 1px solid #dee2e6; padding: 16px 24px; text-align: right; }
    .close-btn { color: #6c757d; font-size: 28px; font-weight: bold; cursor: pointer; background: none; border: none; }
    
    @media (max-width: 768px) {
        .kas-header {
            flex-direction: column;
            align-items: stretch;
        }
    }
</style>

<!-- KONTEN UTAMA HALAMAN -->
<h1 class="page-title">Manajemen Kas Toko</h1>

<div class="kas-header">
    <div class="saldo-card glass-effect">
        <p>Saldo Kas Saat Ini</p>
        <h2 id="current-saldo">Rp <?php echo number_format($saldo_saat_ini, 0, ',', '.'); ?></h2>
    </div>
    <div class="action-buttons">
        <button id="btn-add-pemasukan" class="btn btn-success btn-kas"><i class="fas fa-plus"></i> Tambah Pemasukan</button>
        <button id="btn-add-pengeluaran" class="btn btn-danger btn-kas"><i class="fas fa-minus"></i> Catat Pengeluaran</button>
    </div>
</div>

<div class="card glass-effect">
    <div class="card-header">
        <h2 class="card-title">Riwayat Transaksi Kas</h2>
    </div>
    <div class="card-body">
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Keterangan</th>
                        <th class="text-right">Masuk</th>
                        <th class="text-right">Keluar</th>
                        <th class="text-right">Saldo</th>
                    </tr>
                </thead>
                <tbody id="transaksi-table-body">
                    <?php if (empty($transaksi)): ?>
                        <tr><td colspan="5" style="text-align:center; padding: 20px;">Belum ada transaksi kas.</td></tr>
                    <?php else: ?>
                        <?php foreach($transaksi as $trx): ?>
                        <tr>
                            <td><?php echo date('d M Y, H:i', strtotime($trx['tanggal'])); ?></td>
                            <td><?php echo htmlspecialchars($trx['keterangan']); ?></td>
                            <td class="text-right text-masuk"><?php echo ($trx['jenis'] == 'masuk') ? number_format($trx['jumlah'], 0, ',', '.') : '-'; ?></td>
                            <td class="text-right text-keluar"><?php echo ($trx['jenis'] == 'keluar') ? number_format($trx['jumlah'], 0, ',', '.') : '-'; ?></td>
                            <td class="text-right"><?php echo number_format($trx['saldo_terakhir'], 0, ',', '.'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal/Popup untuk Transaksi Kas -->
<div id="kasModal" class="modal">
    <div class="modal-content">
        <form id="kasForm">
            <div class="modal-header">
                <h2 id="modalKasTitle"></h2>
                <button type="button" class="close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="jenis_transaksi" name="jenis">
                <div class="form-group">
                    <label for="jumlah">Jumlah *</label>
                    <input type="number" id="jumlah" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="keterangan">Keterangan *</label>
                    <textarea id="keterangan" rows="3" class="form-control" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary close-btn">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Transaksi</button>
            </div>
        </form>
    </div>
</div>


<?php require_once 'includes/footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('kasModal');
    const modalTitle = document.getElementById('modalKasTitle');
    const jenisTransaksiInput = document.getElementById('jenis_transaksi');
    const closeModalBtns = modal.querySelectorAll('.close-btn');

    function openModal(jenis, title) {
        // PERBAIKAN: Urutan diubah. Reset dulu, baru isi nilainya.
        document.getElementById('kasForm').reset(); 
        jenisTransaksiInput.value = jenis;
        modalTitle.textContent = title;
        modal.style.display = 'flex';
    }

    document.getElementById('btn-add-pemasukan').addEventListener('click', function() {
        openModal('masuk', 'Tambah Pemasukan Kas');
    });

    document.getElementById('btn-add-pengeluaran').addEventListener('click', function() {
        openModal('keluar', 'Catat Pengeluaran Kas');
    });

    closeModalBtns.forEach(btn => btn.addEventListener('click', () => { modal.style.display = 'none'; }));

    document.getElementById('kasForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const submitButton = this.querySelector('button[type="submit"]');

        // PERBAIKAN: Mencegah double-click
        submitButton.disabled = true;
        submitButton.innerHTML = 'Menyimpan...';
        
        const payload = {
            jenis: jenisTransaksiInput.value,
            jumlah: document.getElementById('jumlah').value,
            keterangan: document.getElementById('keterangan').value,
        };

        fetch('add_kas_transaction.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update UI secara dinamis
                document.getElementById('current-saldo').textContent = 'Rp ' + data.new_saldo.toLocaleString('id-ID', { minimumFractionDigits: 0 });
                
                const tableBody = document.getElementById('transaksi-table-body');
                const noTrxRow = tableBody.querySelector('td[colspan="5"]');
                if(noTrxRow) noTrxRow.parentElement.remove();

                const newRow = `
                    <tr>
                        <td>Baru saja</td>
                        <td>${data.new_trx.keterangan}</td>
                        <td class="text-right text-masuk">${data.new_trx.jenis === 'masuk' ? data.new_trx.jumlah.toLocaleString('id-ID') : '-'}</td>
                        <td class="text-right text-keluar">${data.new_trx.jenis === 'keluar' ? data.new_trx.jumlah.toLocaleString('id-ID') : '-'}</td>
                        <td class="text-right">${data.new_trx.saldo_terakhir.toLocaleString('id-ID')}</td>
                    </tr>
                `;
                tableBody.insertAdjacentHTML('afterbegin', newRow);

                modal.style.display = 'none';
            } else {
                alert('Gagal: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan jaringan.');
        })
        .finally(() => {
             // PERBAIKAN: Aktifkan kembali tombol setelah selesai
            submitButton.disabled = false;
            submitButton.innerHTML = 'Simpan Transaksi';
        });
    });
});
</script>

