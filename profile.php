<?php
session_start();
require 'connect.php';

// Bắt buộc đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: trangchu.php?show_login=1");
    exit;
}

$userId = $_SESSION['user_id'];
$successMsg = '';
$errorMsg = '';

// Lấy thông tin user hiện tại
$stmt = $conn->prepare("SELECT id, fullname, email, created_at, is_verified FROM users_id WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: trangchu.php");
    exit;
}

// Xử lý cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($fullname)) {
        $errorMsg = 'Vui lòng nhập họ tên.';
    } elseif (empty($email)) {
        $errorMsg = 'Vui lòng nhập email.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMsg = 'Email không hợp lệ.';
    } else {
        // Kiểm tra email đã tồn tại chưa (trừ email hiện tại)
        $checkEmail = $conn->prepare("SELECT id FROM users_id WHERE email = ? AND id != ?");
        $checkEmail->execute([$email, $userId]);
        if ($checkEmail->fetch()) {
            $errorMsg = 'Email này đã được sử dụng bởi tài khoản khác.';
        } else {
            // Cập nhật thông tin cơ bản
            $updateData = [$fullname, $email, $userId];
            $updateFields = "fullname = ?, email = ?";

            // Nếu có đổi mật khẩu
            if (!empty($newPassword)) {
                if (empty($currentPassword)) {
                    $errorMsg = 'Vui lòng nhập mật khẩu hiện tại để đổi mật khẩu.';
                } elseif (strlen($newPassword) < 6) {
                    $errorMsg = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
                } elseif ($newPassword !== $confirmPassword) {
                    $errorMsg = 'Mật khẩu mới và xác nhận mật khẩu không khớp.';
                } else {
                    // Kiểm tra mật khẩu hiện tại
                    $checkPass = $conn->prepare("SELECT password_hash FROM users_id WHERE id = ?");
                    $checkPass->execute([$userId]);
                    $userPass = $checkPass->fetch(PDO::FETCH_ASSOC);
                    
                    if ($userPass && password_verify($currentPassword, $userPass['password_hash'])) {
                        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                        $updateFields .= ", password_hash = ?";
                        $updateData = [$fullname, $email, $newPasswordHash, $userId];
                    } else {
                        $errorMsg = 'Mật khẩu hiện tại không đúng.';
                    }
                }
            }

            // Cập nhật nếu không có lỗi
            if (empty($errorMsg)) {
                $updateSql = "UPDATE users_id SET $updateFields WHERE id = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->execute($updateData);

                // Cập nhật session
                $_SESSION['fullname'] = $fullname;
                $_SESSION['email'] = $email;

                $successMsg = 'Cập nhật thông tin thành công!';
                
                // Reload thông tin user
                $stmt = $conn->prepare("SELECT id, fullname, email, created_at, is_verified FROM users_id WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        }
    }
}

$pageTitle = 'Thông tin tài khoản';
include 'header.php';
?>

<style>
.profile-section {
    padding: 2rem 0;
    background: #f8f9fa;
    min-height: 60vh;
}

.profile-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    padding: 2rem;
}

.profile-header {
    border-bottom: 2px solid #f0f0f0;
    padding-bottom: 1.5rem;
    margin-bottom: 2rem;
}

.profile-header h3 {
    margin: 0;
    color: #222;
    font-weight: 700;
}

.profile-info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f0f0f0;
}

.profile-info-item:last-child {
    border-bottom: none;
}

.profile-info-label {
    font-weight: 600;
    color: #555;
    min-width: 150px;
}

.profile-info-value {
    color: #222;
    flex: 1;
    text-align: right;
}

.verified-badge {
    display: inline-block;
    background: #28a745;
    color: #fff;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.85rem;
    font-weight: 600;
}

.unverified-badge {
    display: inline-block;
    background: #ffc107;
    color: #000;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.85rem;
    font-weight: 600;
}
</style>

<section class="profile-section">
    <div class="container" style="max-width: 800px;">
        <div class="profile-card">
            <div class="profile-header">
                <h3><i class="bi bi-person-circle"></i> Thông tin tài khoản</h3>
            </div>

            <?php if ($successMsg): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($successMsg) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($errorMsg): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($errorMsg) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Thông tin hiện tại -->
            <div class="mb-4">
                <h5 class="mb-3">Thông tin hiện tại</h5>
                <div class="profile-info-item">
                    <span class="profile-info-label">Họ tên:</span>
                    <span class="profile-info-value"><?= htmlspecialchars($user['fullname']) ?></span>
                </div>
                <div class="profile-info-item">
                    <span class="profile-info-label">Email:</span>
                    <span class="profile-info-value"><?= htmlspecialchars($user['email']) ?></span>
                </div>
                <div class="profile-info-item">
                    <span class="profile-info-label">Trạng thái:</span>
                    <span class="profile-info-value">
                        <?php if ($user['is_verified']): ?>
                            <span class="verified-badge"><i class="bi bi-check-circle"></i> Đã xác minh</span>
                        <?php else: ?>
                            <span class="unverified-badge"><i class="bi bi-exclamation-triangle"></i> Chưa xác minh</span>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="profile-info-item">
                    <span class="profile-info-label">Ngày đăng ký:</span>
                    <span class="profile-info-value"><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></span>
                </div>
            </div>

            <hr>

            <!-- Form cập nhật -->
            <div>
                <h5 class="mb-3">Cập nhật thông tin</h5>
                <form method="POST" action="profile.php">
                    <div class="mb-3">
                        <label class="form-label">Họ tên <span class="text-danger">*</span></label>
                        <input type="text" name="fullname" class="form-control" value="<?= htmlspecialchars($user['fullname']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>

                    <hr class="my-4">

                    <h6 class="mb-3">Đổi mật khẩu (để trống nếu không muốn đổi)</h6>
                    
                    <div class="mb-3">
                        <label class="form-label">Mật khẩu hiện tại</label>
                        <input type="password" name="current_password" class="form-control" placeholder="Nhập mật khẩu hiện tại">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Mật khẩu mới</label>
                        <input type="password" name="new_password" class="form-control" placeholder="Nhập mật khẩu mới (tối thiểu 6 ký tự)">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Xác nhận mật khẩu mới</label>
                        <input type="password" name="confirm_password" class="form-control" placeholder="Nhập lại mật khẩu mới">
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-dark">
                            <i class="bi bi-save"></i> Cập nhật thông tin
                        </button>
                        <a href="trangchu.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Quay lại
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<?php include 'footer.php'; ?>

