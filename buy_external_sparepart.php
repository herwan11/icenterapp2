<?php
// buy_external_sparepart.php
require_once 'includes/db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$response = ['success' => false, 'message' => 'Data tidak valid.'];

if (isset($data['invoice'], $data['nama'], $data['jumlah'], $data['harga'])) {
    $invoice = $data['invoice'];
    $nama_sparepart = trim($data['nama']);
    $jumlah = intval($data['jumlah']);
    $harga_satuan = floatval($data['harga']);
    $total_harga = $jumlah * $harga_satuan;

    // Generate kode transaksi unik: SPL-TahunBulanTanggal-JamMenitDetik
    $id_pembelian = 'SPL-' . date('ymd-His');

    if ($jumlah > 0 && $harga_satuan >= 0 && !empty($nama_sparepart)) {
        $conn->begin_transaction();
        try {
            // 1. Simpan ke tabel pembelian_sparepart_luar
            // Perhatian: Kolom total_harga di tabel ini (pembelian_sparepart_luar) sudah sesuai
            $stmt_beli = $conn->prepare("INSERT INTO pembelian_sparepart_luar (id_pembelian, invoice_service, nama_sparepart, jumlah, harga_satuan, total_harga) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_beli->bind_param("sssiid", $id_pembelian, $invoice, $nama_sparepart, $jumlah, $harga_satuan, $total_harga);
            $stmt_beli->execute();

            // 2. Potong saldo kas (Dicatat di transaksi_kas)
            $keterangan_kas = "Beli sparepart luar: " . $nama_sparepart . " untuk service " . $invoice;
            
            // Ambil saldo terakhir (Membutuhkan kolom saldo_terakhir)
            $saldo_sebelumnya = 0;
            // Gunakan kolom saldo_terakhir yang sudah ditambahkan via script perbaikan SQL
            $result_saldo = $conn->query("SELECT saldo_terakhir FROM transaksi_kas ORDER BY id DESC LIMIT 1");
            if ($result_saldo->num_rows > 0) {
                $saldo_sebelumnya = $result_saldo->fetch_assoc()['saldo_terakhir'];
            }
            $saldo_terakhir = $saldo_sebelumnya - $total_harga;
            
            // Simpan transaksi kas (Membutuhkan kolom jumlah dan saldo_terakhir)
            // Menggunakan 'jumlah' dan 'saldo_terakhir' sesuai skema yang diperbaiki
            $stmt_kas = $conn->prepare("INSERT INTO transaksi_kas (jenis, jumlah, keterangan, saldo_terakhir) VALUES ('keluar', ?, ?, ?)");
            $stmt_kas->bind_param("dsd", $total_harga, $keterangan_kas, $saldo_terakhir);
            $stmt_kas->execute();
            
            // 3. Update sub_total di tabel service
            $stmt_service = $conn->prepare("UPDATE service SET sub_total = sub_total + ? WHERE invoice = ?");
            $stmt_service->bind_param("ds", $total_harga, $invoice);
            $stmt_service->execute();

            $conn->commit();
            $response = ['success' => true, 'message' => 'Pembelian berhasil dicatat dan kas terpotong.'];

        } catch (Exception $e) {
            $conn->rollback();
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
    }
}

echo json_encode($response);
$conn->close();
?>