<?php
// all_data_service.php

require_once 'includes/header.php';

// Ambil data teknisi untuk dropdown edit
$teknisi_list = [];
$res_tech = $conn->query("SELECT id, nama FROM karyawan WHERE jabatan = 'Teknisi' ORDER BY nama ASC");
while($row = $res_tech->fetch_assoc()) { $teknisi_list[] = $row; }

// --- Ambil SEMUA data service ---
$service_data = [];
$sql = "SELECT 
            s.*, 
            c.nama as nama_customer, 
            k.nama as nama_teknisi,
            u.nama as nama_kasir
        FROM service s
        LEFT JOIN customers c ON s.customer_id = c.id
        LEFT JOIN karyawan k ON s.teknisi_id = k.id
        LEFT JOIN users u ON s.kasir_id = u.id
        ORDER BY s.tanggal DESC";

$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Ambil info sparepart ringkas untuk tabel utama
        $invoice = $row['invoice'];
        $parts_summary = [];
        
        $sql_int = "SELECT ms.nama FROM sparepart_keluar sk JOIN master_sparepart ms ON sk.code_sparepart = ms.code_sparepart WHERE sk.invoice_service = '$invoice'";
        $res_int = $conn->query($sql_int);
        while($p = $res_int->fetch_assoc()) $parts_summary[] = $p['nama'];
        
        $sql_ext = "SELECT nama_sparepart FROM pembelian_sparepart_luar WHERE invoice_service = '$invoice'";
        $res_ext = $conn->query($sql_ext);
        while($p = $res_ext->fetch_assoc()) $parts_summary[] = $p['nama_sparepart'] . " (Ext)";
        
        $row['parts_desc'] = !empty($parts_summary) ? implode(", ", $parts_summary) : "-";
        $service_data[] = $row;
    }
}
$conn->close();

function get_status_color($status) {
    switch (strtolower($status)) {
        case 'selesai': case 'diambil': return '#28a745';
        case 'proses': return '#007bff';
        case 'batal': case 'refund': return '#dc3545';
        default: return '#ffc107';
    }
}
?>

<style>
    .data-table-container { padding: 24px; }
    .data-table { width: 100%; border-collapse: collapse; font-size: 12px; }
    .data-table th, .data-table td { padding: 10px 12px; text-align: left; border-bottom: 1px solid var(--border-color); white-space: nowrap; vertical-align: middle; }
    .data-table thead th { background-color: #f8f9fa; color: var(--text-secondary); font-weight: 600; text-transform: uppercase; font-size: 11px; }
    .data-table tbody tr:hover { background-color: #f1f1f1; }
    .status-badge-all { padding: 4px 8px; border-radius: 12px; color: white; font-size: 10px; font-weight: 600; }
    .col-sparepart { white-space: normal !important; max-width: 200px; color: #555; }
    
    .btn-action { padding: 6px 10px; border-radius: 6px; font-size: 11px; margin-right: 4px; border: none; cursor: pointer; color: white; transition: transform 0.1s; }
    .btn-action:hover { transform: scale(1.05); }
    .btn-view { background-color: #17a2b8; }
    .btn-edit { background-color: #ffc107; color: #333; }
    .btn-delete { background-color: #dc3545; }

    /* Modal Styles */
    .modal { display: none; position: fixed; z-index: 1050; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); backdrop-filter: blur(2px); }
    .modal-content { background-color: #fff; margin: 5% auto; padding: 0; border: 1px solid #888; width: 90%; max-width: 700px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.2); animation: slideDown 0.3s ease; }
    @keyframes slideDown { from {transform: translateY(-50px); opacity: 0;} to {transform: translateY(0); opacity: 1;} }
    .modal-header { padding: 15px 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; background: #f8f9fa; border-radius: 12px 12px 0 0; }
    .modal-body { padding: 20px; max-height: 70vh; overflow-y: auto; }
    .modal-footer { padding: 15px 20px; border-top: 1px solid #eee; text-align: right; background: #f8f9fa; border-radius: 0 0 12px 12px; }
    .close-btn { font-size: 24px; cursor: pointer; color: #aaa; background:none; border:none; } .close-btn:hover { color: #000; }

    /* Nota Styles (Print Friendly) */
    .nota-container { font-family: 'Courier New', Courier, monospace; color: #000; padding: 20px; background: #fff; }
    .nota-header { text-align: center; margin-bottom: 20px; border-bottom: 2px dashed #000; padding-bottom: 10px; }
    .nota-logo { max-width: 120px; margin-bottom: 10px; display: block; margin-left: auto; margin-right: auto; }
    .nota-title { font-size: 18px; font-weight: bold; margin-bottom: 5px; }
    .nota-address { font-size: 11px; line-height: 1.4; margin-bottom: 5px; }
    .nota-contact { font-size: 12px; font-weight: bold; }
    .nota-info { font-size: 12px; margin-bottom: 15px; }
    .nota-table { width: 100%; font-size: 12px; border-collapse: collapse; margin-bottom: 10px; }
    .nota-table th { text-align: left; border-bottom: 1px dashed #000; padding: 5px 0; }
    .nota-table td { padding: 5px 0; }
    .nota-total { border-top: 1px dashed #000; padding-top: 5px; margin-top: 10px; }
    .nota-footer { text-align: center; margin-top: 20px; font-size: 11px; border-top: 2px dashed #000; padding-top: 10px; }
    .nota-website { margin-top: 8px; font-weight: bold; font-size: 12px; }
    
    @media print {
        body * { visibility: hidden; }
        #printableArea, #printableArea * { visibility: visible; }
        #printableArea { position: absolute; left: 0; top: 0; width: 100%; padding: 0; }
        .modal-content { box-shadow: none; border: none; }
        .nota-container { padding: 0; width: 100%; max-width: 100%; }
    }
</style>

<h1 class="page-title">All Service Data</h1>

<div class="glass-effect data-table-container">
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 120px;">Aksi</th>
                    <th>Invoice</th>
                    <th>Tanggal</th>
                    <th>Customer</th>
                    <th>Perangkat</th>
                    <th>Kerusakan</th>
                    <th>Sparepart</th>
                    <th>Teknisi</th>
                    <th>Total Biaya</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($service_data)): ?>
                    <tr><td colspan="10" align="center">Belum ada data.</td></tr>
                <?php else: ?>
                    <?php foreach ($service_data as $data): ?>
                    <tr>
                        <td>
                            <button class="btn-action btn-view" onclick="showNota('<?php echo $data['invoice']; ?>')" title="Lihat Nota"><i class="fas fa-print"></i></button>
                            <button class="btn-action btn-edit" onclick="editService('<?php echo $data['invoice']; ?>')" title="Edit Data"><i class="fas fa-edit"></i></button>
                            <button class="btn-action btn-delete" onclick="deleteService('<?php echo $data['invoice']; ?>')" title="Hapus"><i class="fas fa-trash-alt"></i></button>
                        </td>
                        <td><?php echo $data['invoice']; ?></td>
                        <td><?php echo date('d/m/y H:i', strtotime($data['tanggal'])); ?></td>
                        <td><?php echo htmlspecialchars($data['nama_customer'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($data['merek_hp'] . ' ' . $data['tipe_hp']); ?></td>
                        <td><?php echo htmlspecialchars($data['kerusakan']); ?></td>
                        <td class="col-sparepart"><?php echo htmlspecialchars($data['parts_desc']); ?></td>
                        <td><?php echo htmlspecialchars($data['nama_teknisi'] ?? '-'); ?></td>
                        <td>Rp <?php echo number_format($data['sub_total'], 0, ',', '.'); ?></td>
                        <td>
                            <span class="status-badge-all" style="background-color: <?php echo get_status_color($data['status_service']); ?>;">
                                <?php echo $data['status_service']; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- 1. MODAL EDIT SERVICE -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Edit Data Service</h3>
            <button class="close-btn" onclick="closeModal('editModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="editForm">
                <input type="hidden" id="edit_invoice" name="invoice">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Merek HP</label>
                        <input type="text" id="edit_merek" name="merek_hp" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Tipe HP</label>
                        <input type="text" id="edit_tipe" name="tipe_hp" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>IMEI / SN</label>
                        <input type="text" id="edit_imei" name="imei_sn" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Teknisi</label>
                        <select id="edit_teknisi" name="teknisi_id" class="form-control">
                            <?php foreach($teknisi_list as $t): ?>
                                <option value="<?php echo $t['id']; ?>"><?php echo $t['nama']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Kerusakan</label>
                    <textarea id="edit_kerusakan" name="kerusakan" class="form-control" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label>Kelengkapan</label>
                    <textarea id="edit_kelengkapan" name="kelengkapan" class="form-control" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label>Status Service</label>
                    <select id="edit_status" name="status_service" class="form-control">
                        <option value="Antrian">Antrian</option>
                        <option value="Proses">Proses</option>
                        <option value="Selesai">Selesai</option>
                        <option value="Diambil">Diambil</option>
                        <option value="Batal">Batal</option>
                        <option value="Refund">Refund</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Catatan Tambahan</label>
                    <textarea id="edit_keterangan" name="keterangan" class="form-control" rows="2"></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('editModal')">Batal</button>
            <button class="btn btn-primary" onclick="submitEdit()">Simpan Perubahan</button>
        </div>
    </div>
</div>

<!-- 2. MODAL NOTA (LIHAT) -->
<div id="notaModal" class="modal">
    <div class="modal-content" style="max-width: 400px;"> <!-- Ukuran kertas struk -->
        <div class="modal-header">
            <h3>Pratinjau Nota</h3>
            <button class="close-btn" onclick="closeModal('notaModal')">&times;</button>
        </div>
        <div class="modal-body" id="printableArea">
            <div class="nota-container">
                <div class="nota-header">
                    <!-- LOGO DITAMBAHKAN -->
                    <img src="assets/media/icenter.png" alt="iCenter Logo" class="nota-logo">
                    
                    <div class="nota-title">iCenter Apple</div>
                    
                    <!-- ALAMAT DIPERBAIKI -->
                    <div class="nota-address">
                        Jl. Nangka, Mappasaile, Kec. Pangkajene,<br>
                        Kabupaten Pangkajene Dan Kepulauan,<br>
                        Sulawesi Selatan 90611
                    </div>
                    
                    <!-- WA DIPERBAIKI -->
                    <div class="nota-contact">WA: 0852-9805-8500</div>
                </div>
                
                <div class="nota-info">
                    <table style="width:100%">
                        <tr><td>No. Invoice</td><td align="right" id="nota_inv">INV-XXX</td></tr>
                        <tr><td>Tanggal</td><td align="right" id="nota_date">01/01/2025</td></tr>
                        <tr><td>Pelanggan</td><td align="right" id="nota_cust">Nama User</td></tr>
                        <tr><td>Unit</td><td align="right" id="nota_unit">iPhone 13</td></tr>
                        <tr><td>Teknisi</td><td align="right" id="nota_tech">Budi</td></tr>
                    </table>
                </div>
                
                <table class="nota-table">
                    <thead>
                        <tr>
                            <th>Deskripsi</th>
                            <th style="text-align:right;">Biaya</th>
                        </tr>
                    </thead>
                    <tbody id="nota_items">
                        <!-- Items will be injected here -->
                    </tbody>
                </table>

                <div class="nota-total">
                    <table style="width:100%">
                        <tr><td><strong>Total</strong></td><td align="right"><strong id="nota_total">Rp 0</strong></td></tr>
                        <tr><td>Bayar/DP</td><td align="right" id="nota_bayar">Rp 0</td></tr>
                        <tr><td>Sisa</td><td align="right" id="nota_sisa">Rp 0</td></tr>
                        <tr><td colspan="2" style="padding-top:5px; text-align:center;"><span id="nota_status_bayar" style="border:1px solid #000; padding:2px 5px;">LUNAS</span></td></tr>
                    </table>
                </div>

                <div class="nota-footer">
                    <p>Terima Kasih atas Kepercayaan Anda</p>
                    <p>Garansi berlaku sesuai ketentuan.</p>
                    <!-- WEBSITE DITAMBAHKAN -->
                    <p class="nota-website">www.icenterpangkep.my.id</p>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('notaModal')">Tutup</button>
            <button class="btn btn-primary" onclick="window.print()">Print Nota</button>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<script>
// --- MODAL FUNCTIONS ---
function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

// --- DELETE FUNCTION ---
function deleteService(invoice) {
    if(confirm('PERINGATAN: Menghapus data service ini akan menghapus semua riwayat sparepart terkait dan mengembalikan stok (jika ada). Anda yakin?')) {
        fetch('delete_service.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({invoice: invoice})
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert(data.message);
            }
        })
        .catch(err => alert('Gagal menghapus.'));
    }
}

// --- EDIT FUNCTION ---
function editService(invoice) {
    fetch(`get_service_detail.php?invoice=${invoice}`)
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            const s = data.service;
            document.getElementById('edit_invoice').value = s.invoice;
            document.getElementById('edit_merek').value = s.merek_hp;
            document.getElementById('edit_tipe').value = s.tipe_hp;
            document.getElementById('edit_imei').value = s.imei_sn;
            document.getElementById('edit_kerusakan').value = s.kerusakan;
            document.getElementById('edit_kelengkapan').value = s.kelengkapan;
            document.getElementById('edit_teknisi').value = s.teknisi_id;
            document.getElementById('edit_status').value = s.status_service;
            document.getElementById('edit_keterangan').value = s.keterangan;
            
            document.getElementById('editModal').style.display = 'block';
        } else {
            alert(data.message);
        }
    });
}

function submitEdit() {
    const formData = new FormData(document.getElementById('editForm'));
    const data = Object.fromEntries(formData.entries());

    fetch('update_service_full.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.message);
        }
    });
}

// --- SHOW NOTA FUNCTION ---
function showNota(invoice) {
    fetch(`get_service_detail.php?invoice=${invoice}`)
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            const s = data.service;
            const parts = data.parts;

            // Fill Info
            document.getElementById('nota_inv').innerText = s.invoice;
            document.getElementById('nota_date').innerText = s.tanggal.split(' ')[0]; // Ambil tanggal saja
            document.getElementById('nota_cust').innerText = s.nama_customer || 'Umum';
            document.getElementById('nota_unit').innerText = s.merek_hp + ' ' + s.tipe_hp;
            document.getElementById('nota_tech').innerText = s.nama_teknisi || '-';

            // Fill Items Table
            const tbody = document.getElementById('nota_items');
            tbody.innerHTML = '';
            
            let total_part_price = 0;
            
            // Render Spareparts
            parts.forEach(p => {
                const subtotal = parseFloat(p.subtotal);
                total_part_price += subtotal;
                tbody.innerHTML += `
                    <tr>
                        <td>${p.nama_part} (${p.jumlah}x)</td>
                        <td align="right">Rp ${subtotal.toLocaleString('id-ID')}</td>
                    </tr>
                `;
            });

            // Render Jasa Service (Subtotal - Total Part)
            // Asumsi: sub_total di tabel service adalah Total Tagihan
            const grand_total = parseFloat(s.sub_total);
            const jasa = grand_total - total_part_price;
            
            if (jasa > 0) {
                tbody.innerHTML += `
                    <tr>
                        <td>Jasa Service & Perbaikan</td>
                        <td align="right">Rp ${jasa.toLocaleString('id-ID')}</td>
                    </tr>
                `;
            }

            // Fill Totals
            document.getElementById('nota_total').innerText = 'Rp ' + grand_total.toLocaleString('id-ID');
            
            const bayar = parseFloat(s.uang_muka) + (parseFloat(s.total_bayar) || 0); // Asumsi kolom total_bayar menyimpan pembayaran tambahan
            document.getElementById('nota_bayar').innerText = 'Rp ' + bayar.toLocaleString('id-ID');
            
            const sisa = grand_total - bayar;
            document.getElementById('nota_sisa').innerText = 'Rp ' + sisa.toLocaleString('id-ID');
            
            document.getElementById('nota_status_bayar').innerText = s.status_pembayaran.toUpperCase();
            
            // Show Modal
            document.getElementById('notaModal').style.display = 'block';
        } else {
            alert(data.message);
        }
    });
}
</script>