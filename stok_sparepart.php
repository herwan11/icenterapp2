<?php
// stok_sparepart.php

// Sertakan header halaman (wajib)
require_once 'includes/header.php';

$message = '';

// Menampilkan notifikasi setelah proses hapus
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'deletesuccess') {
        $message = "<div class='alert alert-success'>Data sparepart yang dipilih berhasil dihapus!</div>";
    } elseif ($_GET['status'] == 'deletefailed') {
        $message = "<div class='alert alert-danger'>Gagal menghapus data atau tidak ada data yang dipilih.</div>";
    }
}

// --- Proses Form Simpan Data Baru ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['simpan_sparepart'])) {
    // Ambil semua data dari form
    $code_sparepart = $_POST['code_sparepart'];
    $nama = $_POST['nama'];
    $model = $_POST['model'];
    $kategori = $_POST['kategori'];
    $satuan = $_POST['satuan'];
    $harga_beli = $_POST['harga_beli'];
    $harga_jual = $_POST['harga_jual'];
    $supplier_merek = $_POST['supplier_merek'];
    $stok_tersedia = $_POST['stok_tersedia'];
    $stok_minimum = $_POST['stok_minimum'];

    // Siapkan query INSERT
    $sql_insert = "INSERT INTO master_sparepart (code_sparepart, nama, model, kategori, satuan, harga_beli, harga_jual, supplier_merek, stok_tersedia, stok_minimum) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt_insert = $conn->prepare($sql_insert);
    if ($stmt_insert) {
        $stmt_insert->bind_param("sssssddssi", 
            $code_sparepart, $nama, $model, $kategori, $satuan, 
            $harga_beli, $harga_jual, $supplier_merek, $stok_tersedia, $stok_minimum
        );

        if ($stmt_insert->execute()) {
            $message = "<div class='alert alert-success'>Sparepart baru berhasil disimpan!</div>";
        } else {
            $message = "<div class='alert alert-danger'>Gagal menyimpan data: " . $stmt_insert->error . "</div>";
        }
        $stmt_insert->close();
    } else {
        $message = "<div class='alert alert-danger'>Gagal menyiapkan query: " . $conn->error . "</div>";
    }
}


// --- Ambil SEMUA data dari database untuk ditampilkan di tabel ---
$sparepart_data = [];
$sql_select = "SELECT * FROM master_sparepart ORDER BY nama ASC";
$result = $conn->query($sql_select);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $sparepart_data[] = $row;
    }
}
$conn->close();
?>

<!-- Style khusus untuk halaman ini -->
<style>
    .form-container {
        padding: 24px;
        margin-bottom: 24px;
    }
    .form-grid-3 {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 16px 24px;
    }
    .form-group label {
        margin-bottom: 8px;
        font-weight: 500;
        color: var(--text-secondary);
    }
    .form-group input,
    .form-group select {
        width: 100%;
        padding: 10px;
        border-radius: 8px;
        border: 1px solid var(--border-color);
        background-color: #fff;
    }
    .form-footer {
        margin-top: 16px;
        text-align: right;
    }
    .data-table-container {
        padding: 24px;
    }
    .data-table-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
    }
    .btn-delete-selected {
        background-color: var(--accent-danger);
        color: white;
        padding: 8px 16px;
        border-radius: 8px;
        font-weight: 500;
        display: none; /* Sembunyikan secara default */
    }
    .data-table {
        width: 100%;
        border-collapse: collapse;
    }
    .data-table th, .data-table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid var(--border-color);
    }
    .data-table thead th {
        background-color: #f8f9fa;
        color: var(--text-secondary);
        font-weight: 600;
        font-size: 12px;
    }
    .data-table tbody tr:hover {
        background-color: #f1f1f1;
    }
    /* Gaya untuk baris dengan stok rendah */
    .stock-low {
        background-color: rgba(255, 59, 48, 0.15) !important;
        color: #c51f14;
        font-weight: 500;
    }
    /* Notifikasi */
    .alert { padding: 15px; margin-bottom: 20px; border: 1px solid transparent; border-radius: .25rem; }
    .alert-success { color: #155724; background-color: #d4edda; border-color: #c3e6cb; }
    .alert-danger { color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; }
</style>

<!-- Konten Utama Halaman -->
<h1 class="page-title">Manajemen Stok Sparepart</h1>

<?php echo $message; // Tampilkan pesan sukses atau error di sini ?>

<!-- Form Input Data Baru -->
<div class="form-container glass-effect">
    <h2 style="margin-bottom: 16px;">Tambah Sparepart Baru</h2>
    <form action="stok_sparepart.php" method="POST">
        <div class="form-grid-3">
            <div class="form-group">
                <label for="code_sparepart">Code Sparepart</label>
                <input type="text" id="code_sparepart" name="code_sparepart" required>
            </div>
            <div class="form-group">
                <label for="nama">Nama Sparepart</label>
                <input type="text" id="nama" name="nama" required>
            </div>
            <div class="form-group">
                <label for="model">Model</label>
                <input type="text" id="model" name="model">
            </div>
            <div class="form-group">
                <label for="kategori">Kategori</label>
                <input type="text" id="kategori" name="kategori">
            </div>
            <div class="form-group">
                <label for="satuan">Satuan</label>
                <input type="text" id="satuan" name="satuan" value="pcs">
            </div>
            <div class="form-group">
                <label for="harga_beli">Harga Beli (/pcs)</label>
                <input type="number" id="harga_beli" name="harga_beli" value="0">
            </div>
            <div class="form-group">
                <label for="harga_jual">Harga Jual (/pcs)</label>
                <input type="number" id="harga_jual" name="harga_jual" value="0">
            </div>
            <div class="form-group">
                <label for="supplier_merek">Supplier/Merek</label>
                <input type="text" id="supplier_merek" name="supplier_merek">
            </div>
            <div class="form-group">
                <label for="stok_tersedia">Stok Awal</label>
                <input type="number" id="stok_tersedia" name="stok_tersedia" value="0">
            </div>
            <div class="form-group">
                <label for="stok_minimum">Stok Minimum</label>
                <input type="number" id="stok_minimum" name="stok_minimum" value="0">
            </div>
        </div>
        <div class="form-footer">
            <button type="submit" name="simpan_sparepart" class="btn btn-primary">Simpan Sparepart</button>
        </div>
    </form>
</div>

<!-- Tabel Pratinjau Data -->
<div class="data-table-container glass-effect">
    <form action="delete_sparepart.php" method="POST" id="deleteForm">
        <div class="data-table-header">
            <h2>Daftar Stok Sparepart</h2>
            <button type="submit" id="deleteBtn" class="btn-delete-selected">Hapus Terpilih</button>
        </div>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll"></th>
                        <th>Code</th>
                        <th>Nama</th>
                        <th>Kategori</th>
                        <th>Harga Beli</th>
                        <th>Harga Jual</th>
                        <th>Stok</th>
                        <th>Stok Min.</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sparepart_data)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 20px;">Belum ada data sparepart.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($sparepart_data as $data): ?>
                            <!-- PERBAIKAN LOGIKA DI SINI -->
                            <tr class="<?php echo ($data['stok_tersedia'] <= $data['stok_minimum']) ? 'stock-low' : ''; ?>">
                                <td><input type="checkbox" name="selected_spareparts[]" value="<?php echo htmlspecialchars($data['code_sparepart']); ?>" class="row-checkbox"></td>
                                <td><?php echo htmlspecialchars($data['code_sparepart']); ?></td>
                                <td><?php echo htmlspecialchars($data['nama']); ?></td>
                                <td><?php echo htmlspecialchars($data['kategori']); ?></td>
                                <td><?php echo number_format($data['harga_beli'], 0, ',', '.'); ?></td>
                                <td><?php echo number_format($data['harga_jual'], 0, ',', '.'); ?></td>
                                <td><?php echo htmlspecialchars($data['stok_tersedia']); ?></td>
                                <td><?php echo htmlspecialchars($data['stok_minimum']); ?></td>
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
    const selectAllCheckbox = document.getElementById('selectAll');
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');
    const deleteBtn = document.getElementById('deleteBtn');

    function toggleDeleteButton() {
        const anyChecked = Array.from(rowCheckboxes).some(cb => cb.checked);
        deleteBtn.style.display = anyChecked ? 'block' : 'none';
    }

    selectAllCheckbox.addEventListener('change', function() {
        rowCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
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

    document.getElementById('deleteForm').addEventListener('submit', function(e) {
        const anyChecked = Array.from(rowCheckboxes).some(cb => cb.checked);
        if (!anyChecked) {
            e.preventDefault();
            alert('Silakan pilih setidaknya satu item untuk dihapus.');
        } else if (!confirm('Apakah Anda yakin ingin menghapus item yang dipilih?')) {
            e.preventDefault();
        }
    });
});
</script>
