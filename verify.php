<?php
require 'connect.php';

$successMsg = "";
$errorMsg   = "";

$email = $_GET['email'] ?? '';
$code  = $_GET['code']  ?? '';

if ($email !== '' && $code !== '') {

    $sql = "SELECT id FROM users_id 
            WHERE email = ? AND verification_code = ? AND is_verified = 0";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$email, $code]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // cập nhật đã xác minh
        $upd = $conn->prepare("
            UPDATE users_id 
            SET is_verified = 1, verification_code = NULL
            WHERE id = ?
        ");
        $upd->execute([$user['id']]);

        $successMsg = "Xác minh tài khoản thành công. Bạn có thể đăng nhập ngay.";
    } else {
        $errorMsg = "Mã xác minh không hợp lệ, đã dùng hoặc tài khoản đã được xác minh.";
    }

} else {
    $errorMsg = "Thiếu thông tin email hoặc mã xác minh.";
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <title>Xác minh tài khoản</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width:400px">
  <h3 class="text-center mb-3">Xác minh email</h3>

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

  <div class="text-center mt-3">
    <a href="trangchu.php?show_login=1" class="btn btn-dark">Đến trang đăng nhập</a>
  </div>
</div>
</body>
</html>
