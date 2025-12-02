<?php
// sparepart_masuk.php

require_once 'includes/header.php';

// Cek hak akses (Hanya Admin & Owner)
if (get_user_role() !== 'owner' && get_user_role() !== 'admin') {
    echo "<script>alert('Akses ditolak.'); window.location.href='index.php';</script>";
    exit();
}

$message = '';

// --- Proses Penyimpanan Data ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_stok'])) {
    $code_sparepart = $_POST['code_sparepart'];
    $jumlah = intval($_POST['jumlah']);
    $tanggal = date('Y-m-d H:i:s');

    if ($code_sparepart && $jumlah > 0) {
        $conn->begin_transaction();
        try {
            // 1. Insert ke tabel riwayat sparepart_masuk
            $stmt_log = $conn->prepare("INSERT INTO sparepart_masuk (tanggal_masuk, code_sparepart, jumlah) VALUES (?, ?, ?)");
            $stmt_log->bind_param("ssi", $tanggal, $code_sparepart, $jumlah);
            
            if (!$stmt_log->execute()) {
                throw new Exception("Gagal mencatat riwayat: " . $stmt_log->error);
            }

            // 2. Update stok di master_sparepart
            $stmt_update = $conn->prepare("UPDATE master_sparepart SET stok_tersedia = stok_tersedia + ? WHERE code_sparepart = ?");
            $stmt_update->bind_param("is", $jumlah, $code_sparepart);
            
            if (!$stmt_update->execute()) {
                throw new Exception("Gagal update stok master: " . $stmt_update->error);
            }

            $conn->commit();
            $message = "<div class='alert alert-success'>Berhasil menambahkan stok sebanyak $jumlah unit untuk kode $code_sparepart.</div>";
        } catch (Exception $e) {
            $conn->rollback();
            $message = "<div class='alert alert-danger'>Terjadi kesalahan: " . $e->getMessage() . "</div>";
        }
    } else {
        $message = "<div class='alert alert-danger'>Data tidak valid. Pastikan memilih barang dan jumlah > 0.</div>";
    }
}

// --- Ambil Data Sparepart untuk Dropdown ---
$spareparts = [];
$sql_part = "SELECT code_sparepart, nama, supplier_merek, stok_tersedia FROM master_sparepart ORDER BY code_sparepart ASC";
$res_part = $conn->query($sql_part);
while ($row = $res_part->fetch_assoc()) {
    $spareparts[] = $row;
}

// --- Ambil Riwayat Masuk (SEMUA DATA untuk fitur search JS) ---
$history = [];
// Limit dihapus agar semua history termuat untuk pencarian lokal JS
$sql_hist = "SELECT sm.*, ms.nama, ms.supplier_merek 
             FROM sparepart_masuk sm 
             JOIN master_sparepart ms ON sm.code_sparepart = ms.code_sparepart 
             ORDER BY sm.tanggal_masuk DESC";
$res_hist = $conn->query($sql_hist);
if ($res_hist) {
    while ($row = $res_hist->fetch_assoc()) {
        $history[] = $row;
    }
}
?>

<style>
    .form-container { padding: 24px; margin-bottom: 24px; }
    .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 500; color: var(--text-secondary); }
    .form-control { width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background-color: #fff; font-size: 14px; }
    .form-control:focus { border-color: var(--accent-primary); outline: none; box-shadow: 0 0 0 3px rgba(0,122,255,0.1); }
    .form-control[readonly] { background-color: #f9f9f9; cursor: not-allowed; }
    
    .btn-submit { 
        margin-top: 20px; width: 100%; padding: 12px; 
        border-radius: 8px; font-weight: 600; 
        background: var(--accent-success); color: white; 
        border: none; cursor: pointer; transition: background 0.2s;
    }
    .btn-submit:hover { background: #2da44e; }
    
    /* Styling Tabel & Search */
    .table-header-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }
    .search-box {
        position: relative;
        width: 300px;
    }
    .search-box input {
        width: 100%;
        padding: 8px 12px 8px 35px;
        border: 1px solid #ddd;
        border-radius: 20px;
        font-size: 13px;
    }
    .search-box i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #888;
    }

    /* Scrollable Table Container */
    .table-scroll-wrapper {
        max-height: 400px; /* Batas tinggi scroll */
        overflow-y: auto;
        border: 1px solid #eee;
        border-radius: 8px;
    }
    
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table th { 
        position: sticky; 
        top: 0; 
        background: #f8f9fa; 
        color: #666; 
        font-weight: 600; 
        z-index: 1;
        padding: 12px;
        text-align: left;
        font-size: 13px;
        border-bottom: 2px solid #ddd;
    }
    .data-table td { 
        padding: 10px 12px; 
        border-bottom: 1px solid #eee; 
        text-align: left; 
        font-size: 13px; 
        color: #333;
    }
    .data-table tr:hover { background-color: #f5f5f7; }
</style>

<h1 class="page-title">Input Sparepart Masuk (Restock)</h1>

<?php echo $message; ?>

<div class="glass-effect form-container">
    <h3 style="margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px;">Form Tambah Stok</h3>
    
    <form method="POST" action="">
        <div class="form-grid-2">
            <!-- Kolom Kiri -->
            <div>
                <div class="form-group">
                    <label>Pilih Code Sparepart</label>
                    <select id="select_sparepart" name="code_sparepart" class="form-control" required>
                        <option value="">-- Cari Code / Nama --</option>
                        <?php foreach ($spareparts as $p): ?>
                        <option value="<?php echo $p['code_sparepart']; ?>" 
                                data-supplier="<?php echo htmlspecialchars($p['supplier_merek']); ?>"
                                data-stok="<?php echo $p['stok_tersedia']; ?>"
                                data-nama="<?php echo htmlspecialchars($p['nama']); ?>">
                            <?php echo $p['code_sparepart'] . ' - ' . htmlspecialchars($p['nama']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Jumlah Masuk</label>
                    <input type="number" name="jumlah" class="form-control" min="1" placeholder="Masukkan jumlah..." required>
                </div>
            </div>

            <!-- Kolom Kanan (Info Readonly) -->
            <div>
                <div class="form-group">
                    <label>Nama Barang</label>
                    <input type="text" id="info_nama" class="form-control" readonly placeholder="Otomatis terisi...">
                </div>
                <div class="form-group">
                    <label>Supplier / Merek</label>
                    <input type="text" id="info_supplier" class="form-control" readonly placeholder="Otomatis terisi...">
                </div>
                <div class="form-group">
                    <label>Stok Saat Ini</label>
                    <input type="text" id="info_stok_awal" class="form-control" readonly placeholder="0">
                </div>
            </div>
        </div>

        <button type="submit" name="simpan_stok" class="btn-submit"><i class="fas fa-save"></i> Simpan Penambahan Stok</button>
    </form>
</div>

<!-- Tabel Riwayat Masuk -->
<div class="glass-effect form-container">
    <div class="table-header-row">
        <h3 style="margin:0;">Riwayat Barang Masuk</h3>
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="historySearch" placeholder="Cari Tgl, Code, Nama..." onkeyup="filterHistory()">
        </div>
    </div>
    
    <div class="table-scroll-wrapper">
        <table class="data-table" id="historyTable">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Code Sparepart</th>
                    <th>Nama Barang</th>
                    <th>Supplier</th>
                    <th>Jumlah Masuk</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($history)): ?>
                    <tr><td colspan="5" align="center">Belum ada data barang masuk.</td></tr>
                <?php else: ?>
                    <?php foreach ($history as $h): ?>
                    <tr>
                        <td><?php echo date('d M Y, H:i', strtotime($h['tanggal_masuk'])); ?></td>
                        <td class="searchable"><?php echo htmlspecialchars($h['code_sparepart']); ?></td>
                        <td class="searchable"><?php echo htmlspecialchars($h['nama']); ?></td>
                        <td class="searchable"><?php echo htmlspecialchars($h['supplier_merek']); ?></td>
                        <td style="color: var(--accent-success); font-weight: bold;">+<?php echo $h['jumlah']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectPart = document.getElementById('select_sparepart');
    const inputNama = document.getElementById('info_nama');
    const inputSupplier = document.getElementById('info_supplier');
    const inputStokAwal = document.getElementById('info_stok_awal');

    // Event saat dropdown berubah (Auto-fill info)
    selectPart.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        
        if (selectedOption.value) {
            const supplier = selectedOption.getAttribute('data-supplier');
            const stok = selectedOption.getAttribute('data-stok');
            const nama = selectedOption.getAttribute('data-nama');
            
            inputNama.value = nama || '-';
            inputSupplier.value = supplier || '-';
            inputStokAwal.value = stok;
        } else {
            inputNama.value = '';
            inputSupplier.value = '';
            inputStokAwal.value = '';
        }
    });
});

// Fungsi Filter Pencarian Tabel
function filterHistory() {
    const input = document.getElementById('historySearch');
    const filter = input.value.toLowerCase();
    const table = document.getElementById('historyTable');
    const tr = table.getElementsByTagName('tr');

    for (let i = 1; i < tr.length; i++) { // Mulai dari 1 untuk skip header
        let found = false;
        // Cari di semua kolom dalam baris tersebut
        const tds = tr[i].getElementsByTagName('td');
        for (let j = 0; j < tds.length; j++) {
            if (tds[j]) {
                const txtValue = tds[j].textContent || tds[j].innerText;
                if (txtValue.toLowerCase().indexOf(filter) > -1) {
                    found = true;
                    break; // Jika ketemu di salah satu kolom, tampilkan baris
                }
            }
        }
        tr[i].style.display = found ? "" : "none";
    }
}
</script>