<?php
// includes/db.php

// --- PENTING: Ganti dengan kredensial database Anda ---
$host = "localhost";
$user = "icey4741_icenter";
$password = "Herwansyah11!";
$database = "icey4741_icenter";

// Buat koneksi
$conn = new mysqli($host, $user, $password, $database);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Set karakter set ke utf8mb4 untuk mendukung berbagai karakter
$conn->set_charset("utf8mb4");

?>