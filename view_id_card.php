<?php
// view_id_card.php
require_once 'includes/db.php';

$token = isset($_GET['token']) ? $_GET['token'] : '';
$user = null;

if ($token) {
    // Cari user berdasarkan token QR
    $stmt = $conn->prepare("SELECT * FROM users WHERE qr_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
}

if (!$user) {
    die("ID Card tidak ditemukan atau link tidak valid.");
}

// URL Foto (Fallback jika tidak ada)
$foto_url = !empty($user['foto']) && file_exists($user['foto']) 
            ? $user['foto'] 
            : 'assets/media/icenter.png'; // Fallback ke logo jika tidak ada foto

// Warna Utama (Biru seperti referensi - Cyan/Blue)
$primary_color = '#00AEEF'; 
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ID Card - <?php echo htmlspecialchars($user['nama']); ?></title>
    <!-- Font -->
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            font-family: 'Roboto', sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        /* Container Kartu */
        .card-container {
            width: 320px; /* Ukuran ID Card Portrait */
            height: 520px;
            background: white;
            position: relative;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            display: flex;
            overflow: hidden;
            /* Tidak ada border-radius agar terlihat tegas seperti kartu PVC */
        }

        /* --- SIDEBAR KIRI (ROLE) --- */
        .sidebar-left {
            width: 50px;
            background-color: <?php echo $primary_color; ?>;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .vertical-text {
            writing-mode: vertical-rl;
            text-orientation: mixed;
            transform: rotate(180deg);
            color: white;
            font-family: 'Oswald', sans-serif;
            font-weight: 700;
            font-size: 28px;
            letter-spacing: 4px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        /* --- KONTEN KANAN --- */
        .main-content {
            flex-grow: 1;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
        }

        /* Header (Logo & Nama) */
        .header {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: flex-start; /* Logo di kiri */
            gap: 10px;
            margin-bottom: 20px;
            margin-top: 10px;
        }

        .logo-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid <?php echo $primary_color; ?>;
            padding: 2px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .logo-img {
            width: 100%;
            height: auto;
            object-fit: contain;
        }

        .company-info {
            text-align: left;
        }

        .company-title {
            font-weight: 700;
            font-size: 16px;
            color: #000;
            line-height: 1.2;
        }
        .company-subtitle {
            font-size: 12px;
            color: #555;
            font-weight: 500;
        }

        /* Area Foto dengan Background Shape Biru */
        .photo-area {
            position: relative;
            width: 180px;
            height: 180px;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 10px;
            margin-bottom: 20px;
        }

        /* Shape Biru di Belakang Foto (Tetesan air miring) */
        .blue-shape {
            position: absolute;
            width: 100%;
            height: 100%;
            background-color: <?php echo $primary_color; ?>;
            border-radius: 50% 50% 50% 0; /* Bentuk tetesan */
            transform: rotate(-45deg);
            z-index: 1;
        }

        .profile-img {
            width: 160px;
            height: 160px;
            border-radius: 50%;
            object-fit: cover;
            position: relative;
            z-index: 2;
            border: 4px solid white; /* Border putih pemisah */
        }

        /* Info Karyawan */
        .info-area {
            text-align: center;
            width: 100%;
            z-index: 2;
            margin-bottom: 20px;
        }

        .employee-name {
            font-family: 'Oswald', sans-serif;
            font-size: 24px;
            font-weight: 700;
            color: #000;
            text-transform: uppercase;
            margin: 0;
            line-height: 1.2;
        }

        .employee-role-sub {
            font-size: 14px;
            color: #333;
            margin-top: 5px;
            font-weight: 400;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Footer (QR Code & Website) */
        .footer-area {
            margin-top: auto;
            text-align: center;
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* QR Barcode Style */
        .qr-section {
            margin-bottom: 10px;
        }

        .website-text {
            font-size: 10px;
            color: #000;
            letter-spacing: 1px;
            font-weight: 500;
            margin-top: 5px;
        }

        /* Tombol Download */
        .btn-download {
            margin-top: 30px;
            background: <?php echo $primary_color; ?>;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            transition: transform 0.2s;
            display: flex;
            align-items: center;
            gap: 10px;
            font-family: 'Roboto', sans-serif;
        }
        .btn-download:hover { transform: scale(1.05); filter: brightness(1.1); }

    </style>
</head>
<body>

    <div class="card-container" id="idCard">
        <!-- Sidebar Kiri -->
        <div class="sidebar-left">
            <!-- ROLE diputar vertikal -->
            <div class="vertical-text"><?php echo strtoupper($user['role']); ?></div>
        </div>

        <!-- Konten Kanan -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <div class="logo-circle">
                    <img src="assets/media/icenter.png" alt="Logo" class="logo-img">
                </div>
                <div class="company-info">
                    <div class="company-title">iCenter Apple</div>
                    <!-- Bisa diisi role atau Authorized Service -->
                    <div class="company-subtitle">Authorized Service</div> 
                </div>
            </div>

            <!-- Foto dengan Background Shape -->
            <div class="photo-area">
                <div class="blue-shape"></div>
                <img src="<?php echo htmlspecialchars($foto_url); ?>" alt="Foto Karyawan" class="profile-img">
            </div>

            <!-- Nama & Role -->
            <div class="info-area">
                <h1 class="employee-name"><?php echo strtoupper($user['nama']); ?></h1>
                <div class="employee-role-sub">STAFF ID: <?php echo str_pad($user['id'], 4, '0', STR_PAD_LEFT); ?></div>
            </div>

            <!-- Footer QR & Website -->
            <div class="footer-area">
                <div class="qr-section">
                    <!-- Placeholder QR -->
                    <div id="qrcode"></div>
                </div>
                <!-- Pengganti ID Number Barcode -->
                <div class="website-text">www.icenterpangkep.my.id</div>
            </div>
        </div>
    </div>

    <button class="btn-download" onclick="downloadCard()">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
        Download ID Card
    </button>

    <script>
        // Generate QR Code
        // Isi QR adalah URL profil atau token user agar unik
        const qrContent = window.location.href; 
        
        new QRCode(document.getElementById("qrcode"), {
            text: qrContent,
            width: 100,  
            height: 100, 
            colorDark : "#000000",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.H
        });

        // Fungsi Download Gambar
        function downloadCard() {
            const card = document.getElementById('idCard');
            const btn = document.querySelector('.btn-download');
            
            btn.innerHTML = 'Memproses...';
            
            html2canvas(card, {
                scale: 3, // Resolusi tinggi (3x) agar tajam saat dicetak
                useCORS: true, // Penting untuk gambar profil
                backgroundColor: null,
                logging: false
            }).then(canvas => {
                const link = document.createElement('a');
                // Nama file bersih
                const cleanName = '<?php echo preg_replace("/[^a-zA-Z0-9]/", "", $user["nama"]); ?>';
                link.download = 'ID-Card-' + cleanName + '.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
                
                btn.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg> Download ID Card';
            });
        }
    </script>

</body>
</html>