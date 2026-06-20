<?php
include 'koneksi.php';

$sql = "CREATE TABLE IF NOT EXISTS barang_masuk (
    id_masuk INT AUTO_INCREMENT PRIMARY KEY,
    id_transaksi VARCHAR(50) UNIQUE NOT NULL,
    tanggal_masuk DATE NOT NULL,
    no_surat_jalan VARCHAR(50),
    supplier VARCHAR(100),
    id_barang INT NOT NULL,
    jumlah INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_barang) REFERENCES data_barang(id_barang) ON DELETE CASCADE
)";

if(mysqli_query($conn, $sql)) {
    echo "<h3>MANTAP! Tabel barang_masuk berhasil dibuat!</h3>";
    echo "<a href='barang_masuk.php'>Lanjut ke halaman Barang Masuk</a>";
} else {
    echo "Waduh error: " . mysqli_error($conn);
}
?>