<?php
session_start();
include 'koneksi.php';

if (isset($_SESSION["login"])) { header("Location: dashboard.php"); exit; }

// --- LOGIKA PROSES PENGAJUAN AKUN (STAFF) ---
if (isset($_POST['daftar'])) {
    $nama_lengkap = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap']);
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $email = mysqli_real_escape_string($koneksi, $_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = 'staff'; 
    $status_akun = 'Menunggu Persetujuan'; 

    $cek = mysqli_query($koneksi, "SELECT id_user FROM users WHERE username='$username' OR email='$email'");
    
    if (mysqli_num_rows($cek) > 0) {
        $error_msg = "Gagal! Username atau Email sudah terdaftar.";
    } else {
        $query = "INSERT INTO users (nama_lengkap, username, email, password, role, status_akun) 
                  VALUES ('$nama_lengkap', '$username', '$email', '$password', '$role', '$status_akun')";
        if (mysqli_query($koneksi, $query)) {
            $sukses_daftar = true;
        } else {
            $error_msg = "Database Error: " . mysqli_error($koneksi);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minta Akun - PT Citra Buana Delapan</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;}
        body { display: flex; height: 100vh; overflow: hidden; background-color: #f1f5f9; }
        
        .left-panel { background-color: #172134; width: 50%; display: flex; flex-direction: column; align-items: center; justify-content: center; position: relative; }
        .logo-card { background: white; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.3); overflow: hidden; width: 80%; max-width: 450px; }
        .logo-card img { width: 100%; display: block; }
        .version-badge { margin-top: 25px; background-color: rgba(255, 255, 255, 0.1); color: #d1d5db; padding: 8px 24px; border-radius: 20px; border: 1px solid rgba(255, 255, 255, 0.2); font-size: 14px; }
        .footer-text { position: absolute; bottom: 30px; color: #64748b; font-size: 14px; }

        .right-panel { background-color: #ffffff; width: 50%; display: flex; align-items: center; justify-content: center; overflow-y: auto; padding: 20px; }
        .login-container { width: 100%; max-width: 420px; padding: 20px; }
        .login-container h2 { color: #0f172a; font-size: 28px; margin-bottom: 8px; }
        .login-container p { color: #64748b; font-size: 15px; margin-bottom: 25px; }

        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: #0f172a; margin-bottom: 6px; }
        .form-group input { width: 100%; padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 8px; background-color: #f0f4f8; font-size: 14px; outline: none; transition: border-color 0.3s; }
        .form-group input:focus { border-color: #3b82f6; background-color: #ffffff; }

        .form-actions { display: flex; justify-content: flex-start; align-items: center; font-size: 14px; margin-bottom: 25px; }
        .form-actions a { color: #3b82f6; text-decoration: underline; font-weight: 600; }
        
        .btn-submit { width: 100%; padding: 14px; background-color: #111827; color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; transition: 0.2s; display: block; }
        .btn-submit:hover { background-color: #000000; transform: translateY(-2px); }
        .error-msg { color: #ef4444; font-size: 13px; font-weight: 600; margin-bottom: 15px; text-align: center; background: #fee2e2; padding: 10px; border-radius: 8px; }

        @media (max-width: 768px) { body { flex-direction: column; overflow: auto; } .left-panel, .right-panel { width: 100%; height: auto; min-height: 50vh;} .left-panel { padding: 40px 20px; } .right-panel { padding: 40px 20px; } .logo-card { width: 100%; } }
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
            <h2>Pengajuan Akun Baru</h2>
            <p style="margin-bottom: 20px;">Silakan isi data untuk meminta akses ke Admin</p>

            <?php if(isset($error_msg)): ?>
                <div class="error-msg"><?= $error_msg ?></div>
            <?php endif; ?>

            <form action="" method="POST">
                
                <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 20px; padding: 10px; text-align: center; font-size: 14px; font-weight: 600; color: #0f172a; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                    Role Anda: Pengguna (Staff)
                </div>

                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" placeholder="Masukkan nama lengkap Anda" required>
                </div>

                <div class="form-group">
                    <label>Username (Untuk Login)</label>
                    <input type="text" name="username" placeholder="Buat nama pengguna" required>
                </div>

                <div class="form-group">
                    <label>Alamat Email</label>
                    <input type="email" name="email" placeholder="contoh@citrabuana.com" required>
                </div>

                <div class="form-group">
                    <label>Kata Sandi</label>
                    <input type="password" name="password" placeholder="Buat kata sandi aman" required>
                </div>

                <div class="form-actions">
                    <span style="color: #64748b; margin-right: 5px;">Sudah punya akun?</span> 
                    <a href="login.php">Masuk disini</a>
                </div>

                <button type="submit" name="daftar" class="btn-submit">Kirim Pengajuan Akun</button>
            </form>
        </div>
    </div>

    <?php if(isset($sukses_daftar)): ?>
    <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15,23,42,0.8); backdrop-filter: blur(4px); display: flex; justify-content: center; align-items: center; z-index: 9999; animation: fadeIn 0.3s ease-out;">
        <div style="background: white; width: 420px; padding: 40px 30px; border-radius: 16px; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.3); animation: popIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);">
            <div style="width: 80px; height: 80px; background-color: #e0f2fe; color: #3b82f6; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 40px; margin: 0 auto 20px auto;">📝</div>
            <h2 style="color: #0f172a; font-size: 22px; font-weight: 700; margin-bottom: 12px;">Pengajuan Dikirim!</h2>
            <p style="color: #64748b; font-size: 14.5px; line-height: 1.6; margin-bottom: 30px;">
                Akun Anda berhasil dibuat. Status saat ini <strong>Menunggu Persetujuan Admin</strong>. Silakan tunggu konfirmasi Admin agar Anda bisa login.
            </p>
            <button onclick="window.location.href='login.php'" style="background-color: #3b82f6; color: white; border: none; padding: 12px 24px; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; width: 100%;">Kembali ke Login</button>
        </div>
    </div>
    <?php endif; ?>

</body>
</html>