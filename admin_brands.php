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

// Xử lý thêm/sửa/xóa thương hiệu
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete'])) {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            // Xóa logo nếu có
            $uploadDir = 'uploads/brands/';
            $stmt = $conn->prepare("SELECT logo FROM brands WHERE id = ?");
            $stmt->execute([$id]);
            $brand = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($brand && !empty($brand['logo'])) {
                $logoFileName = $brand['logo'];
                // Chỉ xóa nếu là file (không phải URL)
                if (strpos($logoFileName, 'http') !== 0 && file_exists($uploadDir . $logoFileName)) {
                    @unlink($uploadDir . $logoFileName);
                }
            }
            
            $stmt = $conn->prepare("DELETE FROM brands WHERE id = ?");
            $stmt->execute([$id]);
            header("Location: admin_brands.php?success=1");
            exit;
        }
    } elseif (isset($_POST['save'])) {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        // Xử lý upload ảnh
        $uploadDir = 'uploads/brands/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Lấy URL từ input nếu có
        $logoUrlInput = trim($_POST['logo_url_input'] ?? '');
        $logoFileName = ''; // Chỉ lưu tên file vào database

        // Ưu tiên: Upload file > URL input > Giữ logo cũ (khi edit)
        if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['logo_file'];
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            $maxSize = 5 * 1024 * 1024; // 5MB

            if (in_array($file['type'], $allowedTypes) && $file['size'] <= $maxSize) {
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $fileName = time() . '_' . uniqid() . '.' . $extension;
                $filePath = $uploadDir . $fileName;

                if (move_uploaded_file($file['tmp_name'], $filePath)) {
                    // Xóa logo cũ nếu có (khi edit)
                    if ($id > 0) {
                        $stmt = $conn->prepare("SELECT logo FROM brands WHERE id = ?");
                        $stmt->execute([$id]);
                        $oldBrand = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($oldBrand && !empty($oldBrand['logo']) && file_exists($uploadDir . $oldBrand['logo'])) {
                            @unlink($uploadDir . $oldBrand['logo']);
                        }
                    }
                    $logoFileName = $fileName; // Chỉ lưu tên file
                }
            }
        } elseif (!empty($logoUrlInput)) {
            // Nếu là URL, lưu nguyên URL (không phải tên file)
            $logoFileName = $logoUrlInput;
        } elseif ($id > 0 && empty($logoUrlInput)) {
            // Giữ logo cũ khi edit
            $stmt = $conn->prepare("SELECT logo FROM brands WHERE id = ?");
            $stmt->execute([$id]);
            $oldBrand = $stmt->fetch(PDO::FETCH_ASSOC);
            $logoFileName = $oldBrand['logo'] ?? '';
        }

        if (empty($slug) && !empty($name)) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        }

        if (!empty($name) && !empty($slug)) {
            if ($id > 0) {
                // Update
                $stmt = $conn->prepare("
                    UPDATE brands SET 
                        name = ?, slug = ?, logo = ?, description = ?, is_active = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $name, $slug, $logoFileName, $description, $is_active, $id
                ]);
            } else {
                // Insert
                $stmt = $conn->prepare("
                    INSERT INTO brands (name, slug, logo, description, is_active)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $name, $slug, $logoFileName, $description, $is_active
                ]);
            }
            header("Location: admin_brands.php?success=1");
            exit;
        }
    }
}

// Lấy danh sách thương hiệu
$search = trim($_GET['search'] ?? '');
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(name LIKE ? OR slug LIKE ?)";
    $searchTerm = '%' . $search . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sqlCount = "SELECT COUNT(*) FROM brands $whereSQL";
$countStmt = $conn->prepare($sqlCount);
$countStmt->execute($params);
$totalBrands = $countStmt->fetchColumn();
$totalPages = ceil($totalBrands / $perPage);

$sql = "SELECT * FROM brands $whereSQL ORDER BY name ASC LIMIT $perPage OFFSET $offset";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$brands = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy thương hiệu để edit
$editBrand = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM brands WHERE id = ?");
    $stmt->execute([$editId]);
    $editBrand = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quản lý thương hiệu - Admin</title>
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
        .brand-logo {
            width: 60px;
            height: 60px;
            object-fit: contain;
            border-radius: 5px;
            background: #f8f9fa;
            padding: 5px;
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
                    <a href="admin_brands.php" class="active">
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
                    <h2>Quản lý thương hiệu</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#brandModal">
                        <i class="bi bi-plus-circle"></i> Thêm thương hiệu
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
                                       placeholder="Tìm kiếm theo tên, slug..." 
                                       value="<?= htmlspecialchars($search) ?>">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-dark w-100">Tìm kiếm</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Danh sách thương hiệu -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Logo</th>
                                        <th>Tên thương hiệu</th>
                                        <th>Slug</th>
                                        <th>Mô tả</th>
                                        <th>Trạng thái</th>
                                        <th>Ngày tạo</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($brands)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">Không có thương hiệu nào</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($brands as $brand): ?>
                                            <tr>
                                                <td>
                                                    <?php if ($brand['logo']): ?>
                                                        <?php 
                                                        $logoFileName = trim($brand['logo']);
                                                        // Nếu là URL tuyệt đối
                                                        if (strpos($logoFileName, 'http') === 0 || strpos($logoFileName, '//') === 0) {
                                                            $imgSrc = $logoFileName;
                                                        } else {
                                                            // Chỉ là tên file, ghép với thư mục cố định
                                                            $imgSrc = 'uploads/brands/' . $logoFileName;
                                                        }
                                                        ?>
                                                        <img src="<?= htmlspecialchars($imgSrc) ?>" 
                                                             alt="<?= htmlspecialchars($brand['name']) ?>" 
                                                             class="brand-logo"
                                                             onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'60\' height=\'60\'%3E%3Crect width=\'60\' height=\'60\' fill=\'%23f8f9fa\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%23999\' font-size=\'12\'%3ENo Image%3C/text%3E%3C/svg%3E';">
                                                    <?php else: ?>
                                                        <div class="brand-logo d-flex align-items-center justify-content-center text-muted">
                                                            <i class="bi bi-image"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <strong><?= htmlspecialchars($brand['name']) ?></strong>
                                                </td>
                                                <td><code><?= htmlspecialchars($brand['slug']) ?></code></td>
                                                <td>
                                                    <?php if ($brand['description']): ?>
                                                        <small><?= htmlspecialchars(substr($brand['description'], 0, 50)) ?>...</small>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?= $brand['is_active'] ? 'success' : 'secondary' ?>">
                                                        <?= $brand['is_active'] ? 'Hoạt động' : 'Tắt' ?>
                                                    </span>
                                                </td>
                                                <td><?= date('d/m/Y', strtotime($brand['created_at'])) ?></td>
                                                <td>
                                                    <a href="?edit=<?= $brand['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('Bạn chắc chắn muốn xóa?');">
                                                        <input type="hidden" name="id" value="<?= $brand['id'] ?>">
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

    <!-- Modal Thêm/Sửa thương hiệu -->
    <div class="modal fade" id="brandModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title"><?= $editBrand ? 'Sửa thương hiệu' : 'Thêm thương hiệu mới' ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" value="<?= $editBrand['id'] ?? 0 ?>">
                        <input type="hidden" name="save" value="1">
                        
                        <div class="mb-3">
                            <label class="form-label">Tên thương hiệu *</label>
                            <input type="text" name="name" class="form-control" required 
                                   value="<?= htmlspecialchars($editBrand['name'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Slug *</label>
                            <input type="text" name="slug" class="form-control" required 
                                   value="<?= htmlspecialchars($editBrand['slug'] ?? '') ?>" 
                                   placeholder="VD: nike">
                            <small class="text-muted">Sẽ tự động tạo từ tên nếu để trống</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Logo *</label>
                            <input type="file" name="logo_file" id="logo_file" class="form-control" 
                                   accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                            <small class="text-muted">Chọn ảnh logo (JPG, PNG, GIF, WEBP - tối đa 5MB)</small>
                            <div id="logo_preview" class="mt-2" style="display: none;">
                                <img id="preview_img" src="" alt="Preview" 
                                     style="max-width: 200px; max-height: 200px; object-fit: contain; border: 1px solid #ddd; padding: 5px; border-radius: 5px;">
                                <br>
                                <button type="button" class="btn btn-sm btn-outline-danger mt-2" onclick="clearLogoPreview()">
                                    <i class="bi bi-x-circle"></i> Xóa ảnh
                                </button>
                            </div>
                                                    <?php if ($editBrand && !empty($editBrand['logo'])): ?>
                                                        <div id="current_logo" class="mt-2">
                                                            <p class="text-muted mb-1">Logo hiện tại:</p>
                                                            <?php 
                                                            $currentLogo = $editBrand['logo'];
                                                            $currentLogoSrc = (strpos($currentLogo, 'http') === 0 || strpos($currentLogo, '//') === 0) 
                                                                ? $currentLogo 
                                                                : 'uploads/brands/' . $currentLogo;
                                                            ?>
                                                            <img src="<?= htmlspecialchars($currentLogoSrc) ?>" alt="Current logo" 
                                                                 style="max-width: 200px; max-height: 200px; object-fit: contain; border: 1px solid #ddd; padding: 5px; border-radius: 5px;">
                                                        </div>
                                                    <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Hoặc nhập URL logo</label>
                            <input type="text" name="logo_url_input" id="logo_url_input" class="form-control" 
                                   placeholder="https://example.com/logo.png"
                                   value="<?= ($editBrand && !empty($editBrand['logo']) && strpos($editBrand['logo'], 'http') === 0) ? htmlspecialchars($editBrand['logo']) : '' ?>">
                            <small class="text-muted">Nếu không upload file, bạn có thể nhập URL ảnh</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mô tả</label>
                            <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($editBrand['description'] ?? '') ?></textarea>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" 
                                       <?= ($editBrand['is_active'] ?? 1) ? 'checked' : '' ?>>
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
    <script>
        // Preview ảnh khi chọn file
        document.getElementById('logo_file')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('logo_preview');
                    const previewImg = document.getElementById('preview_img');
                    const currentLogo = document.getElementById('current_logo');
                    
                    previewImg.src = e.target.result;
                    preview.style.display = 'block';
                    
                    // Ẩn logo hiện tại nếu có
                    if (currentLogo) {
                        currentLogo.style.display = 'none';
                    }
                    
                    // Xóa URL input
                    document.getElementById('logo_url_input').value = '';
                    document.getElementById('logo_url').value = '';
                };
                reader.readAsDataURL(file);
            }
        });

        function clearLogoPreview() {
            document.getElementById('logo_file').value = '';
            document.getElementById('logo_preview').style.display = 'none';
            const currentLogo = document.getElementById('current_logo');
            if (currentLogo) {
                currentLogo.style.display = 'block';
            }
        }

        // Xử lý khi nhập URL
        document.getElementById('logo_url_input')?.addEventListener('input', function() {
            if (this.value.trim()) {
                document.getElementById('logo_file').value = '';
                document.getElementById('logo_preview').style.display = 'none';
                const currentLogo = document.getElementById('current_logo');
                if (currentLogo) {
                    currentLogo.style.display = 'none';
                }
            }
        });

        <?php if ($editBrand): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = new bootstrap.Modal(document.getElementById('brandModal'));
            modal.show();
        });
        <?php endif; ?>
    </script>
</body>
</html>

