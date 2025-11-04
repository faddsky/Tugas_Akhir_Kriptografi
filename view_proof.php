<?php
require_once 'config.php';
require_once 'crypto_utils.php'; // Sekarang file ini punya fungsi steganografi lagi
check_login('admin');

if (!isset($_GET['id'])) {
    die("ID Pesanan tidak ditemukan.");
}

$order_id = (int)$_GET['id'];

// Ambil data pesanan
$stmt = $db->prepare("
    SELECT o.proof_path, o.status, u.username, b.title 
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN books b ON o.book_id = b.id
    WHERE o.id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Pesanan tidak ditemukan.");
}

$order = $result->fetch_assoc();
$proof_path = $order['proof_path'];
$file_ext = strtolower(pathinfo($proof_path, PATHINFO_EXTENSION));

$content_html = '';

if (empty($proof_path) || !file_exists($proof_path)) {
    $content_html = '<div class="alert alert-danger">File bukti tidak ditemukan di server.</div>';
} 
// ===================================
// KASUS 1: JIKA GAMBAR (Steganografi Konseptual)
// ===================================
elseif (in_array($file_ext, ['jpg', 'jpeg', 'png'])) {
    
    // Cari path ke file .txt rahasia
    $txt_path = 'uploads/stego_txt/' . pathinfo($proof_path, PATHINFO_FILENAME) . '.txt';
    
    // Panggil fungsi demo extract
    $hidden_message = steganography_extract_demo($txt_path);
    
    $content_html = '
        <h4 class="fw-bold text-primary">Tipe Bukti: Gambar (Steganografi)</h4>
        <hr>
        <h5>Gambar Bukti Bayar:</h5>
        <img src="' . htmlspecialchars($proof_path) . '" class="img-fluid rounded border mb-3" alt="Bukti Bayar">
        
        <h5 class="mt-4">Pesan Tersembunyi (Hasil Ekstraksi dari file .txt):</h5>';
        
    if ($hidden_message == "Pesan rahasia tidak ditemukan.") {
        $content_html .= '<div class="alert alert-danger"><strong>' . htmlspecialchars($hidden_message) . '</strong></div>';
    } else {
        // --- PERBAIKAN DI SINI ---
        // 1. Amankan teksnya dulu
        $safe_message = htmlspecialchars($hidden_message);
        // 2. Ubah karakter \n (newline) menjadi tag <br>
        $formatted_message = nl2br($safe_message);
        
        $content_html .= '<div class="alert alert-success"><strong>' . $formatted_message . '</strong></div>';
        // --- AKHIR PERBAIKAN ---
    }
} 
// ===================================
// KASUS 2: JIKA FILE (Enkripsi AES)
// ===================================
elseif ($file_ext == 'enc') {
    $original_filename = str_replace('.enc', '', basename($proof_path));
    $original_filename = preg_replace('/^\d+_/', '', $original_filename); 

    $content_html = '
        <h4 class="fw-bold text-primary">Tipe Bukti: File (AES Terenkripsi)</h4>
        <hr>
        <p>File ini disimpan di server dalam format terenkripsi AES-256.</p>
        <div class="alert alert-info">
            <strong>Nama Asli (Perkiraan):</strong> ' . htmlspecialchars($original_filename) . '
        </div>
        <a href="download_proof.php?id=' . $order_id . '" class="btn btn-success btn-lg">
            <i class="fas fa-download"></i> Download & Dekripsi File Bukti
        </a>
    ';
} else {
    $content_html = '<div class="alert alert-warning">Format file tidak dikenali.</div>';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Lihat Bukti Pembayaran</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body style="background-color: #f8f9fa;">
    <nav class="navbar navbar-expand-lg shadow-sm" style="background-color: #212529 !important;">
        <div class="container">
            <a class="navbar-brand fw-bold" href="admin_dashboard.php" style="color: #ffffff !important;">Admin Panel</a>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="admin_dashboard.php" style="color: #ffffff !important;">Kembali ke Dashboard</a>
                </li>
            </ul>
        </div>
    </nav>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-dark text-white p-3">
                        <h3 class="mb-0">Verifikasi Bukti Bayar (Order ID: <?= $order_id ?>)</h3>
                    </div>
                    <div class="card-body p-4">
                        <p><strong>User:</strong> <?= htmlspecialchars($order['username']) ?></p>
                        <p><strong>Buku:</strong> <?= htmlspecialchars($order['title']) ?></p>
                        <hr>
                        <?= $content_html ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </body>
</html>