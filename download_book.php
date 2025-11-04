<?php
require_once 'config.php';
require_once 'crypto_utils.php';
check_login('user');

if (!isset($_GET['order_id'])) {
    die("ID Pesanan tidak valid.");
}

$order_id = (int)$_GET['order_id'];
$user_id = $_SESSION['user_id'];

// 1. Validasi Keamanan:
// Pastikan user ini benar-benar membeli barang ini DAN statusnya 'Completed'
$stmt_check = $db->prepare("
    SELECT o.book_id, o.status, b.digital_file_path, b.original_filename
    FROM orders o
    JOIN books b ON o.book_id = b.id
    WHERE o.id = ? AND o.user_id = ?
");
$stmt_check->bind_param("ii", $order_id, $user_id);
$stmt_check->execute();
$result = $stmt_check->get_result();

if ($result->num_rows == 0) {
    die("Error: Pesanan tidak ditemukan atau Anda tidak memiliki akses ke file ini.");
}

$data = $result->fetch_assoc();

// 2. Cek Status Pembayaran
if ($data['status'] != 'Completed') {
    die("Error: Pesanan ini belum disetujui. Download tidak diizinkan.");
}

// 3. Cek Keberadaan File
$encrypted_path = $data['digital_file_path'];
$original_filename = $data['original_filename'];

if (empty($encrypted_path) || !file_exists($encrypted_path)) {
    die("Error: File di server rusak atau hilang. Hubungi admin.");
}

// 4. Dekripsi dan Kirim File (AES)
$plaintext_content = decrypt_file_to_browser($encrypted_path);

if ($plaintext_content === false) {
    die("Dekripsi file gagal. Cek kunci AES.");
}

// 5. Paksa Browser untuk Download
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