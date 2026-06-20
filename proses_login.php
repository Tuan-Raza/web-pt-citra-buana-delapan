<?php
session_start();
// Pastikan file koneksi sudah di-include dan menggunakan variabel $koneksi 
include 'koneksi.php'; 

// Pastikan tombol submit sudah ditekan
if (isset($_POST['login'])) {
    // Ubah $conn menjadi $koneksi 
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = $_POST['password'];
    $role_dipilih = mysqli_real_escape_string($koneksi, $_POST['role']);

    // Cek user di database berdasarkan username atau email
    $query = "SELECT * FROM users WHERE username='$username' OR email='$username'";
    $result = mysqli_query($koneksi, $query);

    if (mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_assoc($result);
        
        // Cek password
        if (password_verify($password, $row['password'])) {
            
            // CEK JABATAN (ROLE): Apakah sesuai dengan database?
            if ($row['role'] !== $role_dipilih) {
                // Lempar parameter error ke URL agar ditangkap oleh UI login.php 
                header("Location: login.php?pesan=akses_ditolak&role_dipilih=$role_dipilih&role_asli=".$row['role']);
                exit;
            } 
            
            // CEK STATUS PERSETUJUAN (Staff yang baru daftar)
            elseif (isset($row['status_akun']) && $row['status_akun'] === 'Menunggu Persetujuan') {
                // Lempar parameter belum_diacc ke URL 
                header("Location: login.php?pesan=belum_diacc&nama=".urlencode($row['nama_lengkap']));
                exit;
            } 
            
            else {
                // Jika sesuai dan sudah di-ACC, set session dan masuk
                $_SESSION['login'] = true;
                $_SESSION['id_user'] = $row['id_user'];
                $_SESSION['role'] = $row['role'];
                $_SESSION['nama_lengkap'] = $row['nama_lengkap'];
                
                // PENGATURAN REDIRECT BERDASARKAN ROLE
                if ($row['role'] == 'admin') {
                    // Masuk ke dashboard admin
                    header("Location: dashboard.php");
                    exit;
                } elseif ($row['role'] == 'staff') {
                    // Masuk ke dashboard khusus staff
                    header("Location: staff_dashboard.php");
                    exit;
                }
            }
        } else {
            // Password salah
            header("Location: login.php?pesan=error");
            exit;
        }
    } else {
        // Username/Email tidak ditemukan
        header("Location: login.php?pesan=error");
        exit;
    }
} else {
    // Jika diakses langsung tanpa lewat form
    header("Location: login.php");
    exit;
}
?>