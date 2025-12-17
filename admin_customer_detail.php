<?php
session_start();
require 'connect.php';

// Kiểm tra quyền admin
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['user_id'])) {
    header("Location: trangchu.php?message=Bạn không có quyền truy cập");
    exit;
}

if (isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    $stmt = $conn->prepare("SELECT role FROM users_id WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && $user['role'] === 'admin') {
        $_SESSION['admin_id'] = $_SESSION['user_id'];
        $_SESSION['admin_name'] = $_SESSION['fullname'];
        $_SESSION['admin_email'] = $_SESSION['email'];
    } else {
        header("Location: trangchu.php?message=Bạn không có quyền truy cập");
        exit;
    }
}

$adminName = $_SESSION['admin_name'] ?? $_SESSION['fullname'] ?? 'Admin';

$customerId = (int)($_GET['id'] ?? 0);
if ($customerId <= 0) {
    header("Location: admin_customers.php");
    exit;
}

// Lấy thông tin khách hàng
$stmt = $conn->prepare("SELECT * FROM users_id WHERE id = ? AND role = 'customer'");
$stmt->execute([$customerId]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    header("Location: admin_customers.php");
    exit;
}

// Lấy danh sách đơn hàng của khách hàng
$stmt = $conn->prepare("
    SELECT * FROM orders 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$customerId]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy địa chỉ của khách hàng
$stmt = $conn->prepare("
    SELECT * FROM addresses 
    WHERE user_id = ? 
    ORDER BY is_default DESC, created_at DESC
");
$stmt->execute([$customerId]);
$addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Thống kê
$stats = [];
$stats['total_orders'] = count($orders);
$stats['total_spent'] = 0;
foreach ($orders as $order) {
    if ($order['status'] != 'Đã hủy') {
        $stats['total_spent'] += $order['total_amount'];
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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Chi tiết khách hàng - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: #212529;
            color: #fff;
        }
        .sidebar a {
            color: #fff;
            text-decoration: none;
            padding: 12px 20px;
            display: block;
            transition: background 0.3s;
        }
        .sidebar a:hover {
            background: #343a40;
        }
        .sidebar a.active {
            background: #0d6efd;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-0">
                <div class="p-3">
                    <h5 class="mb-0">Admin Panel</h5>
                    <small class="text-muted"><?= htmlspecialchars($adminName) ?></small>
                </div>
                <hr class="text-white">
                <nav>
                    <a href="admin_dashboard.php">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                    <a href="admin_orders.php">
                        <i class="bi bi-cart"></i> Quản lý đơn hàng
                    </a>
                    <a href="admin_products.php">
                        <i class="bi bi-box-seam"></i> Quản lý sản phẩm
                    </a>
                    <a href="admin_customers.php" class="active">
                        <i class="bi bi-people"></i> Quản lý khách hàng
                    </a>
                    <a href="admin_sellers.php">
                        <i class="bi bi-shop"></i> Quản lý người bán
                    </a>
                    <a href="admin_users.php">
                        <i class="bi bi-person-gear"></i> Quản lý Admin
                    </a>
                    <a href="admin_categories.php">
                        <i class="bi bi-tags"></i> Quản lý danh mục
                    </a>
                    <a href="admin_brands.php">
                        <i class="bi bi-award"></i> Quản lý thương hiệu
                    </a>
                    <a href="admin_coupons.php">
                        <i class="bi bi-ticket-perforated"></i> Quản lý mã khuyến mãi
                    </a>
                    <a href="admin_banners.php">
                        <i class="bi bi-images"></i> Quản lý Banner
                    </a>
                    <a href="admin_reports.php">
                        <i class="bi bi-graph-up"></i> Báo cáo doanh thu
                    </a>
                    <hr class="text-white">
                    <a href="trangchu.php">
                        <i class="bi bi-house"></i> Về trang chủ
                    </a>
                    <a href="logout.php">
                        <i class="bi bi-box-arrow-right"></i> Đăng xuất
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Chi tiết khách hàng</h2>
                    <a href="admin_customers.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Quay lại
                    </a>
                </div>

                <div class="row">
                    <!-- Thông tin khách hàng -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="bi bi-person"></i> Thông tin khách hàng</h5>
                            </div>
                            <div class="card-body">
                                <p><strong>ID:</strong> <?= $customer['id'] ?></p>
                                <p><strong>Họ tên:</strong> <?= htmlspecialchars($customer['fullname']) ?></p>
                                <p><strong>Email:</strong> <?= htmlspecialchars($customer['email']) ?></p>
                                <p><strong>Trạng thái:</strong> 
                                    <span class="badge bg-<?= $customer['is_verified'] ? 'success' : 'warning' ?>">
                                        <?= $customer['is_verified'] ? 'Đã xác thực' : 'Chưa xác thực' ?>
                                    </span>
                                </p>
                                <p><strong>Ngày đăng ký:</strong> <?= htmlspecialchars($customer['created_at']) ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Thống kê -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Thống kê</h5>
                            </div>
                            <div class="card-body">
                                <p><strong>Tổng số đơn hàng:</strong> 
                                    <span class="badge bg-info fs-6"><?= $stats['total_orders'] ?></span>
                                </p>
                                <p><strong>Tổng chi tiêu:</strong> 
                                    <span class="text-danger fw-bold fs-5"><?= vnd($stats['total_spent']) ?></span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Địa chỉ -->
                <?php if (!empty($addresses)): ?>
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="bi bi-geo-alt"></i> Địa chỉ giao hàng</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <?php foreach ($addresses as $addr): ?>
                                <div class="col-md-6">
                                    <div class="border p-3 rounded">
                                        <?php if ($addr['is_default']): ?>
                                            <span class="badge bg-primary mb-2">Mặc định</span>
                                        <?php endif; ?>
                                        <p class="mb-1"><strong><?= htmlspecialchars($addr['fullname']) ?></strong></p>
                                        <p class="mb-1"><i class="bi bi-telephone"></i> <?= htmlspecialchars($addr['phone']) ?></p>
                                        <p class="mb-0">
                                            <?= htmlspecialchars($addr['address']) ?>
                                            <?php if ($addr['ward']): ?>, <?= htmlspecialchars($addr['ward']) ?><?php endif; ?>
                                            <?php if ($addr['district']): ?>, <?= htmlspecialchars($addr['district']) ?><?php endif; ?>
                                            <?php if ($addr['city']): ?>, <?= htmlspecialchars($addr['city']) ?><?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Lịch sử đơn hàng -->
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="bi bi-receipt"></i> Lịch sử đơn hàng</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Mã đơn</th>
                                        <th>Ngày đặt</th>
                                        <th>Tổng tiền</th>
                                        <th>Trạng thái</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($orders)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">Chưa có đơn hàng nào</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($orders as $order): ?>
                                            <tr>
                                                <td><strong><?= htmlspecialchars($order['order_code']) ?></strong></td>
                                                <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                                                <td><strong><?= vnd($order['total_amount']) ?></strong></td>
                                                <td>
                                                    <span class="badge bg-<?= 
                                                        $order['status'] === 'Đã giao' ? 'success' : 
                                                        ($order['status'] === 'Đang xử lý' ? 'warning' : 
                                                        ($order['status'] === 'Đã hủy' ? 'danger' : 'secondary')) 
                                                    ?>">
                                                        <?= htmlspecialchars($order['status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="admin_order_detail.php?id=<?= $order['id'] ?>" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-eye"></i> Xem chi tiết
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>



