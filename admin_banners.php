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

// Xử lý thêm/sửa/xóa banner
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete'])) {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            // Xóa ảnh nếu có
            $uploadDir = 'uploads/banners/';
            $stmt = $conn->prepare("SELECT image FROM banners WHERE id = ?");
            $stmt->execute([$id]);
            $banner = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($banner && !empty($banner['image'])) {
                $imgFileName = $banner['image'];
                if (strpos($imgFileName, 'http') !== 0 && file_exists($uploadDir . $imgFileName)) {
                    @unlink($uploadDir . $imgFileName);
                }
            }
            
            $stmt = $conn->prepare("DELETE FROM banners WHERE id = ?");
            $stmt->execute([$id]);
            header("Location: admin_banners.php?success=1");
            exit;
        }
    } elseif (isset($_POST['save'])) {
        $id = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $subtitle = trim($_POST['subtitle'] ?? '');
        $kicker = trim($_POST['kicker'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $link = trim($_POST['link'] ?? '');
        $link_text = trim($_POST['link_text'] ?? '');
        $sort_order = (int)($_POST['sort_order'] ?? 0);
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        // Xử lý upload ảnh
        $uploadDir = 'uploads/banners/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $imageFileName = ''; // Chỉ lưu tên file vào database
        $imageUrlInput = trim($_POST['image_url_input'] ?? '');

        // Ưu tiên: Upload file > URL input > Giữ ảnh cũ (khi edit)
        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['image_file'];
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            $maxSize = 5 * 1024 * 1024; // 5MB

            if (in_array($file['type'], $allowedTypes) && $file['size'] <= $maxSize) {
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $fileName = time() . '_' . uniqid() . '.' . $extension;
                $filePath = $uploadDir . $fileName;

                if (move_uploaded_file($file['tmp_name'], $filePath)) {
                    // Xóa ảnh cũ nếu có (khi edit)
                    if ($id > 0) {
                        $stmt = $conn->prepare("SELECT image FROM banners WHERE id = ?");
                        $stmt->execute([$id]);
                        $oldBanner = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($oldBanner && !empty($oldBanner['image']) && file_exists($uploadDir . $oldBanner['image'])) {
                            @unlink($uploadDir . $oldBanner['image']);
                        }
                    }
                    $imageFileName = $fileName; // Chỉ lưu tên file
                }
            }
        } elseif (!empty($imageUrlInput)) {
            // Nếu là URL, lưu nguyên URL
            $imageFileName = $imageUrlInput;
        } elseif ($id > 0 && empty($imageUrlInput)) {
            // Giữ ảnh cũ khi edit
            $stmt = $conn->prepare("SELECT image FROM banners WHERE id = ?");
            $stmt->execute([$id]);
            $oldBanner = $stmt->fetch(PDO::FETCH_ASSOC);
            $imageFileName = $oldBanner['image'] ?? '';
        }

        if (!empty($imageFileName)) {
            if ($id > 0) {
                // Update
                $stmt = $conn->prepare("
                    UPDATE banners SET 
                        title = ?, subtitle = ?, kicker = ?, description = ?, 
                        image = ?, link = ?, link_text = ?, sort_order = ?, is_active = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $title, $subtitle, $kicker, $description, 
                    $imageFileName, $link, $link_text, $sort_order, $is_active, $id
                ]);
            } else {
                // Insert
                $stmt = $conn->prepare("
                    INSERT INTO banners (title, subtitle, kicker, description, image, link, link_text, sort_order, is_active)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $title, $subtitle, $kicker, $description, 
                    $imageFileName, $link, $link_text, $sort_order, $is_active
                ]);
            }
            header("Location: admin_banners.php?success=1");
            exit;
        }
    }
}

// Lấy danh sách banner
$search = trim($_GET['search'] ?? '');
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(title LIKE ? OR subtitle LIKE ? OR kicker LIKE ?)";
    $searchTerm = '%' . $search . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

try {
    $sqlCount = "SELECT COUNT(*) FROM banners $whereSQL";
    $countStmt = $conn->prepare($sqlCount);
    $countStmt->execute($params);
    $totalBanners = $countStmt->fetchColumn();
    $totalPages = ceil($totalBanners / $perPage);

    $sql = "SELECT * FROM banners $whereSQL ORDER BY sort_order ASC, id ASC LIMIT $perPage OFFSET $offset";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $banners = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Lấy banner để edit
    $editBanner = null;
    if (isset($_GET['edit'])) {
        $editId = (int)$_GET['edit'];
        $stmt = $conn->prepare("SELECT * FROM banners WHERE id = ?");
        $stmt->execute([$editId]);
        $editBanner = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    // Nếu bảng banners chưa tồn tại
    $banners = [];
    $totalBanners = 0;
    $totalPages = 0;
    $editBanner = null;
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quản lý Banner - Admin</title>
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
        .banner-img {
            width: 200px;
            height: 100px;
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
                    <a href="admin_banners.php" class="active">
                        <i class="bi bi-images"></i> Quản lý Banner
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
                    <h2>Quản lý Banner</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#bannerModal">
                        <i class="bi bi-plus-circle"></i> Thêm Banner
                    </button>
                </div>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="bi bi-check-circle me-2"></i>Thao tác thành công!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (empty($banners) && !isset($_GET['search'])): ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Bảng banners chưa được tạo. Vui lòng chạy file <code>banners_table.sql</code> trong database.
                    </div>
                <?php endif; ?>

                <!-- Tìm kiếm -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-10">
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Tìm kiếm theo title, subtitle, kicker..." 
                                       value="<?= htmlspecialchars($search) ?>">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-dark w-100">Tìm kiếm</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Danh sách banner -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Hình ảnh</th>
                                        <th>Kicker</th>
                                        <th>Title</th>
                                        <th>Subtitle</th>
                                        <th>Link</th>
                                        <th>Thứ tự</th>
                                        <th>Trạng thái</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($banners)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-4">Không có banner nào</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($banners as $banner): ?>
                                            <tr>
                                                <td>
                                                    <?php 
                                                    $imgFileName = trim($banner['image'] ?? '');
                                                    if (!empty($imgFileName)) {
                                                        if (strpos($imgFileName, 'http') === 0 || strpos($imgFileName, '//') === 0) {
                                                            $imgSrc = $imgFileName;
                                                        } else {
                                                            $imgSrc = 'uploads/banners/' . $imgFileName;
                                                            if (!file_exists($imgSrc)) {
                                                                $imgSrc = '../' . $imgFileName;
                                                            }
                                                        }
                                                    } else {
                                                        $imgSrc = '../images/placeholder.png';
                                                    }
                                                    ?>
                                                    <img src="<?= htmlspecialchars($imgSrc) ?>" alt="Banner" 
                                                         class="banner-img"
                                                         onerror="this.src='../images/placeholder.png';">
                                                </td>
                                                <td><?= htmlspecialchars($banner['kicker'] ?? '-') ?></td>
                                                <td><strong><?= htmlspecialchars($banner['title'] ?? '-') ?></strong></td>
                                                <td><?= htmlspecialchars($banner['subtitle'] ?? '-') ?></td>
                                                <td>
                                                    <?php if ($banner['link']): ?>
                                                        <a href="../<?= htmlspecialchars($banner['link']) ?>" target="_blank" class="text-decoration-none">
                                                            <?= htmlspecialchars($banner['link_text'] ?? $banner['link']) ?>
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= $banner['sort_order'] ?></td>
                                                <td>
                                                    <span class="badge bg-<?= $banner['is_active'] ? 'success' : 'secondary' ?>">
                                                        <?= $banner['is_active'] ? 'Hoạt động' : 'Tắt' ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="?edit=<?= $banner['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('Bạn chắc chắn muốn xóa?');">
                                                        <input type="hidden" name="id" value="<?= $banner['id'] ?>">
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

    <!-- Modal Thêm/Sửa Banner -->
    <div class="modal fade" id="bannerModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title"><?= $editBanner ? 'Sửa Banner' : 'Thêm Banner mới' ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" value="<?= $editBanner['id'] ?? 0 ?>">
                        <input type="hidden" name="save" value="1">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Kicker (Dòng nhỏ phía trên)</label>
                                <input type="text" name="kicker" class="form-control" 
                                       value="<?= htmlspecialchars($editBanner['kicker'] ?? '') ?>" 
                                       placeholder="VD: BLACK FRIDAY">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Title (Tiêu đề chính)</label>
                                <input type="text" name="title" class="form-control" 
                                       value="<?= htmlspecialchars($editBanner['title'] ?? '') ?>" 
                                       placeholder="VD: UP TO">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Subtitle (Tiêu đề phụ)</label>
                                <input type="text" name="subtitle" class="form-control" 
                                       value="<?= htmlspecialchars($editBanner['subtitle'] ?? '') ?>" 
                                       placeholder="VD: 60%">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Mô tả (Description)</label>
                                <input type="text" name="description" class="form-control" 
                                       value="<?= htmlspecialchars($editBanner['description'] ?? '') ?>" 
                                       placeholder="VD: New styles added...">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Link</label>
                                <input type="text" name="link" class="form-control" 
                                       value="<?= htmlspecialchars($editBanner['link'] ?? '') ?>" 
                                       placeholder="VD: trangchu.php?category=sale">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Text nút</label>
                                <input type="text" name="link_text" class="form-control" 
                                       value="<?= htmlspecialchars($editBanner['link_text'] ?? '') ?>" 
                                       placeholder="VD: Xem ngay">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Hình ảnh *</label>
                                <input type="file" name="image_file" id="image_file" class="form-control" 
                                       accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                                <small class="text-muted">Chọn ảnh banner (JPG, PNG, GIF, WEBP - tối đa 5MB)</small>
                                <div id="image_preview" class="mt-2" style="display: none;">
                                    <img id="preview_img" src="" alt="Preview" 
                                         style="max-width: 400px; max-height: 200px; object-fit: cover; border: 1px solid #ddd; padding: 5px; border-radius: 5px;">
                                    <br>
                                    <button type="button" class="btn btn-sm btn-outline-danger mt-2" onclick="clearImagePreview()">
                                        <i class="bi bi-x-circle"></i> Xóa ảnh
                                    </button>
                                </div>
                                <?php if ($editBanner && !empty($editBanner['image'])): ?>
                                    <div id="current_image" class="mt-2">
                                        <p class="text-muted mb-1">Ảnh hiện tại:</p>
                                        <?php 
                                        $currentImg = $editBanner['image'];
                                        if (strpos($currentImg, 'http') === 0 || strpos($currentImg, '//') === 0) {
                                            $currentImgSrc = $currentImg;
                                        } else {
                                            $currentImgSrc = 'uploads/banners/' . $currentImg;
                                            if (!file_exists($currentImgSrc)) {
                                                $currentImgSrc = '../' . $currentImg;
                                            }
                                        }
                                        ?>
                                        <img src="<?= htmlspecialchars($currentImgSrc) ?>" alt="Current image" 
                                             style="max-width: 400px; max-height: 200px; object-fit: cover; border: 1px solid #ddd; padding: 5px; border-radius: 5px;">
                                    </div>
                                <?php endif; ?>
                                <div class="mt-2">
                                    <label class="form-label small">Hoặc nhập URL ảnh</label>
                                    <input type="text" name="image_url_input" id="image_url_input" class="form-control form-control-sm" 
                                           placeholder="https://example.com/image.jpg"
                                           value="<?= ($editBanner && !empty($editBanner['image']) && strpos($editBanner['image'], 'http') === 0) ? htmlspecialchars($editBanner['image']) : '' ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Thứ tự sắp xếp</label>
                                <input type="number" name="sort_order" class="form-control" 
                                       value="<?= $editBanner['sort_order'] ?? 0 ?>">
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" 
                                           <?= ($editBanner['is_active'] ?? 1) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="is_active">Kích hoạt</label>
                                </div>
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
        document.getElementById('image_file')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('image_preview');
                    const previewImg = document.getElementById('preview_img');
                    const currentImg = document.getElementById('current_image');
                    
                    previewImg.src = e.target.result;
                    preview.style.display = 'block';
                    
                    if (currentImg) {
                        currentImg.style.display = 'none';
                    }
                    
                    document.getElementById('image_url_input').value = '';
                };
                reader.readAsDataURL(file);
            }
        });

        function clearImagePreview() {
            document.getElementById('image_file').value = '';
            document.getElementById('image_preview').style.display = 'none';
            const currentImg = document.getElementById('current_image');
            if (currentImg) {
                currentImg.style.display = 'block';
            }
        }

        // Xử lý khi nhập URL
        document.getElementById('image_url_input')?.addEventListener('input', function() {
            if (this.value.trim()) {
                document.getElementById('image_file').value = '';
                document.getElementById('image_preview').style.display = 'none';
                const currentImg = document.getElementById('current_image');
                if (currentImg) {
                    currentImg.style.display = 'none';
                }
            }
        });
    </script>
    <?php if ($editBanner): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = new bootstrap.Modal(document.getElementById('bannerModal'));
            modal.show();
        });
    </script>
    <?php endif; ?>
</body>
</html>




