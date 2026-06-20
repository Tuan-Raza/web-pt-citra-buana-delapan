<?php
session_start();
include 'koneksi.php';

// Pastikan user sudah login
if (!isset($_SESSION["login"])) { 
    header("Location: login.php"); 
    exit; 
}

$status = "";
if (isset($_GET['id'])) {
    // Amankan input ID
    $id = mysqli_real_escape_string($koneksi, $_GET['id']);
    
    $query = "DELETE FROM data_barang WHERE id_barang = '$id'";
    if (mysqli_query($koneksi, $query)) {
        $status = "sukses";
    } else {
        $status = "gagal";
    }
} else {
    header("Location: data_barang.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hapus Barang - PT Citra Buana Delapan</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, sans-serif; }
        body { background-color: #f1f5f9; display: flex; justify-content: center; align-items: center; height: 100vh; overflow: hidden; }
        
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15,23,42,0.8); backdrop-filter: blur(4px); display: flex; justify-content: center; align-items: center; z-index: 9999; animation: fadeIn 0.3s ease-out; }
        .modal-box { background: white; width: 400px; padding: 40px 30px; border-radius: 16px; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.3); animation: popIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        
        .icon-wrapper { width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 40px; margin: 0 auto 20px auto; }
        .icon-success { background-color: #dcfce7; color: #16a34a; }
        .icon-error { background-color: #fee2e2; color: #ef4444; }
        
        h2 { color: #0f172a; font-size: 24px; font-weight: 700; margin-bottom: 12px; }
        p { color: #64748b; font-size: 14.5px; line-height: 1.6; margin-bottom: 30px; }
        
        .btn { color: white; border: none; padding: 12px 24px; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; width: 100%; text-decoration: none; display: inline-block; transition: 0.2s; }
        .btn-success { background-color: #16a34a; }
        .btn-success:hover { background-color: #15803d; }
        .btn-error { background-color: #ef4444; }
        .btn-error:hover { background-color: #dc2626; }
        
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes popIn { from { transform: scale(0.8); opacity: 0; } to { transform: scale(1); opacity: 1; } }
    </style>
</head>
<body>

    <div class="modal-overlay">
        <div class="modal-box">
            <?php if($status == "sukses"): ?>
                <div class="icon-wrapper icon-success">✓</div>
                <h2>Data Dihapus!</h2>
                <p>Data barang telah berhasil dihapus dari sistem. Anda akan dialihkan sebentar lagi...</p>
                <a href="data_barang.php" class="btn btn-success">Kembali Sekarang</a>
                
                <script>
                    setTimeout(function(){
                        window.location.href = 'data_barang.php';
                    }, 2000);
                </script>

            <?php else: ?>
                <div class="icon-wrapper icon-error">✖</div>
                <h2>Gagal Menghapus!</h2>
                <p>Terjadi kesalahan atau data barang tidak ditemukan di dalam database.</p>
                <a href="data_barang.php" class="btn btn-error">Kembali</a>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>