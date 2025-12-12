<?php
// login.php

// Mulai sesi PHP
session_start();

// Jika pengguna sudah login, arahkan ke halaman utama
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Sertakan file koneksi database
require_once 'includes/db.php';

$error_message = '';

// Proses form jika ada data yang dikirim (metode POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error_message = "Username dan password tidak boleh kosong.";
    } else {
        // Ambil data user dari database
        // Kita ambil id, nama, username, password, role, dan foto untuk sesi
        $stmt = $conn->prepare("SELECT id, nama, username, password, role, foto FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Verifikasi password
            // 1. Cek dengan password_verify (untuk password yang sudah di-hash)
            // 2. Jika gagal, cek sebagai plaintext (untuk kompatibilitas data lama/legacy)
            $password_valid = false;

            if (password_verify($password, $user['password'])) {
                $password_valid = true;
            } elseif ($password === $user['password']) {
                // Fallback untuk password lama yang belum di-hash
                $password_valid = true;
                
                // Opsional: Otomatis update ke hash agar lebih aman ke depannya
                // $new_hash = password_hash($password, PASSWORD_DEFAULT);
                // $conn->query("UPDATE users SET password = '$new_hash' WHERE id = " . $user['id']);
            }

            if ($password_valid) {
                // Login berhasil, simpan data ke sesi
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                // Simpan path foto ke sesi jika perlu, atau gunakan default
                $_SESSION['foto'] = !empty($user['foto']) ? $user['foto'] : null;

                // Arahkan ke halaman utama
                header("Location: index.php");
                exit();
            } else {
                $error_message = "Username atau password salah.";
            }
        } else {
            $error_message = "Username atau password salah.";
        }
        $stmt->close();
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - iCenter Apple Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* CSS untuk halaman login, terpisah dari template utama */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-container {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
            box-sizing: border-box;
        }
        .login-container h1 {
            color: #1d2129;
            margin-bottom: 10px;
            font-size: 24px;
        }
        .login-container p {
            color: #606770;
            margin-bottom: 30px;
            font-size: 16px;
        }
        .login-form .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        .login-form label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #4b4f56;
        }
        .login-form input {
            width: 100%;
            padding: 12px;
            border: 1px solid #dddfe2;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 16px;
        }
        .login-form input:focus {
            outline: none;
            border-color: #1877f2;
            box-shadow: 0 0 0 2px rgba(24, 119, 242, 0.2);
        }
        .login-button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(90deg, #007aff, #00c6ff);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 122, 255, 0.3);
        }
        .error-message {
            background-color: #ffebe8;
            color: #c92a2a;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>iCenter Apple</h1>
        <p>Silakan login untuk melanjutkan</p>

        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form action="login.php" method="post" class="login-form">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="login-button">Login</button>
        </form>
    </div>
</body>
</html>