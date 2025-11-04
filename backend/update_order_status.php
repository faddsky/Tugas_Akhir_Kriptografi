<?php
require_once '../config.php';
check_login('admin');

if (!isset($_GET['id']) || !isset($_GET['status'])) {
    header("Location: ../admin_dashboard.php");
    exit;
}

$order_id = (int)$_GET['id'];
$status = $_GET['status'];

// Pastikan statusnya valid
if ($status == 'Completed' || $status == 'Cancelled') {
    
    $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $order_id);
    $stmt->execute();

    // Jika statusnya 'Completed', kurangi stok buku
    if ($status == 'Completed') {
        // Ambil book_id dari order
        $stmt_book = $db->prepare("SELECT book_id FROM orders WHERE id = ?");
        $stmt_book->bind_param("i", $order_id);
        $stmt_book->execute();
        $result = $stmt_book->get_result();
        
        if ($result->num_rows > 0) {
            $order = $result->fetch_assoc();
            $book_id = $order['book_id'];
            
            // Kurangi stok
            $db->query("UPDATE books SET stock = stock - 1 WHERE id = $book_id AND stock > 0");
        }
    }

}

header("Location: ../admin_dashboard.php");
exit;
?>