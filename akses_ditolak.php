<?php
session_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akses Ditolak - PT Citra Buana Delapan</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { display: flex; height: 100vh; background-color: #f1f5f9; justify-content: center; align-items: center; overflow: hidden; }

        .error-card { background: white; width: 420px; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; overflow: hidden; text-align: center; animation: popIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); padding: 40px 30px; }
        
        /* Ikon Gembok Merah Keren */
        .icon-box { width: 80px; height: 80px; background-color: #fee2e2; color: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 40px; margin: 0 auto 20px auto; box-shadow: 0 0 20px rgba(239, 68, 68, 0.2); }
        
        h2 { color: #0f172a; font-size: 24px; font-weight: 700; margin-bottom: 12px; }
        p { color: #64748b; font-size: 14.5px; line-height: 1.6; margin-bottom: 30px; }
        
        .btn-back { display: inline-block; background-color: #3b82f6; color: white; border: none; padding: 12px 24px; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; transition: 0.2s; box-shadow: 0 4px 10px rgba(59, 130, 246, 0.2); width: 100%;}
        .btn-back:hover { background-color: #2563eb; transform: translateY(-2px); }

        @keyframes popIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }
    </style>
</head>
<body>

    <div class="error-card">
        <div class="icon-box">🔒</div>
        <h2>Akses Ditolak!</h2>
        <p>Maaf, <strong><?= isset($_SESSION['nama_lengkap']) ? htmlspecialchars($_SESSION['nama_lengkap']) : 'Staff' ?></strong>.<br>Halaman ini dikhususkan hanya untuk <strong>Administrator</strong>. Anda tidak memiliki izin untuk mengakses fitur ini.</p>
        <a href="dashboard.php" class="btn-back">Kembali ke Dashboard</a>
    </div>

</body>
</html>