<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Giỏ hàng của bạn</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .cart-thumb{
      width:70px;
      height:70px;
      object-fit:cover;
      border-radius:8px;
      border:1px solid #eee;
    }
  </style>
</head>
<body class="bg-light">

<div class="container py-4" style="max-width:960px">
  <h3 class="mb-3">Giỏ hàng</h3>

  <div id="cartEmpty" class="alert alert-info d-none">
    Giỏ hàng đang trống.  
    <a href="trangchu.php" class="alert-link">Tiếp tục mua sắm</a>
  </div>

  <div id="cartWrap" class="card d-none">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table align-middle">
          <thead class="table-light">
            <tr>
              <th>Sản phẩm</th>
              <th style="width:130px">Giá</th>
              <th style="width:120px">Số lượng</th>
              <th style="width:130px">Thành tiền</th>
              <th style="width:80px"></th>
            </tr>
          </thead>
          <tbody id="cartBody">
          <!-- JS render -->
          </tbody>
        </table>
      </div>

      <div class="d-flex justify-content-between align-items-center mt-3">
        <div>
          <button id="btnClearAll" class="btn btn-outline-danger btn-sm">
            Xóa toàn bộ giỏ hàng
          </button>
        </div>
        <div class="text-end">
          <div class="fw-semibold mb-1">
            Tổng tiền: <span id="cartTotal" class="text-danger fs-5">0₫</span>
          </div>
          <div class="mt-2">
            <a href="trangchu.php" class="btn btn-outline-secondary btn-sm me-2">Tiếp tục mua</a>
            <a href="thanhtoan.php" class="btn btn-dark btn-sm">Tiến hành thanh toán</a>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
function nf(n){ return (n||0).toLocaleString('vi-VN') + '₫'; }

function getCart(){
  return JSON.parse(localStorage.getItem('cart') || '[]');
}
function saveCart(cart){
  localStorage.setItem('cart', JSON.stringify(cart));
}

const cartBody   = document.getElementById('cartBody');
const cartTotal  = document.getElementById('cartTotal');
const cartEmpty  = document.getElementById('cartEmpty');
const cartWrap   = document.getElementById('cartWrap');
const btnClearAll= document.getElementById('btnClearAll');

function renderCartPage(){
  let cart = getCart();

  if (!cart.length){
    cartEmpty.classList.remove('d-none');
    cartWrap.classList.add('d-none');
    return;
  }

  cartEmpty.classList.add('d-none');
  cartWrap.classList.remove('d-none');

  cartBody.innerHTML = '';
  let total = 0;

  cart.forEach((item, idx) => {
    const lineTotal = item.price * item.qty;
    total += lineTotal;

    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>
        <div class="d-flex align-items-center gap-3">
          <img src="${item.img}" class="cart-thumb" alt="">
          <div>${item.name}</div>
        </div>
      </td>
      <td>${nf(item.price)}</td>
      <td>
        <div class="d-flex align-items-center gap-2">
          <button class="btn btn-sm btn-outline-secondary btn-minus">-</button>
          <span class="cart-qty">${item.qty}</span>
          <button class="btn btn-sm btn-outline-secondary btn-plus">+</button>
        </div>
      </td>
      <td class="fw-semibold text-danger">${nf(lineTotal)}</td>
      <td>
        <button class="btn btn-sm btn-outline-danger btn-remove">
          Xóa
        </button>
      </td>
    `;

    tr.querySelector('.btn-minus').onclick = () => {
      let cart = getCart();
      if (cart[idx].qty > 1) cart[idx].qty -= 1;
      else cart.splice(idx,1);
      saveCart(cart);
      renderCartPage();
    };

    tr.querySelector('.btn-plus').onclick = () => {
      let cart = getCart();
      cart[idx].qty += 1;
      saveCart(cart);
      renderCartPage();
    };

    tr.querySelector('.btn-remove').onclick = () => {
      let cart = getCart();
      cart.splice(idx,1);
      saveCart(cart);
      renderCartPage();
    };

    cartBody.appendChild(tr);
  });

  cartTotal.textContent = nf(total);
}

btnClearAll.onclick = () => {
  if (confirm('Bạn có chắc muốn xóa toàn bộ giỏ hàng?')){
    localStorage.removeItem('cart');
    renderCartPage();
  }
};

renderCartPage();
</script>

</body>
</html>
