<?php
// absensi.php
require_once 'includes/header.php';
?>

<style>
    .scanner-wrapper {
        max-width: 600px;
        margin: 20px auto;
        padding: 20px;
    }
    .scanner-box {
        background: white;
        padding: 10px;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        text-align: center;
        overflow: hidden;
        position: relative;
    }
    #reader {
        width: 100%;
        min-height: 300px;
        background: #000;
        border-radius: 10px;
        margin-bottom: 20px;
        display: none; /* Sembunyikan awal */
    }
    /* Sembunyikan elemen bawaan library yang mengganggu */
    #reader__dashboard_section_csr span, 
    #reader__dashboard_section_swaplink {
        display: none !important;
    }
    .status-msg {
        margin-top: 20px;
        font-weight: bold;
        padding: 15px;
        border-radius: 12px;
        display: none;
        font-size: 14px;
        line-height: 1.5;
    }
    .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    .loading { background: #e2e3e5; color: #383d41; border: 1px solid #d6d8db; }
    
    .btn-action {
        margin-top: 15px;
        padding: 12px 24px;
        background: var(--accent-primary);
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        font-size: 16px;
    }
    
    .btn-stop {
        background: #ff3b30;
    }
</style>

<!-- Load Library HTML5-QRCode via CDN yang stabil -->
<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

<h1 class="page-title text-center">Scan Absensi</h1>

<div class="scanner-wrapper">
    <!-- Peringatan HTTPS -->
    <div id="https-warning" style="display:none; background:#fff3cd; color:#856404; padding:15px; border-radius:8px; margin-bottom:20px; border:1px solid #ffeeba;">
        <strong>Perhatian:</strong> Akses kamera memerlukan koneksi aman (HTTPS) atau localhost. Jika kamera tidak terbuka, pastikan Anda menggunakan HTTPS.
    </div>

    <div class="scanner-box glass-effect">
        <div id="device-status" style="margin-bottom: 15px; font-size: 13px; color: #666; border-bottom: 1px solid #eee; padding-bottom: 10px;">
            <i class="fas fa-sync fa-spin"></i> Memuat info perangkat...
        </div>

        <!-- Tombol Mulai Scan -->
        <div id="start-screen">
            <div style="margin-bottom: 20px; font-size: 60px; color: #ddd;">
                <i class="fas fa-qrcode"></i>
            </div>
            <button id="btn-start-cam" class="btn-action" onclick="startScanner()">
                <i class="fas fa-camera"></i> Mulai Scan
            </button>
            <p style="margin-top: 15px; font-size: 13px; color: #888;">
                Izinkan akses kamera saat diminta browser.
            </p>
        </div>

        <!-- Area Kamera -->
        <div id="reader"></div>
        
        <!-- Tombol Stop -->
        <button id="btn-stop-cam" class="btn-action btn-stop" style="display:none;" onclick="stopScanner()">
            Batalkan Scan
        </button>
        
        <!-- Pesan Status -->
        <div id="result-msg" class="status-msg"></div>
        
        <button id="btn-retry" class="btn-action" style="background:#6c757d; display: none;" onclick="location.reload()">Muat Ulang Halaman</button>
    </div>
</div>

<script>
    // 1. Cek HTTPS
    if (location.protocol !== 'https:' && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
        document.getElementById('https-warning').style.display = 'block';
    }

    // 2. Fingerprint Generator
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
    // Pastikan user_id tersedia dari session PHP
    const userId = <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '0'; ?>;
    
    document.getElementById('device-status').innerHTML = `<i class="fas fa-mobile-alt"></i> Device ID Anda: <strong>${fingerprint}</strong>`;

    let html5QrCode = null;
    let isProcessing = false;

    // 3. Fungsi Start Scanner (Manual Trigger)
    function startScanner() {
        // UI Update
        document.getElementById('start-screen').style.display = 'none';
        document.getElementById('reader').style.display = 'block';
        document.getElementById('btn-stop-cam').style.display = 'inline-block';
        document.getElementById('result-msg').style.display = 'none';

        html5QrCode = new Html5Qrcode("reader");
        
        const config = { fps: 10, qrbox: { width: 250, height: 250 } };
        
        // Mulai kamera (Minta kamera belakang 'environment')
        html5QrCode.start({ facingMode: "environment" }, config, onScanSuccess, onScanFailure)
        .then(() => {
            console.log("Kamera dimulai.");
        })
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

        // Pause scanner sementara
        html5QrCode.pause();

        // Tampilkan Loading
        const msgBox = document.getElementById('result-msg');
        msgBox.style.display = 'block';
        msgBox.className = 'status-msg loading';
        msgBox.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memvalidasi...';
        
        // Kirim Data ke Server
        fetch('process_absensi.php', {
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
                msgBox.innerHTML = `<i class="fas fa-check-circle" style="font-size:24px; display:block; margin-bottom:10px;"></i> <strong>BERHASIL!</strong><br>${data.message}`;
                
                // Stop camera permanent
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
                
                // Resume scanner setelah 3 detik
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
            // Opsi retry manual
            document.getElementById('btn-retry').style.display = 'inline-block';
        });
    }

    function onScanFailure(error) {
        // Abaikan error saat scanning frame kosong agar tidak spam log
    }

    function showError(msg) {
        const msgBox = document.getElementById('result-msg');
        msgBox.style.display = 'block';
        msgBox.className = 'status-msg error';
        msgBox.innerHTML = `<i class="fas fa-times-circle"></i> <strong>GAGAL!</strong><br>${msg}`;
    }
</script>

<?php require_once 'includes/footer.php'; ?>