<?php
// waiting_list.php

require_once 'includes/header.php';

// Ambil status dari URL, default ke 'Antrian' jika tidak ada
$status = isset($_GET['status']) ? htmlspecialchars($_GET['status']) : 'Antrian';

// --- QUERY UTAMA DIPERBARUI ---
// Menggunakan GROUP_CONCAT untuk mengambil nama teknisi dari tabel service_teams
// Menghitung total sparepart dari internal dan eksternal
$sql = "SELECT s.*, c.nama as nama_customer,
        -- Menggabungkan nama teknisi menjadi string dipisah koma
        GROUP_CONCAT(u.nama SEPARATOR ', ') as team_teknisi,
        -- Mengambil daftar ID teknisi untuk pengecekan tombol Join
        GROUP_CONCAT(u.id) as team_ids,
        (
            COALESCE((SELECT SUM(sk.jumlah * ms.harga_jual) 
             FROM sparepart_keluar sk 
             JOIN master_sparepart ms ON sk.code_sparepart = ms.code_sparepart 
             WHERE sk.invoice_service = s.invoice), 0) 
            +
            COALESCE((SELECT SUM(psl.total_jual) 
             FROM pembelian_sparepart_luar psl 
             WHERE psl.invoice_service = s.invoice), 0)
        ) as total_sparepart_calculated
        FROM service s
        LEFT JOIN customers c ON s.customer_id = c.id
        -- Join ke tabel service_teams lalu ke users untuk ambil nama teknisi
        LEFT JOIN service_teams st ON s.invoice = st.invoice
        LEFT JOIN users u ON st.user_id = u.id
        WHERE s.status_service = ? 
        GROUP BY s.invoice
        ORDER BY s.tanggal DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $status);
$stmt->execute();
$result = $stmt->get_result();
$services = $result->fetch_all(MYSQLI_ASSOC);

// Ambil data sparepart untuk modal
$spareparts_list = [];
$result_spareparts = $conn->query("SELECT code_sparepart, nama, harga_jual, stok_tersedia FROM master_sparepart ORDER BY nama ASC");
while($row = $result_spareparts->fetch_assoc()) {
    $spareparts_list[] = $row;
}

// Cek Role & User ID untuk logika tombol
$user_role = strtolower($_SESSION['role'] ?? '');
$current_user_id = $_SESSION['user_id'] ?? 0;
// Hanya teknisi, owner, dan admin yang boleh ambil job
$can_take_job = in_array($user_role, ['teknisi', 'owner', 'admin']);
?>

<!-- Style khusus -->
<style>
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table th, .data-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #e9ecef; vertical-align: middle; }
    .data-table thead th { background-color: #f8f9fa; font-weight: 600; color: #495057; }
    .data-table tbody tr:hover { background-color: #f1f3f5; }

    .status-badge { padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: 500; color: white; display: inline-block; }
    .status-antrian { background-color: #ffc107; color: #333; }
    .status-proses { background-color: #17a2b8; }
    .status-selesai, .status-lunas { background-color: #28a745; }
    .status-diambil { background-color: #007bff; }
    .status-batal, .status-refund { background-color: #dc3545; }
    .status-belum-lunas { background-color: #fd7e14; }

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
    
    /* Tombol Aksi */
    .btn-take-job {
        background-color: var(--accent-primary); color: white; border: none; padding: 6px 12px;
        border-radius: 20px; font-size: 12px; font-weight: 600; cursor: pointer;
        display: inline-flex; align-items: center; gap: 5px; transition: transform 0.2s, box-shadow 0.2s;
    }
    .btn-take-job:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,122,255,0.3); }
    
    .btn-join-job {
        background-color: #6f42c1; color: white; border: none; padding: 4px 10px;
        border-radius: 15px; font-size: 11px; font-weight: 600; cursor: pointer;
        display: inline-flex; align-items: center; gap: 4px; margin-top: 5px;
    }
    .btn-join-job:hover { background-color: #59359a; }
    
    .status-disabled { opacity: 0.5; pointer-events: none; cursor: not-allowed; filter: grayscale(1); }
    .input-manual { width: 130px; padding: 8px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px; font-weight: 600; }
    .input-manual:focus { border-color: var(--accent-primary); outline: none; }
    .input-danger { border: 2px solid #dc3545 !important; color: #dc3545; background-color: #fff8f8; }
    .input-success { border: 2px solid #28a745 !important; color: #28a745; background-color: #f8fff9; }

    /* Modal */
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
    .modal-tabs { display: flex; border-bottom: 1px solid #dee2e6; margin-bottom: 20px; }
    .tab-link { padding: 10px 20px; cursor: pointer; border: none; background: none; font-weight: 500; color: #6c757d; }
    .tab-link.active { color: var(--accent-primary); border-bottom: 2px solid var(--accent-primary); }
    .tab-content { display: none; }
    .tab-content.active { display: block; }
</style>

<h1 class="page-title">Daftar Service: <?php echo $status; ?></h1>

<div class="card glass-effect">
    <div class="card-body">
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <?php if ($status != 'Selesai'): ?>
                            <th>Customer</th>
                            <th>Perangkat</th>
                            <th>Kerusakan</th>
                            <th>Kelengkapan</th>
                        <?php endif; ?>
                        <th>Status</th>
                        
                        <!-- Kolom Teknisi -->
                        <th>Tim Teknisi</th>

                        <?php if ($status == 'Selesai'): ?>
                            <th>Harga Part</th>
                            <th>Jasa</th>
                            <th>Sub Total</th>
                            <th>Uang Muka</th> 
                            <th>Total Bayar</th>
                            <th>Sisa</th>
                            <th>Pay</th>
                        <?php endif; ?>
                        
                        <?php if (!in_array($status, ['Antrian', 'Proses', 'Batal'])): ?>
                            <th>Pembayaran</th>
                        <?php endif; ?>

                        <?php if ($status == 'Proses'): ?>
                            <th>Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($services)): ?>
                        <tr><td colspan="12" style="text-align: center;">Tidak ada data untuk status "<?php echo $status; ?>"</td></tr>
                    <?php else: ?>
                        <?php foreach ($services as $row): ?>
                            <?php
                                // Kalkulasi Keuangan
                                $total_sparepart = $row['total_sparepart_calculated'];
                                $sub_total_db = $row['sub_total']; 
                                $biaya_jasa = max(0, $sub_total_db - $total_sparepart);
                                $uang_muka = $row['uang_muka'];
                                $total_bayar = isset($row['total_bayar']) ? $row['total_bayar'] : 0;
                                $sisa_bayar = $sub_total_db - $uang_muka - $total_bayar;

                                // Cek apakah user saat ini sudah ada di tim
                                $team_ids = !empty($row['team_ids']) ? explode(',', $row['team_ids']) : [];
                                $is_in_team = in_array($current_user_id, $team_ids);
                            ?>
                            <tr data-invoice="<?php echo htmlspecialchars($row['invoice']); ?>">
                                <td><?php echo htmlspecialchars($row['invoice']); ?></td>

                                <?php if ($status != 'Selesai'): ?>
                                    <td><?php echo htmlspecialchars($row['nama_customer'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['merek_hp'] . ' ' . $row['tipe_hp']); ?></td>
                                    <td><?php echo htmlspecialchars($row['kerusakan']); ?></td>
                                    <td><?php echo htmlspecialchars($row['kelengkapan']); ?></td>
                                <?php endif; ?>

                                <td>
                                    <!-- Status Service Badge -->
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

                                <!-- KOLOM TEKNISI (MULTI USER) -->
                                <td class="teknisi-cell">
                                    <?php if (!empty($row['team_teknisi'])): ?>
                                        <!-- Menampilkan List Teknisi (Teknisi 1, Teknisi 2, dst) -->
                                        <div style="font-weight: 600; color: #333; font-size: 12px; line-height: 1.4;">
                                            <i class="fas fa-users" style="color:#007aff; margin-right:4px;"></i> 
                                            <?php echo htmlspecialchars($row['team_teknisi']); ?>
                                        </div>
                                        
                                        <!-- Tombol Join (Muncul jika status Proses, user belum join, dan punya akses) -->
                                        <?php if ($status == 'Proses' && $can_take_job && !$is_in_team): ?>
                                            <button class="btn-join-job" onclick="takeJob('<?php echo $row['invoice']; ?>', this, 'join')" title="Gabung mengerjakan">
                                                <i class="fas fa-plus-circle"></i> Join
                                            </button>
                                        <?php endif; ?>

                                    <?php else: ?>
                                        <!-- BELUM ADA TEKNISI -->
                                        <?php if ($can_take_job): ?>
                                            <button class="btn-take-job" onclick="takeJob('<?php echo $row['invoice']; ?>', this, 'take')">
                                                <i class="fas fa-hand-paper"></i> Ambil Alih
                                            </button>
                                        <?php else: ?>
                                            <span style="color:#aaa; font-style:italic; font-size: 11px;">Belum ada teknisi</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>

                                <?php if ($status == 'Selesai'): ?>
                                    <td class="val-sparepart" data-value="<?php echo $total_sparepart; ?>">Rp <?php echo number_format($total_sparepart, 0, ',', '.'); ?></td>
                                    <td><input type="number" class="input-manual input-jasa" value="<?php echo $biaya_jasa; ?>" data-invoice="<?php echo $row['invoice']; ?>" placeholder="0"></td>
                                    <td class="val-subtotal" data-value="<?php echo $sub_total_db; ?>">Rp <?php echo number_format($sub_total_db, 0, ',', '.'); ?></td>
                                    <td class="val-uangmuka" data-value="<?php echo $uang_muka; ?>">Rp <?php echo number_format($uang_muka, 0, ',', '.'); ?></td>
                                    <td><input type="number" class="input-manual input-total-bayar" value="<?php echo $total_bayar; ?>" data-invoice="<?php echo $row['invoice']; ?>" placeholder="0"></td>
                                    <td class="val-sisa-bayar" style="font-weight:bold;">Rp <?php echo number_format($sisa_bayar, 0, ',', '.'); ?></td>
                                    <td><?php echo htmlspecialchars(ucfirst($row['metode_pembayaran'])); ?></td>
                                <?php endif; ?>

                                <?php if (!in_array($status, ['Antrian', 'Proses', 'Batal'])): ?>
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
                                <?php endif; ?>

                                <?php if ($status == 'Proses'): ?>
                                <td>
                                    <button class="btn btn-info manage-sparepart-btn" data-invoice="<?php echo htmlspecialchars($row['invoice']); ?>"><i class="fas fa-cog"></i> Sparepart</button>
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

<!-- Modal Sparepart -->
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
                            <tbody><!-- JS --></tbody>
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
                        <div class="form-group"><label>Jumlah</label><input type="number" id="sparepart-qty" value="1" min="1" class="form-control"></div>
                        <button type="button" id="add-sparepart-btn" class="btn btn-primary" style="width: 100%;">Tambah</button>
                     </div>
                     <div id="external" class="tab-content">
                         <form id="buyExternalForm">
                            <div class="form-group"><label>Nama Sparepart</label><input type="text" id="external_nama" class="form-control" required></div>
                            <div class="form-group"><label>Harga Beli Satuan (Modal)</label><input type="number" id="external_harga_beli" class="form-control" required placeholder="Harga beli dari toko luar"></div>
                            <div class="form-group"><label>Harga Jual Satuan (Ke Customer)</label><input type="number" id="external_harga_jual" class="form-control" required placeholder="Harga yang dibebankan ke customer"></div>
                             <div class="form-group"><label>Jumlah</label><input type="number" id="external_qty" value="1" min="1" class="form-control" required></div>
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
// Fungsi Ambil Alih Job / Join Job
function takeJob(invoice, btnElement, actionType) {
    const confirmMsg = actionType === 'join' 
        ? 'Anda akan bergabung mengerjakan service ini. Nama Anda akan ditambahkan ke tim. Lanjutkan?' 
        : 'Anda yakin ingin mengambil alih service ini? Status akan berubah menjadi PROSES.';
        
    if(!confirm(confirmMsg)) return;
    
    const originalText = btnElement.innerHTML;
    btnElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ...';
    btnElement.disabled = true;

    fetch('take_service.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ invoice: invoice, action: actionType })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            location.reload(); 
        } else {
            alert('Gagal: ' + data.message);
            btnElement.innerHTML = originalText;
            btnElement.disabled = false;
        }
    })
    .catch(err => {
        console.error(err);
        alert('Terjadi kesalahan jaringan.');
        btnElement.innerHTML = originalText;
        btnElement.disabled = false;
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const tableBody = document.querySelector('.data-table tbody');
    const modal = document.getElementById('sparepartModal');
    const closeModalBtns = modal.querySelectorAll('.close-btn');
    let currentInvoice = null;

    function formatRupiah(angka) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
    }

    const tabs = document.querySelectorAll('.tab-link');
    const tabContents = document.querySelectorAll('.tab-content');
    tabs.forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault(); 
            tabs.forEach(item => item.classList.remove('active'));
            this.classList.add('active');
            const targetId = this.dataset.tab;
            tabContents.forEach(content => content.classList.remove('active'));
            document.getElementById(targetId).classList.add('active');
        });
    });

    function calculateRow(row) {
        const sparepartVal = parseFloat(row.querySelector('.val-sparepart')?.dataset.value) || 0;
        const inputJasa = row.querySelector('.input-jasa');
        const jasaVal = parseFloat(inputJasa?.value) || 0;
        const newSubTotal = sparepartVal + jasaVal;
        const subTotalElem = row.querySelector('.val-subtotal');
        if(subTotalElem) {
            subTotalElem.textContent = formatRupiah(newSubTotal);
            subTotalElem.dataset.value = newSubTotal; 
        }
        const uangMukaVal = parseFloat(row.querySelector('.val-uangmuka')?.dataset.value) || 0;
        const inputTotalBayar = row.querySelector('.input-total-bayar');
        const totalBayarVal = parseFloat(inputTotalBayar?.value) || 0;
        const sisaBayar = newSubTotal - uangMukaVal - totalBayarVal;
        const sisaElem = row.querySelector('.val-sisa-bayar');
        if(sisaElem) {
            sisaElem.textContent = formatRupiah(sisaBayar);
            sisaElem.style.color = (sisaBayar > 0) ? '#dc3545' : '#28a745';
        }
        const payStatusContainer = row.querySelector('.status-container[data-type="payment"]');
        const payStatusBadge = payStatusContainer?.querySelector('.status-badge');
        if (sisaBayar > 0) {
            if(inputTotalBayar) { inputTotalBayar.classList.add('input-danger'); inputTotalBayar.classList.remove('input-success'); }
            if(payStatusContainer) {
                payStatusContainer.classList.add('status-disabled');
                if(payStatusBadge) { payStatusBadge.textContent = 'Belum Lunas'; payStatusBadge.className = 'status-badge status-belum-lunas'; }
            }
        } else {
            if(inputTotalBayar) { inputTotalBayar.classList.remove('input-danger'); inputTotalBayar.classList.add('input-success'); }
            if(payStatusContainer) { payStatusContainer.classList.remove('status-disabled'); }
        }
    }

    const jasaInputs = document.querySelectorAll('.input-jasa');
    const totalBayarInputs = document.querySelectorAll('.input-total-bayar');

    document.querySelectorAll('tbody tr').forEach(row => {
        if(row.querySelector('.input-jasa')) calculateRow(row);
    });

    jasaInputs.forEach(input => {
        input.addEventListener('input', function() { calculateRow(this.closest('tr')); });
        input.addEventListener('change', function() {
            fetch('update_biaya_jasa.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ invoice: this.dataset.invoice, biaya_jasa: this.value })
            }).then(r=>r.json()).then(d=> { 
                if(d.success) { 
                    const badge = this.closest('tr').querySelector('.status-container[data-type="payment"] .status-badge');
                    if(badge) { badge.textContent = d.status_bayar; badge.className = `status-badge status-${d.status_bayar.toLowerCase().replace(' ', '-')}`; }
                    calculateRow(this.closest('tr')); 
                }
            });
        });
    });

    totalBayarInputs.forEach(input => {
        input.addEventListener('input', function() { calculateRow(this.closest('tr')); });
        input.addEventListener('change', function() {
            fetch('update_total_bayar.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ invoice: this.dataset.invoice, total_bayar: this.value })
            }).then(r=>r.json()).then(d=> {
                if(d.success) {
                    const badge = this.closest('tr').querySelector('.status-container[data-type="payment"] .status-badge');
                    if(badge) { badge.textContent = d.status_bayar; badge.className = `status-badge status-${d.status_bayar.toLowerCase().replace(' ', '-')}`; }
                    calculateRow(this.closest('tr'));
                }
            });
        });
    });

    tableBody.addEventListener('click', function(event) {
        const target = event.target;
        const statusContainer = target.closest('.status-container');
        if (statusContainer && statusContainer.classList.contains('status-disabled')) return;
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
                const select = statusContainer.querySelector('.status-select');
                const endpoint = (statusContainer.dataset.type === 'service') ? 'update_status.php' : 'update_payment_status.php';
                fetch(endpoint, {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ invoice: invoice, status: select.value })
                }).then(r=>r.json()).then(d=>{
                    if(d.success) {
                        if(statusContainer.dataset.type === 'service') location.reload();
                        else {
                            statusContainer.querySelector('.status-badge').textContent = select.value;
                            statusContainer.querySelector('.status-badge').className = `status-badge status-${select.value.toLowerCase().replace(/ /g, '-')}`;
                            statusContainer.querySelector('.status-editor').style.display = 'none';
                            statusContainer.querySelector('.status-display').style.display = 'flex';
                        }
                    } else alert(d.message);
                });
            }
        }
        const manageBtn = target.closest('.manage-sparepart-btn');
        if (manageBtn) {
            currentInvoice = manageBtn.dataset.invoice;
            document.getElementById('modalTitle').textContent = `Penggunaan Sparepart (${currentInvoice})`;
            loadUsedParts(currentInvoice);
            modal.style.display = 'flex';
        }
    });

    closeModalBtns.forEach(btn => btn.addEventListener('click', () => { modal.style.display = 'none'; }));
    
    async function loadUsedParts(invoice) {
        const res = await fetch(`get_used_spareparts.php?invoice=${invoice}`);
        const result = await res.json();
        const tbody = document.getElementById('usedPartsTable').querySelector('tbody');
        tbody.innerHTML = '';
        if (result.success && result.data.length > 0) {
            document.getElementById('noPartsMessage').style.display = 'none';
            result.data.forEach(part => {
                tbody.innerHTML += `<tr><td>${part.nama}</td><td>${part.jumlah}</td><td>${part.tipe}</td><td><button class="remove-part-btn" data-id="${part.id}" data-type="${part.tipe}">Hapus</button></td></tr>`;
            });
        } else document.getElementById('noPartsMessage').style.display = 'block';
    }
    
    document.getElementById('add-sparepart-btn').addEventListener('click', async function() {
        const selector = document.getElementById('sparepart-selector');
        const qtyInput = document.getElementById('sparepart-qty');
        if(!selector.value) return alert('Pilih sparepart');
        fetch('add_sparepart_to_service.php', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({invoice:currentInvoice, spareparts:[{code:selector.value, qty:qtyInput.value, harga:selector.options[selector.selectedIndex].dataset.harga}]})
        }).then(r=>r.json()).then(d=>{ if(d.success) loadUsedParts(currentInvoice); else alert(d.message); });
    });

    document.getElementById('buyExternalForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        fetch('buy_external_sparepart.php', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({
                invoice: currentInvoice,
                nama: document.getElementById('external_nama').value,
                harga_beli: document.getElementById('external_harga_beli').value,
                harga_jual: document.getElementById('external_harga_jual').value,
                jumlah: document.getElementById('external_qty').value
            })
        }).then(r=>r.json()).then(d=>{ if(d.success){ loadUsedParts(currentInvoice); this.reset(); } else alert(d.message); });
    });

    document.getElementById('usedPartsTable').addEventListener('click', async function(e) {
        if(e.target.classList.contains('remove-part-btn')) {
             if(!confirm('Hapus?')) return;
             const endpoint = e.target.dataset.type === 'internal' ? 'remove_used_sparepart.php' : 'remove_external_sparepart.php';
             fetch(endpoint, { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({id:e.target.dataset.id}) })
             .then(r=>r.json()).then(d=>{ if(d.success) loadUsedParts(currentInvoice); });
        }
    });
});
</script>