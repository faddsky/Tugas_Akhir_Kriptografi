<?php
require_once 'config.php';
check_login('admin');

if (!isset($_GET['id'])) {
    header("Location: admin_dashboard.php");
    exit;
}

$id = (int)$_GET['id'];

$stmt_get = $db->prepare("SELECT cover_image FROM books WHERE id = ?");
$stmt_get->bind_param("i", $id);
$stmt_get->execute();
$result = $stmt_get->get_result();
if ($result->num_rows == 1) {
    $book = $result->fetch_assoc();
    $cover_path = 'uploads/books/' . $book['cover_image'];
    if (file_exists($cover_path) && $book['cover_image'] != 'default_cover.jpg') {
        unlink($cover_path);
    }
}

$stmt_del = $db->prepare("DELETE FROM books WHERE id = ?");
$stmt_del->bind_param("i", $id);
$stmt_del->execute();

header("Location: admin_dashboard.php");
exit;
?>
