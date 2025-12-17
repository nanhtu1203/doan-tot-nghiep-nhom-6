<?php
session_start();
require 'connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$couponCode = trim($_POST['code'] ?? '');
$totalAmount = floatval($_POST['total'] ?? 0);

if (empty($couponCode)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập mã khuyến mãi']);
    exit;
}

// Lấy thông tin mã khuyến mãi
$stmt = $conn->prepare("SELECT * FROM coupons WHERE code = ? AND is_active = 1");
$stmt->execute([$couponCode]);
$coupon = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$coupon) {
    echo json_encode(['success' => false, 'message' => 'Mã khuyến mãi không tồn tại hoặc đã bị vô hiệu hóa']);
    exit;
}

// Kiểm tra thời gian hiệu lực
$now = date('Y-m-d H:i:s');
if ($coupon['start_date'] && $coupon['start_date'] > $now) {
    echo json_encode(['success' => false, 'message' => 'Mã khuyến mãi chưa có hiệu lực']);
    exit;
}

if ($coupon['end_date'] && $coupon['end_date'] < $now) {
    echo json_encode(['success' => false, 'message' => 'Mã khuyến mãi đã hết hạn']);
    exit;
}

// Kiểm tra số lượt sử dụng
if ($coupon['usage_limit'] && $coupon['used_count'] >= $coupon['usage_limit']) {
    echo json_encode(['success' => false, 'message' => 'Mã khuyến mãi đã hết lượt sử dụng']);
    exit;
}

// Kiểm tra đơn tối thiểu
if ($coupon['min_order'] > 0 && $totalAmount < $coupon['min_order']) {
    echo json_encode([
        'success' => false, 
        'message' => 'Đơn hàng tối thiểu ' . number_format($coupon['min_order'], 0, ',', '.') . '₫ để sử dụng mã này'
    ]);
    exit;
}

// Tính toán giảm giá
$discountAmount = 0;
if ($coupon['type'] === 'percent') {
    $discountAmount = ($totalAmount * $coupon['value']) / 100;
    if ($coupon['max_discount'] && $discountAmount > $coupon['max_discount']) {
        $discountAmount = $coupon['max_discount'];
    }
} else {
    $discountAmount = $coupon['value'];
}

// Đảm bảo không giảm quá tổng tiền
if ($discountAmount > $totalAmount) {
    $discountAmount = $totalAmount;
}

$finalAmount = max(0, $totalAmount - $discountAmount);

echo json_encode([
    'success' => true,
    'message' => 'Áp dụng mã khuyến mãi thành công',
    'coupon' => [
        'id' => $coupon['id'],
        'code' => $coupon['code'],
        'type' => $coupon['type'],
        'value' => $coupon['value']
    ],
    'discount' => $discountAmount,
    'final_amount' => $finalAmount
]);

