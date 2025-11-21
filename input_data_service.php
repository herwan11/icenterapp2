<?php
// input_data_service.php

require_once 'includes/header.php';

$message = '';

// --- Logika Penyimpanan Data Service Utama---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_order'])) {
    try {
        $invoice = $_POST['invoice'];
        $kasir_id = $_SESSION['user_id'];
        $merek_hp = $_POST['merek_hp'];
        $tipe_hp = $_POST['tipe_hp'];
        $imei_sn = $_POST['imei_sn'];
        $kerusakan = $_POST['kerusakan'];
        $kelengkapan = $_POST['kelengkapan'];
        $teknisi_id = $_POST['teknisi_id'];
        $customer_id = $_POST['customer_id'];
        $garansi = $_POST['garansi'];
        $keterangan = $_POST['keterangan'];
        $durasi_garansi_val = $_POST['durasi_garansi_val'];
        $durasi_garansi_unit = $_POST['durasi_garansi_unit'];
        $durasi_garansi = $durasi_garansi_val . ' ' . $durasi_garansi_unit;
        
        // PERUBAHAN: Sub total diset ke 0 karena input dihilangkan dari form
        $sub_total = 0; 
        
        $uang_muka = $_POST['uang_muka'];
        $metode_pembayaran = $_POST['metode_pembayaran'];
        
        $status_pembayaran = ($uang_muka >= $sub_total && $sub_total > 0) ? 'Lunas' : 'Belum Lunas';

        $sql_service = "INSERT INTO service (invoice, kasir_id, merek_hp, tipe_hp, imei_sn, kerusakan, kelengkapan, teknisi_id, customer_id, garansi, keterangan, durasi_garansi, sub_total, uang_muka, metode_pembayaran, status_pembayaran) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_service = $conn->prepare($sql_service);
        $stmt_service->bind_param("sisssssiisssddss", $invoice, $kasir_id, $merek_hp, $tipe_hp, $imei_sn, $kerusakan, $kelengkapan, $teknisi_id, $customer_id, $garansi, $keterangan, $durasi_garansi, $sub_total, $uang_muka, $metode_pembayaran, $status_pembayaran);
        
        if ($stmt_service->execute()) {
            $message = "<div class='alert alert-success'>Order service baru dengan invoice <strong>$invoice</strong> berhasil dibuat!</div>";
        } else {
             $message = "<div class='alert alert-danger'>Gagal membuat order: " . $conn->error . "</div>";
        }

    } catch (Exception $e) {
        $message = "<div class='alert alert-danger'>Terjadi kesalahan: " . $e->getMessage() . "</div>";
    }
}

// --- Ambil Data untuk Dropdown ---
$customers = [];
$result_cust = $conn->query("SELECT id, nama, no_hp, alamat, keluhan FROM pelanggan ORDER BY nama ASC");
while($row = $result_cust->fetch_assoc()){ $customers[] = $row; }

$teknisi = [];
$result_tech = $conn->query("SELECT id, nama FROM karyawan WHERE jabatan = 'Teknisi' ORDER BY nama ASC");
while($row = $result_tech->fetch_assoc()){ $teknisi[] = $row; }

// --- Generate Nomor Invoice Unik ---
$tanggal_inv = date("dmy");
$waktu_inv = date("His");
$invoice_number = "INV-" . $tanggal_inv . "-" . $waktu_inv;
?>

<!-- Style khusus untuk halaman ini -->
<style>
    .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px 30px; }
    .form-section h4 { font-size: 16px; margin-bottom: 16px; border-bottom: 1px solid #eee; padding-bottom: 8px; color: var(--text-primary); }
    .form-group { margin-bottom: 16px; }
    .form-group label { display: block; font-weight: 500; color: #555; margin-bottom: 8px; font-size: 13px; }
    .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; background-color: #fff; transition: all 0.2s; }
    .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: var(--accent-primary); outline: none; box-shadow: 0 0 0 2px rgba(0, 122, 255, 0.2); }
    .form-group input[readonly], .form-group textarea[readonly] { background-color: #f5f5f5; cursor: not-allowed; }
    .radio-group label { margin-right: 15px; }
    .form-footer { margin-top: 30px; text-align: right; }

    /* Gaya untuk popup/modal yang lebih keren */
    .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); align-items: center; justify-content: center; backdrop-filter: blur(5px); }
    .modal-content { background-color: #ffffff; padding: 0; border: none; width: 90%; max-width: 500px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); overflow: hidden; }
    .modal-header { display: flex; justify-content: space-between; align-items: center; background-color: #f8f9fa; border-bottom: 1px solid #dee2e6; padding: 16px 24px; }
    .modal-header h2 { font-size: 18px; margin: 0; color: #333; }
    .modal-body { padding: 24px; }
    .modal-footer { background-color: #f8f9fa; border-top: 1px solid #dee2e6; padding: 16px 24px; text-align: right; }
    .close-btn { color: #6c757d; font-size: 28px; font-weight: bold; cursor: pointer; background: none; border: none; }
</style>

<!-- KONTEN UTAMA HALAMAN -->
<h1 class="page-title">Input Data Service Baru</h1>

<?php echo $message; ?>

<div class="card glass-effect">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h2 class="card-title mb-0">Invoice: <?php echo $invoice_number; ?></h2>
        <button id="newCustomerBtn" class="btn btn-success"><i class="fas fa-plus"></i> New Customer</button>
    </div>
    <div class="card-body">
        <form action="input_data_service.php" method="POST">
            <input type="hidden" name="invoice" value="<?php echo $invoice_number; ?>">

            <div class="form-grid">
                <!-- Kolom Kiri -->
                <div>
                    <div class="form-section">
                        <h4>Data Konsumen</h4>
                        <div class="form-group">
                            <label>Nama Konsumen *</label>
                             <select id="customer_id" name="customer_id" class="form-control" required>
                                <option value="">--- Pilih Konsumen ---</option>
                                <?php foreach($customers as $customer): ?>
                                <option value="<?php echo $customer['id']; ?>" data-alamat="<?php echo htmlspecialchars($customer['alamat']); ?>" data-kontak="<?php echo htmlspecialchars($customer['no_hp']); ?>" data-keluhan="<?php echo htmlspecialchars($customer['keluhan']); ?>"><?php echo htmlspecialchars($customer['nama']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group"><label>Alamat</label><textarea id="customer_alamat" rows="2" readonly></textarea></div>
                        <div class="form-group"><label>Kontak (WhatsApp)</label><input type="text" id="customer_kontak" readonly></div>
                    </div>
                    <div class="form-section">
                        <h4>Detail Service</h4>
                        <div class="form-group"><label>Kerusakan *</label><textarea id="kerusakan" name="kerusakan" rows="2" required></textarea></div>
                        <div class="form-group"><label>Kelengkapan *</label><textarea name="kelengkapan" rows="2" required></textarea></div>
                        <div class="form-group">
                            <label>Teknisi *</label>
                            <select name="teknisi_id" required>
                                <option value="">--- Pilih Teknisi ---</option>
                                <?php foreach($teknisi as $tech): ?>
                                <option value="<?php echo $tech['id']; ?>"><?php echo htmlspecialchars($tech['nama']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Kolom Kanan -->
                <div>
                    <div class="form-section">
                        <h4>Detail Perangkat</h4>
                        <div class="form-group"><label>Merek Handphone *</label><input type="text" name="merek_hp" required></div>
                        <div class="form-group"><label>Tipe Handphone *</label><input type="text" name="tipe_hp" required></div>
                        <div class="form-group"><label>Imei / SN *</label><input type="text" name="imei_sn" required></div>
                    </div>
                    <div class="form-section">
                        <h4>Garansi & Biaya</h4>
                        <div class="form-group"><label>Untuk Klaim Garansi</label><div class="radio-group"><input type="radio" id="garansi_yes" name="garansi" value="ya"><label for="garansi_yes">Yes</label><input type="radio" id="garansi_no" name="garansi" value="tidak" checked><label for="garansi_no">No</label></div></div>
                        <div class="form-group"><label>Deskripsi (Keterangan)</label><textarea name="keterangan" rows="2"></textarea></div>
                        <div class="form-group"><label>Durasi Garansi</label><div class="input-group"><input type="number" name="durasi_garansi_val" value="0" class="form-control"><select name="durasi_garansi_unit" class="form-control"><option>Hari</option><option>Minggu</option><option>Bulan</option><option>Tahun</option></select></div></div>
                        
                        <!-- PERUBAHAN: Input Sub Total DIHILANGKAN -->
                        
                        <div class="form-group"><label>Uang Muka</label><input type="number" name="uang_muka" value="0"></div>
                        <div class="form-group"><label>Payment</label><select name="metode_pembayaran"><option value="cash">Cash</option><option value="credit">Credit</option></select></div>
                    </div>
                </div>
            </div>
            
            <div class="form-footer">
                <button type="submit" name="create_order" class="btn btn-primary">CREATE ORDER</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal/Popup untuk Tambah Customer Baru -->
<div id="customerModal" class="modal">
    <div class="modal-content">
        <form id="addCustomerForm">
            <div class="modal-header">
                <h2>Pelanggan Baru</h2>
                <button type="button" class="close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group"><label>Nama *</label><input type="text" id="new_customer_nama" required></div>
                <div class="form-group"><label>Kontak (WhatsApp) *</label><input type="text" id="new_customer_kontak" required></div>
                <div class="form-group"><label>Alamat</label><textarea id="new_customer_alamat" rows="2"></textarea></div>
                <div class="form-group"><label>Keluhan *</label><textarea id="new_customer_keluhan" rows="2" required></textarea></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary close-btn">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Pelanggan</button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<!-- Skrip JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const customerSelect = document.getElementById('customer_id');
    const customerAlamat = document.getElementById('customer_alamat');
    const customerKontak = document.getElementById('customer_kontak');
    const kerusakanTextarea = document.getElementById('kerusakan');

    customerSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        customerAlamat.value = selectedOption.getAttribute('data-alamat') || '';
        customerKontak.value = selectedOption.getAttribute('data-kontak') || '';
        kerusakanTextarea.value = selectedOption.getAttribute('data-keluhan') || ''; 
    });

    // --- Logika untuk Modal Customer ---
    const modal = document.getElementById('customerModal');
    const openModalBtn = document.getElementById('newCustomerBtn');
    const closeModalBtns = modal.querySelectorAll('.close-btn');
    const addCustomerForm = document.getElementById('addCustomerForm');

    openModalBtn.addEventListener('click', () => { modal.style.display = 'flex'; });
    closeModalBtns.forEach(btn => { btn.addEventListener('click', () => { modal.style.display = 'none'; }); });
    
    addCustomerForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const nama = document.getElementById('new_customer_nama').value;
        const kontak = document.getElementById('new_customer_kontak').value;
        const alamat = document.getElementById('new_customer_alamat').value;
        const keluhan = document.getElementById('new_customer_keluhan').value;

        fetch('add_customer.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nama, kontak, alamat, keluhan }) 
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const newOption = new Option(data.customer.nama, data.customer.id, true, true);
                newOption.setAttribute('data-alamat', data.customer.alamat);
                newOption.setAttribute('data-kontak', data.customer.kontak);
                newOption.setAttribute('data-keluhan', data.customer.keluhan);
                customerSelect.appendChild(newOption);
                
                // Trigger change event
                customerSelect.dispatchEvent(new Event('change'));

                modal.style.display = 'none';
                addCustomerForm.reset();
            } else {
                alert('Gagal menambahkan pelanggan: ' + data.message);
            }
        })
        .catch(error => console.error('Error:', error));
    });
});
</script>