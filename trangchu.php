<?php
session_start();
require 'connect.php';

// ====== XỬ LÝ ĐẶT HÀNG (KHÁCH HÀNG ĐẶT TỪ GIỎ MINI) ======
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    $customer_name  = $_POST['name'] ?? '';
    $customer_phone = $_POST['phone'] ?? '';
    $customer_addr  = $_POST['address'] ?? '';

    // Sinh mã đơn hàng ngẫu nhiên
    $order_code = 'HD' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));

    // Lấy giỏ hàng từ session (giả sử lưu ở đây)
    $cart = $_SESSION['cart'] ?? [];

    if (!empty($cart)) {
        // Lưu đơn hàng chính
        $stmt = $conn->prepare("INSERT INTO orders (order_code, customer_name, customer_phone, customer_addr, created_at) 
                                VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$order_code, $customer_name, $customer_phone, $customer_addr]);
        $order_id = $conn->lastInsertId();

        // Lưu chi tiết sản phẩm
        foreach ($cart as $product_id => $item) {
            $stmt2 = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price)
                                     VALUES (?, ?, ?, ?)");
            $stmt2->execute([$order_id, $product_id, $item['qty'], $item['price']]);
        }

        // clear cart
        unset($_SESSION['cart']);

        // chuyển cho người bán xem đơn
        header("Location: seller.php?order_code=" . urlencode($order_code));
        exit;
    }
}

// ====== LẤY SẢN PHẨM TỪ DATABASE ĐỂ HIỂN THỊ NGOÀI TRANG CHỦ ======
$stmtProd = $conn->prepare("
    SELECT id, name, brand, price, old_price, sale_percent, category, gender, material, color, pattern, sizes, image_main
    FROM products
    ORDER BY id DESC
");
$stmtProd->execute();
$products = $stmtProd->fetchAll(PDO::FETCH_ASSOC);

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
  <title>Giày Thể Thao Adodas</title>

  <!-- Bootstrap + Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <!-- CSS của em -->
  <link rel="stylesheet" href="../css/style.css">

  <!-- Thêm CSS nhỏ để xử lý ẩn theo filter/search -->
  <style>
    .product-card[data-hide-search="1"],
    .product-card[data-hide-filter="1"] {
      display: none !important;
    }

    /* mini cart box */
    #miniCartBox{
      position: fixed;
      right: 16px;
      bottom: 16px;
      width: 320px;
      max-height: 80vh;
      background: #fff;
      border: 1px solid #ddd;
      border-radius: 10px;
      box-shadow: 0 20px 40px rgba(0,0,0,.15);
      display: none;
      flex-direction: column;
      z-index: 9999;
      font-size: 14px;
    }
    #miniCartBox .mini-header{
      padding:12px 16px;
      border-bottom:1px solid #eee;
      display:flex;
      justify-content:space-between;
      align-items:center;
      font-weight:600;
    }
    #miniCartBox .mini-header button{
      border:0;
      background:#eee;
      border-radius:6px;
      font-size:12px;
      padding:4px 8px;
    }
    #miniCartBox .mini-body{
      padding:12px 16px;
      flex:1;
      min-height:80px;
      max-height:300px;
      overflow-y:auto;
    }
    .mini-item{
      display:flex;
      gap:10px;
      align-items:flex-start;
      border-bottom:1px solid #f3f3f3;
      padding-bottom:10px;
      margin-bottom:10px;
    }
    .mini-thumb img{
      width:44px;
      height:44px;
      object-fit:cover;
      border-radius:6px;
      border:1px solid #eee;
    }
    .mini-info{
      flex:1;
      min-width:0;
    }
    .mini-name{
      font-weight:500;
      line-height:1.3;
    }
    .mini-row1{
      font-size:13px;
      color:#444;
    }
    .mini-row2{
      font-size:13px;
      color:#666;
      margin-top:4px;
    }
    .qty-wrap{
      display:flex;
      align-items:center;
      gap:4px;
    }
    .qty-btn{
      border:1px solid #ccc;
      background:#fff;
      border-radius:4px;
      padding:0 6px;
      line-height:18px;
      font-size:12px;
    }
    .mini-side{
      text-align:right;
      min-width:70px;
    }
    .mini-lineprice{
      font-weight:600;
      font-size:13px;
    }
    .remove-item-btn{
      border:0;
      background:#ffefef;
      color:#d00000;
      border-radius:4px;
      font-size:12px;
      line-height:16px;
      padding:2px 6px;
      margin-top:4px;
    }
    #miniCartBox .mini-footer{
      border-top:1px solid #eee;
      padding:12px 16px;
    }
    .mini-totalline{
      display:flex;
      justify-content:space-between;
      font-size:14px;
      font-weight:600;
      margin-bottom:10px;
    }
    .mini-actions{
      display:flex;
      gap:8px;
    }
    .mini-clear-btn{
      flex:1;
      background:#fff;
      border:1px solid #dc3545;
      color:#dc3545;
      border-radius:6px;
      font-size:13px;
      padding:6px 8px;
    }
    .mini-pay-btn{
      flex:1;
      background:#000;
      border:0;
      color:#fff;
      border-radius:6px;
      font-size:13px;
      padding:6px 8px;
    }

    #miniToast{
      position:fixed;
      right:16px;
      bottom:16px;
      background:#198754;
      color:#fff;
      font-size:14px;
      padding:10px 14px;
      border-radius:6px;
      box-shadow:0 10px 30px rgba(0,0,0,.2);
      display:none;
      z-index:10000;
      font-weight:500;
    }
  </style>
  
</head>

<body>

<!-- TOPBAR -->
<div class="topbar">
  <div class="container d-flex justify-content-between">
    <div>MIỄN PHÍ GIAO HÀNG TRÊN TOÀN QUỐC</div>
    
    <div class="d-none d-lg-flex gap-4">
      <span>Hotline: <b>0789.888.666</b></span>

      <button id="logoutBtn" class="auth-link">
        Đăng xuất
      </button>

      <a href="#" class="auth-link">Tin tức</a>
      <a href="../php/track_order.php" class="auth-link">Tra cứu đơn hàng</a>
      <a href="#" class="auth-link">Hướng dẫn chọn size</a>
    </div>
  </div>
</div>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg border-bottom bg-white">
  <div class="container">
    <a class="navbar-brand logo d-flex align-items-center" href="#">
      <i class="bi bi-triangle-fill me-2"></i>THẾ GIỚI Giày Thể Thao Adodas
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav me-auto">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="categoryMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            DANH MỤC
          </a>
          <ul class="dropdown-menu" aria-labelledby="categoryMenu">
            <li><a class="dropdown-item" href="#">GIÀY THỂ THAO LÀM BẰNG DA</a></li>
            <li><a class="dropdown-item" href="#">GIÀY THỂ THAO LÀM BẰNG DA TỔNG HỢP</a></li>
            <li><a class="dropdown-item" href="#">GIÀY THỂ THAO LÀM BẰNG VẢI CAO CẤP</a></li>
            <li><a class="dropdown-item" href="#">HÀNG MỚI VỀ</a></li>
          </ul>
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
      </div>
    </div>
  </div>
</nav>

<!-- BREADCRUMB -->
<div class="container">
  <nav class="breadcrumb-wrap" aria-label="breadcrumb">
    <ol class="breadcrumb small mb-2">
      <li class="breadcrumb-item"><a href="#">Trang chủ</a></li>
      <li class="breadcrumb-item"><a href="#">Thể Thao</a></li>
      <li class="breadcrumb-item active">Giày Thể Thao</li>
    </ol>
  </nav>
</div>

<!-- HEADER + TAGLINE -->
<section class="container">
  <div class="d-flex flex-wrap align-items-end gap-3">
    <div>
      <h2 class="fw-bold mb-0">GIÀY THỂ THAO ADODAS</h2>
      <small class="text-muted">(Sản phẩm mới nhất)</small>
      <p class="small text-muted mt-1 mb-0">
        Giày Thể Thao chính hãng ✓ Giá tốt ✓ Đổi trả 15 ngày ✓ FREESHIP ✓ Ưu Đãi Online
      </p>
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
          <input class="form-check-input filter-category" type="checkbox" value="sneaker"> Sneaker
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

  </div>
</section>

<!-- PRODUCT GRID (render từ DB) -->
 
<section class="container py-3">
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
</section>

<!-- FOOTER -->
<footer class="py-4 border-top">
  <div class="container small text-center text-muted">© 2025 TheGioiGiay – Demo</div>
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

      row.querySelector('.minus').addEventListener('click', () => {
        if (cart[idx].qty > 1) {
          cart[idx].qty -= 1;
        } else {
          cart.splice(idx,1);
        }
        saveCart();
        renderCart();
      });

      row.querySelector('.plus').addEventListener('click', () => {
        cart[idx].qty += 1;
        saveCart();
        renderCart();
      });

      row.querySelector('.remove-item-btn').addEventListener('click', () => {
        cart.splice(idx,1);
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
  // Hàm bỏ dấu tiếng Việt để so khớp
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

  // Hiện / ẩn message "Không tìm thấy..."
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
      // Lấy tên sản phẩm từ data-name (ưu tiên) hoặc từ .card-title
      const rawName =
        card.getAttribute('data-name') ||
        (card.querySelector('.card-title')?.textContent || '');

      const match = kw === '' ? true : normalize(rawName).includes(kw);

      // Nếu không khớp keyword và keyword không rỗng → ẩn theo search
      if (!match && kw !== ''){
        card.setAttribute('data-hide-search','1');
      } else {
        // khớp hoặc ô tìm kiếm đang rỗng → bỏ cờ ẩn search
        card.removeAttribute('data-hide-search');
      }

      // Đếm số sp vẫn đang hiển thị:
      // chỉ tính nếu nó không bị ẩn bởi filter và cũng không bị ẩn bởi search
      if (!card.hasAttribute('data-hide-filter') &&
          !card.hasAttribute('data-hide-search')){
        visibleCount++;
      }
    });

    toggleNoResultSearch(visibleCount === 0);
  }

  // Gõ Enter là tìm
  inputSearch.addEventListener('keydown', e => {
    if (e.key === 'Enter'){
      runSearch();
    }
  });

  // Bấm icon kính lúp là tìm
  btnSearch.addEventListener('click', runSearch);

  // Gõ tới đâu lọc realtime tới đó
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

</body>
</html>
