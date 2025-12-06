<?php
session_start();
require 'connect.php';

// Chỉ chuyển hướng nếu CHƯA có session
if (empty($_SESSION['seller_name'])) {
    header("Location: seller_login.php");
    exit;
}

$sellerName = $_SESSION['seller_name'];



// --------- HÀM FORMAT TIỀN ----------
function vnd($n) {
    return number_format((int)$n, 0, ',', '.') . 'đ';
}

// --------- XỬ LÝ ĐỔI TRẠNG THÁI ĐƠN HÀNG ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $status  = $_POST['status'] ?? '';

    // thêm 'Đã trả hàng' vào danh sách trạng thái đích cho phép
    $allow = ['Đang xử lý','Đã duyệt','Đang giao','Đã giao','Đã hủy','Đã trả hàng'];
    if ($orderId > 0 && in_array($status, $allow, true)) {
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$status, $orderId]);
    }
    header('Location: seller_login.php');
    exit;
}

// --------- LẤY TAB HIỆN TẠI ----------
$tab = $_GET['tab'] ?? 'products';

// --------- LẤY DỮ LIỆU PHỤC VỤ TỪNG TAB ----------

// 1. TAB XỬ LÝ ĐƠN HÀNG
$ordersProcessing = [];
if ($tab === 'orders') {
    // thêm 'Yêu cầu trả hàng' để người bán xử lý
    $stmt = $conn->prepare("
        SELECT id, order_code, customer_name, customer_phone,
               total_amount, status, created_at
        FROM orders
        WHERE status IN ('Đang xử lý','Đã duyệt','Đang giao','Yêu cầu trả hàng')
        ORDER BY created_at DESC
    ");
    $stmt->execute();
    $ordersProcessing = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 2. TAB LỊCH SỬ ĐÃ BÁN + DOANH THU
$totalOrdersTemp = $totalRevenueTemp = $totalRevenueFinal = 0;
$salesHistory = [];
if ($tab === 'sales') {
    // Doanh thu tạm tính: đơn đã duyệt + đang giao + đã giao
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) AS cnt,
            COALESCE(SUM(total_amount),0) AS sum
        FROM orders
        WHERE status IN ('Đã duyệt','Đang giao','Đã giao')
    ");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalOrdersTemp   = (int)$row['cnt'];
    $totalRevenueTemp  = (int)$row['sum'];

    // Doanh thu hoàn thành: chỉ tính đơn đã giao
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(total_amount),0) AS sum
        FROM orders
        WHERE status = 'Đã giao'
    ");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalRevenueFinal = (int)$row['sum'];

    // Danh sách lịch sử: thêm cả đơn trả hàng
    $stmt = $conn->prepare("
        SELECT id, order_code, customer_name, customer_phone,
               total_amount, status, created_at
        FROM orders
        WHERE status IN ('Đã duyệt','Đang giao','Đã giao','Đã hủy','Yêu cầu trả hàng','Đã trả hàng')
        ORDER BY created_at DESC
    ");
    $stmt->execute();
    $salesHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 3. TAB QUẢN LÝ KHÁCH HÀNG
$customers = [];
if ($tab === 'customers') {
    $stmt = $conn->prepare("
        SELECT 
            customer_name,
            customer_phone,
            customer_addr,
            COUNT(*) AS total_orders,
            COALESCE(SUM(total_amount),0) AS total_spent
        FROM orders
        GROUP BY customer_name, customer_phone, customer_addr
        ORDER BY total_spent DESC
    ");
    $stmt->execute();
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 4. TAB THEO DÕI ĐƠN HÀNG (TIMELINE ĐƠN GẦN ĐÂY)
$orderTimeline = [];
if ($tab === 'tracking') {
    $stmt = $conn->prepare("
        SELECT id, order_code, customer_name, customer_phone,
               total_amount, status, created_at
        FROM orders
        ORDER BY created_at DESC
        LIMIT 50
    ");
    $stmt->execute();
    $orderTimeline = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<title>Bảng điều khiển người bán</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
/* ================== RESET ================== */
*,
*::before,
*::after {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: "Segoe UI", Arial, sans-serif;
    background: #fff4ee;          /* nền trắng cam kiểu Shopee */
    color: #222;
}

/* ================== NAVBAR ================== */
.navbar {
    background: #ff5722;
    color: #fff;
    border-bottom: 1px solid #ff8a50;
}

.navbar .navbar-brand {
    font-weight: 600;
}

.navbar .btn-outline-light {
    border-color: #ffe0d1;
    color: #ffe0d1;
}

.navbar .btn-outline-light:hover {
    background: #ffe0d1;
    color: #c62828;
}

/* ================== TABS ================== */
.nav-tabs {
    background: #ffffff;
    border-bottom: 1px solid #ffd7cc;
}

.nav-tabs .nav-link {
    padding: 10px 20px;
    color: #555;
    border: none;
    border-radius: 0;
}

.nav-tabs .nav-link:hover {
    color: #ff5722;
    background: #fff7f3;
}

.nav-tabs .nav-link.active {
    color: #ff5722;
    font-weight: 600;
    border-bottom: 3px solid #ff5722;
    background: #ffffff;
}

/* ================== CARD WRAPPER ================== */
.container-fluid {
    max-width: 1200px;
}

/* card tone trắng cam */
.card {
    background: #ffffff;
    border-radius: 10px;
    border: 1px solid #ffd7cc;
}

.card-header {
    background: #fff3ec;
    border-bottom: 1px solid #ffd7cc;
    color: #ff5722;
    font-weight: 600;
}

.card-body {
    background: #ffffff;
}

/* ================== KPI / DOANH THU ================== */
.summary-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 10px;
}

.summary-card {
    background: #020617;
    border-radius: 10px;
    padding: 14px 16px;
    border: 1px solid #1e293b;
    color: #e5e7eb;
}

.summary-card-title {
    font-size: 13px;
    opacity: 0.7;
}

.summary-card-value {
    margin-top: 4px;
    font-size: 24px;
    font-weight: 700;
}

.summary-card-value.temp { color: #22c55e; }
.summary-card-value.done { color: #38bdf8; }

/* ================== TABLE ================== */
.table {
    border-collapse: collapse;
    background: #ffffff;
}

.table thead {
    background: #fff0e6;
}

.table thead th {
    color: #ff5722;
    font-weight: 600;
    border-bottom: 1px solid #ffd7cc;
    font-size: 13px;
}

.table tbody td {
    border-bottom: 1px solid #ffe2d5;
    font-size: 13px;
    color: #333;
}

.table tbody tr:hover {
    background: #fff7f3;
}

/* ================== TRẠNG THÁI ĐƠN HÀNG ================== */
.badge-status {
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 600;
    display: inline-block;
}

.badge-status.status-pending {   /* Đang xử lý */
    background: #ffe5d6;
    color: #ff5722;
}

.badge-status.status-approved {  /* Đã duyệt */
    background: #ffd1ba;
    color: #d84315;
}

.badge-status.status-shipping {  /* Đang giao */
    background: #ffecb3;
    color: #f57c00;
}

.badge-status.status-done {      /* Đã giao */
    background: #c8e6c9;
    color: #2e7d32;
}

.badge-status.status-cancel {    /* Đã hủy */
    background: #ffcdd2;
    color: #c62828;
}

/* yêu cầu trả hàng */
.badge-status.status-return-request {
    background: #e3f2fd;
    color: #1565c0;
}

/* đã trả hàng xong */
.badge-status.status-return-done {
    background: #d1c4e9;
    color: #4527a0;
}

/* ================== BUTTONS ================== */
.btn-orange,
.btn-primary {
    background: #ff5722;
    border-color: #ff5722;
}

.btn-orange:hover,
.btn-primary:hover {
    background: #e64a19;
    border-color: #e64a19;
}

/* Responsive */
@media (max-width: 992px) {
    .summary-row {
        grid-template-columns: 1fr;
    }
}
</style>
</head>
<body>

<nav class="navbar navbar-dark px-3">
    <span class="navbar-brand mb-0 h5">Bảng điều khiển người bán</span>
    <div class="d-flex gap-2">
        <a href="trangchu.php" class="btn btn-outline-light btn-sm">Về trang mua hàng</a>
        <a href="seller_login.php" class="btn btn-danger btn-sm">Đăng xuất</a>

    </div>
</nav>

<ul class="nav nav-tabs seller-tabs px-3">
    <li class="nav-item">
        <a class="nav-link <?= $tab==='products'?'active':'' ?>" href="?tab=products">Quản lý sản phẩm</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab==='orders'?'active':'' ?>" href="?tab=orders">Xử lý đơn hàng</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab==='sales'?'active':'' ?>" href="?tab=sales">Lịch sử đã bán</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab==='customers'?'active':'' ?>" href="?tab=customers">Quản lý khách hàng</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab==='tracking'?'active':'' ?>" href="?tab=tracking">Theo dõi đơn hàng</a>
    </li>
</ul>

<div class="container-fluid py-4">
    <?php if ($tab === 'products'): ?>
        <!-- TAB QUẢN LÝ SẢN PHẨM -->
        <div class="card">
            <div class="card-header">Quản lý sản phẩm</div>
            <div class="card-body">
                <p>Chuyển sang trang quản lý sản phẩm chi tiết:</p>
                <a href="seller_products.php" class="btn btn-orange btn-sm">Mở trang quản lý sản phẩm</a>
            </div>
        </div>

    <?php elseif ($tab === 'orders'): ?>
        <!-- TAB XỬ LÝ ĐƠN HÀNG -->
        <div class="card mb-3">
            <div class="card-header">Đơn hàng cần xử lý</div>
            <div class="card-body p-0">
                <?php if (!$ordersProcessing): ?>
                    <p class="p-3 text-secondary mb-0">Hiện chưa có đơn cần xử lý.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table mb-0 align-middle">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>Mã đơn</th>
                                <th>Khách hàng</th>
                                <th>SĐT</th>
                                <th>Tổng tiền</th>
                                <th>Trạng thái</th>
                                <th>Ngày tạo</th>
                                <th class="text-end">Hành động</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($ordersProcessing as $i => $o):
                                $statusClass = match($o['status']) {
                                    'Đang xử lý'      => 'status-pending',
                                    'Đã duyệt'        => 'status-approved',
                                    'Đang giao'       => 'status-shipping',
                                    'Đã giao'         => 'status-done',
                                    'Đã hủy'          => 'status-cancel',
                                    'Yêu cầu trả hàng'=> 'status-return-request',
                                    'Đã trả hàng'     => 'status-return-done',
                                    default           => 'status-pending'
                                };
                            ?>
                                <tr>
                                    <td><?= $i+1 ?></td>
                                    <td><strong><?= htmlspecialchars($o['order_code']) ?></strong></td>
                                    <td><?= htmlspecialchars($o['customer_name']) ?></td>
                                    <td><?= htmlspecialchars($o['customer_phone']) ?></td>
                                    <td><?= vnd($o['total_amount']) ?></td>
                                    <td>
                                        <span class="badge-status <?= $statusClass ?>">
                                            <?= htmlspecialchars($o['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($o['created_at']) ?></td>
                                    <td class="text-end">
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                            <input type="hidden" name="update_status" value="1">

                                            <?php if ($o['status'] === 'Đang xử lý'): ?>
                                                <input type="hidden" name="status" value="Đã duyệt">
                                                <button class="btn btn-success btn-sm">Duyệt đơn</button>

                                            <?php elseif ($o['status'] === 'Đã duyệt'): ?>
                                                <input type="hidden" name="status" value="Đang giao">
                                                <button class="btn btn-warning btn-sm">Đánh dấu đang giao</button>

                                            <?php elseif ($o['status'] === 'Đang giao'): ?>
                                                <input type="hidden" name="status" value="Đã giao">
                                                <button class="btn btn-primary btn-sm">Hoàn thành</button>

                                            <?php elseif ($o['status'] === 'Yêu cầu trả hàng'): ?>
                                                <!-- Nút duyệt trả hàng -->
                                                <input type="hidden" name="status" value="Đã trả hàng">
                                                <button class="btn btn-outline-primary btn-sm">
                                                    Duyệt trả hàng
                                                </button>
                                            <?php endif; ?>
                                        </form>

                                        <?php
                                        // Không cho hủy các trạng thái đã giao / đã trả / yêu cầu trả
                                        if (!in_array($o['status'], ['Đã giao','Đã trả hàng','Yêu cầu trả hàng'], true)): ?>
                                            <form method="post" class="d-inline ms-1">
                                                <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                                <input type="hidden" name="update_status" value="1">
                                                <input type="hidden" name="status" value="Đã hủy">
                                                <button class="btn btn-outline-danger btn-sm">Hủy</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <?php elseif ($tab === 'sales'): ?>
        <!-- TAB LỊCH SỬ ĐÃ BÁN + DOANH THU -->
        <div class="card mb-3">
            <div class="card-header">Tổng quan doanh thu</div>
            <div class="card-body">
                <div class="summary-row">
                    <div class="summary-card">
                        <div class="summary-card-title">
                            Tổng đơn đã bán (đã duyệt + đang giao + đã giao)
                        </div>
                        <div class="summary-card-value">
                            <?= $totalOrdersTemp ?>
                        </div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-card-title">
                            Doanh thu tạm tính
                        </div>
                        <div class="summary-card-value temp">
                            <?= vnd($totalRevenueTemp) ?>
                        </div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-card-title">
                            Doanh thu hoàn thành (đơn đã giao)
                        </div>
                        <div class="summary-card-value done">
                            <?= vnd($totalRevenueFinal) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Danh sách đơn đã xử lý</div>
            <div class="card-body p-0">
                <?php if (!$salesHistory): ?>
                    <p class="p-3 text-secondary mb-0">Chưa có đơn hàng trong lịch sử.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table mb-0 align-middle">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>Mã đơn</th>
                                <th>Khách hàng</th>
                                <th>SĐT</th>
                                <th>Tổng tiền</th>
                                <th>Trạng thái</th>
                                <th>Ngày tạo</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($salesHistory as $i => $o):
                                $statusClass = match($o['status']) {
                                    'Đang xử lý'      => 'status-pending',
                                    'Đã duyệt'        => 'status-approved',
                                    'Đang giao'       => 'status-shipping',
                                    'Đã giao'         => 'status-done',
                                    'Đã hủy'          => 'status-cancel',
                                    'Yêu cầu trả hàng'=> 'status-return-request',
                                    'Đã trả hàng'     => 'status-return-done',
                                    default           => 'status-pending'
                                };
                            ?>
                                <tr>
                                    <td><?= $i+1 ?></td>
                                    <td><strong><?= htmlspecialchars($o['order_code']) ?></strong></td>
                                    <td><?= htmlspecialchars($o['customer_name']) ?></td>
                                    <td><?= htmlspecialchars($o['customer_phone']) ?></td>
                                    <td><?= vnd($o['total_amount']) ?></td>
                                    <td><span class="badge-status <?= $statusClass ?>"><?= htmlspecialchars($o['status']) ?></span></td>
                                    <td><?= htmlspecialchars($o['created_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <?php elseif ($tab === 'customers'): ?>
        <!-- TAB QUẢN LÝ KHÁCH HÀNG -->
        <div class="card">
            <div class="card-header">Danh sách khách hàng đã mua</div>
            <div class="card-body p-0">
                <?php if (!$customers): ?>
                    <p class="p-3 text-secondary mb-0">Chưa có dữ liệu khách hàng.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table mb-0 align-middle">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>Khách hàng</th>
                                <th>SĐT</th>
                                <th>Địa chỉ</th>
                                <th>Số đơn đã mua</th>
                                <th>Tổng chi tiêu</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($customers as $i => $c): ?>
                                <tr>
                                    <td><?= $i+1 ?></td>
                                    <td><?= htmlspecialchars($c['customer_name']) ?></td>
                                    <td><?= htmlspecialchars($c['customer_phone']) ?></td>
                                    <td><?= htmlspecialchars($c['customer_addr']) ?></td>
                                    <td><?= (int)$c['total_orders'] ?></td>
                                    <td><?= vnd($c['total_spent']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <?php elseif ($tab === 'tracking'): ?>
        <!-- TAB THEO DÕI ĐƠN HÀNG -->
        <div class="card">
            <div class="card-header">Theo dõi đơn hàng gần đây</div>
            <div class="card-body p-0">
                <?php if (!$orderTimeline): ?>
                    <p class="p-3 text-secondary mb-0">Chưa có đơn hàng.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table mb-0 align-middle">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>Mã đơn</th>
                                <th>Khách hàng</th>
                                <th>SĐT</th>
                                <th>Tổng tiền</th>
                                <th>Trạng thái</th>
                                <th>Ngày tạo</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($orderTimeline as $i => $o):
                                $statusClass = match($o['status']) {
                                    'Đang xử lý'      => 'status-pending',
                                    'Đã duyệt'        => 'status-approved',
                                    'Đang giao'       => 'status-shipping',
                                    'Đã giao'         => 'status-done',
                                    'Đã hủy'          => 'status-cancel',
                                    'Yêu cầu trả hàng'=> 'status-return-request',
                                    'Đã trả hàng'     => 'status-return-done',
                                    default           => 'status-pending'
                                };
                            ?>
                                <tr>
                                    <td><?= $i+1 ?></td>
                                    <td><strong><?= htmlspecialchars($o['order_code']) ?></strong></td>
                                    <td><?= htmlspecialchars($o['customer_name']) ?></td>
                                    <td><?= htmlspecialchars($o['customer_phone']) ?></td>
                                    <td><?= vnd($o['total_amount']) ?></td>
                                    <td><span class="badge-status <?= $statusClass ?>"><?= htmlspecialchars($o['status']) ?></span></td>
                                    <td><?= htmlspecialchars($o['created_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
