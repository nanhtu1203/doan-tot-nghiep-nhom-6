<?php
session_start();
require 'connect.php';

// Bắt buộc đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: trangchu.php?message=Vui lòng đăng nhập để quản lý địa chỉ");
    exit;
}

$userId = $_SESSION['user_id'];
$pageTitle = 'Quản lý địa chỉ';

// Xử lý thêm địa chỉ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_address'])) {
    $fullname = trim($_POST['fullname'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $ward = trim($_POST['ward'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $isDefault = isset($_POST['is_default']) ? 1 : 0;

    if (!empty($fullname) && !empty($phone) && !empty($address)) {
        // Nếu đặt làm mặc định, bỏ mặc định của các địa chỉ khác
        if ($isDefault) {
            $stmt = $conn->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ?");
            $stmt->execute([$userId]);
        }

        $stmt = $conn->prepare("
            INSERT INTO addresses (user_id, fullname, phone, address, ward, district, city, is_default)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $fullname, $phone, $address, $ward, $district, $city, $isDefault]);
        
        header("Location: address.php?success=1");
        exit;
    }
}

// Xử lý cập nhật địa chỉ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_address'])) {
    $addressId = (int)($_POST['address_id'] ?? 0);
    $fullname = trim($_POST['fullname'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $ward = trim($_POST['ward'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $isDefault = isset($_POST['is_default']) ? 1 : 0;

    if ($addressId > 0 && !empty($fullname) && !empty($phone) && !empty($address)) {
        // Kiểm tra địa chỉ thuộc về user này
        $check = $conn->prepare("SELECT id FROM addresses WHERE id = ? AND user_id = ?");
        $check->execute([$addressId, $userId]);
        if ($check->fetch()) {
            // Nếu đặt làm mặc định, bỏ mặc định của các địa chỉ khác
            if ($isDefault) {
                $stmt = $conn->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ? AND id != ?");
                $stmt->execute([$userId, $addressId]);
            }

            $stmt = $conn->prepare("
                UPDATE addresses 
                SET fullname = ?, phone = ?, address = ?, ward = ?, district = ?, city = ?, is_default = ?
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$fullname, $phone, $address, $ward, $district, $city, $isDefault, $addressId, $userId]);
            
            header("Location: address.php?success=1");
            exit;
        }
    }
}

// Xử lý xóa địa chỉ
if (isset($_GET['delete'])) {
    $addressId = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM addresses WHERE id = ? AND user_id = ?");
    $stmt->execute([$addressId, $userId]);
    header("Location: address.php?success=1");
    exit;
}

// Xử lý đặt làm mặc định
if (isset($_GET['set_default'])) {
    $addressId = (int)$_GET['set_default'];
    // Kiểm tra địa chỉ thuộc về user này
    $check = $conn->prepare("SELECT id FROM addresses WHERE id = ? AND user_id = ?");
    $check->execute([$addressId, $userId]);
    if ($check->fetch()) {
        // Bỏ mặc định của tất cả địa chỉ
        $stmt = $conn->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        // Đặt địa chỉ này làm mặc định
        $stmt = $conn->prepare("UPDATE addresses SET is_default = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$addressId, $userId]);
        
        header("Location: address.php?success=1");
        exit;
    }
}

// Lấy danh sách địa chỉ
$stmt = $conn->prepare("
    SELECT id, fullname, phone, address, ward, district, city, is_default, created_at
    FROM addresses
    WHERE user_id = ?
    ORDER BY is_default DESC, created_at DESC
");
$stmt->execute([$userId]);
$addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy địa chỉ để chỉnh sửa (nếu có)
$editAddress = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM addresses WHERE id = ? AND user_id = ?");
    $stmt->execute([$editId, $userId]);
    $editAddress = $stmt->fetch(PDO::FETCH_ASSOC);
}

include 'header.php';
?>

<div class="container py-4" style="max-width: 900px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold mb-0">Quản lý địa chỉ</h3>
        <button type="button" class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#addAddressModal">
            <i class="bi bi-plus-circle"></i> Thêm địa chỉ mới
        </button>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i>Thao tác thành công!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (empty($addresses)): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-geo-alt" style="font-size: 48px; color: #ccc;"></i>
                <p class="text-muted mt-3">Bạn chưa có địa chỉ nào. Hãy thêm địa chỉ để nhận hàng.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($addresses as $addr): ?>
                <div class="col-md-6">
                    <div class="card h-100 <?= $addr['is_default'] ? 'border-primary' : '' ?>">
                        <div class="card-body">
                            <?php if ($addr['is_default']): ?>
                                <span class="badge bg-primary mb-2">Mặc định</span>
                            <?php endif; ?>
                            
                            <h6 class="fw-bold mb-2"><?= htmlspecialchars($addr['fullname']) ?></h6>
                            <p class="mb-1">
                                <i class="bi bi-telephone"></i> <?= htmlspecialchars($addr['phone']) ?>
                            </p>
                            <p class="mb-1 text-muted small">
                                <?= htmlspecialchars($addr['address']) ?>
                                <?php if (!empty($addr['ward'])): ?>, <?= htmlspecialchars($addr['ward']) ?><?php endif; ?>
                                <?php if (!empty($addr['district'])): ?>, <?= htmlspecialchars($addr['district']) ?><?php endif; ?>
                                <?php if (!empty($addr['city'])): ?>, <?= htmlspecialchars($addr['city']) ?><?php endif; ?>
                            </p>
                            
                            <div class="mt-3 d-flex gap-2">
                                <a href="?edit=<?= $addr['id'] ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i> Sửa
                                </a>
                                <?php if (!$addr['is_default']): ?>
                                    <a href="?set_default=<?= $addr['id'] ?>" class="btn btn-sm btn-outline-success">
                                        <i class="bi bi-star"></i> Đặt mặc định
                                    </a>
                                <?php endif; ?>
                                <a href="?delete=<?= $addr['id'] ?>" 
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Bạn có chắc muốn xóa địa chỉ này?')">
                                    <i class="bi bi-trash"></i> Xóa
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Thêm/Sửa địa chỉ -->
<div class="modal fade" id="addAddressModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= $editAddress ? 'Sửa địa chỉ' : 'Thêm địa chỉ mới' ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="<?= $editAddress ? 'update_address' : 'add_address' ?>" value="1">
                    <?php if ($editAddress): ?>
                        <input type="hidden" name="address_id" value="<?= $editAddress['id'] ?>">
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label class="form-label">Họ tên người nhận <span class="text-danger">*</span></label>
                        <input type="text" name="fullname" class="form-control" required 
                               value="<?= htmlspecialchars($editAddress['fullname'] ?? '') ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                        <input type="tel" name="phone" class="form-control" required 
                               value="<?= htmlspecialchars($editAddress['phone'] ?? '') ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Địa chỉ chi tiết <span class="text-danger">*</span></label>
                        <textarea name="address" class="form-control" rows="2" required><?= htmlspecialchars($editAddress['address'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Phường/Xã</label>
                            <input type="text" name="ward" class="form-control" 
                                   value="<?= htmlspecialchars($editAddress['ward'] ?? '') ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Quận/Huyện</label>
                            <input type="text" name="district" class="form-control" 
                                   value="<?= htmlspecialchars($editAddress['district'] ?? '') ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Tỉnh/Thành phố</label>
                            <input type="text" name="city" class="form-control" 
                                   value="<?= htmlspecialchars($editAddress['city'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_default" id="isDefault" 
                               <?= ($editAddress && $editAddress['is_default']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="isDefault">
                            Đặt làm địa chỉ mặc định
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-dark"><?= $editAddress ? 'Cập nhật' : 'Thêm địa chỉ' ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($editAddress): ?>
<script>
// Tự động mở modal nếu đang edit
document.addEventListener('DOMContentLoaded', function() {
    const modal = new bootstrap.Modal(document.getElementById('addAddressModal'));
    modal.show();
});
</script>
<?php endif; ?>

<?php include 'footer.php'; ?>

