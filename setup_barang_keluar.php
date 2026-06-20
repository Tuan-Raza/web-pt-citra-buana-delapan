<?php
include 'koneksi.php';

$sql = "CREATE TABLE IF NOT EXISTS barang_keluar (
    id_keluar INT AUTO_INCREMENT PRIMARY KEY,
    id_transaksi VARCHAR(50) UNIQUE NOT NULL,
    tanggal_keluar DATE NOT NULL,
    tujuan VARCHAR(150),
    id_barang INT NOT NULL,
    jumlah INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_barang) REFERENCES data_barang(id_barang) ON DELETE CASCADE
)";

if(mysqli_query($conn, $sql)) {
    echo "<h3>MANTAP! Tabel barang_keluar berhasil dibuat!</h3>";
    echo "<a href='barang_keluar.php'>Lanjut ke halaman Barang Keluar</a>";
} else {
    echo "Waduh error: " . mysqli_error($conn);
}
?>