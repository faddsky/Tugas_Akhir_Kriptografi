<?php
require_once 'config.php';
require_once 'crypto_utils.php'; // Sekarang file ini punya fungsi steganografi lagi
check_login();

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Ambil daftar pesanan 'Pending'
$pending_orders_query = $db->prepare("
    SELECT o.id, b.title 
    FROM orders o
    JOIN books b ON o.book_id = b.id
    WHERE o.user_id = ? AND o.status = 'Pending'
");
$pending_orders_query->bind_param("i", $user_id);
$pending_orders_query->execute();
$pending_orders = $pending_orders_query->get_result();

// Ambil nama user untuk demo
$user_query = $db->prepare("SELECT username FROM users WHERE id = ?");
$user_query->bind_param("i", $user_id);
$user_query->execute();
$user_result = $user_query->get_result()->fetch_assoc();
$user_name = $user_result['username']; // Kita akan pakai ini

// Logika saat form disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['proof_file']) && isset($_POST['order_id'])) {
    
    $order_id = (int)$_POST['order_id'];
    $file = $_FILES['proof_file'];

    if ($file['error'] != 0) {
        $error = "Gagal mengupload file. Silakan coba lagi.";
    } else {
        $file_tmp_path = $file['tmp_name'];
        $file_name = basename($file['name']);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        $allowed_img = ['jpg', 'jpeg', 'png'];
        $allowed_file = ['pdf', 'txt'];
        $final_path = '';

        try {
            // ===================================
            // KASUS 1: JIKA GAMBAR (Steganografi Konseptual)
            // ===================================
            if (in_array($file_ext, $allowed_img)) {
                
                // --- PERUBAHAN DIMULAI DI SINI ---
                $currentDate = date("Y-m-d H:i:s");
                $message_to_hide = "Order ID: " . $order_id . "\n" .
                                   "Nama User: " . $user_name . "\n" .
                                   "Tanggal Konfirmasi: " . $currentDate;
                // --- PERUBAHAN SELESAI ---
                
                // Panggil fungsi demo dari crypto_utils.php
                $stego_result = steganography_embed_demo(
                    ['tmp_name' => $file_tmp_path, 'name' => $file_name], 
                    $message_to_hide
                );
                
                if (!$stego_result) throw new Exception("Gagal memproses steganografi.");
                $final_path = $stego_result['image_path']; // Path ke gambar stego

            // ===================================
            // KASUS 2: JIKA FILE (Enkripsi AES)
            // ===================================
            } elseif (in_array($file_ext, $allowed_file)) {
                $encrypted_filename = time() . '_' . $file_name . '.enc';
                $dest_path = 'uploads/files_enc/' . $encrypted_filename;
                
                if (!encrypt_file($file_tmp_path, $dest_path)) {
                    throw new Exception("Gagal mengenkripsi file.");
                }
                $final_path = $dest_path;

            } else {
                throw new Exception("Format file tidak didukung. Harap upload .jpg, .png, .pdf, atau .txt");
            }

            // ===================================
            // UPDATE DATABASE ORDER
            // ===================================
            $stmt_update = $db->prepare("UPDATE orders SET status = 'Waiting for Confirmation', proof_path = ? WHERE id = ? AND user_id = ?");
            $stmt_update->bind_param("sii", $final_path, $order_id, $user_id);
            if ($stmt_update->execute()) {
                $_SESSION['message'] = "Konfirmasi pembayaran berhasil diupload. Admin akan segera mengecek.";
                header("Location: dashboard.php");
                exit;
            } else {
                throw new Exception("Gagal menyimpan data ke database.");
            }

        } catch (Exception $e) {
            $error = $e->getMessage();
            // Jika gagal, hapus file yg mungkin sudah terupload
            if (!empty($final_path) && file_exists($final_path)) {
                unlink($final_path);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Konfirmasi Pembayaran</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background-color: #f8f9fa;">
   <nav class="navbar navbar-expand-lg shadow-sm" style="background-color: #007bff !important;">
  <div class="container d-flex justify-content-between align-items-center">
    <a class="navbar-brand fw-bold mb-0" href="dashboard.php" style="color: #ffffff !important;">
      Toko Buku Sukodadi
    </a>
    <a class="nav-link fw-semibold" href="dashboard.php" style="color: #ffffff !important;">
      Kembali ke Dashboard
    </a>
  </div>
</nav>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <h2 class="text-center fw-bold mb-4" style="color: #0d6efd;">Konfirmasi Pembayaran</h2>
                        
                        <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
                        
                        <form action="konfirmasi_pembayaran.php" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="order_id" class="form-label">Pilih Pesanan (Pending)</label>
                                <select class="form-select" id="order_id" name="order_id" required 
                                        onchange="showSteganoMessage()">
                                    <option value="">-- Pilih ID Pesanan --</option>
                                    <?php $pending_orders->data_seek(0); ?>
                                    <?php while($order = $pending_orders->fetch_assoc()): ?>
                                        <option value="<?= $order['id'] ?>">
                                            ID: <?= $order['id'] ?> (<?= htmlspecialchars($order['title']) ?>)
                                        </option>
                                    <?php endwhile; ?>
                                    <?php if($pending_orders->num_rows == 0): ?>
                                        <option disabled>Tidak ada pesanan yang menunggu pembayaran</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="proof_file" class="form-label">Upload Bukti Bayar</label>
                                <input class="form-control" type="file" id="proof_file" name="proof_file" accept=".jpg,.jpeg,.png,.pdf,.txt" required>
                                <div class="form-text">File (PDF/TXT) akan di-enkripsi AES.</div>
                            </div>

                            <div id="stegano-preview" class="alert alert-info" style="display: none;">
                                <p class="fw-bold mb-1">Demo Steganografi:</p>
                                <small id="stegano-message-text"></small>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary fw-bold" <?= $pending_orders->num_rows == 0 ? 'disabled' : '' ?>>
                                    Kirim Konfirmasi
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function showSteganoMessage() {
            var select = document.getElementById('order_id');
            var previewBox = document.getElementById('stegano-preview');
            var previewText = document.getElementById('stegano-message-text');
            
            // --- PERUBAHAN DIMULAI DI SINI ---
            
            // 1. Ambil nama user dari PHP
            var userName = "<?php echo htmlspecialchars($user_name); ?>";
            
            // 2. Ambil tanggal hari ini (versi JS)
            var today = new Date();
            var dateString = today.getFullYear() + '-' + (today.getMonth() + 1) + '-' + today.getDate();

            if (select.value) {
                // 3. Susun pesan baru
                var message = "Jika Anda mengupload file GAMBAR (.jpg/.png), kami akan menyembunyikan pesan ini di file .txt terpisah: <br>";
                message += "<strong>'Order ID: " + select.value + "'</strong><br>";
                message += "<strong>'Nama User: " + userName + "'</strong><br>";
                message += "<strong>'Tanggal Konfirmasi: " + dateString + "'</strong>";

                previewText.innerHTML = message;
                // --- PERUBAHAN SELESAI ---

                previewBox.style.display = 'block';
            } else {
                previewBox.style.display = 'none';
            }
        }
    </transcribe>
</script>
</body>
</html>
