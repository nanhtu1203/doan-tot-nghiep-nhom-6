<?php
session_start();

// Xóa tất cả session
$_SESSION = array();

// Xóa cookie session nếu có
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Hủy session
session_destroy();

// Kiểm tra nếu là AJAX request
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    // Trả về JSON response cho AJAX
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Đăng xuất thành công', 'redirect' => 'trangchu.php']);
    exit;
} else {
    // Redirect về trang chủ
    header("Location: trangchu.php");
    exit;
}
?>

