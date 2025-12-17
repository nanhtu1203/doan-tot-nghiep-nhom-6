<?php
session_start();
require 'connect.php';

$error = '';
$success = '';
$otpDisplay = ''; // Để hiển thị OTP nếu không gửi được email

// ====== KẾT NỐI PHPMailer (nếu có) ======
$phpmailerAvailable = false;

if (file_exists(__DIR__ . '/../PHPMailer/src/PHPMailer.php')) {
    require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/../PHPMailer/src/SMTP.php';
    require_once __DIR__ . '/../PHPMailer/src/Exception.php';
    $phpmailerAvailable = true;
}

function sendOTPEmail($toEmail, $otp) {
    global $phpmailerAvailable;
    
    if (!$phpmailerAvailable) {
        // Nếu không có PHPMailer, trả về false để hiển thị OTP trên màn hình
        return false;
    }
    
    // Sử dụng fully qualified class name thay vì use statement
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;

        // ====== SỬA EMAIL / APP PASSWORD CỦA BẠN ======
        $mail->Username   = 'anhtu120304@gmail.com';
        $mail->Password   = 'jmgk zgnv tzeo rghk'; // App password 16 ký tự

        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
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
    } catch (\PHPMailer\PHPMailer\Exception $e) {
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
            $expireTime = time() + 600; // +10 phút (timestamp)

            // Lưu OTP vào DB (nếu có cột reset_code và reset_expires)
            try {
                $save = $conn->prepare("
                    UPDATE users_id 
                    SET reset_code = ?, reset_expires = ? 
                    WHERE email = ?
                ");
                $save->execute([$otp, date('Y-m-d H:i:s', $expireTime), $email]);
            } catch (PDOException $e) {
                // Nếu không có cột reset_code/reset_expires, bỏ qua
            }

            // Gửi OTP qua Email
            if (sendOTPEmail($email, $otp)) {
                $_SESSION['reset_email'] = $email;
                header("Location: reset_password.php");  // chuyển sang bước nhập OTP
                exit;
            } else {
                // Nếu không gửi được email (không có PHPMailer), hiển thị OTP trên màn hình
                $_SESSION['reset_email'] = $email;
                $_SESSION['reset_otp'] = $otp; // Lưu OTP vào session để dùng
                $_SESSION['reset_otp_expires'] = $expireTime; // Lưu thời gian hết hạn
                $otpDisplay = $otp; // Hiển thị OTP trên màn hình
                $success = "Mã OTP đã được tạo. Vui lòng kiểm tra email hoặc sử dụng mã bên dưới (chế độ development).";
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

    <?php if ($success): ?>
        <div class="alert alert-success text-center small">
            <?= $success ?>
        </div>
    <?php endif; ?>

    <?php if ($otpDisplay): ?>
        <div class="alert alert-info text-center">
            <h5>Mã OTP của bạn (Development Mode):</h5>
            <h2 class="text-danger fw-bold"><?= htmlspecialchars($otpDisplay) ?></h2>
            <p class="small mb-2">Mã có hiệu lực trong 10 phút.</p>
            <a href="reset_password.php" class="btn btn-dark btn-sm">Tiếp tục đặt lại mật khẩu</a>
        </div>
    <?php endif; ?>

    <form method="post" class="card p-4 shadow-sm">

        <label class="form-label">Nhập email đã đăng ký</label>
        <input type="email" name="email" class="form-control mb-3" placeholder="example@gmail.com" required>

        <button class="btn btn-dark w-100">Gửi mã OTP</button>

        <div class="text-center small mt-3">
            <a href="trangchu.php?show_login=1">Quay lại đăng nhập</a>
        </div>

    </form>
</div>

</body>
</html>
