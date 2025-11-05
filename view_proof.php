<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';
require_once 'crypto_utils.php'; // Berisi AES + LSB + Random Pixel
check_login('admin');

if (!isset($_GET['id'])) {
    die("ID Pesanan tidak ditemukan.");
}

$order_id = (int)$_GET['id'];

// ===================================================
// 1️⃣ Ambil data pesanan + bukti pembayaran
// ===================================================
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

// ===================================================
// 2️⃣ Validasi file bukti
// ===================================================
$full_path = __DIR__ . '/' . $proof_path; // Pastikan path absolut

if (empty($proof_path) || !file_exists($full_path)) {
    // Debug opsional — bisa dihapus nanti
    // echo "<pre>DEBUG PATH:\nProof path dari DB: $proof_path\nFull path dicek: $full_path\n</pre>";

    $content_html = '<div class="alert alert-danger">File bukti tidak ditemukan di server.</div>';
}

// ===================================================
// 3️⃣ KASUS GAMBAR (STEGANOGRAFI AES + LSB + RANDOM PIXEL)
// ===================================================
elseif (in_array($file_ext, ['jpg', 'jpeg', 'png'])) {

    // Gunakan path absolut untuk ekstraksi
    $hidden_message = lsb_extract_random_secure($full_path, 'Order' . $order_id);

    $content_html = '
        <h4 class="fw-bold text-primary">Tipe Bukti: Gambar (Steganografi AES + Random Pixel)</h4>
        <hr>
        <h5>Gambar Bukti Bayar:</h5>
        <img src="' . htmlspecialchars($proof_path) . '" class="img-fluid rounded border mb-3" alt="Bukti Bayar">
        
        <h5 class="mt-4">Pesan Tersembunyi (Hasil Ekstraksi):</h5>';

    if (empty($hidden_message) || $hidden_message === "Pesan tidak dapat didekripsi.") {
        $content_html .= '<div class="alert alert-danger"><strong>Tidak ada pesan rahasia ditemukan.</strong></div>';
    } else {
        $safe_message = htmlspecialchars($hidden_message);
        $formatted_message = nl2br($safe_message);
        $content_html .= '<div class="alert alert-success"><strong>' . $formatted_message . '</strong></div>';
    }
}

// ===================================================
// 4️⃣ KASUS FILE TEREKRIPSI (AES .enc)
// ===================================================
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
}

// ===================================================
// 5️⃣ FORMAT TIDAK DIKENALI
// ===================================================
else {
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
    <nav class="navbar navbar-expand-lg shadow-sm" style="background-color: #2563eb !important;">
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
                    <div class="card-header text-white p-3" style="background-color: #2563eb;">
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
