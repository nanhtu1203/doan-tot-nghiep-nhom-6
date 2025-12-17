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
    } else {
        header("Location: trangchu.php?message=Bạn không có quyền truy cập");
        exit;
    }
}

// Lấy tham số filter
$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo = $_GET['date_to'] ?? date('Y-m-d');

// Thiết lập header để xuất Excel
$filename = 'BaoCaoDoanhThu_' . date('Y-m-d_His') . '.xls';
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// BOM UTF-8 để Excel hiển thị tiếng Việt đúng
echo "\xEF\xBB\xBF";

// Lấy dữ liệu
$stats = [];

// Tổng quan
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
$overview = $stmt->fetch(PDO::FETCH_ASSOC);

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
$byStatus = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Top sản phẩm
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
    LIMIT 20
");
$stmt->execute([$dateFrom, $dateTo]);
$topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
$byCategory = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
");
$stmt->execute([$dateFrom, $dateTo]);
$byBrand = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
");
$stmt->execute([$dateFrom, $dateTo]);
$bySeller = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Doanh thu theo ngày
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
$daily = $stmt->fetchAll(PDO::FETCH_ASSOC);

function vnd($n) {
    return number_format((int)$n, 0, ',', '.') . '₫';
}

function formatNumber($n) {
    return number_format((int)$n, 0, ',', '.');
}
?>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #4CAF50; color: white; font-weight: bold; }
        .title { font-size: 18px; font-weight: bold; margin-bottom: 10px; }
        .section { margin-top: 30px; }
    </style>
</head>
<body>
    <div class="title">BÁO CÁO DOANH THU</div>
    <div>Khoảng thời gian: <?= date('d/m/Y', strtotime($dateFrom)) ?> - <?= date('d/m/Y', strtotime($dateTo)) ?></div>
    <div>Ngày xuất báo cáo: <?= date('d/m/Y H:i:s') ?></div>

    <div class="section">
        <div class="title">1. TỔNG QUAN</div>
        <table>
            <tr>
                <th>Chỉ tiêu</th>
                <th>Giá trị</th>
            </tr>
            <tr>
                <td>Tổng doanh thu</td>
                <td><?= formatNumber($overview['total_revenue']) ?> ₫</td>
            </tr>
            <tr>
                <td>Tổng số đơn hàng</td>
                <td><?= $overview['total_orders'] ?></td>
            </tr>
            <tr>
                <td>Giá trị đơn trung bình</td>
                <td><?= formatNumber($overview['avg_order_value']) ?> ₫</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="title">2. DOANH THU THEO TRẠNG THÁI</div>
        <table>
            <tr>
                <th>Trạng thái</th>
                <th>Số đơn hàng</th>
                <th>Doanh thu (₫)</th>
            </tr>
            <?php foreach ($byStatus as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['status']) ?></td>
                <td><?= $item['count'] ?></td>
                <td><?= formatNumber($item['revenue']) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="section">
        <div class="title">3. TOP 20 SẢN PHẨM BÁN CHẠY</div>
        <table>
            <tr>
                <th>STT</th>
                <th>Tên sản phẩm</th>
                <th>Thương hiệu</th>
                <th>Danh mục</th>
                <th>Số lượng đã bán</th>
                <th>Doanh thu (₫)</th>
            </tr>
            <?php $stt = 1; foreach ($topProducts as $item): ?>
            <tr>
                <td><?= $stt++ ?></td>
                <td><?= htmlspecialchars($item['name'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($item['brand'] ?? '') ?></td>
                <td><?= htmlspecialchars($item['category'] ?? '') ?></td>
                <td><?= $item['total_sold'] ?></td>
                <td><?= formatNumber($item['total_revenue']) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="section">
        <div class="title">4. DOANH THU THEO DANH MỤC</div>
        <table>
            <tr>
                <th>Danh mục</th>
                <th>Số đơn hàng</th>
                <th>Số lượng sản phẩm đã bán</th>
                <th>Doanh thu (₫)</th>
            </tr>
            <?php foreach ($byCategory as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['category']) ?></td>
                <td><?= $item['orders'] ?></td>
                <td><?= $item['items_sold'] ?></td>
                <td><?= formatNumber($item['revenue']) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="section">
        <div class="title">5. DOANH THU THEO THƯƠNG HIỆU</div>
        <table>
            <tr>
                <th>Thương hiệu</th>
                <th>Số đơn hàng</th>
                <th>Số lượng sản phẩm đã bán</th>
                <th>Doanh thu (₫)</th>
            </tr>
            <?php foreach ($byBrand as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['brand']) ?></td>
                <td><?= $item['orders'] ?></td>
                <td><?= $item['items_sold'] ?></td>
                <td><?= formatNumber($item['revenue']) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="section">
        <div class="title">6. DOANH THU THEO NGƯỜI BÁN</div>
        <table>
            <tr>
                <th>Người bán</th>
                <th>Số đơn hàng</th>
                <th>Số lượng sản phẩm đã bán</th>
                <th>Doanh thu (₫)</th>
            </tr>
            <?php foreach ($bySeller as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['shop_name'] ?? $item['fullname'] ?? 'N/A') ?></td>
                <td><?= $item['orders'] ?></td>
                <td><?= $item['items_sold'] ?></td>
                <td><?= formatNumber($item['revenue']) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="section">
        <div class="title">7. DOANH THU THEO NGÀY</div>
        <table>
            <tr>
                <th>Ngày</th>
                <th>Số đơn hàng</th>
                <th>Doanh thu (₫)</th>
            </tr>
            <?php foreach ($daily as $item): ?>
            <tr>
                <td><?= date('d/m/Y', strtotime($item['date'])) ?></td>
                <td><?= $item['orders'] ?></td>
                <td><?= formatNumber($item['revenue']) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>

