<?php
// (Kode PHP di bagian atas file dashboard.php Anda tetap SAMA)
require_once 'config.php';
check_login('user');
$user_id = $_SESSION['user_id'];
$query_user = "SELECT username AS name FROM users WHERE id = $user_id";
$result_user = mysqli_query($db, $query_user);
$user = mysqli_fetch_assoc($result_user);
$search = isset($_GET['search']) ? $_GET['search'] : '';
$query_books = "SELECT id, title, author, cover_image FROM books";
if ($search) {
    $safe_search = mysqli_real_escape_string($db, $search);
    $query_books .= " WHERE title LIKE '%$safe_search%' OR author LIKE '%$safe_search%'";
}
$result_books = mysqli_query($db, $query_books);
$query_pesanan = "
    SELECT o.id, b.title, o.status, o.order_date 
    FROM orders o
    JOIN books b ON o.book_id = b.id
    WHERE o.user_id = $user_id
    ORDER BY o.order_date DESC
";
$result_pesanan = mysqli_query($db, $query_pesanan);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Dashboard User - Toko Buku</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    /* (CSS Anda yang lain tetap SAMA) */
    body { background-color: #f8f9fa; padding-bottom: 60px; }
    .navbar { background-color: #007bff !important; }
    .navbar .navbar-brand, .navbar .nav-link, .navbar .dropdown-item { color: #ffffff !important; }
    .navbar .dropdown-menu { background-color: #0d6efd; }
    .navbar .dropdown-item:hover { background-color: #0b5ed7; }
    .book-card img { height: 250px; object-fit: cover; border-top-left-radius: .5rem; border-top-right-radius: .5rem; }
    .book-card { transition: transform .2s; border: none; }
    .book-card:hover { transform: scale(1.03); box-shadow: 0 4px 15px rgba(0,0,0,0.15); }
    h2, h3 { color: #0d6efd; font-weight: bold; }
    .feature-card { background-color: #fff; border-radius: .75rem; transition: all .2s; }
    .feature-card:hover { transform: translateY(-5px); box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold" href="#"> Toko Buku Sukodadi</a>
    <form class="d-flex me-auto ms-3" method="GET" action="">
        <input type="text" name="search" class="form-control me-2" placeholder="Cari judul atau penulis" value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit" class="btn btn-light text-primary fw-bold">Cari</button>
    </form>
    <ul class="navbar-nav ms-auto">
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" data-bs-toggle="dropdown">
          Halo, <?php echo htmlspecialchars($user['name']); ?>
        </a>
        <ul class="dropdown-menu dropdown-menu-end">
          <?php if ($_SESSION['role'] == 'admin'): ?>
            <li><a class="dropdown-item text-white" href="admin_dashboard.php">Ke Admin Panel</a></li>
          <?php endif; ?>
          <li><a class="dropdown-item text-white" href="logout.php">Logout</a></li>
        </ul>
      </li>
    </ul>
  </div>
</nav>

<div class="container mt-5">
  
  <?php
    if (isset($_SESSION['message'])) {
        echo '<div class="alert alert-success">' . $_SESSION['message'] . '</div>';
        unset($_SESSION['message']);
    }
    if (isset($_SESSION['error'])) {
        echo '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
        unset($_SESSION['error']);
    }
  ?>

  <h3 class="mb-4">Menu Keamanan</h3>
  <div class="row mb-5">
    <div class="col-md-6 mb-3">
        <a href="konfirmasi_pembayaran.php" class="text-decoration-none">
            <div class="card feature-card shadow-sm border-0 p-3">
                <div class="d-flex align-items-center">
                    <div class="fa-2x me-3 text-success"><i class="fas fa-file-invoice-dollar"></i></div>
                    <div>
                        <h5 class="fw-bold mb-0">Konfirmasi Pembayaran</h5>
                        <p class="text-muted mb-0">Upload bukti bayar (AES & Steganografi)</p>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-6 mb-3">
        <a href="inbox.php" class="text-decoration-none">
            <div class="card feature-card shadow-sm border-0 p-3">
                <div class="d-flex align-items-center">
                    <div class="fa-2x me-3 text-primary"><i class="fas fa-inbox"></i></div>
                    <div>
                        <h5 class="fw-bold mb-0">Kotak Masuk (Aman)</h5>
                        <p class="text-muted mb-0">Lihat & Balas Pesan (Super Enkripsi)</p>
                    </div>
                </div>
            </div>
        </a>
    </div>
  </div>


  <h2 class="mb-4 text-center"> Daftar Buku</h2>
  <div class="row">
    <?php while($book = mysqli_fetch_assoc($result_books)): ?>
      <div class="col-md-3 mb-4">
        <div class="card book-card shadow-sm">
          <img src="uploads/books/<?php echo !empty($book['cover_image']) ? htmlspecialchars($book['cover_image']) : 'default_cover.jpg'; ?>" class="card-img-top" alt="Gambar Buku">
          <div class="card-body">
            <h5 class="card-title"><?php echo htmlspecialchars($book['title']); ?></h5>
            <p class="card-text mb-1"><strong>Penulis:</strong> <?php echo htmlspecialchars($book['author']); ?></p>
            <div class="d-grid">
              <a href="backend/ajukan_beli.php?book_id=<?php echo $book['id']; ?>" class="btn btn-success btn-sm">Beli Buku Ini</a>
            </div>
          </div>
        </div>
      </div>
    <?php endwhile; ?>
  </div>

<hr class="mt-5 mb-4">
  <h3 class="mt-4 mb-3">Riwayat Pembelian Saya</h3>
  <table class="table table-striped table-hover">
    <thead class="table-primary">
      <tr>
        <th>Judul Buku</th>
        <th>Tanggal Pesanan</th>
        <th>Status / Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php while($row = mysqli_fetch_assoc($result_pesanan)): ?>
        <tr>
          <td><?php echo htmlspecialchars($row['title']); ?></td>
          <td><?php echo htmlspecialchars($row['order_date']); ?></td>
          
          <td>
            <?php if ($row['status'] == 'Pending'): ?>
              <span class="badge bg-warning text-dark">Pending</span>
              
            <?php elseif ($row['status'] == 'Waiting for Confirmation'): ?>
              <span class="badge bg-info">Menunggu Konfirmasi</span>
              
            <?php elseif ($row['status'] == 'Completed'): ?>
              <a href="download_book.php?order_id=<?= $row['id'] ?>" class="btn btn-success btn-sm">
                  <i class="fas fa-download"></i> Download Buku
              </a>
              
            <?php elseif ($row['status'] == 'Cancelled'): ?>
              <span class="badge bg-danger">Cancelled</span>
            <?php endif; ?>
          </td>
          
        </tr>
      <?php endwhile; ?>
      <?php if(mysqli_num_rows($result_pesanan) == 0): ?>
        <tr><td colspan="3" class="text-center">Anda belum pernah membeli buku.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>