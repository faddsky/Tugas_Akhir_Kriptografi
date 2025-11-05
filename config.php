<?php
// 1. SESSION SETUP
ini_set('session.save_path', realpath(dirname($_SERVER['DOCUMENT_ROOT']) . '/tmp'));
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. KONEKSI DATABASE
define('DB_HOST', 'sql303.infinityfree.com');
define('DB_USER', 'if0_40331929');
define('DB_PASS', 'ArezzzZ12');
define('DB_NAME', 'if0_40331929_kripto');

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) {
    die("Koneksi database gagal: " . $db->connect_error);
}

// 3. KUNCI AES UNTUK ENKRIPSI DATA (bukan password login)
define('AES_KEY_SECRET', 'a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4'); // 32 byte
define('AES_IV_SECRET', 'ini_iv_16_byte!!'); // 16 byte

if (strlen(AES_KEY_SECRET) !== 32) {
    die("Error: AES_KEY_SECRET harus tepat 32 karakter.");
}
if (strlen(AES_IV_SECRET) !== 16) {
    die("Error: AES_IV_SECRET harus tepat 16 karakter.");
}

// 4. PEPPER UNTUK HASH PASSWORD LOGIN
$pepperPath = __DIR__ . '/private/secret_pepper.php';
if (file_exists($pepperPath)) {
    define('APP_PEPPER', include($pepperPath));
} else {
    define('APP_PEPPER', 'dummy_pepper_dev'); 
}

// 5. LOAD UTILITAS KRIPTO (semua fungsi hash, AES, stego, dsb.)
require_once __DIR__ . '/crypto_utils.php';

// 6. CEK LOGIN SESSION (fungsi pembatasan akses)
function check_login($role = 'user') {
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php");
        exit;
    }
    if ($role === 'admin' && (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin')) {
        header("Location: dashboard.php");
        exit;
    }
}

// 7. FUNGSI BANTUAN STEGO (menggunakan hybrid AES + LSB random pixel)
function simpan_bukti_dengan_stegano($file_array, $pesan, $password = 'default_password')
{
    $upload_dir = __DIR__ . '/uploads/stego_img/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

    $output_path = $upload_dir . time() . '_' . basename($file_array['name']);

    if (!move_uploaded_file($file_array['tmp_name'], $output_path)) {
        return ['status' => 'error', 'msg' => 'Upload gagal'];
    }

    $result = lsb_embed_random_secure($output_path, $pesan, $output_path, $password);
    if ($result['status'] !== 'ok') {
        unlink($output_path);
        return ['status' => 'error', 'msg' => $result['msg']];
    }

    return ['status' => 'ok', 'path' => $output_path];
}

function ambil_pesan_stegano($image_path, $password = 'default_password')
{
    return lsb_extract_random_secure($image_path, $password);
}
?>
