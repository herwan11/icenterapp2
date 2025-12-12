<?php
// save_employee.php
require_once 'includes/db.php';
session_start();

header('Content-Type: application/json');

// Cek hak akses Owner
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'owner') {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
    exit;
}

$id = $_POST['id'] ?? '';
$nama = $_POST['nama'] ?? '';
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? 'karyawan';
$old_foto = $_POST['old_foto'] ?? '';

// Validasi dasar
if (empty($nama) || empty($username)) {
    echo json_encode(['success' => false, 'message' => 'Nama dan Username wajib diisi.']);
    exit;
}

// Handle Upload Foto
$foto_name = $old_foto;
if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
    $target_dir = "assets/uploads/employees/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (in_array($file_ext, $allowed_ext)) {
        // Nama file unik: timestamp_username.ext
        $new_filename = time() . '_' . preg_replace('/[^a-zA-Z0-9]/', '', $username) . '.' . $file_ext;
        $target_file = $target_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['foto']['tmp_name'], $target_file)) {
            $foto_name = $new_filename;
            // Hapus foto lama jika ada dan bukan default
            if (!empty($old_foto) && file_exists($target_dir . $old_foto)) {
                unlink($target_dir . $old_foto);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal upload foto.']);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Format file tidak didukung.']);
        exit;
    }
}

try {
    if (!empty($id)) {
        // --- UPDATE ---
        $query = "UPDATE users SET nama=?, username=?, role=?, foto=? WHERE id=?";
        $params = [$nama, $username, $role, $foto_name, $id];
        $types = "ssssi";
        
        // Update password hanya jika diisi
        if (!empty($password)) {
            $query = "UPDATE users SET nama=?, username=?, password=?, role=?, foto=? WHERE id=?";
            $params = [$nama, $username, $password, $role, $foto_name, $id]; // Note: Password plaintext sesuai sistem lama (ideally hash)
            $types = "sssssi";
        }
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        
    } else {
        // --- INSERT ---
        if (empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Password wajib untuk user baru.']);
            exit;
        }
        
        // Cek username unik
        $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Username sudah digunakan.']);
            exit;
        }
        
        $stmt = $conn->prepare("INSERT INTO users (nama, username, password, role, foto) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $nama, $username, $password, $role, $foto_name);
    }
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception($stmt->error);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
}
?>