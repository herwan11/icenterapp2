<?php
// device_binding.php
require_once 'includes/header.php';

// Cek hak akses: HANYA OWNER
if (get_user_role() !== 'owner') {
    echo "<script>alert('Akses ditolak. Halaman ini hanya untuk Owner.'); window.location.href='index.php';</script>";
    exit();
}

$message = '';

// Handle Binding Process
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'];
    $fingerprint = $_POST['device_fingerprint'];
    $device_info = $_POST['device_info'];

    if ($user_id && $fingerprint) {
        // Cek apakah user ini sudah punya device terdaftar?
        $check = $conn->query("SELECT id FROM device_bindings WHERE user_id = '$user_id'");
        if ($check->num_rows > 0) {
            // Update device lama
            $stmt = $conn->prepare("UPDATE device_bindings SET device_fingerprint = ?, device_info = ?, created_at = NOW() WHERE user_id = ?");
            $stmt->bind_param("ssi", $fingerprint, $device_info, $user_id);
        } else {
            // Insert device baru
            $stmt = $conn->prepare("INSERT INTO device_bindings (user_id, device_fingerprint, device_info) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $user_id, $fingerprint, $device_info);
        }

        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>Device berhasil didaftarkan untuk user tersebut!</div>";
        } else {
            $message = "<div class='alert alert-danger'>Gagal mendaftarkan device: " . $conn->error . "</div>";
        }
    } else {
        $message = "<div class='alert alert-danger'>Data tidak lengkap.</div>";
    }
}

// Ambil daftar user
$users = [];
$res = $conn->query("SELECT id, nama, role FROM users ORDER BY nama ASC");
while($row = $res->fetch_assoc()) {
    $users[] = $row;
}
?>

<h1 class="page-title">Device Binding (Pendaftaran HP Absen)</h1>

<?php echo $message; ?>

<div class="card glass-effect">
    <div class="card-body">
        <div style="background-color: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <i class="fas fa-exclamation-triangle"></i> <strong>PENTING:</strong><br>
            Buka halaman ini <u>MENGGUNAKAN HP KARYAWAN</u> yang ingin didaftarkan.<br>
            Sistem akan membaca identitas perangkat (fingerprint) saat ini dan menguncinya ke akun yang Anda pilih di bawah.
        </div>

        <form method="POST" id="bindingForm">
            <input type="hidden" name="device_fingerprint" id="device_fingerprint">
            <input type="hidden" name="device_info" id="device_info">

            <div class="form-group">
                <label>Pilih Karyawan yang Memiliki HP Ini:</label>
                <select name="user_id" class="form-control" required style="max-width: 400px;">
                    <option value="">-- Pilih Karyawan --</option>
                    <?php foreach($users as $u): ?>
                        <option value="<?php echo $u['id']; ?>"><?php echo $u['nama'] . " (" . ucfirst($u['role']) . ")"; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="device-preview" style="margin: 20px 0; font-size: 13px; color: #555;">
                Sedang mendeteksi perangkat...
            </div>

            <button type="submit" class="btn btn-primary" id="btnBind" disabled>
                <i class="fas fa-link"></i> Kunci Akun ke HP Ini
            </button>
        </form>
    </div>
</div>

<script>
    // Simple Browser Fingerprinting Script
    // Menggabungkan UserAgent, Screen Res, Timezone, Platform, Language
    function generateFingerprint() {
        const components = [
            navigator.userAgent,
            navigator.language,
            new Date().getTimezoneOffset(),
            screen.width + 'x' + screen.height,
            navigator.platform
        ];
        // Create simple hash
        const str = components.join('###');
        let hash = 0;
        for (let i = 0; i < str.length; i++) {
            const char = str.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash; // Convert to 32bit integer
        }
        return Math.abs(hash).toString(16);
    }

    document.addEventListener('DOMContentLoaded', function() {
        const fingerprint = generateFingerprint();
        const deviceInfo = navigator.userAgent + " (" + navigator.platform + ")";

        document.getElementById('device_fingerprint').value = fingerprint;
        document.getElementById('device_info').value = deviceInfo;

        document.getElementById('device-preview').innerHTML = `
            <strong>Info Perangkat Terdeteksi:</strong><br>
            Fingerprint ID: <code>${fingerprint}</code><br>
            Platform: ${navigator.platform}<br>
            Browser: ${navigator.userAgent}
        `;

        document.getElementById('btnBind').disabled = false;
    });
</script>

<?php require_once 'includes/footer.php'; ?>