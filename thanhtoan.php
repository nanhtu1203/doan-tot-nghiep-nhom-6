<?php
// thanhtoan.php

// CASE 1: MUA NGAY 1 SẢN PHẨM
// URL dạng: thanhtoan.php?buyNow=1&name=...&price=...&img=...
if (isset($_GET['buyNow']) && $_GET['buyNow'] == '1') {
    $name  = $_GET['name']  ?? 'Sản phẩm';
    $price = $_GET['price'] ?? 0;
    $img   = $_GET['img']   ?? '';
    ?>
    <!doctype html>
    <html lang="vi">
    <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title>Thanh toán</title>
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light">

    <div class="container py-4" style="max-width:800px">
      <h3 class="mb-3">Thanh toán nhanh</h3>

      <!-- Sản phẩm mua ngay -->
      <div class="card mb-3">
        <div class="card-body d-flex gap-3 align-items-start">
          <img src="<?php echo htmlspecialchars($img); ?>" style="width:80px;height:80px;object-fit:cover;border-radius:6px">
          <div class="flex-grow-1">
            <div class="fw-semibold"><?php echo htmlspecialchars($name); ?></div>
            <div class="text-muted small">Số lượng: 1</div>
            <div class="fw-bold text-danger fs-5">
              <?php echo number_format((int)$price, 0, ',', '.'); ?>₫
            </div>
          </div>
        </div>
      </div>

      <!-- Tổng tiền -->
      <div class="card mb-3">
        <div class="card-body d-flex justify-content-between">
          <div class="fw-semibold">Tổng thanh toán</div>
          <div class="fw-bold text-danger fs-5">
            <?php echo number_format((int)$price, 0, ',', '.'); ?>₫
          </div>
        </div>
      </div>

      <!-- Form thông tin nhận hàng -->
      <div class="card">
        <div class="card-header fw-semibold">Thông tin nhận hàng</div>
        <div class="card-body">
          <form id="buyNowForm">
            <!-- gửi lại sản phẩm -->
            <input type="hidden" name="name"  value="<?php echo htmlspecialchars($name); ?>">
            <input type="hidden" name="price" value="<?php echo htmlspecialchars($price); ?>">
            <input type="hidden" name="qty"   value="1">
            <input type="hidden" name="mode"  value="buyNow">

            <div class="mb-3">
              <label class="form-label">Họ tên người nhận</label>
              <input name="fullname" class="form-control" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Số điện thoại</label>
              <input name="phone" class="form-control" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Địa chỉ nhận hàng</label>
              <textarea name="address" class="form-control" rows="2" required></textarea>
            </div>

            <div class="mb-3">
              <label class="form-label">Phương thức thanh toán</label>
              <select name="payment_method" class="form-select" required>
                <option value="cod">Thanh toán khi nhận hàng (COD)</option>
                <option value="bank">Chuyển khoản</option>
              </select>
            </div>

            <div class="d-flex justify-content-between">
              <a href="trangchu.php" class="btn btn-outline-secondary">Quay lại mua thêm</a>
              <button type="submit" class="btn btn-dark">Xác nhận đặt hàng</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <script>
    // xử lý submit: nếu chọn chuyển khoản thì sang trang ngân hàng, ngược lại báo thành công và về trang chủ
    document.getElementById('buyNowForm').addEventListener('submit', function(e){
      e.preventDefault();

      const method = this.payment_method.value;

      if (method === 'bank') {
        // chuyển sang trang thanh toán ngân hàng
        window.location.href = 'bank.php';
      } else {
        alert('Đặt hàng thành công');
        window.location.href = 'trangchu.php';
      }
    });
    </script>

    </body>
    </html>
    <?php
    exit;
}

// CASE 2: THANH TOÁN GIỎ HÀNG
// Không có buyNow => checkout toàn bộ giỏ bằng JS đọc localStorage
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Thanh toán giỏ hàng</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4" style="max-width:900px">
  <h3 class="mb-3">Thanh toán giỏ hàng</h3>

  <!-- Danh sách sản phẩm trong giỏ -->
  <div class="card mb-3">
    <div class="card-body" id="cartList">
      <!-- JS sẽ render -->
    </div>
  </div>

  <!-- Tổng tiền -->
  <div class="card mb-3">
    <div class="card-body d-flex justify-content-between">
      <div class="fw-semibold">Tổng thanh toán</div>
      <div class="fw-bold text-danger fs-5" id="cartGrandTotal">0₫</div>
    </div>
  </div>

  <!-- Form thông tin nhận hàng -->
  <div class="card">
    <div class="card-header fw-semibold">Thông tin nhận hàng</div>
    <div class="card-body">
      <form id="cartCheckoutForm">
        <input type="hidden" name="mode" value="cart">
        <input type="hidden" name="items" id="cartItemsInput">

        <div class="mb-3">
          <label class="form-label">Họ tên người nhận</label>
          <input name="fullname" class="form-control" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Số điện thoại</label>
          <input name="phone" class="form-control" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Địa chỉ nhận hàng</label>
          <textarea name="address" class="form-control" rows="2" required></textarea>
        </div>

        <div class="mb-3">
          <label class="form-label">Phương thức thanh toán</label>
          <select name="payment_method" class="form-select" required>
            <option value="cod">Thanh toán khi nhận hàng (COD)</option>
            <option value="bank">Chuyển khoản</option>
          </select>
        </div>

        <div class="d-flex justify-content-between">
          <a href="trangchu.php" class="btn btn-outline-secondary">Quay lại giỏ hàng</a>
          <button type="submit" class="btn btn-dark">Xác nhận đặt hàng</button>
        </div>
      </form>
    </div>
  </div>

</div>

<script>
// format tiền
function nf(n){ return (n||0).toLocaleString('vi-VN') + '₫'; }

// đọc giỏ hàng từ localStorage
const cartData = JSON.parse(localStorage.getItem('cart') || '[]');
const wrap = document.getElementById('cartList');
const totalEl = document.getElementById('cartGrandTotal');
const hiddenInput = document.getElementById('cartItemsInput');

let total = 0;

if (cartData.length === 0) {
  wrap.innerHTML = '<div class="text-muted">Giỏ hàng đang trống.</div>';
} else {
  wrap.innerHTML = '';
  cartData.forEach(item => {
    const lineTotal = item.price * item.qty;
    total += lineTotal;

    const row = document.createElement('div');
    row.className = 'd-flex align-items-start border-bottom py-2 gap-3';
    row.innerHTML = `
      <img src="${item.img}" style="width:60px;height:60px;object-fit:cover;border-radius:6px">
      <div class="flex-grow-1">
        <div class="fw-semibold">${item.name}</div>
        <div class="text-muted small">SL: ${item.qty}</div>
        <div class="text-danger fw-bold">${nf(item.price)}</div>
      </div>
      <div class="text-end fw-bold">${nf(lineTotal)}</div>
    `;
    wrap.appendChild(row);
  });
}

totalEl.textContent = nf(total);

// Đưa toàn bộ giỏ vào input hidden dưới dạng JSON để sau này gửi server nếu cần
hiddenInput.value = JSON.stringify(cartData);

// handle submit thanh toán giỏ
document.getElementById('cartCheckoutForm').addEventListener('submit', function(e){
  e.preventDefault();

  const method = this.payment_method.value;

  if (method === 'bank') {
    // xóa giỏ hàng trước khi sang trang ngân hàng
    localStorage.removeItem('cart');
    // qua trang thanh toán ngân hàng
    window.location.href = 'bank.php';
  } else {
    alert('Đặt hàng thành công');
    localStorage.removeItem('cart');
    window.location.href = 'trangchu.php';
  }
});
</script>

</body>
</html>
