<?php
require 'connect.php';

// biến trạng thái để báo ra UI
$successMsg = "";
$errorMsg   = "";

// nếu user bấm submit form (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($fullname === '' || $email === '' || $password === '') {
        $errorMsg = "Vui lòng nhập đầy đủ thông tin.";
    } else {
        // kiểm tra email đã tồn tại chưa
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $errorMsg = "Email đã tồn tại. Vui lòng dùng email khác.";
        } else {
            // hash mật khẩu
            $hash = password_hash($password, PASSWORD_DEFAULT);

            // lưu vào DB
            $ins = $conn->prepare("INSERT INTO users (fullname, email, password_hash) VALUES (?, ?, ?)");
            $ins->execute([$fullname, $email, $hash]);

            // báo thành công
            $successMsg = "Đăng ký tài khoản thành công. Bạn có thể đăng nhập ngay.";
        }
    }
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Đăng ký tài khoản</title>
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
      <label class="form-label">Họ và tên</label>
      <input type="text" name="fullname" class="form-control" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Email</label>
      <input type="email" name="email" class="form-control" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Mật khẩu</label>
      <input type="password" name="password" class="form-control" minlength="6" required>
    </div>

    <button type="submit" class="btn btn-dark w-100">Đăng ký</button>

    <div class="text-center small mt-3">
      Đã có tài khoản? <a href="login.php">Đăng nhập</a>
    </div>
  </form>
</div>

</body>
</html>
