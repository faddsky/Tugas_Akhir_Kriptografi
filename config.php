<?php
// ===================================================
// 1. SESSION SETUP
// ===================================================
ini_set('session.save_path', realpath(dirname($_SERVER['DOCUMENT_ROOT']) . '/tmp'));
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// ===================================================
// 2. KONEKSI DATABASE
// ===================================================
define('DB_HOST', 'sql303.infinityfree.com');
define('DB_USER', 'if0_40331929');
define('DB_PASS', 'ArezzzZ12'); // Sesuaikan dengan password hosting Anda
define('DB_NAME', 'if0_40331929_kripto');

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) {
    die("Koneksi database gagal: " . $db->connect_error);
}

// ===================================================
// 3. KUNCI AES UNTUK ENKRIPSI DATA (bukan password login)
// ===================================================
define('AES_KEY_SECRET', 'a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4'); // 32 byte
define('AES_IV_SECRET', 'ini_iv_16_byte!!'); // 16 byte

if (strlen(AES_KEY_SECRET) !== 32) {
    die("Error: Kunci AES (AES_KEY_SECRET) harus tepat 32 byte/karakter.");
}
if (strlen(AES_IV_SECRET) !== 16) {
    die("Error: IV AES (AES_IV_SECRET) harus tepat 16 byte/karakter.");
}

// ===================================================
// 4. PEPPER UNTUK HASH PASSWORD LOGIN
// ===================================================
// 🔹 TAMBAHAN: simpan file rahasia di /htdocs/private/secret_pepper.php
$pepperPath = __DIR__ . '/private/secret_pepper.php';
if (file_exists($pepperPath)) {
    define('APP_PEPPER', include($pepperPath));
} else {
    define('APP_PEPPER', 'dummy_pepper_dev'); // fallback jika di lokal
}

// ===================================================
// 5. FUNGSI HASH PASSWORD DENGAN PEPPER + BCRYPT
// ===================================================
// 🔹 TAMBAHAN
function hash_password_secure($password) {
    $pre = hash_hmac('sha256', $password, APP_PEPPER);
    return password_hash($pre, PASSWORD_BCRYPT);
}

function verify_password_secure($password, $storedHash) {
    $pre = hash_hmac('sha256', $password, APP_PEPPER);
    return password_verify($pre, $storedHash);
}

// ===================================================
// 6. CEK LOGIN SESSION
// ===================================================
function check_login($role = 'user') {
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php");
        exit;
    }
    if ($role == 'admin' && $_SESSION['role'] != 'admin') {
        header("Location: dashboard.php");
        exit;
    }
}
?>
