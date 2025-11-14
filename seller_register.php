<?php
session_start();
require 'connect.php';

$errorMsg = "";
$successMsg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname   = $_POST['fullname']   ?? '';
    $email      = $_POST['email']      ?? '';
    $phone      = $_POST['phone']      ?? '';
    $shop_name  = $_POST['shop_name']  ?? '';
    $password   = $_POST['password']   ?? '';
    $password2  = $_POST['password2']  ?? '';

    // Kiểm tra input
    if ($fullname === '' || $email === '' || $shop_name === '' || $password === '' || $password2 === '') {
        $errorMsg = "Vui lòng nhập đủ thông tin bắt buộc";
    } elseif ($password !== $password2) {
        $errorMsg = "Mật khẩu nhập lại không khớp";
    } else {
        // Hash mật khẩu
        $hash = password_hash($password, PASSWORD_BCRYPT);

        try {
            // Lưu vào bảng sellers (chính xác với DB của bạn)
            $sql = "INSERT INTO sellers (fullname, email, phone, shop_name, password_hash)
                    VALUES (?, ?, ?, ?, ?)";
            $st  = $conn->prepare($sql);
            $st->execute([$fullname, $email, $phone, $shop_name, $hash]);

            $successMsg = "Đăng ký thành công. Mời đăng nhập.";
        } catch(PDOException $e) {
            // Ví dụ email đã tồn tại UNIQUE -> sẽ nhảy vào đây
            $errorMsg = "Lỗi: " . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Đăng ký người bán</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5" style="max-width:400px">
  <h3 class="text-center mb-3">Tạo tài khoản</h3>

  <?php if ($errorMsg !== ""): ?>
    <div class="alert alert-danger py-2 small text-center">
      <?php echo htmlspecialchars($errorMsg); ?>
    </div>
  <?php endif; ?>

  <?php if ($successMsg !== ""): ?>
    <div class="alert alert-success py-2 small text-center">
      <?php echo htmlspecialchars($successMsg); ?>
    </div>
  <?php endif; ?>

  <form method="post" class="card p-3 shadow-sm">

    <div class="mb-3">
      <label class="form-label">Họ và tên chủ shop</label>
      <input
        type="text"
        name="fullname"
        class="form-control"
        placeholder="Nguyễn Văn A"
        required
      >
    </div>

    <div class="mb-3">
      <label class="form-label">Tên shop</label>
      <input
        type="text"
        name="shop_name"
        class="form-control"
        placeholder="Thế Giới Giày"
        required
      >
    </div>

    <div class="mb-3">
      <label class="form-label">Email đăng nhập</label>
      <input
        type="email"
        name="email"
        class="form-control"
        placeholder="you@shop.com"
        required
      >
    </div>

    <div class="mb-3">
      <label class="form-label">Số điện thoại (tuỳ chọn)</label>
      <input
        type="text"
        name="phone"
        class="form-control"
        placeholder="09xxxxxxx"
      >
    </div>

    <div class="mb-3">
      <label class="form-label">Mật khẩu</label>
      <input
        type="password"
        name="password"
        class="form-control"
        minlength="6"
        placeholder="••••••••"
        required
      >
    </div>

    <div class="mb-3">
      <label class="form-label">Nhập lại mật khẩu</label>
      <input
        type="password"
        name="password2"
        class="form-control"
        minlength="6"
        placeholder="••••••••"
        required
      >
    </div>

    <button type="submit" class="btn btn-dark w-100">
      Đăng ký
    </button>

    <div class="text-center small mt-3">
      Đã có tài khoản?
      <a href="seller_login.php">Đăng nhập</a>
    </div>

  </form>
</div>

</body>
</html>
