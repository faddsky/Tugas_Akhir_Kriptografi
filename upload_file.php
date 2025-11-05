<?php
require_once 'config.php';
require_once 'crypto_utils.php';
check_login();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['secure_file'])) {
    if ($_FILES['secure_file']['error'] == 0) {
        $user_id = $_SESSION['user_id'];
        $original_filename = basename($_FILES['secure_file']['name']);
        $tmp_path = $_FILES['secure_file']['tmp_name'];
        
        // Tentukan path file terenkripsi
        $encrypted_filename = time() . '_' . $original_filename . '.enc';
        $dest_path = 'uploads/files_enc/' . $encrypted_filename;

        // Panggil fungsi Enkripsi File
        if (encrypt_file($tmp_path, $dest_path)) {
            // Simpan ke database
            $stmt = $db->prepare("INSERT INTO secure_files (user_id, original_filename, encrypted_file_path) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $user_id, $original_filename, $dest_path);
            if ($stmt->execute()) {
                $success = "File berhasil dienkripsi dan disimpan!";
            } else {
                $error = "Gagal menyimpan data file ke database.";
                unlink($dest_path); // Hapus file terenkripsi jika gagal simpan DB
            }
        } else {
            $error = "Proses enkripsi file gagal.";
        }
    } else {
        $error = "Terjadi error saat mengupload file.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enkripsi File</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo">TokoBuku Terenkripsi</div>
        <div class="user-menu"><a href="dashboard.php">Kembali ke Dashboard</a></div>
    </nav>

    <div class="form-container">
        <h2>Upload File Aman (Enkripsi AES)</h2>
        <p style="text-align: center; color: #64748b; font-size: 15px; margin-top: -20px; margin-bottom: 25px;">File akan dienkripsi menggunakan AES-256</p>

        <?php if ($error): ?><p style="color:red;"><?= $error ?></p><?php endif; ?>
        <?php if ($success): ?><p style="color:green;"><?= $success ?></p><?php endif; ?>

        <form action="upload_file.php" method="POST" enctype="multipart/form-data">
            <label for="secure_file">Pilih File (Teks, Dokumen, dll)</label>
            <input type="file" id="secure_file" name="secure_file" required>
            
            <button type="submit">Upload & Enkripsi</button>
        </form>
    </div>
</body>
</html>
