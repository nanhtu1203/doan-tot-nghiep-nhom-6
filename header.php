<?php
// Đảm bảo session đã start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load categories nếu chưa có (cho navbar)
if (!isset($categories)) {
    if (!isset($conn)) {
        require 'connect.php';
    }
    $stmtCat = $conn->query("SELECT id, name, slug FROM categories ORDER BY sort_order, name");
    $categories = $stmtCat->fetchAll(PDO::FETCH_ASSOC);
}

// Lấy page title nếu có, mặc định là trang chủ
$pageTitle = isset($pageTitle) ? $pageTitle : 'Adodas – Adodas, Quần Áo, Phụ Kiện Thời Trang chính hãng';
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle) ?></title>

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
        
        <!-- MÃ KHUYẾN MÃI -->
        <li class="nav-item">
          <a class="nav-link text-primary fw-bold" href="coupons.php">
            <i class="bi bi-ticket-perforated"></i> Mã khuyến mãi
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
          <span class="badge bg-danger">0</span>
        </a>
        <div class="user-menu-container position-relative">

            <div id="userIcon" class="icon-wrap" style="cursor:pointer;">
                <svg class="icon" viewBox="0 0 24 24">
                    <circle cx="12" cy="7" r="4"></circle>
                    <path d="M4 20c0-4 4-7 8-7s8 3 8 7"></path>
                </svg>
            </div>

            <div id="userDropdown" class="user-dropdown">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- Đã đăng nhập: Hiển thị menu đầy đủ -->
                    <a href="profile.php">Thông tin tài khoản</a>
                    <a href="history.php">Lịch sử mua hàng</a>
                    <a href="track_order.php">Tra cứu đơn hàng</a>
                    <a href="address.php">Thay đổi địa chỉ</a>
                    <a href="#" id="logoutFromMenu">Đăng xuất</a>
                <?php else: ?>
                    <!-- Chưa đăng nhập: Hiển thị đăng nhập/đăng ký -->
                    <a href="#" data-bs-toggle="modal" data-bs-target="#loginModal">Đăng nhập</a>
                    <a href="#" data-bs-toggle="modal" data-bs-target="#registerModal">Đăng ký</a>
                <?php endif; ?>
            </div>

        </div>


      </div>
    </div>
  </div>
</nav>

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

  // Kiểm tra đăng nhập (từ PHP session)
  const isLoggedIn = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;

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
  
  // Hàm kiểm tra đăng nhập và redirect nếu chưa đăng nhập
  function checkLoginAndRedirect() {
    if (!isLoggedIn) {
      window.location.href = 'trangchu.php?show_login=1';
      return false;
    }
    return true;
  }

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

  function addProductToCart(name, price, img, productId) {
    const found = cart.find(p => p.id === productId || (p.id === undefined && p.name === name));
    if (found){
      found.qty += 1;
    } else {
      cart.push({ id: productId || 0, name, price, img, qty: 1 });
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
      if (!checkLoginAndRedirect()) return;
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

  // Chỉ thêm nút cho product-card nếu có
  $$('.product-card').forEach(card => {
    const body  = card.querySelector('.card-body') || card;
    const name  = card.querySelector('.card-title')?.textContent?.trim() || 'Sản phẩm';
    const img   = card.querySelector('img')?.getAttribute('src') || '';
    const priceText = card.querySelector('.price')?.textContent || '';
    const price = parsePrice(priceText);
    const productId = card.getAttribute('data-id') || '0';

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
        if (!checkLoginAndRedirect()) return;
        addProductToCart(name, price, img, productId);
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
        if (!checkLoginAndRedirect()) return;
        const url = 'thanhtoan.php'
          + '?buyNow=1'
          + '&id='    + encode(productId)
          + '&name='  + encode(name)
          + '&price=' + encode(price)
          + '&img='   + encode(img);

        window.location.href = url;
      });

      btnRow.appendChild(buyBtn);
    }
  });

  renderCart();
  
  // Export để các trang khác có thể dùng
  window.addProductToCart = addProductToCart;
  window.showToast = showToast;
})();
</script>

<!-- SCRIPT: SEARCH -->
<script>
(function(){
  const inputSearch = document.getElementById('searchBox');
  const btnSearch = document.getElementById('searchBtn');
  const productCards = Array.from(document.querySelectorAll('.product-card'));
  const gridSection = document.querySelector('section.container.py-3');

  if (inputSearch && btnSearch) {
    // Hàm redirect đến trang search
    function redirectToSearch() {
      const keyword = inputSearch.value.trim();
      if (keyword) {
        window.location.href = 'search.php?q=' + encodeURIComponent(keyword);
      }
    }

    // Hàm filter real-time trên trang hiện tại (chỉ khi có product cards)
    function normalize(str){
      return (str || '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g,'')
        .replace(/đ/g,'d');
    }

    function filterProducts() {
      if (productCards.length === 0) return; // Không có sản phẩm thì không filter
      
      const kwRaw = inputSearch.value.trim();
      const kw = normalize(kwRaw);
      let visibleCount = 0;

      productCards.forEach(card => {
        const rawName =
          card.getAttribute('data-name') ||
          (card.querySelector('.card-title')?.textContent || '');

        const match = kw === '' ? true : normalize(rawName).includes(kw);

        if (!match && kw !== ''){
          card.setAttribute('data-hide-search','1');
          card.style.display = 'none';
        } else {
          card.removeAttribute('data-hide-search');
          card.style.display = '';
        }

        if (!card.hasAttribute('data-hide-filter') &&
            !card.hasAttribute('data-hide-search')){
          visibleCount++;
        }
      });

      // Hiển thị thông báo nếu không có kết quả
      toggleNoResultSearch(visibleCount === 0 && kw !== '');
    }

    function toggleNoResultSearch(isEmpty){
      let msgEl = document.getElementById('noResultSearch');
      if (isEmpty){
        if (!msgEl && gridSection){
          msgEl = document.createElement('div');
          msgEl.id = 'noResultSearch';
          msgEl.className = 'alert alert-light border text-center mt-3';
          msgEl.textContent = 'Không tìm thấy sản phẩm phù hợp.';
          gridSection.appendChild(msgEl);
        }
      } else if (msgEl){
        msgEl.remove();
      }
    }

    // Khi nhấn Enter hoặc click nút search -> redirect đến trang search
    inputSearch.addEventListener('keydown', e => {
      if (e.key === 'Enter'){
        e.preventDefault();
        redirectToSearch();
      }
    });

    btnSearch.addEventListener('click', function(e) {
      e.preventDefault();
      redirectToSearch();
    });

    // Filter real-time khi đang gõ (chỉ trên trang có product cards)
    if (productCards.length > 0) {
      inputSearch.addEventListener('input', filterProducts);
    }
  }
})();
</script>

<!-- SCRIPT: USER MENU -->
<script>
// Toggle menu
const userIcon = document.getElementById("userIcon");
const userDropdown = document.getElementById("userDropdown");

if (userIcon && userDropdown) {
    userIcon.onclick = function(e) {
        e.stopPropagation();
        userDropdown.style.display = userDropdown.style.display === "block" ? "none" : "block";
    };

    // Đóng menu khi click vào các link bên trong
    userDropdown.addEventListener("click", function(e) {
        if (e.target.tagName === "A") {
            userDropdown.style.display = "none";
        }
    });

    // Ấn ra ngoài để đóng menu
    document.addEventListener("click", function(e) {
        if (!userIcon.contains(e.target) && !userDropdown.contains(e.target)) {
            userDropdown.style.display = "none";
        }
    });
}

// Đăng xuất trong menu (chỉ khi đã đăng nhập)
const logoutBtn = document.getElementById("logoutFromMenu");
if (logoutBtn) {
    logoutBtn.onclick = function(e){
        e.preventDefault();
        fetch("logout.php")
            .then(() => window.location.reload());
    };
}
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
            <a href="#" class="small text-decoration-none" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal">Quên mật khẩu?</a>
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

<!-- MODAL QUÊN MẬT KHẨU -->
<div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-bottom">
        <h5 class="modal-title fw-bold" id="forgotPasswordModalLabel">Quên mật khẩu</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <div id="forgotPasswordError" class="alert alert-danger d-none"></div>
        <div id="forgotPasswordSuccess" class="alert alert-success d-none"></div>
        <form id="forgotPasswordForm">
          <div class="mb-3">
            <label class="form-label">Nhập email đã đăng ký <span class="text-danger">*</span></label>
            <input type="email" name="email" class="form-control" required placeholder="example@gmail.com">
            <small class="text-muted">Mã OTP sẽ được gửi đến email của bạn.</small>
          </div>
          <button type="submit" class="btn btn-dark w-100 mb-3">Gửi mã OTP</button>
          <div class="text-center mt-3">
            <span class="small">Nhớ mật khẩu?</span>
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
      
      // Redirect theo role
      if (result.redirect) {
        window.location.href = result.redirect;
      } else {
        // Reload trang để cập nhật session
        window.location.reload();
      }
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

// Xử lý quên mật khẩu
document.getElementById('forgotPasswordForm')?.addEventListener('submit', async function(e) {
  e.preventDefault();
  
  const formData = new FormData(this);
  formData.append('action', 'forgot_password');
  
  const errorDiv = document.getElementById('forgotPasswordError');
  const successDiv = document.getElementById('forgotPasswordSuccess');
  errorDiv.classList.add('d-none');
  successDiv.classList.add('d-none');
  
  try {
    const response = await fetch('auth_ajax.php', {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
      // Thành công
      if (result.otp) {
        // Hiển thị OTP trên màn hình (development mode)
        successDiv.innerHTML = `
          <h6>Mã OTP đã được tạo!</h6>
          <p class="mb-2">Mã OTP của bạn (Development Mode):</p>
          <h3 class="text-danger fw-bold">${result.otp}</h3>
          <p class="small mb-2">Mã có hiệu lực trong 10 phút.</p>
          <a href="reset_password.php" class="btn btn-dark btn-sm">Tiếp tục đặt lại mật khẩu</a>
        `;
      } else {
        successDiv.innerHTML = result.message;
      }
      successDiv.classList.remove('d-none');
      
      // Reset form
      this.reset();
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

// Reset form khi đóng modal quên mật khẩu
document.getElementById('forgotPasswordModal')?.addEventListener('hidden.bs.modal', function() {
  document.getElementById('forgotPasswordForm')?.reset();
  document.getElementById('forgotPasswordError').classList.add('d-none');
  document.getElementById('forgotPasswordSuccess').classList.add('d-none');
});

// Tự động mở modal đăng nhập nếu có tham số show_login=1 trong URL
(function() {
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get('show_login') === '1') {
    // Đợi DOM load xong và Bootstrap sẵn sàng
    document.addEventListener('DOMContentLoaded', function() {
      setTimeout(function() {
        const loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
        loginModal.show();
        // Xóa tham số khỏi URL để không mở lại khi refresh
        const newUrl = window.location.pathname + window.location.search.replace(/[?&]show_login=1/, '').replace(/^\?/, '?').replace(/^$/, '');
        window.history.replaceState({}, '', newUrl || window.location.pathname);
      }, 100);
    });
  }
})();
</script>

