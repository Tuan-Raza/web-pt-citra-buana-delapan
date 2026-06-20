<?php
session_start();

// Hapus semua data session login
$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Hancurkan session
session_destroy();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keluar Sistem - PT Citra Buana Delapan</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { display: flex; height: 100vh; background-color: #0f172a; justify-content: center; align-items: center; overflow: hidden; }

        /* TOAST / MODAL BOX PROSES KELUAR */
        .toast-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15,23,42,0.8); backdrop-filter: blur(4px); display: flex; justify-content: center; align-items: center; }
        .toast-box { background: white; padding: 40px 30px; border-radius: 20px; width: 400px; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.3); animation: popIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        .toast-icon { width: 80px; height: 80px; background: #fee2e2; color: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 35px; margin: 0 auto 20px auto; font-weight: bold; box-shadow: 0 0 20px rgba(239, 68, 68, 0.2); }
        .toast-box h3 { color: #0f172a; margin-bottom: 12px; font-size: 22px; font-weight: 700; }
        .toast-box p { color: #64748b; font-size: 14.5px; margin-bottom: 20px; line-height: 1.6; }
        
        /* Loading Animation Minimalis */
        .spinner { width: 24px; height: 24px; border: 3px solid #cbd5e1; border-top-color: #ef4444; border-radius: 50%; margin: 0 auto; animation: spin 0.8s linear infinite; }

        @keyframes popIn { from { transform: scale(0.8); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>

    <div class="toast-overlay">
        <div class="toast-box">
            <div class="toast-icon">➔</div>
            <h3>Berhasil Keluar</h3>
            <p>Sesi Anda telah berakhir. Mengalihkan kembali ke halaman login utama...</p>
            <div class="spinner"></div>
        </div>
    </div>

    <script>
        // Delay 2 detik biar animasi popup keluar kelihatan rapi, baru pindah ke login.php
        setTimeout(function() {
            window.location.href = 'login.php';
        }, 2000);
    </script>
</body>
</html>