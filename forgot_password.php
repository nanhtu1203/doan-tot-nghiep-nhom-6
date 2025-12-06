<?php
session_start();
require 'connect.php';

// ====== KẾT NỐI PHPMailer ======
require __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require __DIR__ . '/../PHPMailer/src/SMTP.php';
require __DIR__ . '/../PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error = '';
$success = '';

function sendOTPEmail($toEmail, $otp) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;

        // ====== SỬA EMAIL / APP PASSWORD CỦA BẠN ======
        $mail->Username   = 'yourgmail@gmail.com';
        $mail->Password   = 'xxxxxxxxxxxxxxxx'; // App password 16 ký tự

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom('yourgmail@gmail.com', 'Support Web');
        $mail->addAddress($toEmail);

        $mail->isHTML(true);
        $mail->Subject = 'Ma OTP dat lai mat khau';
        $mail->Body    = '
            <h2>Ma OTP cua ban: <span style="color:#d9534f;">' . $otp . '</span></h2>
            <p>Ma co hieu luc trong 10 phut. Khong chia se ma nay cho bat ky ai.</p>
        ';

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}


// ====== XỬ LÝ FORM ======
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');

    if ($email === '') {
        $error = "Vui lòng nhập Email.";
    } else {
        // Kiểm tra email có tồn tại không
        $stmt = $conn->prepare("SELECT id FROM users_id WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $error = "Email này không tồn tại trong hệ thống!";
        } else {
            // Tạo mã OTP 6 số
            $otp = random_int(100000, 999999);
            $expireTime = date('Y-m-d H:i:s', time() + 600); // +10 phút

            // Lưu OTP vào DB
            $save = $conn->prepare("
                UPDATE users_id 
                SET reset_code = ?, reset_expires = ? 
                WHERE email = ?
            ");
            $save->execute([$otp, $expireTime, $email]);

            // Gửi OTP qua Email
            if (sendOTPEmail($email, $otp)) {
                $_SESSION['reset_email'] = $email;
                header("Location: reset_password.php");  // chuyển sang bước nhập OTP
                exit;
            } else {
                $error = "Không gửi được email. Vui lòng thử lại.";
            }
        }
    }
}

?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Quên mật khẩu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5" style="max-width:420px;">
    <h3 class="text-center mb-3">Quên mật khẩu</h3>

    <?php if ($error): ?>
        <div class="alert alert-danger text-center small">
            <?= $error ?>
        </div>
    <?php endif; ?>

    <form method="post" class="card p-4 shadow-sm">

        <label class="form-label">Nhập email đã đăng ký</label>
        <input type="email" name="email" class="form-control mb-3" placeholder="example@gmail.com" required>

        <button class="btn btn-dark w-100">Gửi mã OTP</button>

        <div class="text-center small mt-3">
            <a href="login.php">Quay lại đăng nhập</a>
        </div>

    </form>
</div>

</body>
</html>
