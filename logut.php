<?php
session_start();
require 'connect.php';

$errorMsg   = "";
$successMsg = "";

// nhận message từ logout.php (vd: ?message=Đăng xuất thành công)
if (isset($_GET['message']) && $_GET['message'] !== '') {
    $successMsg = $_GET['message'];
}

// nếu user bấm submit form (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $errorMsg = "Vui lòng nhập đầy đủ email và mật khẩu.";
    } else {
        // lấy thông tin user theo email
        $stmt = $conn->prepare("SELECT id, fullname, email, password_hash, is_verified FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $errorMsg = "Email hoặc mật khẩu không đúng.";
        } else {
            // kiểm tra mật khẩu
            if (!password_verify($password, $user['password_hash'])) {
                $errorMsg = "Email hoặc mật khẩu không đúng.";
            } else {
                // kiểm tra đã xác minh email chưa
                if ((int)$user['is_verified'] === 0) {
                    $errorMsg = "Tài khoản chưa xác minh email. Vui lòng kiểm tra hộp thư.";
                } else {
                    // đăng nhập thành công: lưu session, chuyển sang trang chủ
                    $_SESSION['user_id']   = $user['id'];
                    $_SESSION['fullname']  = $user['fullname'];
                    $_SESSION['user_email'] = $user['email'];

                    header("Location: trangchu.php");
                    exit();
                }
            }
        }
    }
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Đăng nhập</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5" style="max-width:400px">
  <h3 class="text-center mb-3">Đăng nhập</h3>

  <?php if ($successMsg !== ""): ?>
    <div class="alert alert-success py-2 small text-center">
      <?php echo htmlspecialchars($successMsg); ?>
    </div>
  <?php endif; ?>

  <?php if ($errorMsg !== ""): ?>
    <div class="alert alert-danger py-2 small text-center">
      <?php echo htmlspecialchars($errorMsg); ?>
    </div>
  <?php endif; ?>

  <form method="post" class="card p-3 shadow-sm">
    <div class="mb-3">
      <label class="form-label">Email</label>
      <input type="email" name="email" class="form-control" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Mật khẩu</label>
      <input type="password" name="password" class="form-control" required>
    </div>

    <button type="submit" class="btn btn-dark w-100">Đăng nhập</button>

    <div class="text-center small mt-3">
      Chưa có tài khoản? <a href="register.php">Đăng ký</a>
    </div>
  </form>
</div>

</body>
</html>
