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

$sellerId = (int)($_GET['id'] ?? 0);
if ($sellerId <= 0) {
    header("Location: admin_sellers.php");
    exit;
}

// Lấy thông tin người bán
$stmt = $conn->prepare("SELECT * FROM sellers WHERE id = ?");
$stmt->execute([$sellerId]);
$seller = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$seller) {
    header("Location: admin_sellers.php");
    exit;
}

// Lấy danh sách sản phẩm của người bán
$stmt = $conn->prepare("
    SELECT * FROM products 
    WHERE seller_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$sellerId]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Thống kê
$stats = [];
$stats['total_products'] = count($products);

// Tính tổng doanh thu từ các đơn hàng có sản phẩm của seller này
$stmt = $conn->prepare("
    SELECT SUM(oi.quantity * oi.price) as total_revenue
    FROM order_items oi
    INNER JOIN products p ON oi.product_id = p.id
    WHERE p.seller_id = ?
");
$stmt->execute([$sellerId]);
$revenueData = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['total_revenue'] = $revenueData['total_revenue'] ?? 0;

// Lấy số đơn hàng có sản phẩm của seller
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT oi.order_id) as total_orders
    FROM order_items oi
    INNER JOIN products p ON oi.product_id = p.id
    WHERE p.seller_id = ?
");
$stmt->execute([$sellerId]);
$orderData = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['total_orders'] = $orderData['total_orders'] ?? 0;

function vnd($n) {
    return number_format((int)$n, 0, ',', '.') . '₫';
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Chi tiết người bán - Admin</title>
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
                    <a href="admin_customers.php">
                        <i class="bi bi-people"></i> Quản lý khách hàng
                    </a>
                    <a href="admin_sellers.php" class="active">
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
                    <h2>Chi tiết người bán</h2>
                    <a href="admin_sellers.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Quay lại
                    </a>
                </div>

                <div class="row">
                    <!-- Thông tin người bán -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="bi bi-shop"></i> Thông tin người bán</h5>
                            </div>
                            <div class="card-body">
                                <p><strong>ID:</strong> <?= $seller['id'] ?></p>
                                <p><strong>Họ tên:</strong> <?= htmlspecialchars($seller['fullname']) ?></p>
                                <p><strong>Email:</strong> <?= htmlspecialchars($seller['email']) ?></p>
                                <p><strong>Số điện thoại:</strong> <?= htmlspecialchars($seller['phone'] ?? 'N/A') ?></p>
                                <p><strong>Tên shop:</strong> <span class="text-primary fw-bold"><?= htmlspecialchars($seller['shop_name']) ?></span></p>
                                <p><strong>Ngày đăng ký:</strong> <?= date('d/m/Y H:i', strtotime($seller['created_at'])) ?></p>
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
                                <p><strong>Tổng số sản phẩm:</strong> 
                                    <span class="badge bg-info fs-6"><?= $stats['total_products'] ?></span>
                                </p>
                                <p><strong>Tổng số đơn hàng:</strong> 
                                    <span class="badge bg-warning fs-6"><?= $stats['total_orders'] ?></span>
                                </p>
                                <p><strong>Tổng doanh thu:</strong> 
                                    <span class="text-danger fw-bold fs-5"><?= vnd($stats['total_revenue']) ?></span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Danh sách sản phẩm -->
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="bi bi-box-seam"></i> Danh sách sản phẩm</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Hình ảnh</th>
                                        <th>Tên sản phẩm</th>
                                        <th>Giá</th>
                                        <th>Giá cũ</th>
                                        <th>Giảm giá</th>
                                        <th>Danh mục</th>
                                        <th>Ngày tạo</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($products)): ?>
                                        <tr>
                                            <td colspan="9" class="text-center text-muted py-4">Chưa có sản phẩm nào</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($products as $product): ?>
                                            <tr>
                                                <td><?= $product['id'] ?></td>
                                                <td>
                                                    <?php if ($product['image_main']): ?>
                                                        <img src="../<?= htmlspecialchars($product['image_main']) ?>" 
                                                             alt="<?= htmlspecialchars($product['name']) ?>" 
                                                             style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                                    <?php else: ?>
                                                        <div style="width: 50px; height: 50px; background: #f0f0f0; border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                                                            <i class="bi bi-image text-muted"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td><strong><?= htmlspecialchars($product['name']) ?></strong></td>
                                                <td><strong class="text-danger"><?= vnd($product['price']) ?></strong></td>
                                                <td>
                                                    <?php if ($product['old_price'] && $product['old_price'] > $product['price']): ?>
                                                        <span class="text-muted text-decoration-line-through"><?= vnd($product['old_price']) ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($product['sale_percent'] && $product['sale_percent'] > 0): ?>
                                                        <span class="badge bg-danger">-<?= $product['sale_percent'] ?>%</span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= htmlspecialchars($product['category'] ?? 'N/A') ?></td>
                                                <td><?= date('d/m/Y', strtotime($product['created_at'])) ?></td>
                                                <td>
                                                    <a href="admin_products.php?search=<?= urlencode($product['name']) ?>" 
                                                       class="btn btn-sm btn-outline-primary">
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

