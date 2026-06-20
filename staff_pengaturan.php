<?php
session_start();
require 'koneksi.php'; // Menggunakan $koneksi

// Proteksi: Pastikan yang masuk benar-benar staff
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'staff') {
    header("Location: login.php?pesan=akses_ditolak");
    exit();
}

$id_user = $_SESSION['id_user'];

// --- PROSES UPDATE NAMA & FOTO PROFIL ---
if (isset($_POST['update_profil'])) {
    $nama_baru = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap']);
    $query_update = "UPDATE users SET nama_lengkap='$nama_baru' WHERE id_user='$id_user'";

    // Cek jika ada file foto yang diupload
    if (!empty($_FILES['foto_profil']['name'])) {
        $nama_file = $_FILES['foto_profil']['name'];
        $tmp_file = $_FILES['foto_profil']['tmp_name'];
        $ext = pathinfo($nama_file, PATHINFO_EXTENSION);
        $nama_foto_baru = "staff_" . $id_user . "_" . time() . "." . $ext;
        
        if (move_uploaded_file($tmp_file, 'uploads/' . $nama_foto_baru)) {
            $query_update = "UPDATE users SET nama_lengkap='$nama_baru', foto_profil='$nama_foto_baru' WHERE id_user='$id_user'";
        }
    }

    if (mysqli_query($koneksi, $query_update)) {
        $_SESSION['nama_lengkap'] = $nama_baru; // Update session agar topbar langsung berubah
        header("Location: staff_pengaturan.php?pesan=profil_sukses");
        exit();
    }
}

// --- PROSES UPDATE KATA SANDI ---
if (isset($_POST['update_sandi'])) {
    $sandi_baru = mysqli_real_escape_string($koneksi, $_POST['sandi_baru']);
    $sandi_hash = password_hash($sandi_baru, PASSWORD_DEFAULT);
    
    if (mysqli_query($koneksi, "UPDATE users SET password='$sandi_hash' WHERE id_user='$id_user'")) {
        header("Location: staff_pengaturan.php?pesan=sandi_sukses");
        exit();
    }
}

// Mengambil Data User Saat Ini
$q_me = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user='$id_user'");
$me = mysqli_fetch_assoc($q_me);

// Bikin Inisial & Avatar HTML
$inisial = 'ST';
if (!empty($me['nama_lengkap'])) {
    $words = explode(" ", $me['nama_lengkap']);
    $inisial = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
}
$avatar_html = $inisial;
if(!empty($me['foto_profil']) && file_exists('uploads/' . $me['foto_profil'])) {
    $foto_url = 'uploads/' . $me['foto_profil'];
    $avatar_html = "<img src='$foto_url' alt='Profil' style='width: 100%; height: 100%; object-fit: cover;'>";
}

// Mengambil Data Pengaturan Perusahaan (HANYA UNTUK DITAMPILKAN / READ-ONLY)
$q_pengaturan = mysqli_query($koneksi, "SELECT * FROM pengaturan_perusahaan WHERE id=1");
$pengaturan = mysqli_fetch_assoc($q_pengaturan);
$is_stok_alert_active = $pengaturan['stok_menipis_aktif'] ?? 0;

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>Pengaturan Akun - PT Citra Buana Delapan</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { display: flex; background-color: #f8fafc; height: 100vh; overflow: hidden; }
        
        /* --- SIDEBAR STAFF --- */
        .sidebar { width: 250px; background: white; border-right: 1px solid #e2e8f0; display: flex; flex-direction: column; transition: 0.3s; }
        .sidebar-header { display: block; text-align: center; padding: 25px 20px; border-bottom: 1px solid #f1f5f9; }
        .menu { list-style: none; padding: 20px 0; flex: 1; }
        .menu a { display: block; padding: 12px 20px; color: #64748b; text-decoration: none; font-size: 14px; font-weight: 500; }
        .menu a.active { background: #e0f2fe; color: #0ea5e9; border-left: 4px solid #0ea5e9; }
        
        /* --- MAIN CONTENT & TOPBAR --- */
        .main-content { flex: 1; display: flex; flex-direction: column; overflow-y: auto; padding: 30px; }
        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #e2e8f0; }
        .topbar-left { display: flex; align-items: baseline; gap: 15px; }
        .topbar-left h2 { font-size: 24px; color: #0f172a; font-weight: 700; }
        .topbar-right { display: flex; align-items: center; gap: 15px; }
        .user-info { text-align: right; }
        .user-info strong { display: block; font-size: 14px; color: #0f172a; }
        .user-info span { font-size: 12px; color: #94a3b8; }
        .avatar { width: 42px; height: 42px; background-color: #0ea5e9; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 15px; overflow: hidden; }
        
        /* --- GRID LAYOUT PENGATURAN --- */
        .settings-grid { display: grid; grid-template-columns: 1fr 2.5fr; gap: 25px; margin-bottom: 25px; }
        .card { background: white; padding: 30px; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #e2e8f0; }
        .card-header { font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        
        /* --- PROFIL PANEL (KIRI) --- */
        .profile-panel { text-align: center; }
        .profile-avatar-large { width: 120px; height: 120px; background-color: #0ea5e9; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 40px; font-weight: bold; margin: 0 auto 20px auto; box-shadow: 0 10px 20px rgba(14,165,233,0.2); overflow: hidden;}
        .profile-panel h3 { font-size: 20px; color: #0f172a; margin-bottom: 5px; }
        .profile-panel p { color: #64748b; font-size: 14px; margin-bottom: 25px; }
        .btn-outline { display: block; width: 100%; padding: 12px; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; background: white; border: 1px solid #cbd5e1; color: #334155; margin-bottom: 15px; transition: 0.2s;}
        .btn-outline:hover { background: #f8fafc; border-color: #94a3b8; }
        .btn-outline-red { border-color: #fca5a5; color: #ef4444; }
        .btn-outline-red:hover { background: #fef2f2; border-color: #ef4444; }

        /* --- FORM INPUTS --- */
        .form-row { display: flex; gap: 20px; margin-bottom: 20px; }
        .form-row .form-group { flex: 1; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 8px; }
        .form-group input, .form-group select { width: 100%; padding: 12px 16px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 14px; outline: none; background: #f8fafc; color: #0f172a; transition: 0.3s; }
        .form-group input:focus, .form-group select:focus { border-color: #0ea5e9; background: white; box-shadow: 0 0 0 3px rgba(14,165,233,0.1); }
        .input-readonly { background: #e2e8f0 !important; cursor: not-allowed; color: #64748b !important; }

        /* --- TOGGLE SWITCH CSS --- */
        .preference-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #f1f5f9;}
        .pref-info h4 { font-size: 14px; color: #0f172a; margin-bottom: 5px;}
        .pref-info p { font-size: 13px; color: #64748b;}
        .toggle-wrapper { display: flex; align-items: center; gap: 10px;}
        .toggle-status { font-size: 13px; font-weight: 600; color: #64748b; }
        .switch { position: relative; display: inline-block; width: 44px; height: 24px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: not-allowed; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .4s; border-radius: 34px; opacity: 0.7;}
        .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: #0ea5e9; }
        input:checked + .slider:before { transform: translateX(20px); }

        /* --- MODAL STYLES --- */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15,23,42,0.6); backdrop-filter: blur(3px); z-index: 100; justify-content: center; align-items: center; animation: fadeIn 0.2s;}
        .modal-box { background: white; padding: 30px; border-radius: 16px; width: 500px; box-shadow: 0 20px 40px rgba(0,0,0,0.15); animation: popIn 0.3s; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px; }
        .modal-header h3 { font-size: 20px; color: #0f172a; font-weight: 700; }
        .modal-subtitle { font-size: 13px; color: #64748b; margin-bottom: 25px; display: block;}
        .close-btn { background: none; border: none; font-size: 24px; color: #94a3b8; cursor: pointer; transition: 0.2s; }
        .close-btn:hover { color: #0f172a; }
        .modal-footer { margin-top: 30px; display: flex; gap: 10px; }
        .btn-full { width: 100%; display: block; padding: 12px; background: #0ea5e9; color: white; border: none; border-radius: 10px; font-weight: bold; cursor: pointer;}
        .btn-full:hover { background: #0284c7; }

        /* --- TOAST ALERT --- */
        .toast { position: fixed; top: 20px; right: 20px; padding: 15px 25px; border-radius: 10px; color: white; font-weight: 600; font-size: 14px; z-index: 9999; box-shadow: 0 10px 25px rgba(0,0,0,0.2); animation: slideIn 0.4s, slideOut 0.4s 4s forwards; display: none; }
        .toast.success { background-color: #10b981; }
        
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes popIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        @keyframes slideIn { from { transform: translateX(100%); } to { transform: translateX(0); } }
        @keyframes slideOut { from { transform: translateX(0); } to { transform: translateX(150%); } }

        @media (max-width: 768px) {
            body { flex-direction: column; overflow-y: auto; }
            .sidebar { width: 100%; height: auto; border-right: none; border-bottom: 1px solid #e2e8f0; }
            .settings-grid { grid-template-columns: 1fr; }
            .form-row { flex-direction: column; gap: 0; }
        }
    </style>
</head>
<body>

    <?php if(isset($_GET['pesan']) && ($_GET['pesan'] == 'profil_sukses' || $_GET['pesan'] == 'sandi_sukses')): ?>
    <div class="toast success" style="display: block;">
        ✓ Perubahan akun berhasil disimpan!
    </div>
    <?php endif; ?>

    <div class="sidebar">
        <div class="sidebar-header">
            <h3 style="font-size: 18px; color: #0ea5e9; letter-spacing: 1px;">PT Citra Buana 8</h3>
            <p style="font-size: 12px; color: #64748b; margin-top: 5px;">Inventory System v3.5</p>
        </div>
        
        <ul class="menu">
            <li><a href="staff_dashboard.php">Beranda Operasional</a></li>
            <li><a href="cek_stok.php">Cek Stok Gudang</a></li>
            <li><a href="input_barang.php">Input Barang Masuk/Keluar</a></li>
            <li><a href="staff_pengaturan.php" class="active">Pengaturan Akun</a></li>
        </ul>
        <div style="margin-top: auto; padding: 20px;">
            <a href="logout.php" style="color: #ef4444; text-decoration: none; font-size: 14px; font-weight: bold;">Keluar Aplikasi</a>
        </div>
    </div>

    <div class="main-content">
        <div class="topbar">
            <div class="topbar-left">
                <h2>Pengaturan Akun</h2>
            </div>
            <div class="topbar-right">
                <div class="user-info">
                    <strong><?= htmlspecialchars($me['nama_lengkap']) ?></strong>
                    <span><?= ucfirst($me['role']) ?> Operasional</span>
                </div>
                <div class="avatar"><?= $avatar_html ?></div>
            </div>
        </div>

        <div class="settings-grid">
            <div class="card profile-panel">
                <div class="profile-avatar-large">
                    <?= $avatar_html ?>
                </div>
                <h3><?= htmlspecialchars($me['nama_lengkap']) ?></h3>
                <p><?= ucfirst($me['role']) ?> Gudang</p>
                
                <button onclick="bukaModal('modalEditProfil')" class="btn-outline">Ganti Foto & Nama</button>
                <button onclick="bukaModal('modalUbahSandi')" class="btn-outline btn-outline-red">Ubah Kata Sandi</button>
            </div>

            <div class="card">
                <div class="card-header">Informasi Perusahaan (Read-Only)</div>
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Nama Perusahaan</label>
                    <input type="text" class="input-readonly" value="<?= htmlspecialchars($pengaturan['nama_perusahaan'] ?? 'PT Citra Buana Delapan') ?>" readonly>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Email Kontak</label>
                        <input type="email" class="input-readonly" value="<?= htmlspecialchars($pengaturan['email_kontak'] ?? 'admin@citrabuana.com') ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Nomor Telepon</label>
                        <input type="text" class="input-readonly" value="<?= htmlspecialchars($pengaturan['no_telepon'] ?? '0812 9998 9695') ?>" readonly>
                    </div>
                </div>

                <div class="card-header" style="margin-top: 40px;">Preferensi Sistem (Read-Only)</div>
                <div class="preference-row">
                    <div class="pref-info">
                        <h4>Peringatan Stok Menipis</h4>
                        <p>Status fitur peringatan otomatis di Dashboard jika stok menipis.</p>
                    </div>
                    <div class="toggle-wrapper">
                        <span class="toggle-status"><?= $is_stok_alert_active == 1 ? 'Aktif' : 'Nonaktif' ?></span>
                        <label class="switch">
                            <input type="checkbox" <?= $is_stok_alert_active == 1 ? 'checked' : '' ?> disabled>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>
                <p style="font-size: 12px; color: #94a3b8; text-align: right; margin-top: -10px;">*Hanya Admin yang dapat mengubah Informasi Perusahaan & Sistem.</p>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="modalEditProfil">
        <div class="modal-box">
            <div class="modal-header">
                <h3>Edit Profil Akun</h3>
                <button class="close-btn" onclick="tutupModal('modalEditProfil')">&times;</button>
            </div>
            <span class="modal-subtitle">Perbarui nama lengkap dan foto profil Anda.</span>
            
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" value="<?= htmlspecialchars($me['nama_lengkap']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Foto Profil (Opsional)</label>
                    <input type="file" name="foto_profil" accept="image/*" style="border: 1px dashed #cbd5e1; padding: 20px; background: #f8fafc;">
                    <small style="color: #64748b; font-size: 11px; margin-top: 5px; display: block;">Biarkan kosong jika tidak ingin mengubah foto.</small>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="update_profil" class="btn-full">Simpan Profil</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="modalUbahSandi">
        <div class="modal-box">
            <div class="modal-header">
                <h3>Ubah Kata Sandi</h3>
                <button class="close-btn" onclick="tutupModal('modalUbahSandi')">&times;</button>
            </div>
            <span class="modal-subtitle">Buat kata sandi baru untuk akun Anda.</span>
            
            <form action="" method="POST">
                <div class="form-group">
                    <label>Kata Sandi Baru</label>
                    <input type="text" name="sandi_baru" placeholder="Masukkan sandi baru..." required minlength="4">
                </div>
                <div class="modal-footer">
                    <button type="submit" name="update_sandi" class="btn-full" style="background: #ef4444;">Update Kata Sandi</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function bukaModal(id) {
            document.getElementById(id).style.display = 'flex';
        }
        function tutupModal(id) {
            document.getElementById(id).style.display = 'none';
        }
    </script>
</body>
</html>