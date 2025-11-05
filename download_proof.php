<?php
require_once 'config.php';
require_once 'crypto_utils.php';
check_login('admin');

if (!isset($_GET['id'])) {
    die("ID tidak ditemukan.");
}

$order_id = (int)$_GET['id'];
$stmt = $db->prepare("SELECT proof_path FROM orders WHERE id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("File tidak ditemukan.");
}

$order = $result->fetch_assoc();
$encrypted_path = $order['proof_path'];

if (empty($encrypted_path) || !file_exists($encrypted_path)) {
    die("File terenkripsi tidak ditemukan di server.");
}

// Rekonstruksi nama file asli
$original_filename = str_replace('.enc', '', basename($encrypted_path));
$original_filename = preg_replace('/^\d+_/', '', $original_filename); // Hapus timestamp

// Panggil fungsi dekripsi file dari crypto_utils.php
$plaintext_content = decrypt_file_to_browser($encrypted_path);

if ($plaintext_content === false) {
    die("Dekripsi file gagal. Cek kunci AES.");
}

// Paksa browser untuk men-download
header("Content-Description: File Transfer");
header("Content-Type: application/octet-stream");
header("Content-Disposition: attachment; filename=\"" . $original_filename . "\"");
header("Expires: 0");
header("Cache-Control: must-revalidate");
header("Pragma: public");
header("Content-Length: " . strlen($plaintext_content));
echo $plaintext_content;
exit;
?>
