<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Giày Thể Thao</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/style.css">
</head>

<body>

<div class="topbar">
  <div class="container d-flex justify-content-between">
    <div>MIỄN PHÍ GIAO HÀNG TRÊN TOÀN QUỐC</div>
    
    <div class="d-none d-lg-flex gap-4">
      <span>Hotline: <b>0906.413.666</b></span>
        <a href="../php/trangchu.php?show_login=1" class="auth-link">ĐĂNG NHẬP</a>
        <a href="../php/register.php" class="auth-link">ĐĂNG KÍ</a>
        <a href="#" class="auth-link">Tin tức</a>
        <a href="#" class="auth-link">Tra cứu đơn hàng</a>
        <a href="#" class="auth-link">Hướng dẫn chọn size</a>

    </div>
  </div>
</div>
<!-- Navbar -->
<nav class="navbar navbar-expand-lg border-bottom bg-white">
  <div class="container">
    <a class="navbar-brand logo d-flex align-items-center" href="#">
      <i class="bi bi-triangle-fill me-2"></i>THẾ GIỚI GIẦY
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
        <a class="cart position-relative" href="#"><i class="bi bi-bag fs-4"></i><span class="badge bg-danger">2</span></a>
      </div>
    </div>
  </div>
</nav>

<!-- Breadcrumb -->
<div class="container">
  <nav class="breadcrumb-wrap" aria-label="breadcrumb">
    <ol class="breadcrumb small mb-2">
      <li class="breadcrumb-item"><a href="#">Trang chủ</a></li>
      <li class="breadcrumb-item"><a href="#">Thể Thao</a></li>
      <li class="breadcrumb-item active">Giày Thể Thao</li>
    </ol>
  </nav>
</div>

<!-- Header + tagline -->
<section class="container">
  <div class="d-flex flex-wrap align-items-end gap-3">
    <div>
      <h2 class="fw-bold mb-0">GIÀY THỂ THAO</h2>
      <small class="text-muted">(26 sản phẩm)</small>
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

  <!-- Filter buttons -->
  <div class="filterbar mt-3 d-flex flex-wrap gap-2">
    <!-- Loại giày -->
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
            <button class="btn btn-sm btn-outline-secondary filter-size" data-size="39">40</button>
            <button class="btn btn-sm btn-outline-secondary filter-size" data-size="39">41</button>
            <button class="btn btn-sm btn-outline-secondary filter-size" data-size="39">42</button>
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

</section>

<!-- Product grid -->
<!-- Product grid -->
<section class="container py-3">
  <!-- 1 -->
  <div class="row g-3">
    <div class="col-6 col-md-4 col-lg-3">
      <div
        class="card product-card position-relative"
        data-name="Giày vải nhung TGG 712 đỏ phối vạch" 
        data-category="the-thao"        
        data-gender="nam"     
        data-material="da"             
        data-color="đen"                 
        data-size="35,36,37,38,39,40,41,42"         
        data-pattern="trơn"             
        data-price="290000"            
      >
        <span class="badge-sale">31%</span>
        <img class="card-img-top" src="../images/da1.png" alt="">
        <div class="card-body">
          <div class="small text-muted">TheGioiGiay</div>
          <h6 class="card-title mb-1">Giày Thể Thao Da Nam TM-TA17</h6>
          <div class="d-flex align-items-baseline gap-2">
            <span class="price">290.000₫</span>
            <small class="old">390.000₫</small>
          </div>
        </div>
      </div>
    </div>
  <!-- 2 -->
    <div class="col-6 col-md-4 col-lg-3">
      <div
        class="card product-card position-relative"
        data-name="Giày vải nhung TGG 712 đỏ phối vạch" 
        data-category="the-thao"        
        data-gender="nam"     
        data-material="da"             
        data-color="đen"                 
        data-size="35,36,37,38,39,40,41,42"         
        data-pattern="trơn"             
        data-price="230000"            
      >
        <span class="badge-sale">31%</span>
        <img class="card-img-top" src="../images/da2.png" alt="">
        <div class="card-body">
          <div class="small text-muted">TheGioiGiay</div>
          <h6 class="card-title mb-1">Giày Thể Thao Da Nam BV122-36</h6>
          <div class="d-flex align-items-baseline gap-2">
            <span class="price">230.000₫</span>
            <small class="old">390.000₫</small>
          </div>
        </div>
      </div>
    </div>
  <!-- 3 -->
    <div class="col-6 col-md-4 col-lg-3">
      <div
        class="card product-card position-relative"
        data-name="Giày vải nhung TGG 712 đỏ phối vạch" 
        data-category="the-thao"        
        data-gender="nam"     
        data-material="da"             
        data-color="đen"                 
        data-size="35,36,37,38,39,40,41,42"         
        data-pattern="trơn"             
        data-price="370000"            
      >
        <span class="badge-sale">31%</span>
        <img class="card-img-top" src="../images/da3.png" alt="">
        <div class="card-body">
          <div class="small text-muted">TheGioiGiay</div>
          <h6 class="card-title mb-1">Giày Thể Thao Da Nam BN0112</h6>
          <div class="d-flex align-items-baseline gap-2">
            <span class="price">370.000₫</span>
            <small class="old">490.000₫</small>
          </div>
        </div>
      </div>
    </div>
  <!-- 4 -->
    <div class="col-6 col-md-4 col-lg-3">
      <div
        class="card product-card position-relative"
        data-name="Giày vải nhung TGG 712 đỏ phối vạch" 
        data-category="the-thao"        
        data-gender="nam"     
        data-material="da"             
        data-color="đen"                 
        data-size="35,36,37,38,39,40,41,42"         
        data-pattern="trơn"             
        data-price="570000"            
      >
        <span class="badge-sale">31%</span>
        <img class="card-img-top" src="../images/da4.png" alt="">
        <div class="card-body">
          <div class="small text-muted">TheGioiGiay</div>
          <h6 class="card-title mb-1">Giày Thể Thao Da Nam BN0068</h6>
          <div class="d-flex align-items-baseline gap-2">
            <span class="price">570.000₫</span>
            <small class="old">790.000₫</small>
          </div>
        </div>
      </div>
    </div>
  <!-- 5 -->
    <div class="col-6 col-md-4 col-lg-3">
      <div
        class="card product-card position-relative"
        data-name="Giày vải nhung TGG 712 đỏ phối vạch" 
        data-category="the-thao"        
        data-gender="nam"     
        data-material="da"             
        data-color="be"                 
        data-size="35,36,37,38,39,40,41,42"         
        data-pattern="trơn"             
        data-price="450000"            
      >
        <span class="badge-sale">31%</span>
        <img class="card-img-top" src="../images/da5.png" alt="">
        <div class="card-body">
          <div class="small text-muted">TheGioiGiay</div>
          <h6 class="card-title mb-1">GIÀY THỂ THAO DA DẬP LỖ BUỘC DÂY SIÊU ÊM GTT35763</h6>
          <div class="d-flex align-items-baseline gap-2">
            <span class="price">450.000₫</span>
            <small class="old">690.000₫</small>
          </div>
        </div>
      </div>
    </div>
  <!-- 6 -->
    <div class="col-6 col-md-4 col-lg-3">
      <div
        class="card product-card position-relative"
        data-name="Giày vải nhung TGG 712 đỏ phối vạch" 
        data-category="the-thao"        
        data-gender="Nữ"     
        data-material="da"             
        data-color="đen"                 
        data-size="35,36,37,38,39,40,41,42"         
        data-pattern="trơn"             
        data-price="350000"            
      >
        <span class="badge-sale">31%</span>
        <img class="card-img-top" src="../images/danu1.png" alt="">
        <div class="card-body">
          <div class="small text-muted">TheGioiGiay</div>
          <h6 class="card-title mb-1">Giày Thể Thao Nữ P67</h6>
          <div class="d-flex align-items-baseline gap-2">
            <span class="price">350.000₫</span>
            <small class="old">490.000₫</small>
          </div>
        </div>
      </div>
    </div>
  <!-- 7 -->
    <div class="col-6 col-md-4 col-lg-3">
      <div
        class="card product-card position-relative"
        data-name="Giày vải nhung TGG 712 đỏ phối vạch" 
        data-category="the-thao"        
        data-gender="Nữ"     
        data-material="da"             
        data-color="đen"                 
        data-size="35,36,37,38,39,40,41,42"         
        data-pattern="trơn"             
        data-price="250000"            
      >
        <span class="badge-sale">31%</span>
        <img class="card-img-top" src="../images/danu2.png" alt="">
        <div class="card-body">
          <div class="small text-muted">TheGioiGiay</div>
          <h6 class="card-title mb-1">Giày Thể Thao Nữ TM-SZ132</h6>
          <div class="d-flex align-items-baseline gap-2">
            <span class="price">250.000₫</span>
            <small class="old">390.000₫</small>
          </div>
        </div>
      </div>
    </div>
  <!-- 8 -->
    <div class="col-6 col-md-4 col-lg-3">
      <div
        class="card product-card position-relative"
        data-name="Giày vải nhung TGG 712 đỏ phối vạch" 
        data-category="the-thao"        
        data-gender="Nữ"     
        data-material="da"             
        data-color="đen"                 
        data-size="35,36,37,38,39,40,41,42"         
        data-pattern="trơn"             
        data-price="470000"            
      >
        <span class="badge-sale">31%</span>
        <img class="card-img-top" src="../images/danu3.png" alt="">
        <div class="card-body">
          <div class="small text-muted">TheGioiGiay</div>
          <h6 class="card-title mb-1">Giày thể thao nữ mẫu mới 2022 da pu cao cấp tăng chiều cao 4cm BM005</h6>
          <div class="d-flex align-items-baseline gap-2">
            <span class="price">470.000₫</span>
            <small class="old">690.000₫</small>
          </div>
        </div>
      </div>
    </div>

  </div>
</section>


<footer class="py-4 border-top">
  <div class="container small text-center text-muted">© 2025 TheGioiGiay – Demo</div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Search script -->
<script>
document.addEventListener('DOMContentLoaded', () => {
  // Bỏ dấu để so khớp tiếng Việt
  const deAccent = s => (s || '')
    .toLowerCase()
    .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
    .replace(/đ/g,'d');

  const box   = document.getElementById('searchBox');
  const btn   = document.getElementById('searchBtn');
  const cards = Array.from(document.querySelectorAll('.product-card'));

  function filterProducts() {
    const q = deAccent(box.value.trim());
    let shown = 0;

    cards.forEach(card => {
      const name = card.dataset.name ||
                   (card.querySelector('.card-title')?.textContent || '');
      const match = deAccent(name).includes(q);
      card.style.display = match ? '' : 'none';
      if (match) shown++;
    });

    toggleNoResult(shown === 0);
  }

  function toggleNoResult(show) {
    let el = document.getElementById('noResult');
    if (show) {
      if (!el) {
        el = document.createElement('div');
        el.id = 'noResult';
        el.className = 'alert alert-light border text-center mt-3';
        el.textContent = 'Không tìm thấy sản phẩm phù hợp.';
        document.querySelector('section.container.py-3')?.appendChild(el);
      }
    } else if (el) el.remove();
  }

  box.addEventListener('input', filterProducts);
  box.addEventListener('keydown', e => { if (e.key === 'Enter') filterProducts(); });
  btn.addEventListener('click', filterProducts);
});
</script>

<!-- Cart script: thêm mới, không sửa HTML gốc -->
<script>
document.addEventListener('DOMContentLoaded', () => {
  // --- Helpers ---
  const $$ = (sel, root=document) => root.querySelector(sel);
  const $$$ = (sel, root=document) => Array.from(root.querySelectorAll(sel));
  const parsePrice = (s) => Number((s||'').replace(/[^\d]/g,'') || 0);
  const toVND = n => (n||0).toLocaleString('vi-VN') + '₫';

  // --- Elements from existing HTML (không đổi cấu trúc cũ) ---
  const cartIcon = $$('.cart');
  const badgeEl  = $$('.cart .badge');

  // --- State ---
  let cart = JSON.parse(localStorage.getItem('cart') || '[]');

  // --- UI: inject mini cart container (không chạm HTML gốc) ---
  const mini = document.createElement('div');
  mini.id = 'miniCart';
  mini.className = 'position-fixed bottom-0 end-0 bg-white border shadow rounded';
  mini.style.cssText = 'width:320px;display:none;z-index:1050;';
  mini.innerHTML = `
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
      <h6 class="m-0 fw-bold">🛒 Giỏ hàng</h6>
      <button class="btn btn-sm btn-outline-secondary" id="closeMini">Đóng</button>
    </div>
    <div class="p-3">
      <ul id="cartItems" class="list-unstyled m-0"></ul>
      <hr>
      <div class="d-flex justify-content-between">
        <strong>Tổng cộng</strong><strong id="cartTotal">0₫</strong>
      </div>
      <div class="d-flex gap-2 mt-3">
        <button id="clearCart" class="btn btn-outline-danger btn-sm w-50">Xóa tất cả</button>
        <button id="checkout" class="btn btn-dark btn-sm w-50">Thanh toán</button>
      </div>
    </div>
  `;
  document.body.appendChild(mini);

  const listEl   = $$('#cartItems', mini);
  const totalEl  = $$('#cartTotal', mini);

  // --- Add "Thêm vào giỏ" buttons dynamically (không sửa HTML gốc) ---
  $$$('.product-card').forEach(card => {
    const body = card.querySelector('.card-body') || card;
    if (!body.querySelector('.addToCart')) {
      const btn = document.createElement('button');
      btn.className = 'btn btn-sm btn-dark mt-2 addToCart';
      btn.textContent = 'Thêm vào giỏ';
      body.appendChild(btn);

      btn.addEventListener('click', (e) => {
        e.stopPropagation();
        const name  = card.dataset.name || (card.querySelector('.card-title')?.textContent?.trim() || 'Sản phẩm');
        const price = parsePrice(card.querySelector('.price')?.textContent);
        const img   = card.querySelector('img')?.src || '';
        addToCart({ name, price, img });
        toast('Đã thêm vào giỏ hàng!');
      });
    }
  });

  // --- Actions ---
  function addToCart(item){
    const found = cart.find(p => p.name === item.name);
    if (found) found.qty += 1;
    else cart.push({ ...item, qty: 1 });
    persist();
    render();
  }
  function removeAt(i){
    cart.splice(i,1);
    persist();
    render();
  }
  function changeQty(i, delta){
    cart[i].qty = Math.max(1, cart[i].qty + delta);
    persist();
    render();
  }
  function clearCart(){
    cart = [];
    persist();
    render();
  }
  function persist(){ localStorage.setItem('cart', JSON.stringify(cart)); }

  // --- Render ---
  function render(){
    // list
    listEl.innerHTML = '';
    let total = 0, count = 0;
    cart.forEach((p, i) => {
      total += p.price * p.qty;
      count += p.qty;
      const li = document.createElement('li');
      li.className = 'd-flex align-items-center justify-content-between mb-3';
      li.innerHTML = `
        <div class="d-flex align-items-center gap-2">
          <img src="${p.img}" width="44" height="44" style="object-fit:cover;border-radius:6px">
          <div>
            <div class="fw-medium">${p.name}</div>
            <div class="text-muted small">${toVND(p.price)} · SL: 
              <button class="btn btn-sm btn-outline-secondary px-2 py-0 qty-minus">-</button>
              <span class="mx-1">${p.qty}</span>
              <button class="btn btn-sm btn-outline-secondary px-2 py-0 qty-plus">+</button>
            </div>
          </div>
        </div>
        <div class="text-end">
          <div class="fw-semibold">${toVND(p.price * p.qty)}</div>
          <button class="btn btn-sm btn-outline-danger mt-1 remove-item"><i class="bi bi-x"></i></button>
        </div>
      `;
      listEl.appendChild(li);

      li.querySelector('.remove-item').addEventListener('click', () => removeAt(i));
      li.querySelector('.qty-minus').addEventListener('click', () => changeQty(i, -1));
      li.querySelector('.qty-plus').addEventListener('click', () => changeQty(i, +1));
    });

    totalEl.textContent = toVND(total);
    // badge
    if (badgeEl) badgeEl.textContent = String(count);
  }

  // --- Mini cart toggle ---
  cartIcon?.addEventListener('click', (e) => {
    e.preventDefault();
    mini.style.display = (mini.style.display === 'none' || !mini.style.display) ? 'block' : 'none';
  });
  $$('#closeMini')?.addEventListener('click', () => mini.style.display = 'none');
  $$('#clearCart')?.addEventListener('click', clearCart);
  $$('#checkout')?.addEventListener('click', () => toast('Demo: Chuyển sang trang thanh toán!'));

  // --- Toast helper (Bootstrap) ---
  function toast(msg){
    const t = document.createElement('div');
    t.className = 'toast align-items-center text-bg-success position-fixed bottom-0 end-0 m-3';
    t.setAttribute('role','alert');
    t.innerHTML = `
      <div class="d-flex">
        <div class="toast-body">${msg}</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>`;
    document.body.appendChild(t);
    new bootstrap.Toast(t).show();
  }

  // --- Init ---
  render();
});
</script>
<script>
document.addEventListener('DOMContentLoaded', () => {

  // lấy danh sách sản phẩm
  const products = Array.from(document.querySelectorAll('.product-card'));

  // lấy input bộ lọc
  const catChecks     = Array.from(document.querySelectorAll('.filter-category'));
  const genderChecks  = Array.from(document.querySelectorAll('.filter-gender'));
  const matChecks     = Array.from(document.querySelectorAll('.filter-material'));
  const colorChecks   = Array.from(document.querySelectorAll('.filter-color'));
  const patternChecks = Array.from(document.querySelectorAll('.filter-pattern'));
  const sizeBtns      = Array.from(document.querySelectorAll('.filter-size'));

  const priceMinInput = document.getElementById('priceMin');
  const priceMaxInput = document.getElementById('priceMax');
  const applyPriceBtn = document.getElementById('applyPrice');

  // state size chọn (vì size là button chứ ko phải checkbox)
  let activeSizes = [];

  sizeBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      const val = btn.getAttribute('data-size');
      if (activeSizes.includes(val)) {
        // bỏ chọn
        activeSizes = activeSizes.filter(v => v !== val);
        btn.classList.remove('btn-dark');
        btn.classList.add('btn-outline-secondary');
      } else {
        // chọn
        activeSizes.push(val);
        btn.classList.remove('btn-outline-secondary');
        btn.classList.add('btn-dark');
      }
      applyFilter();
    });
  });

  // khi tick các checkbox khác thì lọc lại
  [...catChecks, ...genderChecks, ...matChecks, ...colorChecks, ...patternChecks].forEach(input => {
    input.addEventListener('change', applyFilter);
  });

  // khi bấm áp dụng giá
  applyPriceBtn?.addEventListener('click', () => {
    applyFilter();
  });

  function getCheckedValues(nodeList) {
    return nodeList
      .filter(i => i.checked)
      .map(i => i.value.toLowerCase().trim());
  }

  function applyFilter() {
    const selectedCats     = getCheckedValues(catChecks);       // mảng string
    const selectedGender   = getCheckedValues(genderChecks);
    const selectedMat      = getCheckedValues(matChecks);
    const selectedColor    = getCheckedValues(colorChecks);
    const selectedPattern  = getCheckedValues(patternChecks);
    const selectedSizes    = activeSizes.slice();               // copy

    const minPrice = priceMinInput?.value ? parseInt(priceMinInput.value,10) : null;
    const maxPrice = priceMaxInput?.value ? parseInt(priceMaxInput.value,10) : null;

    let visibleCount = 0;

    products.forEach(card => {
      // đọc data từ card
      const cat     = (card.dataset.category  || '').toLowerCase();
      const gender  = (card.dataset.gender    || '').toLowerCase();   // có thể chứa nhiều, ví dụ "nam,nữ"
      const mat     = (card.dataset.material  || '').toLowerCase();
      const color   = (card.dataset.color     || '').toLowerCase();
      const pattern = (card.dataset.pattern   || '').toLowerCase();
      const sizes   = (card.dataset.size      || '').toLowerCase();   // "35,36,37"
      const price   = card.dataset.price ? parseInt(card.dataset.price,10) : null;

      // từng điều kiện
      // 1. loại giày
      if (selectedCats.length > 0 && !selectedCats.includes(cat)) {
        card.style.display = 'none'; return;
      }

      // 2. giới tính
      if (selectedGender.length > 0) {
        // phải giao nhau: vd gender="nam,nữ,unisex"
        const okGender = selectedGender.some(g => gender.includes(g));
        if (!okGender) {
          card.style.display = 'none'; return;
        }
      }

      // 3. chất liệu
      if (selectedMat.length > 0 && !selectedMat.includes(mat)) {
        card.style.display = 'none'; return;
      }

      // 4. màu sắc
      if (selectedColor.length > 0 && !selectedColor.includes(color)) {
        card.style.display = 'none'; return;
      }

      // 5. họa tiết
      if (selectedPattern.length > 0 && !selectedPattern.includes(pattern)) {
        card.style.display = 'none'; return;
      }

      // 6. size
      if (selectedSizes.length > 0) {
        // card phải có ít nhất một size trùng
        const okSize = selectedSizes.some(sz => sizes.split(',').map(s=>s.trim()).includes(sz));
        if (!okSize) {
          card.style.display = 'none'; return;
        }
      }

      // 7. khoảng giá
      if (minPrice !== null && (price === null || price < minPrice)) {
        card.style.display = 'none'; return;
      }
      if (maxPrice !== null && (price === null || price > maxPrice)) {
        card.style.display = 'none'; return;
      }

      // nếu qua hết điều kiện
      card.style.display = '';
      visibleCount++;
    });

    // optional: hiển thị "không tìm thấy"
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

  // chạy 1 lần đầu để đồng bộ giao diện
  applyFilter();
});
</script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const sortSelect = document.getElementById('sortSelect');
  const gridRow = document.querySelector('section.container.py-3 .row.g-3');

  function getCards() {
    return Array.from(gridRow.querySelectorAll('.col-6.col-md-4.col-lg-3'));
  }

  function getPriceNum(card) {
    const p = card.querySelector('.product-card')?.dataset.price || '';
    return parseInt(p, 10) || 0;
  }

  function applySort(mode) {
    const cards = getCards();

    if (mode === 'price-asc') {
      cards.sort((a, b) => getPriceNum(a) - getPriceNum(b));
    } else if (mode === 'price-desc') {
      cards.sort((a, b) => getPriceNum(b) - getPriceNum(a));
    } else {
      return;
    }

    cards.forEach(card => gridRow.appendChild(card));
  }

  sortSelect.addEventListener('change', () => {
    applySort(sortSelect.value);
  });
});
</script>

</body>
</html>
