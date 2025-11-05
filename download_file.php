<?php
require_once 'config.php';
require_once 'crypto_utils.php';
check_login('admin');

if (!isset($_GET['id'])) {
    die("ID tidak ditemukan.");
}

$id = (int)$_GET['id'];
$stmt = $db->prepare("SELECT original_filename, encrypted_file_path FROM secure_files WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("File tidak ditemukan.");
}

$file = $result->fetch_assoc();
$original_filename = $file['original_filename'];
$encrypted_path = $file['encrypted_file_path'];

if (!file_exists($encrypted_path)) {
    die("File terenkripsi tidak ditemukan di server.");
}

// Panggil fungsi dekripsi file
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
