<?php
session_start();
require 'connect.php';

$pageTitle = 'Mã khuyến mãi';

// Lấy danh sách mã khuyến mãi đang hoạt động
$now = date('Y-m-d H:i:s');
$stmt = $conn->prepare("
    SELECT * FROM coupons 
    WHERE is_active = 1 
    AND (start_date IS NULL OR start_date <= ?)
    AND (end_date IS NULL OR end_date >= ?)
    ORDER BY created_at DESC
");
$stmt->execute([$now, $now]);
$coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);

function vnd($n) {
    return number_format((int)$n, 0, ',', '.') . '₫';
}

include 'header.php';
?>

<div class="container py-5">
    <h2 class="mb-4">Mã khuyến mãi</h2>
    
    <?php if (empty($coupons)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> Hiện tại không có mã khuyến mãi nào đang hoạt động.
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($coupons as $coupon): ?>
                <?php
                $isExpired = false;
                $isNotStarted = false;
                $usageInfo = '';
                
                if ($coupon['end_date'] && strtotime($coupon['end_date']) < time()) {
                    $isExpired = true;
                }
                if ($coupon['start_date'] && strtotime($coupon['start_date']) > time()) {
                    $isNotStarted = true;
                }
                
                if ($coupon['usage_limit']) {
                    $remaining = $coupon['usage_limit'] - $coupon['used_count'];
                    $usageInfo = "Còn {$remaining} lượt sử dụng";
                } else {
                    $usageInfo = "Không giới hạn lượt sử dụng";
                }
                
                $discountText = '';
                if ($coupon['type'] === 'percent') {
                    $discountText = "Giảm {$coupon['value']}%";
                    if ($coupon['max_discount']) {
                        $discountText .= " (tối đa " . vnd($coupon['max_discount']) . ")";
                    }
                } else {
                    $discountText = "Giảm " . vnd($coupon['value']);
                }
                
                $minOrderText = $coupon['min_order'] > 0 ? "Đơn tối thiểu: " . vnd($coupon['min_order']) : "Không có điều kiện";
                ?>
                
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-<?= $isExpired ? 'secondary' : ($isNotStarted ? 'warning' : 'success') ?> shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="card-title text-primary mb-1">
                                        <strong><?= htmlspecialchars($coupon['code']) ?></strong>
                                    </h5>
                                    <p class="text-muted small mb-0"><?= $discountText ?></p>
                                </div>
                                <?php if ($isExpired): ?>
                                    <span class="badge bg-secondary">Hết hạn</span>
                                <?php elseif ($isNotStarted): ?>
                                    <span class="badge bg-warning">Sắp diễn ra</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Đang áp dụng</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-3">
                                <small class="text-muted d-block">
                                    <i class="bi bi-cart"></i> <?= $minOrderText ?>
                                </small>
                                <small class="text-muted d-block mt-1">
                                    <i class="bi bi-clock"></i> 
                                    <?php if ($coupon['start_date']): ?>
                                        Từ: <?= date('d/m/Y H:i', strtotime($coupon['start_date'])) ?>
                                    <?php endif; ?>
                                    <?php if ($coupon['end_date']): ?>
                                        <br>Đến: <?= date('d/m/Y H:i', strtotime($coupon['end_date'])) ?>
                                    <?php endif; ?>
                                </small>
                                <small class="text-muted d-block mt-1">
                                    <i class="bi bi-people"></i> <?= $usageInfo ?>
                                </small>
                            </div>
                            
                            <button class="btn btn-outline-primary btn-sm w-100 copy-coupon-btn" 
                                    data-code="<?= htmlspecialchars($coupon['code']) ?>"
                                    <?= ($isExpired || $isNotStarted) ? 'disabled' : '' ?>>
                                <i class="bi bi-copy"></i> Sao chép mã
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
// Copy mã khuyến mãi
document.querySelectorAll('.copy-coupon-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const code = this.getAttribute('data-code');
        navigator.clipboard.writeText(code).then(() => {
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="bi bi-check"></i> Đã sao chép!';
            this.classList.remove('btn-outline-primary');
            this.classList.add('btn-success');
            
            setTimeout(() => {
                this.innerHTML = originalText;
                this.classList.remove('btn-success');
                this.classList.add('btn-outline-primary');
            }, 2000);
        });
    });
});
</script>

<?php include 'footer.php'; ?>

