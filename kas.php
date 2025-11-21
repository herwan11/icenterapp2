<?php
// kas_toko.php
require_once 'includes/header.php';

// Ambil saldo kas saat ini
$saldo_saat_ini = 0;
$result_saldo = $conn->query("SELECT saldo_terakhir FROM transaksi_kas ORDER BY id DESC LIMIT 1");
if ($result_saldo->num_rows > 0) {
    $saldo_saat_ini = $result_saldo->fetch_assoc()['saldo_terakhir'];
}

// Ambil riwayat transaksi
$riwayat_kas = [];
$result_riwayat = $conn->query("SELECT * FROM transaksi_kas ORDER BY id DESC");
if ($result_riwayat) {
    while($row = $result_riwayat->fetch_assoc()){
        $riwayat_kas[] = $row;
    }
}
?>

<!-- Style khusus untuk halaman kas -->
<style>
    .kas-header { display: flex; justify-content: space-between; align-items: center; gap: 20px; margin-bottom: 24px; flex-wrap: wrap; }
    .saldo-card { padding: 20px 24px; flex-grow: 1; }
    .saldo-card p { color: var(--text-secondary); margin-bottom: 8px; }
    .saldo-card h2 { font-size: 32px; color: var(--accent-primary); }
    .action-buttons { display: flex; gap: 12px; }
    .btn-masuk { background-color: var(--accent-success); color: white; }
    .btn-keluar { background-color: var(--accent-danger); color: white; }
    .text-masuk { color: var(--accent-success); font-weight: 500; }
    .text-keluar { color: var(--accent-danger); font-weight: 500; }
    /* Gaya untuk modal yang sudah ada di CSS utama, hanya sedikit penyesuaian */
    .modal-body .form-group label { text-align: left; }
</style>

<!-- KONTEN UTAMA HALAMAN -->
<div class="kas-header">
    <div class="saldo-card glass-effect">
        <p>Saldo Kas Saat Ini</p>
        <h2 id="saldoDisplay">Rp <?php echo number_format($saldo_saat_ini, 0, ',', '.'); ?></h2>
    </div>
    <div class="action-buttons">
        <button id="btnTambahPemasukan" class="btn btn-masuk"><i class="fas fa-plus"></i> Tambah Pemasukan</button>
        <button id="btnCatatPengeluaran" class="btn btn-keluar"><i class="fas fa-minus"></i> Catat Pengeluaran</button>
    </div>
</div>

<div class="card glass-effect">
    <div class="card-body">
        <h2 class="card-title" style="margin-bottom: 16px;">Riwayat Transaksi Kas</h2>
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
                <tbody id="riwayatKasBody">
                    <?php if (empty($riwayat_kas)): ?>
                        <tr><td colspan="5" style="text-align: center;">Belum ada riwayat transaksi.</td></tr>
                    <?php else: ?>
                        <?php foreach ($riwayat_kas as $kas): ?>
                            <tr>
                                <td><?php echo date('d M Y, H:i', strtotime($kas['tanggal'])); ?></td>
                                <td><?php echo htmlspecialchars($kas['keterangan']); ?></td>
                                <td class="text-right text-masuk"><?php echo ($kas['jenis'] == 'masuk') ? number_format($kas['jumlah'], 0, ',', '.') : '-'; ?></td>
                                <td class="text-right text-keluar"><?php echo ($kas['jenis'] == 'keluar') ? number_format($kas['jumlah'], 0, ',', '.') : '-'; ?></td>
                                <td class="text-right"><strong><?php echo number_format($kas['saldo_terakhir'], 0, ',', '.'); ?></strong></td>
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
                <input type="hidden" id="jenisTransaksi" name="jenis">
                <div class="form-group">
                    <label for="jumlah">Jumlah *</label>
                    <input type="number" id="jumlah" name="jumlah" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="keterangan">Keterangan *</label>
                    <textarea id="keterangan" name="keterangan" rows="3" class="form-control" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary close-btn">Batal</button>
                <button type="submit" id="btnSimpanKas" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>


<?php require_once 'includes/footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('kasModal');
    const closeModalBtns = modal.querySelectorAll('.close-btn');
    const kasForm = document.getElementById('kasForm');
    const modalTitle = document.getElementById('modalKasTitle');
    const jenisInput = document.getElementById('jenisTransaksi');
    const saldoDisplay = document.getElementById('saldoDisplay');
    const riwayatBody = document.getElementById('riwayatKasBody');

    const openModal = (jenis, title) => {
        kasForm.reset();
        jenisInput.value = jenis;
        modalTitle.textContent = title;
        modal.style.display = 'flex';
    };

    document.getElementById('btnTambahPemasukan').addEventListener('click', () => openModal('masuk', 'Tambah Pemasukan Kas'));
    document.getElementById('btnCatatPengeluaran').addEventListener('click', () => openModal('keluar', 'Catat Pengeluaran Kas'));
    closeModalBtns.forEach(btn => btn.addEventListener('click', () => { modal.style.display = 'none'; }));

    kasForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const payload = {
            jenis: jenisInput.value,
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
                // Update saldo di kartu
                const formattedSaldo = new Intl.NumberFormat('id-ID').format(data.transaction.saldo_terakhir);
                saldoDisplay.textContent = `Rp ${formattedSaldo}`;

                // Tambah baris baru ke tabel riwayat
                const newRow = `
                    <tr>
                        <td>${data.transaction.tanggal}</td>
                        <td>${data.transaction.keterangan}</td>
                        <td class="text-right text-masuk">${data.transaction.masuk > 0 ? new Intl.NumberFormat('id-ID').format(data.transaction.masuk) : '-'}</td>
                        <td class="text-right text-keluar">${data.transaction.keluar > 0 ? new Intl.NumberFormat('id-ID').format(data.transaction.keluar) : '-'}</td>
                        <td class="text-right"><strong>${formattedSaldo}</strong></td>
                    </tr>
                `;
                // Hapus pesan "belum ada data" jika ada
                const noDataRow = riwayatBody.querySelector('td[colspan="5"]');
                if (noDataRow) noDataRow.parentElement.remove();
                
                riwayatBody.insertAdjacentHTML('afterbegin', newRow);

                modal.style.display = 'none';
            } else {
                alert('Gagal: ' + data.message);
            }
        })
        .catch(error => console.error('Error:', error));
    });
});
</script>
