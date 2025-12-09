<?php
// penjualan_sparepart_view.php

// Sertakan header halaman (wajib)
require_once 'includes/header.php';

// Cek hak akses
if (get_user_role() !== 'owner' && get_user_role() !== 'admin') {
    header("Location: index.php");
    exit();
}

$message = '';
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'success') {
        $message = "<div class='alert alert-success'>Penjualan Sparepart berhasil dicatat dan stok telah dikurangi!</div>";
    } elseif ($_GET['status'] == 'fail' && isset($_GET['message'])) {
        $message = "<div class='alert alert-danger'>Gagal mencatat penjualan: " . htmlspecialchars($_GET['message']) . "</div>";
    }
}

// --- Data untuk Dropdown ---
$spareparts_list = [];
$result_spareparts = $conn->query("SELECT code_sparepart, nama, harga_jual, stok_tersedia FROM master_sparepart WHERE stok_tersedia > 0 ORDER BY nama ASC");
while($row = $result_spareparts->fetch_assoc()) {
    $spareparts_list[] = $row;
}

$customers = [];
$result_cust = $conn->query("SELECT id, nama, kontak as no_hp FROM customers ORDER BY nama ASC"); // Pastikan pakai 'customers' dan 'kontak'
while($row = $result_cust->fetch_assoc()){ $customers[] = $row; }


// --- Logika UNION untuk Menggabungkan Semua Transaksi Sparepart ---
// 1. Penggunaan Internal (Service) & Direct Sales
// 2. Penggunaan Eksternal (Service)

$sql_union = "
    SELECT * FROM (
        -- 1. SPAREPART INTERNAL (Via Service & Direct)
        SELECT
            sk.id as id_transaksi,
            sk.tanggal_keluar as tanggal,
            CASE 
                WHEN sk.invoice_service LIKE 'DIRECT-%' THEN 'Penjualan Langsung'
                ELSE 'Penggunaan Service (Toko)'
            END as tipe,
            sk.invoice_service as referensi,
            sk.code_sparepart,
            ms.nama as nama_sparepart,
            sk.jumlah,
            ms.harga_jual as harga_satuan_jual,
            (sk.jumlah * ms.harga_jual) as subtotal_jual,
            CASE 
                WHEN sk.invoice_service LIKE 'DIRECT-%' THEN 'Lunas'
                ELSE s.status_pembayaran
            END as status_bayar
        FROM sparepart_keluar sk
        JOIN master_sparepart ms ON sk.code_sparepart = ms.code_sparepart
        LEFT JOIN service s ON sk.invoice_service = s.invoice

        UNION ALL

        -- 2. SPAREPART EKSTERNAL (Via Service)
        SELECT
            0 as id_transaksi, -- ID Pembelian varchar, kita set 0 atau hash nanti jika perlu
            psl.tanggal_beli as tanggal,
            'Penggunaan Service (Luar)' as tipe,
            psl.invoice_service as referensi,
            'EXTERNAL' as code_sparepart,
            psl.nama_sparepart,
            psl.jumlah,
            psl.harga_jual as harga_satuan_jual,
            psl.total_jual as subtotal_jual,
            s.status_pembayaran as status_bayar
        FROM pembelian_sparepart_luar psl
        LEFT JOIN service s ON psl.invoice_service = s.invoice
    ) AS gabungan
    ORDER BY tanggal DESC
";

$transaksi_data = [];
$result_all = $conn->query($sql_union);

if ($result_all) {
    while ($row = $result_all->fetch_assoc()) {
        // Logika nama pelanggan
        if (strpos($row['referensi'], 'DIRECT-') === 0) {
            // Parse ID Customer dari string DIRECT-P{id}-T...
            $parts = explode('-', $row['referensi']);
            $cust_id_str = str_replace('P', '', $parts[1] ?? '');
            $row['pelanggan_display'] = "Direct (ID: $cust_id_str)";
        } else {
            // Invoice Service
            $row['pelanggan_display'] = $row['referensi'];
        }
        
        $transaksi_data[] = $row;
    }
}

$conn->close();
?>

<!-- Style khusus (disamakan dengan stok_sparepart.php) -->
<style>
    .form-container { padding: 24px; margin-bottom: 24px; }
    .form-grid-3 { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px 24px; }
    .form-group label { margin-bottom: 8px; font-weight: 500; color: var(--text-secondary); font-size: 13px; }
    .form-group input, .form-group select { width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background-color: #fff; }
    .form-footer { margin-top: 16px; text-align: right; }
    .data-table-container { padding: 24px; }
    .data-table-header { 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        margin-bottom: 16px; 
    }
    .data-table { width: 100%; border-collapse: collapse; min-width: 800px; /* Lebar minimum untuk responsif */ }
    .data-table th, .data-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid var(--border-color); white-space: nowrap; }
    .data-table thead th { background-color: #f8f9fa; color: var(--text-secondary); font-weight: 600; font-size: 12px; text-transform: uppercase; }
    .data-table tbody tr:hover { background-color: #f1f1f1; }
    .alert { padding: 15px; margin-bottom: 20px; border: 1px solid transparent; border-radius: .25rem; }
    .alert-success { color: #155724; background-color: #d4edda; border-color: #c3e6cb; }
    .alert-danger { color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; }
    
    .badge-tipe { padding: 4px 8px; border-radius: 6px; font-size: 11px; font-weight: 500; color: white; }
    .bg-direct { background-color: var(--accent-success); }
    .bg-service-toko { background-color: var(--accent-primary); }
    .bg-service-luar { background-color: #ff9500; } /* Orange */

    /* Tombol Hapus */
    .btn-delete-selected {
        background-color: var(--accent-danger);
        color: white;
        padding: 8px 16px;
        border-radius: 8px;
        font-weight: 500;
        display: none; /* Sembunyikan secara default */
    }
    /* Gaya untuk ikon gembok/disabled */
    .disabled-icon {
        color: #adb5bd; /* Abu-abu */
        font-size: 16px;
    }
    
    @media (max-width: 768px) {
        .data-table th, .data-table td { font-size: 12px; }
    }
</style>

<!-- KONTEN UTAMA HALAMAN -->
<h1 class="page-title">Penjualan Sparepart</h1>

<?php echo $message; // Tampilkan pesan sukses atau error di sini ?>

<!-- Form Penjualan Sparepart Langsung -->
<div class="form-container glass-effect">
    <h2 style="margin-bottom: 16px;">Penjualan Sparepart (Langsung)</h2>
    <form action="penjualan_sparepart_handler.php" method="POST" id="directSellForm">
        <input type="hidden" name="jenis_transaksi" value="direct_sell">
        <div class="form-grid-3">
            
            <div class="form-group">
                <label for="pelanggan_id">Pilih Pelanggan</label>
                <select id="pelanggan_id" name="pelanggan_id" class="form-control" required>
                    <option value="">--- Pilih Konsumen ---</option>
                    <?php foreach($customers as $customer): ?>
                    <option value="<?php echo $customer['id']; ?>"><?php echo htmlspecialchars($customer['nama']) . ' (' . htmlspecialchars($customer['no_hp']) . ')'; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="code_sparepart">Pilih Sparepart</label>
                <select id="sparepart_jual_code" name="code_sparepart" class="form-control" required>
                    <option value="">--- Pilih Sparepart ---</option>
                    <?php foreach($spareparts_list as $part): ?>
                    <option value="<?php echo $part['code_sparepart']; ?>" data-harga="<?php echo $part['harga_jual']; ?>" data-stok="<?php echo $part['stok_tersedia']; ?>">
                        <?php echo htmlspecialchars($part['nama']); ?> (Stok: <?php echo $part['stok_tersedia']; ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="jumlah_jual">Jumlah Jual</label>
                <input type="number" id="jumlah_jual" name="jumlah_jual" value="1" min="1" class="form-control" required>
            </div>
             
             <div class="form-group">
                <label for="harga_satuan_jual">Harga Satuan</label>
                <input type="number" id="harga_satuan_jual" name="harga_satuan_jual" readonly class="form-control" value="0">
            </div>
            
             <div class="form-group">
                <label for="total_jual">Total Harga</label>
                <input type="number" id="total_jual" name="total_jual" readonly class="form-control" value="0">
            </div>

            <div class="form-group">
                <label for="status_pembayaran_jual">Status Pembayaran</label>
                <select name="status_pembayaran_jual" class="form-control">
                    <option value="Lunas">Lunas</option>
                    <option value="Belum Lunas">Belum Lunas</option>
                </select>
            </div>
        </div>
        <div class="form-footer">
            <button type="submit" name="jual_sparepart" class="btn btn-primary">Catat Penjualan</button>
        </div>
    </form>
</div>

<!-- Tabel Riwayat Penjualan dan Penggunaan -->
<div class="data-table-container glass-effect">
    <form id="deleteTransactionForm">
        <div class="data-table-header">
            <h2>Riwayat Penjualan & Penggunaan Sparepart</h2>
            <button type="submit" id="deleteBtn" class="btn btn-delete-selected"><i class="fas fa-trash"></i> Hapus Terpilih</button>
        </div>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll"></th> <!-- Checkbox All -->
                        <th>ID</th>
                        <th>Tanggal</th>
                        <th>Tipe Transaksi</th>
                        <th>Pelanggan/Invoice</th>
                        <th>Code Sparepart</th>
                        <th>Nama Sparepart</th>
                        <th class="text-right">Jumlah</th>
                        <th class="text-right">Harga Jual Satuan</th>
                        <th class="text-right">Sub Total</th>
                        <th>Status Bayar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transaksi_data)): ?>
                        <tr>
                            <td colspan="11" style="text-align: center; padding: 20px;">Belum ada riwayat penjualan atau penggunaan sparepart.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($transaksi_data as $data): ?>
                            <?php 
                                $tipe_class = 'bg-service-toko';
                                if($data['tipe'] == 'Penjualan Langsung') $tipe_class = 'bg-direct';
                                if($data['tipe'] == 'Penggunaan Service (Luar)') $tipe_class = 'bg-service-luar';
                                
                                $is_direct_sell = ($data['tipe'] == 'Penjualan Langsung');
                            ?>
                            <tr>
                                <td>
                                    <?php if ($is_direct_sell): ?>
                                        <input type="checkbox" name="selected_ids[]" value="<?php echo htmlspecialchars($data['id_transaksi']); ?>" class="row-checkbox">
                                    <?php else: ?>
                                        <i class="fas fa-lock disabled-icon" title="Hapus via menu Service/Proses"></i>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($data['id_transaksi']); ?></td>
                                <td><?php echo date('d M Y, H:i', strtotime($data['tanggal'])); ?></td>
                                <td>
                                    <span class="badge-tipe <?php echo $tipe_class; ?>">
                                        <?php echo htmlspecialchars($data['tipe']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($data['pelanggan_display']); ?></td>
                                <td><?php echo htmlspecialchars($data['code_sparepart']); ?></td>
                                <td><?php echo htmlspecialchars($data['nama_sparepart']); ?></td>
                                <td class="text-right"><?php echo htmlspecialchars($data['jumlah']); ?></td>
                                <td class="text-right"><?php echo number_format($data['harga_satuan_jual'], 0, ',', '.'); ?></td>
                                <td class="text-right"><strong><?php echo number_format($data['subtotal_jual'], 0, ',', '.'); ?></strong></td>
                                <td>
                                    <span class="status-badge" style="background-color: <?php echo ($data['status_bayar'] == 'Lunas') ? 'var(--accent-success)' : 'var(--accent-danger)'; ?>; color: white; padding: 4px 8px; border-radius: 6px; font-size: 11px;">
                                        <?php echo htmlspecialchars($data['status_bayar'] ?? 'N/A'); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </form>
</div>


<?php
// Sertakan footer halaman (wajib)
require_once 'includes/footer.php';
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sparepartSelect = document.getElementById('sparepart_jual_code');
    const jumlahInput = document.getElementById('jumlah_jual');
    const hargaSatuanInput = document.getElementById('harga_satuan_jual');
    const totalInput = document.getElementById('total_jual');
    
    // --- Logika Checkbox & Hapus ---
    const selectAllCheckbox = document.getElementById('selectAll');
    // Hanya ambil checkbox yang TIDAK disabled (yaitu, Penjualan Langsung)
    const rowCheckboxes = document.querySelectorAll('.row-checkbox'); 
    const deleteBtn = document.getElementById('deleteBtn');
    const deleteForm = document.getElementById('deleteTransactionForm');

    function toggleDeleteButton() {
        const anyChecked = Array.from(rowCheckboxes).some(cb => cb.checked);
        deleteBtn.style.display = anyChecked ? 'block' : 'none';
    }

    selectAllCheckbox.addEventListener('change', function() {
        // Hanya cek/uncek checkbox yang TIDAK disabled
        rowCheckboxes.forEach(checkbox => {
            if (!checkbox.disabled) {
                checkbox.checked = this.checked;
            }
        });
        toggleDeleteButton();
    });

    rowCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            if (!this.checked) {
                selectAllCheckbox.checked = false;
            }
            toggleDeleteButton();
        });
    });

    deleteForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const selectedIds = Array.from(rowCheckboxes)
            .filter(cb => cb.checked && !cb.disabled)
            .map(cb => cb.value);

        if (selectedIds.length === 0) {
            alert('Silakan pilih setidaknya satu transaksi Penjualan Langsung untuk dihapus.');
            return;
        }

        if (!confirm(`Anda yakin ingin menghapus ${selectedIds.length} transaksi Penjualan Langsung ini? Stok dan Kas terkait akan dikembalikan/disesuaikan.`)) {
            return;
        }
        
        deleteBtn.disabled = true;
        deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menghapus...';

        fetch('delete_sparepart_transaction.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ selected_ids: selectedIds })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                // Muat ulang halaman untuk refresh data
                window.location.reload(); 
            } else {
                alert('Gagal menghapus: ' + (data.message || 'Error tidak diketahui.'));
            }
        })
        .catch(error => {
            console.error('Fetch Error:', error);
            alert('Terjadi kesalahan jaringan.');
        })
        .finally(() => {
            deleteBtn.disabled = false;
            deleteBtn.innerHTML = '<i class="fas fa-trash"></i> Hapus Terpilih';
        });
    });


    // --- Logika Hitung Total (sudah ada) ---
    function calculateTotal() {
        const selectedOption = sparepartSelect.options[sparepartSelect.selectedIndex];
        // Pastikan opsi sparepart terpilih
        if (!selectedOption || !selectedOption.value) {
            hargaSatuanInput.value = 0;
            totalInput.value = 0;
            return;
        }

        const hargaJual = parseFloat(selectedOption.dataset.harga) || 0;
        const stok = parseInt(selectedOption.dataset.stok) || 0;
        let jumlah = parseInt(jumlahInput.value) || 0;
        
        // Jika input kosong atau 0, anggap 1 untuk perhitungan awal, 
        // tapi validasi tetap dilakukan terhadap nilai sebenarnya
        if (isNaN(jumlah) || jumlah <= 0) {
            jumlah = 1;
        }

        // Validasi stok
        if (jumlah > stok) {
            alert(`Stok hanya tersedia ${stok}. Jumlah dikoreksi.`);
            
            // LOGIKA BARU: Koreksi kuantitas ke jumlah stok yang tersedia (misal: 0 atau 5)
            // Jika stok 0, kuantitas dikoreksi ke 0.
            jumlah = stok; 
            
            // Update nilai di input
            jumlahInput.value = jumlah;
        }

        // Perhitungan total (menggunakan nilai jumlah yang sudah dikoreksi)
        hargaSatuanInput.value = hargaJual;
        totalInput.value = hargaJual * jumlah;
    }

    sparepartSelect.addEventListener('change', calculateTotal);
    jumlahInput.addEventListener('input', calculateTotal);
    
    // Pemicu perubahan awal jika ada opsi yang sudah terpilih secara default
    if (sparepartSelect.value) {
        calculateTotal();
    }
});
</script>