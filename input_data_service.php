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
    /* Container Utama */
    .input-service-container {
        max-width: 1200px;
        margin: 0 auto;
    }

    /* Header Invoice */
    .invoice-header {
        background: linear-gradient(135deg, #007aff, #005bb5);
        color: white;
        padding: 20px 30px;
        border-radius: 16px 16px 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 4px 15px rgba(0, 122, 255, 0.2);
    }
    .invoice-header h2 {
        font-size: 24px;
        font-weight: 700;
        margin: 0;
        letter-spacing: 0.5px;
    }
    .invoice-header .invoice-label {
        font-size: 12px;
        opacity: 0.8;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    /* Layout Form Grid */
    .form-layout {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
        padding: 30px;
        background-color: #ffffff;
        border-radius: 0 0 16px 16px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
    }

    /* Section Styles */
    .form-section {
        background-color: #f9f9f9;
        padding: 25px;
        border-radius: 12px;
        border: 1px solid #eef0f2;
        height: 100%;
    }
    .form-section h4 {
        font-size: 16px;
        font-weight: 700;
        color: #1c1c1e;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #e5e5ea;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .form-section h4 i {
        color: var(--accent-primary);
    }

    /* Form Group Styles */
    .form-group { margin-bottom: 18px; }
    .form-group label {
        display: block;
        font-weight: 600;
        color: #48484a;
        margin-bottom: 8px;
        font-size: 13px;
    }
    .form-control {
        width: 100%;
        padding: 12px 16px;
        border: 1px solid #d1d1d6;
        border-radius: 8px;
        background-color: #fff;
        font-size: 14px;
        color: #1c1c1e;
        transition: all 0.2s ease;
    }
    .form-control:focus {
        border-color: var(--accent-primary);
        box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.15);
        outline: none;
    }
    .form-control[readonly] {
        background-color: #f2f2f7;
        color: #8e8e93;
        cursor: not-allowed;
    }
    textarea.form-control { resize: vertical; min-height: 80px; }

    /* Custom Select Wrapper for Customer */
    .customer-select-wrapper {
        display: flex;
        gap: 10px;
    }
    .btn-add-customer {
        background-color: #34c759;
        color: white;
        border: none;
        border-radius: 8px;
        width: 48px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        cursor: pointer;
        transition: background-color 0.2s;
    }
    .btn-add-customer:hover {
        background-color: #2db14d;
    }

    /* Radio Button Group */
    .radio-group {
        display: flex;
        gap: 20px;
        align-items: center;
        padding: 10px 0;
    }
    .radio-item {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
    }
    .radio-item input[type="radio"] {
        width: 18px;
        height: 18px;
        accent-color: var(--accent-primary);
        cursor: pointer;
    }

    /* Input Group (e.g., Duration) */
    .input-group {
        display: flex;
        gap: 10px;
    }

    /* Footer Button */
    .form-footer {
        margin-top: 30px;
        text-align: right;
        grid-column: span 2;
        border-top: 1px solid #eee;
        padding-top: 20px;
    }
    .btn-submit {
        background-color: var(--accent-primary);
        color: white;
        padding: 14px 32px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 16px;
        border: none;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(0, 122, 255, 0.3);
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0, 122, 255, 0.4);
    }

    /* Modal Styles (Dipercantik) */
    .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); align-items: center; justify-content: center; backdrop-filter: blur(5px); }
    .modal-content { background-color: #ffffff; padding: 0; border: none; width: 90%; max-width: 500px; border-radius: 16px; box-shadow: 0 20px 40px rgba(0,0,0,0.2); overflow: hidden; animation: modalSlideIn 0.3s ease; }
    @keyframes modalSlideIn { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    .modal-header { display: flex; justify-content: space-between; align-items: center; background-color: #f8f9fa; border-bottom: 1px solid #dee2e6; padding: 20px 30px; }
    .modal-header h2 { font-size: 20px; margin: 0; color: #333; font-weight: 700; }
    .modal-body { padding: 30px; }
    .modal-footer { background-color: #f8f9fa; border-top: 1px solid #dee2e6; padding: 20px 30px; text-align: right; display: flex; justify-content: flex-end; gap: 10px; }
    .close-btn { color: #8e8e93; font-size: 24px; font-weight: bold; cursor: pointer; background: none; border: none; transition: color 0.2s; }
    .close-btn:hover { color: #333; }

    /* Responsiveness */
    @media (max-width: 992px) {
        .form-layout { grid-template-columns: 1fr; }
        .form-footer { grid-column: span 1; text-align: center; }
        .btn-submit { width: 100%; justify-content: center; }
    }
</style>

<!-- KONTEN UTAMA HALAMAN -->
<h1 class="page-title">Input Data Service Baru</h1>

<?php echo $message; ?>

<div class="input-service-container">
    <!-- Header Kartu -->
    <div class="invoice-header">
        <div>
            <div class="invoice-label">Nomor Invoice</div>
            <h2><?php echo $invoice_number; ?></h2>
        </div>
        <div style="text-align: right;">
            <div class="invoice-label">Tanggal</div>
            <div style="font-weight: 600; font-size: 16px;"><?php echo date("d M Y"); ?></div>
        </div>
    </div>

    <form action="input_data_service.php" method="POST">
        <input type="hidden" name="invoice" value="<?php echo $invoice_number; ?>">

        <div class="form-layout">
            <!-- KOLOM KIRI: Data Konsumen & Teknisi -->
            <div class="left-column">
                <!-- Section 1: Data Konsumen -->
                <div class="form-section mb-4">
                    <h4><i class="fas fa-user-circle"></i> Data Konsumen</h4>
                    
                    <div class="form-group">
                        <label>Nama Konsumen <span style="color:red">*</span></label>
                        <div class="customer-select-wrapper">
                            <select id="customer_id" name="customer_id" class="form-control" required>
                                <option value="">--- Pilih Konsumen ---</option>
                                <?php foreach($customers as $customer): ?>
                                <option value="<?php echo $customer['id']; ?>" data-alamat="<?php echo htmlspecialchars($customer['alamat']); ?>" data-kontak="<?php echo htmlspecialchars($customer['no_hp']); ?>" data-keluhan="<?php echo htmlspecialchars($customer['keluhan']); ?>"><?php echo htmlspecialchars($customer['nama']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" id="newCustomerBtn" class="btn-add-customer" title="Tambah Konsumen Baru">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Kontak (WhatsApp)</label>
                        <input type="text" id="customer_kontak" class="form-control" readonly placeholder="Otomatis terisi...">
                    </div>

                    <div class="form-group">
                        <label>Alamat</label>
                        <textarea id="customer_alamat" class="form-control" rows="2" readonly placeholder="Otomatis terisi..."></textarea>
                    </div>
                </div>

                <!-- Section 2: Detail Kerusakan -->
                <div class="form-section" style="margin-top: 24px;">
                    <h4><i class="fas fa-tools"></i> Detail Service</h4>
                    
                    <div class="form-group">
                        <label>Keluhan / Kerusakan <span style="color:red">*</span></label>
                        <textarea id="kerusakan" name="kerusakan" class="form-control" rows="3" required placeholder="Jelaskan kerusakan perangkat..."></textarea>
                    </div>

                    <div class="form-group">
                        <label>Kelengkapan <span style="color:red">*</span></label>
                        <textarea name="kelengkapan" class="form-control" rows="2" required placeholder="Contoh: Unit + Charger + Dus"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Teknisi Penanggung Jawab <span style="color:red">*</span></label>
                        <select name="teknisi_id" class="form-control" required>
                            <option value="">--- Pilih Teknisi ---</option>
                            <?php foreach($teknisi as $tech): ?>
                            <option value="<?php echo $tech['id']; ?>"><?php echo htmlspecialchars($tech['nama']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- KOLOM KANAN: Perangkat & Biaya -->
            <div class="right-column">
                <!-- Section 3: Identitas Perangkat -->
                <div class="form-section mb-4">
                    <h4><i class="fas fa-mobile-alt"></i> Identitas Perangkat</h4>
                    
                    <div class="form-group">
                        <label>Merek Handphone <span style="color:red">*</span></label>
                        <input type="text" name="merek_hp" class="form-control" required placeholder="Contoh: Apple, Samsung">
                    </div>

                    <div class="form-group">
                        <label>Tipe Handphone <span style="color:red">*</span></label>
                        <input type="text" name="tipe_hp" class="form-control" required placeholder="Contoh: iPhone 13 Pro">
                    </div>

                    <div class="form-group">
                        <label>Nomor IMEI / Serial Number <span style="color:red">*</span></label>
                        <input type="text" name="imei_sn" class="form-control" required placeholder="Masukkan IMEI atau SN">
                    </div>
                </div>

                <!-- Section 4: Garansi & Pembayaran -->
                <div class="form-section" style="margin-top: 24px;">
                    <h4><i class="fas fa-file-invoice-dollar"></i> Garansi & Pembayaran</h4>
                    
                    <div class="form-group">
                        <label>Klaim Garansi?</label>
                        <div class="radio-group">
                            <label class="radio-item">
                                <input type="radio" id="garansi_no" name="garansi" value="tidak" checked>
                                <span>Tidak</span>
                            </label>
                            <label class="radio-item">
                                <input type="radio" id="garansi_yes" name="garansi" value="ya">
                                <span>Ya</span>
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Durasi Garansi Toko</label>
                        <div class="input-group">
                            <input type="number" name="durasi_garansi_val" value="0" class="form-control" style="flex: 1;">
                            <select name="durasi_garansi_unit" class="form-control" style="flex: 1;">
                                <option>Hari</option>
                                <option>Minggu</option>
                                <option>Bulan</option>
                                <option>Tahun</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Catatan Tambahan (Keterangan)</label>
                        <textarea name="keterangan" class="form-control" rows="2" placeholder="Catatan untuk teknisi atau kasir..."></textarea>
                    </div>

                    <div class="form-group">
                        <label>Uang Muka (DP)</label>
                        <div class="input-group">
                            <span style="padding: 12px; background: #eee; border: 1px solid #d1d1d6; border-right: none; border-radius: 8px 0 0 8px;">Rp</span>
                            <input type="number" name="uang_muka" value="0" class="form-control" style="border-radius: 0 8px 8px 0;">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Metode Pembayaran</label>
                        <select name="metode_pembayaran" class="form-control">
                            <option value="cash">Cash</option>
                            <option value="credit">Credit / Transfer</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Footer Tombol Submit -->
            <div class="form-footer">
                <button type="submit" name="create_order" class="btn-submit">
                    <i class="fas fa-check-circle"></i> BUAT ORDER SERVICE
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Modal/Popup untuk Tambah Customer Baru -->
<div id="customerModal" class="modal">
    <div class="modal-content">
        <form id="addCustomerForm">
            <div class="modal-header">
                <h2>Tambah Pelanggan Baru</h2>
                <button type="button" class="close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Nama Lengkap <span style="color:red">*</span></label>
                    <input type="text" id="new_customer_nama" class="form-control" required placeholder="Nama sesuai KTP/Panggilan">
                </div>
                <div class="form-group">
                    <label>Kontak (WhatsApp) <span style="color:red">*</span></label>
                    <input type="text" id="new_customer_kontak" class="form-control" required placeholder="08xxxxxxxxxx">
                </div>
                <div class="form-group">
                    <label>Alamat</label>
                    <textarea id="new_customer_alamat" class="form-control" rows="2" placeholder="Alamat domisili"></textarea>
                </div>
                <div class="form-group">
                    <label>Keluhan Awal <span style="color:red">*</span></label>
                    <textarea id="new_customer_keluhan" class="form-control" rows="2" required placeholder="Keluhan yang disampaikan pelanggan..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary close-btn" style="background: #e5e5ea; color: #333;">Batal</button>
                <button type="submit" class="btn btn-primary" style="background: #007aff; color: white; border: none;">Simpan Pelanggan</button>
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
    
    // Close modal when clicking outside
    window.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });
    
    addCustomerForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const nama = document.getElementById('new_customer_nama').value;
        const kontak = document.getElementById('new_customer_kontak').value;
        const alamat = document.getElementById('new_customer_alamat').value;
        const keluhan = document.getElementById('new_customer_keluhan').value;

        // Visual feedback on button
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerText;
        submitBtn.innerText = 'Menyimpan...';
        submitBtn.disabled = true;

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
                alert('Pelanggan berhasil ditambahkan!');
            } else {
                alert('Gagal menambahkan pelanggan: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan jaringan.');
        })
        .finally(() => {
            submitBtn.innerText = originalText;
            submitBtn.disabled = false;
        });
    });
});
</script>