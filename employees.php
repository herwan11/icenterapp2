<?php
// employees.php
require_once 'includes/header.php';

// Memastikan hanya Owner yang bisa melakukan modifikasi data
// Menggunakan strtolower untuk menghindari masalah case-sensitivity dari database
$is_owner = (strtolower(get_user_role()) === 'owner');
$message = '';

// --- FUNGSI UPLOAD FOTO ---
function upload_foto($file) {
    $target_dir = "assets/uploads/employees/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $new_name = uniqid('EMP_') . '.' . $file_ext;
    $target_file = $target_dir . $new_name;
    
    $check = getimagesize($file["tmp_name"]);
    if($check === false) return false;
    
    // Validasi format file
    if(!in_array($file_ext, ['jpg', 'png', 'jpeg'])) return false;
    
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return $target_file;
    }
    return false;
}

// --- LOGIKA HAPUS KARYAWAN (Hanya Owner) ---
if ($is_owner && isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    // Keamanan: Cegah hapus akun sendiri
    if ($id == $_SESSION['user_id']) {
        $message = "<div class='alert alert-danger'>Kesalahan: Anda tidak dapat menghapus akun Anda sendiri saat sedang login.</div>";
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>Data karyawan berhasil dihapus dari sistem.</div>";
        } else {
            $message = "<div class='alert alert-danger'>Gagal menghapus: " . $conn->error . "</div>";
        }
    }
}

// --- LOGIKA SIMPAN DATA (TAMBAH/EDIT) ---
if ($is_owner && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = $_POST['nama'];
    $username = $_POST['username'];
    $password = $_POST['password']; 
    $role = $_POST['role'];
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    // Handle File Foto Profil
    $foto_path = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $foto_path = upload_foto($_FILES['foto']);
    }

    if ($id > 0) {
        // --- PROSES UPDATE ---
        $sql = "UPDATE users SET nama=?, username=?, role=?";
        $params = [$nama, $username, $role];
        $types = "sss";
        
        if (!empty($password)) {
            $sql .= ", password=?";
            $params[] = $password;
            $types .= "s";
        }
        if ($foto_path) {
            $sql .= ", foto=?";
            $params[] = $foto_path;
            $types .= "s";
        }
        
        $sql .= " WHERE id=?";
        $params[] = $id;
        $types .= "i";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>Data '" . htmlspecialchars($nama) . "' berhasil diperbarui.</div>";
        }
        
    } else {
        // --- PROSES INSERT (Karyawan Baru) ---
        if (empty($password)) {
            $message = "<div class='alert alert-danger'>Gagal: Password wajib diisi untuk pembuatan akun karyawan baru.</div>";
        } else {
            // Generate QR Token unik untuk sistem ID Card Digital
            $qr_token = md5(uniqid($username, true));
            $sql = "INSERT INTO users (nama, username, password, role, foto, qr_token) VALUES (?, ?, ?, ?, ?, ?)";
            $foto_db = $foto_path ? $foto_path : '';
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssss", $nama, $username, $password, $role, $foto_db, $qr_token);
            
            if ($stmt->execute()) {
                $message = "<div class='alert alert-success'>Karyawan baru '" . htmlspecialchars($nama) . "' berhasil ditambahkan ke database.</div>";
            } else {
                $message = "<div class='alert alert-danger'>Gagal menambahkan karyawan: " . $conn->error . "</div>";
            }
        }
    }
}

// --- PENGAMBILAN DATA KARYAWAN DARI DATABASE ---
$users = [];
$res = $conn->query("SELECT * FROM users ORDER BY role ASC, nama ASC");
while($row = $res->fetch_assoc()) {
    $users[] = $row;
}
?>

<!-- CSS Internal untuk Halaman Karyawan -->
<style>
    .data-table-container { padding: 24px; margin-top: 20px; }
    .table-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table th, .data-table td { padding: 14px 15px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.1); vertical-align: middle; }
    .data-table th { font-weight: 600; color: #aaa; text-transform: uppercase; font-size: 12px; letter-spacing: 1px; }
    
    .avatar-small { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(255,255,255,0.2); box-shadow: 0 4px 10px rgba(0,0,0,0.3); }
    .role-badge { padding: 5px 12px; border-radius: 20px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
    
    /* Warna Badge Role Sesuai Struktur DB */
    .role-owner { background: #ff4757; color: #fff; }
    .role-admin { background: #2f3542; color: #fff; }
    .role-teknisi { background: #1e90ff; color: #fff; }
    .role-manager { background: #ffa502; color: #fff; }
    .role-markom { background: #2ed573; color: #fff; }
    .role-front-desk { background: #747d8c; color: #fff; }
    
    /* Desain Modal Modern */
    .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); align-items: center; justify-content: center; backdrop-filter: blur(8px); }
    .modal-content { background: #1e1e1e; border: 1px solid rgba(255,255,255,0.1); width: 95%; max-width: 500px; border-radius: 20px; padding: 0; overflow: hidden; box-shadow: 0 25px 50px rgba(0,0,0,0.5); animation: zoomIn 0.3s ease; color: #fff; }
    @keyframes zoomIn { from {transform: scale(0.9); opacity: 0;} to {transform: scale(1); opacity: 1;} }
    
    .modal-header { padding: 20px 25px; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center; }
    .modal-body { padding: 25px; }
    .modal-footer { padding: 20px 25px; border-top: 1px solid rgba(255,255,255,0.1); text-align: right; }
    
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 500; font-size: 13px; color: #bbb; }
    .form-control { width: 100%; padding: 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; color: #fff; transition: all 0.3s; }
    .form-control:focus { outline: none; border-color: var(--accent-primary); background: rgba(255,255,255,0.1); }
    
    /* Perbaikan warna teks drop-down/select item menjadi hitam */
    .form-control option { color: #000; background: #fff; }
    
    .close-modal { cursor: pointer; font-size: 24px; color: #888; transition: color 0.3s; }
    .close-modal:hover { color: #fff; }
</style>

<!-- Library Pembuat QR Code -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<div class="content-header">
    <h1 class="page-title">Manajemen Karyawan</h1>
    <p class="page-subtitle">Kelola akses dan data tim iCenter Apple</p>
</div>

<?php echo $message; ?>

<div class="glass-effect data-table-container">
    <div class="table-header">
        <h3>Database User Sistem</h3>
        <?php if($is_owner): ?>
        <button class="btn btn-primary" onclick="openEmployeeModal('add')">
            <i class="fas fa-plus-circle"></i> Tambah Karyawan Baru
        </button>
        <?php endif; ?>
    </div>

    <div style="overflow-x: auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th width="80">Profil</th>
                    <th>Nama Lengkap</th>
                    <th>Username</th>
                    <th>Hak Akses</th>
                    <th class="text-right">Tindakan</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($users as $u): ?>
                <tr>
                    <td>
                        <?php if(!empty($u['foto']) && file_exists($u['foto'])): ?>
                            <img src="<?php echo htmlspecialchars($u['foto']); ?>" class="avatar-small">
                        <?php else: ?>
                            <div class="avatar-small" style="background: rgba(255,255,255,0.1); display:flex; align-items:center; justify-content:center; color:#555;">
                                <i class="fas fa-user-tie fa-lg"></i>
                            </div>
                        <?php endif; ?>
                    </td>
                    <!-- Warna teks nama tetap hitam (#000) sesuai permintaan sebelumnya -->
                    <td style="font-weight: 600; color: #000;"><?php echo htmlspecialchars($u['nama']); ?></td>
                    <td><code style="color: var(--accent-primary);"><?php echo htmlspecialchars($u['username']); ?></code></td>
                    <td>
                        <span class="role-badge role-<?php echo strtolower(str_replace(' ', '-', $u['role'])); ?>">
                            <?php echo ucfirst($u['role']); ?>
                        </span>
                    </td>
                    <td class="text-right">
                        <!-- Fitur Lihat ID Card (Tersedia untuk semua level login) -->
                        <button class="btn btn-tertiary btn-sm" onclick="showQRCard('<?php echo addslashes($u['nama']); ?>', '<?php echo $u['qr_token']; ?>')" title="Digital ID Card">
                            <i class="fas fa-id-card"></i>
                        </button>

                        <?php if($is_owner): ?>
                            <!-- Fitur Edit & Hapus (Khusus Owner) -->
                            <button class="btn btn-tertiary btn-sm" onclick='editEmployeeData(<?php echo json_encode($u); ?>)' title="Ubah Data">
                                <i class="fas fa-pen-nib" style="color: #ffa502;"></i>
                            </button>
                            <button class="btn btn-tertiary btn-sm" onclick="confirmDelete(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['nama']); ?>')" title="Hapus Akun">
                                <i class="fas fa-user-minus" style="color: #ff4757;"></i>
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL POP-UP: FORM TAMBAH/EDIT KARYAWAN -->
<div id="employeeModal" class="modal">
    <div class="modal-content">
        <form method="POST" enctype="multipart/form-data">
            <div class="modal-header">
                <h2 id="modalTitle">Tambah Karyawan</h2>
                <span class="close-modal" onclick="closeModal('employeeModal')">&times;</span>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id" id="form_id">
                
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama" id="form_nama" class="form-control" placeholder="Contoh: Heriawan Kadir" required>
                </div>
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" id="form_username" class="form-control" placeholder="Digunakan untuk login" required>
                </div>
                <div class="form-group">
                    <label>Kata Sandi <span id="pwd_hint" style="display:none; color:#f0932b; font-size:11px;">(Biarkan kosong jika tidak ingin diubah)</span></label>
                    <input type="password" name="password" id="form_password" class="form-control" placeholder="Minimal 6 karakter">
                </div>
                <div class="form-group">
                    <label>Jabatan / Role</label>
                    <select name="role" id="form_role" class="form-control" required>
                        <option value="Teknisi">Teknisi</option>
                        <option value="Admin">Admin</option>
                        <option value="Front Desk">Front Desk</option>
                        <option value="Markom">Markom</option>
                        <option value="Manager">Manager</option>
                        <option value="Owner">Owner</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Foto Profil (Opsional)</label>
                    <input type="file" name="foto" class="form-control" accept="image/jpeg,image/png">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-tertiary" onclick="closeModal('employeeModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL POP-UP: QR CODE ID CARD -->
<div id="qrCardModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>ID Card Digital</h2>
            <span class="close-modal" onclick="closeModal('qrCardModal')">&times;</span>
        </div>
        <div class="modal-body" style="text-align: center; padding: 30px;">
            <h3 id="display_emp_name" style="margin-bottom: 5px;">Nama Karyawan</h3>
            <p style="color: #888; font-size: 13px; margin-bottom: 25px;">Scan QR ini melalui aplikasi iCenter untuk verifikasi absen</p>
            
            <div id="qrcode-box" style="display: inline-block; padding: 15px; background: #fff; border-radius: 15px; margin-bottom: 25px;"></div>
            
            <br>
            <a id="id_card_link" href="#" target="_blank" class="btn btn-primary btn-sm">
                <i class="fas fa-external-link-alt"></i> Tampilkan ID Card Full
            </a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<!-- Script untuk Interaksi Modal dan UI -->
<script>
    function openEmployeeModal(mode) {
        const modal = document.getElementById('employeeModal');
        modal.style.display = 'flex';
        
        if(mode === 'add') {
            document.getElementById('modalTitle').innerText = 'Tambah Karyawan Baru';
            document.getElementById('form_id').value = '';
            document.getElementById('form_nama').value = '';
            document.getElementById('form_username').value = '';
            document.getElementById('form_role').value = 'Teknisi';
            document.getElementById('form_password').required = true;
            document.getElementById('pwd_hint').style.display = 'none';
        }
    }

    function editEmployeeData(data) {
        openEmployeeModal('edit');
        document.getElementById('modalTitle').innerText = 'Ubah Data Karyawan';
        document.getElementById('form_id').value = data.id;
        document.getElementById('form_nama').value = data.nama;
        document.getElementById('form_username').value = data.username;
        document.getElementById('form_role').value = data.role;
        document.getElementById('form_password').required = false;
        document.getElementById('pwd_hint').style.display = 'block';
    }

    function closeModal(id) {
        document.getElementById(id).style.display = 'none';
    }

    function confirmDelete(id, nama) {
        if(confirm('Hapus data karyawan "' + nama + '"? Tindakan ini tidak dapat dibatalkan.')) {
            window.location.href = 'employees.php?action=delete&id=' + id;
        }
    }

    function showQRCard(nama, token) {
        if(!token || token === '') {
            alert('Token QR belum dibuat. Silakan edit dan simpan kembali user ini untuk mengaktifkan ID Card.');
            return;
        }
        
        document.getElementById('qrCardModal').style.display = 'flex';
        document.getElementById('display_emp_name').innerText = nama;
        
        const baseUrl = window.location.origin + window.location.pathname.replace('employees.php', '');
        const targetUrl = baseUrl + 'view_id_card.php?token=' + token;
        
        document.getElementById('id_card_link').href = targetUrl;
        
        const qrContainer = document.getElementById('qrcode-box');
        qrContainer.innerHTML = '';
        new QRCode(qrContainer, {
            text: targetUrl,
            width: 200,
            height: 200,
            colorDark : "#000000",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.H
        });
    }

    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    }
</script>