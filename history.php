<?php
session_start();
require 'connect.php';

// Bắt buộc đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?message=Vui lòng đăng nhập để xem lịch sử mua hàng");
    exit;
}

$userId   = $_SESSION['user_id'];
$fullname = $_SESSION['fullname'] ?? '';
$email    = $_SESSION['email'] ?? '';

// XỬ LÝ HỦY ĐƠN / TRẢ HÀNG
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;

    if ($orderId > 0) {
        // Lấy đơn hàng thuộc đúng user
        $stmt = $conn->prepare("SELECT id, status FROM orders WHERE id = ? AND user_id = ?");
        $stmt->execute([$orderId, $userId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order) {
            $status = $order['status'];

            // HỦY ĐƠN
            if (isset($_POST['cancel_order'])) {
                // Cho phép hủy khi đơn chưa giao
                $allowCancel = ['Đang xử lý', 'Đã duyệt'];
                if (in_array($status, $allowCancel, true)) {
                    $upd = $conn->prepare("UPDATE orders SET status = 'Đã hủy' WHERE id = ?");
                    $upd->execute([$orderId]);
                }
            }

            // TRẢ HÀNG
            if (isset($_POST['return_order'])) {
                // Cho phép trả khi đơn đã giao
                if ($status === 'Đã giao') {
                    // Bạn có thể đổi text này, ví dụ 'Yêu cầu trả hàng'
                    $upd = $conn->prepare("UPDATE orders SET status = 'Yêu cầu trả hàng' WHERE id = ?");
                    $upd->execute([$orderId]);
                }
            }
        }
    }

    // Reload lại trang để tránh F5 gửi lại form
    header("Location: history.php");
    exit;
}

// Lấy danh sách đơn hàng của user
// orders: id, order_code, user_id, customer_name, customer_phone, customer_addr,
//         total_amount, status, created_at
$stmt = $conn->prepare("
    SELECT 
        o.id,
        o.order_code,
        o.customer_name,
        o.customer_phone,
        o.customer_addr,
        o.total_amount,
        o.status,
        o.created_at,
        SUM(oi.quantity * oi.price) AS calc_total
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.user_id = ?
    GROUP BY 
        o.id,
        o.order_code,
        o.customer_name,
        o.customer_phone,
        o.customer_addr,
        o.total_amount,
        o.status,
        o.created_at
    ORDER BY o.created_at DESC
");
$stmt->execute([$userId]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

function vnd($n) {
    return number_format((int)$n, 0, ',', '.') . '₫';
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Lịch sử mua hàng</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">
    <h3 class="mb-3">Lịch sử mua hàng</h3>

    <p><strong>Tài khoản:</strong> <?php echo htmlspecialchars($fullname); ?></p>
    <p><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>

    <?php if (empty($orders)): ?>
        <div class="alert alert-info">
            Bạn chưa có đơn hàng nào.
        </div>
        <a href="trangchu.php" class="btn btn-secondary">Quay lại trang chủ</a>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle bg-white">
                <thead class="table-light">
                    <tr>
                        <th>Mã đơn</th>
                        <th>Người nhận</th>
                        <th>SĐT</th>
                        <th>Địa chỉ</th>
                        <th>Trạng thái</th>
                        <th>Tổng tiền</th>
                        <th>Ngày tạo</th>
                        <th>Trả hàng</th>
                        <th>Hủy đơn</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($orders as $o): ?>
                    <?php
                        $status = $o['status'];
                        $total  = $o['calc_total'] !== null ? $o['calc_total'] : $o['total_amount'];

                        // điều kiện hiển thị nút
                        $canCancel = in_array($status, ['Đang xử lý', 'Đã duyệt'], true);
                        $canReturn = ($status === 'Đã giao');
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($o['order_code']); ?></td>
                        <td><?php echo htmlspecialchars($o['customer_name']); ?></td>
                        <td><?php echo htmlspecialchars($o['customer_phone']); ?></td>
                        <td><?php echo htmlspecialchars($o['customer_addr']); ?></td>
                        <td><?php echo htmlspecialchars($status); ?></td>
                        <td><?php echo vnd($total); ?></td>
                        <td><?php echo htmlspecialchars($o['created_at']); ?></td>

                        <!-- Cột TRẢ HÀNG -->
                        <td class="text-center">
                            <?php if ($canReturn): ?>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                                    <button type="submit" name="return_order"
                                            class="btn btn-sm btn-outline-primary"
                                            onclick="return confirm('Gửi yêu cầu trả hàng cho đơn này?');">
                                        Trả hàng
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="text-muted small">–</span>
                            <?php endif; ?>
                        </td>

                        <!-- Cột HỦY ĐƠN -->
                        <td class="text-center">
                            <?php if ($canCancel): ?>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                                    <button type="submit" name="cancel_order"
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Bạn chắc chắn muốn hủy đơn này?');">
                                        Hủy đơn
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="text-muted small">–</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <a href="trangchu.php" class="btn btn-secondary mt-3">Quay lại trang chủ</a>
    <?php endif; ?>
</div>

</body>
</html>
