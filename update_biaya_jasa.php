<?php
// update_biaya_jasa.php
// Menghitung ulang Sub Total berdasarkan (Total Sparepart + Input Biaya Jasa)

require_once 'includes/db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['invoice'], $data['biaya_jasa'])) {
    $invoice = $data['invoice'];
    $biaya_jasa = floatval($data['biaya_jasa']);

    // 1. Hitung Total Sparepart (Internal + Eksternal)
    $sql_sparepart = "SELECT 
        (
            COALESCE((SELECT SUM(sk.jumlah * ms.harga_jual) 
             FROM sparepart_keluar sk 
             JOIN master_sparepart ms ON sk.code_sparepart = ms.code_sparepart 
             WHERE sk.invoice_service = '$invoice'), 0) 
            +
            COALESCE((SELECT SUM(psl.total_harga) 
             FROM pembelian_sparepart_luar psl 
             WHERE psl.invoice_service = '$invoice'), 0)
        ) as total_sparepart";
    
    $result_part = $conn->query($sql_sparepart);
    $row_part = $result_part->fetch_assoc();
    $total_sparepart = floatval($row_part['total_sparepart']);

    // 2. Hitung Sub Total Baru
    $new_sub_total = $total_sparepart + $biaya_jasa;

    // 3. Update Sub Total ke Database
    $stmt = $conn->prepare("UPDATE service SET sub_total = ? WHERE invoice = ?");
    $stmt->bind_param("ds", $new_sub_total, $invoice);

    if ($stmt->execute()) {
        // 4. Cek Status Pembayaran (MEMPERHITUNGKAN UANG MUKA & TOTAL BAYAR)
        $cek_bayar = $conn->query("SELECT uang_muka, total_bayar FROM service WHERE invoice = '$invoice'");
        $row_bayar = $cek_bayar->fetch_assoc();
        
        $uang_muka = floatval($row_bayar['uang_muka']);
        // Jika kolom total_bayar belum ada, anggap 0
        $total_bayar = isset($row_bayar['total_bayar']) ? floatval($row_bayar['total_bayar']) : 0;
        
        // Logic Lunas: Uang Muka + Total Bayar >= Sub Total Baru
        $status_bayar = (($uang_muka + $total_bayar) >= $new_sub_total && $new_sub_total > 0) ? 'Lunas' : 'Belum Lunas';
        
        $conn->query("UPDATE service SET status_pembayaran = '$status_bayar' WHERE invoice = '$invoice'");

        echo json_encode([
            'success' => true, 
            'message' => 'Biaya jasa diperbarui', 
            'new_sub_total' => $new_sub_total,
            'status_bayar' => $status_bayar
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal update database']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
}
?>