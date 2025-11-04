<?php
require_once 'config.php';
require_once 'crypto_utils.php'; // Kita butuh ini untuk enkripsi
check_login('admin');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $author = trim($_POST['author']);
    $stock = (int)$_POST['stock']; // Anda bisa set stock ke 99999 untuk digital
    
    $cover_image_name = 'default_cover.jpg';
    $encrypted_file_path = null;
    $original_file_name = null;

    // 1. Proses Gambar Sampul (Sama seperti sebelumnya)
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == 0) {
        $target_dir = "uploads/books/";
        $cover_image_name = time() . '_' . basename($_FILES["cover_image"]["name"]);
        $target_file = $target_dir . $cover_image_name;
        
        if (!move_uploaded_file($_FILES["cover_image"]["tmp_name"], $target_file)) {
            $error = "Gagal mengupload gambar sampul.";
            $cover_image_name = 'default_cover.jpg';
        }
    }

    // 2. Proses File Digital (BARU - Enkripsi AES)
    if (empty($error) && isset($_FILES['digital_file']) && $_FILES['digital_file']['error'] == 0) {
        $digital_file = $_FILES['digital_file'];
        $original_file_name = basename($digital_file['name']);
        $tmp_path = $digital_file['tmp_name'];
        
        // Tentukan path file terenkripsi
        $encrypted_filename = time() . '_' . $original_file_name . '.enc';
        $encrypted_file_path = 'uploads/digital_books/' . $encrypted_filename;

        // Panggil fungsi Enkripsi File (AES)
        if (!encrypt_file($tmp_path, $encrypted_file_path)) {
            $error = "Proses enkripsi file digital gagal.";
        }
        
    } elseif (empty($error)) {
        $error = "Anda wajib mengupload file digital buku.";
    }

    // 3. Simpan ke Database
    if (empty($error)) {
        $stmt = $db->prepare("
            INSERT INTO books (title, author, stock, cover_image, digital_file_path, original_filename) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("ssisss", $title, $author, $stock, $cover_image_name, $encrypted_file_path, $original_file_name);
        
        if ($stmt->execute()) {
            $success = "Buku (beserta file digital terenkripsi) berhasil ditambahkan!";
        } else {
            $error = "Gagal menambahkan buku ke database.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Buku</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background-color: #f8f9fa;">
    <nav class="navbar navbar-expand-lg shadow-sm" style="background-color: #0d6efd !important;">
         <div class="container">
            <a class="navbar-brand fw-bold" href="admin_dashboard.php" style="color: #ffffff !important;">Admin Panel</a>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="admin_dashboard.php" style="color: #ffffff !important;">Kembali</a></li>
            </ul>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <h2 class="text-center fw-bold mb-4" style="color: #1d4ed8;">Form Tambah Buku Baru</h2>
                        
                        <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
                        <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

                        <form action="add_book.php" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="title" class="form-label">Judul Buku</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>
                            <div class="mb-3">
                                <label for="author" class="form-label">Penulis</label>
                                <input type="text" class="form-control" id="author" name="author" required>
                            </div>
                            <div class="mb-3">
                                <label for="stock" class="form-label">Stok (isi 999 untuk digital)</label>
                                <input type="number" class="form-control" id="stock" name="stock" value="999" required>
                            </div>
                            <div class="mb-3">
                                <label for="cover_image" class="form-label">Gambar Sampul (Cover)</label>
                                <input type="file" class="form-control" id="cover_image" name="cover_image" accept="image/*">
                            </div>
                            
                            <div class="mb-3">
                                <label for="digital_file" class="form-label fw-bold text-danger">File Digital (E-book PDF, dll) *Wajib</label>
                                <input type="file" class="form-control" id="digital_file" name="digital_file" required>
                                <div class="form-text">File ini akan dienkripsi AES saat diupload.</div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary fw-bold">Tambah Buku</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
