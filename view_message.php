<?php
require_once 'config.php';
require_once 'crypto_utils.php';
check_login('admin');

if (!isset($_GET['thread_id'])) {
    die("Thread ID tidak ditemukan.");
}

$thread_id = (int)$_GET['thread_id'];
$admin_id = $_SESSION['user_id'];

// Ambil semua pesan dalam thread ini
$msg_query = $db->prepare("SELECT * FROM messages WHERE thread_id = ? ORDER BY created_at ASC");
$msg_query->bind_param("i", $thread_id);
$msg_query->execute();
$messages = $msg_query->get_result();

if ($messages->num_rows == 0) {
    die("Percakapan tidak ditemukan.");
}

// Dapatkan ID user (penerima balasan) dari pesan pertama
$messages->data_seek(0);
$first_message = $messages->fetch_assoc();
$user_id_receiver = ($first_message['sender_id'] == $admin_id) ? $first_message['receiver_id'] : $first_message['sender_id'];

// Tandai pesan dari user sebagai 'Dibaca'
$db->query("UPDATE messages SET status = 'Dibaca' WHERE thread_id = $thread_id AND sender_id = $user_id_receiver AND status = 'Terkirim'");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Lihat Percakapan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css"> <style>
        /* Style untuk chat */
        .chat-container { max-width: 800px; margin: 40px auto; }
        .chat-bubble { padding: 10px 15px; border-radius: 20px; margin-bottom: 10px; max-width: 70%; }
        .user { background-color: #e0e7ff; color: #1e293b; margin-right: auto; }
        .admin { background-color: #2563eb; color: white; margin-left: auto; }
        .chat-box { display: flex; flex-direction: column; background: #f8fafc; border: 1px solid #cbd5e1; padding: 20px; border-radius: 10px; height: 400px; overflow-y: auto; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">Admin Panel</div>
        <div class="user-menu"><a href="admin_dashboard.php">Kembali ke Admin</a></div>
    </nav>
    
    <div class="chat-container">
        <h2 style="text-align: center; margin-bottom: 20px;">Percakapan #<?= $thread_id ?></h2>
        
        <div class="chat-box mb-3">
            <?php
            // Reset pointer dan loop lagi untuk display
            $messages->data_seek(0); 
            ?>
            <?php while($msg = $messages->fetch_assoc()): ?>
                <?php $plaintext = super_decrypt($msg['encrypted_text'], 5); ?>
                
                <div class="chat-bubble <?php echo ($msg['sender_id'] == $admin_id) ? 'admin' : 'user'; ?>">
                    <p class="mb-0"><?= htmlspecialchars($plaintext) ?></p>
                    <small class="d-block text-end opacity-75"><?= date('H:i', strtotime($msg['created_at'])) ?></small>
                </div>
            <?php endwhile; ?>
        </div>
        
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="fw-bold">Balas Pesan:</h5>
                <form action="backend/send_reply.php" method="POST">
                    <input type="hidden" name="thread_id" value="<?= $thread_id ?>">
                    <input type="hidden" name="receiver_id" value="<?= $user_id_receiver ?>"> <textarea name="message" class="form-control mb-2" placeholder="Tulis balasan Anda..." rows="3" required></textarea>
                    
                    <button type="submit" class="btn btn-primary w-100 fw-bold">Kirim Balasan</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
