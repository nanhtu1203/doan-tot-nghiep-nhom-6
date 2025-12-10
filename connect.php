<?php
$host = "localhost:3306";   // máy chủ MySQL
$user = "root";        // tài khoản mặc định của XAMPP
$pass = "";            // mật khẩu trống (XAMPP)
$db   = "shop";        // TÊN DATABASE BẠN ĐANG DÙNG

try {
    // Kết nối PDO
    $conn = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass
    );

    // Bật lỗi PDO để dễ debug
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {

    // Báo lỗi rõ ràng
    die("Lỗi kết nối Database: " . $e->getMessage());
}
?>
