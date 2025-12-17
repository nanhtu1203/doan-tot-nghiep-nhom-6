<?php
session_start();
require 'connect.php';

$orderCode = $_GET['order_code'] ?? '';

// Lấy thông tin đơn hàng
$order = null;
if (!empty($orderCode)) {
    $stmt = $conn->prepare("
        SELECT id, order_code, customer_name, customer_phone, customer_addr, 
               total_amount, status, created_at
        FROM orders 
        WHERE order_code = ?
    ");
    $stmt->execute([$orderCode]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
}

$pageTitle = 'Đặt hàng thành công';
include 'header.php';

function vnd($n) {
    return number_format((int)$n, 0, ',', '.') . '₫';
}
?>

<div class="container py-5" style="max-width: 700px;">
    <?php if ($order): ?>
        <div class="text-center mb-4">
            <div class="mb-3">
                <i class="bi bi-check-circle-fill text-success" style="font-size: 80px;"></i>
            </div>
            <h2 class="fw-bold text-success mb-2">Đặt hàng thành công!</h2>
            <p class="text-muted">Cảm ơn bạn đã mua sắm tại Adodas</p>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Thông tin đơn hàng</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-4 fw-semibold">Mã đơn hàng:</div>
                    <div class="col-8">
                        <strong class="text-danger"><?= htmlspecialchars($order['order_code']) ?></strong>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-4 fw-semibold">Người nhận:</div>
                    <div class="col-8"><?= htmlspecialchars($order['customer_name']) ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-4 fw-semibold">Số điện thoại:</div>
                    <div class="col-8"><?= htmlspecialchars($order['customer_phone']) ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-4 fw-semibold">Địa chỉ:</div>
                    <div class="col-8"><?= nl2br(htmlspecialchars($order['customer_addr'])) ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-4 fw-semibold">Tổng tiền:</div>
                    <div class="col-8">
                        <strong class="text-danger fs-5"><?= vnd($order['total_amount']) ?></strong>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-4 fw-semibold">Trạng thái:</div>
                    <div class="col-8">
                        <span class="badge bg-warning"><?= htmlspecialchars($order['status']) ?></span>
                    </div>
                </div>
                <div class="row">
                    <div class="col-4 fw-semibold">Ngày đặt:</div>
                    <div class="col-8"><?= htmlspecialchars($order['created_at']) ?></div>
                </div>
            </div>
        </div>

        <div class="alert alert-info">
            <h6 class="fw-bold mb-2">Lưu ý:</h6>
            <ul class="mb-0">
                <li>Vui lòng lưu mã đơn hàng để tra cứu đơn hàng sau này</li>
                <li>Chúng tôi sẽ liên hệ với bạn trong thời gian sớm nhất</li>
                <li>Bạn có thể xem chi tiết đơn hàng trong <a href="history.php">Lịch sử mua hàng</a></li>
            </ul>
        </div>

        <div class="d-flex gap-2 justify-content-center">
            <a href="trangchu.php" class="btn btn-outline-secondary">Tiếp tục mua sắm</a>
            <a href="history.php" class="btn btn-dark">Xem lịch sử đơn hàng</a>
            <a href="track_order.php?order_code=<?= urlencode($orderCode) ?>" class="btn btn-outline-primary">Tra cứu đơn hàng</a>
        </div>
    <?php else: ?>
        <div class="text-center">
            <div class="mb-3">
                <i class="bi bi-exclamation-triangle text-warning" style="font-size: 80px;"></i>
            </div>
            <h3 class="mb-3">Không tìm thấy đơn hàng</h3>
            <p class="text-muted mb-4">Mã đơn hàng không hợp lệ hoặc đã hết hạn.</p>
            <a href="trangchu.php" class="btn btn-dark">Quay lại trang chủ</a>
        </div>
    <?php endif; ?>
</div>

<script>
// Xóa giỏ hàng sau khi đặt hàng thành công
if (localStorage.getItem('cart')) {
    localStorage.removeItem('cart');
    // Cập nhật badge giỏ hàng
    const badge = document.querySelector('.cart .badge');
    if (badge) {
        badge.textContent = '0';
    }
}
</script>

<?php include 'footer.php'; ?>

