<?php
// Mulai session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. KONEKSI DATABASE
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Sesuaikan dengan password XAMPP/phpMyAdmin Anda
define('DB_NAME', 'kripto');

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) {
    die("Koneksi database gagal: " . $db->connect_error);
}

// ... (kode koneksi database di atas) ...

// 2. KUNCI KRIPTOGRAFI
// Kunci ini HARUS dirahasiakan dan HARUS 32 byte (256 bit) untuk AES-256
define('AES_KEY_SECRET', 'a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4'); 
// IV HARUS 16 byte (128 bit) untuk AES-256-CBC
define('AES_IV_SECRET', 'ini_iv_16_byte!!'); 

// Cek panjang (PENTING)
if (strlen(AES_KEY_SECRET) !== 32) {
    die("Error: Kunci AES (AES_KEY_SECRET) harus tepat 32 byte/karakter.");
}
if (strlen(AES_IV_SECRET) !== 16) {
    die("Error: IV AES (AES_IV_SECRET) harus tepat 16 byte/karakter.");
}

// ... (sisa kode config.php) ...

// 3. Helper function untuk mengecek login
function check_login($role = 'user') {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
    if ($role == 'admin' && $_SESSION['role'] != 'admin') {
        header("Location: dashboard.php"); // Bukan admin, tendang ke dashboard user
        exit;
    }
}
?>