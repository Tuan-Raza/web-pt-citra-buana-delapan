<?php
include 'koneksi.php';

// 1. Hapus tabel users yang lama (kalau ada) biar bersih
mysqli_query($conn, "DROP TABLE IF EXISTS users");

// 2. Buat ulang tabel users dengan struktur yang paling pas
$buat_tabel = "CREATE TABLE users (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    nama_lengkap VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff') DEFAULT 'staff'
)";
mysqli_query($conn, $buat_tabel);

// 3. Bikin password "admin123" jadi hash yang aman dan bisa dibaca sistem
$password_plain = 'admin123';
$password_hash = password_hash($password_plain, PASSWORD_DEFAULT);

// 4. Masukkan data Admin ke database
$insert_admin = "INSERT INTO users (nama_lengkap, username, email, password, role) 
                 VALUES ('Tuan Raza', 'admin', 'admin@citrabuana.com', '$password_hash', 'admin')";

if(mysqli_query($conn, $insert_admin)){
    echo "<h3>MANTAP! Akun Admin berhasil di-reset dan dibuat ulang.</h3>";
    echo "<p>Silakan login menggunakan:</p>";
    echo "<ul>";
    echo "<li><strong>Email:</strong> admin@citrabuana.com</li>";
    echo "<li><strong>Password:</strong> admin123</li>";
    echo "<li><strong>Peran:</strong> Admin Gudang</li>";
    echo "</ul>";
    echo "<br><a href='login.php' style='padding: 10px 15px; background: #3b82f6; color: white; text-decoration: none; border-radius: 5px;'>Kembali ke Login</a>";
} else {
    echo "Waduh, ada error bro: " . mysqli_error($conn);
}
?>