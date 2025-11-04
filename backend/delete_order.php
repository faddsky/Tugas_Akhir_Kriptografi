<?php
require_once '../config.php';
check_login('admin');

if (!isset($_GET['id'])) {
    header("Location: ../admin_dashboard.php");
    exit;
}

$order_id = (int)$_GET['id'];

$stmt = $db->prepare("DELETE FROM orders WHERE id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();

header("Location: ../admin_dashboard.php");
exit;
?>