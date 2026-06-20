<?php
include 'koneksi.php';

$sql = "CREATE TABLE IF NOT EXISTS data_barang (
    id_barang INT AUTO_INCREMENT PRIMARY KEY,
    kode_sku VARCHAR(50) UNIQUE NOT NULL,
    nama_barang VARCHAR(150) NOT NULL,
    kategori VARCHAR(50),
    lokasi_rak VARCHAR(50),
    harga_satuan INT,
    stok_tersedia INT DEFAULT 0,
    satuan VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if(mysqli_query($conn, $sql)) {
    echo "<h3>Tabel berhasil dibuat!</h3>";
    echo "<a href='data_barang.php'>Klik di sini untuk kembali ke Data Barang</a>";
} else {
    echo "Error: " . mysqli_error($conn);
}
?>