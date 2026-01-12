<?php
// employees.php
require_once 'includes/header.php';

$is_owner = (get_user_role() === 'owner');
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
    
    // Hanya izinkan format tertentu
    if(!in_array($file_ext, ['jpg', 'png', 'jpeg'])) return false;
    
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return $target_file;
    }
    return false;
}

// --- LOGIKA HAPUS (Hanya Owner) ---
if ($is_owner && isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    // Cegah hapus diri sendiri
    if ($id == $_SESSION['user_id']) {
        $message = "<div class='alert alert-danger'>Tidak dapat menghapus akun sendiri yang sedang login.</div>";
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>Karyawan berhasil dihapus.</div>";
        } else {
            $message = "<div class='alert alert-danger'>Gagal menghapus: " . $conn->error . "</div>";
        }
    }
}

// --- LOGIKA TAMBAH/EDIT (Hanya Owner) ---
if ($is_owner && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = $_POST['nama'];
    $username = $_POST['username'];
    $password = $_POST['password']; // Jika kosong saat edit, password tidak berubah
    $role = $_POST['role'];
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    // Generate QR Token jika baru
    $qr_token = md5(uniqid($username, true));
    
    // Handle Upload Foto
    $foto_path = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $foto_path = upload_foto($_FILES['foto']);
    }

    if ($id > 0) {
        // UPDATE
        $sql = "UPDATE users SET nama=?, username=?, role=?";
        $params = [$nama, $username, $role];
        $types = "sss";
        
        if (!empty($password)) {
            $sql .= ", password=?";
            $params[] = $password; // Password plain text (sesuai sistem lama), idealnya hash
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
            $message = "<div class='alert alert-success'>Data karyawan berhasil diperbarui.</div>";
        }
        
    } else {
        // INSERT
        // Pastikan password diisi
        if (empty($password)) {
            $message = "<div class='alert alert-danger'>Password wajib diisi untuk karyawan baru.</div>";
        } else {
            $sql = "INSERT INTO users (nama, username, password, role, foto, qr_token) VALUES (?, ?, ?, ?, ?, ?)";
            $foto_db = $foto_path ? $foto_path : '';
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssss", $nama, $username, $password, $role, $foto_db, $qr_token);
            
            if ($stmt->execute()) {
                $message = "<div class='alert alert-success'>Karyawan baru berhasil ditambahkan.</div>";
            } else {
                $message = "<div class='alert alert-danger'>Gagal menambah: " . $conn->error . "</div>";
            }
        }
    }
}

// --- AMBIL DATA KARYAWAN ---
$users = [];
$res = $conn->query("SELECT * FROM users ORDER BY role ASC, nama ASC");
while($row = $res->fetch_assoc()) {
    $users[] = $row;
}
?>

<style>
    .data-table-container { padding: 24px; }
    .table-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table th, .data-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #e9ecef; vertical-align: middle; }
    .data-table th { background: #f8f9fa; font-weight: 600; color: #6c757d; }
    
    .avatar-small { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    .role-badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
    .role-owner { background: #000; color: #fff; }
    .role-admin { background: var(--accent-primary); color: #fff; }
    .role-teknisi { background: #e9ecef; color: #333; }
    .role-karyawan { background: #e9ecef; color: #333; }
    
    /* Modal Styles */
    .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; backdrop-filter: blur(4px); }
    .modal-content { background: #fff; width: 90%; max-width: 500px; border-radius: 16px; padding: 0; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.2); animation: slideUp 0.3s ease; }
    @keyframes slideUp { from {transform: translateY(20px); opacity: 0;} to {transform: translateY(0); opacity: 1;} }
    .modal-header { padding: 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; background: #f8f9fa; }
    .modal-body { padding: 24px; }
    .form-group { margin-bottom: 16px; }
    .form-group label { display: block; margin-bottom: 6px; font-weight: 500; font-size: 13px; color: #555; }
    .form-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; }
    
    .qr-preview-container { text-align: center; padding: 20px; }
    .qr-preview-container h3 { font-size: 18px; margin-bottom: 5px; color: #333; }
    .qr-preview-container p { color: #888; font-size: 13px; margin-bottom: 20px; }
    #qrcode-display { display: flex; justify-content: center; margin: 20px auto; padding: 10px; background: white; border: 1px solid #eee; border-radius: 12px; width: fit-content; }
    .btn-download-id { display: inline-block; margin-top: 15px; padding: 10px 20px; background: #000; color: white; border-radius: 20px; font-weight: 500; font-size: 13px; transition: transform 0.2s; }
    .btn-download-id:hover { transform: scale(1.05); }
</style>

<!-- Library QRCode.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<h1 class="page-title">Data Karyawan</h1>
<?php echo $message; ?>

<div class="glass-effect data-table-container">
    <div class="table-header">
        <h3>Daftar Pengguna Sistem</h3>
        <?php if($is_owner): ?>
        <button class="btn btn-primary" onclick="openModal('employeeModal', 'add')"><i class="fas fa-plus"></i> Tambah Karyawan</button>
        <?php endif; ?>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th width="60">Foto</th>
                <th>Nama Lengkap</th>
                <th>Username</th>
                <th>Role</th>
                <th class="text-right">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($users as $u): ?>
            <tr>
                <td>
                    <?php if(!empty($u['foto']) && file_exists($u['foto'])): ?>
                        <img src="<?php echo htmlspecialchars($u['foto']); ?>" class="avatar-small">
                    <?php else: ?>
                        <div class="avatar-small" style="background:#eee; display:flex; align-items:center; justify-content:center; color:#ccc;">
                            <i class="fas fa-user"></i>
                        </div>
                    <?php endif; ?>
                </td>
                <td style="font-weight: 500;"><?php echo htmlspecialchars($u['nama']); ?></td>
                <td><?php echo htmlspecialchars($u['username']); ?></td>
                <td>
                    <span class="role-badge role-<?php echo strtolower($u['role']); ?>">
                        <?php echo ucfirst($u['role']); ?>
                    </span>
                </td>
                <td class="text-right">
                    <!-- Tombol Lihat (Semua Role) -->
                    <button class="btn btn-tertiary btn-sm" onclick="showQR('<?php echo $u['nama']; ?>', '<?php echo $u['qr_token']; ?>')" title="Lihat ID Card">
                        <i class="fas fa-eye"></i>
                    </button>

                    <!-- Tombol Edit & Hapus (Hanya Owner) -->
                    <?php if($is_owner): ?>
                        <button class="btn btn-tertiary btn-sm" onclick='editEmployee(<?php echo json_encode($u); ?>)' title="Edit">
                            <i class="fas fa-edit" style="color: var(--accent-warning);"></i>
                        </button>
                        <button class="btn btn-tertiary btn-sm" onclick="deleteEmployee(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['nama']); ?>')" title="Hapus">
                            <i class="fas fa-trash-alt" style="color: var(--accent-danger);"></i>
                        </button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- MODAL FORM (ADD/EDIT) - HANYA MUNCUL VIA JS -->
<div id="employeeModal" class="modal">
    <div class="modal-content">
        <form method="POST" enctype="multipart/form-data">
            <div class="modal-header">
                <h2 id="modalTitle">Tambah Karyawan</h2>
                <button type="button" class="close-btn" onclick="closeModal('employeeModal')">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id" id="emp_id">
                
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama" id="emp_nama" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" id="emp_username" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Password <span id="pass_hint" style="font-weight:normal; color:#888; font-size:11px;">(Isi jika ingin mengubah)</span></label>
                    <input type="password" name="password" class="form-control">
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role" id="emp_role" class="form-control">
                        <option value="teknisi">Teknisi</option>
                        <option value="admin">Admin</option>
                        <option value="owner">Owner</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Foto Profil</label>
                    <input type="file" name="foto" class="form-control" accept="image/*">
                </div>
            </div>
            <div class="modal-footer" style="padding: 20px; border-top: 1px solid #eee; text-align: right;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('employeeModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL QR VIEW -->
<div id="qrModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>ID Card Karyawan</h2>
            <button type="button" class="close-btn" onclick="closeModal('qrModal')">&times;</button>
        </div>
        <div class="modal-body qr-preview-container">
            <h3 id="qr_emp_name">Nama Karyawan</h3>
            <p>Scan QR ini untuk melihat Digital ID Card</p>
            
            <div id="qrcode-display"></div>
            
            <?php if($is_owner): ?>
            <a id="qr_link" href="#" target="_blank" class="btn-download-id">
                <i class="fas fa-external-link-alt"></i> Buka ID Card Digital
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<script>
    function openModal(id, mode) {
        document.getElementById(id).style.display = 'flex';
        if(id === 'employeeModal' && mode === 'add') {
            document.getElementById('modalTitle').innerText = 'Tambah Karyawan';
            document.getElementById('emp_id').value = '';
            document.getElementById('emp_nama').value = '';
            document.getElementById('emp_username').value = '';
            document.getElementById('emp_role').value = 'teknisi';
            document.getElementById('pass_hint').style.display = 'none';
        }
    }

    function closeModal(id) {
        document.getElementById(id).style.display = 'none';
    }

    function editEmployee(data) {
        openModal('employeeModal', 'edit');
        document.getElementById('modalTitle').innerText = 'Edit Data Karyawan';
        document.getElementById('emp_id').value = data.id;
        document.getElementById('emp_nama').value = data.nama;
        document.getElementById('emp_username').value = data.username;
        document.getElementById('emp_role').value = data.role;
        document.getElementById('pass_hint').style.display = 'inline';
    }

    function deleteEmployee(id, nama) {
        if(confirm('Yakin ingin menghapus karyawan "' + nama + '"?')) {
            window.location.href = 'employees.php?action=delete&id=' + id;
        }
    }

    function showQR(nama, token) {
        if(!token) {
            alert('User ini belum memiliki token QR. Silakan edit dan simpan ulang user ini.');
            return;
        }
        openModal('qrModal');
        document.getElementById('qr_emp_name').innerText = nama;
        
        // Link menuju halaman ID Card
        // Asumsi file ada di root dengan nama view_id_card.php
        const baseUrl = window.location.origin + window.location.pathname.replace('employees.php', '');
        const targetUrl = baseUrl + 'view_id_card.php?token=' + token;
        
        // Cek jika elemen ada (hanya ada jika owner) sebelum set href
        const btnLink = document.getElementById('qr_link');
        if(btnLink) {
            btnLink.href = targetUrl;
        }
        
        // Generate QR
        document.getElementById('qrcode-display').innerHTML = '';
        new QRCode(document.getElementById("qrcode-display"), {
            text: targetUrl,
            width: 180,
            height: 180,
            colorDark : "#000000",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.H
        });
    }

    // Close on click outside
    window.onclick = function(e) {
        if(e.target.classList.contains('modal')) e.target.style.display = 'none';
    }
</script>