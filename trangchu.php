<?php
session_start();
require 'connect.php';

// ====== XỬ LÝ ĐẶT HÀNG (KHÁCH HÀNG ĐẶT TỪ GIỎ MINI / THANHTOAN) ======
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    $customerName    = $_POST['name']    ?? '';
    $customerPhone   = $_POST['phone']   ?? '';
    $customerAddress = $_POST['address'] ?? '';

    // Sinh mã đơn hàng ngẫu nhiên
    $orderCode = 'HD' . strtoupper(substr(md5(uniqid('', true)), 0, 6));

    // Lấy user_id đang đăng nhập (nếu có) để liên kết với lịch sử mua hàng
    $userId = $_SESSION['user_id'] ?? null;

    // Lấy giỏ hàng từ session (phải có chỗ khác trong code set $_SESSION['cart'])
    $cart = $_SESSION['cart'] ?? [];

    if (!empty($cart)) {
        // Tính tổng tiền đơn hàng
        $totalAmount = 0;
        foreach ($cart as $productId => $item) {
            $qty   = (int)($item['qty']   ?? 0);
            $price = (int)($item['price'] ?? 0);
            $totalAmount += $qty * $price;
        }

        // Lưu đơn hàng vào bảng orders (đúng với cấu trúc bạn vừa tạo)
        // id, order_code, user_id, customer_name, customer_phone, customer_addr,
        // total_amount, status, created_at
        $stmt = $conn->prepare("
            INSERT INTO orders (
                order_code,
                user_id,
                customer_name,
                customer_phone,
                customer_addr,
                total_amount,
                status,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, 'Đang xử lý', NOW())
        ");
        $stmt->execute([
            $orderCode,
            $userId,
            $customerName,
            $customerPhone,
            $customerAddress,
            $totalAmount
        ]);

        $orderId = $conn->lastInsertId();

        // Lưu chi tiết sản phẩm vào order_items
        foreach ($cart as $productId => $item) {
            $stmt2 = $conn->prepare("
                INSERT INTO order_items (order_id, product_id, quantity, price)
                VALUES (?, ?, ?, ?)
            ");
            $stmt2->execute([
                $orderId,
                $productId,
                (int)$item['qty'],
                (int)$item['price']
            ]);
        }

        // Xóa giỏ hàng trong session
        unset($_SESSION['cart']);

        // Chuyển cho người bán xem đơn, tuỳ bạn muốn redirect đi đâu
        header("Location: seller.php?order_code=" . urlencode($orderCode));
        exit;
    }
}



$category = $_GET['category'] ?? '';

// Load categories trước để dùng cho filter
$stmtCat = $conn->query("SELECT id, name, slug, image FROM categories WHERE is_active = 1 ORDER BY sort_order, name");
$categories = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

// Lọc nâng cao
$where = [];
$params = [];
if (!empty($_GET['category'])) {
    // Nếu category là slug, cần tìm name tương ứng
    $categorySlug = $_GET['category'];
    $categoryName = null;
    foreach ($categories as $cat) {
        if ($cat['slug'] === $categorySlug) {
            $categoryName = $cat['name'];
            break;
        }
    }
    if ($categoryName) {
        $where[] = 'category = ?';
        $params[] = $categoryName;
    }
}
if (!empty($_GET['brand'])) {
    $where[] = 'brand = ?';
    $params[] = $_GET['brand'];
}
if (!empty($_GET['color'])) {
    $where[] = 'color = ?';
    $params[] = $_GET['color'];
}
if (!empty($_GET['price_min'])) {
    $where[] = 'price >= ?';
    $params[] = $_GET['price_min'];
}
if (!empty($_GET['price_max'])) {
    $where[] = 'price <= ?';
    $params[] = $_GET['price_max'];
}
$whereSQL = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
$perPage = 12;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $perPage;
// Đếm tổng số sản phẩm
$sqlCount = "SELECT COUNT(*) FROM products $whereSQL";
$countStmt = $conn->prepare($sqlCount);
$countStmt->execute($params);
$totalProducts = $countStmt->fetchColumn();
$totalPages = ceil($totalProducts / $perPage);
// Lấy danh sách sản phẩm
$sql = "SELECT id, name, brand, price, old_price, sale_percent, category, gender, material, color, pattern, sizes, image_main FROM products $whereSQL ORDER BY id DESC LIMIT $perPage OFFSET $offset";
$stmtProd = $conn->prepare($sql);
$stmtProd->execute($params);
$products = $stmtProd->fetchAll(PDO::FETCH_ASSOC);

// Lấy các giá trị filter từ database
$filterCategories = $conn->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
$filterMaterials = $conn->query("SELECT DISTINCT material FROM products WHERE material IS NOT NULL AND material != '' ORDER BY material")->fetchAll(PDO::FETCH_COLUMN);
$filterColors = $conn->query("SELECT DISTINCT color FROM products WHERE color IS NOT NULL AND color != '' ORDER BY color")->fetchAll(PDO::FETCH_COLUMN);
$filterPatterns = $conn->query("SELECT DISTINCT pattern FROM products WHERE pattern IS NOT NULL AND pattern != '' ORDER BY pattern")->fetchAll(PDO::FETCH_COLUMN);
$filterBrands = $conn->query("SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL AND brand != '' ORDER BY brand")->fetchAll(PDO::FETCH_COLUMN);

// Lấy banners từ database
$banners = [];
try {
    $stmtBanners = $conn->query("SELECT * FROM banners WHERE is_active = 1 ORDER BY sort_order ASC, id ASC");
    $banners = $stmtBanners->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Nếu bảng banners chưa tồn tại, bỏ qua
    $banners = [];
}

// helper format giá kiểu 290.000₫
function vnd($n){
    if ($n === null || $n === '') return '';
    return number_format((int)$n, 0, ',', '.') . "₫";
}

$pageTitle = 'Adodas – Adodas, Quần Áo, Phụ Kiện Thời Trang chính hãng';
$includeTrangchuCSS = true;
include 'header.php';
?>

<!-- HEADER + TAGLINE -->
<section class="container">

<!-- BANNER CHUYỂN ẢNH -->
<?php if (!empty($banners)): ?>
<div id="bannerSlide"
     class="carousel slide carousel-fade mt-3"
     data-bs-ride="carousel"
     data-bs-interval="3000"
     data-bs-pause="false"
     data-bs-wrap="true">

  <div class="carousel-inner">
    <?php foreach ($banners as $index => $banner): ?>
      <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
        <div class="hero-slide">
          <div class="hero-slide-inner">
            <div class="hero-img-wrap">
              <img src="../<?= htmlspecialchars($banner['image']) ?>" alt="<?= htmlspecialchars($banner['title'] ?? 'Banner') ?>">
            </div>
            <div class="hero-content">
              <?php if (!empty($banner['kicker'])): ?>
                <div class="hero-kicker"><?= htmlspecialchars($banner['kicker']) ?></div>
              <?php endif; ?>
              <?php if (!empty($banner['title']) || !empty($banner['subtitle'])): ?>
                <div class="hero-title">
                  <?php if (!empty($banner['title'])): ?>
                    <span><?= htmlspecialchars($banner['title']) ?></span>
                  <?php endif; ?>
                  <?php if (!empty($banner['subtitle'])): ?>
                    <span><?= htmlspecialchars($banner['subtitle']) ?></span>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
              <?php if (!empty($banner['description'])): ?>
                <div class="hero-sub">
                  <?= htmlspecialchars($banner['description']) ?>
                </div>
              <?php endif; ?>
              <?php if (!empty($banner['link'])): ?>
                <div class="hero-cta-row">
                  <a href="<?= htmlspecialchars($banner['link']) ?>" class="btn btn-light">
                    <?= htmlspecialchars($banner['link_text'] ?? 'Xem ngay') ?> →
                  </a>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if (count($banners) > 1): ?>
  <button class="carousel-control-prev" type="button" data-bs-target="#bannerSlide" data-bs-slide="prev">
    <span class="carousel-control-prev-icon"></span>
  </button>
  <button class="carousel-control-next" type="button" data-bs-target="#bannerSlide" data-bs-slide="next">
    <span class="carousel-control-next-icon"></span>
  </button>
  <?php endif; ?>

</div>
<?php endif; ?>


<!-- BỘ LỌC NÂNG CAO -->
<section class="container py-3">
  <form method="GET" action="trangchu.php" class="row g-2 align-items-end mb-4">
    <div class="col-md-3">
      <label class="form-label">Danh mục</label>
      <select name="category" class="form-select">
        <option value="">Tất cả</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= htmlspecialchars($cat['slug']) ?>" <?= (($_GET['category'] ?? '') === $cat['slug'] ? 'selected' : '') ?>><?= htmlspecialchars($cat['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label">Hãng</label>
      <select name="brand" class="form-select">
        <option value="">Tất cả</option>
        <?php foreach ($filterBrands as $brand): ?>
          <option value="<?= htmlspecialchars($brand) ?>" <?= (($_GET['brand'] ?? '') === $brand ? 'selected' : '') ?>><?= htmlspecialchars($brand) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label">Màu</label>
      <select name="color" class="form-select">
        <option value="">Tất cả</option>
        <?php foreach ($filterColors as $color): ?>
          <option value="<?= htmlspecialchars($color) ?>" <?= (($_GET['color'] ?? '') === $color ? 'selected' : '') ?>><?= htmlspecialchars($color) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label">Giá từ</label>
      <input type="number" name="price_min" class="form-control" value="<?= htmlspecialchars($_GET['price_min'] ?? '') ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label">Giá đến</label>
      <input type="number" name="price_max" class="form-control" value="<?= htmlspecialchars($_GET['price_max'] ?? '') ?>">
    </div>
    <div class="col-md-1 d-grid">
      <button type="submit" class="btn btn-dark">Lọc</button>
    </div>
  </form>
</section>

</section>

<!-- FLASH DEAL SECTION -->
<?php if (count($products) > 0): ?>
<section class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold mb-0" style="font-size: 1.75rem;">FLASH DEAL</h2>
    <a href="trangchu.php?category=sale" class="text-decoration-none text-dark fw-bold">
      Xem tất cả <i class="bi bi-arrow-right"></i>
    </a>
  </div>
  <div class="row g-3">
    <?php 
    // Lấy 4 sản phẩm có sale_percent > 0 hoặc có old_price
    $flashProducts = array_filter($products, function($p) {
      return (!empty($p['sale_percent']) && $p['sale_percent'] > 0) || (!empty($p['old_price']) && $p['old_price'] > $p['price']);
    });
    $flashProducts = array_slice($flashProducts, 0, 4);
    
    if (count($flashProducts) === 0) {
      $flashProducts = array_slice($products, 0, 4);
    }
    
    foreach ($flashProducts as $p): 
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
    <div class="col-6 col-md-3">
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
</section>
<?php endif; ?>

<!-- FEATURED CATEGORIES từ DB -->
<?php if (!empty($categories)): ?>
<section class="container py-4">
  <h2 class="fw-bold mb-4" style="font-size: 1.75rem;">Danh mục nổi bật</h2>
  <div class="row g-3">
    <?php 
    $featuredCategories = array_slice($categories, 0, 4);
    $gradients = [
      'linear-gradient(135deg,#667eea 0%,#764ba2 100%)',
      'linear-gradient(135deg,#f093fb 0%,#f5576c 100%)',
      'linear-gradient(135deg,#4facfe 0%,#00f2fe 100%)',
      'linear-gradient(135deg,#43e97b 0%,#38f9d7 100%)'
    ];
    foreach ($featuredCategories as $index => $cat): 
      $gradient = $gradients[$index % count($gradients)];
      $catImg = '';
      if (!empty($cat['image'])) {
        $catImgFileName = trim($cat['image']);
        if (strpos($catImgFileName, 'http') === 0 || strpos($catImgFileName, '//') === 0) {
          $catImg = $catImgFileName;
        } else {
          $catImg = 'uploads/categories/' . $catImgFileName;
        }
      }
    ?>
      <div class="col-6 col-md-3">
        <a href="trangchu.php?category=<?= htmlspecialchars($cat['slug']) ?>" class="text-decoration-none">
          <div class="category-card text-center p-4 border rounded" style="background: <?= $gradient ?>; color: #fff; min-height: 150px; display: flex; flex-direction: column; justify-content: center; position: relative; overflow: hidden;">
            <?php if ($catImg): ?>
              <img src="<?= htmlspecialchars($catImg) ?>" alt="<?= htmlspecialchars($cat['name']) ?>" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; opacity: 0.3; z-index: 0;">
            <?php endif; ?>
            <h5 class="fw-bold mb-2" style="position: relative; z-index: 1;"><?= htmlspecialchars($cat['name']) ?></h5>
          </div>
        </a>
      </div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- PRODUCT GRID (render từ DB) -->
<section class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold mb-0" style="font-size: 1.75rem;">Tất cả sản phẩm</h2>
    <span class="text-muted">(<?= $totalProducts ?> sản phẩm)</span>
  </div>
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
        }

        $price_show = vnd($p['price']);
        $old_show   = (!empty($p['old_price']) && $p['old_price'] > 0) ? vnd($p['old_price']) : '';
    ?>
    <div class="col-6 col-md-4 col-lg-3">
      <div
        class="card product-card position-relative"
        data-id="<?php echo $p['id']; ?>"
        data-name="<?php echo htmlspecialchars($p['name']); ?>"
        data-category="<?php echo htmlspecialchars($p['category']); ?>"
        data-gender="<?php echo htmlspecialchars($p['gender']); ?>"
        data-material="<?php echo htmlspecialchars($p['material']); ?>"
        data-color="<?php echo htmlspecialchars($p['color']); ?>"
        data-size="<?php echo htmlspecialchars($p['sizes']); ?>"
        data-pattern="<?php echo htmlspecialchars($p['pattern']); ?>"
        data-price="<?php echo htmlspecialchars($p['price']); ?>"
      >
        <?php echo $badge_html; ?>

        <img class="card-img-top" src="<?php echo htmlspecialchars($img); ?>" alt="">
        <div class="card-body">
          <div class="small text-muted"><?php echo htmlspecialchars($p['brand']); ?></div>
          <h6 class="card-title mb-1"><?php echo htmlspecialchars($p['name']); ?></h6>

          <div class="d-flex align-items-baseline gap-2">
            <span class="price"><?php echo $price_show; ?></span>
            <?php if ($old_show !== ''): ?>
              <small class="old"><?php echo $old_show; ?></small>
            <?php endif; ?>
          </div>

          <!-- Các nút Thêm vào giỏ / Mua ngay sẽ được JS phía dưới tự inject -->
        </div>
      </div>
    </div>
    <?php endforeach; ?>

    <?php if (count($products) === 0): ?>
      <div class="col-12">
        <div class="alert alert-light border text-center">
          Chưa có sản phẩm nào.
        </div>
      </div>
    <?php endif; ?>

  </div>
  <!-- PHÂN TRANG -->
  <nav aria-label="Page navigation products">
    <ul class="pagination justify-content-center mt-4">
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
      <li class="page-item <?= $i == $page ? 'active' : '' ?>">
        <a class="page-link" href="?<?php $params = $_GET; $params['page'] = $i; echo http_build_query($params); ?>"><?= $i ?></a>
      </li>
      <?php endfor; ?>
    </ul>
  </nav>
</section>

<!-- SCRIPT: FILTER -->
<script>
(function(){
  const products = Array.from(document.querySelectorAll('.product-card'));

  const catChecks     = Array.from(document.querySelectorAll('.filter-category'));
  const genderChecks  = Array.from(document.querySelectorAll('.filter-gender'));
  const matChecks     = Array.from(document.querySelectorAll('.filter-material'));
  const colorChecks   = Array.from(document.querySelectorAll('.filter-color'));
  const patternChecks = Array.from(document.querySelectorAll('.filter-pattern'));
  const sizeBtns      = Array.from(document.querySelectorAll('.filter-size'));

  const priceMinInput = document.getElementById('priceMin');
  const priceMaxInput = document.getElementById('priceMax');
  const applyPriceBtn = document.getElementById('applyPrice');

  let activeSizes = [];

  sizeBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      const val = btn.getAttribute('data-size');
      if (activeSizes.includes(val)) {
        activeSizes = activeSizes.filter(v => v !== val);
        btn.classList.remove('btn-dark');
        btn.classList.add('btn-outline-secondary');
      } else {
        activeSizes.push(val);
        btn.classList.remove('btn-outline-secondary');
        btn.classList.add('btn-dark');
      }
      applyFilter();
    });
  });

  [...catChecks, ...genderChecks, ...matChecks, ...colorChecks, ...patternChecks]
    .forEach(input => {
      input.addEventListener('change', applyFilter);
    });

  applyPriceBtn?.addEventListener('click', () => {
    applyFilter();
  });

  function getCheckedValues(nodeList) {
    return nodeList
      .filter(i => i.checked)
      .map(i => i.value.toLowerCase().trim());
  }

  function applyFilter(){
    const selectedCats     = getCheckedValues(catChecks);
    const selectedGender   = getCheckedValues(genderChecks);
    const selectedMat      = getCheckedValues(matChecks);
    const selectedColor    = getCheckedValues(colorChecks);
    const selectedPattern  = getCheckedValues(patternChecks);
    const selectedSizes    = activeSizes.slice();

    const minPrice = priceMinInput?.value ? parseInt(priceMinInput.value,10) : null;
    const maxPrice = priceMaxInput?.value ? parseInt(priceMaxInput.value,10) : null;

    let visibleCount = 0;

    products.forEach(card => {
      const cat     = (card.dataset.category  || '').toLowerCase();
      const gender  = (card.dataset.gender    || '').toLowerCase();
      const mat     = (card.dataset.material  || '').toLowerCase();
      const color   = (card.dataset.color     || '').toLowerCase();
      const pattern = (card.dataset.pattern   || '').toLowerCase();
      const sizes   = (card.dataset.size      || '').toLowerCase();
      const price   = card.dataset.price ? parseInt(card.dataset.price,10) : null;

      let hideByFilter = false;

      if (selectedCats.length > 0 && !selectedCats.includes(cat)) {
        hideByFilter = true;
      }

      if (!hideByFilter && selectedGender.length > 0) {
        const okGender = selectedGender.some(g => gender.includes(g));
        if (!okGender) hideByFilter = true;
      }

      if (!hideByFilter && selectedMat.length > 0 && !selectedMat.includes(mat)) {
        hideByFilter = true;
      }

      if (!hideByFilter && selectedColor.length > 0 && !selectedColor.includes(color)) {
        hideByFilter = true;
      }

      if (!hideByFilter && selectedPattern.length > 0 && !selectedPattern.includes(pattern)) {
        hideByFilter = true;
      }

      if (!hideByFilter && selectedSizes.length > 0) {
        const arrSize = sizes.split(',').map(s => s.trim());
        const okSize = selectedSizes.some(sz => arrSize.includes(sz));
        if (!okSize) hideByFilter = true;
      }

      if (!hideByFilter && minPrice !== null && (price === null || price < minPrice)) {
        hideByFilter = true;
      }
      if (!hideByFilter && maxPrice !== null && (price === null || price > maxPrice)) {
        hideByFilter = true;
      }

      if (hideByFilter) {
        card.setAttribute('data-hide-filter', '1');
      } else {
        card.removeAttribute('data-hide-filter');
        visibleCount++;
      }
    });

    toggleNoResultFilter(visibleCount === 0);
  }

  function toggleNoResultFilter(show) {
    let el = document.getElementById('noResultFilter');
    if (show) {
      if (!el) {
        el = document.createElement('div');
        el.id = 'noResultFilter';
        el.className = 'alert alert-light border text-center mt-3';
        el.textContent = 'Không có sản phẩm nào khớp bộ lọc.';
        const gridSection = document.querySelector('section.container.py-3');
        gridSection && gridSection.appendChild(el);
      }
    } else if (el) {
      el.remove();
    }
  }

  applyFilter();
})();
</script>

<!-- SCRIPT: SORT -->
<script>
(function(){
  const sortSelect = document.getElementById('sortSelect');
  const gridRow    = document.querySelector('section.container.py-3 .row.g-3');

  function getCols() {
    return Array.from(gridRow.querySelectorAll('.col-6.col-md-4.col-lg-3'));
  }

  function getPriceNum(col) {
    const p = col.querySelector('.product-card')?.dataset.price || '';
    return parseInt(p,10) || 0;
  }

  function applySort(mode){
    const cols = getCols();

    if (mode === 'price-asc') {
      cols.sort((a,b) => getPriceNum(a) - getPriceNum(b));
    } else if (mode === 'price-desc') {
      cols.sort((a,b) => getPriceNum(b) - getPriceNum(a));
    } else {
      return;
    }

    cols.forEach(col => gridRow.appendChild(col));
  }

  sortSelect.addEventListener('change', () => {
    applySort(sortSelect.value);
  });
})();
</script>

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
