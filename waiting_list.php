<?php
// waiting_list.php

require_once 'includes/header.php';

// Ambil status dari URL, default ke 'Antrian' jika tidak ada
$status = isset($_GET['status']) ? htmlspecialchars($_GET['status']) : 'Antrian';

// Query untuk mengambil data service berdasarkan status
$sql = "SELECT s.*, c.nama as nama_customer, k.nama as nama_teknisi 
        FROM service s
        LEFT JOIN customers c ON s.customer_id = c.id
        LEFT JOIN karyawan k ON s.teknisi_id = k.id
        WHERE s.status_service = ? 
        ORDER BY s.tanggal DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $status);
$stmt->execute();
$result = $stmt->get_result();
$services = $result->fetch_all(MYSQLI_ASSOC);

// Ambil semua data sparepart untuk modal
$spareparts_list = [];
$result_spareparts = $conn->query("SELECT code_sparepart, nama, harga_jual, stok_tersedia FROM master_sparepart ORDER BY nama ASC");
while($row = $result_spareparts->fetch_assoc()) {
    $spareparts_list[] = $row;
}

?>

<!-- Style khusus untuk halaman ini -->
<style>
    /* Gaya tabel tradisional */
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table th, .data-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #e9ecef; vertical-align: middle; }
    .data-table thead th { background-color: #f8f9fa; font-weight: 600; color: #495057; }
    .data-table tbody tr:hover { background-color: #f1f3f5; }

    /* Gaya status */
    .status-badge { padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: 500; color: white; display: inline-block; }
    .status-antrian { background-color: #ffc107; color: #333; }
    .status-proses { background-color: #17a2b8; }
    .status-selesai, .status-lunas { background-color: #28a745; }
    .status-diambil { background-color: #007bff; }
    .status-batal, .status-refund { background-color: #dc3545; }
    .status-belum-lunas { background-color: #fd7e14; }

    /* Gaya editor status */
    .status-container { display: flex; align-items: center; gap: 8px; min-width: 150px; }
    .status-display { display: flex; align-items: center; gap: 8px; justify-content: space-between; width: 100%; }
    .status-edit-icon { cursor: pointer; color: #6c757d; transition: color 0.2s; }
    .status-edit-icon:hover { color: #007bff; }
    .status-editor { display: none; align-items: center; gap: 8px; width: 100%; }
    .status-editor select { padding: 4px; border-radius: 4px; border: 1px solid #ccc; flex-grow: 1; }
    .status-editor .btn-save, .status-editor .btn-cancel { padding: 4px 8px; font-size: 0.8rem; border: none; color: white; border-radius: 4px; cursor: pointer;}
    .btn-save { background-color: #28a745; }
    .btn-cancel { background-color: #6c757d; }
    .btn-info { background-color: #17a2b8; color: white; border-radius: 6px; padding: 5px 10px; font-size: 12px;}

    /* === GAYA BARU UNTUK MODAL SPAREPART YANG LEBIH KEREN === */
    .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6); align-items: center; justify-content: center; backdrop-filter: blur(5px); }
    .modal-content { background-color: #ffffff; margin: auto; padding: 0; border: none; width: 90%; max-width: 800px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); overflow: hidden; }
    .modal-header { display: flex; justify-content: space-between; align-items: center; background-color: #f8f9fa; border-bottom: 1px solid #dee2e6; padding: 16px 24px; }
    .modal-header h2 { font-size: 18px; margin: 0; color: #333; }
    .modal-body { padding: 24px; max-height: 60vh; overflow-y: auto;}
    .modal-footer { background-color: #f8f9fa; border-top: 1px solid #dee2e6; padding: 16px 24px; text-align: right; }
    .close-btn { color: #6c757d; font-size: 28px; font-weight: bold; cursor: pointer; background: none; border: none; }
    .modal-section h4 { font-size: 16px; margin-bottom: 12px; border-bottom: 1px solid #eee; padding-bottom: 8px; color: #333; }
    #usedPartsTable { font-size: 13px; }
    #usedPartsTable td, #usedPartsTable th { padding: 8px; }
    .remove-part-btn { background-color: #dc3545; color: white; border-radius: 5px; font-size: 11px; padding: 3px 8px; border: none; cursor: pointer; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; font-weight: 500; color: #555;}
    .form-control { width: 100%; padding: 8px 12px; border: 1px solid #ccc; border-radius: 6px; }
    .modal-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
    @media (max-width: 768px) { .modal-grid { grid-template-columns: 1fr; } }
    .modal-tabs { display: flex; border-bottom: 1px solid #dee2e6; margin-bottom: 20px; }
    .tab-link { padding: 10px 20px; cursor: pointer; border: none; background: none; font-weight: 500; color: #6c757d; }
    .tab-link.active { color: var(--accent-primary); border-bottom: 2px solid var(--accent-primary); }
    .tab-content { display: none; }
    .tab-content.active { display: block; }
</style>

<!-- KONTEN UTAMA HALAMAN -->
<h1 class="page-title">Daftar Service: <?php echo $status; ?></h1>

<div class="card glass-effect">
    <div class="card-body">
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Customer</th>
                        <th>Perangkat</th>
                        <th>Kerusakan</th>
                        <th>Kelengkapan</th>
                        <th>Status</th>
                        <th>Pembayaran</th>
                        <?php if ($status == 'Proses'): ?>
                            <th>Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($services)): ?>
                        <tr><td colspan="<?php echo ($status == 'Proses') ? '8' : '7'; ?>" style="text-align: center;">Tidak ada data untuk status "<?php echo $status; ?>"</td></tr>
                    <?php else: ?>
                        <?php foreach ($services as $row): ?>
                            <tr data-invoice="<?php echo htmlspecialchars($row['invoice']); ?>">
                                <td><?php echo htmlspecialchars($row['invoice']); ?></td>
                                <td><?php echo htmlspecialchars($row['nama_customer'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($row['merek_hp'] . ' ' . $row['tipe_hp']); ?></td>
                                <td><?php echo htmlspecialchars($row['kerusakan']); ?></td>
                                <td><?php echo htmlspecialchars($row['kelengkapan']); ?></td>
                                <td>
                                    <div class="status-container" data-type="service">
                                        <div class="status-display">
                                            <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $row['status_service'])); ?>"><?php echo htmlspecialchars($row['status_service']); ?></span>
                                            <i class="fas fa-pencil-alt status-edit-icon"></i>
                                        </div>
                                        <div class="status-editor">
                                            <select class="status-select">
                                                <option value="Antrian" <?php echo ($row['status_service'] == 'Antrian') ? 'selected' : ''; ?>>Antrian</option>
                                                <option value="Proses" <?php echo ($row['status_service'] == 'Proses') ? 'selected' : ''; ?>>Proses</option>
                                                <option value="Selesai" <?php echo ($row['status_service'] == 'Selesai') ? 'selected' : ''; ?>>Selesai</option>
                                                <option value="Diambil" <?php echo ($row['status_service'] == 'Diambil') ? 'selected' : ''; ?>>Diambil</option>
                                                <option value="Batal" <?php echo ($row['status_service'] == 'Batal') ? 'selected' : ''; ?>>Batal</option>
                                                <option value="Refund" <?php echo ($row['status_service'] == 'Refund') ? 'selected' : ''; ?>>Refund</option>
                                            </select>
                                            <button class="btn-save"><i class="fas fa-check"></i></button>
                                            <button class="btn-cancel"><i class="fas fa-times"></i></button>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                     <div class="status-container" data-type="payment">
                                        <div class="status-display">
                                            <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $row['status_pembayaran'])); ?>"><?php echo htmlspecialchars($row['status_pembayaran']); ?></span>
                                            <i class="fas fa-pencil-alt status-edit-icon"></i>
                                        </div>
                                        <div class="status-editor">
                                            <select class="status-select">
                                                <option value="Lunas" <?php echo ($row['status_pembayaran'] == 'Lunas') ? 'selected' : ''; ?>>Lunas</option>
                                                <option value="Belum Lunas" <?php echo ($row['status_pembayaran'] == 'Belum Lunas') ? 'selected' : ''; ?>>Belum Lunas</option>
                                            </select>
                                            <button class="btn-save"><i class="fas fa-check"></i></button>
                                            <button class="btn-cancel"><i class="fas fa-times"></i></button>
                                        </div>
                                    </div>
                                </td>
                                <?php if ($status == 'Proses'): ?>
                                <td>
                                    <button class="btn btn-info manage-sparepart-btn" data-invoice="<?php echo htmlspecialchars($row['invoice']); ?>">
                                        <i class="fas fa-cog"></i> Sparepart
                                    </button>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal/Popup untuk Manajemen Sparepart -->
<div id="sparepartModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle"></h2>
            <button type="button" class="close-btn">&times;</button>
        </div>
        <div class="modal-body">
            <div class="modal-grid">
                <div class="modal-section" id="used-parts-section">
                    <h4>Sparepart Terpakai</h4>
                    <div class="table-wrapper">
                        <table class="data-table" id="usedPartsTable">
                            <thead><tr><th>Nama</th><th>Jumlah</th><th>Tipe</th><th>Aksi</th></tr></thead>
                            <tbody><!-- Diisi oleh JavaScript --></tbody>
                        </table>
                    </div>
                    <div id="noPartsMessage" style="text-align:center; padding: 20px; color: #888; display:none;">Belum ada sparepart yang digunakan.</div>
                </div>
                <div class="modal-section" id="add-part-section">
                     <h4>Tambah Penggunaan</h4>
                     <div class="modal-tabs">
                         <button class="tab-link active" data-tab="internal">Gunakan Stok Internal</button>
                         <button class="tab-link" data-tab="external">Beli dari Luar</button>
                     </div>

                     <!-- Konten Tab Stok Internal -->
                     <div id="internal" class="tab-content active">
                         <div class="form-group">
                            <label>Pilih Sparepart</label>
                            <select id="sparepart-selector" class="form-control">
                                <option value="">--- Pilih ---</option>
                                <?php foreach($spareparts_list as $part): ?>
                                <option value="<?php echo $part['code_sparepart']; ?>" data-harga="<?php echo $part['harga_jual']; ?>" data-stok="<?php echo $part['stok_tersedia']; ?>">
                                    <?php echo htmlspecialchars($part['nama']); ?> (Stok: <?php echo $part['stok_tersedia']; ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Jumlah</label>
                            <input type="number" id="sparepart-qty" value="1" min="1" class="form-control">
                        </div>
                        <button type="button" id="add-sparepart-btn" class="btn btn-primary" style="width: 100%;">Tambah</button>
                     </div>
                     
                     <!-- Konten Tab Beli dari Luar -->
                     <div id="external" class="tab-content">
                         <form id="buyExternalForm">
                            <div class="form-group">
                                <label>Nama Sparepart</label>
                                <input type="text" id="external_nama" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Harga Beli Satuan</label>
                                <input type="number" id="external_harga" class="form-control" required>
                            </div>
                             <div class="form-group">
                                <label>Jumlah</label>
                                <input type="number" id="external_qty" value="1" min="1" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary" style="width: 100%;">Beli & Gunakan</button>
                         </form>
                     </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary close-btn">Tutup</button>
        </div>
    </div>
</div>


<?php require_once 'includes/footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tableBody = document.querySelector('.data-table tbody');
    const modal = document.getElementById('sparepartModal');
    const closeModalBtns = modal.querySelectorAll('.close-btn');
    let currentInvoice = null;

    // --- Logika Gabungan untuk Semua Klik di Dalam Tabel ---
    tableBody.addEventListener('click', function(event) {
        const target = event.target;
        
        // --- Logika untuk Edit Status Interaktif ---
        const statusContainer = target.closest('.status-container');
        if (statusContainer) {
            const row = target.closest('tr');
            
            if (target.closest('.status-edit-icon')) {
                statusContainer.querySelector('.status-display').style.display = 'none';
                statusContainer.querySelector('.status-editor').style.display = 'flex';
            }

            if (target.closest('.btn-cancel')) {
                statusContainer.querySelector('.status-editor').style.display = 'none';
                statusContainer.querySelector('.status-display').style.display = 'flex';
            }

            if (target.closest('.btn-save')) {
                const invoice = row.dataset.invoice;
                const type = statusContainer.dataset.type;
                const select = statusContainer.querySelector('.status-select');
                const newStatus = select.value;
                const oldStatusSpan = statusContainer.querySelector('.status-display .status-badge');
                const oldStatus = oldStatusSpan.textContent;
                
                const endpoint = (type === 'service') ? 'update_status.php' : 'update_payment_status.php';
                const payload = { invoice: invoice, status: newStatus };

                fetch(endpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (type === 'service') {
                            updateMenuBadge(oldStatus, newStatus);
                            row.style.transition = 'opacity 0.5s ease';
                            row.style.opacity = '0';
                            setTimeout(() => row.remove(), 500);
                        } else {
                            oldStatusSpan.textContent = newStatus;
                            oldStatusSpan.className = `status-badge status-${newStatus.toLowerCase().replace(/ /g, '-')}`;
                            statusContainer.querySelector('.status-editor').style.display = 'none';
                            statusContainer.querySelector('.status-display').style.display = 'flex';
                        }
                    } else {
                        alert('Gagal memperbarui status: ' + (data.message || 'Error tidak diketahui.'));
                    }
                })
                .catch(error => {
                    console.error('Fetch Error:', error);
                    alert('Terjadi kesalahan jaringan. Silakan coba lagi.');
                });
            }
        }
        
        // --- Logika untuk Modal Sparepart ---
        const manageBtn = target.closest('.manage-sparepart-btn');
        if (manageBtn) {
            currentInvoice = manageBtn.dataset.invoice;
            document.getElementById('modalTitle').textContent = `Penggunaan Sparepart (Invoice: ${currentInvoice})`;
            loadUsedParts(currentInvoice);
            modal.style.display = 'flex';
        }
    });

    closeModalBtns.forEach(btn => btn.addEventListener('click', () => { modal.style.display = 'none'; }));

    async function loadUsedParts(invoice) {
        const response = await fetch(`get_used_spareparts.php?invoice=${invoice}`);
        const result = await response.json();
        const usedPartsTbody = document.getElementById('usedPartsTable').querySelector('tbody');
        const noPartsMsg = document.getElementById('noPartsMessage');
        usedPartsTbody.innerHTML = '';

        if (result.success && result.data.length > 0) {
            usedPartsTbody.parentElement.style.display = 'table';
            noPartsMsg.style.display = 'none';
            result.data.forEach(part => {
                const row = `
                    <tr data-id="${part.id}" data-type="${part.tipe}">
                        <td>${part.nama}</td>
                        <td>${part.jumlah}</td>
                        <td><span class="badge" style="background-color: ${part.tipe === 'internal' ? '#007bff' : '#6c757d'}">${part.tipe}</span></td>
                        <td><button class="remove-part-btn" data-id="${part.id}" data-type="${part.tipe}">Hapus</button></td>
                    </tr>`;
                usedPartsTbody.innerHTML += row;
            });
        } else {
            usedPartsTbody.parentElement.style.display = 'none';
            noPartsMsg.style.display = 'block';
        }
    }
    
    // --- Logika untuk Tab di Modal ---
    const tabs = document.querySelectorAll('.tab-link');
    const tabContents = document.querySelectorAll('.tab-content');
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(item => item.classList.remove('active'));
            tab.classList.add('active');
            const target = document.getElementById(tab.dataset.tab);
            tabContents.forEach(content => content.classList.remove('active'));
            target.classList.add('active');
        });
    });

    // --- Logika Penambahan Sparepart ---
    document.getElementById('add-sparepart-btn').addEventListener('click', async function() {
        const selector = document.getElementById('sparepart-selector');
        const qtyInput = document.getElementById('sparepart-qty');
        const selectedOption = selector.options[selector.selectedIndex];

        if (!selectedOption.value) { alert('Pilih sparepart terlebih dahulu.'); return; }
        
        const payload = {
            invoice: currentInvoice,
            spareparts: [{
                code: selectedOption.value,
                qty: parseInt(qtyInput.value),
                harga: parseFloat(selectedOption.dataset.harga)
            }]
        };

        const response = await fetch('add_sparepart_to_service.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const result = await response.json();

        if (result.success) {
            loadUsedParts(currentInvoice);
            selector.selectedIndex = 0;
            qtyInput.value = 1;
        } else {
            alert('Gagal menambah sparepart: ' + result.message);
        }
    });

    document.getElementById('buyExternalForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const payload = {
            invoice: currentInvoice,
            nama: document.getElementById('external_nama').value,
            harga: document.getElementById('external_harga').value,
            jumlah: document.getElementById('external_qty').value,
        };
        
        const response = await fetch('buy_external_sparepart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const result = await response.json();

        if(result.success) {
            loadUsedParts(currentInvoice);
            this.reset();
        } else {
            alert('Gagal: ' + result.message);
        }
    });

    document.getElementById('usedPartsTable').addEventListener('click', async function(e) {
        if(e.target.classList.contains('remove-part-btn')) {
            const id = e.target.dataset.id;
            const type = e.target.dataset.type;
            if(!confirm('Anda yakin ingin menghapus sparepart ini? Stok/Kas akan dikembalikan.')) return;

            const endpoint = type === 'internal' ? 'remove_used_sparepart.php' : 'remove_external_sparepart.php';
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });
            const result = await response.json();

            if (result.success) {
                loadUsedParts(currentInvoice);
            } else {
                alert('Gagal menghapus: ' + result.message);
            }
        }
    });

    function updateMenuBadge(oldStatus, newStatus) {
        const oldBadge = document.querySelector(`.badge[data-status="${oldStatus}"]`);
        const newBadge = document.querySelector(`.badge[data-status="${newStatus}"]`);

        if (oldBadge) {
            let count = parseInt(oldBadge.textContent) - 1;
            oldBadge.textContent = count;
            if (count <= 0) {
                oldBadge.style.display = 'none';
            }
        }
        if (newBadge) {
            let count = isNaN(parseInt(newBadge.textContent)) ? 0 : parseInt(newBadge.textContent);
            count++;
            newBadge.textContent = count;
            newBadge.style.display = 'inline-flex';
        }
    }
});
</script>

