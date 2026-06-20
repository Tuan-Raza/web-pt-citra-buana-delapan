<?php
$host = "localhost";
$user = "root"; // Sesuaikan jika kamu menggunakan password di XAMPP/Laragon
$pass = "";
$db   = "db_citrabuana";

$koneksi = mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
?>