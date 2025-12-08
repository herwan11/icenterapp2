<?php
// suplier.php
require_once 'includes/header.php';

// Cek hak akses (Hanya Admin & Owner)
if (get_user_role() !== 'owner' && get_user_role() !== 'admin') {
    echo "<script>alert('Akses ditolak.'); window.location.href='index.php';</script>";
    exit();
}

// Ambil Data Suplier
$supliers = [];
$result = $conn->query("SELECT * FROM suplier ORDER BY nama_suplier ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $supliers[] = $row;
    }
}
?>

<style>
    .data-table-container { padding: 24px; }
    .table-header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    
    /* Search Box Style */
    .search-box { position: relative; width: 300px; }
    .search-box input {
        width: 100%; padding: 10px 12px 10px 40px;
        border: 1px solid #e5e5ea; border-radius: 20px;
        font-size: 14px; background: rgba(255,255,255,0.8);
        transition: all 0.3s;
    }
    .search-box input:focus { border-color: var(--accent-primary); box-shadow: 0 0 0 3px rgba(0,122,255,0.1); outline: none; }
    .search-box i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #8a8a8e; }

    /* Table Style */
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table th { 
        text-align: left; padding: 14px 16px; 
        background: rgba(248,249,250,0.8); 
        color: var(--text-secondary); font-weight: 600; font-size: 12px; text-transform: uppercase; 
        border-bottom: 2px solid #e5e5ea;
    }
    .data-table td { 
        padding: 14px 16px; border-bottom: 1px solid #f2f2f7; 
        color: var(--text-primary); font-size: 14px; vertical-align: middle;
    }
    .data-table tr:hover { background-color: rgba(0,122,255,0.03); }
    
    .contact-badge { 
        display: inline-flex; align-items: center; gap: 6px; 
        background: #eef2ff; color: var(--accent-primary); 
        padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 500;
    }
    
    .btn-action {
        width: 32px; height: 32px; border-radius: 8px;
        display: inline-flex; align-items: center; justify-content: center;
        border: none; cursor: pointer; transition: all 0.2s;
        color: #8a8a8e; background: transparent;
    }
    .btn-action:hover { background: #f2f2f7; color: var(--text-primary); }
    .btn-delete:hover { background: #fff1f0; color: var(--accent-danger); }

    /* Modal Form Style */
    .modal-content { width: 100%; max-width: 500px; border-radius: 16px; }
    .form-group { margin-bottom: 16px; }
    .form-group label { display: block; margin-bottom: 6px; font-weight: 500; font-size: 13px; color: var(--text-secondary); }
    .form-control { width: 100%; padding: 10px 12px; border: 1px solid #d1d1d6; border-radius: 8px; }
</style>

<h1 class="page-title">Data Suplier</h1>

<div class="glass-effect data-table-container">
    <div class="table-header-row">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Cari Nama, Kontak, atau Alamat..." onkeyup="filterTable()">
        </div>
        <button class="btn btn-primary" onclick="openModal('addSuplierModal')">
            <i class="fas fa-plus"></i> Tambah Suplier
        </button>
    </div>

    <div class="table-wrapper">
        <table class="data-table" id="suplierTable">
            <thead>
                <tr>
                    <th>Nama Suplier</th>
                    <th>Kontak</th>
                    <th>Alamat</th>
                    <th>Keterangan</th>
                    <th style="width: 100px; text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($supliers)): ?>
                    <tr><td colspan="5" style="text-align:center; padding: 30px; color: #8a8a8e;">Belum ada data suplier.</td></tr>
                <?php else: ?>
                    <?php foreach ($supliers as $s): ?>
                        <tr>
                            <td style="font-weight: 600;"><?php echo htmlspecialchars($s['nama_suplier']); ?></td>
                            <td>
                                <?php if($s['kontak']): ?>
                                    <span class="contact-badge"><i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($s['kontak']); ?></span>
                                <?php else: ?>
                                    <span style="color:#ccc;">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($s['alamat'] ?? '-'); ?></td>
                            <td style="color: #8a8a8e; font-style: italic;"><?php echo htmlspecialchars($s['keterangan'] ?? '-'); ?></td>
                            <td style="text-align: right;">
                                <button class="btn-action" title="Edit (Coming Soon)"><i class="fas fa-edit"></i></button>
                                <button class="btn-action btn-delete" onclick="deleteSuplier(<?php echo $s['id']; ?>, '<?php echo htmlspecialchars($s['nama_suplier']); ?>')" title="Hapus">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah Suplier -->
<div id="addSuplierModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Tambah Suplier Baru</h2>
            <button type="button" class="close-btn" onclick="closeModal('addSuplierModal')">&times;</button>
        </div>
        <form id="addSuplierForm">
            <div class="modal-body">
                <div class="form-group">
                    <label>Nama Suplier <span style="color:red">*</span></label>
                    <input type="text" name="nama" class="form-control" required placeholder="Contoh: CV. Maju Jaya">
                </div>
                <div class="form-group">
                    <label>Kontak / No. HP</label>
                    <input type="text" name="kontak" class="form-control" placeholder="08xxxxxxxxxx">
                </div>
                <div class="form-group">
                    <label>Alamat</label>
                    <textarea name="alamat" class="form-control" rows="2" placeholder="Alamat lengkap..."></textarea>
                </div>
                <div class="form-group">
                    <label>Keterangan</label>
                    <textarea name="keterangan" class="form-control" rows="2" placeholder="Catatan tambahan..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addSuplierModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<script>
// Fungsi Modal Standar
function openModal(id) { document.getElementById(id).style.display = 'flex'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }

// Tutup modal jika klik di luar
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = "none";
    }
}

// Filter Pencarian Tabel
function filterTable() {
    const input = document.getElementById("searchInput");
    const filter = input.value.toLowerCase();
    const table = document.getElementById("suplierTable");
    const tr = table.getElementsByTagName("tr");

    for (let i = 1; i < tr.length; i++) {
        let found = false;
        const tds = tr[i].getElementsByTagName("td");
        for (let j = 0; j < tds.length - 1; j++) { // Skip kolom aksi
            if (tds[j] && tds[j].innerText.toLowerCase().indexOf(filter) > -1) {
                found = true;
                break;
            }
        }
        tr[i].style.display = found ? "" : "none";
    }
}

// Hapus Suplier
function deleteSuplier(id, nama) {
    if(confirm(`Yakin ingin menghapus suplier "${nama}"?`)) {
        fetch('delete_suplier.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                location.reload();
            } else {
                alert('Gagal: ' + data.message);
            }
        });
    }
}

// Submit Form Tambah
document.getElementById('addSuplierForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());

    fetch('add_suplier.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            location.reload();
        } else {
            alert('Gagal: ' + data.message);
        }
    });
});
</script>