<?php
// generate_qr.php

// === BAGIAN API (JSON) ===
if (isset($_GET['action']) && $_GET['action'] == 'get_token') {
    // Bersihkan buffer agar tidak ada HTML yang ikut
    while (ob_get_level()) ob_end_clean(); 
    header('Content-Type: application/json');
    
    // Include DB secara manual untuk API
    require_once 'includes/db.php'; 
    
    try {
        // Cek koneksi DB
        if ($conn->connect_error) {
            throw new Exception("Koneksi DB Gagal: " . $conn->connect_error);
        }

        $token = bin2hex(random_bytes(16));
        $now = time();
        $exp = $now + 35; // Valid 35 detik
        
        // Persiapan Query dengan pengecekan error prepare
        $query = "INSERT INTO qr_tokens (token, created_at, expires_at) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($query);
        
        if (!$stmt) {
            // Ini biasanya terjadi jika tabel qr_tokens BELUM DIBUAT atau salah nama tabel
            throw new Exception("Gagal prepare query: " . $conn->error);
        }

        $stmt->bind_param("sii", $token, $now, $exp);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'token' => $token]);
        } else {
            throw new Exception("Gagal simpan token: " . $stmt->error);
        }
    } catch (Exception $e) {
        // Tangkap semua error dan kirim sebagai JSON
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit(); // Stop eksekusi agar HTML di bawah tidak terkirim
}

// === BAGIAN TAMPILAN (HTML) ===
require_once 'includes/header.php';

// Hanya Admin/Owner
if (get_user_role() !== 'owner' && get_user_role() !== 'admin') {
    echo "<script>window.location.href='index.php';</script>";
    exit();
}
?>

<style>
    .qr-page-wrapper {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 80vh;
        padding: 20px;
    }
    .qr-card {
        background: white;
        padding: 40px;
        border-radius: 24px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.15);
        text-align: center;
        width: 100%;
        max-width: 500px;
        position: relative;
        overflow: hidden;
    }
    .qr-box {
        margin: 30px auto;
        padding: 10px;
        background: white;
        border: 2px solid #f0f0f0;
        border-radius: 16px;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 270px; /* Menjaga tinggi agar tidak berkedip */
    }
    .timer-bar {
        width: 100%;
        height: 8px;
        background: #f0f0f0;
        border-radius: 4px;
        overflow: hidden;
        margin-top: 30px;
    }
    .timer-progress {
        height: 100%;
        background: linear-gradient(90deg, #007aff, #00c6ff);
        width: 100%;
        border-radius: 4px;
        transition: width 1s linear;
    }
    .clock-display {
        font-size: 32px;
        font-weight: 800;
        color: #1c1c1e;
        margin-bottom: 5px;
        font-family: monospace;
    }
    .status-badge {
        display: inline-block;
        padding: 8px 16px;
        background: #eefcf1;
        color: #27a844;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
        margin-top: 15px;
        min-width: 200px;
    }
    .error-text { color: #ff3b30; background: #fff5f5; }
</style>

<!-- Gunakan library QR Code yang lebih ringan dan kompatibel -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<h1 class="page-title text-center">Monitor Absensi Karyawan</h1>

<div class="qr-page-wrapper">
    <div class="qr-card">
        <div class="clock-display" id="clock">00:00:00</div>
        <div class="date-display" id="date" style="color:#888; margin-bottom:20px;">Memuat tanggal...</div>
        
        <h2 style="font-size: 20px; color: #333; margin-bottom: 5px;">Scan untuk Masuk</h2>
        <p style="color: #666; font-size: 14px;">Gunakan HP yang terdaftar</p>
        
        <div class="qr-box">
            <div id="qrcode"></div>
        </div>
        
        <div class="timer-bar">
            <div class="timer-progress" id="progressBar"></div>
        </div>
        
        <div class="status-badge" id="status-text">
            <i class="fas fa-sync fa-spin"></i> Menghubungkan...
        </div>
    </div>
</div>

<script>
    let qrContainer = document.getElementById("qrcode");
    let timerInterval = null;
    let isFetching = false;

    function updateTime() {
        const now = new Date();
        document.getElementById('clock').innerText = now.toLocaleTimeString('id-ID', { hour12: false });
        document.getElementById('date').innerText = now.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
    }

    function generateQR() {
        if (isFetching) return;
        isFetching = true;

        const statusText = document.getElementById('status-text');
        const progressBar = document.getElementById('progressBar');
        
        // Reset Progress Bar Visual
        progressBar.style.transition = 'none';
        progressBar.style.width = '100%';

        fetch('generate_qr.php?action=get_token')
            .then(response => {
                if (!response.ok) {
                    throw new Error("HTTP error " + response.status); 
                }
                return response.text(); 
            }) 
            .then(text => {
                // Coba parse JSON, jika gagal tangkap errornya
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error("Respon bukan JSON:", text);
                    throw new Error("Respon server tidak valid (bukan JSON). Cek console log.");
                }
            })
            .then(data => {
                if (data.success) {
                    // Bersihkan QR lama
                    qrContainer.innerHTML = '';
                    
                    // Generate QR Baru
                    new QRCode(qrContainer, {
                        text: data.token,
                        width: 250,
                        height: 250,
                        colorDark : "#000000",
                        colorLight : "#ffffff",
                        correctLevel : QRCode.CorrectLevel.H
                    });

                    // Update Status UI
                    statusText.innerHTML = '<i class="fas fa-check-circle"></i> Aktif. Scan sekarang.';
                    statusText.className = 'status-badge';
                    
                    // Mulai Animasi Progress Bar (30 detik)
                    // Sedikit delay agar transisi CSS reset terbaca browser
                    setTimeout(() => {
                        progressBar.style.transition = 'width 30s linear';
                        progressBar.style.width = '0%';
                    }, 50);

                } else {
                    throw new Error(data.message);
                }
            })
            .catch(err => {
                console.error("QR Error:", err);
                statusText.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ' + err.message;
                statusText.className = 'status-badge error-text';
                
                // Coba lagi lebih cepat jika error (5 detik)
                setTimeout(generateQR, 5000); 
                return; // Keluar agar tidak set timeout 30s di bawah
            })
            .finally(() => {
                isFetching = false;
            });
            
        // Jadwalkan refresh berikutnya dalam 30 detik (jika sukses)
        if(timerInterval) clearTimeout(timerInterval);
        timerInterval = setTimeout(generateQR, 30000);
    }

    document.addEventListener('DOMContentLoaded', function() {
        updateTime();
        setInterval(updateTime, 1000);
        generateQR(); // Start loop
    });
</script>

<?php require_once 'includes/footer.php'; ?>