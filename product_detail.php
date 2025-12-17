<?php
session_start();
require 'connect.php';

$id = $_GET['id'] ?? 0;
if (!$id) {
    header("Location: trangchu.php");
    exit;
}

$stmt = $conn->prepare("
    SELECT *
    FROM products
    WHERE id = ?
");
$stmt->execute([$id]);
$p = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$p) {
    header("Location: trangchu.php");
    exit;
}

// Hàm xử lý đường dẫn ảnh
function getProductImagePath($imgFileName) {
    if (empty($imgFileName)) {
        return '../images/placeholder.png';
    }
    if (strpos($imgFileName, 'http') === 0 || strpos($imgFileName, '//') === 0) {
        // URL tuyệt đối
        return $imgFileName;
    }
    // Chỉ là tên file, ghép với thư mục
    return 'uploads/products/' . $imgFileName;
}

// Helper format giá
function vnd($n){
    if ($n === null || $n === '') return '';
    return number_format((int)$n, 0, ',', '.') . "₫";
}

$images = array_filter([
    $p['image_main'],
    $p['image_1'],
    $p['image_2'],
    $p['image_3']
]);

// Xử lý đường dẫn cho tất cả ảnh
$images = array_map('getProductImagePath', $images);
$mainImage = getProductImagePath($p['image_main']);

$pageTitle = htmlspecialchars($p['name']) . ' – Adodas';
include 'header.php';
?>

<style>
.product-detail-section {
    padding: 2rem 0;
    background: #fff;
}

.product-detail-container {
    max-width: 1200px;
    margin: 0 auto;
}

.product-gallery {
    position: sticky;
    top: 100px;
}

.main-image-wrapper {
    position: relative;
    width: 100%;
    aspect-ratio: 1;
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid #e5e5e5;
    background: #f8f8f8;
    margin-bottom: 1rem;
}

.main-image-wrapper img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.main-image-wrapper:hover img {
    transform: scale(1.05);
}

.thumb-list {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.thumb-item {
    width: 80px;
    height: 80px;
    border-radius: 8px;
    overflow: hidden;
    border: 2px solid transparent;
    cursor: pointer;
    transition: all 0.2s;
    background: #f8f8f8;
}

.thumb-item:hover,
.thumb-item.active {
    border-color: #000;
}

.thumb-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.product-info-section {
    padding-left: 2rem;
}

.product-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: #222;
    margin-bottom: 0.5rem;
    line-height: 1.3;
}

.product-brand {
    font-size: 0.9rem;
    color: #888;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 1rem;
}

.product-price-section {
    margin: 1.5rem 0;
    padding: 1rem 0;
    border-top: 1px solid #e5e5e5;
    border-bottom: 1px solid #e5e5e5;
}

.product-price {
    color: #d32f2f;
    font-size: 2rem;
    font-weight: 700;
    margin-right: 1rem;
}

.product-old-price {
    color: #999;
    text-decoration: line-through;
    font-size: 1.2rem;
}

.product-sale-badge {
    display: inline-block;
    background: #d32f2f;
    color: #fff;
    padding: 0.25rem 0.75rem;
    border-radius: 4px;
    font-size: 0.85rem;
    font-weight: 600;
    margin-left: 1rem;
}

.product-details {
    margin: 1.5rem 0;
}

.detail-row {
    display: flex;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f0f0f0;
}

.detail-label {
    font-weight: 600;
    color: #555;
    min-width: 120px;
}

.detail-value {
    color: #222;
}

.product-description {
    margin: 2rem 0;
    padding: 1.5rem;
    background: #f8f8f8;
    border-radius: 8px;
}

.product-description h5 {
    font-weight: 700;
    margin-bottom: 1rem;
    color: #222;
}

.product-description p {
    color: #555;
    line-height: 1.8;
    white-space: pre-wrap;
}

.product-actions {
    margin-top: 2rem;
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.btn-add-cart {
    flex: 1;
    min-width: 200px;
    padding: 0.875rem 1.5rem;
    background: #000;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 1rem;
    transition: all 0.2s;
    text-decoration: none;
    display: inline-block;
    text-align: center;
    cursor: pointer;
}

.btn-add-cart:hover {
    background: #333;
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.btn-buy-now {
    flex: 1;
    min-width: 200px;
    padding: 0.875rem 1.5rem;
    background: #d32f2f;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 1rem;
    transition: all 0.2s;
    text-decoration: none;
    display: inline-block;
    text-align: center;
}

.btn-buy-now:hover {
    background: #b71c1c;
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(211,47,47,0.3);
}

@media (max-width: 768px) {
    .product-info-section {
        padding-left: 0;
        margin-top: 2rem;
    }
    
    .product-gallery {
        position: static;
    }
    
    .product-actions {
        flex-direction: column;
    }
    
    .btn-add-cart,
    .btn-buy-now {
        width: 100%;
    }
}
</style>

<section class="product-detail-section">
    <div class="container product-detail-container">
        <div class="row">
            <!-- GALLERY -->
            <div class="col-md-6 product-gallery">
                <div class="main-image-wrapper">
                    <img id="mainImg" src="<?= htmlspecialchars($mainImage) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                </div>
                <?php if (count($images) > 1): ?>
                <div class="thumb-list">
                    <?php foreach ($images as $index => $img): ?>
                        <div class="thumb-item <?= $index === 0 ? 'active' : '' ?>" onclick="changeImage('<?= htmlspecialchars($img) ?>', this)">
                            <img src="<?= htmlspecialchars($img) ?>" alt="Thumbnail <?= $index + 1 ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- PRODUCT INFO -->
            <div class="col-md-6 product-info-section">
                <h1 class="product-title"><?= htmlspecialchars($p['name']) ?></h1>
                <div class="product-brand"><?= htmlspecialchars($p['brand']) ?></div>

                <div class="product-price-section">
                    <span class="product-price"><?= vnd($p['price']) ?></span>
                    <?php if (!empty($p['old_price']) && $p['old_price'] > $p['price']): ?>
                        <span class="product-old-price"><?= vnd($p['old_price']) ?></span>
                        <?php
                        $discount = round((($p['old_price'] - $p['price']) / $p['old_price']) * 100);
                        if ($discount > 0):
                        ?>
                            <span class="product-sale-badge">-<?= $discount ?>%</span>
                        <?php endif; ?>
                    <?php elseif (!empty($p['sale_percent']) && $p['sale_percent'] > 0): ?>
                        <span class="product-sale-badge">-<?= htmlspecialchars($p['sale_percent']) ?>%</span>
                    <?php endif; ?>
                </div>

                <div class="product-details">
                    <?php if (!empty($p['category'])): ?>
                    <div class="detail-row">
                        <span class="detail-label">Danh mục:</span>
                        <span class="detail-value"><?= htmlspecialchars($p['category']) ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($p['material'])): ?>
                    <div class="detail-row">
                        <span class="detail-label">Chất liệu:</span>
                        <span class="detail-value"><?= htmlspecialchars($p['material']) ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($p['color'])): ?>
                    <div class="detail-row">
                        <span class="detail-label">Màu sắc:</span>
                        <span class="detail-value"><?= htmlspecialchars($p['color']) ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($p['pattern'])): ?>
                    <div class="detail-row">
                        <span class="detail-label">Họa tiết:</span>
                        <span class="detail-value"><?= htmlspecialchars($p['pattern']) ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($p['gender'])): ?>
                    <div class="detail-row">
                        <span class="detail-label">Giới tính:</span>
                        <span class="detail-value"><?= htmlspecialchars($p['gender']) ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($p['sizes'])): ?>
                    <div class="detail-row">
                        <span class="detail-label">Size:</span>
                        <span class="detail-value"><?= htmlspecialchars($p['sizes']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="product-actions">
                    <button type="button" class="btn-add-cart" id="btnAddToCart">
                        <i class="bi bi-bag"></i> Thêm vào giỏ hàng
                    </button>
                    <a href="thanhtoan.php?buyNow=1&id=<?= $p['id'] ?>&name=<?= urlencode($p['name']) ?>&price=<?= $p['price'] ?>&img=<?= urlencode($mainImage) ?>" class="btn-buy-now" id="btnBuyNow">
                        <i class="bi bi-lightning"></i> Mua ngay
                    </a>
                </div>

                <?php if (!empty($p['description'])): ?>
                <div class="product-description">
                    <h5>Mô tả sản phẩm</h5>
                    <p><?= nl2br(htmlspecialchars($p['description'])) ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script>
function changeImage(src, thumbElement) {
    document.getElementById("mainImg").src = src;
    
    // Update active thumbnail
    if (thumbElement) {
        document.querySelectorAll('.thumb-item').forEach(item => {
            item.classList.remove('active');
        });
        thumbElement.classList.add('active');
    }
}

// Kiểm tra đăng nhập
const isLoggedIn = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;

function checkLoginAndRedirect() {
    if (!isLoggedIn) {
        window.location.href = 'trangchu.php?show_login=1';
        return false;
    }
    return true;
}

// Xử lý nút Thêm vào giỏ hàng
document.getElementById('btnAddToCart')?.addEventListener('click', function(e) {
    e.preventDefault();
    if (!checkLoginAndRedirect()) return;
    
    // Sử dụng hàm addProductToCart từ header.php nếu có
    if (typeof window.addProductToCart === 'function') {
        const productId = <?= $p['id'] ?>;
        const name = <?= json_encode($p['name']) ?>;
        const price = <?= (int)$p['price'] ?>;
        const img = <?= json_encode($mainImage) ?>;
        
        window.addProductToCart(name, price, img, productId);
        if (typeof window.showToast === 'function') {
            window.showToast('Đã thêm vào giỏ hàng');
        } else {
            alert('Đã thêm vào giỏ hàng');
        }
    } else {
        // Fallback: redirect đến trang chủ với thông báo
        alert('Vui lòng đăng nhập để thêm vào giỏ hàng');
        window.location.href = 'trangchu.php?show_login=1';
    }
});

// Xử lý nút Mua ngay
document.getElementById('btnBuyNow')?.addEventListener('click', function(e) {
    if (!checkLoginAndRedirect()) {
        e.preventDefault();
        return false;
    }
});
</script>

<?php include 'footer.php'; ?>
