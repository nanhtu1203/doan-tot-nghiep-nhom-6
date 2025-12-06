<?php
require 'connect.php';

$successMsg = "";
$errorMsg   = "";

/* Gửi email xác minh */
function sendVerificationEmail($toEmail, $code) {
    $subject = "Xac minh tai khoan cua ban";

    $verifyLink = "http://localhost/doantotnghiep/php/verify.php?email="
                . urlencode($toEmail) . "&code=" . urlencode($code);

    $message  = "Chao ban,\n\n";
    $message .= "Ma xac minh cua ban la: $code \n";
    $message .= "Hoac mo link sau de xac minh:\n$verifyLink\n\n";

    $headers = "From: no-reply@yourdomain.com\r\n";
    return @mail($toEmail, $subject, $message, $headers);
}

/* ----------------- XỬ LÝ ĐĂNG KÝ ----------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $fullname = trim($_POST['fullname'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($fullname === '' || $email === '' || $password === '') {
        $errorMsg = "Vui lòng nhập đầy đủ thông tin.";
    } else {

        // CHECK EMAIL TỒN TẠI TRONG users_id
        $check = $conn->prepare("SELECT id FROM users_id WHERE email = ?");
        $check->execute([$email]);

        if ($check->fetch()) {
            $errorMsg = "Email đã tồn tại. Vui lòng dùng email khác.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $verificationCode = random_int(100000, 999999);

            // INSERT USER VÀO users_id
            $ins = $conn->prepare("
                INSERT INTO users_id (fullname, email, password_hash, verification_code, is_verified)
                VALUES (?, ?, ?, ?, 0)
            ");

            $ok = $ins->execute([$fullname, $email, $hash, $verificationCode]);

            if ($ok) {

                // LOCALHOST – show mã xác minh
                if ($_SERVER['SERVER_NAME'] === 'localhost') {

                    $link = "http://localhost/doantotnghiep/php/verify.php?email=" 
                          . urlencode($email) . "&code=" . urlencode($verificationCode);

                    $successMsg  = "Đăng ký thành công.<br>";
                    $successMsg .= "Mã xác minh: <strong>" . $verificationCode . "</strong><br>";
                    $successMsg .= "Link xác minh:<br><a href=\"$link\">$link</a>";

                } else {
                    // HOST THẬT
                    if (sendVerificationEmail($email, $verificationCode)) {
                        $successMsg = "Đăng ký thành công. Vui lòng kiểm tra email để xác minh.";
                    } else {
                        $successMsg = "Đăng ký thành công, nhưng không gửi được email xác minh.";
                    }
                }
            } else {
                $errorMsg = "Có lỗi xảy ra. Vui lòng thử lại.";
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
  <title>Đăng ký tài khoản</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5" style="max-width:400px">
  <h3 class="text-center mb-3">Tạo tài khoản</h3>

  <?php if ($errorMsg): ?>
    <div class="alert alert-danger text-center"><?php echo $errorMsg; ?></div>
  <?php endif; ?>

  <?php if ($successMsg): ?>
    <div class="alert alert-success text-center"><?php echo $successMsg; ?></div>
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
