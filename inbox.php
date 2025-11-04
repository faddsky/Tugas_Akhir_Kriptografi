<?php
require_once 'config.php';
require_once 'crypto_utils.php';
check_login('user');

$user_id = $_SESSION['user_id'];

// Ambil daftar percakapan (threads)
// Kita grup berdasarkan thread_id
$threads_query = $db->prepare("
    SELECT 
        m.thread_id,
        MAX(m.created_at) AS last_message_time,
        (SELECT mm.encrypted_text FROM messages mm WHERE mm.thread_id = m.thread_id ORDER BY mm.created_at DESC LIMIT 1) AS last_message_encrypted,
        (SELECT mm.sender_id FROM messages mm WHERE mm.thread_id = m.thread_id ORDER BY mm.created_at DESC LIMIT 1) AS last_sender_id,
        (SELECT mm.status FROM messages mm WHERE mm.thread_id = m.thread_id ORDER BY mm.created_at DESC LIMIT 1) AS last_status
    FROM messages m
    WHERE m.sender_id = ? OR m.receiver_id = ?
    GROUP BY m.thread_id
    ORDER BY last_message_time DESC
");
$threads_query->bind_param("ii", $user_id, $user_id);
$threads_query->execute();
$threads_result = $threads_query->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kotak Masuk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body style="background-color: #f8f9fa;">
    <nav class="navbar navbar-expand-lg shadow-sm" style="background-color: #007bff !important;">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php" style="color: #ffffff !important;">Toko Buku</a>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="dashboard.php" style="color: #ffffff !important;">Kembali</a></li>
            </ul>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold" style="color: #0d6efd;">Kotak Masuk Anda</h2>
            <a href="send_message.php" class="btn btn-primary"><i class="fas fa-pen"></i> Tulis Pesan Baru</a>
        </div>

        <div class="list-group shadow-sm">
            <?php if ($threads_result->num_rows == 0): ?>
                <div class="list-group-item text-center p-4">Anda belum memiliki pesan.</div>
            <?php endif; ?>
            
            <?php while ($thread = $threads_result->fetch_assoc()): ?>
                <?php
                    // Dekripsi pesan terakhir untuk preview
                    $last_message = super_decrypt($thread['last_message_encrypted'], 5);
                    $preview = (strlen($last_message) > 50) ? substr($last_message, 0, 50) . '...' : $last_message;
                    
                    $is_unread = ($thread['last_sender_id'] == 1 && $thread['last_status'] != 'Dibaca');
                ?>
                <a href="view_thread.php?thread_id=<?= $thread['thread_id'] ?>" class="list-group-item list-group-item-action p-3 <?= $is_unread ? 'fw-bold' : '' ?>">
                    <div class="d-flex w-100 justify-content-between">
                        <h5 class="mb-1">Percakapan #<?= $thread['thread_id'] ?></h5>
                        <small><?= date('d M Y, H:i', strtotime($thread['last_message_time'])) ?></small>
                    </div>
                    <p class="mb-1">
                        <?php if ($thread['last_sender_id'] == $user_id): ?>
                            <span class="text-muted">Anda:</span>
                        <?php else: ?>
                            <span class="text-primary">Admin:</span>
                        <?php endif; ?>
                        <?= htmlspecialchars($preview) ?>
                    </p>
                    <?php if ($is_unread): ?>
                        <span class="badge bg-success">Balasan Baru</span>
                    <?php endif; ?>
                </a>
            <?php endwhile; ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>