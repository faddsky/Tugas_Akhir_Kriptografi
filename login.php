<?php
require_once 'config.php';
require_once 'crypto_utils.php';

$error = '';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php"); // Sudah login, tendang ke dashboard
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $db->prepare("SELECT id, username, password_hash, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        // Verifikasi menggunakan BCRYPT
        if (verify_password_bcrypt($password, $user['password_hash'])) {
            
            // --- CATATAN UNTUK TUGAS ---
            // Jika Anda mendaftar pakai SHA-256, ganti kondisi 'if' di atas dengan:
            // if (verify_password_sha256($password, $user['password_hash'])) {
            // --- AKHIR CATATAN ---

            // Password benar, simpan data ke session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Arahkan berdasarkan role
            if ($user['role'] == 'admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: dashboard.php");
            }
            exit;
        } else {
            $error = "Username atau password salah.";
        }
    } else {
        $error = "Username atau password salah.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Toko Buku</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="auth-page">
        <div class="login-card">
            <h2>Selamat Datang!</h2>
            <p>Silakan login ke akun Anda</p>

            <?php if ($error): ?><p style"color:red;"><?= $error ?></p><?php endif; ?>

            <form action="login.php" method="POST">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
                <button type="submit">Login</button>
            </form>
            
            <div class="register-text">
                Belum punya akun? <a href="register.php">Daftar di sini</a>
            </div>
        </div>
    </div>
</body>
</html>