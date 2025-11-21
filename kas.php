<?php
// kas_toko.php
require_once 'includes/header.php';

// Ambil saldo kas saat ini
$saldo_saat_ini = 0;
// Harap pastikan Anda sudah menjalankan 'perbaikan_kas.sql' untuk menambahkan kolom saldo_terakhir
$result_saldo = $conn->query("SELECT saldo_terakhir FROM transaksi_kas ORDER BY id DESC LIMIT 1");
if ($result_saldo->num_rows > 0) {
    $saldo_saat_ini = $result_saldo->fetch_assoc()['saldo_terakhir'];
}

// Ambil riwayat transaksi
$riwayat_kas = [];
// Harap pastikan kolom 'jumlah' dan 'saldo_terakhir' sudah ada
$result_riwayat = $conn->query("SELECT * FROM transaksi_kas ORDER BY id DESC");
if ($result_riwayat) {
    while($row = $result_riwayat->fetch_assoc()){
        $riwayat_kas[] = $row;
    }
}
?>

<!-- Style khusus untuk halaman kas -->
<style>
    /* Menggunakan kembali sebagian besar gaya dari style.css dan menambahkan detail */
    .kas-header { 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        gap: 20px; 
        margin-bottom: 24px; 
        flex-wrap: wrap; 
    }
    .saldo-card { 
        padding: 24px 30px; 
        flex-grow: 1; 
        min-width: 250px;
        border-left: 5px solid var(--accent-primary); 
    }
    .saldo-card p { 
        color: var(--text-secondary); 
        margin-bottom: 4px; 
        font-size: 14px;
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
    .btn-masuk, .btn-keluar {
        padding: 12px 20px;
        border-radius: 10px;
        font-weight: 600;
        transition: transform 0.2s;
        font-size: 14px;
    }
    .btn-masuk { 
        background-color: var(--accent-success); 
        color: white; 
        box-shadow: 0 4px 12px rgba(52, 199, 89, 0.3);
    }
    .btn-keluar { 
        background-color: var(--accent-danger); 
        color: white; 
        box-shadow: 0 4px 12px rgba(255, 59, 48, 0.3);
    }
    .btn-masuk:hover, .btn-keluar:hover { transform: translateY(-2px); }

    /* === Gaya Tabel Riwayat Responsif === */
    .data-table { 
        width: 100%; 
        border-collapse: collapse; 
        min-width: 600px; /* Lebar minimum untuk mencegah penumpukan di tablet */
    }
    .data-table th, .data-table td { 
        padding: 12px 15px; 
        text-align: left; 
        border-bottom: 1px solid var(--border-color); 
        vertical-align: middle; 
        white-space: nowrap; /* Mencegah baris pecah */
    }
    .data-table thead th { 
        background-color: #f8f9fa; 
        color: var(--text-secondary); 
        font-weight: 600; 
        font-size: 12px; 
        text-transform: uppercase;
    }
    .data-table tbody tr:hover { 
        background-color: #f1f3f5; 
    }
    
    .text-masuk { color: var(--accent-success); font-weight: 600; }
    .text-keluar { color: var(--accent-danger); font-weight: 600; }
    .saldo-final {
        font-weight: 700;
        color: var(--accent-primary);
    }

    /* === Gaya Modal Form === */
    .modal-content {
        max-width: 450px; 
    }
    .modal-header h2 {
        font-size: 20px;
        font-weight: 600;
    }
    .modal-body { 
        padding: 24px; 
    }
    .form-group {
        margin-bottom: 20px;
    }
    .form-group label {
        display: block;
        font-size: 13px;
        font-weight: 500;
        color: #555;
        margin-bottom: 6px;
    }
    .form-control {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #ddd;
        border-radius: 8px;
        background-color: #fff;
    }
    .modal-footer button {
        padding: 10px 18px;
        border-radius: 8px;
        font-weight: 600;
    }

    /* === MEDIA QUERY UNTUK RESPONSIVITAS MOBILE (<= 768px) === */
    @media (max-width: 768px) {
        .kas-header {
            flex-direction: column; /* Tumpuk item secara vertikal */
            align-items: stretch; /* Regangkan item agar mengisi lebar penuh */
        }
        .saldo-card {
            min-width: 100%; /* Kartu saldo mengambil lebar penuh */
        }
        .action-buttons {
            width: 100%; /* Kotak tombol mengambil lebar penuh */
            justify-content: space-between; /* Tombol dibagi rata */
        }
        .btn-masuk, .btn-keluar {
            flex: 1; /* Tombol mengisi ruang yang tersedia */
            text-align: center;
        }
        .data-table th, .data-table td {
            padding: 8px 10px; /* Kurangi padding di mobile */
            font-size: 13px;
        }
        /* Memastikan tabel tetap di dalam table-wrapper untuk scroll horizontal */
        .table-wrapper {
            overflow-x: auto;
        }
    }
</style>

<!-- KONTEN UTAMA HALAMAN -->
<h1 class="page-title">Manajemen Kas Toko</h1>

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
                                <td class="text-right saldo-final"><strong><?php echo number_format($kas['saldo_terakhir'], 0, ',', '.'); ?></strong></td>
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
        
        // Nonaktifkan tombol untuk mencegah double-click
        const submitButton = document.getElementById('btnSimpanKas');
        submitButton.disabled = true;
        submitButton.textContent = 'Menyimpan...';

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
                // Menggunakan data.new_saldo dan data.new_trx
                const newTrx = data.new_trx;
                const formattedSaldo = new Intl.NumberFormat('id-ID').format(data.new_saldo);
                saldoDisplay.textContent = `Rp ${formattedSaldo}`;

                // Atur nilai Masuk dan Keluar
                const masuk = newTrx.jenis === 'masuk' ? new Intl.NumberFormat('id-ID').format(newTrx.jumlah) : '-';
                const keluar = newTrx.jenis === 'keluar' ? new Intl.NumberFormat('id-ID').format(newTrx.jumlah) : '-';
                
                // Mendapatkan tanggal saat ini untuk tampilan riwayat (PHP sudah menangani tanggal aslinya)
                const now = new Date();
                const dateOptions = { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' };
                const formattedDate = now.toLocaleDateString('id-ID', dateOptions).replace(/\./g, '');

                // Tambah baris baru ke tabel riwayat
                const newRow = `
                    <tr>
                        <td>${formattedDate}</td>
                        <td>${newTrx.keterangan}</td>
                        <td class="text-right text-masuk">${masuk}</td>
                        <td class="text-right text-keluar">${keluar}</td>
                        <td class="text-right saldo-final"><strong>${formattedSaldo}</strong></td>
                    </tr>
                `;
                // Hapus pesan "belum ada data" jika ada
                const noDataRow = riwayatBody.querySelector('td[colspan="5"]');
                if (noDataRow) noDataRow.parentElement.remove();
                
                // Masukkan baris baru di posisi paling atas
                riwayatBody.insertAdjacentHTML('afterbegin', newRow);

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
            submitButton.disabled = false;
            submitButton.textContent = 'Simpan';
        });
    });
});
</script>