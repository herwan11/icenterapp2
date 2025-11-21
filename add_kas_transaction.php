<?php
// add_kas_transaction.php
require_once 'includes/db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$response = ['success' => false, 'message' => 'Data tidak valid.'];

if (isset($data['jenis'], $data['jumlah'], $data['keterangan'])) {
    $jenis = $data['jenis'];
    $jumlah = floatval($data['jumlah']);
    $keterangan = trim($data['keterangan']);

    if (($jenis === 'masuk' || $jenis === 'keluar') && $jumlah > 0 && !empty($keterangan)) {
        $conn->begin_transaction();
        try {
            // 1. Ambil saldo terakhir (Membutuhkan kolom saldo_terakhir)
            $saldo_sebelumnya = 0;
            // Ganti nama kolom 'jumlah' di query menjadi 'saldo_terakhir'
            $result_saldo = $conn->query("SELECT saldo_terakhir FROM transaksi_kas ORDER BY id DESC LIMIT 1");
            if ($result_saldo->num_rows > 0) {
                $saldo_sebelumnya = $result_saldo->fetch_assoc()['saldo_terakhir'];
            }

            // 2. Hitung saldo baru
            $saldo_terakhir = ($jenis === 'masuk') ? $saldo_sebelumnya + $jumlah : $saldo_sebelumnya - $jumlah;

            // 3. Simpan transaksi baru
            // Perhatian: Pastikan tabel transaksi_kas memiliki kolom 'jumlah' (bukan total_harga) dan 'saldo_terakhir'
            $stmt = $conn->prepare("INSERT INTO transaksi_kas (jenis, jumlah, keterangan, saldo_terakhir) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sdsd", $jenis, $jumlah, $keterangan, $saldo_terakhir);
            $stmt->execute();
            
            $conn->commit();
            
            $response = [
                'success' => true,
                'message' => 'Transaksi berhasil dicatat.',
                'new_saldo' => $saldo_terakhir,
                'new_trx' => [
                    'jenis' => $jenis,
                    'jumlah' => $jumlah,
                    'keterangan' => $keterangan,
                    'saldo_terakhir' => $saldo_terakhir
                ]
            ];

        } catch (Exception $e) {
            $conn->rollback();
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
    } else {
         $response['message'] = 'Pastikan semua field terisi dengan benar.';
    }
}

echo json_encode($response);
$conn->close();
?>