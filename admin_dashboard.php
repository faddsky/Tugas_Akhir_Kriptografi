<?php
require_once 'config.php';
require_once 'crypto_utils.php'; // <--- TAMBAHKAN BARIS INI
check_login('admin');

// (Kode PHP di bagian atas file admin_dashboard.php Anda tetap SAMA)
$books = $db->query("SELECT * FROM books ORDER BY id DESC");
$users = $db->query("SELECT id, username, role FROM users ORDER BY username");
$messages = $db->query("SELECT m.*, u.username FROM messages m JOIN users u ON m.sender_id = u.id ORDER BY m.created_at DESC"); // Query ini tidak terpakai di versi baru, tapi tidak apa-apa
$files = $db->query("SELECT f.*, u.username FROM secure_files f JOIN users u ON f.user_id = u.id ORDER BY f.upload_time DESC");

// Query order diupdate untuk mengambil proof_path
$orders = $db->query("
    SELECT o.id, u.username, b.title, o.order_date, o.status, o.proof_path 
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN books b ON o.book_id = b.id
    ORDER BY o.order_date DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo">Admin Toko Buku Sukodadi</div>
        <div class="user-menu">
            <span>Admin: <b><?= htmlspecialchars($_SESSION['username']) ?></b></span>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <div class="admin-dashboard container">
        <h2>Manajemen Toko Buku Sukodadi</h2>
        <p style="text-align: center; margin-bottom: 30px;"><a href="add_book.php">Tambah Buku Baru</a></p>

        <div class="admin-transactions">
            <h3><i class="fas fa-shopping-cart"></i> Manajemen Pesanan</h3>
            <table class="history-table">
                <thead>
                    <tr>
                        <th>ID Pesanan</th>
                        <th>User</th>
                        <th>Buku</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($order = $orders->fetch_assoc()): ?>
                    <tr>
                        <td><?= $order['id'] ?></td>
                        <td><?= htmlspecialchars($order['username']) ?></td>
                        <td><?= htmlspecialchars($order['title']) ?></td>
                        <td>
                            <?php if ($order['status'] == 'Pending'): ?>
                                <span style="color: #d97706; font-weight: 600;">Pending</span>
                            <?php elseif ($order['status'] == 'Waiting for Confirmation'): ?>
                                <span style="color: #0d6efd; font-weight: 600;">Menunggu Konfirmasi</span>
                            <?php elseif ($order['status'] == 'Completed'): ?>
                                <span style="color: #16a34a; font-weight: 600;">Completed</span>
                            <?php else: ?>
                                <span style="color: #dc2626; font-weight: 600;">Cancelled</span>
                            <?php endif; ?>
                        </td>
                        <td style="min-width: 200px;">
                            <?php if ($order['status'] == 'Waiting for Confirmation'): ?>
                                <a href="view_proof.php?id=<?= $order['id'] ?>" 
                                   class="btn-view" style="background-color: #0dcaf0;">
                                   <i class="fas fa-eye"></i> Lihat Bukti
                                </a>
                                <a href="backend/update_order_status.php?id=<?= $order['id'] ?>&status=Completed" 
                                   style="background-color: #28a745; color: white; padding: 4px 8px; border-radius: 5px; text-decoration: none; font-size: 12px;"
                                   onclick="return confirm('Setujui pesanan ini?')">
                                   <i class="fas fa-check"></i>
                                </a>
                                <a href="backend/update_order_status.php?id=<?= $order['id'] ?>&status=Cancelled" 
                                   style="background-color: #dc3545; color: white; padding: 4px 8px; border-radius: 5px; text-decoration: none; font-size: 12px;"
                                   onclick="return confirm('Batalkan pesanan ini?')">
                                   <i class="fas fa-times"></i>
                                </a>
                            <?php elseif ($order['status'] == 'Pending'): ?>
                                <span style="font-size: 12px; color: #6c757d;">Menunggu bukti bayar</span>
                            <?php else: ?>
                                <a href="backend/delete_order.php?id=<?= $order['id'] ?>"
                                   style="background-color: #6c757d; color: white; padding: 4px 8px; border-radius: 5px; text-decoration: none; font-size: 12px;"
                                   onclick="return confirm('Hapus riwayat pesanan ini?')">
                                   <i class="fas fa-trash"></i> Hapus
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if($orders->num_rows == 0): ?>
                        <tr><td colspan="5" style="text-align: center;">Belum ada pesanan yang masuk.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="admin-book-list" style="margin-top: 40px;">
            <h3>Daftar Buku</h3>
            <div class="admin-books">
                <?php while($book = $books->fetch_assoc()): ?>
                <div class="admin-book-card">
                    <img src="uploads/books/<?= htmlspecialchars($book['cover_image']) ?>" alt="Cover">
                    <h4><?= htmlspecialchars($book['title']) ?></h4>
                    <p>Penulis: <?= htmlspecialchars($book['author']) ?></p>
                    <p>Stok: <?= $book['stock'] ?></p>
                    <div class="admin-book-actions">
                        <a href="delete_book.php?id=<?= $book['id'] ?>" class="delete" onclick="return confirm('Yakin hapus buku ini?')">Hapus</a>
                    </div>
                </div>
                <?php endwhile; ?>
                <?php if($books->num_rows == 0): ?>
                    <p style="text-align: center; grid-column: 1 / -1;">Belum ada buku ditambahkan.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="admin-transactions" style="margin-top: 40px;">
            <h3>Riwayat Pesan (Super Enkripsi)</h3>
            <table class="history-table">
                <thead>
                    <tr>
                        <th>Thread ID</th>
                        <th>Pengirim Terakhir</th>
                        <th>Pesan Terakhir (Preview)</th>
                        <th>Waktu</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Query BARU: Ambil per percakapan (thread), bukan per pesan
                    $threads_query_admin = $db->query("
                        SELECT 
                            m.thread_id,
                            MAX(m.created_at) AS last_message_time,
                            (SELECT mm.encrypted_text FROM messages mm WHERE mm.thread_id = m.thread_id ORDER BY mm.created_at DESC LIMIT 1) AS last_message_encrypted,
                            (SELECT u.username FROM messages mm JOIN users u ON mm.sender_id = u.id WHERE mm.thread_id = m.thread_id ORDER BY mm.created_at DESC LIMIT 1) AS last_sender_username,
                            (SELECT mm.status FROM messages mm WHERE mm.thread_id = m.thread_id ORDER BY mm.created_at DESC LIMIT 1) AS last_status
                        FROM messages m
                        WHERE m.receiver_id = 1 OR m.sender_id = 1
                        GROUP BY m.thread_id
                        ORDER BY last_message_time DESC
                    ");
                    ?>
                    <?php while($thread = $threads_query_admin->fetch_assoc()): ?>
                    <?php
                        $preview_admin = super_decrypt($thread['last_message_encrypted'], 5);
                        $preview_admin = (strlen($preview_admin) > 30) ? substr($preview_admin, 0, 30) . '...' : $preview_admin;
                    ?>
                    <tr>
                        <td>#<?= $thread['thread_id'] ?></td>
                        <td><?= htmlspecialchars($thread['last_sender_username']) ?></td>
                        <td style="word-break: break-all; max-width: 200px;"><?= htmlspecialchars($preview_admin) ?></td>
                        <td><?= date('d M Y, H:i', strtotime($thread['last_message_time'])) ?></td>
                        <td>
                            <?php if ($thread['last_status'] == 'Terkirim'): ?>
                                <span style="color: #d97706; font-weight: 600;">Pesan Baru</span>
                            <?php elseif ($thread['last_status'] == 'Dibalas'): ?>
                                <span style="color: #16a34a; font-weight: 600;">Sudah Dibalas</span>
                            <?php else: ?>
                                <span style="color: #6c757d; font-weight: 600;">Sudah Dibaca</span>
                            <?php endif; ?>
                        </td>
                        <td><a href="view_message.php?thread_id=<?= $thread['thread_id'] ?>" class="btn-view">Lihat & Balas</a></td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if($threads_query_admin->num_rows == 0): ?>
                        <tr><td colspan="6" style="text-align: center;">Tidak ada pesan.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div> </body>
</html>