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

// Xử lý thêm/sửa/xóa admin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete'])) {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0 && $id != $_SESSION['admin_id']) {
            $stmt = $conn->prepare("DELETE FROM users_id WHERE id = ? AND role = 'admin'");
            $stmt->execute([$id]);
            header("Location: admin_users.php?success=1");
            exit;
        }
    } elseif (isset($_POST['save'])) {
        $id = (int)($_POST['id'] ?? 0);
        $fullname = trim($_POST['fullname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (!empty($fullname) && !empty($email)) {
            if ($id > 0) {
                // Update
                if (!empty($password)) {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users_id SET fullname = ?, email = ?, password_hash = ?, is_verified = ? WHERE id = ? AND role = 'admin'");
                    $stmt->execute([$fullname, $email, $hash, $is_active, $id]);
                } else {
                    $stmt = $conn->prepare("UPDATE users_id SET fullname = ?, email = ?, is_verified = ? WHERE id = ? AND role = 'admin'");
                    $stmt->execute([$fullname, $email, $is_active, $id]);
                }
            } else {
                // Insert
                if (empty($password)) {
                    $password = 'admin123'; // Mật khẩu mặc định
                }
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users_id (fullname, email, password_hash, role, is_verified) VALUES (?, ?, ?, 'admin', ?)");
                $stmt->execute([$fullname, $email, $hash, $is_active]);
            }
            header("Location: admin_users.php?success=1");
            exit;
        }
    }
}

// Lấy danh sách admin
$search = trim($_GET['search'] ?? '');
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

$where = ["role = 'admin'"];
$params = [];

if (!empty($search)) {
    $where[] = "(fullname LIKE ? OR email LIKE ?)";
    $searchTerm = '%' . $search . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$whereSQL = 'WHERE ' . implode(' AND ', $where);

$sqlCount = "SELECT COUNT(*) FROM users_id $whereSQL";
$countStmt = $conn->prepare($sqlCount);
$countStmt->execute($params);
$totalAdmins = $countStmt->fetchColumn();
$totalPages = ceil($totalAdmins / $perPage);

$sql = "SELECT * FROM users_id $whereSQL ORDER BY created_at DESC LIMIT $perPage OFFSET $offset";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy admin để edit
$editAdmin = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM users_id WHERE id = ? AND role = 'admin'");
    $stmt->execute([$editId]);
    $editAdmin = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quản lý Admin - Admin</title>
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
                    <a href="admin_users.php" class="active">
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
                    <h2>Quản lý Admin</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#adminModal">
                        <i class="bi bi-plus-circle"></i> Thêm Admin
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
                                       placeholder="Tìm kiếm theo tên, email..." 
                                       value="<?= htmlspecialchars($search) ?>">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-dark w-100">Tìm kiếm</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Danh sách admin -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Họ tên</th>
                                        <th>Email</th>
                                        <th>Trạng thái</th>
                                        <th>Ngày tạo</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($admins)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">Không có admin nào</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($admins as $admin): ?>
                                            <tr>
                                                <td><?= $admin['id'] ?></td>
                                                <td><strong><?= htmlspecialchars($admin['fullname']) ?></strong></td>
                                                <td><?= htmlspecialchars($admin['email']) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= $admin['is_verified'] ? 'success' : 'warning' ?>">
                                                        <?= $admin['is_verified'] ? 'Hoạt động' : 'Tạm khóa' ?>
                                                    </span>
                                                </td>
                                                <td><?= date('d/m/Y', strtotime($admin['created_at'])) ?></td>
                                                <td>
                                                    <a href="?edit=<?= $admin['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <?php if ($admin['id'] != $_SESSION['admin_id']): ?>
                                                        <form method="POST" class="d-inline" onsubmit="return confirm('Bạn chắc chắn muốn xóa admin này?');">
                                                            <input type="hidden" name="id" value="<?= $admin['id'] ?>">
                                                            <input type="hidden" name="delete" value="1">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <span class="text-muted small">(Bạn)</span>
                                                    <?php endif; ?>
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

    <!-- Modal Thêm/Sửa Admin -->
    <div class="modal fade" id="adminModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title"><?= $editAdmin ? 'Sửa Admin' : 'Thêm Admin mới' ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" value="<?= $editAdmin['id'] ?? 0 ?>">
                        <input type="hidden" name="save" value="1">
                        
                        <div class="mb-3">
                            <label class="form-label">Họ tên *</label>
                            <input type="text" name="fullname" class="form-control" required 
                                   value="<?= htmlspecialchars($editAdmin['fullname'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" class="form-control" required 
                                   value="<?= htmlspecialchars($editAdmin['email'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mật khẩu <?= $editAdmin ? '(Để trống nếu không đổi)' : '*' ?></label>
                            <input type="password" name="password" class="form-control" 
                                   <?= $editAdmin ? '' : 'required' ?>>
                            <?php if ($editAdmin): ?>
                                <small class="text-muted">Chỉ nhập nếu muốn đổi mật khẩu</small>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" 
                                       <?= ($editAdmin['is_verified'] ?? 1) ? 'checked' : '' ?>>
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
    <?php if ($editAdmin): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = new bootstrap.Modal(document.getElementById('adminModal'));
            modal.show();
        });
    </script>
    <?php endif; ?>
</body>
</html>

