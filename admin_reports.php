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

// Lấy tham số filter
$dateFrom = $_GET['date_from'] ?? date('Y-m-01'); // Mặc định đầu tháng
$dateTo = $_GET['date_to'] ?? date('Y-m-d'); // Mặc định hôm nay
$period = $_GET['period'] ?? 'month'; // day, week, month, year

// Xử lý period
if ($period === 'today') {
    $dateFrom = date('Y-m-d');
    $dateTo = date('Y-m-d');
} elseif ($period === 'week') {
    $dateFrom = date('Y-m-d', strtotime('monday this week'));
    $dateTo = date('Y-m-d');
} elseif ($period === 'month') {
    $dateFrom = date('Y-m-01');
    $dateTo = date('Y-m-d');
} elseif ($period === 'year') {
    $dateFrom = date('Y-01-01');
    $dateTo = date('Y-m-d');
}

// Thống kê tổng quan
$stats = [];

// Tổng doanh thu (chỉ đơn đã giao)
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_orders,
        COALESCE(SUM(total_amount), 0) as total_revenue,
        COALESCE(AVG(total_amount), 0) as avg_order_value
    FROM orders 
    WHERE DATE(created_at) BETWEEN ? AND ? 
    AND status = 'Đã giao'
");
$stmt->execute([$dateFrom, $dateTo]);
$stats['overview'] = $stmt->fetch(PDO::FETCH_ASSOC);

// Doanh thu theo trạng thái
$stmt = $conn->prepare("
    SELECT 
        status,
        COUNT(*) as count,
        COALESCE(SUM(total_amount), 0) as revenue
    FROM orders 
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY status
    ORDER BY revenue DESC
");
$stmt->execute([$dateFrom, $dateTo]);
$stats['by_status'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Doanh thu theo ngày (cho biểu đồ)
$stmt = $conn->prepare("
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as orders,
        COALESCE(SUM(CASE WHEN status = 'Đã giao' THEN total_amount ELSE 0 END), 0) as revenue
    FROM orders 
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");
$stmt->execute([$dateFrom, $dateTo]);
$stats['daily'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Top sản phẩm bán chạy
$stmt = $conn->prepare("
    SELECT 
        p.id,
        p.name,
        p.brand,
        p.category,
        SUM(oi.quantity) as total_sold,
        SUM(oi.quantity * oi.price) as total_revenue
    FROM order_items oi
    INNER JOIN orders o ON oi.order_id = o.id
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE DATE(o.created_at) BETWEEN ? AND ?
    AND o.status = 'Đã giao'
    GROUP BY p.id, p.name, p.brand, p.category
    ORDER BY total_sold DESC
    LIMIT 10
");
$stmt->execute([$dateFrom, $dateTo]);
$stats['top_products'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Doanh thu theo danh mục
$stmt = $conn->prepare("
    SELECT 
        COALESCE(p.category, 'Chưa phân loại') as category,
        COUNT(DISTINCT o.id) as orders,
        SUM(oi.quantity) as items_sold,
        SUM(oi.quantity * oi.price) as revenue
    FROM order_items oi
    INNER JOIN orders o ON oi.order_id = o.id
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE DATE(o.created_at) BETWEEN ? AND ?
    AND o.status = 'Đã giao'
    GROUP BY p.category
    ORDER BY revenue DESC
");
$stmt->execute([$dateFrom, $dateTo]);
$stats['by_category'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Doanh thu theo thương hiệu
$stmt = $conn->prepare("
    SELECT 
        COALESCE(p.brand, 'Chưa phân loại') as brand,
        COUNT(DISTINCT o.id) as orders,
        SUM(oi.quantity) as items_sold,
        SUM(oi.quantity * oi.price) as revenue
    FROM order_items oi
    INNER JOIN orders o ON oi.order_id = o.id
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE DATE(o.created_at) BETWEEN ? AND ?
    AND o.status = 'Đã giao'
    GROUP BY p.brand
    ORDER BY revenue DESC
    LIMIT 10
");
$stmt->execute([$dateFrom, $dateTo]);
$stats['by_brand'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Doanh thu theo người bán
$stmt = $conn->prepare("
    SELECT 
        s.id,
        s.shop_name,
        s.fullname,
        COUNT(DISTINCT o.id) as orders,
        SUM(oi.quantity) as items_sold,
        SUM(oi.quantity * oi.price) as revenue
    FROM order_items oi
    INNER JOIN orders o ON oi.order_id = o.id
    LEFT JOIN products p ON oi.product_id = p.id
    LEFT JOIN sellers s ON p.seller_id = s.id
    WHERE DATE(o.created_at) BETWEEN ? AND ?
    AND o.status = 'Đã giao'
    GROUP BY s.id, s.shop_name, s.fullname
    ORDER BY revenue DESC
    LIMIT 10
");
$stmt->execute([$dateFrom, $dateTo]);
$stats['by_seller'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

function vnd($n) {
    return number_format((int)$n, 0, ',', '.') . '₫';
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Báo cáo doanh thu - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 2rem;
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
                    <a href="admin_reports.php" class="active">
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
                    <h2>Báo cáo doanh thu</h2>
                    <a href="export_revenue_excel.php?date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?>" 
                       class="btn btn-success">
                        <i class="bi bi-file-earmark-excel"></i> Xuất Excel
                    </a>
                </div>

                <!-- Filter -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Khoảng thời gian</label>
                                <select name="period" class="form-select" onchange="this.form.submit()">
                                    <option value="today" <?= $period === 'today' ? 'selected' : '' ?>>Hôm nay</option>
                                    <option value="week" <?= $period === 'week' ? 'selected' : '' ?>>Tuần này</option>
                                    <option value="month" <?= $period === 'month' ? 'selected' : '' ?>>Tháng này</option>
                                    <option value="year" <?= $period === 'year' ? 'selected' : '' ?>>Năm nay</option>
                                    <option value="custom" <?= $period === 'custom' ? 'selected' : '' ?>>Tùy chọn</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Từ ngày</label>
                                <input type="date" name="date_from" class="form-control" 
                                       value="<?= htmlspecialchars($dateFrom) ?>" 
                                       <?= $period !== 'custom' ? 'readonly' : '' ?>>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Đến ngày</label>
                                <input type="date" name="date_to" class="form-control" 
                                       value="<?= htmlspecialchars($dateTo) ?>" 
                                       <?= $period !== 'custom' ? 'readonly' : '' ?>>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-search"></i> Xem báo cáo
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Thống kê tổng quan -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card stat-card">
                            <div class="card-body">
                                <h6 class="text-muted">Tổng doanh thu</h6>
                                <h3 class="mb-0 text-success"><?= vnd($stats['overview']['total_revenue']) ?></h3>
                                <small class="text-muted"><?= $stats['overview']['total_orders'] ?> đơn hàng</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stat-card" style="border-left-color: #28a745;">
                            <div class="card-body">
                                <h6 class="text-muted">Tổng đơn hàng</h6>
                                <h3 class="mb-0"><?= $stats['overview']['total_orders'] ?></h3>
                                <small class="text-muted">Đã giao</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stat-card" style="border-left-color: #ffc107;">
                            <div class="card-body">
                                <h6 class="text-muted">Giá trị đơn trung bình</h6>
                                <h3 class="mb-0"><?= vnd($stats['overview']['avg_order_value']) ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Biểu đồ doanh thu theo ngày -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Biểu đồ doanh thu theo ngày</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Doanh thu theo trạng thái -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Doanh thu theo trạng thái</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Trạng thái</th>
                                                <th>Số đơn</th>
                                                <th>Doanh thu</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($stats['by_status'] as $item): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($item['status']) ?></td>
                                                    <td><?= $item['count'] ?></td>
                                                    <td><strong><?= vnd($item['revenue']) ?></strong></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Top sản phẩm bán chạy -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Top 10 sản phẩm bán chạy</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Sản phẩm</th>
                                                <th>Đã bán</th>
                                                <th>Doanh thu</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($stats['top_products'] as $item): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?= htmlspecialchars($item['name'] ?? 'N/A') ?></strong><br>
                                                        <small class="text-muted"><?= htmlspecialchars($item['brand'] ?? '') ?></small>
                                                    </td>
                                                    <td><?= $item['total_sold'] ?></td>
                                                    <td><strong><?= vnd($item['total_revenue']) ?></strong></td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <?php if (empty($stats['top_products'])): ?>
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted">Chưa có dữ liệu</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Doanh thu theo danh mục và thương hiệu -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Doanh thu theo danh mục</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Danh mục</th>
                                                <th>Đơn hàng</th>
                                                <th>Đã bán</th>
                                                <th>Doanh thu</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($stats['by_category'] as $item): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($item['category']) ?></td>
                                                    <td><?= $item['orders'] ?></td>
                                                    <td><?= $item['items_sold'] ?></td>
                                                    <td><strong><?= vnd($item['revenue']) ?></strong></td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <?php if (empty($stats['by_category'])): ?>
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted">Chưa có dữ liệu</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Doanh thu theo thương hiệu</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Thương hiệu</th>
                                                <th>Đơn hàng</th>
                                                <th>Đã bán</th>
                                                <th>Doanh thu</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($stats['by_brand'] as $item): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($item['brand']) ?></td>
                                                    <td><?= $item['orders'] ?></td>
                                                    <td><?= $item['items_sold'] ?></td>
                                                    <td><strong><?= vnd($item['revenue']) ?></strong></td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <?php if (empty($stats['by_brand'])): ?>
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted">Chưa có dữ liệu</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Doanh thu theo người bán -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Doanh thu theo người bán</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Người bán</th>
                                        <th>Đơn hàng</th>
                                        <th>Sản phẩm đã bán</th>
                                        <th>Doanh thu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stats['by_seller'] as $item): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($item['shop_name'] ?? $item['fullname'] ?? 'N/A') ?></strong>
                                            </td>
                                            <td><?= $item['orders'] ?></td>
                                            <td><?= $item['items_sold'] ?></td>
                                            <td><strong class="text-success"><?= vnd($item['revenue']) ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($stats['by_seller'])): ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">Chưa có dữ liệu</td>
                                        </tr>
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
    <script>
        // Biểu đồ doanh thu
        const revenueData = <?= json_encode($stats['daily']) ?>;
        const labels = revenueData.map(item => {
            const date = new Date(item.date);
            return date.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit' });
        });
        const revenues = revenueData.map(item => parseInt(item.revenue));
        const orders = revenueData.map(item => parseInt(item.orders));

        const ctx = document.getElementById('revenueChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Doanh thu (₫)',
                    data: revenues,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1,
                    yAxisID: 'y'
                }, {
                    label: 'Số đơn hàng',
                    data: orders,
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    tension: 0.1,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Doanh thu (₫)'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Số đơn hàng'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>

