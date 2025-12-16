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
            : 'assets/media/icenter.png'; // Fallback

// Warna Utama (Biru Terang untuk Sidebar)
$primary_color = '#00AEEF'; 
// Warna Sekunder (Biru Gelap untuk blok Nama/Info)
$dark_color = '#0e2b42'; 
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ID Card - <?php echo htmlspecialchars($user['nama']); ?></title>
    <!-- Font -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            font-family: 'Montserrat', sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        /* Container Kartu */
        .id-card-wrapper {
            width: 320px;
            height: 560px; /* Rasio Portrait */
            background: white;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            display: flex;
            position: relative;
            overflow: hidden;
        }

        /* --- BAGIAN KIRI (Konten Utama) --- */
        .left-content {
            flex: 1; /* Mengisi sisa ruang */
            display: flex;
            flex-direction: column;
            position: relative;
        }

        /* 1. Top Section (Putih - Logo & Foto) */
        .top-section {
            background-color: white;
            padding-top: 30px;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding-bottom: 20px;
            flex-grow: 1; /* Mendorong bagian bawah */
        }

        .company-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .logo-img {
            width: 40px;
            height: auto;
        }

        .company-name {
            font-size: 18px;
            font-weight: 700;
            color: #333;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .company-name span {
            color: <?php echo $primary_color; ?>; /* Warna Biru pada kata kedua/Apple */
        }

        .photo-container {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            overflow: hidden;
            border: 5px solid #f0f0f0; /* Border tipis abu-abu */
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .photo-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* 2. Middle Section (Biru Gelap - Nama & Info) */
        .info-block {
            background-color: <?php echo $dark_color; ?>;
            color: white;
            padding: 20px;
            position: relative;
        }

        .employee-name {
            font-size: 20px;
            font-weight: 700;
            text-transform: capitalize;
            margin-bottom: 15px;
            line-height: 1.2;
        }

        .details-row {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        /* Kotak QR Code dengan border putih */
        .qr-box {
            background: white;
            padding: 5px;
            width: 70px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .details-text {
            font-size: 12px;
            line-height: 1.5;
            color: #e0e0e0;
        }
        .details-text strong {
            color: white;
            font-size: 13px;
            display: block;
            margin-bottom: 2px;
        }

        /* 3. Footer Section (Putih - Website) */
        .card-footer {
            background-color: white;
            padding: 10px 20px;
            font-size: 11px;
            color: #555;
            font-weight: 600;
        }

        /* --- BAGIAN KANAN (Sidebar Biru) --- */
        .right-sidebar {
            width: 65px;
            background-color: <?php echo $primary_color; ?>;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .vertical-role {
            writing-mode: vertical-rl;
            text-orientation: mixed;
            transform: rotate(180deg); /* Memutar teks agar terbaca dari bawah ke atas */
            color: white;
            font-size: 24px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 3px;
            white-space: nowrap;
        }

        /* Tombol Download */
        .btn-download {
            margin-top: 30px;
            background: <?php echo $dark_color; ?>;
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
            font-family: 'Montserrat', sans-serif;
        }
        .btn-download:hover { transform: scale(1.05); }

    </style>
</head>
<body>

    <div class="id-card-wrapper" id="idCard">
        
        <!-- KONTEN KIRI -->
        <div class="left-content">
            
            <!-- Logo & Foto -->
            <div class="top-section">
                <div class="company-header">
                    <!-- Ganti src logo sesuai kebutuhan -->
                    <img src="assets/media/icenter.png" alt="Logo" class="logo-img">
                    <div class="company-name">iCenter <span>Apple</span></div>
                </div>
                
                <div class="photo-container">
                    <img src="<?php echo htmlspecialchars($foto_url); ?>" alt="Foto Profil">
                </div>
            </div>

            <!-- Blok Biru Gelap (Nama & QR) -->
            <div class="info-block">
                <div class="employee-name"><?php echo htmlspecialchars($user['nama']); ?></div>
                
                <div class="details-row">
                    <div class="qr-box">
                        <div id="qrcode"></div>
                    </div>
                    <div class="details-text">
                        <strong>Staff ID: <?php echo str_pad($user['id'], 4, '0', STR_PAD_LEFT); ?></strong>
                        <?php echo date('Y'); ?> Authorized<br>
                        Service Personnel
                    </div>
                </div>
            </div>

            <!-- Website Footer -->
            <div class="card-footer">
                www.icenterpangkep.my.id
            </div>
        </div>

        <!-- SIDEBAR KANAN (Role) -->
        <div class="right-sidebar">
            <div class="vertical-role"><?php echo strtoupper($user['role']); ?></div>
        </div>

    </div>

    <button class="btn-download" onclick="downloadCard()">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
        Download ID Card
    </button>

    <script>
        // Generate QR Code
        // Isi QR adalah URL saat ini (profil user)
        const qrContent = window.location.href; 
        
        new QRCode(document.getElementById("qrcode"), {
            text: qrContent,
            width: 60,  
            height: 60, 
            colorDark : "<?php echo $dark_color; ?>", // QR warna gelap sesuai tema
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.M
        });

        // Fungsi Download Gambar
        function downloadCard() {
            const card = document.getElementById('idCard');
            const btn = document.querySelector('.btn-download');
            
            btn.innerHTML = 'Memproses...';
            
            html2canvas(card, {
                scale: 3, // Resolusi tinggi
                useCORS: true, 
                backgroundColor: null
            }).then(canvas => {
                const link = document.createElement('a');
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