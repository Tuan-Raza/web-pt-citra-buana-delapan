<?php
session_start();
include 'koneksi.php';

// Cek Login
if (!isset($_SESSION["login"])) { header("Location: login.php"); exit; }
$id_user_login = $_SESSION['id_user'];

// ==============================================================================
// AUTO-SETUP DATABASE ANTI-GAGAL (Memastikan semua kolom terkoneksi sempurna)
// ==============================================================================
$cek_kolom_telp = mysqli_query($koneksi, "SHOW COLUMNS FROM users LIKE 'no_telp'");
if(mysqli_num_rows($cek_kolom_telp) == 0) {
    mysqli_query($koneksi, "ALTER TABLE users ADD COLUMN no_telp VARCHAR(20) DEFAULT '' AFTER email");
}

$cek_kolom_foto = mysqli_query($koneksi, "SHOW COLUMNS FROM users LIKE 'foto_profil'");
if(mysqli_num_rows($cek_kolom_foto) == 0) {
    mysqli_query($koneksi, "ALTER TABLE users ADD COLUMN foto_profil VARCHAR(255) DEFAULT '' AFTER no_telp");
}

// Ini kuncian utamanya boy! Biar ga bikin blank putih lagi, kita bikin kolom status_akun jika belum ada
$cek_kolom_status = mysqli_query($koneksi, "SHOW COLUMNS FROM users LIKE 'status_akun'");
if(mysqli_num_rows($cek_kolom_status) == 0) {
    mysqli_query($koneksi, "ALTER TABLE users ADD COLUMN status_akun VARCHAR(30) DEFAULT 'Disetujui' AFTER foto_profil");
    // Maksa semua akun lama statusnya langsung otomatis Aktif/Disetujui biar ga kosong
    mysqli_query($koneksi, "UPDATE users SET status_akun='Disetujui' WHERE status_akun IS NULL OR status_akun = ''");
}

mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS pengaturan_perusahaan (
    id INT PRIMARY KEY, 
    nama_perusahaan VARCHAR(150), 
    email_kontak VARCHAR(100), 
    no_telp VARCHAR(20), 
    stok_menipis_aktif INT DEFAULT 0
)");

$cek_pengaturan = mysqli_query($koneksi, "SELECT * FROM pengaturan_perusahaan WHERE id=1");
if(mysqli_num_rows($cek_pengaturan) == 0){
    mysqli_query($koneksi, "INSERT INTO pengaturan_perusahaan (id, nama_perusahaan, email_kontak, no_telp, stok_menipis_aktif) 
                         VALUES (1, 'PT Citra Buana Delapan', 'admin@citrabuana.com', '021-88997766', 0)");
}
// ==============================================================================

// Data User Login Sebelum Update
$q_me = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user='$id_user_login'");
$me = mysqli_fetch_assoc($q_me);

// --- LOGIKA PROSES FORM (CRUD PENGATURAN) ---
$status_msg = "";

// 1. Update Profil User (Nama, Telp, & FOTO)
if(isset($_POST['simpan_profil'])) {
    $nama_baru = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap']);
    $telp_baru = mysqli_real_escape_string($koneksi, $_POST['no_telp']);
    $foto_name = $me['foto_profil']; 
    
    if(isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] == 0) {
        $file_tmp = $_FILES['foto_profil']['tmp_name'];
        $file_name = $_FILES['foto_profil']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = array('jpg', 'jpeg', 'png', 'gif');

        if(in_array($file_ext, $allowed_ext)) {
            $new_file_name = "profil_" . $id_user_login . "_" . time() . "." . $file_ext;
            $upload_dir = 'uploads/'; 
            
            if(!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }
            
            if(move_uploaded_file($file_tmp, $upload_dir . $new_file_name)) {
                if(!empty($me['foto_profil']) && file_exists($upload_dir . $me['foto_profil'])) {
                    unlink($upload_dir . $me['foto_profil']);
                }
                $foto_name = $new_file_name; 
            }
        } else {
            header("Location: pengaturan.php?error=Gagal! Format foto harus JPG, JPEG, atau PNG."); exit;
        }
    }

    mysqli_query($koneksi, "UPDATE users SET nama_lengkap='$nama_baru', no_telp='$telp_baru', foto_profil='$foto_name' WHERE id_user='$id_user_login'");
    $_SESSION['nama_lengkap'] = $nama_baru; 
    header("Location: pengaturan.php?status=Profil dan Foto Berhasil Diperbarui!"); exit;
}

// 2. Update Kata Sandi (Pribadi)
if(isset($_POST['simpan_sandi'])) {
    $sandi_lama = $_POST['sandi_lama'];
    $sandi_baru = $_POST['sandi_baru'];
    $sandi_konfirmasi = $_POST['sandi_konfirmasi'];

    if(password_verify($sandi_lama, $me['password'])) {
        if($sandi_baru === $sandi_konfirmasi) {
            $hash_baru = password_hash($sandi_baru, PASSWORD_DEFAULT);
            mysqli_query($koneksi, "UPDATE users SET password='$hash_baru' WHERE id_user='$id_user_login'");
            header("Location: pengaturan.php?status=Kata Sandi Berhasil Diubah!"); exit;
        } else {
            header("Location: pengaturan.php?error=Konfirmasi sandi baru tidak cocok!"); exit;
        }
    } else {
        header("Location: pengaturan.php?error=Kata sandi saat ini salah!"); exit;
    }
}

// 3. Update Pengaturan Perusahaan
if(isset($_POST['simpan_perusahaan'])) {
    $nama_pt = mysqli_real_escape_string($koneksi, $_POST['nama_perusahaan']);
    $email_pt = mysqli_real_escape_string($koneksi, $_POST['email_kontak']);
    $telp_pt = mysqli_real_escape_string($koneksi, $_POST['no_telp_pt']);
    $toggle_stok = isset($_POST['stok_menipis_aktif']) ? 1 : 0;

    mysqli_query($koneksi, "UPDATE pengaturan_perusahaan SET nama_perusahaan='$nama_pt', email_kontak='$email_pt', no_telp='$telp_pt', stok_menipis_aktif='$toggle_stok' WHERE id=1");
    header("Location: pengaturan.php?status=Informasi Perusahaan Disimpan!"); exit;
}

// 4. Tambah Staff Baru
if(isset($_POST['tambah_staff'])) {
    $nama_staff = mysqli_real_escape_string($koneksi, $_POST['nama_pegawai']);
    $role_staff = mysqli_real_escape_string($koneksi, $_POST['jabatan']);
    $email_staff = mysqli_real_escape_string($koneksi, $_POST['email_username']);
    $sandi_staff = password_hash($_POST['kata_sandi'], PASSWORD_DEFAULT);

    $cek_email = mysqli_query($koneksi, "SELECT email FROM users WHERE email='$email_staff' OR username='$email_staff'");
    if(mysqli_num_rows($cek_email) > 0) {
        header("Location: pengaturan.php?error=Email/Username sudah terdaftar!"); exit;
    } else {
        mysqli_query($koneksi, "INSERT INTO users (nama_lengkap, username, email, password, role, status_akun) VALUES ('$nama_staff', '$email_staff', '$email_staff', '$sandi_staff', '$role_staff', 'Disetujui')");
        header("Location: pengaturan.php?status=Staff Baru Berhasil Ditambahkan!"); exit;
    }
}

// 5. Edit Staff
if(isset($_POST['edit_staff'])) {
    $id_edit = mysqli_real_escape_string($koneksi, $_POST['id_user_edit']);
    $nama_edit = mysqli_real_escape_string($koneksi, $_POST['nama_pegawai_edit']);
    $role_edit = mysqli_real_escape_string($koneksi, $_POST['jabatan_edit']);
    $email_edit = mysqli_real_escape_string($koneksi, $_POST['email_username_edit']);

    $cek_email = mysqli_query($koneksi, "SELECT email FROM users WHERE (email='$email_edit' OR username='$email_edit') AND id_user != '$id_edit'");
    if(mysqli_num_rows($cek_email) > 0) {
        header("Location: pengaturan.php?error=Email/Username sudah dipakai akun lain!"); exit;
    } else {
        mysqli_query($koneksi, "UPDATE users SET nama_lengkap='$nama_edit', email='$email_edit', username='$email_edit', role='$role_edit' WHERE id_user='$id_edit'");
        header("Location: pengaturan.php?status=Data Staff Berhasil Diubah!"); exit;
    }
}

// 5b. Reset Sandi Staff Khusus
if(isset($_POST['edit_sandi_staff'])) {
    $id_sandi_edit = mysqli_real_escape_string($koneksi, $_POST['id_user_sandi_edit']);
    $sandi_baru_staff = $_POST['sandi_baru_staff'];
    $sandi_konfirmasi_staff = $_POST['sandi_konfirmasi_staff'];

    if($sandi_baru_staff === $sandi_konfirmasi_staff) {
        $hash_baru_staff = password_hash($sandi_baru_staff, PASSWORD_DEFAULT);
        mysqli_query($koneksi, "UPDATE users SET password='$hash_baru_staff' WHERE id_user='$id_sandi_edit'");
        header("Location: pengaturan.php?status=Kata Sandi Staff Berhasil Di-reset!"); exit;
    } else {
        header("Location: pengaturan.php?error=Konfirmasi sandi baru tidak cocok!"); exit;
    }
}

// 6. ACC / Setujui Staff Baru
if(isset($_GET['acc_user'])) {
    $id_acc = $_GET['acc_user'];
    mysqli_query($koneksi, "UPDATE users SET status_akun='Disetujui' WHERE id_user='$id_acc'");
    header("Location: pengaturan.php?status=Akun Staff Berhasil Diaktifkan!"); exit;
}

// 7. Hapus Staff
if(isset($_GET['hapus_user'])) {
    $id_hapus = $_GET['hapus_user'];
    if($id_hapus != $id_user_login) { 
        mysqli_query($koneksi, "DELETE FROM users WHERE id_user='$id_hapus'");
        header("Location: pengaturan.php?status=Akun Staff Berhasil Dihapus!"); exit;
    }
}

// --- AMBIL DATA UNTUK DITAMPILKAN LAGI SETELAH UPDATE ---
$q_me = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user='$id_user_login'");
$me = mysqli_fetch_assoc($q_me);

$q_pt = mysqli_query($koneksi, "SELECT * FROM pengaturan_perusahaan WHERE id=1");
$pt = mysqli_fetch_assoc($q_pt);

// Urutkan biar akun pendaftar baru (Menunggu Persetujuan) tampil paling atas
$q_staff = mysqli_query($koneksi, "SELECT * FROM users ORDER BY FIELD(status_akun, 'Menunggu Persetujuan', 'Disetujui'), role ASC, id_user DESC");

// Generate Inisial Avatar
$words = explode(" ", $me['nama_lengkap']);
$inisial = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));

$avatar_html = "";
if(!empty($me['foto_profil']) && file_exists('uploads/' . $me['foto_profil'])) {
    $foto_url = 'uploads/' . $me['foto_profil'];
    $avatar_html = "<img src='$foto_url' alt='Profil' style='width: 100%; height: 100%; object-fit: cover;'>";
} else {
    $avatar_html = $inisial;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Sistem - PT Citra Buana Delapan</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, sans-serif; }
        body { display: flex; height: 100vh; background-color: #f1f5f9; color: #1e293b; overflow: hidden; }

        /* SIDEBAR ORIGINAL DARI LU */
        .sidebar { width: 260px; background-color: #172134; color: white; display: flex; flex-direction: column; box-shadow: 4px 0 10px rgba(0,0,0,0.1); z-index: 10; }
        .sidebar-header { padding: 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .sidebar-header h3 { font-size: 18px; color: #3b82f6; letter-spacing: 1px;}
        .sidebar-header p { font-size: 12px; color: #64748b; margin-top: 5px;}
        .menu { list-style: none; padding: 20px 0; flex: 1; }
        .menu a { display: flex; align-items: center; padding: 14px 25px; color: #94a3b8; text-decoration: none; font-size: 14.5px; transition: all 0.3s ease; }
        .menu a:hover, .menu a.active { background-color: rgba(59, 130, 246, 0.1); color: white; border-left: 4px solid #3b82f6; }

        /* MAIN CONTENT */
        .main-content { flex: 1; display: flex; flex-direction: column; overflow-y: auto; padding: 30px; }
        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #e2e8f0; }
        .topbar-left { display: flex; align-items: baseline; gap: 15px; }
        .topbar-left h2 { font-size: 24px; color: #0f172a; font-weight: 700; }
        .topbar-left span { font-size: 13px; color: #64748b; font-weight: 500;}
        .topbar-right { display: flex; align-items: center; gap: 15px; }
        .user-info { text-align: right; }
        .user-info strong { display: block; font-size: 14px; color: #0f172a; }
        .user-info span { font-size: 12px; color: #94a3b8; }
        .avatar { width: 42px; height: 42px; background-color: #2563eb; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 15px; box-shadow: 0 4px 6px rgba(37,99,235,0.2); overflow: hidden;}

        /* GRID LAYOUT PENGATURAN */
        .settings-grid { display: grid; grid-template-columns: 1fr 2.5fr; gap: 25px; margin-bottom: 25px; }
        .card { background: white; padding: 30px; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #e2e8f0; }
        .card-header { font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }

        /* PROFIL PANEL (KIRI) */
        .profile-panel { text-align: center; }
        .profile-avatar-large { width: 120px; height: 120px; background-color: #2563eb; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 40px; font-weight: bold; margin: 0 auto 20px auto; box-shadow: 0 10px 20px rgba(37,99,235,0.2); overflow: hidden;}
        .profile-panel h3 { font-size: 20px; color: #0f172a; margin-bottom: 5px; }
        .profile-panel p { color: #64748b; font-size: 14px; margin-bottom: 25px; }
        
        .btn-outline { display: block; width: 100%; padding: 12px; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; background: white; border: 1px solid #cbd5e1; color: #334155; margin-bottom: 15px; transition: 0.2s;}
        .btn-outline:hover { background: #f8fafc; border-color: #94a3b8; }
        .btn-outline-red { border-color: #fca5a5; color: #ef4444; }
        .btn-outline-red:hover { background: #fef2f2; border-color: #ef4444; }

        /* FORM INPUTS */
        .form-row { display: flex; gap: 20px; margin-bottom: 20px; }
        .form-row .form-group { flex: 1; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 8px; }
        .form-group input, .form-group select { width: 100%; padding: 12px 16px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 14px; outline: none; background: #f8fafc; color: #0f172a; transition: 0.3s; }
        .form-group input[type="file"] { padding: 9px 16px; background: white; }
        .form-group input:focus, .form-group select:focus { border-color: #3b82f6; background: white; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
        
        .btn-blue { background-color: #2563eb; color: white; border: none; padding: 12px 24px; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; transition: 0.3s; box-shadow: 0 4px 10px rgba(37,99,235,0.2); display: inline-flex; align-items: center; justify-content: center;}
        .btn-blue:hover { background-color: #1d4ed8; transform: translateY(-2px);}

        /* TOGGLE SWITCH CSS */
        .preference-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #f1f5f9;}
        .pref-info h4 { font-size: 14px; color: #0f172a; margin-bottom: 5px;}
        .pref-info p { font-size: 13px; color: #64748b;}
        
        .toggle-wrapper { display: flex; align-items: center; gap: 10px;}
        .toggle-status { font-size: 13px; font-weight: 600; color: #64748b; }
        .switch { position: relative; display: inline-block; width: 44px; height: 24px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .4s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.2);}
        input:checked + .slider { background-color: #2563eb; }
        input:checked + .slider:before { transform: translateX(20px); }

        /* TABLE STAFF & ACTION BUTTONS RAPI */
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { text-align: left; padding: 16px; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; background: #f8fafc;}
        td { padding: 16px; font-size: 14px; color: #1e293b; border-bottom: 1px solid #f1f5f9; vertical-align: middle;}
        
        .badge-role { background: #eff6ff; color: #1d4ed8; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-block;}
        .badge-role.admin { background: #fef2f2; color: #ef4444; }
        
        /* Badges Status Approval */
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-block; }
        .status-badge.disetujui { background-color: #dcfce7; color: #16a34a; }
        .status-badge.pending { background-color: #fef9c3; color: #ca8a04; }
        
        .action-btns { display: flex; gap: 8px; flex-wrap: wrap; }
        .btn-action { padding: 6px 12px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 12px; font-weight: 600; text-decoration: none; transition: 0.2s; cursor: pointer; background: white; display: inline-flex; align-items: center; justify-content: center;}
        .btn-acc { color: #10b981; border-color: #bbf7d0; }
        .btn-acc:hover { background: #f0fdf4; }
        .btn-edit { color: #3b82f6; }
        .btn-edit:hover { background: #eff6ff; }
        .btn-key { color: #8b5cf6; } /* Warna icon sandi ungu/baru */
        .btn-key:hover { background: #f5f3ff; }
        .btn-delete { color: #ef4444; }
        .btn-delete:hover { background: #fef2f2; }

        /* MODAL STYLES */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15,23,42,0.6); backdrop-filter: blur(3px); z-index: 100; justify-content: center; align-items: center; animation: fadeIn 0.2s;}
        .modal-box { background: white; padding: 30px; border-radius: 16px; width: 500px; box-shadow: 0 20px 40px rgba(0,0,0,0.15); animation: popIn 0.3s; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px; }
        .modal-header h3 { font-size: 20px; color: #0f172a; font-weight: 700; }
        .modal-subtitle { font-size: 13px; color: #64748b; margin-bottom: 25px; display: block;}
        .close-btn { background: none; border: none; font-size: 24px; color: #94a3b8; cursor: pointer; transition: 0.2s; }
        .close-btn:hover { color: #0f172a; }
        .modal-footer { margin-top: 30px; display: flex; gap: 10px; }
        .btn-full { width: 100%; display: block; }
        
        .toggle-pw-btn { position: absolute; right: 10px; top: 12px; background: none; border: none; font-size: 12px; font-weight: 700; color: #3b82f6; cursor: pointer; }
        .toggle-pw-btn:hover { color: #1d4ed8; text-decoration: underline; }

        /* MODAL HAPUS KHUSUS */
        .hapus-box { background: white; padding: 30px; border-radius: 16px; width: 380px; text-align: center; box-shadow: 0 10px 25px rgba(0,0,0,0.15); animation: popIn 0.3s ease-out; }
        .hapus-icon { width: 70px; height: 70px; background: #fee2e2; color: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 35px; margin: 0 auto 20px auto; font-weight: bold; }
        .hapus-box h3 { color: #0f172a; margin-bottom: 10px; font-size: 20px; font-weight: 700; }
        .hapus-box p { color: #64748b; font-size: 14px; margin-bottom: 30px; line-height: 1.5; }
        .hapus-btns { display: flex; justify-content: center; gap: 12px; }
        .hapus-btn { padding: 12px 20px; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; border: none; flex: 1; transition: 0.2s; display: flex; align-items: center; justify-content: center; }
        .btn-batal { background: #f1f5f9; color: #475569; }
        .btn-batal:hover { background: #e2e8f0; }
        .btn-yakin { background: #ef4444; color: white; }
        .btn-yakin:hover { background: #dc2626; }

        /* TOAST ALERT CUSTOM */
        .toast { position: fixed; top: 20px; right: 20px; padding: 15px 25px; border-radius: 10px; color: white; font-weight: 600; font-size: 14px; z-index: 9999; box-shadow: 0 10px 25px rgba(0,0,0,0.2); animation: slideIn 0.4s, slideOut 0.4s 4s forwards; }
        .toast.success { background-color: #10b981; }
        .toast.error { background-color: #ef4444; }

        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes popIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        @keyframes slideIn { from { transform: translateX(100%); } to { transform: translateX(0); } }
        @keyframes slideOut { from { transform: translateX(0); } to { transform: translateX(150%); } }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-header">
            <h3>PT Citra Buana 8</h3>
            <p>Inventory System v3.5</p>
        </div>
        <ul class="menu">
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="data_barang.php">Data Barang</a></li>
            <li><a href="barang_masuk.php">Barang Masuk</a></li>
            <li><a href="barang_keluar.php">Barang Keluar</a></li>
            <li><a href="laporan.php">Laporan</a></li>
            <li><a href="pengaturan.php" class="active">Pengaturan</a></li>
        </ul>
        <ul class="menu" style="flex: 0; border-top: 1px solid rgba(255,255,255,0.05);">
            <li><a href="logout.php" style="color: #ef4444;">Keluar</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="topbar">
            <div class="topbar-left">
                <h2>Pengaturan Sistem</h2>
                <span id="realtime-clock">Memuat waktu...</span>
            </div>
            <div class="topbar-right">
                <div class="user-info">
                    <strong><?= htmlspecialchars($me['nama_lengkap']) ?></strong>
                    <span><?= ucfirst($me['role']) ?> Gudang</span>
                </div>
                <div class="avatar"><?= $avatar_html ?></div>
            </div>
        </div>

        <div class="settings-grid">
            <div class="card profile-panel">
                <div class="profile-avatar-large"><?= $avatar_html ?></div>
                
                <h3><?= htmlspecialchars($me['nama_lengkap']) ?></h3>
                <p><?= ucfirst($me['role']) ?> Gudang</p>
                
                <button onclick="bukaModal('modalProfil')" class="btn-outline">Ganti Foto & Nama</button>
                <button onclick="bukaModal('modalSandi')" class="btn-outline btn-outline-red">Ubah Kata Sandi</button>
            </div>

            <div class="card">
                <form action="" method="POST">
                    <div class="card-header">Informasi Perusahaan</div>
                    
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label>Nama Perusahaan</label>
                        <input type="text" name="nama_perusahaan" value="<?= htmlspecialchars($pt['nama_perusahaan']) ?>" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Email Kontak</label>
                            <input type="email" name="email_kontak" value="<?= htmlspecialchars($pt['email_kontak']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Nomor Telepon</label>
                            <input type="text" name="no_telp_pt" value="<?= htmlspecialchars($pt['no_telp']) ?>" required>
                        </div>
                    </div>

                    <div class="card-header" style="margin-top: 40px;">Preferensi Sistem</div>
                    
                    <div class="preference-row">
                        <div class="pref-info">
                            <h4>Peringatan Stok Menipis</h4>
                            <p>Tampilkan peringatan jika stok barang di bawah ambang batas.</p>
                        </div>
                        <div class="toggle-wrapper">
                            <span class="toggle-status" id="statusTeks"><?= ($pt['stok_menipis_aktif'] == 1) ? 'Aktif' : 'Nonaktif' ?></span>
                            <label class="switch">
                                <input type="checkbox" name="stok_menipis_aktif" id="stokToggle" <?= ($pt['stok_menipis_aktif'] == 1) ? 'checked' : '' ?> onchange="updateToggleText()">
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>

                    <div style="text-align: right;">
                        <button type="submit" name="simpan_perusahaan" class="btn-blue">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                Manajemen Akun & Staff
                <button onclick="bukaModal('modalStaff')" class="btn-blue">+ Tambah Staff</button>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Nama Pegawai</th>
                        <th>Jabatan</th>
                        <th>Email/Username</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    while($row = mysqli_fetch_assoc($q_staff)) { 
                        $status_current = isset($row['status_akun']) ? $row['status_akun'] : 'Disetujui';
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($row['nama_lengkap']) ?></strong>
                            <?= ($row['id_user'] == $id_user_login) ? '<span style="color:#3b82f6; font-size:12px;">(Anda)</span>' : '' ?>
                        </td>
                        <td>
                            <?php if($row['role'] == 'admin') { ?>
                                <span class="badge-role admin">Admin Gudang</span>
                            <?php } else { ?>
                                <span class="badge-role">Staff Operasional</span>
                            <?php } ?>
                        </td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td>
                            <?php if($status_current == 'Menunggu Persetujuan') { ?>
                                <span class="status-badge pending">Menunggu ACC</span>
                            <?php } else { ?>
                                <span class="status-badge disetujui">Aktif</span>
                            <?php } ?>
                        </td>
                        <td class="action-btns">
                            <?php if($status_current == 'Menunggu Persetujuan' && $me['role'] == 'admin') { ?>
                                <button type="button" class="btn-action btn-acc" onclick="window.location.href='pengaturan.php?acc_user=<?= $row['id_user'] ?>'">Setujui</button>
                            <?php } ?>

                            <?php if($me['role'] == 'admin' || $row['id_user'] == $id_user_login) { ?>
                                <button type="button" class="btn-action btn-edit" onclick="bukaModalEditStaff('<?= $row['id_user'] ?>', '<?= htmlspecialchars($row['nama_lengkap'], ENT_QUOTES) ?>', '<?= $row['role'] ?>', '<?= htmlspecialchars($row['email'], ENT_QUOTES) ?>')">Edit</button>
                                
                                <button type="button" class="btn-action btn-key" onclick="bukaModalSandiStaff('<?= $row['id_user'] ?>', '<?= htmlspecialchars($row['nama_lengkap'], ENT_QUOTES) ?>')">Sandi</button>
                            <?php } ?>

                            <?php if($row['id_user'] != $id_user_login && $me['role'] == 'admin') { ?>
                                <button type="button" class="btn-action btn-delete" onclick="bukaModalHapusStaff('<?= $row['id_user'] ?>')">Hapus</button>
                            <?php } elseif($row['id_user'] != $id_user_login && $me['role'] != 'admin' && $status_current != 'Menunggu Persetujuan') { ?>
                                <span style="color:#94a3b8; font-size:13px;">-</span>
                            <?php } ?>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal-overlay" id="modalProfil">
        <div class="modal-box">
            <div class="modal-header">
                <h3>Edit Profil</h3>
                <button class="close-btn" onclick="tutupModal('modalProfil')">&times;</button>
            </div>
            <span class="modal-subtitle">Sesuaikan nama dan foto profil Anda.</span>
            
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" value="<?= htmlspecialchars($me['nama_lengkap']) ?>" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Nomor Telepon</label>
                        <input type="text" name="no_telp" value="<?= htmlspecialchars($me['no_telp']) ?>" placeholder="Contoh: 0812...">
                    </div>
                </div>

                <div class="form-group">
                    <label>Upload Foto Baru (Opsional)</label>
                    <input type="file" name="foto_profil" accept="image/png, image/jpeg, image/jpg">
                </div>

                <div class="modal-footer">
                    <button type="submit" name="simpan_profil" class="btn-blue btn-full">Simpan Profil & Foto</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="modalSandi">
        <div class="modal-box">
            <div class="modal-header">
                <h3 style="text-align: center; width: 100%;">Ubah Kata Sandi</h3>
                <button class="close-btn" onclick="tutupModal('modalSandi')">&times;</button>
            </div>
            <span class="modal-subtitle" style="text-align: center;">Pastikan kata sandi baru Anda aman dan mudah diingat.</span>
            
            <form action="" method="POST">
                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Kata Sandi Lama</label>
                    <input type="password" name="sandi_lama" placeholder="Masukkan sandi saat ini" required>
                </div>
                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Kata Sandi Baru</label>
                    <input type="password" name="sandi_baru" placeholder="Buat sandi baru" required>
                </div>
                <div class="form-group">
                    <label>Konfirmasi Sandi Baru</label>
                    <input type="password" name="sandi_konfirmasi" placeholder="Ulangi sandi baru" required>
                </div>

                <div class="modal-footer">
                    <button type="submit" name="simpan_sandi" class="btn-blue btn-full">Perbarui Sandi</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="modalStaff">
        <div class="modal-box">
            <div class="modal-header">
                <h3 style="text-align: center; width: 100%;">Tambah Staff Baru</h3>
                <button class="close-btn" onclick="tutupModal('modalStaff')">&times;</button>
            </div>
            <span class="modal-subtitle" style="text-align: center;">Daftarkan akun untuk anggota tim gudang.</span>
            
            <form action="" method="POST">
                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Nama Pegawai</label>
                    <input type="text" name="nama_pegawai" placeholder="Cth: Cecep" required>
                </div>
                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Jabatan (Role)</label>
                    <select name="jabatan" required>
                        <option value="staff">Staff Operasional</option>
                        <option value="admin">Admin Gudang</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Email / Username (Untuk Login)</label>
                    <input type="text" name="email_username" placeholder="Cth: cecep@citrabuana.com" required>
                </div>
                <div class="form-group">
                    <label>Kata Sandi Akun</label>
                    <input type="password" name="kata_sandi" placeholder="Masukkan kata sandi..." required>
                </div>

                <div class="modal-footer">
                    <button type="submit" name="tambah_staff" class="btn-blue btn-full">Simpan Data Staff</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="modalEditStaff">
        <div class="modal-box">
            <div class="modal-header">
                <h3 style="text-align: center; width: 100%;">Edit Data Staff</h3>
                <button class="close-btn" onclick="tutupModal('modalEditStaff')">&times;</button>
            </div>
            <span class="modal-subtitle" style="text-align: center;">Perbarui informasi anggota tim.</span>
            
            <form action="" method="POST">
                <input type="hidden" name="id_user_edit" id="edit_id_user">
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Nama Pegawai</label>
                    <input type="text" name="nama_pegawai_edit" id="edit_nama_pegawai" required>
                </div>
                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Jabatan (Role)</label>
                    <select name="jabatan_edit" id="edit_jabatan" required>
                        <option value="staff">Staff Operasional</option>
                        <option value="admin">Admin Gudang</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Email / Username (Untuk Login)</label>
                    <input type="text" name="email_username_edit" id="edit_email_username" required>
                </div>

                <div class="modal-footer">
                    <button type="submit" name="edit_staff" class="btn-blue btn-full">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="modalSandiStaff">
        <div class="modal-box">
            <div class="modal-header">
                <h3 style="text-align: center; width: 100%;">Reset Sandi Akun</h3>
                <button class="close-btn" onclick="tutupModal('modalSandiStaff')">&times;</button>
            </div>
            <span class="modal-subtitle" style="text-align: center;">Atur ulang kata sandi login untuk <strong id="nama_staff_sandi"></strong>.</span>
            
            <form action="" method="POST">
                <input type="hidden" name="id_user_sandi_edit" id="edit_id_user_sandi">
                
                <div class="form-group" style="margin-bottom: 20px; position: relative;">
                    <label>Kata Sandi Baru</label>
                    <input type="password" name="sandi_baru_staff" id="input_sandi_baru_staff" placeholder="Ketik sandi baru..." required style="padding-right: 70px;">
                    <button type="button" class="toggle-pw-btn" onclick="togglePasswordInput('input_sandi_baru_staff', this)">LIHAT</button>
                </div>
                <div class="form-group" style="position: relative;">
                    <label>Konfirmasi Sandi Baru</label>
                    <input type="password" name="sandi_konfirmasi_staff" id="input_sandi_konf_staff" placeholder="Ulangi sandi baru..." required style="padding-right: 70px;">
                    <button type="button" class="toggle-pw-btn" onclick="togglePasswordInput('input_sandi_konf_staff', this)">LIHAT</button>
                </div>

                <div class="modal-footer">
                    <button type="submit" name="edit_sandi_staff" class="btn-blue btn-full">Simpan Sandi Baru</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalHapusStaff" class="modal-overlay">
        <div class="hapus-box">
            <div class="hapus-icon">!</div>
            <h3>Hapus Akun Staff?</h3>
            <p>Akun ini akan dihapus permanen dan tidak dapat mengakses sistem lagi.</p>
            <div class="hapus-btns">
                <button onclick="tutupModalHapusStaff()" class="hapus-btn btn-batal">Batal</button>
                <a href="#" id="linkHapusStaff" class="hapus-btn btn-yakin">Ya, Hapus</a>
            </div>
        </div>
    </div>

    <?php if(isset($_GET['status'])): ?>
        <div class="toast success"><?= htmlspecialchars($_GET['status']) ?></div>
        <script>window.history.replaceState(null, null, window.location.pathname);</script>
    <?php endif; ?>

    <?php if(isset($_GET['error'])): ?>
        <div class="toast error"><?= htmlspecialchars($_GET['error']) ?></div>
        <script>window.history.replaceState(null, null, window.location.pathname);</script>
    <?php endif; ?>

    <script>
        function updateClock() {
            const now = new Date();
            const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            document.getElementById('realtime-clock').innerText = `${days[now.getDay()]}, ${String(now.getDate()).padStart(2, '0')} ${months[now.getMonth()]} ${now.getFullYear()} pukul ${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}:${String(now.getSeconds()).padStart(2, '0')} WIB`;
        }
        setInterval(updateClock, 1000); updateClock();

        function updateToggleText() {
            const toggle = document.getElementById('stokToggle');
            const teks = document.getElementById('statusTeks');
            teks.innerText = toggle.checked ? 'Aktif' : 'Nonaktif';
        }

        function bukaModal(id) { document.getElementById(id).style.display = 'flex'; }
        function tutupModal(id) { document.getElementById(id).style.display = 'none'; }

        function bukaModalEditStaff(id, nama, role, email) {
            document.getElementById('edit_id_user').value = id;
            document.getElementById('edit_nama_pegawai').value = nama;
            document.getElementById('edit_jabatan').value = role;
            document.getElementById('edit_email_username').value = email;
            document.getElementById('modalEditStaff').style.display = 'flex';
        }

        function bukaModalSandiStaff(id, nama) {
            document.getElementById('edit_id_user_sandi').value = id;
            document.getElementById('nama_staff_sandi').innerText = nama;
            document.getElementById('modalSandiStaff').style.display = 'flex';
            
            // Reset form
            document.getElementById('input_sandi_baru_staff').value = '';
            document.getElementById('input_sandi_konf_staff').value = '';
            document.getElementById('input_sandi_baru_staff').type = 'password';
            document.getElementById('input_sandi_konf_staff').type = 'password';
            document.querySelectorAll('.toggle-pw-btn').forEach(btn => btn.innerText = 'LIHAT');
        }

        function togglePasswordInput(inputId, btn) {
            const input = document.getElementById(inputId);
            if (input.type === 'password') {
                input.type = 'text';
                btn.innerText = 'TUTUP';
            } else {
                input.type = 'password';
                btn.innerText = 'LIHAT';
            }
        }

        function bukaModalHapusStaff(idUser) {
            document.getElementById('modalHapusStaff').style.display = 'flex';
            document.getElementById('linkHapusStaff').href = 'pengaturan.php?hapus_user=' + idUser;
        }
        function tutupModalHapusStaff() {
            document.getElementById('modalHapusStaff').style.display = 'none';
        }
    </script>
</body>
</html>