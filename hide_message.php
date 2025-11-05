<?php
require_once 'config.php';
require_once 'crypto_utils.php';
check_login();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['stego_image'])) {
    if ($_FILES['stego_image']['error'] == 0) {
        $user_id = $_SESSION['user_id'];
        $message = $_POST['secret_message'];
        $image_file = $_FILES['stego_image'];

        // Panggil fungsi demo steganografi
        $result = steganography_embed_demo($image_file, $message);
        
        if ($result) {
            $stmt = $db->prepare("INSERT INTO stego_images (user_id, image_path, secret_message_path) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $user_id, $result['image_path'], $result['message_path']);
            if ($stmt->execute()) {
                $success = "Pesan berhasil disembunyikan (secara konseptual)!";
            } else {
                $error = "Gagal menyimpan data steganografi ke database.";
            }
        } else {
            $error = "Proses steganografi demo gagal.";
        }
    } else {
        $error = "Terjadi error saat mengupload gambar.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Steganografi</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo">TokoBuku Terenkripsi</div>
        <div class="user-menu"><a href="dashboard.php">Kembali ke Dashboard</a></div>
    </nav>

    <div class="form-container">
        <h2>Sembunyikan Pesan di Gambar</h2>
        <p style="text-align: center; color: #64748b; font-size: 15px; margin-top: -20px; margin-bottom: 25px;">Demo Konsep Steganografi</p>

        <?php if ($error): ?><p style="color:red;"><?= $error ?></p><?php endif; ?>
        <?php if ($success): ?><p style="color:green;"><?= $success ?></p><?php endif; ?>

        <form action="hide_message.php" method="POST" enctype="multipart/form-data">
            <label for="stego_image">Pilih Gambar (Cover Image)</label>
            <input type="file" id="stego_image" name="stego_image" accept="image/*" required>

            <label for="secret_message">Pesan Rahasia Anda</label>
            <textarea id="secret_message" name="secret_message" rows="4" style="width: 100%; padding: 10px; border-radius: 10px; border: 1px solid #cbd5e1; font-family: 'Poppins'; margin-bottom: 18px;" required></textarea>
            
            <button type="submit">Sembunyikan Pesan</button>
        </form>
    </div>
</body>
</html>
