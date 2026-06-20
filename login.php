<?php
session_start();
include 'koneksi.php';

// Jika sudah login, arahkan ke dashboard
if (isset($_SESSION["login"])) {
    header("Location: dashboard.php");
    exit;
}

// Cek apakah kolom status_akun sudah ada sebelum menambahkannya (Mencegah Fatal Error)
$cek_kolom = mysqli_query($koneksi, "SHOW COLUMNS FROM users LIKE 'status_akun'");
if(mysqli_num_rows($cek_kolom) == 0) {
    mysqli_query($koneksi, "ALTER TABLE users ADD COLUMN status_akun VARCHAR(30) DEFAULT 'Disetujui'");
}

// ==========================================
// LOGIKA PROSES LUPA SANDI (TETAP DI SINI)
// ==========================================
if(isset($_POST['reset_sandi'])) {
    $username_reset = mysqli_real_escape_string($koneksi, $_POST['username_reset']);
    $sandi_baru = $_POST['sandi_baru'];
    $sandi_konfirmasi = $_POST['sandi_konfirmasi'];

    // Cek apakah akun ada di database
    $cek_akun = mysqli_query($koneksi, "SELECT id_user FROM users WHERE username='$username_reset' OR email='$username_reset'");
    
    if(mysqli_num_rows($cek_akun) > 0) {
        if($sandi_baru === $sandi_konfirmasi) {
            $hash_baru = password_hash($sandi_baru, PASSWORD_DEFAULT);
            mysqli_query($koneksi, "UPDATE users SET password='$hash_baru' WHERE username='$username_reset' OR email='$username_reset'");
            $sukses_reset = true; 
        } else {
            $error_reset = "Konfirmasi sandi baru tidak cocok!";
            $buka_modal_reset = true; 
        }
    } else {
        $error_reset = "Email atau Username tidak ditemukan di sistem!";
        $buka_modal_reset = true;
    }
}

// ==========================================
// TANGKAP PESAN DARI PROSES LOGIN
// ==========================================
if(isset($_GET['pesan'])) {
    if($_GET['pesan'] == 'error') {
        $error = true;
    } else if($_GET['pesan'] == 'akses_ditolak') {
        $akses_ditolak = true;
        $role_dipilih = $_GET['role_dipilih'];
        $role_asli = $_GET['role_asli'];
    } else if($_GET['pesan'] == 'belum_diacc') {
        $belum_diacc = true;
        $nama_pendaftar = $_GET['nama'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PT Citra Buana Delapan</title>
    <style>
        /* (PASTE SEMUA KODE CSS LAMA LU DI SINI - TIDAK ADA YANG GW UBAH) */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; display: flex; height: 100vh; overflow: hidden; background-color: #f1f5f9; }
        .left-panel { background-color: #172134; width: 50%; display: flex; flex-direction: column; align-items: center; justify-content: center; position: relative; }
        .logo-card { background: white; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.3); overflow: hidden; width: 80%; max-width: 450px; }
        .logo-card img { width: 100%; display: block; }
        .version-badge { margin-top: 25px; background-color: rgba(255, 255, 255, 0.1); color: #d1d5db; padding: 8px 24px; border-radius: 20px; border: 1px solid rgba(255, 255, 255, 0.2); font-size: 14px; }
        .footer-text { position: absolute; bottom: 30px; color: #64748b; font-size: 14px; }
        .right-panel { background-color: #ffffff; width: 50%; display: flex; align-items: center; justify-content: center; position: relative; }
        .login-container { width: 100%; max-width: 420px; padding: 20px; }
        .login-container h2 { color: #0f172a; font-size: 28px; margin-bottom: 8px; }
        .login-container p { color: #64748b; font-size: 15px; margin-bottom: 30px; }
        .role-toggle { display: flex; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 25px; padding: 4px; }
        .role-btn { flex: 1; padding: 10px; text-align: center; font-size: 14px; font-weight: 600; color: #64748b; cursor: pointer; border-radius: 6px; transition: all 0.3s ease; }
        .role-btn.active { background-color: #ffffff; color: #0f172a; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 14px; font-weight: 600; color: #0f172a; margin-bottom: 8px; }
        .form-group input { width: 100%; padding: 12px 16px; border: 1px solid #e2e8f0; border-radius: 8px; background-color: #f0f4f8; font-size: 14px; outline: none; transition: border-color 0.3s; }
        .form-group input:focus { border-color: #3b82f6; background-color: #ffffff; }
        .form-actions { display: flex; justify-content: space-between; align-items: center; font-size: 14px; margin-bottom: 30px; position: relative; z-index: 50; }
        .form-actions a { color: #3b82f6; text-decoration: none; font-weight: 600; position: relative; z-index: 50; cursor: pointer;}
        .form-actions a:hover { text-decoration: underline; }
        .form-actions a.text-gray { color: #94a3b8; font-weight: normal; }
        .btn-submit { width: 100%; padding: 14px; background-color: #111827; color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; transition: background-color 0.3s; position: relative; z-index: 10; }
        .btn-submit:hover { background-color: #000000; }
        .error-msg { color: #ef4444; font-size: 13px; font-weight: 600; margin-bottom: 15px; text-align: center; }
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15,23,42,0.6); backdrop-filter: blur(3px); z-index: 100; justify-content: center; align-items: center; animation: fadeIn 0.2s; }
        .modal-box { background: white; padding: 30px; border-radius: 16px; width: 400px; box-shadow: 0 20px 40px rgba(0,0,0,0.15); animation: popIn 0.3s; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px; }
        .modal-header h3 { font-size: 20px; color: #0f172a; font-weight: 700; margin: 0;}
        .close-btn { background: none; border: none; font-size: 24px; color: #94a3b8; cursor: pointer; transition: 0.2s; }
        .close-btn:hover { color: #0f172a; }
        @media (max-width: 768px) { body { flex-direction: column; overflow: auto; } .left-panel, .right-panel { width: 100%; height: 50vh; } .left-panel { padding: 40px 20px; } .right-panel { height: auto; padding: 40px 20px; } .logo-card { width: 100%; } }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes popIn { from { transform: scale(0.8); opacity: 0; } to { transform: scale(1); opacity: 1; } }
    </style>
</head>
<body>

    <div class="left-panel">
        <div class="logo-card">
            <img src="logo.png" alt="PT Citra Buana Delapan">
        </div>
        <div class="version-badge">Inventory v3.5</div>
        <div class="footer-text">&copy; 2026 PT Citra Buana Delapan</div>
    </div>

    <div class="right-panel">
        <div class="login-container">
            <h2>Masuk ke akun Anda</h2>
            <p>Silakan pilih peran dan masukkan kredensial Anda</p>

            <?php if(isset($error)): ?>
                <div class="error-msg">Email/Username atau Kata Sandi salah!</div>
            <?php endif; ?>

            <form action="proses_login.php" method="POST">
                
                <div class="role-toggle">
                    <div class="role-btn active" id="btn-admin">Admin Gudang</div>
                    <div class="role-btn" id="btn-staff">Pengguna (Staff)</div>
                    <input type="hidden" name="role" id="input-role" value="admin">
                </div>

                <div class="form-group">
                    <label for="username">Email / Username</label>
                    <input type="text" id="username" name="username" placeholder="admin@citrabuana.com" required>
                </div>

                <div class="form-group">
                    <label for="password">Kata Sandi</label>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                </div>

                <div class="form-actions">
                    <a href="daftar.php">Belum punya akun? Daftar</a>
                    <a onclick="document.getElementById('modalLupaSandi').style.display='flex';" class="text-gray">Lupa sandi?</a>
                </div>

                <button type="submit" name="login" class="btn-submit">Masuk ke Sistem</button>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="modalLupaSandi" style="<?php echo isset($buka_modal_reset) ? 'display:flex;' : ''; ?>">
        <div class="modal-box">
            <div class="modal-header">
                <h3>Lupa Kata Sandi</h3>
                <button class="close-btn" onclick="document.getElementById('modalLupaSandi').style.display='none';">&times;</button>
            </div>
            <p style="color: #64748b; font-size: 13px; margin-bottom: 20px;">Masukkan email/username Anda untuk mereset kata sandi.</p>
            
            <?php if(isset($error_reset)): ?>
                <div class="error-msg" style="background:#fee2e2; padding:8px; border-radius:6px;"><?= $error_reset ?></div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Email / Username Akun</label>
                    <input type="text" name="username_reset" placeholder="Masukkan email atau username" required>
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Kata Sandi Baru</label>
                    <input type="password" name="sandi_baru" placeholder="Buat sandi baru" required>
                </div>
                <div class="form-group" style="margin-bottom: 25px;">
                    <label>Konfirmasi Sandi Baru</label>
                    <input type="password" name="sandi_konfirmasi" placeholder="Ulangi sandi baru" required>
                </div>
                <button type="submit" name="reset_sandi" class="btn-submit" style="background-color: #3b82f6;">Simpan Sandi Baru</button>
            </form>
        </div>
    </div>

    <?php if(isset($sukses_reset)): ?>
    <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15,23,42,0.8); backdrop-filter: blur(4px); display: flex; justify-content: center; align-items: center; z-index: 9999; animation: fadeIn 0.3s ease-out;">
        <div style="background: white; width: 400px; padding: 40px 30px; border-radius: 16px; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.3); animation: popIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);">
            <div style="width: 80px; height: 80px; background-color: #dcfce7; color: #16a34a; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 40px; margin: 0 auto 20px auto;">✓</div>
            <h2 style="color: #0f172a; font-size: 24px; font-weight: 700; margin-bottom: 12px;">Sandi Diperbarui!</h2>
            <p style="color: #64748b; font-size: 14.5px; line-height: 1.6; margin-bottom: 30px;">
                Kata sandi Anda berhasil direset. Silakan login menggunakan kata sandi yang baru.
            </p>
            <button onclick="window.location.href='login.php'" style="background-color: #16a34a; color: white; border: none; padding: 12px 24px; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; width: 100%;">Tutup</button>
        </div>
    </div>
    <?php endif; ?>

    <?php if(isset($akses_ditolak)): ?>
    <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15,23,42,0.8); backdrop-filter: blur(4px); display: flex; justify-content: center; align-items: center; z-index: 9999; animation: fadeIn 0.3s ease-out;">
        <div style="background: white; width: 400px; padding: 40px 30px; border-radius: 16px; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.3); animation: popIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);">
            <div style="width: 80px; height: 80px; background-color: #fee2e2; color: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 40px; margin: 0 auto 20px auto;">🔒</div>
            <h2 style="color: #0f172a; font-size: 24px; font-weight: 700; margin-bottom: 12px;">Akses Ditolak!</h2>
            <p style="color: #64748b; font-size: 14.5px; line-height: 1.6; margin-bottom: 30px;">
                Jabatan tidak sesuai! Anda mencoba masuk sebagai <strong><?= htmlspecialchars($role_dipilih == 'admin' ? 'Admin Gudang' : 'Pengguna (Staff)') ?></strong>, padahal akun ini terdaftar sebagai <strong><?= htmlspecialchars($role_asli == 'admin' ? 'Admin Gudang' : 'Pengguna (Staff)') ?></strong>.
            </p>
            <button onclick="window.location.href='login.php'" style="background-color: #3b82f6; color: white; border: none; padding: 12px 24px; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; width: 100%;">Kembali</button>
        </div>
    </div>
    <?php endif; ?>

    <?php if(isset($belum_diacc)): ?>
    <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15,23,42,0.8); backdrop-filter: blur(4px); display: flex; justify-content: center; align-items: center; z-index: 9999; animation: fadeIn 0.3s ease-out;">
        <div style="background: white; width: 420px; padding: 40px 30px; border-radius: 16px; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.3); animation: popIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);">
            <div style="width: 80px; height: 80px; background-color: #fef9c3; color: #ca8a04; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 40px; margin: 0 auto 20px auto;">⏳</div>
            <h2 style="color: #0f172a; font-size: 22px; font-weight: 700; margin-bottom: 12px;">Akun Belum Aktif</h2>
            <p style="color: #64748b; font-size: 14.5px; line-height: 1.6; margin-bottom: 30px;">
                Halo <strong><?= htmlspecialchars($nama_pendaftar) ?></strong>, pengajuan pendaftaran Anda sedang dalam status <strong>Menunggu Persetujuan</strong>. Silakan hubungi Admin untuk meminta aktivasi akun.
            </p>
            <button onclick="window.location.href='login.php'" style="background-color: #ca8a04; color: white; border: none; padding: 12px 24px; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; width: 100%;">Mengerti</button>
        </div>
    </div>
    <?php endif; ?>

    <script>
        const btnAdmin = document.getElementById('btn-admin');
        const btnStaff = document.getElementById('btn-staff');
        const inputRole = document.getElementById('input-role');

        btnAdmin.addEventListener('click', function() {
            btnAdmin.classList.add('active');
            btnStaff.classList.remove('active');
            inputRole.value = 'admin';
        });

        btnStaff.addEventListener('click', function() {
            btnStaff.classList.add('active');
            btnAdmin.classList.remove('active');
            inputRole.value = 'staff';
        });
    </script>
</body>
</html>