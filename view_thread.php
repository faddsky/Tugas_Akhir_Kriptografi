<?php
require_once 'config.php';
require_once 'crypto_utils.php';
check_login('user');

$user_id = $_SESSION['user_id'];

if (!isset($_GET['thread_id'])) {
    header("Location: inbox.php");
    exit;
}

$thread_id = (int)$_GET['thread_id'];

// Ambil semua pesan dalam thread ini
$msg_query = $db->prepare("SELECT * FROM messages WHERE thread_id = ? AND (sender_id = ? OR receiver_id = ?) ORDER BY created_at ASC");
$msg_query->bind_param("iii", $thread_id, $user_id, $user_id);
$msg_query->execute();
$messages = $msg_query->get_result();

// Tandai pesan dari admin sebagai 'Dibaca'
$db->query("UPDATE messages SET status = 'Dibaca' WHERE thread_id = $thread_id AND sender_id = 1 AND status != 'Dibaca'");

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Lihat Percakapan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .chat-bubble { padding: 10px 15px; border-radius: 20px; margin-bottom: 10px; max-width: 70%; }
        .user { background-color: #007bff; color: white; margin-left: auto; }
        .admin { background-color: #e9ecef; color: #333; }
        .chat-box { display: flex; flex-direction: column; }
    </style>
</head>
<body style="background-color: #f8f9fa;">
    <nav class="navbar navbar-expand-lg shadow-sm" style="background-color: #007bff !important;">
        <div class="container">
            <a class="navbar-brand fw-bold" href="inbox.php" style="color: #ffffff !important;"><i class="fas fa-arrow-left"></i> Kembali ke Kotak Masuk</a>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white">
                <h4 class="mb-0">Percakapan #<?= $thread_id ?></h4>
            </div>
            <div class="card-body p-4 chat-box">
                <?php while($msg = $messages->fetch_assoc()): ?>
                    <?php $plaintext = super_decrypt($msg['encrypted_text'], 5); ?>
                    
                    <div class="chat-bubble <?php echo ($msg['sender_id'] == $user_id) ? 'user' : 'admin'; ?>">
                        <p class="mb-0"><?= htmlspecialchars($plaintext) ?></p>
                        <small class="d-block text-end opacity-75"><?= date('H:i', strtotime($msg['created_at'])) ?></small>
                    </div>
                <?php endwhile; ?>
            </div>
            <div class="card-footer p-3">
                <form action="backend/send_reply.php" method="POST">
                    <div class="input-group">
                        <input type="hidden" name="thread_id" value="<?= $thread_id ?>">
                        <input type="hidden" name="receiver_id" value="1"> <textarea name="message" class="form-control" placeholder="Tulis balasan Anda..." rows="2" required></textarea>
                        <button type="submit" class="btn btn-primary">Kirim</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
