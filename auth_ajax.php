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

    $stmt = $conn->prepare(
        "SELECT id, fullname, email, password_hash 
         FROM users_id 
         WHERE email = ?"
    );
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['fullname'] = $user['fullname'];
        $_SESSION['email']    = $user['email'];

        echo json_encode([
            'success' => true, 
            'message' => 'Đăng nhập thành công!',
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

    // INSERT USER
    $ins = $conn->prepare("
        INSERT INTO users_id (fullname, email, password_hash, verification_code, is_verified)
        VALUES (?, ?, ?, ?, 0)
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

echo json_encode(['success' => false, 'message' => 'Invalid action']);
?>

