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

// Hàm upload ảnh - chỉ trả về tên file
function uploadProductImage($fileKey, $uploadDir, $oldFileName = '') {
    if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES[$fileKey];
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        if (in_array($file['type'], $allowedTypes) && $file['size'] <= $maxSize) {
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileName = time() . '_' . uniqid() . '_' . $fileKey . '.' . $extension;
            $filePath = $uploadDir . $fileName;

            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                // Xóa ảnh cũ nếu có
                if (!empty($oldFileName) && file_exists($uploadDir . $oldFileName)) {
                    @unlink($uploadDir . $oldFileName);
                }
                return $fileName; // Chỉ trả về tên file
            }
        }
    }
    return $oldFileName; // Giữ ảnh cũ nếu không upload mới
}

// Xử lý thêm/sửa/xóa sản phẩm
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete'])) {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            // Lấy thông tin ảnh để xóa
            $stmt = $conn->prepare("SELECT image_main, image_1, image_2, image_3 FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($product) {
                // Xóa các file ảnh
                $uploadDir = 'uploads/products/';
                $images = [$product['image_main'], $product['image_1'], $product['image_2'], $product['image_3']];
                foreach ($images as $img) {
                    if (!empty($img) && strpos($img, 'http') !== 0 && file_exists($uploadDir . $img)) {
                        @unlink($uploadDir . $img);
                    }
                }
            }
            
            $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$id]);
            header("Location: admin_products.php?success=1");
            exit;
        }
    } elseif (isset($_POST['save'])) {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $seller_id = (int)($_POST['seller_id'] ?? 1);
        $brand = trim($_POST['brand'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $old_price = !empty($_POST['old_price']) ? floatval($_POST['old_price']) : null;
        $category = trim($_POST['category'] ?? '');
        $gender = trim($_POST['gender'] ?? '');
        $material = trim($_POST['material'] ?? '');
        $color = trim($_POST['color'] ?? '');
        $pattern = trim($_POST['pattern'] ?? '');
        $sizes = trim($_POST['sizes'] ?? '');
        $description = trim($_POST['description'] ?? '');

        // Xử lý upload ảnh
        $uploadDir = 'uploads/products/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Lấy ảnh cũ khi edit
        $oldImages = [];
        if ($id > 0) {
            $stmt = $conn->prepare("SELECT image_main, image_1, image_2, image_3 FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $oldProduct = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($oldProduct) {
                $oldImages = [
                    'image_main' => $oldProduct['image_main'] ?? '',
                    'image_1' => $oldProduct['image_1'] ?? '',
                    'image_2' => $oldProduct['image_2'] ?? '',
                    'image_3' => $oldProduct['image_3'] ?? ''
                ];
            }
        }

        // Upload các ảnh
        $image_main = uploadProductImage('image_main_file', $uploadDir, $oldImages['image_main'] ?? '');
        $image_1 = uploadProductImage('image_1_file', $uploadDir, $oldImages['image_1'] ?? '');
        $image_2 = uploadProductImage('image_2_file', $uploadDir, $oldImages['image_2'] ?? '');
        $image_3 = uploadProductImage('image_3_file', $uploadDir, $oldImages['image_3'] ?? '');

        // Nếu không upload nhưng có URL input
        $imageMainUrl = trim($_POST['image_main_url'] ?? '');
        if (empty($image_main) && !empty($imageMainUrl)) {
            $image_main = $imageMainUrl; // Lưu URL nếu là URL
        } elseif (empty($image_main) && $id > 0) {
            $image_main = $oldImages['image_main'] ?? ''; // Giữ tên file cũ
        }

        if (!empty($name) && $price > 0) {
            if ($id > 0) {
                // Update
                $stmt = $conn->prepare("
                    UPDATE products SET 
                        name = ?, seller_id = ?, brand = ?, price = ?, old_price = ?,
                        category = ?, gender = ?, material = ?, color = ?, pattern = ?,
                        sizes = ?, description = ?, image_main = ?, image_1 = ?, image_2 = ?, image_3 = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $name, $seller_id, $brand, $price, $old_price,
                    $category, $gender, $material, $color, $pattern,
                    $sizes, $description, $image_main, $image_1, $image_2, $image_3, $id
                ]);
                $productId = $id;
            } else {
                // Insert
                $stmt = $conn->prepare("
                    INSERT INTO products (name, seller_id, brand, price, old_price, category, gender, material, color, pattern, sizes, description, image_main, image_1, image_2, image_3)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $name, $seller_id, $brand, $price, $old_price,
                    $category, $gender, $material, $color, $pattern,
                    $sizes, $description, $image_main, $image_1, $image_2, $image_3
                ]);
                $productId = $conn->lastInsertId();
            }

            // Xử lý lưu tồn kho theo size vào bảng product_stock
            if ($productId > 0) {
                // Xóa stock cũ của sản phẩm này (nếu có)
                $stmt = $conn->prepare("DELETE FROM product_stock WHERE product_id = ?");
                $stmt->execute([$productId]);

                // Lưu stock mới từ form
                if (!empty($sizes)) {
                    $sizeArray = array_map('trim', explode(',', $sizes));
                    foreach ($sizeArray as $size) {
                        if (!empty($size)) {
                            $stockKey = 'stock_' . preg_replace('/[^a-zA-Z0-9]/', '_', $size);
                            $quantity = isset($_POST[$stockKey]) ? (int)$_POST[$stockKey] : 0;
                            
                            if ($quantity > 0) {
                                $stmt = $conn->prepare("
                                    INSERT INTO product_stock (product_id, size, quantity) 
                                    VALUES (?, ?, ?)
                                ");
                                $stmt->execute([$productId, $size, $quantity]);
                            }
                        }
                    }
                }
            }

            header("Location: admin_products.php?success=1");
            exit;
        }
    }
}

// Lấy danh sách sản phẩm
$search = trim($_GET['search'] ?? '');
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(p.name LIKE ? OR p.brand LIKE ? OR p.category LIKE ?)";
    $searchTerm = '%' . $search . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sqlCount = "SELECT COUNT(*) FROM products p $whereSQL";
$countStmt = $conn->prepare($sqlCount);
$countStmt->execute($params);
$totalProducts = $countStmt->fetchColumn();
$totalPages = ceil($totalProducts / $perPage);

$sql = "SELECT p.*, s.shop_name, s.fullname as seller_name,
        COALESCE(SUM(ps.quantity), 0) as total_stock
        FROM products p
        LEFT JOIN sellers s ON p.seller_id = s.id
        LEFT JOIN product_stock ps ON p.id = ps.product_id
        $whereSQL
        GROUP BY p.id
        ORDER BY p.created_at DESC
        LIMIT $perPage OFFSET $offset";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách sellers
$sellers = $conn->query("SELECT id, shop_name, fullname FROM sellers")->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách categories và brands từ database
try {
    $categories = $conn->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categories = [];
}

try {
    $brands = $conn->query("SELECT id, name FROM brands WHERE is_active = 1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $brands = [];
}

// Lấy sản phẩm để edit
$editProduct = null;
$editProductStock = [];
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$editId]);
    $editProduct = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Lấy tồn kho theo size
    if ($editProduct) {
        try {
            $stmt = $conn->prepare("SELECT size, quantity FROM product_stock WHERE product_id = ? ORDER BY size");
            $stmt->execute([$editId]);
            $stockRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($stockRows as $row) {
                $editProductStock[$row['size']] = $row['quantity'];
            }
        } catch (PDOException $e) {
            // Bảng product_stock có thể chưa tồn tại
            $editProductStock = [];
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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quản lý sản phẩm - Admin</title>
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
        .product-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
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
                    <a href="admin_products.php" class="active">
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Quản lý sản phẩm</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#productModal">
                        <i class="bi bi-plus-circle"></i> Thêm sản phẩm
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
                                       placeholder="Tìm kiếm theo tên, thương hiệu, danh mục..." 
                                       value="<?= htmlspecialchars($search) ?>">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-dark w-100">Tìm kiếm</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Danh sách sản phẩm -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Hình ảnh</th>
                                        <th>Tên sản phẩm</th>
                                        <th>Thương hiệu</th>
                                        <th>Danh mục</th>
                                        <th>Giá</th>
                                        <th>Tồn kho</th>
                                        <th>Người bán</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($products)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-4">Không có sản phẩm nào</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($products as $p): ?>
                                            <tr>
                                                <td>
                                                    <?php 
                                                    $imgFileName = trim($p['image_main'] ?? '');
                                                    // Nếu là URL tuyệt đối
                                                    if (strpos($imgFileName, 'http') === 0 || strpos($imgFileName, '//') === 0) {
                                                        $imgSrc = $imgFileName;
                                                    } elseif (empty($imgFileName)) {
                                                        $imgSrc = '../images/placeholder.png';
                                                    } else {
                                                        // Chỉ là tên file, ghép với thư mục cố định
                                                        $imgSrc = 'uploads/products/' . $imgFileName;
                                                    }
                                                    ?>
                                                    <img src="<?= htmlspecialchars($imgSrc) ?>" 
                                                         alt="<?= htmlspecialchars($p['name']) ?>" 
                                                         class="product-img"
                                                         onerror="this.onerror=null; this.src='../images/placeholder.png';">
                                                </td>
                                                <td>
                                                    <strong><?= htmlspecialchars($p['name']) ?></strong><br>
                                                    <small class="text-muted">ID: <?= $p['id'] ?></small>
                                                </td>
                                                <td><?= htmlspecialchars($p['brand']) ?></td>
                                                <td><?= htmlspecialchars($p['category'] ?? 'N/A') ?></td>
                                                <td>
                                                    <strong><?= vnd($p['price']) ?></strong>
                                                    <?php if ($p['old_price']): ?>
                                                        <br><small class="text-muted text-decoration-line-through"><?= vnd($p['old_price']) ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $totalStock = (int)($p['total_stock'] ?? 0);
                                                    if ($totalStock > 0): 
                                                    ?>
                                                        <span class="badge bg-success"><?= $totalStock ?> sản phẩm</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Hết hàng</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= htmlspecialchars($p['shop_name'] ?? $p['seller_name'] ?? 'N/A') ?></td>
                                                <td>
                                                    <a href="?edit=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('Bạn chắc chắn muốn xóa?');">
                                                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
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

    <!-- Modal Thêm/Sửa sản phẩm -->
    <div class="modal fade" id="productModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title"><?= $editProduct ? 'Sửa sản phẩm' : 'Thêm sản phẩm mới' ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" value="<?= $editProduct['id'] ?? 0 ?>">
                        <input type="hidden" name="save" value="1">
                        
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">Tên sản phẩm *</label>
                                <input type="text" name="name" class="form-control" required 
                                       value="<?= htmlspecialchars($editProduct['name'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Người bán</label>
                                <select name="seller_id" class="form-select">
                                    <?php foreach ($sellers as $s): ?>
                                        <option value="<?= $s['id'] ?>" 
                                                <?= ($editProduct['seller_id'] ?? 1) == $s['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($s['shop_name'] ?? $s['fullname']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Thương hiệu</label>
                                <select name="brand" class="form-select">
                                    <option value="">Chọn thương hiệu...</option>
                                    <?php foreach ($brands as $b): ?>
                                        <option value="<?= htmlspecialchars($b['name']) ?>" 
                                                <?= (($editProduct['brand'] ?? '') === $b['name'] ? 'selected' : '') ?>>
                                            <?= htmlspecialchars($b['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Nếu không có trong danh sách, vui lòng thêm mới trong <a href="admin_brands.php" target="_blank">Quản lý thương hiệu</a></small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Giá *</label>
                                <input type="number" name="price" class="form-control" required step="0.01" 
                                       value="<?= $editProduct['price'] ?? '' ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Giá cũ</label>
                                <input type="number" name="old_price" class="form-control" step="0.01" 
                                       value="<?= $editProduct['old_price'] ?? '' ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Danh mục</label>
                                <select name="category" class="form-select">
                                    <option value="">Chọn danh mục...</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= htmlspecialchars($cat['name']) ?>" 
                                                <?= (($editProduct['category'] ?? '') === $cat['name'] ? 'selected' : '') ?>>
                                            <?= htmlspecialchars($cat['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Nếu không có trong danh sách, vui lòng thêm mới trong <a href="admin_categories.php" target="_blank">Quản lý danh mục</a></small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Giới tính</label>
                                <select name="gender" class="form-select">
                                    <option value="">Chọn...</option>
                                    <option value="nam" <?= ($editProduct['gender'] ?? '') === 'nam' ? 'selected' : '' ?>>Nam</option>
                                    <option value="nữ" <?= ($editProduct['gender'] ?? '') === 'nữ' ? 'selected' : '' ?>>Nữ</option>
                                    <option value="unisex" <?= ($editProduct['gender'] ?? '') === 'unisex' ? 'selected' : '' ?>>Unisex</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Chất liệu</label>
                                <input type="text" name="material" class="form-control" 
                                       value="<?= htmlspecialchars($editProduct['material'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Màu sắc</label>
                                <input type="text" name="color" class="form-control" 
                                       value="<?= htmlspecialchars($editProduct['color'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Họa tiết</label>
                                <input type="text" name="pattern" class="form-control" 
                                       value="<?= htmlspecialchars($editProduct['pattern'] ?? '') ?>">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Sizes và Tồn kho</label>
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <div class="mb-2">
                                            <label class="form-label small">Danh sách sizes (VD: 35,36,37,38,39,40,41,42)</label>
                                            <input type="text" name="sizes" id="sizes_input" class="form-control" 
                                                   placeholder="35,36,37,38,39,40,41,42"
                                                   value="<?= htmlspecialchars($editProduct['sizes'] ?? '') ?>"
                                                   onchange="updateStockInputs()">
                                            <small class="text-muted">Nhập các size cách nhau bởi dấu phẩy</small>
                                        </div>
                                        <div id="stock_inputs_container" class="row g-2 mt-2">
                                            <!-- Sẽ được tạo động bằng JavaScript -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Hình ảnh chính *</label>
                                <input type="file" name="image_main_file" id="image_main_file" class="form-control" 
                                       accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                                <small class="text-muted">Chọn ảnh chính (JPG, PNG, GIF, WEBP - tối đa 5MB)</small>
                                <div id="image_main_preview" class="mt-2" style="display: none;">
                                    <img id="preview_main_img" src="" alt="Preview" 
                                         style="max-width: 200px; max-height: 200px; object-fit: cover; border: 1px solid #ddd; padding: 5px; border-radius: 5px;">
                                    <br>
                                    <button type="button" class="btn btn-sm btn-outline-danger mt-2" onclick="clearImagePreview('image_main')">
                                        <i class="bi bi-x-circle"></i> Xóa ảnh
                                    </button>
                                </div>
                                <?php if ($editProduct && !empty($editProduct['image_main'])): ?>
                                    <div id="current_image_main" class="mt-2">
                                        <p class="text-muted mb-1">Ảnh hiện tại:</p>
                                        <?php 
                                        $currentImg = $editProduct['image_main'] ?? '';
                                        $currentImgSrc = '';
                                        if (!empty($currentImg)) {
                                            if (strpos($currentImg, 'http') === 0 || strpos($currentImg, '//') === 0) {
                                                $currentImgSrc = $currentImg;
                                            } else {
                                                $currentImgSrc = 'uploads/products/' . $currentImg;
                                            }
                                        }
                                        ?>
                                        <?php if (!empty($currentImgSrc)): ?>
                                            <img src="<?= htmlspecialchars($currentImgSrc) ?>" alt="Current image" 
                                                 style="max-width: 200px; max-height: 200px; object-fit: cover; border: 1px solid #ddd; padding: 5px; border-radius: 5px;">
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                <div class="mt-2">
                                    <label class="form-label small">Hoặc nhập URL ảnh</label>
                                    <input type="text" name="image_main_url" id="image_main_url" class="form-control form-control-sm" 
                                           placeholder="https://example.com/image.jpg"
                                           value="<?= ($editProduct && !empty($editProduct['image_main']) && strpos($editProduct['image_main'], 'http') === 0) ? htmlspecialchars($editProduct['image_main']) : '' ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Ảnh phụ 1</label>
                                <input type="file" name="image_1_file" id="image_1_file" class="form-control" 
                                       accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                                <?php if ($editProduct && !empty($editProduct['image_1'])): ?>
                                    <div class="mt-2">
                                        <?php 
                                        $img1 = $editProduct['image_1'];
                                        $img1Src = (strpos($img1, 'http') === 0 || strpos($img1, '//') === 0) ? $img1 : 'uploads/products/' . $img1;
                                        ?>
                                        <img src="<?= htmlspecialchars($img1Src) ?>" alt="Image 1" 
                                             style="max-width: 100px; max-height: 100px; object-fit: cover; border: 1px solid #ddd; padding: 3px; border-radius: 3px;">
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Ảnh phụ 2</label>
                                <input type="file" name="image_2_file" id="image_2_file" class="form-control" 
                                       accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                                <?php if ($editProduct && !empty($editProduct['image_2'])): ?>
                                    <div class="mt-2">
                                        <?php 
                                        $img2 = $editProduct['image_2'];
                                        $img2Src = (strpos($img2, 'http') === 0 || strpos($img2, '//') === 0) ? $img2 : 'uploads/products/' . $img2;
                                        ?>
                                        <img src="<?= htmlspecialchars($img2Src) ?>" alt="Image 2" 
                                             style="max-width: 100px; max-height: 100px; object-fit: cover; border: 1px solid #ddd; padding: 3px; border-radius: 3px;">
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Ảnh phụ 3</label>
                                <input type="file" name="image_3_file" id="image_3_file" class="form-control" 
                                       accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                                <?php if ($editProduct && !empty($editProduct['image_3'])): ?>
                                    <div class="mt-2">
                                        <?php 
                                        $img3 = $editProduct['image_3'];
                                        $img3Src = (strpos($img3, 'http') === 0 || strpos($img3, '//') === 0) ? $img3 : 'uploads/products/' . $img3;
                                        ?>
                                        <img src="<?= htmlspecialchars($img3Src) ?>" alt="Image 3" 
                                             style="max-width: 100px; max-height: 100px; object-fit: cover; border: 1px solid #ddd; padding: 3px; border-radius: 3px;">
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Mô tả</label>
                                <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($editProduct['description'] ?? '') ?></textarea>
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
        // Preview ảnh chính khi chọn file
        document.getElementById('image_main_file')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('image_main_preview');
                    const previewImg = document.getElementById('preview_main_img');
                    const currentImg = document.getElementById('current_image_main');
                    
                    previewImg.src = e.target.result;
                    preview.style.display = 'block';
                    
                    if (currentImg) {
                        currentImg.style.display = 'none';
                    }
                    
                    document.getElementById('image_main_url').value = '';
                };
                reader.readAsDataURL(file);
            }
        });

        function clearImagePreview(type) {
            document.getElementById(type + '_file').value = '';
            document.getElementById(type + '_preview').style.display = 'none';
            const currentImg = document.getElementById('current_' + type);
            if (currentImg) {
                currentImg.style.display = 'block';
            }
        }

        // Xử lý khi nhập URL
        document.getElementById('image_main_url')?.addEventListener('input', function() {
            if (this.value.trim()) {
                document.getElementById('image_main_file').value = '';
                document.getElementById('image_main_preview').style.display = 'none';
                const currentImg = document.getElementById('current_image_main');
                if (currentImg) {
                    currentImg.style.display = 'none';
                }
            }
        });

        // Quản lý tồn kho theo size
        const productStock = <?= json_encode($editProductStock, JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        
        function updateStockInputs() {
            const sizesInput = document.getElementById('sizes_input');
            const container = document.getElementById('stock_inputs_container');
            const sizes = sizesInput.value.split(',').map(s => s.trim()).filter(s => s);
            
            container.innerHTML = '';
            
            if (sizes.length > 0) {
                sizes.forEach(size => {
                    if (size) {
                        const sizeKey = 'stock_' + size.replace(/[^a-zA-Z0-9]/g, '_');
                        const currentStock = productStock[size] || 0;
                        
                        const col = document.createElement('div');
                        col.className = 'col-md-3 col-6';
                        col.innerHTML = `
                            <label class="form-label small">Size ${size}</label>
                            <input type="number" name="${sizeKey}" class="form-control form-control-sm" 
                                   value="${currentStock}" min="0" placeholder="Số lượng">
                        `;
                        container.appendChild(col);
                    }
                });
            }
        }

        // Khởi tạo khi load trang
        <?php if ($editProduct): ?>
        document.addEventListener('DOMContentLoaded', function() {
            updateStockInputs();
        });
        <?php endif; ?>
        
        // Cập nhật khi thay đổi sizes
        document.getElementById('sizes_input')?.addEventListener('input', updateStockInputs);
    </script>
    <?php if ($editProduct): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = new bootstrap.Modal(document.getElementById('productModal'));
            modal.show();
        });
    </script>
    <?php endif; ?>
</body>
</html>

