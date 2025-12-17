<?php
// thanhtoan.php
session_start();
require 'connect.php';

// Bắt buộc đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: trangchu.php?show_login=1");
    exit;
}

$pageTitle = 'Thanh toán';

// ================== XỬ LÝ LƯU ĐƠN HÀNG (POST) ==================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode      = $_POST['mode'] ?? 'cart';          // buyNow | cart
    $fullname  = trim($_POST['fullname'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $address   = trim($_POST['address'] ?? '');
    $payment   = $_POST['payment_method'] ?? 'cod';
    $userId    = $_SESSION['user_id'] ?? null;

    // Validation
    if (empty($fullname) || empty($phone) || empty($address)) {
        $_SESSION['checkout_error'] = 'Vui lòng điền đầy đủ thông tin nhận hàng.';
        header('Location: thanhtoan.php' . ($mode === 'buyNow' ? '?buyNow=1&' . http_build_query($_GET) : ''));
        exit;
    }

    // Sinh mã đơn
    $orderCode = 'HD' . strtoupper(substr(md5(uniqid('', true)), 0, 6));

    // Lấy danh sách item
    $items = [];

    if ($mode === 'buyNow') {
        // Mua ngay 1 sản phẩm
        $productId = (int)($_POST['product_id'] ?? 0);
        $name  = $_POST['name']  ?? 'Sản phẩm';
        $price = (int)($_POST['price'] ?? 0);
        $qty   = (int)($_POST['qty'] ?? 1);

        $items[] = [
            'product_id' => $productId,
            'name'  => $name,
            'price' => $price,
            'qty'   => $qty
        ];
    } else {
        // Thanh toán giỏ hàng: items là JSON từ localStorage
        $json = $_POST['items'] ?? '[]';
        $decoded = json_decode($json, true);
        if (is_array($decoded)) {
            foreach ($decoded as $row) {
                $items[] = [
                    'product_id' => (int)($row['id'] ?? 0),
                    'name'  => $row['name']  ?? 'Sản phẩm',
                    'price' => (int)($row['price'] ?? 0),
                    'qty'   => (int)($row['qty'] ?? 1)
                ];
            }
        }
    }

    // Tính tổng tiền
    $totalAmount = 0;
    foreach ($items as $it) {
        $totalAmount += $it['price'] * $it['qty'];
    }

    if ($totalAmount <= 0 || empty($items)) {
        $_SESSION['checkout_error'] = 'Giỏ hàng đang trống.';
        header('Location: thanhtoan.php');
        exit;
    }

    // Xử lý mã khuyến mãi
    $couponId = null;
    $discountAmount = 0;
    $couponCode = trim($_POST['coupon_code'] ?? '');
    
    if (!empty($couponCode)) {
        $now = date('Y-m-d H:i:s');
        $stmtCoupon = $conn->prepare("
            SELECT * FROM coupons 
            WHERE code = ? AND is_active = 1 
            AND (start_date IS NULL OR start_date <= ?)
            AND (end_date IS NULL OR end_date >= ?)
        ");
        $stmtCoupon->execute([$couponCode, $now, $now]);
        $coupon = $stmtCoupon->fetch(PDO::FETCH_ASSOC);
        
        if ($coupon) {
            // Kiểm tra số lượt sử dụng
            if (!$coupon['usage_limit'] || $coupon['used_count'] < $coupon['usage_limit']) {
                // Kiểm tra đơn tối thiểu
                if ($coupon['min_order'] <= $totalAmount) {
                    // Tính giảm giá
                    if ($coupon['type'] === 'percent') {
                        $discountAmount = ($totalAmount * $coupon['value']) / 100;
                        if ($coupon['max_discount'] && $discountAmount > $coupon['max_discount']) {
                            $discountAmount = $coupon['max_discount'];
                        }
                    } else {
                        $discountAmount = $coupon['value'];
                    }
                    
                    // Đảm bảo không giảm quá tổng tiền
                    if ($discountAmount > $totalAmount) {
                        $discountAmount = $totalAmount;
                    }
                    
                    $couponId = $coupon['id'];
                }
            }
        }
    }
    
    $finalAmount = max(0, $totalAmount - $discountAmount);

    // Lưu vào bảng orders
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
        $fullname,
        $phone,
        $address,
        $finalAmount
    ]);
    $orderId = $conn->lastInsertId();

    // Lưu chi tiết sản phẩm vào order_items
    $stmtItem = $conn->prepare("
        INSERT INTO order_items (order_id, product_id, quantity, price)
        VALUES (?, ?, ?, ?)
    ");
    foreach ($items as $it) {
        $stmtItem->execute([
            $orderId,
            $it['product_id'],
            $it['qty'],
            $it['price']
        ]);
    }

    // Lưu mã khuyến mãi nếu có
    if ($couponId && $discountAmount > 0) {
        $stmtCouponOrder = $conn->prepare("
            INSERT INTO order_coupons (order_id, coupon_id, discount_amount)
            VALUES (?, ?, ?)
        ");
        $stmtCouponOrder->execute([$orderId, $couponId, $discountAmount]);
        
        // Tăng số lượt sử dụng
        $stmtUpdateCoupon = $conn->prepare("
            UPDATE coupons SET used_count = used_count + 1 WHERE id = ?
        ");
        $stmtUpdateCoupon->execute([$couponId]);
    }

    // Xóa giỏ hàng trong localStorage (sẽ được xử lý bằng JavaScript)
    $_SESSION['order_success'] = true;
    $_SESSION['order_code'] = $orderCode;
    $_SESSION['order_total'] = $finalAmount;

    // Sau khi lưu xong, điều hướng theo phương thức thanh toán
    if ($payment === 'bank') {
        // Chuyển sang trang ngân hàng kèm theo mã đơn
        header('Location: bank.php?order_code=' . urlencode($orderCode));
    } else {
        // Thanh toán COD: về trang thành công
        header('Location: order_success.php?order_code=' . urlencode($orderCode));
    }
    exit;
}

// ================== GIAO DIỆN ==================


// CASE 1: MUA NGAY 1 SẢN PHẨM
// URL: thanhtoan.php?buyNow=1&id=...&name=...&price=...&img=...
if (isset($_GET['buyNow']) && $_GET['buyNow'] == '1') {
    $productId = (int)($_GET['id'] ?? 0);
    $name  = $_GET['name']  ?? 'Sản phẩm';
    $price = $_GET['price'] ?? 0;
    $img   = $_GET['img']   ?? '';
    
    // Lấy thông tin user nếu đã đăng nhập
    $userFullname = $_SESSION['fullname'] ?? '';
    $userEmail = $_SESSION['email'] ?? '';
    
    // Lấy địa chỉ mặc định nếu có
    $defaultAddress = '';
    if (isset($_SESSION['user_id'])) {
        $stmtAddr = $conn->prepare("SELECT address FROM addresses WHERE user_id = ? AND is_default = 1 LIMIT 1");
        $stmtAddr->execute([$_SESSION['user_id']]);
        $addrRow = $stmtAddr->fetch(PDO::FETCH_ASSOC);
        if ($addrRow) {
            $defaultAddress = $addrRow['address'];
        }
    }
    
    $errorMsg = $_SESSION['checkout_error'] ?? '';
    unset($_SESSION['checkout_error']);
    
    include 'header.php';
    ?>

    <div class="container py-4" style="max-width:800px">
      <h3 class="mb-3">Thanh toán nhanh</h3>

      <?php if ($errorMsg): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($errorMsg) ?></div>
      <?php endif; ?>

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

      <!-- Mã khuyến mãi -->
      <div class="card mb-3">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="form-label mb-0 fw-semibold">Mã khuyến mãi</label>
            <a href="coupons.php" class="small text-decoration-none">Xem tất cả mã</a>
          </div>
          <div class="input-group">
            <input type="text" id="buyNowCouponCode" class="form-control" placeholder="Nhập mã khuyến mãi">
            <button type="button" class="btn btn-outline-primary" id="buyNowApplyCoupon">Áp dụng</button>
          </div>
          <div id="buyNowCouponMessage" class="mt-2 small"></div>
          <div id="buyNowCouponInfo" class="mt-2" style="display:none;">
            <div class="d-flex justify-content-between align-items-center">
              <span class="text-success">
                <i class="bi bi-check-circle"></i> Đã áp dụng mã: <strong id="buyNowCouponCodeText"></strong>
              </span>
              <button type="button" class="btn btn-sm btn-link text-danger p-0" id="buyNowRemoveCoupon">Xóa</button>
            </div>
            <div class="text-success small mt-1">
              Giảm: <strong id="buyNowDiscountAmount">0₫</strong>
            </div>
          </div>
          <input type="hidden" name="coupon_code" id="buyNowCouponCodeInput" value="">
        </div>
      </div>

      <!-- Tổng tiền -->
      <div class="card mb-3">
        <div class="card-body">
          <div class="d-flex justify-content-between mb-2">
            <span>Tạm tính:</span>
            <span id="buyNowSubtotal"><?php echo number_format((int)$price, 0, ',', '.'); ?>₫</span>
          </div>
          <div class="d-flex justify-content-between mb-2" id="buyNowDiscountRow" style="display:none;">
            <span class="text-success">Giảm giá:</span>
            <span class="text-success" id="buyNowDiscountDisplay">-0₫</span>
          </div>
          <hr>
          <div class="d-flex justify-content-between">
            <div class="fw-semibold">Tổng thanh toán</div>
            <div class="fw-bold text-danger fs-5" id="buyNowTotal">
              <?php echo number_format((int)$price, 0, ',', '.'); ?>₫
            </div>
          </div>
        </div>
      </div>

      <!-- Form thông tin nhận hàng -->
      <div class="card">
        <div class="card-header fw-semibold">Thông tin nhận hàng</div>
        <div class="card-body">
          <form id="buyNowForm" method="post" action="thanhtoan.php">
            <!-- gửi lại sản phẩm -->
            <input type="hidden" name="product_id" value="<?php echo $productId; ?>">
            <input type="hidden" name="name"  value="<?php echo htmlspecialchars($name); ?>">
            <input type="hidden" name="price" value="<?php echo htmlspecialchars($price); ?>">
            <input type="hidden" name="qty"   value="1">
            <input type="hidden" name="mode"  value="buyNow">

            <div class="mb-3">
              <label class="form-label">Họ tên người nhận <span class="text-danger">*</span></label>
              <input name="fullname" class="form-control" required value="<?= htmlspecialchars($userFullname) ?>">
            </div>

            <div class="mb-3">
              <label class="form-label">Số điện thoại <span class="text-danger">*</span></label>
              <input name="phone" type="tel" class="form-control" required placeholder="VD: 0901234567">
            </div>

            <div class="mb-3">
              <label class="form-label">Địa chỉ nhận hàng <span class="text-danger">*</span></label>
              <textarea name="address" class="form-control" rows="2" required placeholder="Số nhà, đường, phường/xã, quận/huyện, tỉnh/thành phố"><?= htmlspecialchars($defaultAddress) ?></textarea>
            </div>

            <div class="mb-3">
              <label class="form-label">Phương thức thanh toán <span class="text-danger">*</span></label>
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
    const buyNowPrice = <?= (int)$price ?>;
    let buyNowDiscount = 0;
    
    document.getElementById('buyNowApplyCoupon')?.addEventListener('click', async function() {
        const code = document.getElementById('buyNowCouponCode').value.trim();
        const messageEl = document.getElementById('buyNowCouponMessage');
        const infoEl = document.getElementById('buyNowCouponInfo');
        const codeInput = document.getElementById('buyNowCouponCodeInput');
        
        if (!code) {
            messageEl.textContent = 'Vui lòng nhập mã khuyến mãi';
            messageEl.className = 'mt-2 small text-danger';
            return;
        }
        
        try {
            const formData = new FormData();
            formData.append('code', code);
            formData.append('total', buyNowPrice);
            
            const response = await fetch('validate_coupon.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                buyNowDiscount = result.discount;
                codeInput.value = code;
                document.getElementById('buyNowCouponCodeText').textContent = code;
                document.getElementById('buyNowDiscountAmount').textContent = 
                    (result.discount || 0).toLocaleString('vi-VN') + '₫';
                
                messageEl.textContent = result.message;
                messageEl.className = 'mt-2 small text-success';
                infoEl.style.display = 'block';
                
                updateBuyNowTotal();
            } else {
                messageEl.textContent = result.message;
                messageEl.className = 'mt-2 small text-danger';
                infoEl.style.display = 'none';
                codeInput.value = '';
                buyNowDiscount = 0;
                updateBuyNowTotal();
            }
        } catch (error) {
            messageEl.textContent = 'Có lỗi xảy ra. Vui lòng thử lại.';
            messageEl.className = 'mt-2 small text-danger';
        }
    });
    
    document.getElementById('buyNowRemoveCoupon')?.addEventListener('click', function() {
        document.getElementById('buyNowCouponCode').value = '';
        document.getElementById('buyNowCouponCodeInput').value = '';
        document.getElementById('buyNowCouponMessage').textContent = '';
        document.getElementById('buyNowCouponInfo').style.display = 'none';
        buyNowDiscount = 0;
        updateBuyNowTotal();
    });
    
    function updateBuyNowTotal() {
        const final = buyNowPrice - buyNowDiscount;
        document.getElementById('buyNowTotal').textContent = 
            (final || 0).toLocaleString('vi-VN') + '₫';
        
        const discountRow = document.getElementById('buyNowDiscountRow');
        const discountDisplay = document.getElementById('buyNowDiscountDisplay');
        
        if (buyNowDiscount > 0) {
            discountRow.style.display = 'flex';
            discountDisplay.textContent = '-' + (buyNowDiscount || 0).toLocaleString('vi-VN') + '₫';
        } else {
            discountRow.style.display = 'none';
        }
    }
    </script>

    <?php include 'footer.php'; ?>
    <?php
    exit;
}

// CASE 2: THANH TOÁN TOÀN BỘ GIỎ HÀNG
$pageTitle = 'Thanh toán giỏ hàng';

// Lấy thông tin user nếu đã đăng nhập
$userFullname = $_SESSION['fullname'] ?? '';
$userEmail = $_SESSION['email'] ?? '';

// Lấy địa chỉ mặc định nếu có
$defaultAddress = '';
if (isset($_SESSION['user_id'])) {
    $stmtAddr = $conn->prepare("SELECT address FROM addresses WHERE user_id = ? AND is_default = 1 LIMIT 1");
    $stmtAddr->execute([$_SESSION['user_id']]);
    $addrRow = $stmtAddr->fetch(PDO::FETCH_ASSOC);
    if ($addrRow) {
        $defaultAddress = $addrRow['address'];
    }
}

$errorMsg = $_SESSION['checkout_error'] ?? '';
unset($_SESSION['checkout_error']);

include 'header.php';
?>

<div class="container py-4" style="max-width:900px">
  <h3 class="mb-3">Thanh toán giỏ hàng</h3>

  <?php if ($errorMsg): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($errorMsg) ?></div>
  <?php endif; ?>

  <!-- Danh sách sản phẩm trong giỏ -->
  <div class="card mb-3">
    <div class="card-body" id="cartList">
      <!-- JS sẽ render -->
    </div>
  </div>

  <!-- Mã khuyến mãi -->
  <div class="card mb-3">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <label class="form-label mb-0 fw-semibold">Mã khuyến mãi</label>
        <a href="coupons.php" class="small text-decoration-none">Xem tất cả mã</a>
      </div>
      <div class="input-group">
        <input type="text" id="cartCouponCode" class="form-control" placeholder="Nhập mã khuyến mãi">
        <button type="button" class="btn btn-outline-primary" id="cartApplyCoupon">Áp dụng</button>
      </div>
      <div id="cartCouponMessage" class="mt-2 small"></div>
      <div id="cartCouponInfo" class="mt-2" style="display:none;">
        <div class="d-flex justify-content-between align-items-center">
          <span class="text-success">
            <i class="bi bi-check-circle"></i> Đã áp dụng mã: <strong id="cartCouponCodeText"></strong>
          </span>
          <button type="button" class="btn btn-sm btn-link text-danger p-0" id="cartRemoveCoupon">Xóa</button>
        </div>
        <div class="text-success small mt-1">
          Giảm: <strong id="cartDiscountAmount">0₫</strong>
        </div>
      </div>
      <input type="hidden" name="coupon_code" id="cartCouponCodeInput" value="">
    </div>
  </div>

  <!-- Tổng tiền -->
  <div class="card mb-3">
    <div class="card-body">
      <div class="d-flex justify-content-between mb-2">
        <span>Tạm tính:</span>
        <span id="cartSubtotal">0₫</span>
      </div>
      <div class="d-flex justify-content-between mb-2" id="cartDiscountRow" style="display:none;">
        <span class="text-success">Giảm giá:</span>
        <span class="text-success" id="cartDiscountDisplay">-0₫</span>
      </div>
      <hr>
      <div class="d-flex justify-content-between">
        <div class="fw-semibold">Tổng thanh toán</div>
        <div class="fw-bold text-danger fs-5" id="cartGrandTotal">0₫</div>
      </div>
    </div>
  </div>

  <!-- Form thông tin nhận hàng -->
  <div class="card">
    <div class="card-header fw-semibold">Thông tin nhận hàng</div>
    <div class="card-body">
      <form id="cartCheckoutForm" method="post" action="thanhtoan.php">
        <input type="hidden" name="mode" value="cart">
        <input type="hidden" name="items" id="cartItemsInput">

        <div class="mb-3">
          <label class="form-label">Họ tên người nhận <span class="text-danger">*</span></label>
          <input name="fullname" class="form-control" required value="<?= htmlspecialchars($userFullname) ?>">
        </div>

        <div class="mb-3">
          <label class="form-label">Số điện thoại <span class="text-danger">*</span></label>
          <input name="phone" type="tel" class="form-control" required placeholder="VD: 0901234567">
        </div>

        <div class="mb-3">
          <label class="form-label">Địa chỉ nhận hàng <span class="text-danger">*</span></label>
          <textarea name="address" class="form-control" rows="2" required placeholder="Số nhà, đường, phường/xã, quận/huyện, tỉnh/thành phố"><?= htmlspecialchars($defaultAddress) ?></textarea>
        </div>

        <div class="mb-3">
          <label class="form-label">Phương thức thanh toán <span class="text-danger">*</span></label>
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
const subtotalEl = document.getElementById('cartSubtotal');
const hiddenInput = document.getElementById('cartItemsInput');

let cartTotal = 0;
let cartDiscount = 0;

if (cartData.length === 0) {
  wrap.innerHTML = '<div class="text-muted">Giỏ hàng đang trống.</div>';
} else {
  wrap.innerHTML = '';
  cartData.forEach(item => {
    const lineTotal = item.price * item.qty;
    cartTotal += lineTotal;

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

function updateCartTotal() {
  const final = cartTotal - cartDiscount;
  subtotalEl.textContent = nf(cartTotal);
  totalEl.textContent = nf(final);
  
  const discountRow = document.getElementById('cartDiscountRow');
  const discountDisplay = document.getElementById('cartDiscountDisplay');
  
  if (cartDiscount > 0) {
    discountRow.style.display = 'flex';
    discountDisplay.textContent = '-' + nf(cartDiscount);
  } else {
    discountRow.style.display = 'none';
  }
}

updateCartTotal();

// Gửi toàn bộ giỏ vào input hidden dưới dạng JSON
hiddenInput.value = JSON.stringify(cartData);

// Xử lý mã khuyến mãi
document.getElementById('cartApplyCoupon')?.addEventListener('click', async function() {
    const code = document.getElementById('cartCouponCode').value.trim();
    const messageEl = document.getElementById('cartCouponMessage');
    const infoEl = document.getElementById('cartCouponInfo');
    const codeInput = document.getElementById('cartCouponCodeInput');
    
    if (!code) {
        messageEl.textContent = 'Vui lòng nhập mã khuyến mãi';
        messageEl.className = 'mt-2 small text-danger';
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('code', code);
        formData.append('total', cartTotal);
        
        const response = await fetch('validate_coupon.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            cartDiscount = result.discount;
            codeInput.value = code;
            document.getElementById('cartCouponCodeText').textContent = code;
            document.getElementById('cartDiscountAmount').textContent = nf(result.discount);
            
            messageEl.textContent = result.message;
            messageEl.className = 'mt-2 small text-success';
            infoEl.style.display = 'block';
            
            updateCartTotal();
        } else {
            messageEl.textContent = result.message;
            messageEl.className = 'mt-2 small text-danger';
            infoEl.style.display = 'none';
            codeInput.value = '';
            cartDiscount = 0;
            updateCartTotal();
        }
    } catch (error) {
        messageEl.textContent = 'Có lỗi xảy ra. Vui lòng thử lại.';
        messageEl.className = 'mt-2 small text-danger';
    }
});

document.getElementById('cartRemoveCoupon')?.addEventListener('click', function() {
    document.getElementById('cartCouponCode').value = '';
    document.getElementById('cartCouponCodeInput').value = '';
    document.getElementById('cartCouponMessage').textContent = '';
    document.getElementById('cartCouponInfo').style.display = 'none';
    cartDiscount = 0;
    updateCartTotal();
});
</script>

<?php include 'footer.php'; ?>
