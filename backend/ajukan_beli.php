<?php
// Kita perlu naik satu level ('../') untuk menemukan config.php
require_once '../config.php';

// Pastikan user login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php"); 
    exit;
}

// Pastikan ada book_id
if (!isset($_GET['book_id'])) {
    $_SESSION['error'] = "Permintaan tidak valid. ID Buku tidak ditemukan.";
    header("Location: ../dashboard.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$book_id = (int)$_GET['book_id'];

// Cek apakah user punya pesanan PENDING untuk buku ini
$check_query = "SELECT id FROM orders 
                WHERE user_id = $user_id 
                AND book_id = $book_id 
                AND status = 'Pending'";
                
$check_result = mysqli_query($db, $check_query);

if (mysqli_num_rows($check_result) > 0) {
    // Jika sudah ada, beri pesan error
    $_SESSION['error'] = "Anda sudah memiliki pesanan untuk buku ini (status Pending).";
} else {
    // Jika belum ada, masukkan data baru ke tabel 'orders'
    $insert_query = "INSERT INTO orders (user_id, book_id, status) 
                     VALUES ($user_id, $book_id, 'Pending')";
    
    if (mysqli_query($db, $insert_query)) {
        $_SESSION['message'] = "Pesanan berhasil dibuat. Silakan tunggu konfirmasi admin.";
    } else {
        $_SESSION['error'] = "Gagal membuat pesanan. Error: " . mysqli_error($db);
    }
}

// Kembalikan ke dashboard
header("Location: ../dashboard.php");
exit;
?>
