<?php
session_start();
require 'connect.php';

// Kiểm tra quyền admin
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['user_id'])) {
    header("Location: trangchu.php?message=Bạn không có quyền truy cập");
    exit;
}

// Nếu login từ users_id với role=admin
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

$adminId = $_SESSION['admin_id'] ?? $_SESSION['user_id'];
$adminName = $_SESSION['admin_name'] ?? $_SESSION['fullname'] ?? 'Admin';

// Thống kê
$stats = [];

// Tổng số đơn hàng
$stmt = $conn->query("SELECT COUNT(*) FROM orders");
$stats['total_orders'] = $stmt->fetchColumn();

// Tổng số sản phẩm
$stmt = $conn->query("SELECT COUNT(*) FROM products");
$stats['total_products'] = $stmt->fetchColumn();

// Tổng số khách hàng
$stmt = $conn->query("SELECT COUNT(*) FROM users_id WHERE role = 'customer'");
$stats['total_customers'] = $stmt->fetchColumn();

// Tổng số người bán
$stmt = $conn->query("SELECT COUNT(*) FROM sellers");
$stats['total_sellers'] = $stmt->fetchColumn();

// Tổng doanh thu
$stmt = $conn->query("SELECT SUM(total_amount) FROM orders WHERE status != 'Đã hủy'");
$stats['total_revenue'] = $stmt->fetchColumn() ?? 0;

// Đơn hàng mới (hôm nay)
$stmt = $conn->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()");
$stats['today_orders'] = $stmt->fetchColumn();

// Lấy danh sách đơn hàng gần đây
$stmt = $conn->query("
    SELECT o.*, u.fullname as user_name, u.email as user_email
    FROM orders o
    LEFT JOIN users_id u ON o.user_id = u.id
    ORDER BY o.created_at DESC
    LIMIT 10
");
$recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

function vnd($n) {
    return number_format((int)$n, 0, ',', '.') . '₫';
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard - Quản lý hệ thống</title>
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
        .stat-card {
            border-left: 4px solid #0d6efd;
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
                    <a href="admin_dashboard.php" class="active">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                    <a href="admin_orders.php">
                        <i class="bi bi-cart"></i> Quản lý đơn hàng
                    </a>
                    <a href="admin_products.php">
                        <i class="bi bi-box-seam"></i> Quản lý sản phẩm
                    </a>
                    <a href="admin_customers.php">
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
                <h2 class="mb-4">Dashboard</h2>

                <!-- Thống kê -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <h6 class="text-muted">Tổng đơn hàng</h6>
                                <h3 class="mb-0"><?= $stats['total_orders'] ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card" style="border-left-color: #28a745;">
                            <div class="card-body">
                                <h6 class="text-muted">Tổng sản phẩm</h6>
                                <h3 class="mb-0"><?= $stats['total_products'] ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card" style="border-left-color: #ffc107;">
                            <div class="card-body">
                                <h6 class="text-muted">Tổng khách hàng</h6>
                                <h3 class="mb-0"><?= $stats['total_customers'] ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card" style="border-left-color: #dc3545;">
                            <div class="card-body">
                                <h6 class="text-muted">Tổng doanh thu</h6>
                                <h3 class="mb-0"><?= vnd($stats['total_revenue']) ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Đơn hàng gần đây -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Đơn hàng gần đây</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Mã đơn</th>
                                        <th>Khách hàng</th>
                                        <th>Tổng tiền</th>
                                        <th>Trạng thái</th>
                                        <th>Ngày tạo</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recentOrders)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">Chưa có đơn hàng nào</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recentOrders as $order): ?>
                                            <tr>
                                                <td><strong><?= htmlspecialchars($order['order_code']) ?></strong></td>
                                                <td>
                                                    <?= htmlspecialchars($order['customer_name']) ?><br>
                                                    <small class="text-muted"><?= htmlspecialchars($order['user_email'] ?? 'N/A') ?></small>
                                                </td>
                                                <td><?= vnd($order['total_amount']) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= 
                                                        $order['status'] === 'Đã giao' ? 'success' : 
                                                        ($order['status'] === 'Đang xử lý' ? 'warning' : 
                                                        ($order['status'] === 'Đã hủy' ? 'danger' : 'secondary')) 
                                                    ?>">
                                                        <?= htmlspecialchars($order['status']) ?>
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars($order['created_at']) ?></td>
                                                <td>
                                                    <a href="admin_order_detail.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-eye"></i> Xem
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

