<?php
require_once 'config.php';
require_once 'crypto_utils.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Username dan password tidak boleh kosong.";
    } else {
        // Cek apakah username sudah ada
        $stmt_check = $db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt_check->bind_param("s", $username);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $error = "Username sudah digunakan. Silakan pilih yang lain.";
        } else {
            // Gunakan BCRYPT untuk keamanan
            $password_hash = hash_password_bcrypt($password);
            
            // --- CATATAN UNTUK TUGAS ---
            // Jika Anda DIPAKSA menggunakan SHA-256, ganti baris di atas dengan:
            // $password_hash = hash_password_sha256($password);
            // --- AKHIR CATATAN ---

            $stmt_insert = $db->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, 'user')");
            $stmt_insert->bind_param("ss", $username, $password_hash);
            
            if ($stmt_insert->execute()) {
                $success = "Registrasi berhasil! Silakan <a href='login.php'>login</a>.";
            } else {
                $error = "Registrasi gagal. Silakan coba lagi.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi - Toko Buku</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="auth-page">
        <div class="login-card">
            <h2>Buat Akun Baru</h2>
            <p>Silakan isi form di bawah ini</p>

            <?php if ($error): ?><p style="color:red;"><?= $error ?></p><?php endif; ?>
            <?php if ($success): ?><p style="color:green;"><?= $success ?></p><?php endif; ?>

            <form action="register.php" method="POST">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>

                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>

                <button type="submit">Daftar</button>
            </form>
            
            <div class="register-text">
                Sudah punya akun? <a href="login.php">Login di sini</a>
            </div>
        </div>
    </div>
</body>
</html>