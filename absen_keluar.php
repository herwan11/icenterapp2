<?php
// absen_keluar.php
require_once 'includes/header.php';
?>

<style>
    .scanner-wrapper {
        max-width: 500px;
        margin: 20px auto;
        padding: 20px;
    }
    .scanner-box {
        background: white;
        padding: 20px;
        border-radius: 20px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        text-align: center;
        overflow: hidden;
        border-top: 5px solid var(--accent-danger); /* Indikator Merah untuk Keluar */
    }
    #reader {
        width: 100%;
        background: #000;
        border-radius: 12px;
        margin-bottom: 20px;
        display: none; /* Sembunyikan awal */
    }
    #reader video {
        object-fit: cover;
        border-radius: 12px;
    }
    .status-msg {
        margin-top: 15px;
        padding: 15px;
        border-radius: 10px;
        display: none;
        font-weight: 500;
    }
    .success { background: #d4edda; color: #155724; }
    .error { background: #f8d7da; color: #721c24; }
    .loading { background: #e2e3e5; color: #383d41; }
    
    .btn-start-out {
        background: var(--accent-danger); /* Tombol Merah */
        color: white;
        border: none;
        padding: 15px 30px;
        border-radius: 50px;
        font-size: 16px;
        font-weight: bold;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        box-shadow: 0 4px 15px rgba(255, 59, 48, 0.3);
        transition: transform 0.2s;
    }
    .btn-start-out:active { transform: scale(0.95); }
    
    .btn-stop {
        background: #6c757d; color: white; padding: 10px 20px; border-radius: 8px; border:none; margin-top:10px; cursor:pointer;
    }
</style>

<!-- Library HTML5-QRCode -->
<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

<h1 class="page-title text-center" style="color: var(--accent-danger);">Scan Absen Pulang</h1>

<div class="scanner-wrapper">
    <!-- Peringatan HTTPS -->
    <div id="https-warning" style="display:none; background:#fff3cd; color:#856404; padding:15px; border-radius:8px; margin-bottom:20px; border:1px solid #ffeeba;">
        <strong>Perhatian:</strong> Akses kamera memerlukan koneksi aman (HTTPS) atau localhost.
    </div>

    <div class="scanner-box glass-effect">
        <div id="device-status" style="margin-bottom: 20px; font-size: 13px; color: #666;">
            <i class="fas fa-sync fa-spin"></i> Memuat info perangkat...
        </div>

        <!-- Tombol Mulai Scan -->
        <div id="start-screen">
            <div style="margin-bottom: 20px; font-size: 60px; color: var(--accent-danger);">
                <i class="fas fa-door-open"></i>
            </div>
            <button id="btn-start-cam" class="btn-start-out" onclick="startScanner()">
                <i class="fas fa-camera"></i> Scan Pulang
            </button>
            <p style="margin-top: 15px; font-size: 13px; color: #888;">
                Scan QR yang sama di kantor untuk check-out.
            </p>
        </div>

        <!-- Area Kamera -->
        <div id="reader"></div>
        <button id="btn-stop-cam" class="btn-stop" style="display:none;" onclick="stopScanner()">Batalkan</button>
        
        <!-- Pesan Hasil -->
        <div id="result-msg" class="status-msg"></div>
        
        <button id="btn-retry" class="btn-stop" style="display: none;" onclick="location.reload()">Muat Ulang</button>
    </div>
</div>

<script>
    // 1. Cek HTTPS
    if (location.protocol !== 'https:' && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
        document.getElementById('https-warning').style.display = 'block';
    }

    // 2. Identifikasi Device (Fingerprint) - Harus sama persis logikanya
    function generateFingerprint() {
        const components = [
            navigator.userAgent,
            navigator.language,
            new Date().getTimezoneOffset(),
            screen.width + 'x' + screen.height,
            navigator.platform
        ];
        const str = components.join('###');
        let hash = 0;
        for (let i = 0; i < str.length; i++) {
            const char = str.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash; 
        }
        return Math.abs(hash).toString(16);
    }

    const fingerprint = generateFingerprint();
    const userId = <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '0'; ?>;
    
    document.getElementById('device-status').innerHTML = `<i class="fas fa-mobile-alt"></i> Device ID: <strong>${fingerprint}</strong>`;

    // 3. Setup Scanner
    let html5QrCode;
    let isProcessing = false;

    function startScanner() {
        document.getElementById('start-screen').style.display = 'none';
        document.getElementById('reader').style.display = 'block';
        document.getElementById('btn-stop-cam').style.display = 'inline-block';
        document.getElementById('result-msg').style.display = 'none';

        html5QrCode = new Html5Qrcode("reader");
        const config = { fps: 10, qrbox: { width: 250, height: 250 } };
        
        html5QrCode.start({ facingMode: "environment" }, config, onScanSuccess, onScanFailure)
        .catch(err => {
            console.error("Gagal start kamera", err);
            showError("Gagal akses kamera: " + err);
            stopScanner();
        });
    }

    function stopScanner() {
        if (html5QrCode) {
            html5QrCode.stop().then(() => {
                html5QrCode.clear();
                resetUI();
            }).catch(err => {
                console.error("Gagal stop", err);
                resetUI();
            });
        } else {
            resetUI();
        }
    }

    function resetUI() {
        document.getElementById('start-screen').style.display = 'block';
        document.getElementById('reader').style.display = 'none';
        document.getElementById('btn-stop-cam').style.display = 'none';
    }

    // 4. Handle Scan Result
    function onScanSuccess(decodedText, decodedResult) {
        if (isProcessing) return;
        isProcessing = true;

        html5QrCode.pause(); // Pause kamera

        const msgBox = document.getElementById('result-msg');
        msgBox.style.display = 'block';
        msgBox.className = 'status-msg loading';
        msgBox.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memvalidasi Checkout...';
        
        // Kirim ke server (Endpoint Berbeda: process_absen_keluar.php)
        fetch('process_absen_keluar.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                user_id: userId,
                token: decodedText,
                fingerprint: fingerprint
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // SUKSES
                msgBox.className = 'status-msg success';
                msgBox.innerHTML = `<i class="fas fa-check-circle" style="font-size:24px; display:block; margin-bottom:10px;"></i> <strong>BERHASIL PULANG!</strong><br>${data.message}`;
                
                html5QrCode.stop().then(() => {
                    html5QrCode.clear();
                    document.getElementById('reader').style.display = 'none';
                    document.getElementById('btn-stop-cam').style.display = 'none';
                    
                    // Redirect ke home
                    setTimeout(() => window.location.href = 'index.php', 3000);
                });
            } else {
                // GAGAL
                showError(data.message);
                setTimeout(() => {
                    msgBox.style.display = 'none';
                    isProcessing = false;
                    html5QrCode.resume();
                }, 3000);
            }
        })
        .catch(err => {
            console.error(err);
            showError("Terjadi kesalahan jaringan.");
            isProcessing = false;
            document.getElementById('btn-retry').style.display = 'inline-block';
        });
    }

    function onScanFailure(error) {
        // Abaikan
    }

    function showError(msg) {
        const msgBox = document.getElementById('result-msg');
        msgBox.style.display = 'block';
        msgBox.className = 'status-msg error';
        msgBox.innerHTML = `<i class="fas fa-times-circle"></i> <strong>GAGAL!</strong><br>${msg}`;
    }
</script>

<?php require_once 'includes/footer.php'; ?>