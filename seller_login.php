<?php
session_start();
require 'connect.php';

// Khởi tạo biến báo lỗi cho giao diện
$loginError = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Lấy thông tin người bán theo email từ bảng sellers
    $sql = "SELECT * FROM sellers WHERE email = ? LIMIT 1";
    $st  = $conn->prepare($sql);
    $st->execute([$email]);
    $seller = $st->fetch(PDO::FETCH_ASSOC);

    // Kiểm tra mật khẩu
    if ($seller && password_verify($password, $seller['password_hash'])) {
        // Lưu session đăng nhập seller
        $_SESSION['seller_id']   = $seller['id'];
        $_SESSION['seller_name'] = $seller['fullname'];
        $_SESSION['shop_name']   = $seller['shop_name'];

        // Điều hướng sang trang quản lý shop
        header("Location: seller_dashboard.php");
        exit;
    } else {
        $loginError = "Sai email hoặc mật khẩu";
    }
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Đăng nhập người bán</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5" style="max-width:400px">
  <h3 class="text-center mb-3">Người Bán Đăng nhập</h3>

  <?php if ($loginError !== ""): ?>
    <div class="alert alert-danger py-2 small text-center">
      <?php echo htmlspecialchars($loginError); ?>
    </div>
  <?php endif; ?>

  <form method="post" class="card p-3 shadow-sm">
    <div class="mb-3">
      <label class="form-label">Email</label>
      <input
        type="email"
        name="email"
        class="form-control"
        placeholder="you@shop.com"
        required
      >
    </div>

    <div class="mb-3">
      <label class="form-label">Mật khẩu</label>
      <input
        type="password"
        name="password"
        class="form-control"
        placeholder="••••••••"
        required
      >
    </div>

    <button type="submit" class="btn btn-dark w-100">Đăng nhập</button>

    <div class="text-center small mt-3">
      Chưa có tài khoản?
      <a href="seller_register.php">Đăng ký</a>
    </div>
  </form>
</div>

</body>
</html>
