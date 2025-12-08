<?php
// customers.php

require_once 'includes/header.php';

// --- Query Data Pelanggan & Statistik ---
// Menggunakan GROUP_CONCAT untuk menggabungkan history merek hp dan kerusakan agar bisa dicari
$sql = "SELECT p.*, 
        (SELECT COUNT(*) FROM service s WHERE s.customer_id = p.id) as total_service,
        (SELECT COUNT(*) FROM penjualan_sparepart ps WHERE ps.pelanggan_id = p.id) as total_beli_part,
        (SELECT COUNT(*) FROM penjualan_perangkat pp WHERE pp.pelanggan_id = p.id) as total_beli_perangkat,
        -- Mengambil keywords untuk pencarian (Merek HP dan Kerusakan dari history service)
        (
            SELECT GROUP_CONCAT(CONCAT(COALESCE(merek_hp,''), ' ', COALESCE(tipe_hp,''), ' ', COALESCE(kerusakan,'')) SEPARATOR ' ') 
            FROM service s 
            WHERE s.customer_id = p.id
        ) as search_keywords
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
    .data-table tbody tr:hover { background-color: #f1f1f1; }
    
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

    /* Modal Styles */
    .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6); align-items: center; justify-content: center; backdrop-filter: blur(5px); }
    .modal-content { background-color: #ffffff; margin: auto; padding: 0; border: none; width: 90%; max-width: 600px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); overflow: hidden; animation: slideIn 0.3s ease; }
    @keyframes slideIn { from {transform: translateY(-20px); opacity: 0;} to {transform: translateY(0); opacity: 1;} }
    .modal-header { display: flex; justify-content: space-between; align-items: center; background-color: #f8f9fa; border-bottom: 1px solid #dee2e6; padding: 16px 24px; }
    .modal-header h2 { font-size: 18px; margin: 0; color: #333; }
    .modal-body { padding: 24px; max-height: 70vh; overflow-y: auto; }
    .modal-footer { background-color: #f8f9fa; border-top: 1px solid #dee2e6; padding: 16px 24px; text-align: right; }
    .close-btn { color: #6c757d; font-size: 28px; font-weight: bold; cursor: pointer; background: none; border: none; }
    
    .form-group { margin-bottom: 16px; }
    .form-group label { display: block; margin-bottom: 6px; font-weight: 600; color: #48484a; font-size: 13px; }
    .form-control { width: 100%; padding: 10px 14px; border: 1px solid #d1d1d6; border-radius: 8px; font-size: 14px; }
    
    .search-box { position: relative; width: 100%; max-width: 400px; }
    .search-box input { width: 100%; padding: 10px 12px 10px 40px; border: 1px solid #e5e5ea; border-radius: 20px; font-size: 14px; background: rgba(255,255,255,0.8); transition: all 0.3s; }
    .search-box input:focus { border-color: var(--accent-primary); box-shadow: 0 0 0 3px rgba(0,122,255,0.1); outline: none; }
    .search-box i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #8a8a8e; }

    /* Hidden column for search indexing */
    .search-data { display: none; }
</style>

<!-- KONTEN UTAMA -->
<h1 class="page-title">Data Pelanggan</h1>

<div class="glass-effect data-table-container">
    <div class="table-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap: wrap; gap: 15px;">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Cari Nama, HP, Merek, Kerusakan..." onkeyup="filterTable()">
        </div>
        <button onclick="openModal('customerFormModal', 'add')" class="btn btn-primary"><i class="fas fa-plus"></i> Pelanggan Baru</button>
    </div>

    <div class="table-wrapper">
        <table class="data-table" id="customerTable">
            <thead>
                <tr>
                    <th>Nama Pelanggan</th>
                    <th>Kontak / WhatsApp</th>
                    <th>Alamat</th>
                    <th>Riwayat Service</th>
                    <th>Riwayat Belanja</th>
                    <th style="text-align: right;">Aksi</th>
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
                                <!-- Data tersembunyi untuk pencarian -->
                                <span class="search-data">
                                    <?php echo strtolower($c['nama'] . ' ' . $c['no_hp'] . ' ' . $c['keluhan'] . ' ' . $c['search_keywords']); ?>
                                </span>
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

                            <td style="text-align: right;">
                                <button class="btn btn-tertiary btn-sm" onclick='viewDetail(<?php echo $c['id']; ?>)' title="Lihat Detail"><i class="fas fa-eye"></i></button>
                                <button class="btn btn-tertiary btn-sm" onclick='editCustomer(<?php echo json_encode($c); ?>)' title="Edit"><i class="fas fa-edit" style="color: var(--accent-warning);"></i></button>
                                <button class="btn btn-tertiary btn-sm" onclick="deleteCustomer(<?php echo $c['id']; ?>, '<?php echo htmlspecialchars($c['nama'], ENT_QUOTES); ?>')" title="Hapus"><i class="fas fa-trash-alt" style="color: var(--accent-danger);"></i></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL FORM PELANGGAN (TAMBAH & EDIT) -->
<div id="customerFormModal" class="modal">
    <div class="modal-content">
        <form id="customerForm">
            <div class="modal-header">
                <h2 id="modalTitle">Tambah Pelanggan Baru</h2>
                <button type="button" class="close-btn" onclick="closeModal('customerFormModal')">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="cust_id" name="id">
                
                <div class="form-group">
                    <label>Nama Lengkap <span style="color:red">*</span></label>
                    <input type="text" id="cust_nama" name="nama" class="form-control" required placeholder="Nama sesuai KTP">
                </div>
                <div class="form-group">
                    <label>Kontak / WhatsApp <span style="color:red">*</span></label>
                    <input type="text" id="cust_kontak" name="kontak" class="form-control" required placeholder="08xxxxxxxxxx">
                </div>
                <div class="form-group">
                    <label>Alamat</label>
                    <textarea id="cust_alamat" name="alamat" class="form-control" rows="2" placeholder="Alamat domisili"></textarea>
                </div>
                <div class="form-group">
                    <label>Keluhan / Catatan Awal</label>
                    <textarea id="cust_keluhan" name="keluhan" class="form-control" rows="2" placeholder="Catatan awal pelanggan..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('customerFormModal')">Batal</button>
                <button type="submit" class="btn btn-primary" id="btnSave">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL DETAIL PELANGGAN -->
<div id="customerDetailModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Detail Pelanggan</h2>
            <button type="button" class="close-btn" onclick="closeModal('customerDetailModal')">&times;</button>
        </div>
        <div class="modal-body" id="detailContent">
            <!-- Konten akan diisi via AJAX -->
            <div style="text-align:center; padding:20px;">Memuat data...</div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('customerDetailModal')">Tutup</button>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<script>
// Fungsi Modal Umum
function openModal(id) { document.getElementById(id).style.display = 'flex'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }

// Tutup modal jika klik di luar
window.onclick = function(e) {
    if (e.target.classList.contains('modal')) e.target.style.display = 'none';
}

// 1. FILTER TABLE (Pencarian Nama, Merek HP, Kerusakan)
function filterTable() {
    const input = document.getElementById("searchInput");
    const filter = input.value.toLowerCase();
    const table = document.getElementById("customerTable");
    const tr = table.getElementsByTagName("tr");

    for (let i = 1; i < tr.length; i++) { // Mulai dari 1 untuk skip header
        let found = false;
        // Kita cari di text content baris tersebut (termasuk span hidden search-data)
        const rowData = tr[i].textContent || tr[i].innerText;
        
        if (rowData.toLowerCase().indexOf(filter) > -1) {
            tr[i].style.display = "";
        } else {
            tr[i].style.display = "none";
        }
    }
}

// 2. VIEW DETAIL
function viewDetail(id) {
    openModal('customerDetailModal');
    const content = document.getElementById('detailContent');
    content.innerHTML = '<div style="text-align:center; padding:20px;"><i class="fas fa-spinner fa-spin"></i> Memuat data...</div>';

    fetch(`get_customer_detail.php?id=${id}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Gunakan template yang sama seperti di file sebelumnya
                renderDetail(data, content);
            } else {
                content.innerHTML = `<div style="color:red; text-align:center;">${data.message}</div>`;
            }
        })
        .catch(err => {
            content.innerHTML = '<div style="color:red; text-align:center;">Terjadi kesalahan koneksi.</div>';
        });
}

// Helper render detail (diambil dari logika sebelumnya)
function renderDetail(data, container) {
    const c = data.customer;
    let serviceHtml = data.services.length > 0 ? buildTable(data.services, 'service') : '<p style="color:#888; font-style:italic;">Belum ada riwayat service.</p>';
    let purchaseHtml = data.purchases.length > 0 ? buildTable(data.purchases, 'purchase') : '<p style="color:#888; font-style:italic;">Belum ada riwayat pembelian.</p>';

    container.innerHTML = `
        <div class="detail-section">
            <h4 style="border-bottom:1px solid #eee; padding-bottom:5px; margin-bottom:10px; color:#555;">Profil</h4>
            <table style="width:100%; margin-bottom:15px;">
                <tr><td style="width:100px; color:#888;">Nama</td><td>: <strong>${c.nama}</strong></td></tr>
                <tr><td style="color:#888;">Kontak</td><td>: ${c.no_hp}</td></tr>
                <tr><td style="color:#888;">Alamat</td><td>: ${c.alamat || '-'}</td></tr>
            </table>
        </div>
        <div class="detail-section">
            <h4 style="border-bottom:1px solid #eee; padding-bottom:5px; margin-bottom:10px; color:#555;">Riwayat Service</h4>
            ${serviceHtml}
        </div>
        <div class="detail-section" style="margin-top:15px;">
            <h4 style="border-bottom:1px solid #eee; padding-bottom:5px; margin-bottom:10px; color:#555;">Riwayat Pembelian</h4>
            ${purchaseHtml}
        </div>
    `;
}

function buildTable(data, type) {
    // Fungsi helper sederhana untuk tabel detail
    let rows = data.map(item => {
        if(type === 'service') return `<tr><td>${item.tanggal.split(' ')[0]}</td><td>${item.merek_hp} ${item.tipe_hp}</td><td>${item.kerusakan}</td></tr>`;
        return `<tr><td>${item.tanggal}</td><td>Rp ${parseInt(item.total).toLocaleString('id-ID')}</td><td>${item.status_pembayaran}</td></tr>`;
    }).join('');
    
    let header = type === 'service' ? '<th>Tgl</th><th>Unit</th><th>Kerusakan</th>' : '<th>Tgl</th><th>Total</th><th>Status</th>';
    return `<table class="mini-table" style="width:100%; font-size:13px;"><thead><tr>${header}</tr></thead><tbody>${rows}</tbody></table>`;
}


// 3. TAMBAH & EDIT LOGIC
function editCustomer(customer) {
    // Reset Form
    document.getElementById('customerForm').reset();
    
    // Isi Data
    document.getElementById('modalTitle').innerText = 'Edit Data Pelanggan';
    document.getElementById('cust_id').value = customer.id;
    document.getElementById('cust_nama').value = customer.nama;
    document.getElementById('cust_kontak').value = customer.no_hp;
    document.getElementById('cust_alamat').value = customer.alamat;
    document.getElementById('cust_keluhan').value = customer.keluhan; // Keluhan di DB = Catatan di UI
    
    openModal('customerFormModal');
}

// Event Listener tombol Tambah (Reset Form)
document.querySelector('button[onclick*="add"]').addEventListener('click', function() {
    document.getElementById('customerForm').reset();
    document.getElementById('cust_id').value = ''; // Kosongkan ID untuk mode tambah
    document.getElementById('modalTitle').innerText = 'Tambah Pelanggan Baru';
    openModal('customerFormModal');
});

// SUBMIT FORM (Save/Update)
document.getElementById('customerForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSave');
    const originalText = btn.innerText;
    btn.innerText = 'Menyimpan...';
    btn.disabled = true;

    const formData = {
        id: document.getElementById('cust_id').value,
        nama: document.getElementById('cust_nama').value,
        kontak: document.getElementById('cust_kontak').value,
        alamat: document.getElementById('cust_alamat').value,
        keluhan: document.getElementById('cust_keluhan').value
    };

    fetch('save_customer.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Berhasil menyimpan data!');
            location.reload();
        } else {
            alert('Gagal: ' + data.message);
        }
    })
    .catch(err => {
        console.error(err);
        alert('Terjadi kesalahan jaringan.');
    })
    .finally(() => {
        btn.innerText = originalText;
        btn.disabled = false;
    });
});

// 4. DELETE CUSTOMER
function deleteCustomer(id, nama) {
    if (confirm(`Yakin ingin menghapus pelanggan "${nama}"?\n\nPERHATIAN: Jika pelanggan memiliki riwayat service, data mungkin tidak bisa dihapus sepenuhnya (tergantung kebijakan database).`)) {
        fetch('delete_customer.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('Pelanggan berhasil dihapus.');
                location.reload();
            } else {
                alert('Gagal menghapus: ' + data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert('Terjadi kesalahan jaringan.');
        });
    }
}
</script>