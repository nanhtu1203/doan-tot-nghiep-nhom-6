<?php
session_start();
require 'connect.php';

// Lấy từ khóa tìm kiếm
$keyword = trim($_GET['q'] ?? '');
$pageTitle = $keyword ? "Tìm kiếm: " . htmlspecialchars($keyword) : 'Tìm kiếm sản phẩm';

// Load categories cho navbar
$stmtCat = $conn->query("SELECT id, name, slug FROM categories ORDER BY sort_order, name");
$categories = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

// Helper format giá
function vnd($n){
    if ($n === null || $n === '') return '';
    return number_format((int)$n, 0, ',', '.') . "₫";
}

// Xử lý tìm kiếm
$products = [];
$totalProducts = 0;
$totalPages = 0;
$perPage = 12;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $perPage;

if (!empty($keyword)) {
    // Tìm kiếm trong nhiều cột: name, brand, category, description
    $searchTerm = '%' . $keyword . '%';
    
    // Đếm tổng số sản phẩm
    $sqlCount = "SELECT COUNT(*) FROM products 
                 WHERE name LIKE ? 
                 OR brand LIKE ? 
                 OR category LIKE ? 
                 OR description LIKE ?";
    $countStmt = $conn->prepare($sqlCount);
    $countStmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    $totalProducts = $countStmt->fetchColumn();
    $totalPages = ceil($totalProducts / $perPage);
    
    // Lấy danh sách sản phẩm
    $sql = "SELECT id, name, brand, price, old_price, sale_percent, category, gender, 
                   material, color, pattern, sizes, image_main 
            FROM products 
            WHERE name LIKE ? 
            OR brand LIKE ? 
            OR category LIKE ? 
            OR description LIKE ?
            ORDER BY 
                CASE 
                    WHEN name LIKE ? THEN 1
                    WHEN brand LIKE ? THEN 2
                    WHEN category LIKE ? THEN 3
                    ELSE 4
                END,
                id DESC
            LIMIT $perPage OFFSET $offset";
    
    $stmtProd = $conn->prepare($sql);
    $stmtProd->execute([
        $searchTerm, $searchTerm, $searchTerm, $searchTerm,
        $searchTerm, $searchTerm, $searchTerm
    ]);
    $products = $stmtProd->fetchAll(PDO::FETCH_ASSOC);
}

include 'header.php';
?>

<div class="container py-4">
    <div class="mb-4">
        <h2 class="fw-bold mb-3">Kết quả tìm kiếm</h2>
        
        <!-- Form tìm kiếm -->
        <form method="GET" action="search.php" class="mb-4">
            <div class="input-group" style="max-width: 600px;">
                <input type="text" 
                       name="q" 
                       class="form-control form-control-lg" 
                       placeholder="Nhập từ khóa tìm kiếm..." 
                       value="<?= htmlspecialchars($keyword) ?>"
                       required>
                <button class="btn btn-dark btn-lg" type="submit">
                    <i class="bi bi-search"></i> Tìm kiếm
                </button>
            </div>
        </form>
        
        <?php if (!empty($keyword)): ?>
            <div class="mb-3">
                <p class="text-muted mb-0">
                    Tìm thấy <strong><?= $totalProducts ?></strong> sản phẩm cho từ khóa 
                    <strong>"<?= htmlspecialchars($keyword) ?>"</strong>
                </p>
            </div>
        <?php endif; ?>
    </div>

    <?php if (empty($keyword)): ?>
        <!-- Chưa có từ khóa -->
        <div class="alert alert-info">
            <h5>Vui lòng nhập từ khóa tìm kiếm</h5>
            <p class="mb-0">Bạn có thể tìm kiếm theo tên sản phẩm, thương hiệu, danh mục...</p>
        </div>
    <?php elseif (empty($products)): ?>
        <!-- Không tìm thấy kết quả -->
        <div class="alert alert-warning">
            <h5>Không tìm thấy sản phẩm nào</h5>
            <p class="mb-0">Vui lòng thử lại với từ khóa khác hoặc <a href="trangchu.php">xem tất cả sản phẩm</a></p>
        </div>
    <?php else: ?>
        <!-- Hiển thị kết quả -->
        <div class="row g-3">
            <?php foreach ($products as $p): 
                // Xử lý đường dẫn ảnh
                $imgFileName = trim($p['image_main'] ?? '');
                if (empty($imgFileName)) {
                  $img = '../images/placeholder.png';
                } elseif (strpos($imgFileName, 'http') === 0 || strpos($imgFileName, '//') === 0) {
                  // URL tuyệt đối
                  $img = $imgFileName;
                } else {
                  // Chỉ là tên file, ghép với thư mục
                  $img = 'uploads/products/' . $imgFileName;
                }
                
                $badge_html = '';
                if (!empty($p['sale_percent']) && $p['sale_percent'] > 0) {
                    $badge_html = '<span class="badge-sale">'.htmlspecialchars($p['sale_percent']).'%</span>';
                } elseif (!empty($p['old_price']) && $p['old_price'] > $p['price']) {
                    $discount = round((($p['old_price'] - $p['price']) / $p['old_price']) * 100);
                    $badge_html = '<span class="badge-sale">-'.htmlspecialchars($discount).'%</span>';
                }
                
                $price_show = vnd($p['price']);
                $old_show = (!empty($p['old_price']) && $p['old_price'] > 0) ? vnd($p['old_price']) : '';
            ?>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="card product-card position-relative"
                     data-id="<?php echo $p['id']; ?>"
                     data-name="<?php echo htmlspecialchars($p['name']); ?>"
                     data-category="<?php echo htmlspecialchars($p['category']); ?>"
                     data-gender="<?php echo htmlspecialchars($p['gender']); ?>"
                     data-material="<?php echo htmlspecialchars($p['material']); ?>"
                     data-color="<?php echo htmlspecialchars($p['color']); ?>"
                     data-size="<?php echo htmlspecialchars($p['sizes']); ?>"
                     data-pattern="<?php echo htmlspecialchars($p['pattern']); ?>"
                     data-price="<?php echo htmlspecialchars($p['price']); ?>">
                    <?php echo $badge_html; ?>
                    
                    <img class="card-img-top" src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>">
                    <div class="card-body">
                        <div class="small text-muted"><?php echo htmlspecialchars($p['brand']); ?></div>
                        <h6 class="card-title mb-1"><?php echo htmlspecialchars($p['name']); ?></h6>
                        
                        <div class="d-flex align-items-baseline gap-2">
                            <span class="price"><?php echo $price_show; ?></span>
                            <?php if ($old_show !== ''): ?>
                                <small class="old"><?php echo $old_show; ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Phân trang -->
        <?php if ($totalPages > 1): ?>
        <nav aria-label="Page navigation search">
            <ul class="pagination justify-content-center mt-4">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                    <a class="page-link" href="?q=<?= urlencode($keyword) ?>&page=<?= $i ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
// CLICK CẢ SẢN PHẨM → SANG TRANG CHI TIẾT
document.querySelectorAll('.product-card').forEach(card => {
    card.addEventListener('click', function() {
        const id = this.dataset.id;
        if (id) {
            window.location.href = "product_detail.php?id=" + id;
        }
    });
});

// NGĂN 2 NÚT (THÊM GIỎ / MUA NGAY) GÂY CLICK VÀO SẢN PHẨM
document.querySelectorAll('.btn-add-cart-auto, .btn-buy-now-auto')
.forEach(btn => {
    btn.addEventListener('click', e => e.stopPropagation());
});
</script>

<?php include 'footer.php'; ?>

