<?php
require_once '../config.php';
require_once '../crypto_utils.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $message = trim($_POST['message']);
    $thread_id = (int)$_POST['thread_id'];
    $receiver_id = (int)$_POST['receiver_id'];
    $sender_id = $_SESSION['user_id'];
    
    $status = 'Terkirim'; 
    if ($_SESSION['role'] == 'admin') {
        $status = 'Dibalas';
    }

    if (empty($message) || empty($thread_id) || empty($receiver_id)) {
        die("Error: Data tidak lengkap.");
    }

    // Enkripsi pesan
    $encrypted_text = super_encrypt($message, 5);

    // Simpan ke database
    $stmt = $db->prepare("
        INSERT INTO messages (sender_id, receiver_id, thread_id, encrypted_text, status)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iiiss", $sender_id, $receiver_id, $thread_id, $encrypted_text, $status);
    $stmt->execute();
    
    // Update status thread (jika admin yg balas)
    if ($_SESSION['role'] == 'admin') {
        $db->query("UPDATE messages SET status = 'Dibalas' WHERE thread_id = $thread_id AND status != 'Dibalas'");
    }

    // Arahkan kembali
    if ($_SESSION['role'] == 'admin') {
        header("Location: ../view_message.php?thread_id=" . $thread_id);
    } else {
        header("Location: ../view_thread.php?thread_id=" . $thread_id);
    }
    exit;
}
?>
