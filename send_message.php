<?php
require_once 'config.php';
require_once 'crypto_utils.php';
check_login();

$success = '';
$error = '';

// (Logika PHP Anda untuk mengirim pesan tetap SAMA)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $plaintext = $_POST['message'];
    $shift = (int)$_POST['shift'];
    $user_id = $_SESSION['user_id'];
    $admin_id = 1; 

    if (empty($plaintext) || $shift <= 0 || $shift > 25) {
        $error = "Pesan tidak boleh kosong dan shift harus antara 1-25.";
    } else {
        $encrypted_text = super_encrypt($plaintext, $shift);
        $stmt_insert = $db->prepare("
            INSERT INTO messages (sender_id, receiver_id, encrypted_text, status) 
            VALUES (?, ?, ?, 'Terkirim')
        ");
        $stmt_insert->bind_param("iis", $user_id, $admin_id, $encrypted_text);
        
        if ($stmt_insert->execute()) {
            $new_message_id = $stmt_insert->insert_id;
            $db->query("UPDATE messages SET thread_id = $new_message_id WHERE id = $new_message_id");
            
            $_SESSION['message'] = "Pesan baru berhasil dikirim!";
            header("Location: inbox.php"); // Arahkan ke kotak masuk
            exit;
        } else {
            $error = "Gagal menyimpan pesan.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tulis Pesan Baru</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background-color: #f8f9fa;">
    <nav class="navbar navbar-expand-lg shadow-sm" style="background-color: #007bff !important;">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php" style="color: #ffffff !important;">Toko Buku</a>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="inbox.php" style="color: #ffffff !important;">Kembali ke Kotak Masuk</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <h2 class="text-center fw-bold mb-2" style="color: #0d6efd;">Tulis Pesan ke Admin</h2>
                        <p class="text-center text-muted mb-4">(Super Enkripsi: AES + Caesar Cipher)</p>

                        <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

                        <form action="send_message.php" method="POST">
                            <div class="mb-3">
                                <label for="message" class="form-label">Pesan Rahasia Anda</label>
                                <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="shift" class="form-label">Kunci Caesar Shift (1-25)</label>
                                <input type="number" class="form-control" id="shift" name="shift" value="5" min="1" max="25" required>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary fw-bold">Kirim Pesan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
