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

// Xử lý thêm/sửa/xóa mã khuyến mãi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete'])) {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $conn->prepare("DELETE FROM coupons WHERE id = ?");
            $stmt->execute([$id]);
            header("Location: admin_coupons.php?success=1");
            exit;
        }
    } elseif (isset($_POST['save'])) {
        $id = (int)($_POST['id'] ?? 0);
        $code = trim($_POST['code'] ?? '');
        $type = $_POST['type'] ?? 'percent';
        $value = floatval($_POST['value'] ?? 0);
        $min_order = floatval($_POST['min_order'] ?? 0);
        $max_discount = !empty($_POST['max_discount']) ? floatval($_POST['max_discount']) : null;
        $usage_limit = !empty($_POST['usage_limit']) ? (int)$_POST['usage_limit'] : null;
        $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
        $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (!empty($code) && $value > 0) {
            if ($id > 0) {
                // Update
                $stmt = $conn->prepare("
                    UPDATE coupons SET 
                        code = ?, type = ?, value = ?, min_order = ?, max_discount = ?,
                        usage_limit = ?, start_date = ?, end_date = ?, is_active = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $code, $type, $value, $min_order, $max_discount,
                    $usage_limit, $start_date, $end_date, $is_active, $id
                ]);
            } else {
                // Insert
                $stmt = $conn->prepare("
                    INSERT INTO coupons (code, type, value, min_order, max_discount, usage_limit, start_date, end_date, is_active)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $code, $type, $value, $min_order, $max_discount,
                    $usage_limit, $start_date, $end_date, $is_active
                ]);
            }
            header("Location: admin_coupons.php?success=1");
            exit;
        }
    }
}

// Lấy danh sách mã khuyến mãi
$search = trim($_GET['search'] ?? '');
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "code LIKE ?";
    $params[] = '%' . $search . '%';
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sqlCount = "SELECT COUNT(*) FROM coupons $whereSQL";
$countStmt = $conn->prepare($sqlCount);
$countStmt->execute($params);
$totalCoupons = $countStmt->fetchColumn();
$totalPages = ceil($totalCoupons / $perPage);

$sql = "SELECT * FROM coupons $whereSQL ORDER BY created_at DESC LIMIT $perPage OFFSET $offset";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy mã để edit
$editCoupon = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM coupons WHERE id = ?");
    $stmt->execute([$editId]);
    $editCoupon = $stmt->fetch(PDO::FETCH_ASSOC);
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
    <title>Quản lý mã khuyến mãi - Admin</title>
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
                    <a href="admin_coupons.php" class="active">
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
                    <h2>Quản lý mã khuyến mãi</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#couponModal">
                        <i class="bi bi-plus-circle"></i> Thêm mã khuyến mãi
                    </button>
                </div>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="bi bi-check-circle me-2"></i>Thao tác thành công!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Tìm kiếm -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-10">
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Tìm kiếm theo mã..." 
                                       value="<?= htmlspecialchars($search) ?>">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-dark w-100">Tìm kiếm</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Danh sách mã khuyến mãi -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Mã</th>
                                        <th>Loại</th>
                                        <th>Giá trị</th>
                                        <th>Đơn tối thiểu</th>
                                        <th>Giảm tối đa</th>
                                        <th>Đã dùng</th>
                                        <th>Giới hạn</th>
                                        <th>Thời gian</th>
                                        <th>Trạng thái</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($coupons)): ?>
                                        <tr>
                                            <td colspan="10" class="text-center text-muted py-4">Không có mã khuyến mãi nào</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($coupons as $c): ?>
                                            <tr>
                                                <td><strong class="text-primary"><?= htmlspecialchars($c['code']) ?></strong></td>
                                                <td>
                                                    <span class="badge bg-<?= $c['type'] === 'percent' ? 'info' : 'warning' ?>">
                                                        <?= $c['type'] === 'percent' ? '%' : '₫' ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($c['type'] === 'percent'): ?>
                                                        <?= $c['value'] ?>%
                                                    <?php else: ?>
                                                        <?= vnd($c['value']) ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= vnd($c['min_order']) ?></td>
                                                <td><?= $c['max_discount'] ? vnd($c['max_discount']) : 'Không giới hạn' ?></td>
                                                <td><?= $c['used_count'] ?></td>
                                                <td><?= $c['usage_limit'] ? $c['usage_limit'] : 'Không giới hạn' ?></td>
                                                <td>
                                                    <small>
                                                        <?= $c['start_date'] ? date('d/m/Y', strtotime($c['start_date'])) : 'Không giới hạn' ?><br>
                                                        <?= $c['end_date'] ? date('d/m/Y', strtotime($c['end_date'])) : 'Không giới hạn' ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?= $c['is_active'] ? 'success' : 'secondary' ?>">
                                                        <?= $c['is_active'] ? 'Hoạt động' : 'Tắt' ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="?edit=<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('Bạn chắc chắn muốn xóa?');">
                                                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                                        <input type="hidden" name="delete" value="1">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Phân trang -->
                        <?php if ($totalPages > 1): ?>
                        <nav aria-label="Page navigation" class="mt-3">
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Thêm/Sửa mã khuyến mãi -->
    <div class="modal fade" id="couponModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title"><?= $editCoupon ? 'Sửa mã khuyến mãi' : 'Thêm mã khuyến mãi mới' ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" value="<?= $editCoupon['id'] ?? 0 ?>">
                        <input type="hidden" name="save" value="1">
                        
                        <div class="mb-3">
                            <label class="form-label">Mã khuyến mãi *</label>
                            <input type="text" name="code" class="form-control" required 
                                   value="<?= htmlspecialchars($editCoupon['code'] ?? '') ?>" 
                                   placeholder="VD: SALE50">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Loại giảm giá *</label>
                            <select name="type" class="form-select" required>
                                <option value="percent" <?= ($editCoupon['type'] ?? 'percent') === 'percent' ? 'selected' : '' ?>>Phần trăm (%)</option>
                                <option value="fixed" <?= ($editCoupon['type'] ?? '') === 'fixed' ? 'selected' : '' ?>>Số tiền cố định (₫)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Giá trị *</label>
                            <input type="number" name="value" class="form-control" required step="0.01" 
                                   value="<?= $editCoupon['value'] ?? '' ?>" 
                                   placeholder="VD: 10 (nếu %) hoặc 50000 (nếu ₫)">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Đơn hàng tối thiểu (₫)</label>
                            <input type="number" name="min_order" class="form-control" step="0.01" 
                                   value="<?= $editCoupon['min_order'] ?? 0 ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Giảm tối đa (₫) - Chỉ áp dụng với loại %</label>
                            <input type="number" name="max_discount" class="form-control" step="0.01" 
                                   value="<?= $editCoupon['max_discount'] ?? '' ?>" 
                                   placeholder="Để trống nếu không giới hạn">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Giới hạn số lần sử dụng</label>
                            <input type="number" name="usage_limit" class="form-control" 
                                   value="<?= $editCoupon['usage_limit'] ?? '' ?>" 
                                   placeholder="Để trống nếu không giới hạn">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Ngày bắt đầu</label>
                                <input type="datetime-local" name="start_date" class="form-control" 
                                       value="<?= $editCoupon['start_date'] ? date('Y-m-d\TH:i', strtotime($editCoupon['start_date'])) : '' ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Ngày kết thúc</label>
                                <input type="datetime-local" name="end_date" class="form-control" 
                                       value="<?= $editCoupon['end_date'] ? date('Y-m-d\TH:i', strtotime($editCoupon['end_date'])) : '' ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" 
                                       <?= ($editCoupon['is_active'] ?? 1) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_active">Kích hoạt</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                        <button type="submit" class="btn btn-primary">Lưu</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php if ($editCoupon): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = new bootstrap.Modal(document.getElementById('couponModal'));
            modal.show();
        });
    </script>
    <?php endif; ?>
</body>
</html>

