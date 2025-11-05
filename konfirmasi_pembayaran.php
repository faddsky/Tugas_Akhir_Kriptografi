<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';
require_once 'crypto_utils.php'; // Berisi AES + LSB + Random Pixel
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

// Ambil nama user untuk pesan steganografi
$user_query = $db->prepare("SELECT username FROM users WHERE id = ?");
$user_query->bind_param("i", $user_id);
$user_query->execute();
$user_result = $user_query->get_result()->fetch_assoc();
$user_name = $user_result['username'];

// Logika saat form dikirim
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
            // =======================================================
            // 1️⃣ KASUS GAMBAR: Steganografi AES + Random Pixel
            // =======================================================
            if (in_array($file_ext, $allowed_img)) {
                $currentDate = date("Y-m-d H:i:s");

                // 1. Susun pesan rahasia
                $message_plain = "Order ID: {$order_id}\nNama User: {$user_name}\nTanggal Konfirmasi: {$currentDate}";

                // 2. Enkripsi pesan dengan AES
                $encrypted_message = aes_encrypt($message_plain, AES_KEY_SECRET, AES_IV_SECRET);

                // 3. Siapkan direktori hasil
                $upload_dir = __DIR__ . '/uploads/stego_img/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

                $stego_path = $upload_dir . 'stego_' . time() . '_' . $file_name;

                // 4. Sisipkan pesan ke gambar menggunakan random pixel
                $result = lsb_embed_random_secure($file_tmp_path, $stego_path, $encrypted_message, 'Order' . $order_id);

                if (!$result) throw new Exception("Gagal melakukan steganografi AES + LSB.");

                // 5. Simpan path final
                $final_path = 'uploads/stego_img/' . basename($stego_path);
            }

            // =======================================================
            // 2️⃣ KASUS FILE: Enkripsi AES (file .pdf / .txt)
            // =======================================================
            elseif (in_array($file_ext, $allowed_file)) {
                $upload_dir = __DIR__ . '/uploads/files_enc/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

                $encrypted_filename = time() . '_' . $file_name . '.enc';
                $dest_path = $upload_dir . $encrypted_filename;

                if (!encrypt_file($file_tmp_path, $dest_path)) {
                    throw new Exception("Gagal mengenkripsi file.");
                }
                $final_path = 'uploads/files_enc/' . $encrypted_filename;
            }

            else {
                throw new Exception("Format file tidak didukung. Harap upload .jpg, .png, .pdf, atau .txt");
            }

            // =======================================================
            // 3️⃣ UPDATE DATABASE
            // =======================================================
            $stmt_update = $db->prepare("
                UPDATE orders 
                SET status = 'Waiting for Confirmation', proof_path = ? 
                WHERE id = ? AND user_id = ?
            ");
            $stmt_update->bind_param("sii", $final_path, $order_id, $user_id);
            if ($stmt_update->execute()) {
                $_SESSION['message'] = "Konfirmasi pembayaran berhasil dikirim. Admin akan segera memverifikasi.";
                header("Location: dashboard.php");
                exit;
            } else {
                throw new Exception("Gagal menyimpan data ke database.");
            }

        } catch (Exception $e) {
            $error = $e->getMessage();
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
                            <select class="form-select" id="order_id" name="order_id" required onchange="showSteganoMessage()">
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
                            <div class="form-text">File PDF/TXT akan di-enkripsi AES. File gambar akan disisipi pesan rahasia (AES + LSB + Random Pixel).</div>
                        </div>

                        <div id="stegano-preview" class="alert alert-info" style="display: none;">
                            <p class="fw-bold mb-1">Pesan Steganografi (yang akan disembunyikan):</p>
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

<script>
function showSteganoMessage() {
    var select = document.getElementById('order_id');
    var previewBox = document.getElementById('stegano-preview');
    var previewText = document.getElementById('stegano-message-text');
    var userName = "<?php echo htmlspecialchars($user_name); ?>";
    var today = new Date();
    var dateString = today.getFullYear() + '-' + (today.getMonth() + 1) + '-' + today.getDate();

    if (select.value) {
        var message = "<strong>Order ID:</strong> " + select.value + "<br>" +
                      "<strong>Nama User:</strong> " + userName + "<br>" +
                      "<strong>Tanggal Konfirmasi:</strong> " + dateString;
        previewText.innerHTML = message;
        previewBox.style.display = 'block';
    } else {
        previewBox.style.display = 'none';
    }
}
</script>
</body>
</html>
