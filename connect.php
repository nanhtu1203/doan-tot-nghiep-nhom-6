<?php
$host = 'localhost';
$db   = 'Shop';
$user = 'root'; // mặc định XAMPP
$pass = '';     // rỗng nếu bạn chưa đặt mật khẩu MySQL

try {
    $conn = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("Lỗi kết nối CSDL: " . $e->getMessage());
}
?>
