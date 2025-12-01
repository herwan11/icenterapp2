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
    .invoice-header h2 { font-size: 24px; font-weight: 700; margin: 0; letter-spacing: 0.5px; }
    .invoice-header .invoice-label { font-size: 12px; opacity: 0.8; text-transform: uppercase; letter-spacing: 1px; }

    /* FORM LAYOUT BARU (GRID) */
    .form-main-wrapper {
        background-color: #ffffff;
        border-radius: 0 0 16px 16px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        padding: 24px;
    }

    .grid-top-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
        margin-bottom: 24px;
    }

    .bottom-row-section {
        margin-top: 24px;
    }

    /* Section Styles */
    .form-section {
        background-color: #f9f9f9;
        padding: 20px;
        border-radius: 12px;
        border: 1px solid #eef0f2;
        height: 100%; /* Agar tinggi kolom kiri & kanan seimbang */
    }
    .form-section h4 {
        font-size: 15px;
        font-weight: 700;
        color: #1c1c1e;
        margin-bottom: 16px;
        padding-bottom: 8px;
        border-bottom: 2px solid #e5e5ea;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .form-section h4 i { color: var(--accent-primary); }

    /* Form Elements */
    .form-group { margin-bottom: 16px; }
    .form-group label {
        display: block;
        font-weight: 600;
        color: #48484a;
        margin-bottom: 6px;
        font-size: 13px;
    }
    .form-control {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid #d1d1d6;
        border-radius: 8px;
        background-color: #fff;
        font-size: 14px;
        color: #1c1c1e;
        transition: all 0.2s ease;
    }
    .form-control:focus { border-color: var(--accent-primary); box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.15); outline: none; }
    .form-control[readonly] { background-color: #fff; cursor: pointer; } /* Agar terlihat seperti input biasa tapi clickable */
    textarea.form-control { resize: vertical; min-height: 80px; }

    /* Custom Select Wrapper for Customer */
    .customer-select-wrapper { display: flex; gap: 8px; }
    .btn-add-customer {
        background-color: #34c759;
        color: white;
        border: none;
        border-radius: 8px;
        width: 42px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        cursor: pointer;
        transition: background-color 0.2s;
    }
    .btn-add-customer:hover { background-color: #2db14d; }

    /* Grid untuk Garansi & Pembayaran (Bottom) */
    .payment-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
    }

    /* Radio & Checkbox */
    .radio-group { display: flex; gap: 16px; align-items: center; min-height: 42px; }
    .radio-item { display: flex; align-items: center; gap: 6px; cursor: pointer; font-size: 14px; }
    .radio-item input { width: 16px; height: 16px; accent-color: var(--accent-primary); cursor: pointer; }

    /* Footer Button */
    .form-footer {
        margin-top: 24px;
        text-align: right;
        border-top: 1px solid #eee;
        padding-top: 20px;
    }
    .btn-submit {
        background-color: var(--accent-primary);
        color: white;
        padding: 12px 32px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 15px;
        border: none;
        cursor: pointer;
        box-shadow: 0 4px 10px rgba(0, 122, 255, 0.3);
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 6px 14px rgba(0, 122, 255, 0.4); }

    /* Modal Styles */
    .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); align-items: center; justify-content: center; backdrop-filter: blur(3px); }
    .modal-content { background-color: #ffffff; padding: 0; border: none; width: 90%; max-width: 500px; border-radius: 16px; box-shadow: 0 20px 40px rgba(0,0,0,0.2); overflow: hidden; animation: modalSlideIn 0.3s ease; }
    @keyframes modalSlideIn { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    .modal-header { display: flex; justify-content: space-between; align-items: center; background-color: #f8f9fa; border-bottom: 1px solid #dee2e6; padding: 16px 24px; }
    .modal-header h2 { font-size: 18px; margin: 0; color: #333; font-weight: 700; }
    .modal-body { padding: 24px; }
    .modal-footer { background-color: #f8f9fa; border-top: 1px solid #dee2e6; padding: 16px 24px; text-align: right; display: flex; justify-content: flex-end; gap: 10px; }
    .close-btn { color: #8e8e93; font-size: 20px; font-weight: bold; cursor: pointer; background: none; border: none; }
    .close-btn:hover { color: #333; }

    .customer-list { max-height: 300px; overflow-y: auto; border: 1px solid #e5e5ea; border-radius: 8px; margin-top: 10px; }
    .customer-item { padding: 10px 14px; border-bottom: 1px solid #f2f2f7; cursor: pointer; display: flex; justify-content: space-between; align-items: center; }
    .customer-item:last-child { border-bottom: none; }
    .customer-item:hover { background-color: #f0f8ff; }
    .cust-name { font-weight: 600; color: #1c1c1e; font-size: 14px; }
    .cust-contact { font-size: 12px; color: #8a8a8e; }

    /* Responsiveness */
    @media (max-width: 992px) {
        .grid-top-row { grid-template-columns: 1fr; }
        .payment-grid { grid-template-columns: 1fr; }
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

        <div class="form-main-wrapper">
            
            <!-- GRID ATAS: 2 Kolom -->
            <div class="grid-top-row">
                
                <!-- KOLOM KIRI: Konsumen & Perangkat -->
                <div class="left-column-wrapper" style="display: flex; flex-direction: column; gap: 24px;">
                    
                    <!-- 1. Data Konsumen (Ringkas) -->
                    <div class="form-section">
                        <h4><i class="fas fa-user-circle"></i> Data Konsumen</h4>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>Nama Konsumen <span style="color:red">*</span></label>
                            <div class="customer-select-wrapper">
                                <!-- Hidden ID -->
                                <input type="hidden" id="customer_id" name="customer_id" required>
                                
                                <!-- Tampilan Nama (Readonly + Clickable) -->
                                <input type="text" id="customer_display_name" class="form-control" readonly placeholder="Klik tombol + untuk memilih/tambah" style="cursor: pointer;" onclick="openSearchModal()">
                                
                                <!-- Tombol Plus -->
                                <button type="button" class="btn-add-customer" title="Cari / Tambah Konsumen" onclick="openSearchModal()">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Identitas Perangkat -->
                    <div class="form-section" style="flex-grow: 1;">
                        <h4><i class="fas fa-mobile-alt"></i> Identitas Perangkat</h4>
                        <div class="form-group">
                            <label>Merek <span style="color:red">*</span></label>
                            <input type="text" name="merek_hp" class="form-control" required placeholder="Apple, Samsung, dll">
                        </div>
                        <div class="form-group">
                            <label>Tipe <span style="color:red">*</span></label>
                            <input type="text" name="tipe_hp" class="form-control" required placeholder="iPhone 13, S23 Ultra">
                        </div>
                        <div class="form-group">
                            <label>IMEI / SN <span style="color:red">*</span></label>
                            <input type="text" name="imei_sn" class="form-control" required placeholder="Scan atau ketik manual">
                        </div>
                    </div>
                </div>

                <!-- KOLOM KANAN: Detail Service -->
                <div class="right-column-wrapper">
                    <div class="form-section" style="height: 100%;">
                        <h4><i class="fas fa-tools"></i> Detail Service</h4>
                        
                        <div class="form-group">
                            <label>Keluhan / Kerusakan <span style="color:red">*</span></label>
                            <textarea id="kerusakan" name="kerusakan" class="form-control" rows="4" required placeholder="Jelaskan detail kerusakan perangkat..."></textarea>
                        </div>

                        <div class="form-group">
                            <label>Kelengkapan <span style="color:red">*</span></label>
                            <textarea name="kelengkapan" class="form-control" rows="3" required placeholder="Unit, Dus, Charger, dll..."></textarea>
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

            </div> <!-- End Grid Top -->

            <!-- BAGIAN BAWAH (Full Width): Garansi & Pembayaran -->
            <div class="form-section bottom-row-section">
                <h4><i class="fas fa-file-invoice-dollar"></i> Garansi & Pembayaran</h4>
                
                <div class="payment-grid">
                    <!-- Item 1: Klaim Garansi -->
                    <div class="form-group">
                        <label>Klaim Garansi?</label>
                        <div class="radio-group">
                            <label class="radio-item"><input type="radio" id="garansi_no" name="garansi" value="tidak" checked> Tidak</label>
                            <label class="radio-item"><input type="radio" id="garansi_yes" name="garansi" value="ya"> Ya</label>
                        </div>
                    </div>

                    <!-- Item 2: Durasi Garansi -->
                    <div class="form-group">
                        <label>Durasi Garansi Toko</label>
                        <div style="display: flex; gap: 10px;">
                            <input type="number" name="durasi_garansi_val" value="0" class="form-control" style="width: 70px;">
                            <select name="durasi_garansi_unit" class="form-control" style="flex: 1;">
                                <option>Hari</option>
                                <option>Minggu</option>
                                <option>Bulan</option>
                            </select>
                        </div>
                    </div>

                    <!-- Item 3: Catatan -->
                    <div class="form-group">
                        <label>Catatan (Keterangan)</label>
                        <input type="text" name="keterangan" class="form-control" placeholder="Note kasir...">
                    </div>

                    <!-- Item 4: Uang Muka -->
                    <div class="form-group">
                        <label>Uang Muka (DP)</label>
                        <div style="display: flex; align-items: center;">
                            <span style="padding: 0 10px; background: #eee; border: 1px solid #d1d1d6; border-right: none; height: 42px; display: flex; align-items: center; border-radius: 8px 0 0 8px;">Rp</span>
                            <input type="number" name="uang_muka" value="0" class="form-control" style="border-radius: 0 8px 8px 0;">
                        </div>
                    </div>

                    <!-- Item 5: Metode -->
                    <div class="form-group">
                        <label>Metode Pembayaran</label>
                        <select name="metode_pembayaran" class="form-control">
                            <option value="cash">Cash</option>
                            <option value="credit">Transfer / Debit</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="form-footer">
                <button type="submit" name="create_order" class="btn-submit">
                    <i class="fas fa-save"></i> BUAT ORDER SERVICE
                </button>
            </div>

        </div>
    </form>
</div>

<!-- MODAL 1: SEARCH & SELECT CUSTOMER -->
<div id="searchCustomerModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Pilih Konsumen</h2>
            <button type="button" class="close-btn" onclick="closeSearchModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <input type="text" id="search_customer_input" class="form-control" placeholder="Cari nama atau nomor HP..." onkeyup="filterCustomers()" autofocus>
            </div>
            
            <div class="customer-list" id="customerListContainer">
                <!-- List via JS -->
            </div>

            <div style="text-align: center; margin-top: 20px; padding-top: 15px; border-top: 1px solid #eee;">
                <p style="margin-bottom: 10px; color: #666; font-size: 13px;">Konsumen belum terdaftar?</p>
                <button type="button" class="btn btn-primary" onclick="openAddCustomerModal()">
                    <i class="fas fa-user-plus"></i> Tambah Konsumen Baru
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL 2: TAMBAH CUSTOMER BARU -->
<div id="addCustomerModal" class="modal">
    <div class="modal-content">
        <form id="addCustomerForm">
            <div class="modal-header">
                <h2>Tambah Pelanggan Baru</h2>
                <button type="button" class="close-btn" onclick="closeAddCustomerModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Nama Lengkap <span style="color:red">*</span></label>
                    <input type="text" id="new_customer_nama" class="form-control" required placeholder="Nama sesuai KTP">
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
                    <textarea id="new_customer_keluhan" class="form-control" rows="2" required placeholder="Keluhan pelanggan..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary close-btn" onclick="closeAddCustomerModal()" style="background: #e5e5ea; color: #333;">Batal</button>
                <button type="submit" class="btn btn-primary" style="background: #007aff; color: white; border: none;">Simpan</button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<!-- Skrip JavaScript -->
<script>
const customersData = <?php echo json_encode($customers); ?>;
const searchModal = document.getElementById('searchCustomerModal');
const addModal = document.getElementById('addCustomerModal');
const customerListContainer = document.getElementById('customerListContainer');

function openSearchModal() {
    searchModal.style.display = 'flex';
    renderCustomerList(customersData);
    document.getElementById('search_customer_input').focus();
}

function closeSearchModal() {
    searchModal.style.display = 'none';
}

function openAddCustomerModal() {
    closeSearchModal(); 
    addModal.style.display = 'flex';
}

function closeAddCustomerModal() {
    addModal.style.display = 'none';
}

function renderCustomerList(data) {
    customerListContainer.innerHTML = '';
    if (data.length === 0) {
        customerListContainer.innerHTML = '<div style="padding:15px; text-align:center; color:#999;">Tidak ditemukan.</div>';
        return;
    }

    data.forEach(c => {
        const item = document.createElement('div');
        item.className = 'customer-item';
        item.innerHTML = `
            <div>
                <div class="cust-name">${c.nama}</div>
                <div class="cust-contact">${c.no_hp}</div>
            </div>
            <i class="fas fa-chevron-right" style="color:#ccc; font-size: 12px;"></i>
        `;
        item.onclick = () => selectCustomer(c);
        customerListContainer.appendChild(item);
    });
}

function filterCustomers() {
    const query = document.getElementById('search_customer_input').value.toLowerCase();
    const filtered = customersData.filter(c => 
        c.nama.toLowerCase().includes(query) || 
        c.no_hp.toLowerCase().includes(query)
    );
    renderCustomerList(filtered);
}

function selectCustomer(customer) {
    document.getElementById('customer_id').value = customer.id;
    document.getElementById('customer_display_name').value = customer.nama;
    closeSearchModal();
}

document.addEventListener('DOMContentLoaded', function() {
    window.addEventListener('click', (e) => {
        if (e.target === searchModal) closeSearchModal();
        if (e.target === addModal) closeAddCustomerModal();
    });
    
    document.getElementById('addCustomerForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const nama = document.getElementById('new_customer_nama').value;
        const kontak = document.getElementById('new_customer_kontak').value;
        const alamat = document.getElementById('new_customer_alamat').value;
        const keluhan = document.getElementById('new_customer_keluhan').value;

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
                const newCust = data.customer;
                customersData.push(newCust);
                selectCustomer(newCust);
                document.getElementById('kerusakan').value = keluhan;
                closeAddCustomerModal();
                document.getElementById('addCustomerForm').reset();
            } else {
                alert('Gagal: ' + data.message);
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