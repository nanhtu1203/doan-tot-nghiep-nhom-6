<?php
session_start();
require 'connect.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

if ($action === 'login') {
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đầy đủ Email và Mật khẩu.']);
        exit;
    }

    // Kiểm tra trong bảng admins trước
    $stmtAdmin = $conn->prepare("SELECT id, fullname, email, password_hash FROM admins WHERE email = ? AND is_active = 1");
    $stmtAdmin->execute([$email]);
    $admin = $stmtAdmin->fetch(PDO::FETCH_ASSOC);

    if ($admin && password_verify($password, $admin['password_hash'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['fullname'];
        $_SESSION['admin_email'] = $admin['email'];
        $_SESSION['role'] = 'admin';

        echo json_encode([
            'success' => true, 
            'message' => 'Đăng nhập thành công!',
            'role' => 'admin',
            'redirect' => 'admin_dashboard.php',
            'user' => [
                'fullname' => $admin['fullname'],
                'email' => $admin['email']
            ]
        ]);
        exit;
    }

    // Kiểm tra trong bảng sellers
    $stmtSeller = $conn->prepare("SELECT id, fullname, email, password_hash, shop_name FROM sellers WHERE email = ?");
    $stmtSeller->execute([$email]);
    $seller = $stmtSeller->fetch(PDO::FETCH_ASSOC);

    if ($seller && password_verify($password, $seller['password_hash'])) {
        $_SESSION['seller_id'] = $seller['id'];
        $_SESSION['seller_name'] = $seller['fullname'];
        $_SESSION['shop_name'] = $seller['shop_name'];
        $_SESSION['role'] = 'seller';

        echo json_encode([
            'success' => true, 
            'message' => 'Đăng nhập thành công!',
            'role' => 'seller',
            'redirect' => 'seller_dashboard.php',
            'user' => [
                'fullname' => $seller['fullname'],
                'email' => $seller['email']
            ]
        ]);
        exit;
    }

    // Kiểm tra trong bảng users_id (customer)
    $stmt = $conn->prepare(
        "SELECT id, fullname, email, password_hash, role 
         FROM users_id 
         WHERE email = ?"
    );
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['fullname'] = $user['fullname'];
        $_SESSION['email']    = $user['email'];
        $_SESSION['role']    = $user['role'] ?? 'customer';

        $redirectUrl = 'trangchu.php';
        if ($user['role'] === 'admin') {
            $redirectUrl = 'admin_dashboard.php';
        }

        echo json_encode([
            'success' => true, 
            'message' => 'Đăng nhập thành công!',
            'role' => $user['role'] ?? 'customer',
            'redirect' => $redirectUrl,
            'user' => [
                'fullname' => $user['fullname'],
                'email' => $user['email']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Sai thông tin đăng nhập. Vui lòng kiểm tra lại.']);
    }
    exit;
}

if ($action === 'register') {
    $fullname = trim($_POST['fullname'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($fullname === '' || $email === '' || $password === '') {
        echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin.']);
        exit;
    }

    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Mật khẩu phải có ít nhất 6 ký tự.']);
        exit;
    }

    // CHECK EMAIL TỒN TẠI
    $check = $conn->prepare("SELECT id FROM users_id WHERE email = ?");
    $check->execute([$email]);

    if ($check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Email đã tồn tại. Vui lòng dùng email khác.']);
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $verificationCode = random_int(100000, 999999);

    // INSERT USER (mặc định role = customer)
    $ins = $conn->prepare("
        INSERT INTO users_id (fullname, email, password_hash, verification_code, is_verified, role)
        VALUES (?, ?, ?, ?, 0, 'customer')
    ");

    $ok = $ins->execute([$fullname, $email, $hash, $verificationCode]);

    if ($ok) {
        // LOCALHOST – show mã xác minh
        if ($_SERVER['SERVER_NAME'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
            $link = "http://" . $_SERVER['HTTP_HOST'] . "/doantotnghiep/php/verify.php?email=" 
                  . urlencode($email) . "&code=" . urlencode($verificationCode);

            echo json_encode([
                'success' => true, 
                'message' => "Đăng ký thành công!<br>Mã xác minh: <strong>$verificationCode</strong><br><a href='$link' target='_blank'>Click để xác minh</a>",
                'verification_code' => $verificationCode
            ]);
        } else {
            // Gửi email (nếu có cấu hình)
            echo json_encode([
                'success' => true, 
                'message' => 'Đăng ký thành công! Vui lòng kiểm tra email để xác minh tài khoản.'
            ]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra. Vui lòng thử lại.']);
    }
    exit;
}

if ($action === 'forgot_password') {
    $email = trim($_POST['email'] ?? '');

    if ($email === '') {
        echo json_encode(['success' => false, 'message' => 'Vui lòng nhập Email.']);
        exit;
    }

    // Kiểm tra email có tồn tại không
    $stmt = $conn->prepare("SELECT id FROM users_id WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Email này không tồn tại trong hệ thống!']);
        exit;
    }

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

    // Lưu vào session
    $_SESSION['reset_email'] = $email;
    $_SESSION['reset_otp'] = $otp;
    $_SESSION['reset_otp_expires'] = $expireTime;

    // Kiểm tra PHPMailer có sẵn không
    $phpmailerAvailable = false;
    if (file_exists(__DIR__ . '/../PHPMailer/src/PHPMailer.php')) {
        require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
        require_once __DIR__ . '/../PHPMailer/src/SMTP.php';
        require_once __DIR__ . '/../PHPMailer/src/Exception.php';
        $phpmailerAvailable = true;
    }

    // Gửi email nếu có PHPMailer
    $emailSent = false;
    if ($phpmailerAvailable) {
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'yourgmail@gmail.com';
            $mail->Password   = 'xxxxxxxxxxxxxxxx';
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;
            $mail->CharSet    = 'UTF-8';
            $mail->setFrom('yourgmail@gmail.com', 'Support Web');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Ma OTP dat lai mat khau';
            $mail->Body    = '
                <h2>Ma OTP cua ban: <span style="color:#d9534f;">' . $otp . '</span></h2>
                <p>Ma co hieu luc trong 10 phut. Khong chia se ma nay cho bat ky ai.</p>
            ';
            $mail->send();
            $emailSent = true;
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            $emailSent = false;
        }
    }

    if ($emailSent) {
        echo json_encode([
            'success' => true,
            'message' => 'Mã OTP đã được gửi đến email của bạn. Vui lòng kiểm tra hộp thư.',
            'redirect' => 'reset_password.php'
        ]);
    } else {
        // Development mode: hiển thị OTP
        echo json_encode([
            'success' => true,
            'message' => 'Mã OTP đã được tạo. Vui lòng sử dụng mã bên dưới (chế độ development).',
            'otp' => $otp
        ]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
?>

