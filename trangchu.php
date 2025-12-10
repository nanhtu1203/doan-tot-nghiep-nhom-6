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

// Lọc nâng cao
$where = [];
$params = [];
if (!empty($_GET['category'])) {
    $where[] = 'category = ?';
    $params[] = $_GET['category'];
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

// Thêm sau dòng kết nối DB
$stmtCat = $conn->query("SELECT id, name, slug FROM categories ORDER BY sort_order, name");
$categories = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

// helper format giá kiểu 290.000₫
function vnd($n){
    if ($n === null || $n === '') return '';
    return number_format((int)$n, 0, ',', '.') . "₫";
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Adodas – Adodas, Quần Áo, Phụ Kiện Thời Trang chính hãng</title>

  <!-- Bootstrap + Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <!-- CSS của em -->
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/csstrangchu.css">

<style>
  /* BIẾN DROPDOWN CỦA BOOTSTRAP THÀNH HOVER-MENU */
.nav-item.dropdown:hover .dropdown-menu {
    display: block;
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

/* Ẩn mặc định */
.dropdown-menu {
    display: block;
    opacity: 0;
    visibility: hidden;
    transform: translateY(10px);
    transition: all 0.25s ease;
    margin-top: 0;
    border-radius: 8px;
    padding: 15px 20px;
    border: 1px solid #e5e5e5;
    box-shadow: 0 8px 25px rgba(0,0,0,0.08);
}

/* Từng item */
.dropdown-menu .dropdown-item {
    font-size: 14px;
    padding: 8px 10px;
    transition: 0.25s;
}

.dropdown-menu .dropdown-item:hover {
    background: #f5f5f5;
    color: #000;
    transform: translateX(4px);
}

/* Divider đẹp hơn */
.dropdown-divider {
    margin: 6px 0;
    border-color: #ddd;
}

/* Mega menu styling */
.mega-menu .dropdown-menu {
    left: 50%;
    transform: translateX(-50%) translateY(10px);
}

.mega-menu:hover .dropdown-menu {
    transform: translateX(-50%) translateY(0);
}

</style>
  
</head>

<body>

<!-- TOPBAR -->
<div class="topbar">
  <div class="container d-flex justify-content-between align-items-center">
    <div class="topbar-left">
      <span>MIỄN PHÍ GIAO HÀNG TRÊN TOÀN QUỐC</span>
    </div>
    
    <div class="topbar-right d-none d-lg-flex gap-4 align-items-center">
      <span>Hotline: <b>089.887.5522</b></span>
      <?php if (isset($_SESSION['user_id'])): ?>
        <a href="history.php" class="auth-link">Lịch sử mua hàng</a>
        <a href="track_order.php" class="auth-link">Tra cứu đơn hàng</a>
      <?php else: ?>
        <a href="#" class="auth-link" data-bs-toggle="modal" data-bs-target="#loginModal">ĐĂNG NHẬP</a>
        <a href="#" class="auth-link" data-bs-toggle="modal" data-bs-target="#registerModal">ĐĂNG KÝ</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top border-bottom">
  <div class="container">
    <a class="navbar-brand logo d-flex align-items-center" href="trangchu.php">
      <strong style="font-size: 24px; font-weight: 700; color: #000;">Adodas</strong>
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav me-auto">
        <!-- DANH MỤC (FROM DB) -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="catMenu" role="button" data-bs-toggle="dropdown">
            Danh mục
          </a>
          <ul class="dropdown-menu" aria-labelledby="catMenu">
            <?php foreach ($categories as $cat): ?>
              <li><a class="dropdown-item" href="trangchu.php?category=<?= htmlspecialchars($cat['slug']) ?>">
                <?= htmlspecialchars($cat['name']) ?>
              </a></li>
            <?php endforeach; ?>
          </ul>
        </li>

        <!-- QUẦN ÁO -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="quanAoMenu" role="button">
            QUẦN ÁO
          </a>
          <ul class="dropdown-menu" aria-labelledby="quanAoMenu">
            <li><h6 class="dropdown-header">Nổi bật ⭐</h6></li>
            <li><a class="dropdown-item" href="#">MLB</a></li>
            <li><a class="dropdown-item" href="#">ADVL</a></li>
            <li><a class="dropdown-item" href="#">Drew House</a></li>
            <li><a class="dropdown-item" href="#">Essentials</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><h6 class="dropdown-header">Nike</h6></li>
            <li><a class="dropdown-item" href="#">Áo</a></li>
            <li><a class="dropdown-item" href="#">Quần</a></li>
            <li><a class="dropdown-item" href="#">Váy</a></li>
          </ul>
        </li>

        <!-- PHỤ KIỆN -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="phuKienMenu" role="button">
            PHỤ KIỆN
          </a>
          <ul class="dropdown-menu" aria-labelledby="phuKienMenu">
            <li><a class="dropdown-item" href="#">Dép</a></li>
            <li><a class="dropdown-item" href="#">Kính mắt</a></li>
            <li><a class="dropdown-item" href="#">Túi xách/Túi đeo chéo</a></li>
            <li><a class="dropdown-item" href="#">Tất</a></li>
            <li><a class="dropdown-item" href="#">Khẩu trang</a></li>
            <li><a class="dropdown-item" href="#">Balo</a></li>
            <li><a class="dropdown-item" href="#">Chăm sóc giày</a></li>
          </ul>
        </li>

        <!-- THƯƠNG HIỆU -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="brandMenu" role="button">
            THƯƠNG HIỆU
          </a>
          <ul class="dropdown-menu" aria-labelledby="brandMenu">
            <li><a class="dropdown-item" href="trangchu.php?brand=nike">Nike</a></li>
            <li><a class="dropdown-item" href="trangchu.php?brand=jordan">Jordan</a></li>
            <li><a class="dropdown-item" href="trangchu.php?brand=adidas">Adidas</a></li>
            <li><a class="dropdown-item" href="trangchu.php?brand=newbalance">New Balance</a></li>
            <li><a class="dropdown-item" href="trangchu.php?brand=converse">Converse</a></li>
            <li><a class="dropdown-item" href="trangchu.php?brand=puma">Puma</a></li>
            <li><a class="dropdown-item" href="trangchu.php?brand=vans">Vans</a></li>
            <li><a class="dropdown-item" href="trangchu.php?brand=mlb">MLB</a></li>
          </ul>
        </li>

        <!-- SALE -->
        <li class="nav-item">
          <a class="nav-link text-danger fw-bold" href="trangchu.php?category=sale">
            SALE 🔥
          </a>
        </li>
      </ul>

      <div class="d-flex align-items-center gap-2">
        <div class="input-group search">
          <input id="searchBox" class="form-control" placeholder="Tìm kiếm...">
          <button id="searchBtn" class="btn btn-outline-secondary">
            <i class="bi bi-search"></i>
          </button>
        </div>
        <a class="cart position-relative" href="#">
          <i class="bi bi-bag fs-4"></i>
          <span class="badge bg-danger">2</span>
        </a>
        <div class="user-menu-container position-relative">

            <div id="userIcon" class="icon-wrap" style="cursor:pointer;">
                <svg class="icon" viewBox="0 0 24 24">
                    <circle cx="12" cy="7" r="4"></circle>
                    <path d="M4 20c0-4 4-7 8-7s8 3 8 7"></path>
                </svg>
            </div>

            <div id="userDropdown" class="user-dropdown">
                <a href="profile.php">Thông tin tài khoản</a>
                <a href="history.php">Lịch sử mua hàng</a>
                <a href="track_order.php">Tra cứu đơn hàng</a>
                <a href="address.php">Thay đổi địa chỉ</a>
                <a href="#" id="logoutFromMenu">Đăng xuất</a>
            </div>

        </div>


      </div>
    </div>
  </div>
</nav>

<!-- HEADER + TAGLINE -->
<section class="container">

<!-- BANNER CHUYỂN ẢNH KIỂU ADIDAS -->
<div id="bannerSlide"
     class="carousel slide carousel-fade mt-3"
     data-bs-ride="carousel"
     data-bs-interval="3000"
     data-bs-pause="false"
     data-bs-wrap="true">

  <div class="carousel-inner">

    <!-- SLIDE 1 -->
    <div class="carousel-item active">
      <div class="hero-slide">
        <div class="hero-slide-inner">
          <div class="hero-img-wrap">
            <img src="../images/banner1.png" alt="Giày thể thao giảm giá">
          </div>
          <div class="hero-content">
            <div class="hero-kicker">BLACK FRIDAY</div>
            <div class="hero-title">
              <span>UP TO</span>
              <span>60%</span>
              <span>OFF</span>
            </div>
            <div class="hero-sub">
              New styles added. Ưu đãi lớn cho giày thể thao Adodas, số lượng có hạn.
            </div>
            <div class="hero-cta-row">
              <a href="trangchu.php?category=men" class="btn btn-light">Men →</a>
              <a href="trangchu.php?category=women" class="btn btn-light">Women →</a>
              <a href="trangchu.php?category=kids" class="btn btn-light">Kids →</a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- SLIDE 2 -->
    <div class="carousel-item">
      <div class="hero-slide">
        <div class="hero-slide-inner">
          <div class="hero-img-wrap">
            <img src="../images/baner2.png" alt="Bộ sưu tập mới">
          </div>
          <div class="hero-content">
            <div class="hero-kicker">NEW ARRIVALS</div>
            <div class="hero-title">
              <span>GIÀY</span>
              <span>MÙA ĐÔNG</span>
            </div>
            <div class="hero-sub">
              Chống trơn trượt, êm chân, giữ ấm tốt – ready cho mọi cuộc vui cuối năm.
            </div>
            <div class="hero-cta-row">
              <a href="trangchu.php?category=hang-moi-ve" class="btn btn-light">Shop now →</a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- SLIDE 3 – DUP / TÙY Ý -->
    <div class="carousel-item">
      <div class="hero-slide">
        <div class="hero-slide-inner">
          <div class="hero-img-wrap">
            <img src="../images/baner3.png" alt="Best seller">
          </div>
          <div class="hero-content">
            <div class="hero-kicker">BEST SELLERS</div>
            <div class="hero-title">
              <span>TOP</span>
              <span>PICKS</span>
            </div>
            <div class="hero-sub">
              Những mẫu được săn nhiều nhất tháng, nhanh tay kẻo hết size.
            </div>
            <div class="hero-cta-row">
              <a href="trangchu.php" class="btn btn-light">Xem tất cả →</a>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>

  <button class="carousel-control-prev" type="button" data-bs-target="#bannerSlide" data-bs-slide="prev">
    <span class="carousel-control-prev-icon"></span>
  </button>
  <button class="carousel-control-next" type="button" data-bs-target="#bannerSlide" data-bs-slide="next">
    <span class="carousel-control-next-icon"></span>
  </button>

</div>

  <!-- FILTER BAR -->
  <div class="filterbar mt-3 d-flex flex-wrap gap-2">

    <!-- Loại Giày -->
    <div class="dropdown">
      <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">Loại Giày</button>
      <div class="dropdown-menu p-2 filter-menu">
        <label class="form-check">
          <input class="form-check-input filter-category" type="checkbox" value="chay-bo"> Chạy Bộ
        </label>
        <label class="form-check">
          <input class="form-check-input filter-category" type="checkbox" value="Adodas"> Adodas
        </label>
        <label class="form-check">
          <input class="form-check-input filter-category" type="checkbox" value="the-thao"> Thể Thao
        </label>
      </div>
    </div>

    <!-- Giới tính -->
    <div class="dropdown">
      <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">Giới tính</button>
      <div class="dropdown-menu p-2 filter-menu">
        <label class="form-check">
          <input class="form-check-input filter-gender" type="checkbox" value="nam"> Nam
        </label>
        <label class="form-check">
          <input class="form-check-input filter-gender" type="checkbox" value="nữ"> Nữ
        </label>
        <label class="form-check">
          <input class="form-check-input filter-gender" type="checkbox" value="unisex"> Unisex
        </label>
      </div>
    </div>

    <!-- Chất liệu -->
    <div class="dropdown">
      <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">Chất liệu</button>
      <div class="dropdown-menu p-2 filter-menu">
        <label class="form-check">
          <input class="form-check-input filter-material" type="checkbox" value="vải"> Vải
        </label>
        <label class="form-check">
          <input class="form-check-input filter-material" type="checkbox" value="da"> Da
        </label>
        <label class="form-check">
          <input class="form-check-input filter-material" type="checkbox" value="lưới"> Lưới
        </label>
      </div>
    </div>

    <!-- Màu sắc -->
    <div class="dropdown">
      <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">Màu sắc</button>
      <div class="dropdown-menu p-2 filter-menu">
        <label class="form-check d-flex align-items-center gap-2">
          <input class="form-check-input filter-color" type="checkbox" value="trắng">
          <span>Trắng</span>
        </label>
        <label class="form-check d-flex align-items-center gap-2">
          <input class="form-check-input filter-color" type="checkbox" value="đen">
          <span>Đen</span>
        </label>
        <label class="form-check d-flex align-items-center gap-2">
          <input class="form-check-input filter-color" type="checkbox" value="đỏ">
          <span>Đỏ</span>
        </label>
        <label class="form-check d-flex align-items-center gap-2">
          <input class="form-check-input filter-color" type="checkbox" value="xanh">
          <span>Xanh</span>
        </label>
        <label class="form-check d-flex align-items-center gap-2">
          <input class="form-check-input filter-color" type="checkbox" value="be">
          <span>Be</span>
        </label>
      </div>
    </div>

    <!-- Size -->
    <div class="dropdown">
      <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">Size Giày Dép</button>
      <div class="dropdown-menu p-2 filter-menu">
        <div class="d-flex flex-wrap gap-2">
          <button class="btn btn-sm btn-outline-secondary filter-size" data-size="35">35</button>
          <button class="btn btn-sm btn-outline-secondary filter-size" data-size="36">36</button>
          <button class="btn btn-sm btn-outline-secondary filter-size" data-size="37">37</button>
          <button class="btn btn-sm btn-outline-secondary filter-size" data-size="38">38</button>
          <button class="btn btn-sm btn-outline-secondary filter-size" data-size="39">39</button>
          <button class="btn btn-sm btn-outline-secondary filter-size" data-size="40">40</button>
          <button class="btn btn-sm btn-outline-secondary filter-size" data-size="41">41</button>
          <button class="btn btn-sm btn-outline-secondary filter-size" data-size="42">42</button>
        </div>
      </div>
    </div>

    <!-- Họa tiết -->
    <div class="dropdown">
      <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">Họa Tiết</button>
      <div class="dropdown-menu p-2 filter-menu">
        <label class="form-check">
          <input class="form-check-input filter-pattern" type="checkbox" value="trơn"> Trơn
        </label>
        <label class="form-check">
          <input class="form-check-input filter-pattern" type="checkbox" value="logo"> Logo
        </label>
        <label class="form-check">
          <input class="form-check-input filter-pattern" type="checkbox" value="phoi-mau"> Phối màu
        </label>
      </div>
    </div>

    <!-- Khoảng giá -->
    <div class="dropdown">
      <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">Khoảng giá</button>
      <div class="dropdown-menu p-3 filter-menu" style="min-width:260px">
        <div class="d-flex gap-2">
          <input id="priceMin" type="number" class="form-control form-control-sm" placeholder="Từ">
          <input id="priceMax" type="number" class="form-control form-control-sm" placeholder="Đến">
        </div>
        <button id="applyPrice" class="btn btn-sm btn-dark w-100 mt-2">Áp dụng</button>
      </div>
    </div>
        <div class="ms-auto">
      <select id="sortSelect" class="form-select form-select-sm w-auto">
        <option value="">Sắp xếp</option>
        <option value="price-asc">Giá tăng dần</option>
        <option value="price-desc">Giá giảm dần</option>
        <option value="newest">Mới nhất</option>
        <option value="bestseller">Bán chạy</option>
      </select>
    </div>
  </div>
  </div>

<!-- BỘ LỌC NÂNG CAO -->
<section class="container py-3">
  <form method="GET" class="row g-2 align-items-end mb-4">
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
      <input type="text" name="brand" class="form-control" placeholder="VD: Nike" value="<?= htmlspecialchars($_GET['brand'] ?? '') ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label">Màu</label>
      <input type="text" name="color" class="form-control" placeholder="VD: Đen" value="<?= htmlspecialchars($_GET['color'] ?? '') ?>">
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
      $img = $p['image_main'] !== '' ? $p['image_main'] : '../images/placeholder.png';
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
<section class="container py-4">
  <h2 class="fw-bold mb-4" style="font-size: 1.75rem;">Danh mục nổi bật</h2>
  <div class="row g-3">
    <?php foreach (array_slice($categories, 0, 4) as $cat): ?>
      <div class="col-6 col-md-3">
        <a href="trangchu.php?category=<?= htmlspecialchars($cat['slug']) ?>" class="text-decoration-none">
          <div class="category-card text-center p-4 border rounded" style="background: linear-gradient(135deg,#667eea 0%,#764ba2 100%); color: #fff; min-height: 150px; display: flex; flex-direction: column; justify-content: center;">
            <h5 class="fw-bold mb-2"><?= htmlspecialchars($cat['name']) ?></h5>
          </div>
        </a>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- PRODUCT GRID (render từ DB) -->
<section class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold mb-0" style="font-size: 1.75rem;">Tất cả sản phẩm</h2>
    <span class="text-muted">(<?= $totalProducts ?> sản phẩm)</span>
  </div>
  <div class="row g-3">

    <?php foreach ($products as $p): 
        $img = $p['image_main'] !== '' ? $p['image_main'] : '../images/placeholder.png';

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

<!-- FOOTER -->
<footer>
  <div class="container">
    <div class="row">
      <div class="col-md-3 mb-4">
        <h5>Về AdodasDaily</h5>
        <a href="#">Giới thiệu</a>
        <a href="#">Tuyển dụng</a>
        <a href="#">Liên hệ</a>
        <a href="#">Hệ thống cửa hàng</a>
      </div>
      <div class="col-md-3 mb-4">
        <h5>Hỗ trợ khách hàng</h5>
        <a href="#">Câu hỏi thường gặp</a>
        <a href="#">Hướng dẫn đặt hàng</a>
        <a href="#">Hướng dẫn chọn size</a>
        <a href="#">Chính sách đổi trả</a>
        <a href="#">Chính sách bảo hành</a>
      </div>
      <div class="col-md-3 mb-4">
        <h5>Thông tin</h5>
        <a href="#">Tin tức</a>
        <a href="#">Tạp chí giày</a>
        <a href="#">Khuyến mãi</a>
        <a href="#">Sitemap</a>
      </div>
      <div class="col-md-3 mb-4">
        <h5>Liên hệ</h5>
        <p style="color: #ccc; margin-bottom: 0.5rem;">
          <strong>Hotline:</strong><br>
          <a href="tel:0898875522" style="color: #fff; font-size: 1.1rem;">089.887.5522</a>
        </p>
        <p style="color: #ccc; margin-bottom: 0.5rem;">
          <strong>Email:</strong><br>
          <a href="mailto:info@Adodasdaily.vn" style="color: #fff;">info@Adodasdaily.vn</a>
        </p>
        <p style="color: #ccc;">
          <strong>Giờ làm việc:</strong><br>
          9:30 - 22:00 (Tất cả các ngày)
        </p>
      </div>
    </div>
    <div class="footer-bottom">
      <div class="footer-links">
        <a href="#">Chính sách bảo mật</a>
        <a href="#">Điều khoản sử dụng</a>
        <a href="#">Chính sách vận chuyển</a>
      </div>
      <p class="mb-0">© 2025 Adodas. All rights reserved.</p>
    </div>
  </div>
</footer>

<!-- OVERLAY ĐĂNG XUẤT -->
<div id="logoutOverlay" style="
  display:none;
  position:fixed; top:0; left:0; width:100%; height:100%;
  background:rgba(29,2,2,0.6); backdrop-filter:blur(3px);
  justify-content:center; align-items:center; z-index:99999;">
  
  <div style="background:white; padding:30px 40px; border-radius:10px; text-align:center; max-width:300px;">
    <h5>Bạn có chắc muốn đăng xuất?</h5>
    <div style="margin-top:20px;">
      <button id="confirmLogout" class="btn btn-danger me-3">Có</button>
      <button id="cancelLogout" class="btn btn-secondary">Không</button>
    </div>
  </div>
</div>

<!-- MINI CART BOX -->
<div id="miniCartBox">
  <div class="mini-header">
    <div>Giỏ hàng</div>
    <button id="miniCloseBtn">Đóng</button>
  </div>

  <div class="mini-body" id="miniList">
    <!-- render bằng JS -->
  </div>

  <div class="mini-footer">
    <div class="mini-totalline">
      <span class="label">Tổng cộng</span>
      <span class="value" id="miniTotal">0₫</span>
    </div>
    <div class="mini-actions">
      <button class="mini-clear-btn" id="miniClearAll">Xóa tất cả</button>
      <button class="mini-pay-btn" id="miniCheckout">Thanh toán</button>
    </div>
  </div>
</div>

<!-- TOAST GIỎ HÀNG -->
<div id="miniToast">Đã thêm vào giỏ hàng</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- SCRIPT: GIỎ HÀNG -->
<script>
(function(){
  const $ = (sel, root=document) => root.querySelector(sel);
  const $$ = (sel, root=document) => Array.from(root.querySelectorAll(sel));
  const nf = n => (n||0).toLocaleString('vi-VN') + '₫';
  const parsePrice = s => Number((s||'').replace(/[^\d]/g,'')||0);
  const encode = str => encodeURIComponent(str || '');

  const cartIcon    = $('.cart');
  const badgeEl     = $('.cart .badge');
  const miniBox     = $('#miniCartBox');
  const miniList    = $('#miniList');
  const miniTotal   = $('#miniTotal');
  const miniClose   = $('#miniCloseBtn');
  const miniClear   = $('#miniClearAll');
  const miniCheckout= $('#miniCheckout');
  const toastEl     = $('#miniToast');

  let cart = JSON.parse(localStorage.getItem('cart') || '[]');

  function saveCart(){
    localStorage.setItem('cart', JSON.stringify(cart));
  }

  function renderCart(){
    miniList.innerHTML = '';
    let total = 0;
    let count = 0;

    cart.forEach((item, idx) => {
      total += item.price * item.qty;
      count += item.qty;

      const row = document.createElement('div');
      row.className = 'mini-item';
      row.innerHTML = `
        <div class="mini-thumb">
          <img src="${item.img}" alt="sp">
        </div>
        <div class="mini-info">
          <div class="mini-name">${item.name}</div>
          <div class="mini-row1">${nf(item.price)}</div>
          <div class="mini-row2">
            <div class="qty-wrap">
              SL:
              <button class="qty-btn minus">-</button>
              <span class="qty-num">${item.qty}</span>
              <button class="qty-btn plus">+</button>
            </div>
          </div>
        </div>
        <div class="mini-side">
          <div class="mini-lineprice">${nf(item.price * item.qty)}</div>
          <div class="mini-remove-holder">
            <button class="remove-item-btn">x</button>
          </div>
        </div>
      `;

        // NÚT TRỪ SỐ LƯỢNG
        row.querySelector('.minus').addEventListener('click', () => {
            cart[idx].qty = Math.max(1, cart[idx].qty - 1);  
            saveCart();
            renderCart();
        });

        // NÚT CỘNG SỐ LƯỢNG
        row.querySelector('.plus').addEventListener('click', () => {
            cart[idx].qty += 1;
            saveCart();
            renderCart();
        });

        // NÚT XOÁ SẢN PHẨM
        row.querySelector('.remove-item-btn').addEventListener('click', () => {
            cart.splice(idx, 1);
            saveCart();
            renderCart();
        });


      miniList.appendChild(row);
    });

    miniTotal.textContent = nf(total);
    if (badgeEl) badgeEl.textContent = String(count);
  }

  function addProductToCart(name, price, img) {
    const found = cart.find(p => p.name === name);
    if (found){
      found.qty += 1;
    } else {
      cart.push({ name, price, img, qty: 1 });
    }
    saveCart();
    renderCart();
  }

  if (cartIcon){
    cartIcon.addEventListener('click', e => {
      e.preventDefault();
      miniBox.style.display = (miniBox.style.display === 'none' || miniBox.style.display === '') ? 'flex' : 'none';
    });
  }
  if (miniClose){
    miniClose.addEventListener('click', () => {
      miniBox.style.display = 'none';
    });
  }

  if (miniClear){
    miniClear.addEventListener('click', () => {
      cart = [];
      saveCart();
      renderCart();
    });
  }

  if (miniCheckout){
    miniCheckout.addEventListener('click', () => {
      miniBox.style.display = 'none';
      window.location.href = 'thanhtoan.php';
    });
  }

  let toastTimeout = null;
  function showToast(msg){
    if (!toastEl) return;
    toastEl.textContent = msg;
    toastEl.style.display = 'block';
    if (toastTimeout) clearTimeout(toastTimeout);
    toastTimeout = setTimeout(() => {
      toastEl.style.display = 'none';
    }, 1800);
  }

  $$('.product-card').forEach(card => {
    const body  = card.querySelector('.card-body') || card;
    const name  = card.querySelector('.card-title')?.textContent?.trim() || 'Sản phẩm';
    const img   = card.querySelector('img')?.getAttribute('src') || '';
    const priceText = card.querySelector('.price')?.textContent || '';
    const price = parsePrice(priceText);

    let btnRow = body.querySelector('.btn-row-purchase');
    if (!btnRow) {
      btnRow = document.createElement('div');
      btnRow.className = 'btn-row-purchase d-flex gap-2 mt-2';
      body.appendChild(btnRow);
    }

    if (!btnRow.querySelector('.btn-add-cart-auto')) {
      const addBtn = document.createElement('button');
      addBtn.type = 'button';
      addBtn.className = 'btn btn-sm btn-dark flex-fill btn-add-cart-auto';
      addBtn.textContent = 'Thêm vào giỏ';

      addBtn.addEventListener('click', e => {
        e.stopPropagation();
        addProductToCart(name, price, img);
        showToast('Đã thêm vào giỏ hàng');
      });

      btnRow.appendChild(addBtn);
    }

    if (!btnRow.querySelector('.btn-buy-now-auto')) {
      const buyBtn = document.createElement('button');
      buyBtn.type = 'button';
      buyBtn.className = 'btn btn-sm btn-outline-dark flex-fill btn-buy-now-auto';
      buyBtn.textContent = 'Mua ngay';

      buyBtn.addEventListener('click', e => {
        e.stopPropagation();
        const url = 'thanhtoan.php'
          + '?buyNow=1'
          + '&name='  + encode(name)
          + '&price=' + encode(price)
          + '&img='   + encode(img);

        window.location.href = url;
      });

      btnRow.appendChild(buyBtn);
    }
  });

  renderCart();
})();
</script>

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

<!-- SCRIPT: SEARCH -->
<script>
(function(){
  function normalize(str){
    return (str || '')
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g,'')
      .replace(/đ/g,'d');
  }

  const inputSearch   = document.getElementById('searchBox');
  const btnSearch     = document.getElementById('searchBtn');
  const productCards  = Array.from(document.querySelectorAll('.product-card'));
  const gridSection   = document.querySelector('section.container.py-3');

  function toggleNoResultSearch(isEmpty){
    let msgEl = document.getElementById('noResultSearch');
    if (isEmpty){
      if (!msgEl){
        msgEl = document.createElement('div');
        msgEl.id = 'noResultSearch';
        msgEl.className = 'alert alert-light border text-center mt-3';
        msgEl.textContent = 'Không tìm thấy sản phẩm phù hợp.';
        gridSection && gridSection.appendChild(msgEl);
      }
    } else if (msgEl){
      msgEl.remove();
    }
  }

  function runSearch(){
    const kwRaw = inputSearch.value.trim();
    const kw    = normalize(kwRaw);

    let visibleCount = 0;

    productCards.forEach(card => {
      const rawName =
        card.getAttribute('data-name') ||
        (card.querySelector('.card-title')?.textContent || '');

      const match = kw === '' ? true : normalize(rawName).includes(kw);

      if (!match && kw !== ''){
        card.setAttribute('data-hide-search','1');
      } else {
        card.removeAttribute('data-hide-search');
      }

      if (!card.hasAttribute('data-hide-filter') &&
          !card.hasAttribute('data-hide-search')){
        visibleCount++;
      }
    });

    toggleNoResultSearch(visibleCount === 0);
  }

  inputSearch.addEventListener('keydown', e => {
    if (e.key === 'Enter'){
      runSearch();
    }
  });

  btnSearch.addEventListener('click', runSearch);
  inputSearch.addEventListener('input', runSearch);
})();
</script>

<!-- SCRIPT: LOGOUT POPUP -->
<script>
(function(){
  const logoutBtn   = document.getElementById("logoutBtn");
  const overlay     = document.getElementById("logoutOverlay");
  const confirmBtn  = document.getElementById("confirmLogout");
  const cancelBtn   = document.getElementById("cancelLogout");

  logoutBtn.onclick = () => {
    overlay.style.display = "flex";
  };

  confirmBtn.onclick = () => {
    fetch("logout.php")
      .then(() => {
        window.location.href = "login.php?message=Đăng xuất thành công";
      });
  };

  cancelBtn.onclick = () => {
    overlay.style.display = "none";
  };
})();
</script>
<script>
// Toggle menu
document.getElementById("userIcon").onclick = function(e) {
    e.stopPropagation();
    const box = document.getElementById("userDropdown");
    box.style.display = box.style.display === "block" ? "none" : "block";
};

// Ấn ra ngoài để đóng menu
document.addEventListener("click", function () {
    const box = document.getElementById("userDropdown");
    box.style.display = "none";
});

// Đăng xuất trong menu
document.getElementById("logoutFromMenu").onclick = function(e){
    e.preventDefault();
    fetch("logout.php")
        .then(() => window.location.href = "login.php?message=Đăng xuất thành công");
        
};

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


<!-- MODAL ĐĂNG NHẬP -->
<div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-bottom">
        <h5 class="modal-title fw-bold" id="loginModalLabel">Đăng nhập</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <div id="loginError" class="alert alert-danger d-none"></div>
        <form id="loginForm">
          <div class="mb-3">
            <label class="form-label">Tên tài khoản hoặc địa chỉ email <span class="text-danger">*</span></label>
            <input type="email" name="email" class="form-control" required placeholder="Email của bạn">
          </div>
          <div class="mb-3">
            <label class="form-label">Mật khẩu <span class="text-danger">*</span></label>
            <input type="password" name="password" class="form-control" required placeholder="Mật khẩu">
          </div>
          <div class="mb-3 d-flex justify-content-between align-items-center">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="rememberMe">
              <label class="form-check-label small" for="rememberMe">Ghi nhớ mật khẩu</label>
            </div>
            <a href="forgot_password.php" class="small text-decoration-none">Quên mật khẩu?</a>
          </div>
          <button type="submit" class="btn btn-dark w-100 mb-3">Đăng nhập</button>
          <div class="text-center mt-3">
            <span class="small">Bạn chưa có tài khoản?</span>
            <a href="#" class="small text-decoration-none fw-bold" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#registerModal">Đăng ký</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- MODAL ĐĂNG KÝ -->
<div class="modal fade" id="registerModal" tabindex="-1" aria-labelledby="registerModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-bottom">
        <h5 class="modal-title fw-bold" id="registerModalLabel">Đăng ký</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <div id="registerError" class="alert alert-danger d-none"></div>
        <div id="registerSuccess" class="alert alert-success d-none"></div>
        <form id="registerForm">
          <div class="mb-3">
            <label class="form-label">Địa chỉ email <span class="text-danger">*</span></label>
            <input type="email" name="email" class="form-control" required placeholder="Email của bạn">
            <small class="text-muted">Một mật khẩu sẽ được gửi đến địa chỉ email của bạn.</small>
          </div>
          <div class="mb-3">
            <label class="form-label">Họ và tên <span class="text-danger">*</span></label>
            <input type="text" name="fullname" class="form-control" required placeholder="Họ và tên của bạn">
          </div>
          <div class="mb-3">
            <label class="form-label">Mật khẩu <span class="text-danger">*</span></label>
            <input type="password" name="password" class="form-control" minlength="6" required placeholder="Tối thiểu 6 ký tự">
          </div>
          <button type="submit" class="btn btn-dark w-100 mb-3">Đăng ký</button>
          <div class="text-center mt-3">
            <span class="small">Bạn đã có tài khoản?</span>
            <a href="#" class="small text-decoration-none fw-bold" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#loginModal">Đăng nhập</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- SCRIPT XỬ LÝ ĐĂNG NHẬP/ĐĂNG KÝ -->
<script>
// Xử lý đăng nhập
document.getElementById('loginForm')?.addEventListener('submit', async function(e) {
  e.preventDefault();
  
  const formData = new FormData(this);
  formData.append('action', 'login');
  
  const errorDiv = document.getElementById('loginError');
  errorDiv.classList.add('d-none');
  
  try {
    const response = await fetch('auth_ajax.php', {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
      // Đăng nhập thành công
      const modal = bootstrap.Modal.getInstance(document.getElementById('loginModal'));
      modal.hide();
      
      // Reload trang để cập nhật session
      window.location.reload();
    } else {
      // Hiển thị lỗi
      errorDiv.textContent = result.message;
      errorDiv.classList.remove('d-none');
    }
  } catch (error) {
    errorDiv.textContent = 'Có lỗi xảy ra. Vui lòng thử lại.';
    errorDiv.classList.remove('d-none');
  }
});

// Xử lý đăng ký
document.getElementById('registerForm')?.addEventListener('submit', async function(e) {
  e.preventDefault();
  
  const formData = new FormData(this);
  formData.append('action', 'register');
  
  const errorDiv = document.getElementById('registerError');
  const successDiv = document.getElementById('registerSuccess');
  errorDiv.classList.add('d-none');
  successDiv.classList.add('d-none');
  
  try {
    const response = await fetch('auth_ajax.php', {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
      // Đăng ký thành công
      successDiv.innerHTML = result.message;
      successDiv.classList.remove('d-none');
      
      // Reset form
      this.reset();
      
      // Tự động chuyển sang modal đăng nhập sau 3 giây
      setTimeout(() => {
        const registerModal = bootstrap.Modal.getInstance(document.getElementById('registerModal'));
        registerModal.hide();
        
        setTimeout(() => {
          const loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
          loginModal.show();
        }, 300);
      }, 3000);
    } else {
      // Hiển thị lỗi
      errorDiv.textContent = result.message;
      errorDiv.classList.remove('d-none');
    }
  } catch (error) {
    errorDiv.textContent = 'Có lỗi xảy ra. Vui lòng thử lại.';
    errorDiv.classList.remove('d-none');
  }
});

// Reset form khi đóng modal
document.getElementById('loginModal')?.addEventListener('hidden.bs.modal', function() {
  document.getElementById('loginForm')?.reset();
  document.getElementById('loginError').classList.add('d-none');
});

document.getElementById('registerModal')?.addEventListener('hidden.bs.modal', function() {
  document.getElementById('registerForm')?.reset();
  document.getElementById('registerError').classList.add('d-none');
  document.getElementById('registerSuccess').classList.add('d-none');
});
</script>

</body>
</html>
