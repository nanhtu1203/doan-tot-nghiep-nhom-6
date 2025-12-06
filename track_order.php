<?php
// track_order.php
session_start();
require 'connect.php';

$order  = null;
$items  = [];
$error  = '';
$codeInput = $_GET['order_code'] ?? '';

if (isset($_GET['order_code'])) {
    $orderCode = trim($_GET['order_code']);

    if ($orderCode === '') {
        $error = 'Vui lòng nhập mã đơn hàng.';
    } else {
        // Tìm đơn trong bảng orders theo order_code
        $stmt = $conn->prepare("
            SELECT 
                id,
                order_code,
                customer_name,
                customer_phone,
                customer_addr,
                total_amount,
                status,
                created_at
            FROM orders
            WHERE order_code = ?
            LIMIT 1
        ");
        $stmt->execute([$orderCode]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            $error = 'Không tìm thấy đơn hàng với mã này.';
        } else {
            // Lấy danh sách sản phẩm trong đơn (nếu cần)
            $stmt2 = $conn->prepare("
                SELECT 
                    oi.product_id,
                    oi.quantity,
                    oi.price,
                    p.name AS product_name
                FROM order_items oi
                LEFT JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = ?
            ");
            $stmt2->execute([$order['id']]);
            $items = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        }
    }
}

function vnd($n) {
    return number_format((int)$n, 0, ',', '.') . '₫';
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Tra cứu đơn hàng</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
      rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4" style="max-width:800px">
    <h3 class="mb-3">Tra cứu đơn hàng</h3>

    <!-- Form tra mã đơn -->
    <form method="get" class="card mb-4">
        <div class="card-body d-flex flex-wrap gap-2 align-items-center">
            <label class="form-label mb-0 me-2">Nhập mã đơn hàng:</label>
            <input
                type="text"
                name="order_code"
                class="form-control flex-grow-1"
                placeholder="Ví dụ: HDABC123"
                required
                value="<?php echo htmlspecialchars($codeInput); ?>"
            >
            <button type="submit" class="btn btn-dark">
                Tra cứu
            </button>
        </div>
    </form>

    <?php if ($error): ?>
        <div class="alert alert-warning">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($order): ?>
        <div class="card mb-3">
            <div class="card-header fw-semibold">
                Thông tin đơn hàng
            </div>
            <div class="card-body">
                <p><strong>Mã đơn:</strong> <?php echo htmlspecialchars($order['order_code']); ?></p>
                <p><strong>Người nhận:</strong> <?php echo htmlspecialchars($order['customer_name'] ?? ''); ?></p>
                <p><strong>Số điện thoại:</strong> <?php echo htmlspecialchars($order['customer_phone'] ?? ''); ?></p>
                <p><strong>Địa chỉ:</strong> <?php echo nl2br(htmlspecialchars($order['customer_addr'] ?? '')); ?></p>
                <p><strong>Tổng tiền:</strong> <span class="text-danger fw-bold">
                    <?php echo vnd($order['total_amount']); ?>
                </span></p>
                <p><strong>Trạng thái:</strong> <?php echo htmlspecialchars($order['status']); ?></p>
                <p><strong>Thời gian đặt:</strong>
                    <?php echo htmlspecialchars($order['created_at']); ?>
                </p>
            </div>
        </div>

        <?php if (!empty($items)): ?>
            <div class="card">
                <div class="card-header fw-semibold">
                    Sản phẩm trong đơn
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0 table-sm align-middle">
                            <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Sản phẩm</th>
                                <th class="text-center">SL</th>
                                <th class="text-end">Đơn giá</th>
                                <th class="text-end">Thành tiền</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            $i = 1;
                            foreach ($items as $it):
                                $lineTotal = $it['price'] * $it['quantity'];
                                $name = $it['product_name'] ?: ('Sản phẩm #' . $it['product_id']);
                            ?>
                                <tr>
                                    <td><?php echo $i++; ?></td>
                                    <td><?php echo htmlspecialchars($name); ?></td>
                                    <td class="text-center"><?php echo (int)$it['quantity']; ?></td>
                                    <td class="text-end"><?php echo vnd($it['price']); ?></td>
                                    <td class="text-end"><?php echo vnd($lineTotal); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    <?php endif; ?>

    <div class="mt-4">
        <a href="trangchu.php" class="btn btn-outline-secondary">
            Quay lại trang chủ
        </a>
    </div>
</div>

</body>
</html>
