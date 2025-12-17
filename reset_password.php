<?php
session_start();
require 'connect.php';

$error = '';
$success = '';

// Kiểm tra email trong session
if (!isset($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit;
}

$email = $_SESSION['reset_email'];

// Xử lý form đặt lại mật khẩu
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = trim($_POST['otp'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($otp)) {
        $error = "Vui lòng nhập mã OTP.";
    } elseif (empty($newPassword)) {
        $error = "Vui lòng nhập mật khẩu mới.";
    } elseif (strlen($newPassword) < 6) {
        $error = "Mật khẩu mới phải có ít nhất 6 ký tự.";
    } elseif ($newPassword !== $confirmPassword) {
        $error = "Mật khẩu mới và xác nhận mật khẩu không khớp.";
    } else {
        // Kiểm tra user tồn tại
        $stmt = $conn->prepare("SELECT id FROM users_id WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $error = "Email không tồn tại.";
        } else {
            $validOTP = false;
            
            // Kiểm tra OTP từ database (nếu có cột reset_code)
            try {
                $stmtOTP = $conn->prepare("SELECT reset_code, reset_expires FROM users_id WHERE email = ?");
                $stmtOTP->execute([$email]);
                $otpData = $stmtOTP->fetch(PDO::FETCH_ASSOC);
                
                if ($otpData && isset($otpData['reset_code']) && !empty($otpData['reset_code'])) {
                    if ($otpData['reset_code'] === $otp) {
                        // Kiểm tra thời gian hết hạn
                        if (isset($otpData['reset_expires']) && !empty($otpData['reset_expires'])) {
                            $expireTime = strtotime($otpData['reset_expires']);
                            if (time() <= $expireTime) {
                                $validOTP = true;
                            } else {
                                $error = "Mã OTP đã hết hạn. Vui lòng yêu cầu mã mới.";
                            }
                        } else {
                            // Nếu không có reset_expires, chỉ kiểm tra OTP
                            $validOTP = true;
                        }
                    }
                }
            } catch (PDOException $e) {
                // Nếu không có cột reset_code/reset_expires, bỏ qua
            }
            
            // Kiểm tra từ session (development mode hoặc fallback)
            if (!$validOTP) {
                if (isset($_SESSION['reset_otp']) && isset($_SESSION['reset_otp_expires'])) {
                    if ($_SESSION['reset_otp'] === $otp && time() <= $_SESSION['reset_otp_expires']) {
                        $validOTP = true;
                    } elseif (time() > $_SESSION['reset_otp_expires']) {
                        $error = "Mã OTP đã hết hạn. Vui lòng yêu cầu mã mới.";
                    }
                } elseif (isset($_SESSION['reset_otp']) && $_SESSION['reset_otp'] === $otp) {
                    // Fallback: không có expire time trong session
                    $validOTP = true;
                }
            }

            if ($validOTP) {
                // Cập nhật mật khẩu mới
                $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                try {
                    $updateStmt = $conn->prepare("UPDATE users_id SET password_hash = ?, reset_code = NULL, reset_expires = NULL WHERE email = ?");
                    $updateStmt->execute([$passwordHash, $email]);
                } catch (PDOException $e) {
                    // Nếu không có cột reset_code/reset_expires, chỉ cập nhật password
                    $updateStmt = $conn->prepare("UPDATE users_id SET password_hash = ? WHERE email = ?");
                    $updateStmt->execute([$passwordHash, $email]);
                }

                // Xóa session
                unset($_SESSION['reset_email']);
                unset($_SESSION['reset_otp']);
                unset($_SESSION['reset_otp_expires']);

                $success = "Đặt lại mật khẩu thành công! Bạn có thể đăng nhập ngay.";
            } else {
                $error = "Mã OTP không đúng hoặc đã hết hạn.";
            }
        }
    }
}

?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Đặt lại mật khẩu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5" style="max-width:420px;">
    <h3 class="text-center mb-3">Đặt lại mật khẩu</h3>

    <?php if ($error): ?>
        <div class="alert alert-danger text-center small">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success text-center">
            <?= htmlspecialchars($success) ?>
            <div class="mt-3">
                <a href="trangchu.php?show_login=1" class="btn btn-dark">Đăng nhập ngay</a>
            </div>
        </div>
    <?php else: ?>
        <form method="post" class="card p-4 shadow-sm">
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" value="<?= htmlspecialchars($email) ?>" disabled>
                <small class="text-muted">Mã OTP đã được gửi đến email này</small>
            </div>

            <div class="mb-3">
                <label class="form-label">Mã OTP <span class="text-danger">*</span></label>
                <input type="text" name="otp" class="form-control" placeholder="Nhập mã OTP 6 số" required maxlength="6" pattern="[0-9]{6}">
            </div>

            <div class="mb-3">
                <label class="form-label">Mật khẩu mới <span class="text-danger">*</span></label>
                <input type="password" name="new_password" class="form-control" placeholder="Tối thiểu 6 ký tự" required minlength="6">
            </div>

            <div class="mb-3">
                <label class="form-label">Xác nhận mật khẩu mới <span class="text-danger">*</span></label>
                <input type="password" name="confirm_password" class="form-control" placeholder="Nhập lại mật khẩu mới" required minlength="6">
            </div>

            <button type="submit" class="btn btn-dark w-100">Đặt lại mật khẩu</button>

            <div class="text-center small mt-3">
                <a href="forgot_password.php">Gửi lại mã OTP</a> | 
                <a href="trangchu.php?show_login=1">Quay lại đăng nhập</a>
            </div>
        </form>
    <?php endif; ?>
</div>

</body>
</html>

