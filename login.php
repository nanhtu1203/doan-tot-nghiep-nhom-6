<?php
session_start();
require 'connect.php';

$loginError = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $loginError = "Vui lòng nhập đầy đủ Email và Mật khẩu.";
    } else {
        // Chuẩn bị statement PDO
        $stmt = $conn->prepare(
            "SELECT id, fullname, email, password_hash 
             FROM users 
             WHERE email = ?"
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['email']    = $user['email'];

            header("Location: trangchu.php");
            exit;
        } else {
            $loginError = "Sai thông tin đăng nhập. Vui lòng kiểm tra lại.";
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

  <?php if ($loginError !== ""): ?>
    <div class="alert alert-danger py-2 small text-center">
      <?php echo htmlspecialchars($loginError); ?>
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
